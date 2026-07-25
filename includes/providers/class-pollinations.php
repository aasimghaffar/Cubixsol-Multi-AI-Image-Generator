<?php
/**
 * Pollinations.ai engine — free, no API key required.
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
 * Serves images from Pollinations' public GET endpoint.
 */
class Pollinations extends Provider_Base {

	/** {@inheritDoc} */
	public function get_slug() {
		return 'pollinations';
	}

	/** {@inheritDoc} */
	public function get_label() {
		return __( 'Pollinations.ai', 'cubixsol-multi-ai-image-generator' );
	}

	/** {@inheritDoc} */
	public function get_description() {
		return __( 'Free unlimited generation. No account or API key needed.', 'cubixsol-multi-ai-image-generator' );
	}

	/** Pollinations never needs a key. {@inheritDoc} */
	public function requires_api_key() {
		return false;
	}

	/** {@inheritDoc} */
	public function get_supported_sizes() {
		return array( '512x512', '768x768', '1024x1024', '1024x1792', '1792x1024', '2048x2048' );
	}

	/** {@inheritDoc} */
	public function generate( $prompt, $args = array() ) {
		// Check: clamp dimensions into the engine's supported range.
		$width  = isset( $args['width'] ) ? min( absint( $args['width'] ), 2048 ) : 1024;
		$height = isset( $args['height'] ) ? min( absint( $args['height'] ), 2048 ) : 1024;

		$url = add_query_arg(
			array(
				'width'  => max( 64, $width ),
				'height' => max( 64, $height ),
				'nologo' => 'true',
				'seed'   => wp_rand( 1, 999999 ),
			),
			'https://image.pollinations.ai/prompt/' . rawurlencode( $prompt )
		);

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 120,
				'headers' => array( 'Accept' => 'image/*' ),
			)
		);

		// Check: transport failure.
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		// Check: HTTP status.
		$code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			return new WP_Error(
				'aiisp_pollinations_http',
				sprintf(
					/* translators: %d: HTTP status code */
					__( 'Pollinations.ai returned HTTP status %d.', 'cubixsol-multi-ai-image-generator' ),
					$code
				)
			);
		}

		$body = wp_remote_retrieve_body( $response );
		$mime = wp_remote_retrieve_header( $response, 'content-type' );

		// Check: body must look like an image, not an HTML error page.
		if ( '' === $body || false === strpos( (string) $mime, 'image/' ) ) {
			return new WP_Error( 'aiisp_pollinations_body', __( 'Pollinations.ai did not return an image.', 'cubixsol-multi-ai-image-generator' ) );
		}

		return array(
			'base64' => base64_encode( $body ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- binary image transport.
			'mime'   => (string) $mime,
		);
	}
}
