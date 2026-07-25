<?php
/**
 * DeepAI text-to-image engine — cheap and simple.
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
 * Calls the DeepAI text2img endpoint (form-encoded).
 */
class Deepai extends Provider_Base {

	/** API endpoint. */
	const ENDPOINT = 'https://api.deepai.org/api/text2img';

	/** {@inheritDoc} */
	public function get_slug() {
		return 'deepai';
	}

	/** {@inheritDoc} */
	public function get_label() {
		return __( 'DeepAI', 'cubixsol-multi-ai-image-generator' );
	}

	/** {@inheritDoc} */
	public function get_description() {
		return __( 'Simple API with low-cost paid credits (no free tier). Key required.', 'cubixsol-multi-ai-image-generator' );
	}

	/** {@inheritDoc} */
	public function get_key_url() {
		return 'https://deepai.org/dashboard/profile';
	}

	/** {@inheritDoc} */
	public function get_supported_sizes() {
		return array( '512x512', '1024x1024' );
	}

	/** {@inheritDoc} */
	public function generate( $prompt, $args = array() ) {
		// Check: key must be present before any request is made.
		if ( ! $this->is_configured() ) {
			return $this->missing_key_error();
		}

		// DeepAI expects application/x-www-form-urlencoded, so this
		// engine posts a plain array instead of using post_json().
		$response = wp_remote_post(
			self::ENDPOINT,
			array(
				'timeout' => 120,
				'headers' => array( 'api-key' => $this->get_api_key() ),
				'body'    => array( 'text' => $prompt ),
			)
		);

		// Check: transport failure.
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		// Check: HTTP status with provider error detail plus a
		// plain-language hint (402 = account out of credits, etc.).
		if ( 200 !== $code ) {
			$detail = ( is_array( $data ) && isset( $data['err'] ) ) ? (string) $data['err'] : sprintf( 'HTTP %d', $code );
			return new WP_Error(
				'aiisp_deepai_http',
				sanitize_text_field( trim( $this->http_hint( $code ) . ' ' . $detail ) )
			);
		}

		// Check: expected payload shape.
		if ( ! is_array( $data ) || empty( $data['output_url'] ) ) {
			return new WP_Error( 'aiisp_deepai_empty', __( 'DeepAI returned an empty image payload.', 'cubixsol-multi-ai-image-generator' ) );
		}

		return array( 'url' => esc_url_raw( (string) $data['output_url'] ) );
	}

	/**
	 * DeepAI exposes no free read endpoint, so probe text2img with an
	 * empty payload: 401/403 = bad key, 400/422 = auth passed.
	 *
	 * @return true|\WP_Error
	 */
	protected function verify_credentials() {
		return $this->auth_probe(
			self::ENDPOINT,
			array( 'api-key' => $this->get_api_key() ),
			'POST',
			array(),
			true
		);
	}
}
