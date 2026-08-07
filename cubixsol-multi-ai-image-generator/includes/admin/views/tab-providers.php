<?php
/**
 * AI Engines tab (redesigned).
 *
 * Layout:
 *  1. Engine cards — monogram avatar, name, Free/Paid tag, live status,
 *     size chips, and a key input-group with an inline Test button.
 *     The whole card is the label for its radio control, shown at
 *     the top-right corner of the card.
 *  2. Automatic fallback — numbered drag-and-drop priority list.
 *  3. Stock photo credentials — aligned rows with inline testing.
 *
 * Everything is generated from the live registries; adding an engine
 * via the aiisp_register_providers filter lights it up here with no
 * template edits.
 *
 * @package AIISP
 */

// Security check: block direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$aiisp_options   = aiisp()->options();
$aiisp_providers = aiisp()->providers()->all();
$aiisp_active    = (string) $aiisp_options->get( 'active_provider', 'pollinations' );
$aiisp_fallback  = (array) $aiisp_options->get( 'fallback_order', array() );
$aiisp_stock     = aiisp()->stock()->all();

// Merge saved order with any newly registered engines (dynamic-safe).
$aiisp_order = array_values( array_unique( array_merge( $aiisp_fallback, array_keys( $aiisp_providers ) ) ) );

// Configured summary for the section header — computed, never hardcoded.
$aiisp_ready_count = 0;
foreach ( $aiisp_providers as $aiisp_provider_item ) {
	if ( $aiisp_provider_item->is_configured() ) {
		$aiisp_ready_count++;
	}
}

// Deterministic avatar hue per engine so each brand tile is distinct.
$aiisp_avatar_index = 0;
?>

<div class="aiisp-panel">
	<div class="aiisp-panel-head">
		<h2><?php esc_html_e( 'AI Engines', 'cubixsol-multi-ai-image-generator' ); ?></h2>
		<span class="aiisp-badge <?php echo $aiisp_ready_count > 0 ? 'aiisp-badge-ok' : 'aiisp-badge-warn'; ?>">
			<?php
			printf(
				/* translators: 1: configured engine count, 2: total engine count */
				esc_html__( '%1$d of %2$d engines ready', 'cubixsol-multi-ai-image-generator' ),
				(int) $aiisp_ready_count,
				count( $aiisp_providers )
			);
			?>
		</span>
		<p class="aiisp-muted"><?php esc_html_e( 'Choose your primary engine by clicking a card. Add a key, then test it before saving — you can also re-test keys that are already stored.', 'cubixsol-multi-ai-image-generator' ); ?></p>
	</div>

	<div class="aiisp-provider-grid">
		<?php foreach ( $aiisp_providers as $aiisp_slug => $aiisp_provider ) : ?>
			<?php
			$aiisp_avatar_index++;
			$aiisp_has_saved = '' !== (string) $aiisp_options->get( $aiisp_slug . '_api_key' );
			$aiisp_is_free   = ! $aiisp_provider->requires_api_key();
			?>
			<label class="aiisp-provider-card <?php echo $aiisp_active === $aiisp_slug ? 'is-active' : ''; ?>">
				<input type="radio"
					name="aiisp_active_provider"
					value="<?php echo esc_attr( $aiisp_slug ); ?>"
					<?php checked( $aiisp_active, $aiisp_slug ); ?> />

				<div class="aiisp-provider-head">
					<span class="aiisp-avatar aiisp-avatar-<?php echo esc_attr( $aiisp_avatar_index % 9 ); ?>">
						<?php echo esc_html( strtoupper( substr( $aiisp_provider->get_label(), 0, 1 ) ) ); ?>
					</span>
					<div class="aiisp-provider-title">
						<strong><?php echo esc_html( $aiisp_provider->get_label() ); ?></strong>
						<span class="aiisp-provider-tags">
							<?php if ( $aiisp_is_free ) : ?>
								<span class="aiisp-tag aiisp-tag-free"><?php esc_html_e( 'Free', 'cubixsol-multi-ai-image-generator' ); ?></span>
							<?php else : ?>
								<span class="aiisp-tag"><?php esc_html_e( 'API key', 'cubixsol-multi-ai-image-generator' ); ?></span>
							<?php endif; ?>
							<?php if ( $aiisp_provider->is_configured() ) : ?>
								<span class="aiisp-tag aiisp-tag-ok"><?php esc_html_e( 'Ready', 'cubixsol-multi-ai-image-generator' ); ?></span>
							<?php else : ?>
								<span class="aiisp-tag aiisp-tag-warn"><?php esc_html_e( 'Needs key', 'cubixsol-multi-ai-image-generator' ); ?></span>
							<?php endif; ?>
						</span>
					</div>
				</div>

				<p class="aiisp-provider-desc"><?php echo esc_html( $aiisp_provider->get_description() ); ?></p>

				<?php if ( ! $aiisp_is_free ) : ?>
					<div class="aiisp-key-field">
						<div class="aiisp-input-group">
							<input type="password"
								class="aiisp-input"
								name="aiisp_<?php echo esc_attr( $aiisp_slug ); ?>_api_key"
								value=""
								autocomplete="new-password"
								data-saved="<?php echo $aiisp_has_saved ? '1' : '0'; ?>"
								placeholder="<?php echo $aiisp_has_saved ? esc_attr__( '••••••••••••  Key saved securely', 'cubixsol-multi-ai-image-generator' ) : esc_attr__( 'Paste your secret API key', 'cubixsol-multi-ai-image-generator' ); ?>" />

							<!-- Enabled when a key is typed OR one is already saved. -->
							<button type="button"
								class="aiisp-btn aiisp-btn-secondary aiisp-btn-sm aiisp-test-key"
								data-kind="provider"
								data-slug="<?php echo esc_attr( $aiisp_slug ); ?>"
								<?php disabled( ! $aiisp_has_saved ); ?>>
								<?php esc_html_e( 'Test', 'cubixsol-multi-ai-image-generator' ); ?>
							</button>
						</div>

						<p class="aiisp-test-result" hidden></p>

						<div class="aiisp-key-actions">
							<?php if ( '' !== $aiisp_provider->get_key_url() ) : ?>
								<a class="aiisp-key-link" href="<?php echo esc_url( $aiisp_provider->get_key_url() ); ?>" target="_blank" rel="noopener noreferrer">
									<?php esc_html_e( 'Get a key ↗', 'cubixsol-multi-ai-image-generator' ); ?>
								</a>
							<?php endif; ?>

							<?php if ( $aiisp_has_saved ) : ?>
								<label class="aiisp-clear-key">
									<input type="checkbox" name="aiisp_<?php echo esc_attr( $aiisp_slug ); ?>_api_key_clear" value="1" />
									<?php esc_html_e( 'Remove saved key', 'cubixsol-multi-ai-image-generator' ); ?>
								</label>
							<?php endif; ?>
						</div>
					</div>
				<?php else : ?>
					<span class="aiisp-free-tag">
						<span class="dashicons dashicons-yes-alt"></span>
						<?php esc_html_e( 'Works instantly — no account or key needed', 'cubixsol-multi-ai-image-generator' ); ?>
					</span>
				<?php endif; ?>

				<?php $aiisp_notice = $aiisp_provider->get_notice(); ?>
				<?php if ( '' !== $aiisp_notice ) : ?>
					<p class="aiisp-engine-note">
						<span class="dashicons dashicons-info-outline"></span>
						<span><?php echo esc_html( $aiisp_notice ); ?></span>
					</p>
				<?php endif; ?>
			</label>
		<?php endforeach; ?>
	</div>
</div>

<div class="aiisp-panel">
	<div class="aiisp-panel-head">
		<h2><?php esc_html_e( 'Automatic Fallback', 'cubixsol-multi-ai-image-generator' ); ?></h2>
		<label class="aiisp-toggle">
			<input type="hidden" name="aiisp_enable_fallback" value="0" />
			<input type="checkbox" name="aiisp_enable_fallback" value="1" <?php checked( (bool) $aiisp_options->get( 'enable_fallback', 1 ) ); ?> />
			<span class="aiisp-toggle-track"></span>
			<?php esc_html_e( 'Try the next engine when one fails', 'cubixsol-multi-ai-image-generator' ); ?>
		</label>
		<p class="aiisp-muted"><?php esc_html_e( 'Drag to reorder the priority. Engines without a working key are skipped automatically at run time.', 'cubixsol-multi-ai-image-generator' ); ?></p>
	</div>

	<ol class="aiisp-sortable" id="aiisp-fallback-sortable">
		<?php
		foreach ( $aiisp_order as $aiisp_slug ) :
			// Check: only render engines that still exist in the registry.
			if ( ! isset( $aiisp_providers[ $aiisp_slug ] ) ) {
				continue;
			}
			?>
			<li class="aiisp-sortable-item" data-slug="<?php echo esc_attr( $aiisp_slug ); ?>">
				<span class="aiisp-sortable-rank" aria-hidden="true"></span>
				<span class="dashicons dashicons-menu" aria-hidden="true"></span>
				<span class="aiisp-sortable-label"><?php echo esc_html( $aiisp_providers[ $aiisp_slug ]->get_label() ); ?></span>
				<?php if ( $aiisp_providers[ $aiisp_slug ]->is_configured() ) : ?>
					<span class="aiisp-tag aiisp-tag-ok"><?php esc_html_e( 'Ready', 'cubixsol-multi-ai-image-generator' ); ?></span>
				<?php else : ?>
					<span class="aiisp-tag aiisp-tag-warn"><?php esc_html_e( 'Skipped — no key', 'cubixsol-multi-ai-image-generator' ); ?></span>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ol>
	<input type="hidden" id="aiisp-fallback-order" name="aiisp_fallback_order" value="<?php echo esc_attr( implode( ',', $aiisp_order ) ); ?>" />
</div>

<div class="aiisp-panel">
	<div class="aiisp-panel-head">
		<h2><?php esc_html_e( 'Stock Photo Libraries', 'cubixsol-multi-ai-image-generator' ); ?></h2>
		<p class="aiisp-muted"><?php esc_html_e( 'Openverse works with no key. Add free API keys to unlock the other libraries in the editor and Image Workspace.', 'cubixsol-multi-ai-image-generator' ); ?></p>
	</div>

	<div class="aiisp-cred-rows">
		<?php foreach ( $aiisp_stock as $aiisp_slug => $aiisp_source ) : ?>
			<?php
			$aiisp_avatar_index++;
			$aiisp_needs_key = $aiisp_source->requires_api_key();
			$aiisp_has_saved = $aiisp_needs_key && '' !== (string) $aiisp_options->get( $aiisp_slug . '_api_key' );
			?>
			<div class="aiisp-cred-row">
				<div class="aiisp-cred-ident">
					<span class="aiisp-avatar aiisp-avatar-sm aiisp-avatar-<?php echo esc_attr( $aiisp_avatar_index % 9 ); ?>">
						<?php echo esc_html( strtoupper( substr( $aiisp_source->get_label(), 0, 1 ) ) ); ?>
					</span>
					<div>
						<strong><?php echo esc_html( $aiisp_source->get_label() ); ?></strong>
						<?php if ( ! $aiisp_needs_key ) : ?>
							<span class="aiisp-tag aiisp-tag-free"><?php esc_html_e( 'Free — no key', 'cubixsol-multi-ai-image-generator' ); ?></span>
						<?php elseif ( $aiisp_source->is_configured() ) : ?>
							<span class="aiisp-tag aiisp-tag-ok"><?php esc_html_e( 'Ready', 'cubixsol-multi-ai-image-generator' ); ?></span>
						<?php else : ?>
							<span class="aiisp-tag aiisp-tag-warn"><?php esc_html_e( 'Needs key', 'cubixsol-multi-ai-image-generator' ); ?></span>
						<?php endif; ?>
					</div>
				</div>

				<?php if ( $aiisp_needs_key ) : ?>
					<div class="aiisp-cred-controls">
						<div class="aiisp-input-group">
							<input type="password"
								class="aiisp-input"
								id="aiisp-stock-<?php echo esc_attr( $aiisp_slug ); ?>"
								name="aiisp_<?php echo esc_attr( $aiisp_slug ); ?>_api_key"
								value=""
								autocomplete="new-password"
								data-saved="<?php echo $aiisp_has_saved ? '1' : '0'; ?>"
								placeholder="<?php echo $aiisp_has_saved ? esc_attr__( '••••••••••••  Key saved securely', 'cubixsol-multi-ai-image-generator' ) : esc_attr__( 'Paste your secret API key', 'cubixsol-multi-ai-image-generator' ); ?>" />
							<button type="button"
								class="aiisp-btn aiisp-btn-secondary aiisp-btn-sm aiisp-test-key"
								data-kind="stock"
								data-slug="<?php echo esc_attr( $aiisp_slug ); ?>"
								<?php disabled( ! $aiisp_has_saved ); ?>>
								<?php esc_html_e( 'Test', 'cubixsol-multi-ai-image-generator' ); ?>
							</button>
						</div>
						<p class="aiisp-test-result" hidden></p>
						<div class="aiisp-key-actions">
							<?php if ( '' !== $aiisp_source->get_key_url() ) : ?>
								<a class="aiisp-key-link" href="<?php echo esc_url( $aiisp_source->get_key_url() ); ?>" target="_blank" rel="noopener noreferrer">
									<?php esc_html_e( 'Get a key ↗', 'cubixsol-multi-ai-image-generator' ); ?>
								</a>
							<?php endif; ?>
							<?php if ( $aiisp_has_saved ) : ?>
								<label class="aiisp-clear-key">
									<input type="checkbox" name="aiisp_<?php echo esc_attr( $aiisp_slug ); ?>_api_key_clear" value="1" />
									<?php esc_html_e( 'Remove saved key', 'cubixsol-multi-ai-image-generator' ); ?>
								</label>
							<?php endif; ?>
						</div>
					</div>
				<?php else : ?>
					<div class="aiisp-cred-controls">
						<span class="aiisp-muted"><?php esc_html_e( 'Included with the plugin — nothing to configure.', 'cubixsol-multi-ai-image-generator' ); ?></span>
					</div>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	</div>
</div>
