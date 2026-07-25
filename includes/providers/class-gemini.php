<?php
/**
 * Google Gemini image engine.
 *
 * Uses the gemini-2.5-flash-image model via :generateContent — the
 * endpoint available to standard Gemini API keys (including the free
 * tier). The older imagen-*:predict endpoints 404 for regular keys,
 * which is why this engine does not use them.
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
 * Calls the Generative Language generateContent endpoint with an
 * image-output model.
 */
class Gemini extends Provider_Base {

	/** Image-capable model ID. */
	const MODEL = 'gemini-2.5-flash-image';

	/** API endpoint (model + method appended in generate()). */
	const API_BASE = 'https://generativelanguage.googleapis.com/v1beta/models/';

	/** {@inheritDoc} */
	public function get_slug() {
		return 'gemini';
	}

	/** {@inheritDoc} */
	public function get_label() {
		return __( 'Google Gemini', 'cubixsol-multi-ai-image-generator' );
	}

	/** {@inheritDoc} */
	public function get_description() {
		return __( 'Gemini 2.5 Flash Image — fast, high quality, free tier available. API key required.', 'cubixsol-multi-ai-image-generator' );
	}

	/** {@inheritDoc} */
	public function get_key_url() {
		return 'https://aistudio.google.com/apikey';
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

		$url = add_query_arg(
			'key',
			rawurlencode( $this->get_api_key() ),
			self::API_BASE . self::MODEL . ':generateContent'
		);

		$data = $this->post_json(
			$url,
			array(
				'contents'         => array(
					array(
						'parts' => array(
							array( 'text' => $prompt ),
						),
					),
				),
				'generationConfig' => array(
					// The model must be told an image is expected.
					'responseModalities' => array( 'TEXT', 'IMAGE' ),
					'imageConfig'        => array(
						'aspectRatio' => $this->aspect_from_args( $args ),
					),
				),
			)
		);

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		// Check: a candidate with content parts must exist.
		if ( empty( $data['candidates'][0]['content']['parts'] ) || ! is_array( $data['candidates'][0]['content']['parts'] ) ) {
			return new WP_Error( 'aiisp_gemini_empty', __( 'Gemini returned no content. The prompt may have been blocked by safety filters.', 'cubixsol-multi-ai-image-generator' ) );
		}

		// Scan the parts for the inline image payload; the model may
		// also return text parts alongside it.
		foreach ( $data['candidates'][0]['content']['parts'] as $part ) {
			// Check: accept both camelCase and snake_case field names
			// (the REST API has used both across versions).
			$inline = array();
			if ( isset( $part['inlineData'] ) && is_array( $part['inlineData'] ) ) {
				$inline = $part['inlineData'];
			} elseif ( isset( $part['inline_data'] ) && is_array( $part['inline_data'] ) ) {
				$inline = $part['inline_data'];
			}

			if ( ! empty( $inline['data'] ) ) {
				$mime = '';
				if ( isset( $inline['mimeType'] ) ) {
					$mime = (string) $inline['mimeType'];
				} elseif ( isset( $inline['mime_type'] ) ) {
					$mime = (string) $inline['mime_type'];
				}

				return array(
					'base64' => (string) $inline['data'],
					'mime'   => '' !== $mime ? $mime : 'image/png',
				);
			}
		}

		return new WP_Error( 'aiisp_gemini_no_image', __( 'Gemini answered without an image. Try rephrasing the prompt.', 'cubixsol-multi-ai-image-generator' ) );
	}

	/**
	 * Probe the models list — free, read-only, requires valid auth.
	 *
	 * @return true|\WP_Error
	 */
	protected function verify_credentials() {
		return $this->auth_probe(
			add_query_arg( 'key', rawurlencode( $this->get_api_key() ), 'https://generativelanguage.googleapis.com/v1beta/models' )
		);
	}
}
