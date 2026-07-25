<?php
/**
 * OpenAI image engine (DALL-E 3 endpoint).
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
 * Calls the OpenAI /v1/images/generations endpoint.
 */
class Openai extends Provider_Base {

	/** API endpoint. */
	const ENDPOINT = 'https://api.openai.com/v1/images/generations';

	/** {@inheritDoc} */
	public function get_slug() {
		return 'openai';
	}

	/** {@inheritDoc} */
	public function get_label() {
		return __( 'OpenAI (DALL-E 3)', 'cubixsol-multi-ai-image-generator' );
	}

	/** {@inheritDoc} */
	public function get_description() {
		return __( 'High quality, strong prompt understanding. Paid API key required.', 'cubixsol-multi-ai-image-generator' );
	}

	/** {@inheritDoc} */
	public function get_key_url() {
		return 'https://platform.openai.com/api-keys';
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

		// Map the global size to the nearest OpenAI-supported one.
		$width  = isset( $args['width'] ) ? absint( $args['width'] ) : 1024;
		$height = isset( $args['height'] ) ? absint( $args['height'] ) : 1024;
		$size   = '1024x1024';
		if ( $width > $height ) {
			$size = '1792x1024';
		} elseif ( $height > $width ) {
			$size = '1024x1792';
		}

		$data = $this->post_json(
			self::ENDPOINT,
			array(
				'model'           => 'dall-e-3',
				'prompt'          => $prompt,
				'n'               => 1,
				'size'            => $size,
				'response_format' => 'b64_json',
			),
			array( 'Authorization' => 'Bearer ' . $this->get_api_key() )
		);

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		// Check: expected payload shape.
		if ( empty( $data['data'][0]['b64_json'] ) ) {
			return new WP_Error( 'aiisp_openai_empty', __( 'OpenAI returned an empty image payload.', 'cubixsol-multi-ai-image-generator' ) );
		}

		return array(
			'base64' => (string) $data['data'][0]['b64_json'],
			'mime'   => 'image/png',
		);
	}

	/**
	 * Probe the models list — free, read-only, requires valid auth.
	 *
	 * @return true|\WP_Error
	 */
	protected function verify_credentials() {
		return $this->auth_probe(
			'https://api.openai.com/v1/models',
			array( 'Authorization' => 'Bearer ' . $this->get_api_key() )
		);
	}
}
