<?php
/**
 * FLUX engine (Black Forest Labs models served via Together AI's
 * OpenAI-compatible images endpoint).
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
 * Calls the Together AI /v1/images/generations endpoint with a
 * FLUX.1-schnell model.
 */
class Flux extends Provider_Base {

	/** API endpoint. */
	const ENDPOINT = 'https://api.together.xyz/v1/images/generations';

	/** {@inheritDoc} */
	public function get_slug() {
		return 'flux';
	}

	/** {@inheritDoc} */
	public function get_label() {
		return __( 'FLUX (Together AI)', 'cubixsol-multi-ai-image-generator' );
	}

	/** {@inheritDoc} */
	public function get_description() {
		return __( 'Black Forest Labs FLUX.1 — fast, exceptional realism. Together AI key required.', 'cubixsol-multi-ai-image-generator' );
	}

	/** {@inheritDoc} */
	public function get_key_url() {
		return 'https://api.together.ai/settings/api-keys';
	}

	/** {@inheritDoc} */
	public function get_supported_sizes() {
		return array( '512x512', '768x768', '1024x1024', '1792x1024', '1024x1792' );
	}

	/** {@inheritDoc} */
	public function generate( $prompt, $args = array() ) {
		// Check: key must be present before any request is made.
		if ( ! $this->is_configured() ) {
			return $this->missing_key_error();
		}

		// Check: clamp dimensions to FLUX's supported grid.
		$width  = isset( $args['width'] ) ? min( 1792, max( 256, absint( $args['width'] ) ) ) : 1024;
		$height = isset( $args['height'] ) ? min( 1792, max( 256, absint( $args['height'] ) ) ) : 1024;

		$data = $this->post_json(
			self::ENDPOINT,
			array(
				'model'           => 'black-forest-labs/FLUX.1-schnell',
				'prompt'          => $prompt,
				'width'           => $width,
				'height'          => $height,
				'n'               => 1,
				'response_format' => 'base64', // Together's enum is base64|url (not b64_json).
			),
			array( 'Authorization' => 'Bearer ' . $this->get_api_key() )
		);

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		// Check: accept both payload shapes Together may return.
		if ( ! empty( $data['data'][0]['b64_json'] ) ) {
			return array(
				'base64' => (string) $data['data'][0]['b64_json'],
				'mime'   => 'image/png',
			);
		}

		if ( ! empty( $data['data'][0]['url'] ) ) {
			return array( 'url' => esc_url_raw( (string) $data['data'][0]['url'] ) );
		}

		return new WP_Error( 'aiisp_flux_empty', __( 'FLUX returned an empty image payload.', 'cubixsol-multi-ai-image-generator' ) );
	}

	/**
	 * Probe Together AI's models list — free, requires valid auth.
	 *
	 * @return true|\WP_Error
	 */
	protected function verify_credentials() {
		return $this->auth_probe(
			'https://api.together.xyz/v1/models',
			array( 'Authorization' => 'Bearer ' . $this->get_api_key() )
		);
	}
}
