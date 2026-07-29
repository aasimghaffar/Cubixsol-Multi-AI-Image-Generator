<?php
/**
 * Settings page shell.
 *
 * Renders the branded header, the vertical navigation and routes to
 * the active tab partial. Tabs are defined in one array, so adding a
 * tab means adding one line here plus one partial file.
 *
 * @package AIISP
 */

// Security check: block direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$aiisp_options = aiisp()->options();

/*
 * Tab definitions: slug => [ label, dashicon, view file ].
 * The view partial for each tab lives next to this file.
 */
$aiisp_tabs = array(
	'dashboard'  => array( __( 'Dashboard', 'cubixsol-multi-ai-image-generator' ), 'dashicons-chart-bar', 'tab-dashboard.php' ),
	'providers'  => array( __( 'AI Engines', 'cubixsol-multi-ai-image-generator' ), 'dashicons-superhero', 'tab-providers.php' ),
	'media'      => array( __( 'Media & SEO', 'cubixsol-multi-ai-image-generator' ), 'dashicons-format-image', 'tab-media.php' ),
	'post-types' => array( __( 'Post Types', 'cubixsol-multi-ai-image-generator' ), 'dashicons-admin-post', 'tab-post-types.php' ),
	'bulk'       => array( __( 'Bulk Generate', 'cubixsol-multi-ai-image-generator' ), 'dashicons-images-alt2', 'tab-bulk.php' ),
);

// Check: resolve and validate the requested tab (default: dashboard).
$aiisp_current = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'dashboard'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only navigation.
if ( ! isset( $aiisp_tabs[ $aiisp_current ] ) ) {
	$aiisp_current = 'dashboard';
}

// Tabs that contain a settings <form>; dashboard and bulk do not.
$aiisp_form_tabs = array( 'providers', 'media', 'post-types' );
?>
<div class="wrap aiisp-wrap">

	<!-- ================= Branded header ================= -->
	<div class="aiisp-header">
		<div class="aiisp-header-brand">
			<img class="aiisp-logo"
				src="<?php echo esc_url( AIISP_PLUGIN_URL . 'assets/images/logo.png' ); ?>"
				alt="<?php esc_attr_e( 'Cubixsol Multi AI Image Generator logo', 'cubixsol-multi-ai-image-generator' ); ?>"
				width="52" height="52" />
			<div>
				<h1><?php esc_html_e( 'Cubixsol Multi AI Image Generator', 'cubixsol-multi-ai-image-generator' ); ?></h1>
				<p><?php esc_html_e( 'Generate, import and optimize images without leaving WordPress.', 'cubixsol-multi-ai-image-generator' ); ?></p>
			</div>
		</div>
		<div class="aiisp-header-meta">
			<span class="aiisp-chip">
				<?php
				printf(
					/* translators: %s: plugin version */
					esc_html__( 'v%s', 'cubixsol-multi-ai-image-generator' ),
					esc_html( AIISP_VERSION )
				);
				?>
			</span>
			<?php
			// Live engine status chip — computed, never hardcoded.
			$aiisp_active_provider = aiisp()->providers()->get( (string) $aiisp_options->get( 'active_provider' ) );
			if ( $aiisp_active_provider ) :
				$aiisp_ready = $aiisp_active_provider->is_configured();
				?>
				<span class="aiisp-chip <?php echo $aiisp_ready ? 'aiisp-chip-ok' : 'aiisp-chip-warn'; ?>">
					<span class="aiisp-dot"></span>
					<?php echo esc_html( $aiisp_active_provider->get_label() ); ?>
					<?php echo $aiisp_ready ? esc_html__( '· ready', 'cubixsol-multi-ai-image-generator' ) : esc_html__( '· needs key', 'cubixsol-multi-ai-image-generator' ); ?>
				</span>
			<?php endif; ?>

			<!-- Quick launch into the Image Workspace. -->
			<a class="aiisp-chip aiisp-chip-action" href="<?php echo esc_url( admin_url( 'upload.php?page=aiisp-studio' ) ); ?>">
				<span class="dashicons dashicons-superhero"></span>
				<?php esc_html_e( 'Open Image Workspace', 'cubixsol-multi-ai-image-generator' ); ?>
			</a>
		</div>
	</div>

	<!-- ================= Body: sidebar nav + content ================= -->
	<div class="aiisp-body">

		<nav class="aiisp-sidenav" aria-label="<?php esc_attr_e( 'AI Image Workspace settings sections', 'cubixsol-multi-ai-image-generator' ); ?>">
			<?php foreach ( $aiisp_tabs as $aiisp_slug => $aiisp_tab ) : ?>
				<a class="aiisp-sidenav-item <?php echo $aiisp_current === $aiisp_slug ? 'is-active' : ''; ?>"
					href="<?php echo esc_url( add_query_arg( array( 'page' => 'aiisp-settings', 'tab' => $aiisp_slug ), admin_url( 'admin.php' ) ) ); ?>">
					<span class="dashicons <?php echo esc_attr( $aiisp_tab[1] ); ?>"></span>
					<?php echo esc_html( $aiisp_tab[0] ); ?>
				</a>
			<?php endforeach; ?>
		</nav>

		<main class="aiisp-content">
			<?php if ( in_array( $aiisp_current, $aiisp_form_tabs, true ) ) : ?>

				<form method="post"
					action="<?php echo esc_url( add_query_arg( array( 'page' => 'aiisp-settings', 'tab' => $aiisp_current ), admin_url( 'admin.php' ) ) ); ?>">
					<?php
					// CSRF protection for every settings save.
					wp_nonce_field( 'aiisp_save_settings_action', 'aiisp_settings_nonce_field' );

					$aiisp_partial = __DIR__ . '/' . $aiisp_tabs[ $aiisp_current ][2];

					// Check: partial must exist before requiring it.
					if ( is_readable( $aiisp_partial ) ) {
						require $aiisp_partial;
					}
					?>
					<div class="aiisp-save-bar">
						<button type="submit" name="aiisp_settings_submit" value="1" class="aiisp-btn aiisp-btn-primary">
							<span class="dashicons dashicons-saved"></span>
							<?php esc_html_e( 'Save Changes', 'cubixsol-multi-ai-image-generator' ); ?>
						</button>
					</div>
				</form>

			<?php else : ?>

				<?php
				$aiisp_partial = __DIR__ . '/' . $aiisp_tabs[ $aiisp_current ][2];

				// Check: partial must exist before requiring it.
				if ( is_readable( $aiisp_partial ) ) {
					require $aiisp_partial;
				}
				?>

			<?php endif; ?>
		</main>
	</div>
</div>
