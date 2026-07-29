<?php
/**
 * Abstract base for every AI image engine.
 *
 * Holds all shared behaviour (key lookup, HTTP helpers, JSON error
 * extraction, size mapping) so each concrete engine only implements
 * its own request/response shape.
 *
 * @package AIISP
 */

namespace AIISP\Providers;

use WP_Error;

// Security check: block direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shared engine plumbing. Concrete engines extend this class.
 */
abstract class Provider_Base {

	/**
	 * Machine slug (unique across the registry).
	 *
	 * @return string
	 */
	abstract public function get_slug();

	/**
	 * Human readable label shown in the UI.
	 *
	 * @return string
	 */
	abstract public function get_label();

	/**
	 * Short one-line description shown on the provider card.
	 *
	 * @return string
	 */
	abstract public function get_description();

	/**
	 * Sizes supported by the engine as "WIDTHxHEIGHT" strings.
	 *
	 * @return string[]
	 */
	abstract public function get_supported_sizes();

	/**
	 * Generate an image.
	 *
	 * @param string $prompt Sanitized prompt.
	 * @param array  $args   { width:int, height:int, style:string }.
	 * @return array|WP_Error [ 'url' => string ] or [ 'base64' => string, 'mime' => string ].
	 */
	abstract public function generate( $prompt, $args = array() );

	/**
	 * Whether the engine needs an API key. Free engines override this.
	 *
	 * @return bool
	 */
	public function requires_api_key() {
		return true;
	}

	/**
	 * URL where the admin can obtain an API key (shown on the card).
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
	 * Read this engine's API key from the dynamic option field
	 * "{slug}_api_key" created by the Options schema. A test-time
	 * override takes precedence so unsaved keys can be validated.
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
	 * Validate an API key against the live service without running a
	 * full (billable) generation.
	 *
	 * @param string $key Key to test.
	 * @return true|WP_Error True when the key authenticates.
	 */
	public function test_key( $key ) {
		// Check: a keyless engine has nothing to test.
		if ( ! $this->requires_api_key() ) {
			return true;
		}

		// Check: an empty key can never be valid.
		if ( '' === trim( (string) $key ) ) {
			return new WP_Error( 'aiisp_test_empty', __( 'Enter a key before testing.', 'cubixsol-multi-ai-image-generator' ) );
		}

		$this->key_override = trim( (string) $key );
		$result             = $this->verify_credentials();
		$this->key_override = '';

		return $result;
	}

	/**
	 * Engine-specific credential probe. Overridden by each engine.
	 *
	 * @return true|WP_Error
	 */
	protected function verify_credentials() {
		return new WP_Error( 'aiisp_test_unsupported', __( 'Key testing is not available for this engine.', 'cubixsol-multi-ai-image-generator' ) );
	}

	/**
	 * Shared auth probe: request a lightweight endpoint and decide
	 * validity from the HTTP status.
	 *
	 *  - 2xx        → key valid
	 *  - 401 / 403  → key rejected
	 *  - 400 / 422  → auth passed, payload rejected — valid when
	 *                 $lenient (used for engines with no cheap
	 *                 read-only endpoint)
	 *
	 * @param string     $url     Probe URL.
	 * @param array      $headers Request headers.
	 * @param string     $method  GET or POST.
	 * @param mixed      $body    Optional body for POST probes.
	 * @param bool       $lenient Treat 400/422 as valid.
	 * @return true|WP_Error
	 */
	protected function auth_probe( $url, $headers = array(), $method = 'GET', $body = null, $lenient = false ) {
		$args = array(
			'timeout' => 20,
			'method'  => 'POST' === $method ? 'POST' : 'GET',
			'headers' => $headers,
		);

		if ( null !== $body ) {
			$args['headers']['Content-Type'] = 'application/json';
			$args['body']                    = wp_json_encode( $body );
		}

		$response = wp_remote_request( esc_url_raw( $url ), $args );

		// Check: transport failure means the test is inconclusive.
		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'aiisp_test_transport', $response->get_error_message() );
		}

		$code = (int) wp_remote_retrieve_response_code( $response );

		if ( $code >= 200 && $code < 300 ) {
			return true;
		}

		if ( 401 === $code || 403 === $code ) {
			return new WP_Error(
				'aiisp_test_invalid',
				sprintf(
					/* translators: %s: provider label */
					__( '%s rejected this key (unauthorized).', 'cubixsol-multi-ai-image-generator' ),
					$this->get_label()
				)
			);
		}

		if ( $lenient && in_array( $code, array( 400, 422 ), true ) ) {
			return true; // Auth accepted; only the intentionally minimal payload was rejected.
		}

		return new WP_Error(
			'aiisp_test_inconclusive',
			sprintf(
				/* translators: 1: provider label, 2: HTTP status */
				__( '%1$s does not allow confirming a key without running a real generation (probe returned HTTP %2$d). The key was NOT validated — it will be checked on your first generation.', 'cubixsol-multi-ai-image-generator' ),
				$this->get_label(),
				$code
			)
		);
	}

	/**
	 * Whether the engine is ready to run (key present when required).
	 *
	 * @return bool
	 */
	public function is_configured() {
		return ! $this->requires_api_key() || '' !== $this->get_api_key();
	}

	/* ---------------------------------------------------------------------
	 * Shared HTTP helpers
	 * ------------------------------------------------------------------ */

	/**
	 * POST JSON to an endpoint and return the decoded response body.
	 *
	 * Centralizes timeout, error, HTTP-status and JSON-shape checks so
	 * every engine gets identical, battle-tested handling.
	 *
	 * @param string $url     Endpoint.
	 * @param array  $body    Request payload (will be JSON-encoded).
	 * @param array  $headers Extra headers.
	 * @return array|WP_Error Decoded array on success.
	 */
	protected function post_json( $url, $body, $headers = array() ) {
		$response = wp_remote_post(
			esc_url_raw( $url ),
			array(
				'timeout' => 120,
				'headers' => array_merge( array( 'Content-Type' => 'application/json' ), $headers ),
				'body'    => wp_json_encode( $body ),
			)
		);

		return $this->validate_response( $response );
	}

	/**
	 * GET a JSON endpoint and return the decoded response body.
	 *
	 * @param string $url     Endpoint.
	 * @param array  $headers Extra headers.
	 * @return array|WP_Error
	 */
	protected function get_json( $url, $headers = array() ) {
		$response = wp_remote_get(
			esc_url_raw( $url ),
			array(
				'timeout' => 60,
				'headers' => $headers,
			)
		);

		return $this->validate_response( $response );
	}

	/**
	 * Shared response validation: transport error check, HTTP status
	 * check, empty-body check and JSON decode check.
	 *
	 * @param array|WP_Error $response Raw wp_remote_* response.
	 * @return array|WP_Error
	 */
	private function validate_response( $response ) {
		// Check 1: transport-level failure (DNS, timeout, SSL...).
		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'aiisp_transport',
				sprintf(
					/* translators: 1: provider label, 2: error detail */
					__( '%1$s request failed: %2$s', 'cubixsol-multi-ai-image-generator' ),
					$this->get_label(),
					$response->get_error_message()
				)
			);
		}

		$code = wp_remote_retrieve_response_code( $response );
		$raw  = wp_remote_retrieve_body( $response );

		// Check 2: empty body.
		if ( '' === $raw ) {
			return new WP_Error(
				'aiisp_empty_body',
				sprintf(
					/* translators: %s: provider label */
					__( '%s returned an empty response.', 'cubixsol-multi-ai-image-generator' ),
					$this->get_label()
				)
			);
		}

		$data = json_decode( $raw, true );

		// Check 3: non-2xx HTTP status — surface the provider's own
		// error message when it sent one, plus a plain-language hint
		// for the statuses admins hit most often.
		if ( $code < 200 || $code >= 300 ) {
			$detail = $this->extract_error_message( $data );
			$hint   = $this->http_hint( $code );

			return new WP_Error(
				'aiisp_http_' . $code,
				sprintf(
					/* translators: 1: provider label, 2: HTTP code, 3: detail */
					__( '%1$s error (HTTP %2$d): %3$s', 'cubixsol-multi-ai-image-generator' ),
					$this->get_label(),
					$code,
					trim( $hint . ' ' . $detail )
				)
			);
		}

		// Check 4: body must decode to an array.
		if ( ! is_array( $data ) ) {
			return new WP_Error(
				'aiisp_bad_json',
				sprintf(
					/* translators: %s: provider label */
					__( '%s returned a malformed JSON response.', 'cubixsol-multi-ai-image-generator' ),
					$this->get_label()
				)
			);
		}

		return $data;
	}

	/**
	 * Plain-language hint for the HTTP statuses admins hit most often,
	 * so errors explain themselves instead of just showing a code.
	 *
	 * @param int $code HTTP status code.
	 * @return string Hint (may be empty).
	 */
	protected function http_hint( $code ) {
		switch ( (int) $code ) {
			case 401:
			case 403:
				return __( 'The API rejected your key — re-check it on the AI Engines tab.', 'cubixsol-multi-ai-image-generator' );
			case 402:
				return __( 'Your account has no credits — add billing/credits on the provider dashboard.', 'cubixsol-multi-ai-image-generator' );
			case 429:
				return __( 'Rate limit or quota reached — wait a moment or upgrade your plan.', 'cubixsol-multi-ai-image-generator' );
		}
		return '';
	}

	/**
	 * Pull a human-readable error string out of the many shapes the
	 * different providers use for error payloads.
	 *
	 * @param mixed $data Decoded response body (may be null).
	 * @return string
	 */
	private function extract_error_message( $data ) {
		if ( ! is_array( $data ) ) {
			return __( 'No error detail returned.', 'cubixsol-multi-ai-image-generator' );
		}

		// Common shapes: {error:{message}}, {error:"..."}, {message}, {errors:[]}, {detail}.
		if ( isset( $data['error']['message'] ) ) {
			return sanitize_text_field( (string) $data['error']['message'] );
		}
		if ( isset( $data['error'] ) && is_string( $data['error'] ) ) {
			return sanitize_text_field( $data['error'] );
		}
		if ( isset( $data['message'] ) && is_string( $data['message'] ) ) {
			return sanitize_text_field( $data['message'] );
		}
		if ( isset( $data['detail'] ) && is_string( $data['detail'] ) ) {
			return sanitize_text_field( $data['detail'] );
		}
		if ( isset( $data['errors'] ) && is_array( $data['errors'] ) ) {
			return sanitize_text_field( implode( '; ', array_map( 'strval', $data['errors'] ) ) );
		}

		return __( 'Unrecognized error payload.', 'cubixsol-multi-ai-image-generator' );
	}

	/**
	 * Standard "missing key" error used by every premium engine.
	 *
	 * @return WP_Error
	 */
	protected function missing_key_error() {
		return new WP_Error(
			'aiisp_missing_key',
			sprintf(
				/* translators: %s: provider label */
				__( '%s requires an API key. Add one under AI Image Workspace → Providers.', 'cubixsol-multi-ai-image-generator' ),
				$this->get_label()
			)
		);
	}

	/**
	 * Resolve requested width/height into an aspect keyword many
	 * modern APIs use ('1:1', '16:9', '9:16').
	 *
	 * @param array $args Generation args.
	 * @return string
	 */
	protected function aspect_from_args( $args ) {
		$width  = isset( $args['width'] ) ? absint( $args['width'] ) : 1024;
		$height = isset( $args['height'] ) ? absint( $args['height'] ) : 1024;

		if ( $width > $height ) {
			return '16:9';
		}
		if ( $height > $width ) {
			return '9:16';
		}
		return '1:1';
	}
}
