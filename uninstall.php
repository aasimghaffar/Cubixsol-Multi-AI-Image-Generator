<?php
/**
 * Uninstall handler.
 *
 * Runs only when the plugin is DELETED from the Plugins screen —
 * never on deactivation. Removes every trace: options, transients
 * and the custom log table.
 *
 * @package AIISP
 */

// Security check: this file may only run during a WP uninstall.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Check: only users who can delete plugins reach this, but re-verify.
if ( ! current_user_can( 'activate_plugins' ) ) {
	return;
}

global $wpdb;

// 1. Options.
delete_option( 'aiisp_settings' );
delete_option( 'aiisp_version' );

// 2. Transients.
delete_transient( 'aiisp_daily_usage' );

// 3. Custom table — check it exists before dropping.
$aiisp_table = $wpdb->prefix . 'aiisp_generation_log';

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- uninstall cleanup.
$aiisp_found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $aiisp_table ) );

if ( $aiisp_found === $aiisp_table ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- intentional: complete removal of the plugin's own table on uninstall, per repository data-cleanup guidelines.
	$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $aiisp_table ) );
}

// 4. Attachment provenance meta (optional data hygiene).
delete_post_meta_by_key( '_aiisp_generated' );
delete_post_meta_by_key( '_aiisp_prompt' );
