<?php
/**
 * OpenAI image engine (GPT Image).
 *
 * Uses the gpt-image-2 model: OpenAI removed the DALL·E models from
 * the API on May 12, 2026, and is consolidating the interim models
 * (gpt-image-1-mini / 1.5) onto gpt-image-2 on December 1, 2026 —
 * so this engine targets the consolidation model directly.
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

	/** Current OpenAI image model. */
	const MODEL = 'gpt-image-2';

	/** {@inheritDoc} */
	public function get_slug() {
		return 'openai';
	}

	/** {@inheritDoc} */
	public function get_label() {
		return __( 'OpenAI (GPT Image)', 'cubixsol-multi-ai-image-generator' );
	}

	/** {@inheritDoc} */
	public function get_description() {
		return __( 'GPT Image 2 — photorealistic, precise instruction following. Paid API key required.', 'cubixsol-multi-ai-image-generator' );
	}

	/** {@inheritDoc} */
	public function get_key_url() {
		return 'https://platform.openai.com/api-keys';
	}

	/** {@inheritDoc} */
	public function get_supported_sizes() {
		// GPT Image sizes differ from the retired DALL·E sizes.
		return array( '1024x1024', '1536x1024', '1024x1536' );
	}

	/** {@inheritDoc} */
	public function generate( $prompt, $args = array() ) {
		// Check: key must be present before any request is made.
		if ( ! $this->is_configured() ) {
			return $this->missing_key_error();
		}

		// Map the global size onto the nearest GPT Image size.
		$width  = isset( $args['width'] ) ? absint( $args['width'] ) : 1024;
		$height = isset( $args['height'] ) ? absint( $args['height'] ) : 1024;
		$size   = '1024x1024';
		if ( $width > $height ) {
			$size = '1536x1024';
		} elseif ( $height > $width ) {
			$size = '1024x1536';
		}

		// Note: 'response_format' is intentionally NOT sent — the
		// current Images API rejects the parameter with HTTP 400.
		// GPT Image models return base64 by default; both shapes are
		// still accepted below for safety.
		$data = $this->post_json(
			self::ENDPOINT,
			array(
				'model'  => self::MODEL,
				'prompt' => $prompt,
				'n'      => 1,
				'size'   => $size,
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
				'mime'   => 'image/png',
			);
		}

		if ( ! empty( $data['data'][0]['url'] ) ) {
			return array( 'url' => esc_url_raw( (string) $data['data'][0]['url'] ) );
		}

		return new WP_Error( 'aiisp_openai_empty', __( 'OpenAI returned an empty image payload.', 'cubixsol-multi-ai-image-generator' ) );
	}

	/**
	 * Verify the key against the free /v1/models listing endpoint.
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
