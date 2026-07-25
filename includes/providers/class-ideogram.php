<?php
/**
 * Ideogram engine — best-in-class text rendering inside images.
 *
 * Uses the current v3 API (v1/ideogram-v3/generate, multipart). The
 * legacy /generate endpoint validates request bodies before checking
 * authentication, which made key testing unreliable, and is slated
 * for retirement.
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
 * Calls the Ideogram v3 generate endpoint.
 */
class Ideogram extends Provider_Base {

	/** API endpoint (v3). */
	const ENDPOINT = 'https://api.ideogram.ai/v1/ideogram-v3/generate';

	/** {@inheritDoc} */
	public function get_slug() {
		return 'ideogram';
	}

	/** {@inheritDoc} */
	public function get_label() {
		return __( 'Ideogram', 'cubixsol-multi-ai-image-generator' );
	}

	/** {@inheritDoc} */
	public function get_description() {
		return __( 'Excellent at rendering readable text inside images. Key required.', 'cubixsol-multi-ai-image-generator' );
	}

	/** {@inheritDoc} */
	public function get_key_url() {
		return 'https://ideogram.ai/manage-api';
	}

	/** {@inheritDoc} */
	public function get_supported_sizes() {
		return array( '1024x1024', '1792x1024', '1024x1792' );
	}

	/** {@inheritDoc} */
	public function generate( $prompt, $args = array() ) {
		// Check: key must be present before any request is made.
		if ( ! $this->is_configured() ) {
			return $this->missing_key_error();
		}

		// Map the requested shape onto Ideogram v3's aspect enum.
		$aspect = '1x1';
		if ( '16:9' === $this->aspect_from_args( $args ) ) {
			$aspect = '16x9';
		} elseif ( '9:16' === $this->aspect_from_args( $args ) ) {
			$aspect = '9x16';
		}

		// v3 expects multipart/form-data; built manually so no extra
		// HTTP library is required.
		$boundary = wp_generate_password( 24, false );
		$fields   = array(
			'prompt'          => $prompt,
			'aspect_ratio'    => $aspect,
			'rendering_speed' => 'TURBO',
			'num_images'      => '1',
		);

		$body = '';
		foreach ( $fields as $name => $value ) {
			$body .= "--{$boundary}\r\n";
			$body .= "Content-Disposition: form-data; name=\"{$name}\"\r\n\r\n";
			$body .= "{$value}\r\n";
		}
		$body .= "--{$boundary}--\r\n";

		$response = wp_remote_post(
			self::ENDPOINT,
			array(
				'timeout' => 120,
				'headers' => array(
					'Api-Key'      => $this->get_api_key(),
					'Content-Type' => 'multipart/form-data; boundary=' . $boundary,
				),
				'body'    => $body,
			)
		);

		// Check: transport failure.
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		// Check: HTTP status with hint + provider error detail.
		if ( $code < 200 || $code >= 300 ) {
			$detail = '';
			if ( is_array( $data ) ) {
				if ( isset( $data['error'] ) && is_string( $data['error'] ) ) {
					$detail = $data['error'];
				} elseif ( isset( $data['message'] ) && is_string( $data['message'] ) ) {
					$detail = $data['message'];
				}
			}
			if ( '' === $detail ) {
				$detail = sprintf( 'HTTP %d', $code );
			}

			return new WP_Error(
				'aiisp_ideogram_http',
				sanitize_text_field( trim( 'Ideogram: ' . $this->http_hint( $code ) . ' ' . $detail ) )
			);
		}

		// Check: expected payload shape.
		if ( ! is_array( $data ) || empty( $data['data'][0]['url'] ) ) {
			return new WP_Error( 'aiisp_ideogram_empty', __( 'Ideogram returned an empty image payload.', 'cubixsol-multi-ai-image-generator' ) );
		}

		return array( 'url' => esc_url_raw( (string) $data['data'][0]['url'] ) );
	}

	/**
	 * Strict probe against the v3 endpoint (no lenient shortcut).
	 *
	 * Ideogram validates request bodies before authentication, so a
	 * 400 response proves nothing about the key. Only an explicit
	 * 401/403 marks the key invalid; every other non-2xx outcome is
	 * reported as "unverified" rather than falsely confirmed.
	 *
	 * @return true|\WP_Error
	 */
	protected function verify_credentials() {
		return $this->auth_probe(
			self::ENDPOINT,
			array( 'Api-Key' => $this->get_api_key() ),
			'POST',
			array(),
			false
		);
	}
}
