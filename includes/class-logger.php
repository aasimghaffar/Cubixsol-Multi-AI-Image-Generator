<?php
/**
 * Generation history logger.
 *
 * All access to the custom log table goes through this class, and
 * every method verifies the table exists first (self-healing if a
 * host migration or failed activation dropped it).
 *
 * @package AIISP
 */

namespace AIISP;

// Security check: block direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reads and writes the {prefix}aiisp_generation_log table.
 */
class Logger {

	/**
	 * Fully-prefixed table name.
	 *
	 * @return string
	 */
	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'aiisp_generation_log';
	}

	/**
	 * Check whether the log table exists in the database.
	 *
	 * @return bool
	 */
	public static function table_exists() {
		global $wpdb;

		$table = self::table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- schema introspection.
		$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );

		return $found === $table;
	}

	/**
	 * Create the table if (and only if) it does not already exist.
	 *
	 * dbDelta is idempotent, but we still gate it behind an explicit
	 * existence check so activation on large sites never re-runs DDL
	 * unnecessarily.
	 *
	 * @return void
	 */
	public static function maybe_create_table() {
		if ( self::table_exists() ) {
			return; // Check passed: table already present, nothing to do.
		}

		global $wpdb;

		$table   = self::table();
		$collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			created_at DATETIME NOT NULL,
			provider VARCHAR(60) NOT NULL,
			prompt TEXT NOT NULL,
			resolution VARCHAR(20) NOT NULL DEFAULT '',
			post_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			attachment_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			status VARCHAR(20) NOT NULL DEFAULT 'success',
			message TEXT NULL,
			PRIMARY KEY  (id),
			KEY created_at (created_at),
			KEY provider (provider)
		) {$collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Insert a log row.
	 *
	 * @param array $args {
	 *     @type string $provider      Provider slug.
	 *     @type string $prompt        Prompt text.
	 *     @type string $resolution    e.g. 1024x1024.
	 *     @type int    $post_id       Associated post.
	 *     @type int    $attachment_id Created attachment.
	 *     @type string $status        success|fail.
	 *     @type string $message       Details / error text.
	 * }
	 * @return void
	 */
	public function add( $args ) {
		// Check: self-heal a missing table before writing.
		self::maybe_create_table();

		// Check: still missing (DB user lacks CREATE)? Fail silently
		// rather than throwing — logging must never break generation.
		if ( ! self::table_exists() ) {
			return;
		}

		global $wpdb;

		$defaults = array(
			'provider'      => '',
			'prompt'        => '',
			'resolution'    => '',
			'post_id'       => 0,
			'attachment_id' => 0,
			'status'        => 'success',
			'message'       => '',
		);
		$args = wp_parse_args( $args, $defaults );

		// Check: status may only ever be one of two values.
		$status = in_array( $args['status'], array( 'success', 'fail' ), true ) ? $args['status'] : 'fail';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- custom log table.
		$wpdb->insert(
			self::table(),
			array(
				'created_at'    => current_time( 'mysql' ),
				'provider'      => sanitize_key( $args['provider'] ),
				'prompt'        => sanitize_textarea_field( $args['prompt'] ),
				'resolution'    => sanitize_text_field( $args['resolution'] ),
				'post_id'       => absint( $args['post_id'] ),
				'attachment_id' => absint( $args['attachment_id'] ),
				'status'        => $status,
				'message'       => sanitize_textarea_field( $args['message'] ),
			),
			array( '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s' )
		);
	}

	/**
	 * Fetch the most recent rows.
	 *
	 * @param int $limit Max rows (1–200).
	 * @return object[]
	 */
	public function recent( $limit = 50 ) {
		// Check: table must exist before we query it.
		if ( ! self::table_exists() ) {
			return array();
		}

		global $wpdb;

		// Check: clamp the limit into a sane range.
		$limit = max( 1, min( 200, absint( $limit ) ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- custom log table read.
		return (array) $wpdb->get_results(
			$wpdb->prepare( 'SELECT * FROM %i ORDER BY created_at DESC LIMIT %d', self::table(), $limit )
		);
	}

	/**
	 * Aggregate stats for the dashboard cards.
	 *
	 * @return array{total:int, success:int, fail:int}
	 */
	public function stats() {
		$empty = array( 'total' => 0, 'success' => 0, 'fail' => 0 );

		if ( ! self::table_exists() ) {
			return $empty;
		}

		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- custom log table read.
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT COUNT(*) AS total,
					SUM(status = 'success') AS success,
					SUM(status = 'fail') AS fail
				FROM %i",
				self::table()
			),
			ARRAY_A
		);

		// Check: query may return null on an empty/broken table.
		if ( ! is_array( $row ) ) {
			return $empty;
		}

		return array(
			'total'   => absint( $row['total'] ),
			'success' => absint( $row['success'] ),
			'fail'    => absint( $row['fail'] ),
		);
	}

	/**
	 * Delete every row (Clear History action).
	 *
	 * @return bool True on success.
	 */
	public function clear() {
		if ( ! self::table_exists() ) {
			return true; // Nothing to clear counts as success.
		}

		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- admin-initiated clear of the plugin's own log table.
		return false !== $wpdb->query( $wpdb->prepare( 'TRUNCATE TABLE %i', self::table() ) );
	}
}
