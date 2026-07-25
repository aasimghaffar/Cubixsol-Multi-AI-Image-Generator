<?php
/**
 * Stability AI engine (Stable Image Core v2beta).
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
 * Calls the Stability AI stable-image/generate/core endpoint.
 */
class Stability extends Provider_Base {

	/** API endpoint. */
	const ENDPOINT = 'https://api.stability.ai/v2beta/stable-image/generate/core';

	/** {@inheritDoc} */
	public function get_slug() {
		return 'stability';
	}

	/** {@inheritDoc} */
	public function get_label() {
		return __( 'Stability AI', 'cubixsol-multi-ai-image-generator' );
	}

	/** {@inheritDoc} */
	public function get_description() {
		return __( 'Stable Diffusion 3 family, great artistic range. Key required.', 'cubixsol-multi-ai-image-generator' );
	}

	/** {@inheritDoc} */
	public function get_key_url() {
		return 'https://platform.stability.ai/account/keys';
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

		// The endpoint requires multipart/form-data, built manually so
		// no external HTTP library is needed.
		$boundary = wp_generate_password( 24, false );
		$fields = array(
			'prompt'        => $prompt,
			'aspect_ratio'  => $this->aspect_from_args( $args ),
			'output_format' => 'png',
		);

		// Check: include the negative prompt only when one is set —
		// Stability rejects empty negative_prompt values.
		if ( ! empty( $args['negative'] ) ) {
			$fields['negative_prompt'] = (string) $args['negative'];
		}

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
					'Authorization' => 'Bearer ' . $this->get_api_key(),
					'Accept'        => 'application/json',
					'Content-Type'  => 'multipart/form-data; boundary=' . $boundary,
				),
				'body'    => $body,
			)
		);

		// Check: transport failure.
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		// Check: HTTP status with provider error detail plus hint.
		if ( 200 !== $code ) {
			$detail = ( is_array( $data ) && isset( $data['errors'] ) )
				? implode( '; ', array_map( 'strval', (array) $data['errors'] ) )
				: sprintf( 'HTTP %d', $code );
			return new WP_Error(
				'aiisp_stability_http',
				sanitize_text_field( trim( $this->http_hint( $code ) . ' ' . $detail ) )
			);
		}

		// Check: expected payload shape.
		if ( ! is_array( $data ) || empty( $data['image'] ) ) {
			return new WP_Error( 'aiisp_stability_empty', __( 'Stability AI returned an empty image payload.', 'cubixsol-multi-ai-image-generator' ) );
		}

		return array(
			'base64' => (string) $data['image'],
			'mime'   => 'image/png',
		);
	}

	/**
	 * Probe the account endpoint — free, read-only, requires auth.
	 *
	 * @return true|\WP_Error
	 */
	protected function verify_credentials() {
		return $this->auth_probe(
			'https://api.stability.ai/v1/user/account',
			array( 'Authorization' => 'Bearer ' . $this->get_api_key() )
		);
	}
}
