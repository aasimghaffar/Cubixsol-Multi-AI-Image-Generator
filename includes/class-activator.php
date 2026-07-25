<?php
/**
 * Activation routines.
 *
 * @package AIISP
 */

namespace AIISP;

// Security check: block direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Runs on plugin activation and on version upgrades.
 */
class Activator {

	/**
	 * Activation entry point.
	 *
	 * @return void
	 */
	public static function activate() {
		// Check: user must be allowed to activate plugins at all.
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		Logger::maybe_create_table();
		self::seed_defaults();

		update_option( 'aiisp_version', AIISP_VERSION );
	}

	/**
	 * Version-aware upgrade check, hooked on admin_init by Plugin.
	 * Re-runs schema creation when the plugin was updated via FTP or
	 * auto-update (where the activation hook never fires).
	 *
	 * @return void
	 */
	public static function maybe_upgrade() {
		$installed = get_option( 'aiisp_version', '' );

		// Check: nothing to do when versions already match.
		if ( AIISP_VERSION === $installed ) {
			return;
		}

		Logger::maybe_create_table();
		self::seed_defaults();

		update_option( 'aiisp_version', AIISP_VERSION );
	}

	/**
	 * Seed default settings without overwriting anything the admin
	 * has already saved.
	 *
	 * @return void
	 */
	private static function seed_defaults() {
		$stored = get_option( AIISP_OPTION_KEY, array() );

		// Check: recover gracefully from a corrupted (non-array) option.
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		$defaults = array();
		foreach ( aiisp()->options()->get_schema() as $field => $def ) {
			$defaults[ $field ] = $def['default'];
		}

		// Default fallback order = every registered provider, free first.
		if ( empty( $stored['fallback_order'] ) ) {
			$defaults['fallback_order'] = array_keys( aiisp()->providers()->all() );
		}

		update_option( AIISP_OPTION_KEY, wp_parse_args( $stored, $defaults ) );
	}
}
