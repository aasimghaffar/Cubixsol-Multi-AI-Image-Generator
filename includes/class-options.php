<?php
/**
 * Options repository.
 *
 * Single source of truth for every plugin setting. All defaults,
 * sanitization rules and validation live in the schema below, so
 * the settings form, the save handler and the runtime readers are
 * generated dynamically from one definition — nothing is hardcoded
 * in the views or handlers.
 *
 * @package AIISP
 */

namespace AIISP;

// Security check: block direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reads/writes the aiisp_settings option with schema-driven
 * sanitization and defaults.
 */
class Options {

	/**
	 * In-memory cache of the merged option array.
	 *
	 * @var array|null
	 */
	private $cache = null;

	/**
	 * Build the full settings schema.
	 *
	 * Provider API-key fields are generated dynamically from the
	 * provider and stock-source registries, so adding a new engine
	 * automatically creates its setting, its form field and its
	 * sanitizer with zero extra wiring.
	 *
	 * @return array field => [ type, default, sanitize ].
	 */
	public function get_schema() {
		$schema = array(
			'active_provider'  => array(
				'type'     => 'string',
				'default'  => 'pollinations',
				'sanitize' => 'sanitize_key',
			),
			'fallback_order'   => array(
				'type'     => 'array',
				'default'  => array(),
				'sanitize' => array( $this, 'sanitize_slug_list' ),
			),
			'enable_fallback'  => array(
				'type'     => 'bool',
				'default'  => 1,
				'sanitize' => 'absint',
			),
			'image_size'       => array(
				'type'     => 'string',
				'default'  => '1024x1024',
				'sanitize' => array( $this, 'sanitize_size' ),
			),
			'alt_pattern'      => array(
				'type'     => 'string',
				'default'  => '{title} - {prompt}',
				'sanitize' => 'sanitize_text_field',
			),
			'filename_pattern' => array(
				'type'     => 'string',
				'default'  => '{title}-ai-image',
				'sanitize' => 'sanitize_text_field',
			),
			'post_types'       => array(
				'type'     => 'array',
				'default'  => array( 'post', 'page' ),
				'sanitize' => array( $this, 'sanitize_post_types' ),
			),
			'daily_limit'      => array(
				'type'     => 'int',
				'default'  => 100,
				'sanitize' => 'absint',
			),
			'prompt_prefix'    => array(
				'type'     => 'string',
				'default'  => '',
				'sanitize' => 'sanitize_text_field',
			),
			'prompt_suffix'    => array(
				'type'     => 'string',
				'default'  => '',
				'sanitize' => 'sanitize_text_field',
			),
			'negative_prompt'  => array(
				'type'     => 'string',
				'default'  => '',
				'sanitize' => 'sanitize_text_field',
			),
			'default_style'    => array(
				'type'     => 'string',
				'default'  => 'none',
				'sanitize' => array( $this, 'sanitize_style' ),
			),
			'auto_featured'    => array(
				'type'     => 'bool',
				'default'  => 0,
				'sanitize' => 'absint',
			),
		);

		// Dynamically add one masked key field per key-requiring engine.
		foreach ( aiisp()->providers()->all() as $slug => $provider ) {
			if ( $provider->requires_api_key() ) {
				$schema[ $slug . '_api_key' ] = array(
					'type'     => 'secret',
					'default'  => '',
					'sanitize' => 'sanitize_text_field',
				);
			}
		}

		// Same for stock photo sources.
		foreach ( aiisp()->stock()->all() as $slug => $source ) {
			if ( $source->requires_api_key() ) {
				$schema[ $slug . '_api_key' ] = array(
					'type'     => 'secret',
					'default'  => '',
					'sanitize' => 'sanitize_text_field',
				);
			}
		}

		/**
		 * Filter the settings schema. Lets extensions register their
		 * own fields that then flow through save/read automatically.
		 *
		 * @param array $schema Field definitions.
		 */
		return apply_filters( 'aiisp_options_schema', $schema );
	}

	/**
	 * Get one setting (or all) merged with schema defaults.
	 *
	 * @param string|null $key      Setting name, or null for the full array.
	 * @param mixed       $fallback Value when the key is unknown.
	 * @return mixed
	 */
	public function get( $key = null, $fallback = '' ) {
		if ( null === $this->cache ) {
			$stored = get_option( AIISP_OPTION_KEY, array() );

			// Check: stored value must be an array; recover if corrupted.
			if ( ! is_array( $stored ) ) {
				$stored = array();
			}

			$defaults = array();
			foreach ( $this->get_schema() as $field => $def ) {
				$defaults[ $field ] = $def['default'];
			}

			$this->cache = wp_parse_args( $stored, $defaults );
		}

		if ( null === $key ) {
			return $this->cache;
		}

		return array_key_exists( $key, $this->cache ) ? $this->cache[ $key ] : $fallback;
	}

	/**
	 * Persist a raw input array after schema sanitization.
	 *
	 * Unknown fields are dropped; missing fields keep their stored value.
	 *
	 * @param array $input Raw (unslashed) input keyed by field name.
	 * @return void
	 */
	public function save( $input ) {
		// Check: refuse non-array input outright.
		if ( ! is_array( $input ) ) {
			return;
		}

		$current = $this->get();

		foreach ( $this->get_schema() as $field => $def ) {
			if ( ! array_key_exists( $field, $input ) ) {
				continue; // Field not submitted — keep the stored value.
			}

			$value = $input[ $field ];

			// Secrets: an empty submit means "keep the saved key" unless
			// the matching _clear flag was also submitted.
			if ( 'secret' === $def['type'] && '' === trim( (string) $value ) ) {
				if ( empty( $input[ $field . '_clear' ] ) ) {
					continue;
				}
				$current[ $field ] = '';
				continue;
			}

			$current[ $field ] = is_callable( $def['sanitize'] )
				? call_user_func( $def['sanitize'], $value )
				: sanitize_text_field( (string) $value );
		}

		update_option( AIISP_OPTION_KEY, $current );
		$this->cache = null; // Bust the in-memory cache after every write.
	}

	/* ---------------------------------------------------------------------
	 * Field sanitizers
	 * ------------------------------------------------------------------ */

	/**
	 * Sanitize a list of provider slugs against the live registry.
	 *
	 * @param mixed $value Comma string or array of slugs.
	 * @return string[]
	 */
	public function sanitize_slug_list( $value ) {
		$list  = is_array( $value ) ? $value : explode( ',', (string) $value );
		$valid = array_keys( aiisp()->providers()->all() );

		return array_values( array_intersect( array_map( 'sanitize_key', $list ), $valid ) );
	}

	/**
	 * Sanitize the image size against the dynamic union of all
	 * provider-supported sizes.
	 *
	 * @param mixed $value Raw size string.
	 * @return string
	 */
	public function sanitize_size( $value ) {
		$value = sanitize_text_field( (string) $value );
		$valid = aiisp()->providers()->get_all_sizes();

		return in_array( $value, $valid, true ) ? $value : '1024x1024';
	}

	/**
	 * Sanitize a style preset slug against the live preset list.
	 *
	 * @param mixed $value Raw preset slug.
	 * @return string
	 */
	public function sanitize_style( $value ) {
		$value   = sanitize_key( (string) $value );
		$presets = \AIISP\Admin\Meta_Box::get_style_presets();

		return isset( $presets[ $value ] ) ? $value : 'none';
	}

	/**
	 * Sanitize post type slugs against the registered post types.
	 *
	 * @param mixed $value Raw array of slugs.
	 * @return string[]
	 */
	public function sanitize_post_types( $value ) {
		$list       = array_map( 'sanitize_key', (array) $value );
		$registered = get_post_types( array( 'show_ui' => true ), 'names' );
		unset( $registered['attachment'] );

		return array_values( array_intersect( $list, $registered ) );
	}
}
