<?php
/**
 * Post Types tab — the checkbox list is generated dynamically from
 * every UI-visible post type registered on the site (core + CPTs).
 *
 * @package AIISP
 */

// Security check: block direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$aiisp_enabled = (array) aiisp()->options()->get( 'post_types', array() );

// Dynamic: pull every UI post type except attachments.
$aiisp_types = get_post_types( array( 'show_ui' => true ), 'objects' );
unset( $aiisp_types['attachment'] );
?>

<div class="aiisp-panel">
	<div class="aiisp-panel-head">
		<h2><?php esc_html_e( 'Enabled Post Types', 'cubixsol-multi-ai-image-generator' ); ?></h2>
		<p class="aiisp-muted"><?php esc_html_e( 'The generator meta box and bulk tools appear only on the types selected below.', 'cubixsol-multi-ai-image-generator' ); ?></p>
	</div>

	<!-- Hidden sentinel: guarantees the field is present in POST even
	     when every checkbox is unticked, so unchecking all works. -->
	<input type="hidden" name="aiisp_post_types[]" value="" />

	<div class="aiisp-check-grid">
		<?php foreach ( $aiisp_types as $aiisp_type ) : ?>
			<label class="aiisp-check-card">
				<input type="checkbox"
					name="aiisp_post_types[]"
					value="<?php echo esc_attr( $aiisp_type->name ); ?>"
					<?php checked( in_array( $aiisp_type->name, $aiisp_enabled, true ) ); ?> />
				<span class="dashicons <?php echo esc_attr( is_string( $aiisp_type->menu_icon ) && 0 === strpos( (string) $aiisp_type->menu_icon, 'dashicons-' ) ? $aiisp_type->menu_icon : 'dashicons-admin-post' ); ?>"></span>
				<span>
					<strong><?php echo esc_html( $aiisp_type->labels->name ); ?></strong>
					<code><?php echo esc_html( $aiisp_type->name ); ?></code>
				</span>
			</label>
		<?php endforeach; ?>
	</div>
</div>
