<?php
/**
 * Settings save handler — schema-driven, so it never needs editing
 * when new fields or providers are added.
 *
 * @package AIISP
 */

namespace AIISP\Admin;

// Security check: block direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Processes the settings form POST.
 */
class Settings {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'admin_init', array( $this, 'maybe_save' ) );
	}

	/**
	 * Handle the settings form submission.
	 *
	 * @return void
	 */
	public function maybe_save() {
		// Check: only react to our own submit button.
		if ( ! isset( $_POST['aiisp_settings_submit'] ) ) {
			return;
		}

		// Check: nonce must verify (CSRF protection).
		if (
			! isset( $_POST['aiisp_settings_nonce_field'] )
			|| ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['aiisp_settings_nonce_field'] ) ), 'aiisp_save_settings_action' )
		) {
			wp_die( esc_html__( 'Security check failed. Please go back and try again.', 'cubixsol-multi-ai-image-generator' ) );
		}

		// Check: only administrators may change settings.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to change these settings.', 'cubixsol-multi-ai-image-generator' ) );
		}

		// Collect the aiisp_-prefixed fields, strip the prefix, and let
		// the schema-driven Options::save() sanitize every value.
		$input = array();
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified above.
		foreach ( wp_unslash( $_POST ) as $key => $value ) {
			if ( 0 === strpos( $key, 'aiisp_' ) ) {
				$input[ substr( $key, 6 ) ] = $value;
			}
		}

		aiisp()->options()->save( $input );

		add_action(
			'admin_notices',
			static function () {
				printf(
					'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
					esc_html__( 'Settings saved.', 'cubixsol-multi-ai-image-generator' )
				);
			}
		);
	}
}
