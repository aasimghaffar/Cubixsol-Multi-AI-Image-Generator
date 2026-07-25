<?php
/**
 * Bulk Generate tab.
 *
 * Rebuilt with a logical workflow:
 *   1. Pick an enabled post type and Scan for posts missing a
 *      featured image (live AJAX query — never a stale render).
 *   2. Review the results table, tick the posts you want, adjust
 *      each prompt (defaults to the post title) and pick a style.
 *   3. Generate — posts are processed one at a time with a live
 *      progress bar and per-row status.
 *
 * @package AIISP
 */

// Security check: block direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$aiisp_enabled  = (array) aiisp()->options()->get( 'post_types', array() );
$aiisp_presets  = \AIISP\Admin\Meta_Box::get_style_presets();
?>

<div class="aiisp-panel">
	<div class="aiisp-panel-head">
		<h2><?php esc_html_e( 'Bulk Featured Images', 'cubixsol-multi-ai-image-generator' ); ?></h2>
		<p class="aiisp-muted"><?php esc_html_e( 'Find posts without a featured image and generate one for each, sequentially and safely.', 'cubixsol-multi-ai-image-generator' ); ?></p>
	</div>

	<?php if ( empty( $aiisp_enabled ) ) : ?>

		<div class="aiisp-empty">
			<span class="dashicons dashicons-admin-post"></span>
			<p><?php esc_html_e( 'No post types are enabled yet. Enable at least one under the Post Types tab first.', 'cubixsol-multi-ai-image-generator' ); ?></p>
		</div>

	<?php else : ?>

		<!-- Step 1: scan controls -->
		<div class="aiisp-bulk-controls">
			<div class="aiisp-field">
				<label class="aiisp-label" for="aiisp-bulk-post-type"><?php esc_html_e( '1. Post type', 'cubixsol-multi-ai-image-generator' ); ?></label>
				<select class="aiisp-input" id="aiisp-bulk-post-type">
					<?php
					foreach ( $aiisp_enabled as $aiisp_slug ) :
						$aiisp_object = get_post_type_object( $aiisp_slug );
						// Check: skip types unregistered since saving.
						if ( ! $aiisp_object ) {
							continue;
						}
						?>
						<option value="<?php echo esc_attr( $aiisp_slug ); ?>"><?php echo esc_html( $aiisp_object->labels->name ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>

			<div class="aiisp-field">
				<label class="aiisp-label" for="aiisp-bulk-style"><?php esc_html_e( '2. Style preset', 'cubixsol-multi-ai-image-generator' ); ?></label>
				<?php $aiisp_default_style = (string) aiisp()->options()->get( 'default_style', 'none' ); ?>
		<select class="aiisp-input" id="aiisp-bulk-style">
					<?php foreach ( $aiisp_presets as $aiisp_preset_slug => $aiisp_preset_label ) : ?>
						<option value="<?php echo esc_attr( $aiisp_preset_slug ); ?>" <?php selected( $aiisp_default_style, $aiisp_preset_slug ); ?>><?php echo esc_html( $aiisp_preset_label ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>

			<div class="aiisp-field aiisp-field-btn">
				<button type="button" class="aiisp-btn aiisp-btn-secondary" id="aiisp-bulk-scan">
					<span class="dashicons dashicons-search"></span>
					<?php esc_html_e( '3. Scan for posts', 'cubixsol-multi-ai-image-generator' ); ?>
				</button>
			</div>
		</div>

		<!-- Step 2: results table (rows injected by JS from the scan) -->
		<div id="aiisp-bulk-results" class="aiisp-bulk-results" hidden>
			<table class="aiisp-table">
				<thead>
					<tr>
						<th class="aiisp-col-check"><input type="checkbox" id="aiisp-bulk-select-all" checked /></th>
						<th><?php esc_html_e( 'Post', 'cubixsol-multi-ai-image-generator' ); ?></th>
						<th><?php esc_html_e( 'Image prompt (editable)', 'cubixsol-multi-ai-image-generator' ); ?></th>
						<th><?php esc_html_e( 'Status', 'cubixsol-multi-ai-image-generator' ); ?></th>
					</tr>
				</thead>
				<tbody id="aiisp-bulk-rows"><!-- Filled by aiisp-admin.js --></tbody>
			</table>

			<!-- Step 3: run -->
			<div class="aiisp-bulk-run">
				<div class="aiisp-progress aiisp-progress-lg"><span id="aiisp-bulk-progress" style="width:0%"></span></div>
				<button type="button" class="aiisp-btn aiisp-btn-primary" id="aiisp-bulk-generate">
					<span class="dashicons dashicons-superhero"></span>
					<?php esc_html_e( 'Generate for selected posts', 'cubixsol-multi-ai-image-generator' ); ?>
				</button>
			</div>
		</div>

		<p id="aiisp-bulk-message" class="aiisp-muted" hidden></p>

	<?php endif; ?>
</div>
