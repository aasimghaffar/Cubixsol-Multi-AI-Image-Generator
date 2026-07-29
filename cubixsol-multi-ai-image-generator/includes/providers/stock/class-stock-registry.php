<?php
/**
 * Stock source registry — the dynamic list of stock providers.
 * The meta box dropdown and Options schema are generated from it.
 *
 * @package AIISP
 */

namespace AIISP\Providers\Stock;

// Security check: block direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Holds and serves the registered stock sources.
 */
class Stock_Registry {

	/**
	 * Registered sources keyed by slug.
	 *
	 * @var Stock_Base[]
	 */
	private $sources = array();

	/**
	 * Build the registry.
	 */
	public function __construct() {
		$defaults = array(
			new Openverse(),
			new Pexels(),
			new Pixabay(),
			new Unsplash(),
			new Giphy(),
		);

		foreach ( $defaults as $source ) {
			$this->register( $source );
		}

		/**
		 * Let extensions register additional stock sources.
		 *
		 * @param Stock_Registry $registry This registry.
		 */
		do_action( 'aiisp_register_stock_sources', $this );
	}

	/**
	 * Register one source with type + duplicate checks.
	 *
	 * @param Stock_Base $source Source instance.
	 * @return bool
	 */
	public function register( $source ) {
		// Check: must extend the base class.
		if ( ! $source instanceof Stock_Base ) {
			return false;
		}

		$slug = sanitize_key( $source->get_slug() );

		// Check: slug must be non-empty and unique.
		if ( '' === $slug || isset( $this->sources[ $slug ] ) ) {
			return false;
		}

		$this->sources[ $slug ] = $source;
		return true;
	}

	/**
	 * All registered sources.
	 *
	 * @return Stock_Base[] keyed by slug.
	 */
	public function all() {
		return $this->sources;
	}

	/**
	 * One source by slug.
	 *
	 * @param string $slug Source slug.
	 * @return Stock_Base|null
	 */
	public function get( $slug ) {
		$slug = sanitize_key( $slug );
		return isset( $this->sources[ $slug ] ) ? $this->sources[ $slug ] : null;
	}
}
