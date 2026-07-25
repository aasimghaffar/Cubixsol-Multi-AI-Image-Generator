<?php
/**
 * Image Workspace (Media → AI Image Workspace).
 *
 * A standalone playground: generate one or several AI images from a
 * prompt, or search stock libraries — both render into the same
 * preview grid. Each card offers a full-size lightbox (eye) and a
 * "Save to gallery" action; saving shows a popup with the permanent
 * site URL and a copy button. Nothing is written to the Media
 * Library until the admin explicitly saves an image.
 *
 * @package AIISP
 */

// Security check: block direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$aiisp_presets = \AIISP\Admin\Meta_Box::get_style_presets();
$aiisp_sources = aiisp()->stock()->all();
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
				<h1><?php esc_html_e( 'AI Image Workspace', 'cubixsol-multi-ai-image-generator' ); ?></h1>
				<p><?php esc_html_e( 'Generate AI images or search stock libraries, preview them, and save your favorites to the Media Library.', 'cubixsol-multi-ai-image-generator' ); ?></p>
			</div>
		</div>
		<div class="aiisp-header-meta">
			<a class="aiisp-chip" href="<?php echo esc_url( admin_url( 'admin.php?page=aiisp-settings&tab=providers' ) ); ?>">
				<span class="dashicons dashicons-admin-generic"></span>
				<?php esc_html_e( 'Engine settings', 'cubixsol-multi-ai-image-generator' ); ?>
			</a>
		</div>
	</div>

	<div class="aiisp-studio">

		<!-- ================= Mode switcher ================= -->
		<div class="aiisp-studio-tabs" role="tablist">
			<button type="button" class="aiisp-studio-tab is-active" data-mode="generate" role="tab">
				<span class="dashicons dashicons-superhero"></span>
				<?php esc_html_e( 'AI Generate', 'cubixsol-multi-ai-image-generator' ); ?>
			</button>
			<button type="button" class="aiisp-studio-tab" data-mode="stock" role="tab">
				<span class="dashicons dashicons-format-gallery"></span>
				<?php esc_html_e( 'Stock Photos', 'cubixsol-multi-ai-image-generator' ); ?>
			</button>
		</div>

		<!-- ================= Controls: AI Generate ================= -->
		<div class="aiisp-panel aiisp-studio-controls is-active" data-mode="generate">
			<div class="aiisp-studio-form">
				<div class="aiisp-field aiisp-field-grow">
					<label class="aiisp-label" for="aiisp-studio-prompt"><?php esc_html_e( 'Prompt', 'cubixsol-multi-ai-image-generator' ); ?></label>
					<textarea id="aiisp-studio-prompt" class="aiisp-input" rows="2"
						placeholder="<?php esc_attr_e( 'Describe the image you imagine — e.g. \"Minimalist product shot of a perfume bottle on marble, soft studio lighting, 4K\"', 'cubixsol-multi-ai-image-generator' ); ?>"></textarea>
				</div>

				<div class="aiisp-field">
					<label class="aiisp-label" for="aiisp-studio-style"><?php esc_html_e( 'Style', 'cubixsol-multi-ai-image-generator' ); ?></label>
					<?php $aiisp_default_style = (string) aiisp()->options()->get( 'default_style', 'none' ); ?>
					<select id="aiisp-studio-style" class="aiisp-input">
						<?php foreach ( $aiisp_presets as $aiisp_slug => $aiisp_label ) : ?>
							<option value="<?php echo esc_attr( $aiisp_slug ); ?>" <?php selected( $aiisp_default_style, $aiisp_slug ); ?>><?php echo esc_html( $aiisp_label ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="aiisp-field aiisp-field-btn">
					<button type="button" class="aiisp-btn aiisp-btn-primary" id="aiisp-studio-generate">
						<span class="dashicons dashicons-superhero"></span>
						<?php esc_html_e( 'Generate', 'cubixsol-multi-ai-image-generator' ); ?>
					</button>
				</div>
			</div>
		</div>

		<!-- ================= Controls: Stock ================= -->
		<div class="aiisp-panel aiisp-studio-controls" data-mode="stock">
			<div class="aiisp-studio-form">
				<div class="aiisp-field">
					<label class="aiisp-label" for="aiisp-studio-source"><?php esc_html_e( 'Library', 'cubixsol-multi-ai-image-generator' ); ?></label>
					<select id="aiisp-studio-source" class="aiisp-input">
						<?php foreach ( $aiisp_sources as $aiisp_slug => $aiisp_source ) : ?>
							<option value="<?php echo esc_attr( $aiisp_slug ); ?>" <?php disabled( ! $aiisp_source->is_configured() ); ?>>
								<?php
								echo esc_html( $aiisp_source->get_label() );
								if ( ! $aiisp_source->is_configured() ) {
									echo ' ' . esc_html__( '(needs key)', 'cubixsol-multi-ai-image-generator' );
								}
								?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="aiisp-field aiisp-field-grow">
					<label class="aiisp-label" for="aiisp-studio-query"><?php esc_html_e( 'Search', 'cubixsol-multi-ai-image-generator' ); ?></label>
					<input type="text" id="aiisp-studio-query" class="aiisp-input"
						placeholder="<?php esc_attr_e( 'Search stock libraries — try \"startup office\", \"healthy breakfast\" or \"city skyline at dusk\"', 'cubixsol-multi-ai-image-generator' ); ?>" />
				</div>

				<div class="aiisp-field aiisp-field-btn">
					<button type="button" class="aiisp-btn aiisp-btn-primary" id="aiisp-studio-search">
						<span class="dashicons dashicons-search"></span>
						<?php esc_html_e( 'Search', 'cubixsol-multi-ai-image-generator' ); ?>
					</button>
				</div>
			</div>
		</div>

		<!-- ================= Status / errors ================= -->
		<div class="aiisp-mb-status" id="aiisp-studio-status" hidden>
			<span class="aiisp-spinner"></span><span id="aiisp-studio-status-text"></span>
		</div>
		<div class="aiisp-mb-error" id="aiisp-studio-error" hidden></div>

		<!-- ================= Results grid ================= -->
		<div class="aiisp-studio-grid" id="aiisp-studio-grid"><!-- Cards injected by aiisp-admin.js --></div>

		<div class="aiisp-empty" id="aiisp-studio-empty">
			<span class="dashicons dashicons-images-alt2"></span>
			<p><?php esc_html_e( 'Generated and stock images will appear here. Hover a card for the full-size view and the save action.', 'cubixsol-multi-ai-image-generator' ); ?></p>
		</div>
	</div>
</div>
