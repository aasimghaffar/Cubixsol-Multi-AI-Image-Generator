<?php
/**
 * AJAX controller.
 *
 * Every endpoint runs the same security gate: nonce verification
 * followed by a capability check — then per-endpoint object-level
 * checks (e.g. edit_post on the specific post ID).
 *
 * @package AIISP
 */

namespace AIISP\Core;

use AIISP\Logger;

// Security check: block direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and serves all wp_ajax_* endpoints.
 */
class Ajax {

	/**
	 * Register endpoints. All are admin-side (no nopriv variants),
	 * so anonymous visitors can never reach them.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'wp_ajax_aiisp_generate_image', array( $this, 'generate_image' ) );
		add_action( 'wp_ajax_aiisp_set_featured', array( $this, 'set_featured' ) );
		add_action( 'wp_ajax_aiisp_clear_logs', array( $this, 'clear_logs' ) );
		add_action( 'wp_ajax_aiisp_stock_search', array( $this, 'stock_search' ) );
		add_action( 'wp_ajax_aiisp_stock_import', array( $this, 'stock_import' ) );
		add_action( 'wp_ajax_aiisp_bulk_scan', array( $this, 'bulk_scan' ) );
		add_action( 'wp_ajax_aiisp_bulk_generate', array( $this, 'bulk_generate' ) );
		add_action( 'wp_ajax_aiisp_test_key', array( $this, 'test_key' ) );
		add_action( 'wp_ajax_aiisp_import_preview', array( $this, 'import_preview' ) );
	}

	/**
	 * Common security gate for every endpoint.
	 *
	 * @param string $capability Required capability.
	 * @return void Sends a 403 JSON error and exits on failure.
	 */
	private function guard( $capability = 'upload_files' ) {
		// Check 1: request must carry a valid nonce.
		$nonce = isset( $_POST['nonce'] ) ? sanitize_key( wp_unslash( $_POST['nonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'aiisp_ajax_nonce' ) ) {
			wp_send_json_error(
				array( 'message' => esc_html__( 'Security check failed. Refresh the page and try again.', 'cubixsol-multi-ai-image-generator' ) ),
				403
			);
		}

		// Check 2: current user must hold the required capability.
		if ( ! current_user_can( $capability ) ) {
			wp_send_json_error(
				array( 'message' => esc_html__( 'You do not have permission to perform this action.', 'cubixsol-multi-ai-image-generator' ) ),
				403
			);
		}
	}

	/**
	 * Read a POST field with an isset check and a sanitizer applied.
	 *
	 * @param string   $key       Field name.
	 * @param callable $sanitizer Sanitizing callback.
	 * @param mixed    $fallback  Value when the field is absent.
	 * @return mixed
	 */
	private function post_field( $key, $sanitizer, $fallback = '' ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified in guard().
		if ( ! isset( $_POST[ $key ] ) ) {
			return $fallback;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- nonce verified in guard(); the value is sanitized on this same line by the mandatory $sanitizer callback every caller supplies (sanitize_text_field, sanitize_key, absint, ...).
		return call_user_func( $sanitizer, wp_unslash( $_POST[ $key ] ) );
	}

	/* ---------------------------------------------------------------------
	 * Endpoint: generate one image (meta box + bulk both use this core)
	 * ------------------------------------------------------------------ */

	/**
	 * Generate an image through the execution queue (active engine
	 * first, then fallbacks), sideload it, log the result.
	 *
	 * @return void
	 */
	public function generate_image() {
		$this->guard( 'upload_files' );

		$result = $this->run_generation(
			$this->post_field( 'prompt', 'sanitize_textarea_field' ),
			$this->post_field( 'style', 'sanitize_key' ),
			absint( $this->post_field( 'post_id', 'absint', 0 ) ),
			1 === absint( $this->post_field( 'preview', 'absint', 0 ) )
		);

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => esc_html( $result->get_error_message() ) ) );
		}

		wp_send_json_success( $result );
	}

	/**
	 * Shared generation core used by the single and bulk endpoints.
	 *
	 * @param string $prompt  Prompt text.
	 * @param string $style   Style preset slug.
	 * @param int    $post_id Target post.
	 * @param bool   $preview When true the image is returned for
	 *                        on-screen preview only and is NOT saved
	 *                        to the Media Library (Image Workspace flow —
	 *                        the admin saves selected images later).
	 * @return array|\WP_Error Success payload or error.
	 */
	private function run_generation( $prompt, $style, $post_id, $preview = false ) {
		// Check: prompt must not be empty.
		if ( '' === trim( $prompt ) ) {
			return new \WP_Error( 'aiisp_no_prompt', __( 'Please enter an image prompt first.', 'cubixsol-multi-ai-image-generator' ) );
		}

		// Check: object-level permission on the specific post.
		if ( $post_id > 0 && ! current_user_can( 'edit_post', $post_id ) ) {
			return new \WP_Error( 'aiisp_cap', __( 'You cannot edit the target post.', 'cubixsol-multi-ai-image-generator' ) );
		}

		// Check: daily limit.
		if ( aiisp()->usage()->limit_reached() ) {
			return new \WP_Error( 'aiisp_limit', __( 'The daily generation limit has been reached. Raise it under Media Settings or try tomorrow.', 'cubixsol-multi-ai-image-generator' ) );
		}

		// Check: fall back to the configured default style preset when
		// the request did not choose one.
		if ( '' === $style ) {
			$style = (string) aiisp()->options()->get( 'default_style', 'none' );
		}

		// Append the style preset as art direction (dynamic, filterable).
		$presets = \AIISP\Admin\Meta_Box::get_style_presets();
		if ( '' !== $style && 'none' !== $style && isset( $presets[ $style ] ) ) {
			$prompt .= ', ' . str_replace( '-', ' ', $style ) . ' style';
		}

		// Prompt Booster: wrap the prompt with the configured prefix and
		// suffix so every generation carries the site's art direction.
		$prefix = trim( (string) aiisp()->options()->get( 'prompt_prefix', '' ) );
		$suffix = trim( (string) aiisp()->options()->get( 'prompt_suffix', '' ) );
		if ( '' !== $prefix ) {
			$prompt = $prefix . ', ' . $prompt;
		}
		if ( '' !== $suffix ) {
			$prompt .= ', ' . $suffix;
		}

		// Resolve the configured global size.
		$size = (string) aiisp()->options()->get( 'image_size', '1024x1024' );
		list( $width, $height ) = array_map( 'absint', array_pad( explode( 'x', $size ), 2, 1024 ) );

		$queue = aiisp()->providers()->get_execution_queue();

		// Check: at least one engine must be configured.
		if ( empty( $queue ) ) {
			return new \WP_Error( 'aiisp_no_engine', __( 'No configured engine available. Check your provider settings.', 'cubixsol-multi-ai-image-generator' ) );
		}

		$media  = new Media();
		$errors = array();

		// Engines that support negative prompts receive the global one.
		$negative = trim( (string) aiisp()->options()->get( 'negative_prompt', '' ) );

		foreach ( $queue as $provider ) {
			$generated = $provider->generate(
				$prompt,
				array(
					'width'    => $width,
					'height'   => $height,
					'style'    => $style,
					'negative' => $negative,
				)
			);

			// Engine failed → log it and fall back to the next one.
			if ( is_wp_error( $generated ) ) {
				$errors[] = $generated->get_error_message();

				aiisp()->logger()->add(
					array(
						'provider'   => $provider->get_slug(),
						'prompt'     => $prompt,
						'resolution' => $size,
						'post_id'    => $post_id,
						'status'     => 'fail',
						'message'    => $generated->get_error_message(),
					)
				);
				continue;
			}

			// Preview mode: return the raw image for on-screen display
			// without touching the Media Library. Usage still counts
			// and the generation is still logged.
			if ( $preview ) {
				aiisp()->usage()->increment();
				aiisp()->logger()->add(
					array(
						'provider'   => $provider->get_slug(),
						'prompt'     => $prompt,
						'resolution' => $size,
						'post_id'    => $post_id,
						'status'     => 'success',
						'message'    => __( 'Preview (not yet saved to library)', 'cubixsol-multi-ai-image-generator' ),
					)
				);

				return array(
					'preview'  => true,
					'src'      => ! empty( $generated['url'] )
						? $generated['url']
						: 'data:' . ( isset( $generated['mime'] ) ? $generated['mime'] : 'image/png' ) . ';base64,' . $generated['base64'],
					'prompt'   => $prompt,
					'provider' => $provider->get_label(),
				);
			}

			$attachment_id = $media->sideload( $generated, $prompt, $post_id, $style );

			// Check: sideload failure is terminal (filesystem issue).
			if ( is_wp_error( $attachment_id ) ) {
				return $attachment_id;
			}

			aiisp()->usage()->increment();
			aiisp()->logger()->add(
				array(
					'provider'      => $provider->get_slug(),
					'prompt'        => $prompt,
					'resolution'    => $size,
					'post_id'       => $post_id,
					'attachment_id' => $attachment_id,
					'status'        => 'success',
				)
			);

			return array(
				'attachment_id' => $attachment_id,
				'url'           => wp_get_attachment_image_url( $attachment_id, 'large' ),
				'full_url'      => wp_get_attachment_url( $attachment_id ),
				'thumb'         => wp_get_attachment_image_url( $attachment_id, 'thumbnail' ),
				'provider'      => $provider->get_label(),
			);
		}

		return new \WP_Error( 'aiisp_all_failed', implode( ' | ', $errors ) );
	}

	/* ---------------------------------------------------------------------
	 * Endpoint: set featured image
	 * ------------------------------------------------------------------ */

	/**
	 * Attach a generated image as a post's featured image.
	 *
	 * @return void
	 */
	public function set_featured() {
		$this->guard( 'edit_posts' );

		$post_id       = absint( $this->post_field( 'post_id', 'absint', 0 ) );
		$attachment_id = absint( $this->post_field( 'attachment_id', 'absint', 0 ) );

		// Check: both IDs present, post editable by this user.
		if ( ! $post_id || ! $attachment_id || ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'You cannot edit the target post.', 'cubixsol-multi-ai-image-generator' ) ), 403 );
		}

		// Check: attachment must be a real attachment post.
		if ( 'attachment' !== get_post_type( $attachment_id ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Invalid attachment reference.', 'cubixsol-multi-ai-image-generator' ) ) );
		}

		// Check: setter can fail (e.g. race with a deleted post).
		if ( ! set_post_thumbnail( $post_id, $attachment_id ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'WordPress refused to set the featured image.', 'cubixsol-multi-ai-image-generator' ) ) );
		}

		wp_send_json_success( array( 'message' => esc_html__( 'Featured image set.', 'cubixsol-multi-ai-image-generator' ) ) );
	}

	/* ---------------------------------------------------------------------
	 * Endpoint: clear logs
	 * ------------------------------------------------------------------ */

	/**
	 * Truncate the generation history table (admins only).
	 *
	 * @return void
	 */
	public function clear_logs() {
		$this->guard( 'manage_options' );

		// Check: report failure honestly instead of pretending success.
		if ( ! aiisp()->logger()->clear() ) {
			wp_send_json_error( array( 'message' => esc_html__( 'The history table could not be cleared.', 'cubixsol-multi-ai-image-generator' ) ) );
		}

		wp_send_json_success( array( 'message' => esc_html__( 'Generation history cleared.', 'cubixsol-multi-ai-image-generator' ) ) );
	}

	/* ---------------------------------------------------------------------
	 * Endpoints: stock photo search + import
	 * ------------------------------------------------------------------ */

	/**
	 * Search a stock source (dynamic registry lookup).
	 *
	 * @return void
	 */
	public function stock_search() {
		$this->guard( 'upload_files' );

		$source_slug = $this->post_field( 'source', 'sanitize_key', 'openverse' );
		$query       = $this->post_field( 'query', 'sanitize_text_field' );

		// Check: query must not be empty.
		if ( '' === trim( $query ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Please enter a search term.', 'cubixsol-multi-ai-image-generator' ) ) );
		}

		// Check: source must exist in the registry.
		$source = aiisp()->stock()->get( $source_slug );
		if ( null === $source ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Unknown stock source.', 'cubixsol-multi-ai-image-generator' ) ) );
		}

		$results = $source->search( $query, 12 );

		if ( is_wp_error( $results ) ) {
			wp_send_json_error( array( 'message' => esc_html( $results->get_error_message() ) ) );
		}

		wp_send_json_success( array( 'results' => $results ) );
	}

	/**
	 * Import a stock image into the Media Library.
	 *
	 * @return void
	 */
	public function stock_import() {
		$this->guard( 'upload_files' );

		$url     = $this->post_field( 'image_url', 'esc_url_raw' );
		$post_id = absint( $this->post_field( 'post_id', 'absint', 0 ) );
		$credit  = $this->post_field( 'credit', 'sanitize_text_field' );

		// Check: URL must be present and valid.
		if ( '' === $url || ! wp_http_validate_url( $url ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Missing or invalid image URL.', 'cubixsol-multi-ai-image-generator' ) ) );
		}

		$media         = new Media();
		$attachment_id = $media->sideload( array( 'url' => $url ), '' !== $credit ? $credit : 'stock image', $post_id );

		if ( is_wp_error( $attachment_id ) ) {
			wp_send_json_error( array( 'message' => esc_html( $attachment_id->get_error_message() ) ) );
		}

		wp_send_json_success(
			array(
				'attachment_id' => $attachment_id,
				'url'           => wp_get_attachment_image_url( $attachment_id, 'large' ),
			)
		);
	}

	/* ---------------------------------------------------------------------
	 * Endpoints: bulk generation (scan + per-post generate)
	 * ------------------------------------------------------------------ */

	/**
	 * Scan for posts missing a featured image so the bulk table is
	 * built from live data instead of a static page render.
	 *
	 * @return void
	 */
	public function bulk_scan() {
		$this->guard( 'edit_posts' );

		$post_type = $this->post_field( 'post_type', 'sanitize_key', 'post' );
		$enabled   = (array) aiisp()->options()->get( 'post_types', array() );

		// Check: only post types enabled in settings may be scanned.
		if ( ! in_array( $post_type, $enabled, true ) || ! post_type_exists( $post_type ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'That post type is not enabled under Post Types settings.', 'cubixsol-multi-ai-image-generator' ) ) );
		}

		$query = new \WP_Query(
			array(
				'post_type'              => $post_type,
				'post_status'            => array( 'publish', 'draft', 'pending', 'future' ),
				'posts_per_page'         => 50,
				'no_found_rows'          => true,
				'update_post_term_cache' => false,
				'meta_query'             => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- intentional missing-thumbnail lookup.
					array(
						'key'     => '_thumbnail_id',
						'compare' => 'NOT EXISTS',
					),
				),
			)
		);

		$rows = array();
		foreach ( $query->posts as $post ) {
			// Check: only offer posts the current user can actually edit.
			if ( ! current_user_can( 'edit_post', $post->ID ) ) {
				continue;
			}

			$rows[] = array(
				'id'        => (int) $post->ID,
				'title'     => '' !== $post->post_title ? $post->post_title : __( '(no title)', 'cubixsol-multi-ai-image-generator' ),
				'status'    => $post->post_status,
				'edit_link' => get_edit_post_link( $post->ID, 'raw' ),
			);
		}

		wp_send_json_success( array( 'posts' => $rows ) );
	}

	/**
	 * Generate + set featured image for one post in the bulk queue.
	 * The JS runs these sequentially so the server is never hammered
	 * with parallel API calls.
	 *
	 * @return void
	 */
	public function bulk_generate() {
		$this->guard( 'edit_posts' );

		$post_id = absint( $this->post_field( 'post_id', 'absint', 0 ) );
		$prompt  = $this->post_field( 'prompt', 'sanitize_textarea_field' );
		$style   = $this->post_field( 'style', 'sanitize_key', 'none' );

		// Check: post must exist and be editable.
		$post = get_post( $post_id );
		if ( ! $post || ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Post not found or not editable.', 'cubixsol-multi-ai-image-generator' ) ) );
		}

		// Check: fall back to the post title when no prompt was sent.
		if ( '' === trim( $prompt ) ) {
			$prompt = get_the_title( $post_id );
		}

		$result = $this->run_generation( $prompt, $style, $post_id );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => esc_html( $result->get_error_message() ) ) );
		}

		// Check: setting the thumbnail may still fail independently.
		if ( ! set_post_thumbnail( $post_id, $result['attachment_id'] ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Image generated but WordPress refused to set it as featured.', 'cubixsol-multi-ai-image-generator' ) ) );
		}

		wp_send_json_success( $result );
	}

	/* ---------------------------------------------------------------------
	 * Endpoint: test an API key (AI engines + stock sources)
	 * ------------------------------------------------------------------ */

	/**
	 * Validate an API key against its live service before saving.
	 * Settings are admin-only, so this endpoint is too.
	 *
	 * @return void
	 */
	public function test_key() {
		$this->guard( 'manage_options' );

		$kind = $this->post_field( 'kind', 'sanitize_key', 'provider' );
		$slug = $this->post_field( 'slug', 'sanitize_key' );
		$key  = $this->post_field( 'key', 'sanitize_text_field' );

		// Check: fall back to the stored key so admins can re-test a
		// key that is already saved without pasting it again.
		if ( '' === trim( $key ) ) {
			$key = (string) aiisp()->options()->get( $slug . '_api_key' );
		}

		// Check: with no typed key and no saved key there is nothing to test.
		if ( '' === trim( $key ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Enter a key before testing.', 'cubixsol-multi-ai-image-generator' ) ) );
		}

		// Check: look the service up in the matching dynamic registry.
		$service = 'stock' === $kind ? aiisp()->stock()->get( $slug ) : aiisp()->providers()->get( $slug );

		if ( null === $service ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Unknown service.', 'cubixsol-multi-ai-image-generator' ) ) );
		}

		$result = $service->test_key( $key );

		if ( is_wp_error( $result ) ) {
			// Check: an inconclusive probe is not a failure — surface
			// it as a distinct amber "unverified" state so the admin
			// is never shown a false positive OR a false negative.
			if ( 'aiisp_test_inconclusive' === $result->get_error_code() ) {
				wp_send_json_success(
					array(
						'state'   => 'warn',
						'message' => esc_html( $result->get_error_message() ),
					)
				);
			}

			wp_send_json_error( array( 'message' => esc_html( $result->get_error_message() ) ) );
		}

		wp_send_json_success(
			array(
				'state'   => 'ok',
				'message' => sprintf(
					/* translators: %s: service label */
					esc_html__( '%s accepted this key.', 'cubixsol-multi-ai-image-generator' ),
					esc_html( $service->get_label() )
				),
			)
		);
	}

	/* ---------------------------------------------------------------------
	 * Endpoint: save a previewed Image Workspace image to the library
	 * ------------------------------------------------------------------ */

	/**
	 * Import a preview (remote URL or data URI from preview-mode
	 * generation) into the Media Library and return its permanent
	 * site URL for the "copy link" popup.
	 *
	 * @return void
	 */
	public function import_preview() {
		$this->guard( 'upload_files' );

		// Raw read: data URIs contain base64 that sanitizers mangle.
		// It is length-checked, shape-checked and strictly re-verified
		// by Media before anything touches the filesystem.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- nonce verified in guard(); generic sanitizers corrupt base64 data URIs, so this value is instead strictly validated below: it must parse as an image data URI (strict base64 decode + wp_check_filetype_and_ext re-verification on disk) or pass wp_http_validate_url(), and is discarded otherwise.
		$src    = isset( $_POST['src'] ) ? trim( (string) wp_unslash( $_POST['src'] ) ) : '';
		$prompt = $this->post_field( 'prompt', 'sanitize_textarea_field' );

		// Check: a source must be provided.
		if ( '' === $src ) {
			wp_send_json_error( array( 'message' => esc_html__( 'No image source supplied.', 'cubixsol-multi-ai-image-generator' ) ) );
		}

		$media = new Media();

		if ( 0 === strpos( $src, 'data:image/' ) ) {
			// Data URI branch — split "data:image/png;base64,XXXX".
			$parts = explode( ',', $src, 2 );

			// Check: both header and payload segments must exist.
			if ( 2 !== count( $parts ) || false === strpos( $parts[0], ';base64' ) ) {
				wp_send_json_error( array( 'message' => esc_html__( 'Malformed image data.', 'cubixsol-multi-ai-image-generator' ) ) );
			}

			$mime = str_replace( array( 'data:', ';base64' ), '', $parts[0] );

			// Check: only allow the image mimes Media can handle.
			if ( ! in_array( $mime, array( 'image/png', 'image/jpeg', 'image/jpg', 'image/gif', 'image/webp' ), true ) ) {
				wp_send_json_error( array( 'message' => esc_html__( 'Unsupported image type.', 'cubixsol-multi-ai-image-generator' ) ) );
			}

			$attachment_id = $media->sideload( array( 'base64' => $parts[1], 'mime' => $mime ), $prompt );
		} else {
			// Remote URL branch.
			// Check: must be a valid http(s) URL.
			if ( ! wp_http_validate_url( $src ) ) {
				wp_send_json_error( array( 'message' => esc_html__( 'Invalid image URL.', 'cubixsol-multi-ai-image-generator' ) ) );
			}

			$attachment_id = $media->sideload( array( 'url' => esc_url_raw( $src ) ), $prompt );
		}

		if ( is_wp_error( $attachment_id ) ) {
			wp_send_json_error( array( 'message' => esc_html( $attachment_id->get_error_message() ) ) );
		}

		wp_send_json_success(
			array(
				'attachment_id' => $attachment_id,
				'url'           => wp_get_attachment_url( $attachment_id ),
				'thumb'         => wp_get_attachment_image_url( $attachment_id, 'medium' ),
				'library_link'  => admin_url( 'upload.php?item=' . $attachment_id ),
			)
		);
	}
}
