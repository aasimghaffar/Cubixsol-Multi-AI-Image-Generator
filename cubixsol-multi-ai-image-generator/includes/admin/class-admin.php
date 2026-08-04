<?php
/**
 * Admin bootstrap: menu registration, asset loading, notices.
 *
 * @package AIISP
 */

namespace AIISP\Admin;

// Security check: block direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the admin menu and loads scoped assets only on plugin screens.
 */
class Admin {

	/**
	 * Hook suffix returned by add_menu_page (for targeted enqueues).
	 *
	 * @var string
	 */
	private $page_hook = '';

	/**
	 * Hook suffix of the Media → AI Image Workspace submenu page.
	 *
	 * @var string
	 */
	private $studio_hook = '';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_notices', array( $this, 'missing_key_notice' ) );
		add_filter( 'plugin_action_links_' . AIISP_PLUGIN_BASENAME, array( $this, 'plugin_action_links' ) );
	}

	/**
	 * Add quick links to the plugin's row on the Plugins screen so
	 * admins can jump straight to Settings (or the Workspace)
	 * without hunting through the menu.
	 *
	 * @param string[] $links Existing action links (Deactivate, ...).
	 * @return string[]
	 */
	public function plugin_action_links( $links ) {
		$custom = array(
			'aiisp_settings'  => sprintf(
				'<a href="%s">%s</a>',
				esc_url( admin_url( 'admin.php?page=aiisp-settings' ) ),
				esc_html__( 'Settings', 'cubixsol-multi-ai-image-generator' )
			),
			'aiisp_workspace' => sprintf(
				'<a href="%s">%s</a>',
				esc_url( admin_url( 'upload.php?page=aiisp-studio' ) ),
				esc_html__( 'Image Workspace', 'cubixsol-multi-ai-image-generator' )
			),
		);

		// Custom links first, core links (Deactivate) after — the
		// convention core plugins follow.
		return array_merge( $custom, $links );
	}

	/**
	 * Register the top-level menu page.
	 *
	 * @return void
	 */
	public function register_menu() {
		$this->page_hook = add_menu_page(
			__( 'Cubixsol Multi AI Image Generator', 'cubixsol-multi-ai-image-generator' ),
			__( 'AI Image Workspace', 'cubixsol-multi-ai-image-generator' ),
			'manage_options',
			'aiisp-settings',
			array( $this, 'render_page' ),
			'dashicons-format-image',
			81
		);

		// Image Workspace under the core Media menu: generate
		// AI images or search stock, preview in a grid, then save the
		// keepers to the Media Library.
		$this->studio_hook = add_submenu_page(
			'upload.php',
			__( 'AI Image Workspace', 'cubixsol-multi-ai-image-generator' ),
			__( 'AI Image Workspace', 'cubixsol-multi-ai-image-generator' ),
			'upload_files',
			'aiisp-studio',
			array( $this, 'render_studio' )
		);
	}

	/**
	 * Render the Image Workspace (Media → AI Image Workspace).
	 *
	 * @return void
	 */
	public function render_studio() {
		// Check: double-gate the capability.
		if ( ! current_user_can( 'upload_files' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'cubixsol-multi-ai-image-generator' ) );
		}

		$view = AIISP_PLUGIN_DIR . 'includes/admin/views/studio-page.php';

		// Check: the view file must exist.
		if ( ! is_readable( $view ) ) {
			wp_die( esc_html__( 'Cubixsol Multi AI Image Generator view files are missing. Reinstall the plugin.', 'cubixsol-multi-ai-image-generator' ) );
		}

		require $view;
	}

	/**
	 * Render the settings shell (tab routing happens in the view).
	 *
	 * @return void
	 */
	public function render_page() {
		// Check: double-gate the capability even though the menu already does.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'cubixsol-multi-ai-image-generator' ) );
		}

		$view = AIISP_PLUGIN_DIR . 'includes/admin/views/settings-page.php';

		// Check: the view file must exist (broken deploys happen).
		if ( ! is_readable( $view ) ) {
			wp_die( esc_html__( 'Cubixsol Multi AI Image Generator view files are missing. Reinstall the plugin.', 'cubixsol-multi-ai-image-generator' ) );
		}

		require $view;
	}

	/**
	 * Enqueue plugin CSS/JS only on plugin screens and enabled editors,
	 * keeping every other admin page untouched.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_assets( $hook ) {
		$is_settings = ( $hook === $this->page_hook );
		$is_studio   = ( $hook === $this->studio_hook );

		// Editor detection via the screen object: get_post_type() is
		// unreliable during admin_enqueue_scripts (it caused the meta
		// box to render without its JS/CSS), while the screen's
		// post_type is populated on both post.php and post-new.php.
		$screen    = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		$is_editor = $screen
			&& 'post' === $screen->base
			&& in_array( (string) $screen->post_type, (array) aiisp()->options()->get( 'post_types', array() ), true );

		// Check: bail on every unrelated admin screen.
		if ( ! $is_settings && ! $is_editor && ! $is_studio ) {
			return;
		}

		wp_enqueue_style(
			'aiisp-admin',
			AIISP_PLUGIN_URL . 'assets/css/aiisp-admin.css',
			array(),
			AIISP_VERSION
		);

		$deps = array( 'jquery' );
		if ( $is_settings ) {
			$deps[] = 'jquery-ui-sortable'; // Ships with WP core — drag & drop fallback list.
		}

		wp_enqueue_script(
			'aiisp-admin',
			AIISP_PLUGIN_URL . 'assets/js/aiisp-admin.js',
			$deps,
			AIISP_VERSION,
			true
		);

		// Resolve the edited post ID from the global — reliable on
		// post.php and post-new.php during admin_enqueue_scripts.
		$post_id = 0;
		if ( $is_editor && isset( $GLOBALS['post'] ) && $GLOBALS['post'] instanceof \WP_Post ) {
			$post_id = (int) $GLOBALS['post']->ID;
		}

		wp_localize_script(
			'aiisp-admin',
			'aiispData',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'aiisp_ajax_nonce' ),
				'postId'  => $post_id,
				'autoFeatured' => (bool) aiisp()->options()->get( 'auto_featured', 0 ),
				'i18n'    => array(
					'sending'      => __( 'Contacting engine…', 'cubixsol-multi-ai-image-generator' ),
					'downloading'  => __( 'Saving to Media Library…', 'cubixsol-multi-ai-image-generator' ),
					'completed'    => __( 'Done', 'cubixsol-multi-ai-image-generator' ),
					'confirmClear' => __( 'Permanently delete all generation history logs?', 'cubixsol-multi-ai-image-generator' ),
					'genericError' => __( 'An unexpected error occurred. Please try again.', 'cubixsol-multi-ai-image-generator' ),
					'featuredSet'  => __( 'Featured image set ✓', 'cubixsol-multi-ai-image-generator' ),
					'queued'       => __( 'Queued…', 'cubixsol-multi-ai-image-generator' ),
					'generating'   => __( 'Generating…', 'cubixsol-multi-ai-image-generator' ),
					'noPosts'      => __( 'Every post of this type already has a featured image.', 'cubixsol-multi-ai-image-generator' ),
					'selectPosts'  => __( 'Select at least one post first.', 'cubixsol-multi-ai-image-generator' ),
					'scanFirst'    => __( 'Scan for posts first.', 'cubixsol-multi-ai-image-generator' ),
					'emptyPrompt'  => __( 'Please enter an image prompt first.', 'cubixsol-multi-ai-image-generator' ),
					'testing'      => __( 'Testing…', 'cubixsol-multi-ai-image-generator' ),
					'testKey'      => __( 'Test key', 'cubixsol-multi-ai-image-generator' ),
					'saving'       => __( 'Saving…', 'cubixsol-multi-ai-image-generator' ),
					'saved'        => __( 'Saved to gallery', 'cubixsol-multi-ai-image-generator' ),
					'saveGallery'  => __( 'Save to gallery', 'cubixsol-multi-ai-image-generator' ),
					'copied'       => __( 'Copied!', 'cubixsol-multi-ai-image-generator' ),
					'copy'         => __( 'Copy link', 'cubixsol-multi-ai-image-generator' ),
					'noResults'    => __( 'No results found.', 'cubixsol-multi-ai-image-generator' ),
				),
			)
		);
	}

	/**
	 * Dismissible error notice when the active engine has no key.
	 * Shown only on plugin/editor screens to avoid dashboard noise.
	 *
	 * @return void
	 */
	public function missing_key_notice() {
		// Check: only admins can act on this notice.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		// Check: restrict the notice to relevant screens.
		if ( ! $screen || ( false === strpos( (string) $screen->id, 'aiisp' ) && 'post' !== $screen->base ) ) {
			return;
		}

		$active   = (string) aiisp()->options()->get( 'active_provider', 'pollinations' );
		$provider = aiisp()->providers()->get( $active );

		// Check: notice fires only when the engine exists and lacks a key.
		if ( ! $provider || $provider->is_configured() ) {
			return;
		}

		printf(
			'<div class="notice notice-error is-dismissible"><p><strong>%1$s</strong> %2$s</p></div>',
			esc_html__( 'Cubixsol Multi AI Image Generator:', 'cubixsol-multi-ai-image-generator' ),
			esc_html(
				sprintf(
					/* translators: %s: provider label */
					__( 'the active engine (%s) needs an API key before it can generate images.', 'cubixsol-multi-ai-image-generator' ),
					$provider->get_label()
				)
			)
		);
	}
}
