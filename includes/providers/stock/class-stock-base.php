<?php
/**
 * Abstract base for stock photo sources.
 *
 * @package AIISP
 */

namespace AIISP\Providers\Stock;

use WP_Error;

// Security check: block direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shared plumbing for every stock source. Concrete sources implement
 * search() and return a normalized result array.
 */
abstract class Stock_Base {

	/**
	 * Machine slug.
	 *
	 * @return string
	 */
	abstract public function get_slug();

	/**
	 * Human readable label.
	 *
	 * @return string
	 */
	abstract public function get_label();

	/**
	 * Run a search and return normalized items:
	 * [ [ 'id' =>, 'thumb' =>, 'full' =>, 'credit' => ], ... ].
	 *
	 * @param string $query    Search term (already sanitized).
	 * @param int    $per_page Result count (already clamped).
	 * @return array|WP_Error
	 */
	abstract public function search( $query, $per_page );

	/**
	 * Whether the source needs an API key. Keyless sources override.
	 *
	 * @return bool
	 */
	public function requires_api_key() {
		return true;
	}

	/**
	 * URL where the admin can obtain an API key.
	 *
	 * @return string
	 */
	public function get_key_url() {
		return '';
	}

	/**
	 * Temporary key override used while testing an unsaved key.
	 *
	 * @var string
	 */
	protected $key_override = '';

	/**
	 * Read this source's key from the dynamic "{slug}_api_key" option.
	 * A test-time override takes precedence.
	 *
	 * @return string
	 */
	protected function get_api_key() {
		if ( '' !== $this->key_override ) {
			return $this->key_override;
		}
		return (string) aiisp()->options()->get( $this->get_slug() . '_api_key' );
	}

	/**
	 * Validate a key by running a minimal one-result search with it.
	 *
	 * @param string $key Key to test.
	 * @return true|WP_Error
	 */
	public function test_key( $key ) {
		// Check: a keyless source has nothing to test.
		if ( ! $this->requires_api_key() ) {
			return true;
		}

		// Check: an empty key can never be valid.
		if ( '' === trim( (string) $key ) ) {
			return new WP_Error( 'aiisp_test_empty', __( 'Enter a key before testing.', 'cubixsol-multi-ai-image-generator' ) );
		}

		$this->key_override = trim( (string) $key );

		// per_page is 3, NOT 1: Pixabay's API rejects per_page < 3
		// with HTTP 400, which previously made valid keys look
		// invalid. 3 is accepted by every source.
		// The query carries a random suffix so the request URL is
		// unique — otherwise a cache between this site and the API
		// can replay an earlier 200 and validate a garbage key.
		$result = $this->search( 'nature ' . wp_rand( 1000, 9999 ), 3 );

		$this->key_override = '';

		return is_wp_error( $result ) ? $result : true;
	}

	/**
	 * Whether the source is ready to query.
	 *
	 * @return bool
	 */
	public function is_configured() {
		return ! $this->requires_api_key() || '' !== $this->get_api_key();
	}

	/**
	 * Shared GET helper with full response validation.
	 *
	 * @param string $url     Request URL.
	 * @param array  $headers Optional headers.
	 * @return array|WP_Error Decoded JSON body.
	 */
	protected function get_json( $url, $headers = array() ) {
		$response = wp_remote_get(
			esc_url_raw( $url ),
			array(
				'timeout' => 30,
				// no-cache: never let a proxy replay a previous
				// response for a differently-authenticated request.
				'headers' => array_merge(
					array(
						'Cache-Control' => 'no-cache',
						'Pragma'        => 'no-cache',
					),
					$headers
				),
			)
		);

		// Check: transport failure.
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		// Check: HTTP status, translated into plain language for the
		// statuses admins actually hit.
		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			switch ( $code ) {
				case 401:
				case 403:
					$message = sprintf(
						/* translators: %s: source label */
						__( '%s rejected this key (unauthorized). Double-check it and try again.', 'cubixsol-multi-ai-image-generator' ),
						$this->get_label()
					);
					break;
				case 400:
					// Pixabay (and some others) report invalid keys as
					// HTTP 400 rather than 401.
					$message = sprintf(
						/* translators: %s: source label */
						__( '%s rejected the request (HTTP 400) — this usually means the API key is invalid.', 'cubixsol-multi-ai-image-generator' ),
						$this->get_label()
					);
					break;
				case 429:
					$message = sprintf(
						/* translators: %s: source label */
						__( '%s rate limit reached — wait a moment and try again.', 'cubixsol-multi-ai-image-generator' ),
						$this->get_label()
					);
					break;
				default:
					$message = sprintf(
						/* translators: 1: source label, 2: HTTP status code */
						__( '%1$s returned HTTP status %2$d.', 'cubixsol-multi-ai-image-generator' ),
						$this->get_label(),
						$code
					);
			}

			return new WP_Error( 'aiisp_stock_http_' . $code, $message );
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		// Check: valid JSON payload.
		if ( ! is_array( $data ) ) {
			return new WP_Error(
				'aiisp_stock_json',
				sprintf(
					/* translators: %s: source label */
					__( '%s returned a malformed response.', 'cubixsol-multi-ai-image-generator' ),
					$this->get_label()
				)
			);
		}

		return $data;
	}

	/**
	 * Require the decoded response to contain the field a genuine
	 * search result from this source always has. An HTTP 200 without
	 * it (error bodies, HTML behind proxies decoded as JSON, etc.)
	 * must NOT be treated as proof of a valid key.
	 *
	 * @param array  $data  Decoded response body.
	 * @param string $field Expected top-level field.
	 * @return true|WP_Error
	 */
	protected function require_field( $data, $field ) {
		if ( is_array( $data ) && array_key_exists( $field, $data ) ) {
			return true;
		}

		return new WP_Error(
			'aiisp_stock_shape',
			sprintf(
				/* translators: %s: source label */
				__( '%s returned an unexpected response — the API key is likely invalid.', 'cubixsol-multi-ai-image-generator' ),
				$this->get_label()
			)
		);
	}

	/**
	 * Build one normalized result item with per-field sanitization.
	 *
	 * @param string $id     Source item ID.
	 * @param string $thumb  Thumbnail URL.
	 * @param string $full   Full-size URL.
	 * @param string $credit Attribution line.
	 * @return array
	 */
	protected function item( $id, $thumb, $full, $credit ) {
		return array(
			'id'     => sanitize_text_field( (string) $id ),
			'thumb'  => esc_url_raw( (string) $thumb ),
			'full'   => esc_url_raw( (string) $full ),
			'credit' => sanitize_text_field( (string) $credit ),
		);
	}

	/**
	 * Standard "missing key" error.
	 *
	 * @return WP_Error
	 */
	protected function missing_key_error() {
		return new WP_Error(
			'aiisp_stock_key',
			sprintf(
				/* translators: %s: source label */
				__( '%s requires an API key. Add one under AI Image Workspace → Providers.', 'cubixsol-multi-ai-image-generator' ),
				$this->get_label()
			)
		);
	}
}
