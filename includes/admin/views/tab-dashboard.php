<?php
/**
 * Dashboard tab: usage stats and generation history.
 *
 * @package AIISP
 */

// Security check: block direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$aiisp_stats = aiisp()->logger()->stats();
$aiisp_today = aiisp()->usage()->today();
$aiisp_limit = (int) aiisp()->options()->get( 'daily_limit', 100 );
$aiisp_rows  = aiisp()->logger()->recent( 50 );

// Compute the progress percentage safely (limit 0 = unlimited).
$aiisp_pct = $aiisp_limit > 0 ? min( 100, round( ( $aiisp_today / $aiisp_limit ) * 100 ) ) : 0;
?>

<div class="aiisp-card-grid">

	<div class="aiisp-stat-card">
		<span class="aiisp-stat-icon dashicons dashicons-clock"></span>
		<div>
			<span class="aiisp-stat-value"><?php echo esc_html( number_format_i18n( $aiisp_today ) ); ?></span>
			<span class="aiisp-stat-label">
				<?php
				if ( $aiisp_limit > 0 ) {
					printf(
						/* translators: %s: daily limit */
						esc_html__( 'Generated today (limit %s)', 'cubixsol-multi-ai-image-generator' ),
						esc_html( number_format_i18n( $aiisp_limit ) )
					);
				} else {
					esc_html_e( 'Generated today (unlimited)', 'cubixsol-multi-ai-image-generator' );
				}
				?>
			</span>
			<?php if ( $aiisp_limit > 0 ) : ?>
				<div class="aiisp-progress"><span style="width:<?php echo esc_attr( $aiisp_pct ); ?>%"></span></div>
			<?php endif; ?>
		</div>
	</div>

	<div class="aiisp-stat-card">
		<span class="aiisp-stat-icon dashicons dashicons-images-alt2"></span>
		<div>
			<span class="aiisp-stat-value"><?php echo esc_html( number_format_i18n( $aiisp_stats['total'] ) ); ?></span>
			<span class="aiisp-stat-label"><?php esc_html_e( 'All-time generations', 'cubixsol-multi-ai-image-generator' ); ?></span>
		</div>
	</div>

	<div class="aiisp-stat-card">
		<span class="aiisp-stat-icon aiisp-ok dashicons dashicons-yes-alt"></span>
		<div>
			<span class="aiisp-stat-value"><?php echo esc_html( number_format_i18n( $aiisp_stats['success'] ) ); ?></span>
			<span class="aiisp-stat-label"><?php esc_html_e( 'Successful', 'cubixsol-multi-ai-image-generator' ); ?></span>
		</div>
	</div>

	<div class="aiisp-stat-card">
		<span class="aiisp-stat-icon aiisp-fail dashicons dashicons-warning"></span>
		<div>
			<span class="aiisp-stat-value"><?php echo esc_html( number_format_i18n( $aiisp_stats['fail'] ) ); ?></span>
			<span class="aiisp-stat-label"><?php esc_html_e( 'Failed', 'cubixsol-multi-ai-image-generator' ); ?></span>
		</div>
	</div>

</div>

<div class="aiisp-panel">
	<div class="aiisp-panel-head">
		<h2><?php esc_html_e( 'Generation History', 'cubixsol-multi-ai-image-generator' ); ?></h2>
		<button type="button" class="aiisp-btn aiisp-btn-ghost" id="aiisp-clear-logs">
			<span class="dashicons dashicons-trash"></span>
			<?php esc_html_e( 'Clear History', 'cubixsol-multi-ai-image-generator' ); ?>
		</button>
	</div>

	<?php if ( empty( $aiisp_rows ) ) : ?>

		<div class="aiisp-empty">
			<span class="dashicons dashicons-format-image"></span>
			<p><?php esc_html_e( 'No generations yet. Open any post editor and try the Cubixsol Multi AI Image Generator box, or use Bulk Generate.', 'cubixsol-multi-ai-image-generator' ); ?></p>
		</div>

	<?php else : ?>

		<table class="aiisp-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Date', 'cubixsol-multi-ai-image-generator' ); ?></th>
					<th><?php esc_html_e( 'Engine', 'cubixsol-multi-ai-image-generator' ); ?></th>
					<th><?php esc_html_e( 'Prompt', 'cubixsol-multi-ai-image-generator' ); ?></th>
					<th><?php esc_html_e( 'Size', 'cubixsol-multi-ai-image-generator' ); ?></th>
					<th><?php esc_html_e( 'Post', 'cubixsol-multi-ai-image-generator' ); ?></th>
					<th><?php esc_html_e( 'Status', 'cubixsol-multi-ai-image-generator' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $aiisp_rows as $aiisp_row ) : ?>
					<tr>
						<td><?php echo esc_html( mysql2date( get_option( 'date_format' ) . ' H:i', $aiisp_row->created_at ) ); ?></td>
						<td><span class="aiisp-badge"><?php echo esc_html( $aiisp_row->provider ); ?></span></td>
						<td class="aiisp-cell-prompt" title="<?php echo esc_attr( $aiisp_row->prompt ); ?>">
							<?php echo esc_html( wp_trim_words( $aiisp_row->prompt, 10, '…' ) ); ?>
						</td>
						<td><?php echo esc_html( $aiisp_row->resolution ); ?></td>
						<td>
							<?php if ( $aiisp_row->post_id && get_post( $aiisp_row->post_id ) ) : ?>
								<a href="<?php echo esc_url( get_edit_post_link( $aiisp_row->post_id ) ); ?>">
									<?php echo esc_html( wp_trim_words( get_the_title( $aiisp_row->post_id ), 5, '…' ) ); ?>
								</a>
							<?php else : ?>
								<span class="aiisp-muted">—</span>
							<?php endif; ?>
						</td>
						<td>
							<span class="aiisp-badge <?php echo 'success' === $aiisp_row->status ? 'aiisp-badge-ok' : 'aiisp-badge-fail'; ?>">
								<?php echo 'success' === $aiisp_row->status ? esc_html__( 'Success', 'cubixsol-multi-ai-image-generator' ) : esc_html__( 'Failed', 'cubixsol-multi-ai-image-generator' ); ?>
							</span>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

	<?php endif; ?>
</div>
