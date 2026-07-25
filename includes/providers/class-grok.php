<?php
/**
 * Grok / xAI image engine (OpenAI-compatible API).
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
 * Calls the xAI /v1/images/generations endpoint.
 */
class Grok extends Provider_Base {

	/** API endpoint. */
	const ENDPOINT = 'https://api.x.ai/v1/images/generations';

	/** {@inheritDoc} */
	public function get_slug() {
		return 'grok';
	}

	/** {@inheritDoc} */
	public function get_label() {
		return __( 'Grok / xAI', 'cubixsol-multi-ai-image-generator' );
	}

	/** {@inheritDoc} */
	public function get_description() {
		return __( 'xAI image model with an OpenAI-compatible API. Key required.', 'cubixsol-multi-ai-image-generator' );
	}

	/** {@inheritDoc} */
	public function get_key_url() {
		return 'https://console.x.ai/';
	}

	/** {@inheritDoc} */
	public function get_supported_sizes() {
		return array( '1024x1024' );
	}

	/** {@inheritDoc} */
	public function generate( $prompt, $args = array() ) {
		// Check: key must be present before any request is made.
		if ( ! $this->is_configured() ) {
			return $this->missing_key_error();
		}

		$data = $this->post_json(
			self::ENDPOINT,
			array(
				'model'           => 'grok-2-image',
				'prompt'          => $prompt,
				'n'               => 1,
				'response_format' => 'b64_json',
			),
			array( 'Authorization' => 'Bearer ' . $this->get_api_key() )
		);

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		// Check: accept either payload shape the API may return.
		if ( ! empty( $data['data'][0]['b64_json'] ) ) {
			return array(
				'base64' => (string) $data['data'][0]['b64_json'],
				'mime'   => 'image/jpeg',
			);
		}

		if ( ! empty( $data['data'][0]['url'] ) ) {
			return array( 'url' => esc_url_raw( (string) $data['data'][0]['url'] ) );
		}

		return new WP_Error( 'aiisp_grok_empty', __( 'Grok returned an empty image payload.', 'cubixsol-multi-ai-image-generator' ) );
	}

	/**
	 * Probe the models list — free, read-only, requires valid auth.
	 *
	 * @return true|\WP_Error
	 */
	protected function verify_credentials() {
		return $this->auth_probe(
			'https://api.x.ai/v1/models',
			array( 'Authorization' => 'Bearer ' . $this->get_api_key() )
		);
	}
}
