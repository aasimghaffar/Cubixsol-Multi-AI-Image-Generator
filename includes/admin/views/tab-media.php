<?php
/**
 * Media & SEO tab.
 *
 * The size dropdown is generated dynamically from the union of all
 * registered engines' supported sizes.
 *
 * @package AIISP
 */

// Security check: block direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$aiisp_options = aiisp()->options();
$aiisp_sizes   = aiisp()->providers()->get_all_sizes();
$aiisp_size    = (string) $aiisp_options->get( 'image_size', '1024x1024' );
?>

<div class="aiisp-panel">
	<div class="aiisp-panel-head">
		<h2><?php esc_html_e( 'Image Output', 'cubixsol-multi-ai-image-generator' ); ?></h2>
	</div>

	<div class="aiisp-form-grid">
		<div class="aiisp-field">
			<label class="aiisp-label" for="aiisp-image-size"><?php esc_html_e( 'Default image size', 'cubixsol-multi-ai-image-generator' ); ?></label>
			<select class="aiisp-input" id="aiisp-image-size" name="aiisp_image_size">
				<?php foreach ( $aiisp_sizes as $aiisp_option_size ) : ?>
					<option value="<?php echo esc_attr( $aiisp_option_size ); ?>" <?php selected( $aiisp_size, $aiisp_option_size ); ?>>
						<?php echo esc_html( str_replace( 'x', ' × ', $aiisp_option_size ) ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<p class="aiisp-hint"><?php esc_html_e( 'Engines that cannot render this exact size use their closest supported size automatically.', 'cubixsol-multi-ai-image-generator' ); ?></p>
		</div>

		<div class="aiisp-field">
			<label class="aiisp-label" for="aiisp-default-style"><?php esc_html_e( 'Default style preset', 'cubixsol-multi-ai-image-generator' ); ?></label>
			<select class="aiisp-input" id="aiisp-default-style" name="aiisp_default_style">
				<?php
				$aiisp_default_style = (string) $aiisp_options->get( 'default_style', 'none' );
				foreach ( \AIISP\Admin\Meta_Box::get_style_presets() as $aiisp_preset_slug => $aiisp_preset_label ) :
					?>
					<option value="<?php echo esc_attr( $aiisp_preset_slug ); ?>" <?php selected( $aiisp_default_style, $aiisp_preset_slug ); ?>>
						<?php echo esc_html( $aiisp_preset_label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<p class="aiisp-hint"><?php esc_html_e( 'Pre-selected in the editor, Image Workspace and bulk tools. You can still change it per image.', 'cubixsol-multi-ai-image-generator' ); ?></p>
		</div>

		<div class="aiisp-field">
			<label class="aiisp-label" for="aiisp-daily-limit"><?php esc_html_e( 'Daily generation limit', 'cubixsol-multi-ai-image-generator' ); ?></label>
			<input type="number" min="0" step="1" class="aiisp-input" id="aiisp-daily-limit"
				name="aiisp_daily_limit"
				value="<?php echo esc_attr( (int) $aiisp_options->get( 'daily_limit', 100 ) ); ?>"
				placeholder="<?php esc_attr_e( 'e.g. 100 — or 0 for unlimited', 'cubixsol-multi-ai-image-generator' ); ?>" />
			<p class="aiisp-hint"><?php esc_html_e( 'Protects paid API budgets. Set 0 for unlimited.', 'cubixsol-multi-ai-image-generator' ); ?></p>
		</div>

		<div class="aiisp-field">
			<span class="aiisp-label"><?php esc_html_e( 'Featured image automation', 'cubixsol-multi-ai-image-generator' ); ?></span>
			<label class="aiisp-toggle">
				<input type="hidden" name="aiisp_auto_featured" value="0" />
				<input type="checkbox" name="aiisp_auto_featured" value="1" <?php checked( (bool) $aiisp_options->get( 'auto_featured', 0 ) ); ?> />
				<span class="aiisp-toggle-track"></span>
				<?php esc_html_e( 'Automatically set generated images as the featured image', 'cubixsol-multi-ai-image-generator' ); ?>
			</label>
			<p class="aiisp-hint"><?php esc_html_e( 'Applies to images generated from the post editor. Saves one click per post.', 'cubixsol-multi-ai-image-generator' ); ?></p>
		</div>
	</div>
</div>

<div class="aiisp-panel">
	<div class="aiisp-panel-head">
		<h2><?php esc_html_e( 'Prompt Booster', 'cubixsol-multi-ai-image-generator' ); ?></h2>
		<p class="aiisp-muted"><?php esc_html_e( 'Applied to every generation automatically, so all site imagery shares one consistent art direction.', 'cubixsol-multi-ai-image-generator' ); ?></p>
	</div>

	<div class="aiisp-form-grid">
		<div class="aiisp-field">
			<label class="aiisp-label" for="aiisp-prompt-prefix"><?php esc_html_e( 'Prefix — added before every prompt', 'cubixsol-multi-ai-image-generator' ); ?></label>
			<input type="text" class="aiisp-input" id="aiisp-prompt-prefix"
				name="aiisp_prompt_prefix"
				value="<?php echo esc_attr( (string) $aiisp_options->get( 'prompt_prefix', '' ) ); ?>"
				placeholder="<?php esc_attr_e( 'e.g. Professional editorial photograph of', 'cubixsol-multi-ai-image-generator' ); ?>" />
		</div>

		<div class="aiisp-field">
			<label class="aiisp-label" for="aiisp-prompt-suffix"><?php esc_html_e( 'Suffix — added after every prompt', 'cubixsol-multi-ai-image-generator' ); ?></label>
			<input type="text" class="aiisp-input" id="aiisp-prompt-suffix"
				name="aiisp_prompt_suffix"
				value="<?php echo esc_attr( (string) $aiisp_options->get( 'prompt_suffix', '' ) ); ?>"
				placeholder="<?php esc_attr_e( 'e.g. sharp focus, natural lighting, high detail, 4K', 'cubixsol-multi-ai-image-generator' ); ?>" />
		</div>

		<div class="aiisp-field">
			<label class="aiisp-label" for="aiisp-negative-prompt"><?php esc_html_e( 'Negative prompt — what to avoid', 'cubixsol-multi-ai-image-generator' ); ?></label>
			<input type="text" class="aiisp-input" id="aiisp-negative-prompt"
				name="aiisp_negative_prompt"
				value="<?php echo esc_attr( (string) $aiisp_options->get( 'negative_prompt', '' ) ); ?>"
				placeholder="<?php esc_attr_e( 'e.g. blurry, watermark, text, low quality, distorted hands', 'cubixsol-multi-ai-image-generator' ); ?>" />
			<p class="aiisp-hint"><?php esc_html_e( 'Used by engines that support it (Stability AI, Leonardo). Others ignore it safely.', 'cubixsol-multi-ai-image-generator' ); ?></p>
		</div>
	</div>
</div>

<div class="aiisp-panel">
	<div class="aiisp-panel-head">
		<h2><?php esc_html_e( 'SEO Automation', 'cubixsol-multi-ai-image-generator' ); ?></h2>
		<p class="aiisp-muted"><?php esc_html_e( 'Placeholders: {title} = post title, {prompt} = generation prompt, {style} = style preset.', 'cubixsol-multi-ai-image-generator' ); ?></p>
	</div>

	<div class="aiisp-form-grid">
		<div class="aiisp-field">
			<label class="aiisp-label" for="aiisp-alt-pattern"><?php esc_html_e( 'Alt text pattern', 'cubixsol-multi-ai-image-generator' ); ?></label>
			<input type="text" class="aiisp-input" id="aiisp-alt-pattern"
				name="aiisp_alt_pattern"
				value="<?php echo esc_attr( (string) $aiisp_options->get( 'alt_pattern', '{title} - {prompt}' ) ); ?>"
				placeholder="<?php esc_attr_e( 'e.g. {title} — {prompt}', 'cubixsol-multi-ai-image-generator' ); ?>" />
		</div>

		<div class="aiisp-field">
			<label class="aiisp-label" for="aiisp-filename-pattern"><?php esc_html_e( 'Filename pattern', 'cubixsol-multi-ai-image-generator' ); ?></label>
			<input type="text" class="aiisp-input" id="aiisp-filename-pattern"
				name="aiisp_filename_pattern"
				value="<?php echo esc_attr( (string) $aiisp_options->get( 'filename_pattern', '{title}-ai-image' ) ); ?>"
				placeholder="<?php esc_attr_e( 'e.g. {title}-featured — becomes my-post-featured.png', 'cubixsol-multi-ai-image-generator' ); ?>" />
			<p class="aiisp-hint"><?php esc_html_e( 'Sanitized to lowercase-hyphenated slugs automatically.', 'cubixsol-multi-ai-image-generator' ); ?></p>
		</div>
	</div>
</div>
