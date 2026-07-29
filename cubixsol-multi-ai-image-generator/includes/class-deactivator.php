<?php
/**
 * Deactivation routines.
 *
 * Per WordPress.org guidelines, deactivation must NOT delete user
 * data — full removal happens only in uninstall.php.
 *
 * @package AIISP
 */

namespace AIISP;

// Security check: block direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cleans up transient state on deactivation.
 */
class Deactivator {

	/**
	 * Deactivation entry point.
	 *
	 * @return void
	 */
	public static function deactivate() {
		// Check: user must be allowed to deactivate plugins.
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		// Remove volatile state only — options and the log table stay.
		delete_transient( 'aiisp_daily_usage' );
	}
}
