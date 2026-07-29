<?php
/**
 * Plugin service container and hook orchestrator.
 *
 * @package AIISP
 */

namespace AIISP;

use AIISP\Admin\Admin;
use AIISP\Admin\Meta_Box;
use AIISP\Admin\Settings;
use AIISP\Core\Ajax;
use AIISP\Core\Usage;
use AIISP\Providers\Provider_Registry;
use AIISP\Providers\Stock\Stock_Registry;

// Security check: block direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Singleton: builds shared services once and wires all hooks.
 */
final class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Shared service objects, built lazily.
	 *
	 * @var array<string, object>
	 */
	private $services = array();

	/**
	 * Get (and boot) the singleton.
	 *
	 * @return Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
			self::$instance->boot();
		}
		return self::$instance;
	}

	/**
	 * Private constructor — use instance().
	 */
	private function __construct() {}

	/**
	 * Prevent cloning of the singleton.
	 */
	private function __clone() {}

	/**
	 * Prevent unserialization of the singleton.
	 *
	 * @throws \Exception Always.
	 */
	public function __wakeup() {
		throw new \Exception( 'Cannot unserialize singleton.' );
	}

	/**
	 * Wire every hook. Admin-only services load only in wp-admin so
	 * the plugin adds zero weight to front-end requests.
	 *
	 * @return void
	 */
	private function boot() {
		if ( is_admin() ) {
			( new Admin() )->init();
			( new Settings() )->init();
			( new Meta_Box() )->init();
			( new Ajax() )->init();

			// Self-healing schema check after silent (FTP/auto) updates.
			add_action( 'admin_init', array( Activator::class, 'maybe_upgrade' ) );
		}
	}

	/* ---------------------------------------------------------------------
	 * Service accessors (lazy singletons)
	 * ------------------------------------------------------------------ */

	/**
	 * Options repository.
	 *
	 * @return Options
	 */
	public function options() {
		if ( ! isset( $this->services['options'] ) ) {
			$this->services['options'] = new Options();
		}
		return $this->services['options'];
	}

	/**
	 * AI provider registry.
	 *
	 * @return Provider_Registry
	 */
	public function providers() {
		if ( ! isset( $this->services['providers'] ) ) {
			$this->services['providers'] = new Provider_Registry();
		}
		return $this->services['providers'];
	}

	/**
	 * Stock photo source registry.
	 *
	 * @return Stock_Registry
	 */
	public function stock() {
		if ( ! isset( $this->services['stock'] ) ) {
			$this->services['stock'] = new Stock_Registry();
		}
		return $this->services['stock'];
	}

	/**
	 * Generation history logger.
	 *
	 * @return Logger
	 */
	public function logger() {
		if ( ! isset( $this->services['logger'] ) ) {
			$this->services['logger'] = new Logger();
		}
		return $this->services['logger'];
	}

	/**
	 * Daily usage counter.
	 *
	 * @return Usage
	 */
	public function usage() {
		if ( ! isset( $this->services['usage'] ) ) {
			$this->services['usage'] = new Usage();
		}
		return $this->services['usage'];
	}
}
