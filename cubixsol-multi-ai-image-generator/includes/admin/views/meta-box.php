<?php
/**
 * Post editor meta box view.
 *
 * Receives from Meta_Box::render():
 *   $post    — current WP_Post
 *   $presets — dynamic style presets (slug => label)
 *   $sources — dynamic stock source registry entries
 *
 * @package AIISP
 */

// Security check: block direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Check: guard against the view being loaded without its context.
if ( ! isset( $post, $presets, $sources ) ) {
	return;
}
?>
<div class="aiisp-metabox" data-post-id="<?php echo esc_attr( $post->ID ); ?>">

	<!-- Tab switcher -->
	<div class="aiisp-mb-tabs" role="tablist">
		<button type="button" class="aiisp-mb-tab is-active" data-panel="generate" role="tab">
			<span class="dashicons dashicons-superhero"></span><?php esc_html_e( 'AI Generate', 'cubixsol-multi-ai-image-generator' ); ?>
		</button>
		<button type="button" class="aiisp-mb-tab" data-panel="stock" role="tab">
			<span class="dashicons dashicons-format-gallery"></span><?php esc_html_e( 'Stock Photos', 'cubixsol-multi-ai-image-generator' ); ?>
		</button>
	</div>

	<!-- ============ Panel: AI Generate ============ -->
	<div class="aiisp-mb-panel is-active" data-panel="generate">

		<label class="aiisp-label" for="aiisp-prompt"><?php esc_html_e( 'Describe the image', 'cubixsol-multi-ai-image-generator' ); ?></label>
		<textarea id="aiisp-prompt" class="aiisp-input" rows="3"
			placeholder="<?php esc_attr_e( 'Describe your image — subject, setting, mood, lighting. e.g. \"Cozy home office with morning sunlight, plants on shelves, cinematic photo\"', 'cubixsol-multi-ai-image-generator' ); ?>"></textarea>

		<label class="aiisp-label" for="aiisp-style"><?php esc_html_e( 'Style preset', 'cubixsol-multi-ai-image-generator' ); ?></label>
		<?php $aiisp_default_style = (string) aiisp()->options()->get( 'default_style', 'none' ); ?>
		<select id="aiisp-style" class="aiisp-input">
			<?php foreach ( $presets as $aiisp_slug => $aiisp_label ) : ?>
				<option value="<?php echo esc_attr( $aiisp_slug ); ?>" <?php selected( $aiisp_default_style, $aiisp_slug ); ?>><?php echo esc_html( $aiisp_label ); ?></option>
			<?php endforeach; ?>
		</select>

		<button type="button" class="aiisp-btn aiisp-btn-primary aiisp-btn-block" id="aiisp-generate">
			<span class="dashicons dashicons-superhero"></span>
			<?php esc_html_e( 'Generate Image', 'cubixsol-multi-ai-image-generator' ); ?>
		</button>

		<div class="aiisp-mb-status" id="aiisp-status" hidden>
			<span class="aiisp-spinner"></span><span id="aiisp-status-text"></span>
		</div>

		<div class="aiisp-mb-error" id="aiisp-error" hidden></div>

		<div class="aiisp-mb-preview" id="aiisp-preview" hidden>
			<!-- Clicking the image (or the eye) opens the full-size lightbox. -->
			<button type="button" class="aiisp-zoom-wrap aiisp-open-lightbox" title="<?php esc_attr_e( 'View full size', 'cubixsol-multi-ai-image-generator' ); ?>">
				<img src="" alt="" id="aiisp-preview-img" />
				<span class="aiisp-zoom-eye dashicons dashicons-visibility"></span>
			</button>
			<p class="aiisp-mb-provider" id="aiisp-preview-provider"></p>
			<div class="aiisp-mb-toolbar">
				<button type="button" class="aiisp-btn aiisp-btn-secondary" id="aiisp-set-featured">
					<span class="dashicons dashicons-format-image"></span><?php esc_html_e( 'Set featured', 'cubixsol-multi-ai-image-generator' ); ?>
				</button>
				<a class="aiisp-btn aiisp-btn-ghost" id="aiisp-download" href="#" download>
					<span class="dashicons dashicons-download"></span><?php esc_html_e( 'Download', 'cubixsol-multi-ai-image-generator' ); ?>
				</a>
				<button type="button" class="aiisp-btn aiisp-btn-ghost" id="aiisp-regenerate">
					<span class="dashicons dashicons-update"></span><?php esc_html_e( 'Regenerate', 'cubixsol-multi-ai-image-generator' ); ?>
				</button>
			</div>
		</div>
	</div>

	<!-- ============ Panel: Stock Photos ============ -->
	<div class="aiisp-mb-panel" data-panel="stock">

		<label class="aiisp-label" for="aiisp-stock-source"><?php esc_html_e( 'Library', 'cubixsol-multi-ai-image-generator' ); ?></label>
		<select id="aiisp-stock-source" class="aiisp-input">
			<?php foreach ( $sources as $aiisp_slug => $aiisp_source ) : ?>
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

		<div class="aiisp-stock-search">
			<input type="text" id="aiisp-stock-query" class="aiisp-input"
				placeholder="<?php esc_attr_e( 'Search millions of photos — try \"team meeting\" or \"autumn forest\"', 'cubixsol-multi-ai-image-generator' ); ?>" />
			<button type="button" class="aiisp-btn aiisp-btn-secondary" id="aiisp-stock-search">
				<span class="dashicons dashicons-search"></span>
			</button>
		</div>

		<div class="aiisp-mb-status" id="aiisp-stock-status" hidden>
			<span class="aiisp-spinner"></span><span><?php esc_html_e( 'Searching…', 'cubixsol-multi-ai-image-generator' ); ?></span>
		</div>

		<div class="aiisp-mb-error" id="aiisp-stock-error" hidden></div>

		<div class="aiisp-stock-grid" id="aiisp-stock-results"><!-- Filled by aiisp-admin.js --></div>
	</div>
</div>
