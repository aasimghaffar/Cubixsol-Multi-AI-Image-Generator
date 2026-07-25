<?php
/**
 * Leonardo.Ai engine.
 *
 * Leonardo generations are asynchronous: submit a job, then poll the
 * job endpoint until the image URL is ready.
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
 * Calls the Leonardo REST API with submit + poll workflow.
 */
class Leonardo extends Provider_Base {

	/** Job submission endpoint. */
	const ENDPOINT = 'https://cloud.leonardo.ai/api/rest/v1/generations';

	/** Max polling attempts before giving up. */
	const MAX_POLLS = 12;

	/** Seconds between polls. */
	const POLL_DELAY = 4;

	/** {@inheritDoc} */
	public function get_slug() {
		return 'leonardo';
	}

	/** {@inheritDoc} */
	public function get_label() {
		return __( 'Leonardo.Ai', 'cubixsol-multi-ai-image-generator' );
	}

	/** {@inheritDoc} */
	public function get_description() {
		return __( 'Popular creative platform with daily free tokens. Key required.', 'cubixsol-multi-ai-image-generator' );
	}

	/** {@inheritDoc} */
	public function get_key_url() {
		return 'https://app.leonardo.ai/settings';
	}

	/** {@inheritDoc} */
	public function get_supported_sizes() {
		return array( '512x512', '768x768', '1024x1024' );
	}

	/** {@inheritDoc} */
	public function generate( $prompt, $args = array() ) {
		// Check: key must be present before any request is made.
		if ( ! $this->is_configured() ) {
			return $this->missing_key_error();
		}

		$auth = array( 'Authorization' => 'Bearer ' . $this->get_api_key() );

		// Check: clamp to Leonardo's 512–1024 dimension window.
		$width  = isset( $args['width'] ) ? min( 1024, max( 512, absint( $args['width'] ) ) ) : 1024;
		$height = isset( $args['height'] ) ? min( 1024, max( 512, absint( $args['height'] ) ) ) : 1024;

		$payload = array(
			'prompt'     => $prompt,
			'num_images' => 1,
			'width'      => $width,
			'height'     => $height,
		);

		// Check: Leonardo supports negative prompts — include when set.
		if ( ! empty( $args['negative'] ) ) {
			$payload['negative_prompt'] = (string) $args['negative'];
		}

		// Step 1: submit the generation job.
		$job = $this->post_json( self::ENDPOINT, $payload, $auth );

		if ( is_wp_error( $job ) ) {
			return $job;
		}

		// Check: the job ID must be present to poll.
		$job_id = isset( $job['sdGenerationJob']['generationId'] ) ? sanitize_text_field( (string) $job['sdGenerationJob']['generationId'] ) : '';
		if ( '' === $job_id ) {
			return new WP_Error( 'aiisp_leonardo_job', __( 'Leonardo did not return a generation job ID.', 'cubixsol-multi-ai-image-generator' ) );
		}

		// Step 2: poll until the image is ready or we time out.
		for ( $attempt = 0; $attempt < self::MAX_POLLS; $attempt++ ) {
			sleep( self::POLL_DELAY );

			$status = $this->get_json( self::ENDPOINT . '/' . rawurlencode( $job_id ), $auth );

			// Check: a poll failure is not fatal — keep trying.
			if ( is_wp_error( $status ) ) {
				continue;
			}

			$generation = isset( $status['generations_by_pk'] ) ? $status['generations_by_pk'] : array();

			if ( isset( $generation['status'] ) && 'COMPLETE' === $generation['status'] ) {
				// Check: completed job must actually contain an image URL.
				if ( ! empty( $generation['generated_images'][0]['url'] ) ) {
					return array( 'url' => esc_url_raw( (string) $generation['generated_images'][0]['url'] ) );
				}
				return new WP_Error( 'aiisp_leonardo_empty', __( 'Leonardo completed the job without an image.', 'cubixsol-multi-ai-image-generator' ) );
			}

			if ( isset( $generation['status'] ) && 'FAILED' === $generation['status'] ) {
				return new WP_Error( 'aiisp_leonardo_failed', __( 'Leonardo reported the generation job failed.', 'cubixsol-multi-ai-image-generator' ) );
			}
		}

		return new WP_Error( 'aiisp_leonardo_timeout', __( 'Leonardo generation timed out. Try again or pick another engine.', 'cubixsol-multi-ai-image-generator' ) );
	}

	/**
	 * Probe the /me profile endpoint — free, requires valid auth.
	 *
	 * @return true|\WP_Error
	 */
	protected function verify_credentials() {
		return $this->auth_probe(
			'https://cloud.leonardo.ai/api/rest/v1/me',
			array( 'Authorization' => 'Bearer ' . $this->get_api_key() )
		);
	}
}
