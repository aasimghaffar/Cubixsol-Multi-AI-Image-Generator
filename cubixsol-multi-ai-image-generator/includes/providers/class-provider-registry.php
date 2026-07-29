<?php
/**
 * AI provider registry.
 *
 * The single dynamic source of every engine the plugin knows about.
 * All UI (settings cards, fallback list, key fields, size dropdown)
 * is generated from this registry, so adding an engine here — or via
 * the aiisp_register_providers filter — lights it up everywhere with
 * no template edits.
 *
 * @package AIISP
 */

namespace AIISP\Providers;

// Security check: block direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Holds and serves the registered engines.
 */
class Provider_Registry {

	/**
	 * Registered engines keyed by slug.
	 *
	 * @var Provider_Base[]
	 */
	private $providers = array();

	/**
	 * Build the registry.
	 */
	public function __construct() {
		$defaults = array(
			new Pollinations(),
			new Openai(),
			new Gemini(),
			new Grok(),
			new Stability(),
			new Flux(),
			new Leonardo(),
			new Ideogram(),
			new Deepai(),
		);

		foreach ( $defaults as $provider ) {
			$this->register( $provider );
		}

		/**
		 * Let extensions register additional engines. Any object
		 * extending Provider_Base is accepted.
		 *
		 * @param Provider_Registry $registry This registry.
		 */
		do_action( 'aiisp_register_providers', $this );
	}

	/**
	 * Register one engine, with type + duplicate checks.
	 *
	 * @param Provider_Base $provider Engine instance.
	 * @return bool True when registered.
	 */
	public function register( $provider ) {
		// Check: must extend the base class.
		if ( ! $provider instanceof Provider_Base ) {
			return false;
		}

		$slug = sanitize_key( $provider->get_slug() );

		// Check: slug must be non-empty and not already taken.
		if ( '' === $slug || isset( $this->providers[ $slug ] ) ) {
			return false;
		}

		$this->providers[ $slug ] = $provider;
		return true;
	}

	/**
	 * All registered engines.
	 *
	 * @return Provider_Base[] keyed by slug.
	 */
	public function all() {
		return $this->providers;
	}

	/**
	 * One engine by slug.
	 *
	 * @param string $slug Engine slug.
	 * @return Provider_Base|null
	 */
	public function get( $slug ) {
		$slug = sanitize_key( $slug );
		return isset( $this->providers[ $slug ] ) ? $this->providers[ $slug ] : null;
	}

	/**
	 * Union of every engine's supported sizes — feeds the dynamic
	 * size dropdown so no size list is ever hardcoded in a view.
	 *
	 * @return string[] Sorted "WxH" strings.
	 */
	public function get_all_sizes() {
		$sizes = array();

		foreach ( $this->providers as $provider ) {
			$sizes = array_merge( $sizes, $provider->get_supported_sizes() );
		}

		$sizes = array_values( array_unique( $sizes ) );

		// Sort by pixel area so the dropdown reads small → large.
		usort(
			$sizes,
			static function ( $a, $b ) {
				list( $aw, $ah ) = array_map( 'absint', array_pad( explode( 'x', $a ), 2, 0 ) );
				list( $bw, $bh ) = array_map( 'absint', array_pad( explode( 'x', $b ), 2, 0 ) );
				return ( $aw * $ah ) <=> ( $bw * $bh );
			}
		);

		return $sizes;
	}

	/**
	 * Ordered execution queue: active engine first, then the admin's
	 * fallback order — including only engines that are configured.
	 *
	 * @return Provider_Base[]
	 */
	public function get_execution_queue() {
		$options = aiisp()->options();
		$active  = (string) $options->get( 'active_provider', 'pollinations' );
		$order   = (array) $options->get( 'fallback_order', array() );
		$enabled = (bool) $options->get( 'enable_fallback', 1 );

		$queue = array();

		$primary = $this->get( $active );
		if ( $primary && $primary->is_configured() ) {
			$queue[ $primary->get_slug() ] = $primary;
		}

		if ( $enabled ) {
			foreach ( $order as $slug ) {
				$slug = sanitize_key( (string) $slug );

				// Check: skip duplicates and unknown slugs.
				if ( isset( $queue[ $slug ] ) ) {
					continue;
				}

				$provider = $this->get( $slug );
				if ( $provider && $provider->is_configured() ) {
					$queue[ $slug ] = $provider;
				}
			}
		}

		return array_values( $queue );
	}
}
