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

	/**
	 * Set expectations: this engine is a free community service with
	 * no uptime guarantee, so admins understand an occasional failure
	 * is normal and know what to do about it.
	 *
	 * {@inheritDoc}
	 */
	public function get_notice() {
		return __( 'Because this engine is free and shared by many users, it can be busy, slow, or briefly unavailable at peak times — no account or payment can change that. If a generation fails, wait a little while and try again, or switch to another engine on this tab. Enabling Automatic Fallback below lets the plugin do that switch for you.', 'cubixsol-multi-ai-image-generator' );
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

		// Check: transport failure (usually a timeout while the free
		// service is under load) — explain rather than show a raw
		// cURL message.
		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'aiisp_pollinations_transport',
				sprintf(
					/* translators: %s: underlying transport error */
					__( 'Could not reach Pollinations.ai (%s). This free service is sometimes busy — please try again in a few minutes, or pick another engine on the AI Engines tab.', 'cubixsol-multi-ai-image-generator' ),
					$response->get_error_message()
				)
			);
		}

		// Check: HTTP status. 429 and 5xx are the everyday symptoms of
		// a busy free service, so they get actionable wording instead
		// of a bare status code.
		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			if ( 429 === $code || $code >= 500 ) {
				return new WP_Error(
					'aiisp_pollinations_busy',
					sprintf(
						/* translators: %d: HTTP status code */
						__( 'Pollinations.ai is busy or temporarily unavailable right now (HTTP %d). This is normal for a free, shared service and is not a problem with your site. Please try again in a little while, or switch to another engine on the AI Engines tab — with Automatic Fallback enabled, the plugin can do that for you.', 'cubixsol-multi-ai-image-generator' ),
						$code
					)
				);
			}

			return new WP_Error(
				'aiisp_pollinations_http',
				sprintf(
					/* translators: %d: HTTP status code */
					__( 'Pollinations.ai returned HTTP status %d. Please try again, or use another engine on the AI Engines tab.', 'cubixsol-multi-ai-image-generator' ),
					$code
				)
			);
		}

		$body = wp_remote_retrieve_body( $response );
		$mime = wp_remote_retrieve_header( $response, 'content-type' );

		// Check: body must look like an image, not an HTML error page.
		if ( '' === $body || false === strpos( (string) $mime, 'image/' ) ) {
			return new WP_Error(
				'aiisp_pollinations_body',
				__( 'Pollinations.ai did not return an image — the free service is likely under heavy load. Please try again shortly, or switch to another engine on the AI Engines tab.', 'cubixsol-multi-ai-image-generator' )
			);
		}

		return array(
			'base64' => base64_encode( $body ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- binary image transport.
			'mime'   => (string) $mime,
		);
	}
}
