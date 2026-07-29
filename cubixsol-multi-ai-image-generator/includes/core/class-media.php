<?php
/**
 * Media Library sideloader.
 *
 * Every generated or imported image passes through here: download or
 * decode → verify it really is an allowed image type → write to the
 * uploads directory → create the attachment → apply SEO metadata.
 *
 * @package AIISP
 */

namespace AIISP\Core;

use WP_Error;

// Security check: block direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sideloads provider results into the Media Library.
 */
class Media {

	/**
	 * Sideload a provider result.
	 *
	 * @param array  $result  [ 'url' => string ] OR [ 'base64' => string, 'mime' => string ].
	 * @param string $prompt  Prompt (for metadata patterns).
	 * @param int    $post_id Parent post ID, 0 for none.
	 * @param string $style   Style preset slug.
	 * @return int|WP_Error Attachment ID.
	 */
	public function sideload( $result, $prompt, $post_id = 0, $style = '' ) {
		// Check: result must be a well-formed provider payload.
		if ( ! is_array( $result ) || ( empty( $result['url'] ) && empty( $result['base64'] ) ) ) {
			return new WP_Error( 'aiisp_media_input', __( 'No image data was supplied to the media importer.', 'cubixsol-multi-ai-image-generator' ) );
		}

		// Check: post must exist and be editable when one is targeted.
		if ( $post_id > 0 && ( ! get_post( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) ) {
			return new WP_Error( 'aiisp_media_post', __( 'The target post does not exist or you cannot edit it.', 'cubixsol-multi-ai-image-generator' ) );
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$filename = $this->build_filename( $prompt, $post_id );

		$attachment_id = ! empty( $result['base64'] )
			? $this->from_base64( (string) $result['base64'], isset( $result['mime'] ) ? (string) $result['mime'] : 'image/png', $filename, $post_id )
			: $this->from_url( (string) $result['url'], $filename, $post_id );

		if ( is_wp_error( $attachment_id ) ) {
			return $attachment_id;
		}

		$this->apply_metadata( $attachment_id, $prompt, $post_id, $style );

		return $attachment_id;
	}

	/**
	 * Import from a remote URL via download_url + media_handle_sideload,
	 * which runs WP core's own mime verification.
	 *
	 * @param string $url      Remote image URL.
	 * @param string $filename Base filename (no extension).
	 * @param int    $post_id  Parent post.
	 * @return int|WP_Error
	 */
	private function from_url( $url, $filename, $post_id ) {
		// Check: URL must be valid http(s) before any request.
		if ( ! wp_http_validate_url( $url ) ) {
			return new WP_Error( 'aiisp_media_url', __( 'The image URL failed validation.', 'cubixsol-multi-ai-image-generator' ) );
		}

		$tmp = download_url( $url, 120 );

		// Check: download failure.
		if ( is_wp_error( $tmp ) ) {
			return new WP_Error( 'aiisp_media_download', $tmp->get_error_message() );
		}

		// Check: downloaded file must actually be an image.
		$mime = wp_get_image_mime( $tmp );
		if ( false === $mime ) {
			wp_delete_file( $tmp );
			return new WP_Error( 'aiisp_media_type', __( 'The downloaded file is not a valid image.', 'cubixsol-multi-ai-image-generator' ) );
		}

		$file_array = array(
			'name'     => $filename . '.' . $this->extension_for_mime( $mime ),
			'tmp_name' => $tmp,
		);

		$attachment_id = media_handle_sideload( $file_array, $post_id );

		// Check: clean the temp file up when the sideload failed.
		if ( is_wp_error( $attachment_id ) && file_exists( $tmp ) ) {
			wp_delete_file( $tmp );
		}

		return $attachment_id;
	}

	/**
	 * Import from a base64 payload with strict type verification.
	 *
	 * @param string $base64   Base64 string.
	 * @param string $mime     Claimed mime type.
	 * @param string $filename Base filename (no extension).
	 * @param int    $post_id  Parent post.
	 * @return int|WP_Error
	 */
	private function from_base64( $base64, $mime, $filename, $post_id ) {
		// Check: strict decode — reject payloads with invalid characters.
		$binary = base64_decode( $base64, true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- decoding image payload.

		if ( false === $binary || '' === $binary ) {
			return new WP_Error( 'aiisp_media_b64', __( 'Received a malformed base64 image payload.', 'cubixsol-multi-ai-image-generator' ) );
		}

		$upload = wp_upload_bits( $filename . '.' . $this->extension_for_mime( $mime ), null, $binary );

		// Check: filesystem write failure.
		if ( ! empty( $upload['error'] ) ) {
			return new WP_Error(
				'aiisp_media_write',
				__( 'Media storage failure: could not write the image to the uploads directory. Check filesystem permissions.', 'cubixsol-multi-ai-image-generator' ) . ' ' . sanitize_text_field( $upload['error'] )
			);
		}

		// Check: written bytes must verify as a real, allowed image —
		// never trust the mime the API claimed.
		$filetype = wp_check_filetype_and_ext( $upload['file'], basename( $upload['file'] ) );
		if ( empty( $filetype['type'] ) || 0 !== strpos( $filetype['type'], 'image/' ) ) {
			wp_delete_file( $upload['file'] );
			return new WP_Error( 'aiisp_media_verify', __( 'The generated file failed image type verification and was discarded.', 'cubixsol-multi-ai-image-generator' ) );
		}

		$attachment_id = wp_insert_attachment(
			array(
				'post_mime_type' => $filetype['type'],
				'post_title'     => sanitize_text_field( $filename ),
				'post_content'   => '',
				'post_status'    => 'inherit',
			),
			$upload['file'],
			$post_id,
			true // Return WP_Error on failure instead of 0.
		);

		// Check: attachment insert failure.
		if ( is_wp_error( $attachment_id ) ) {
			wp_delete_file( $upload['file'] );
			return $attachment_id;
		}

		wp_update_attachment_metadata(
			$attachment_id,
			wp_generate_attachment_metadata( $attachment_id, $upload['file'] )
		);

		return $attachment_id;
	}

	/**
	 * Apply pattern-driven alt text plus provenance meta.
	 *
	 * @param int    $attachment_id Attachment.
	 * @param string $prompt        Prompt.
	 * @param int    $post_id       Parent post.
	 * @param string $style         Style slug.
	 * @return void
	 */
	private function apply_metadata( $attachment_id, $prompt, $post_id, $style ) {
		$pattern = (string) aiisp()->options()->get( 'alt_pattern', '{title} - {prompt}' );
		$title   = $post_id ? get_the_title( $post_id ) : get_bloginfo( 'name' );

		$alt = strtr(
			$pattern,
			array(
				'{title}'  => $title,
				'{prompt}' => $prompt,
				'{style}'  => $style,
			)
		);

		update_post_meta( $attachment_id, '_wp_attachment_image_alt', sanitize_text_field( $alt ) );
		update_post_meta( $attachment_id, '_aiisp_generated', 1 );
		update_post_meta( $attachment_id, '_aiisp_prompt', sanitize_textarea_field( $prompt ) );
	}

	/**
	 * Build the filename from the configured pattern, sanitized with
	 * post-name (slug) rules.
	 *
	 * @param string $prompt  Prompt.
	 * @param int    $post_id Parent post.
	 * @return string
	 */
	private function build_filename( $prompt, $post_id ) {
		$pattern = (string) aiisp()->options()->get( 'filename_pattern', '{title}-ai-image' );
		$title   = $post_id ? get_the_title( $post_id ) : 'aiisp';

		$name = strtr(
			$pattern,
			array(
				'{title}'  => $title,
				'{prompt}' => wp_trim_words( $prompt, 6, '' ),
			)
		);

		$name = sanitize_title( $name );

		// Check: never return an empty filename.
		return '' !== $name ? $name : 'aiisp-image-' . time();
	}

	/**
	 * Map a mime type to a file extension.
	 *
	 * @param string $mime Mime type.
	 * @return string
	 */
	private function extension_for_mime( $mime ) {
		$map = array(
			'image/jpeg' => 'jpg',
			'image/jpg'  => 'jpg',
			'image/png'  => 'png',
			'image/gif'  => 'gif',
			'image/webp' => 'webp',
		);

		return isset( $map[ $mime ] ) ? $map[ $mime ] : 'png';
	}
}
