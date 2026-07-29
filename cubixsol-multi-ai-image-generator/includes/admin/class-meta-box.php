<?php
/**
 * Post editor meta box.
 *
 * @package AIISP
 */

namespace AIISP\Admin;

// Security check: block direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and renders the generator meta box on enabled post types.
 */
class Meta_Box {

	/**
	 * Style presets — dynamic and filterable so themes/extensions can
	 * add, remove or rename presets without touching plugin files.
	 *
	 * @return array slug => label.
	 */
	public static function get_style_presets() {
		$presets = array(
			'none'          => __( 'No preset', 'cubixsol-multi-ai-image-generator' ),
			'realistic'     => __( 'Realistic', 'cubixsol-multi-ai-image-generator' ),
			'photographic'  => __( 'Photographic', 'cubixsol-multi-ai-image-generator' ),
			'artistic'      => __( 'Artistic', 'cubixsol-multi-ai-image-generator' ),
			'digital-art'   => __( 'Digital Art', 'cubixsol-multi-ai-image-generator' ),
			'oil-painting'  => __( 'Oil Painting', 'cubixsol-multi-ai-image-generator' ),
			'watercolor'    => __( 'Watercolor', 'cubixsol-multi-ai-image-generator' ),
			'pencil-sketch' => __( 'Pencil Sketch', 'cubixsol-multi-ai-image-generator' ),
			'cartoon'       => __( 'Cartoon', 'cubixsol-multi-ai-image-generator' ),
			'anime'         => __( 'Anime', 'cubixsol-multi-ai-image-generator' ),
			'comic-book'    => __( 'Comic Book', 'cubixsol-multi-ai-image-generator' ),
			'pixel-art'     => __( 'Pixel Art', 'cubixsol-multi-ai-image-generator' ),
			'3d-render'     => __( '3D Render', 'cubixsol-multi-ai-image-generator' ),
			'low-poly'      => __( 'Low Poly', 'cubixsol-multi-ai-image-generator' ),
			'cyberpunk'     => __( 'Cyberpunk', 'cubixsol-multi-ai-image-generator' ),
			'steampunk'     => __( 'Steampunk', 'cubixsol-multi-ai-image-generator' ),
			'fantasy'       => __( 'Fantasy', 'cubixsol-multi-ai-image-generator' ),
			'surreal'       => __( 'Surreal', 'cubixsol-multi-ai-image-generator' ),
			'minimalist'    => __( 'Minimalist', 'cubixsol-multi-ai-image-generator' ),
			'vintage'       => __( 'Vintage', 'cubixsol-multi-ai-image-generator' ),
			'neon'          => __( 'Neon', 'cubixsol-multi-ai-image-generator' ),
			'isometric'     => __( 'Isometric', 'cubixsol-multi-ai-image-generator' ),
			'flat-design'   => __( 'Flat Design', 'cubixsol-multi-ai-image-generator' ),
		);

		/**
		 * Filter the style presets shown in the generator dropdowns.
		 *
		 * @param array $presets slug => label.
		 */
		return apply_filters( 'aiisp_style_presets', $presets );
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'add_meta_boxes', array( $this, 'register' ) );
	}

	/**
	 * Register the meta box on every enabled post type (dynamic —
	 * driven entirely by the Post Types setting).
	 *
	 * @return void
	 */
	public function register() {
		$targets = (array) aiisp()->options()->get( 'post_types', array() );

		foreach ( $targets as $post_type ) {
			// Check: skip types that were unregistered since saving.
			if ( ! post_type_exists( $post_type ) ) {
				continue;
			}

			add_meta_box(
				'aiisp-generator',
				__( 'Cubixsol Multi AI Image Generator', 'cubixsol-multi-ai-image-generator' ),
				array( $this, 'render' ),
				$post_type,
				'side',
				'default'
			);
		}
	}

	/**
	 * Render the meta box view.
	 *
	 * @param \WP_Post $post Current post.
	 * @return void
	 */
	public function render( $post ) {
		// Check: object-level permission.
		if ( ! current_user_can( 'edit_post', $post->ID ) ) {
			return;
		}

		$view = AIISP_PLUGIN_DIR . 'includes/admin/views/meta-box.php';

		// Check: view file must exist.
		if ( ! is_readable( $view ) ) {
			return;
		}

		$presets = self::get_style_presets();
		$sources = aiisp()->stock()->all();

		require $view;
	}
}
