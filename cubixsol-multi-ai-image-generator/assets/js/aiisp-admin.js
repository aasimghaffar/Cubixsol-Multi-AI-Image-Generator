/**
 * Cubixsol Multi AI Image Generator — admin JavaScript.
 *
 * Handles: meta box generation + stock search/import, settings page
 * interactions (sortable fallback list, provider cards) and the
 * rebuilt sequential bulk generation queue.
 *
 * Everything is wrapped in an IIFE and reads runtime data only from
 * the localized `aiispData` object — no values are hardcoded here.
 */
/* global jQuery, aiispData */
( function ( $ ) {
	'use strict';

	// Check: bail cleanly if the localized data object is missing.
	if ( 'undefined' === typeof aiispData ) {
		return;
	}

	var i18n = aiispData.i18n || {};

	/**
	 * Small helper around $.post for our AJAX endpoints. The nonce is
	 * attached to every request automatically.
	 *
	 * @param {string}   action  wp_ajax action name.
	 * @param {Object}   data    Extra payload.
	 * @param {Function} done    Success callback (payload).
	 * @param {Function} fail    Failure callback (message).
	 */
	function ajax( action, data, done, fail ) {
		$.post(
			aiispData.ajaxUrl,
			$.extend( { action: action, nonce: aiispData.nonce }, data )
		)
			.done( function ( res ) {
				if ( res && res.success ) {
					done( res.data || {} );
				} else {
					fail( ( res && res.data && res.data.message ) ? res.data.message : i18n.genericError );
				}
			} )
			.fail( function () {
				fail( i18n.genericError );
			} );
	}

	/* =====================================================================
	 * Meta box: tab switching
	 * ================================================================== */

	$( document ).on( 'click', '.aiisp-mb-tab', function () {
		var panel = $( this ).data( 'panel' );
		var $box  = $( this ).closest( '.aiisp-metabox' );

		$box.find( '.aiisp-mb-tab' ).removeClass( 'is-active' );
		$( this ).addClass( 'is-active' );

		$box.find( '.aiisp-mb-panel' ).removeClass( 'is-active' );
		$box.find( '.aiisp-mb-panel[data-panel="' + panel + '"]' ).addClass( 'is-active' );
	} );

	/* =====================================================================
	 * Meta box: AI generation
	 * ================================================================== */

	var lastAttachmentId = 0;

	function setStatus( text ) {
		$( '#aiisp-status' ).prop( 'hidden', false );
		$( '#aiisp-status-text' ).text( text );
	}

	function clearStatus() {
		$( '#aiisp-status' ).prop( 'hidden', true );
	}

	function showError( message ) {
		$( '#aiisp-error' ).prop( 'hidden', false ).text( message );
	}

	function clearError() {
		$( '#aiisp-error' ).prop( 'hidden', true ).empty();
	}

	function runGenerate() {
		var prompt = $.trim( $( '#aiisp-prompt' ).val() || '' );

		clearError();

		// Check: never send an empty prompt to the server.
		if ( ! prompt ) {
			showError( i18n.emptyPrompt || i18n.genericError );
			$( '#aiisp-prompt' ).focus();
			return;
		}

		$( '#aiisp-generate, #aiisp-regenerate' ).prop( 'disabled', true );
		$( '#aiisp-preview' ).prop( 'hidden', true );
		setStatus( i18n.sending );

		ajax(
			'aiisp_generate_image',
			{
				prompt: prompt,
				style: $( '#aiisp-style' ).val() || 'none',
				post_id: aiispData.postId || 0
			},
			function ( data ) {
				clearStatus();
				$( '#aiisp-generate, #aiisp-regenerate' ).prop( 'disabled', false );

				lastAttachmentId = data.attachment_id || 0;

				$( '#aiisp-preview-img' ).attr( 'src', data.url || '' );
				$( '#aiisp-preview-provider' ).text( data.provider || '' );
				$( '#aiisp-download' ).attr( 'href', data.full_url || data.url || '#' );
				$( '#aiisp-preview' ).prop( 'hidden', false );

				// Featured image automation: when enabled in settings,
				// set the fresh image as featured without an extra click.
				if ( aiispData.autoFeatured && aiispData.postId && lastAttachmentId ) {
					$( '#aiisp-set-featured' ).trigger( 'click' );
				}
			},
			function ( message ) {
				clearStatus();
				$( '#aiisp-generate, #aiisp-regenerate' ).prop( 'disabled', false );
				showError( message );
			}
		);
	}

	$( document ).on( 'click', '#aiisp-generate, #aiisp-regenerate', runGenerate );

	$( document ).on( 'click', '#aiisp-set-featured', function () {
		var $btn = $( this );

		// Check: an image must have been generated first.
		if ( ! lastAttachmentId ) {
			return;
		}

		$btn.prop( 'disabled', true );

		ajax(
			'aiisp_set_featured',
			{ post_id: aiispData.postId || 0, attachment_id: lastAttachmentId },
			function () {
				$btn.prop( 'disabled', false ).find( '~ span' ).remove();
				$btn.text( i18n.featuredSet );
			},
			function ( message ) {
				$btn.prop( 'disabled', false );
				showError( message );
			}
		);
	} );

	/* =====================================================================
	 * Meta box: stock photos
	 * ================================================================== */

	function runStockSearch() {
		var query = $.trim( $( '#aiisp-stock-query' ).val() || '' );

		$( '#aiisp-stock-error' ).prop( 'hidden', true ).empty();
		$( '#aiisp-stock-results' ).empty();

		// Check: require a search term client-side too.
		if ( ! query ) {
			$( '#aiisp-stock-query' ).focus();
			return;
		}

		$( '#aiisp-stock-status' ).prop( 'hidden', false );

		ajax(
			'aiisp_stock_search',
			{ source: $( '#aiisp-stock-source' ).val(), query: query },
			function ( data ) {
				$( '#aiisp-stock-status' ).prop( 'hidden', true );

				var results = data.results || [];
				var $grid   = $( '#aiisp-stock-results' );

				if ( ! results.length ) {
					$grid.append( $( '<p/>', { 'class': 'aiisp-muted', text: 'No results.' } ) );
					return;
				}

				// Build result buttons with DOM APIs (never string HTML)
				// so credits/URLs can't inject markup.
				results.forEach( function ( item ) {
					var $btn = $( '<button/>', { type: 'button', 'class': 'aiisp-stock-item' } )
						.attr( 'data-full', item.full )
						.attr( 'data-credit', item.credit );

					$btn.append( $( '<img/>', { src: item.thumb, alt: item.credit } ) );
					$btn.append( $( '<span/>', { 'class': 'aiisp-stock-credit', text: item.credit } ) );
					$grid.append( $btn );
				} );
			},
			function ( message ) {
				$( '#aiisp-stock-status' ).prop( 'hidden', true );
				$( '#aiisp-stock-error' ).prop( 'hidden', false ).text( message );
			}
		);
	}

	$( document ).on( 'click', '#aiisp-stock-search', runStockSearch );
	$( document ).on( 'keydown', '#aiisp-stock-query', function ( e ) {
		if ( 'Enter' === e.key ) {
			e.preventDefault();
			runStockSearch();
		}
	} );

	$( document ).on( 'click', '.aiisp-stock-item', function () {
		var $item = $( this );

		$item.addClass( 'is-importing' );

		ajax(
			'aiisp_stock_import',
			{
				image_url: $item.attr( 'data-full' ),
				credit: $item.attr( 'data-credit' ),
				post_id: aiispData.postId || 0
			},
			function ( data ) {
				$item.removeClass( 'is-importing' );

				lastAttachmentId = data.attachment_id || 0;

				// Reuse the preview area on the generate panel.
				$( '#aiisp-preview-img' ).attr( 'src', data.url || '' );
				$( '#aiisp-preview-provider' ).text( $item.attr( 'data-credit' ) || '' );
				$( '#aiisp-download' ).attr( 'href', data.url || '#' );
				$( '#aiisp-preview' ).prop( 'hidden', false );

				// Switch back to the generate tab to show the preview.
				$( '.aiisp-mb-tab[data-panel="generate"]' ).trigger( 'click' );
			},
			function ( message ) {
				$item.removeClass( 'is-importing' );
				$( '#aiisp-stock-error' ).prop( 'hidden', false ).text( message );
			}
		);
	} );

	/* =====================================================================
	 * Settings: provider cards + sortable fallback list
	 * ================================================================== */

	// Visual highlight follows the checked radio.
	$( document ).on( 'change', '.aiisp-provider-card input[type="radio"]', function () {
		$( '.aiisp-provider-card' ).removeClass( 'is-active' );
		$( this ).closest( '.aiisp-provider-card' ).addClass( 'is-active' );
	} );

	// The key field sits inside the provider card's <label>. Clicking
	// non-interactive parts of it would toggle the radio, so prevent
	// only the label's default action — never stop propagation, or
	// document-delegated handlers (like the Test button) go dead.
	$( document ).on( 'click', '.aiisp-provider-card .aiisp-key-field', function ( e ) {
		if ( ! $( e.target ).closest( 'input, button, a, select, textarea, label' ).length ) {
			e.preventDefault();
		}
	} );

	// Drag & drop fallback ordering (jQuery UI ships with WP core).
	if ( $.fn.sortable && $( '#aiisp-fallback-sortable' ).length ) {
		$( '#aiisp-fallback-sortable' ).sortable( {
			placeholder: 'aiisp-sortable-placeholder',
			update: function () {
				var order = $( this )
					.find( '.aiisp-sortable-item' )
					.map( function () {
						return $( this ).data( 'slug' );
					} )
					.get()
					.join( ',' );

				$( '#aiisp-fallback-order' ).val( order );
			}
		} );
	}

	/* =====================================================================
	 * Settings: clear history
	 * ================================================================== */

	$( document ).on( 'click', '#aiisp-clear-logs', function () {
		// Check: destructive action requires explicit confirmation.
		if ( ! window.confirm( i18n.confirmClear ) ) {
			return;
		}

		var $btn = $( this ).prop( 'disabled', true );

		ajax(
			'aiisp_clear_logs',
			{},
			function () {
				window.location.reload();
			},
			function ( message ) {
				$btn.prop( 'disabled', false );
				window.alert( message );
			}
		);
	} );

	/* =====================================================================
	 * Bulk generation (rebuilt): scan → select → sequential queue
	 * ================================================================== */

	var bulkQueue   = [];
	var bulkRunning = false;

	function bulkMessage( text ) {
		$( '#aiisp-bulk-message' ).prop( 'hidden', ! text ).text( text || '' );
	}

	// Step 1: scan for posts missing a featured image.
	$( document ).on( 'click', '#aiisp-bulk-scan', function () {
		var $btn = $( this ).prop( 'disabled', true );

		bulkMessage( i18n.generating );

		ajax(
			'aiisp_bulk_scan',
			{ post_type: $( '#aiisp-bulk-post-type' ).val() },
			function ( data ) {
				$btn.prop( 'disabled', false );
				bulkMessage( '' );

				var posts = data.posts || [];
				var $rows = $( '#aiisp-bulk-rows' ).empty();

				// Check: friendly empty state instead of a blank table.
				if ( ! posts.length ) {
					$( '#aiisp-bulk-results' ).prop( 'hidden', true );
					bulkMessage( i18n.noPosts );
					return;
				}

				posts.forEach( function ( post ) {
					var $tr = $( '<tr/>' ).attr( 'data-post-id', post.id );

					$tr.append(
						$( '<td/>' ).append(
							$( '<input/>', { type: 'checkbox', 'class': 'aiisp-bulk-check', checked: true } )
						)
					);

					$tr.append(
						$( '<td/>' ).append(
							$( '<a/>', { href: post.edit_link, target: '_blank', rel: 'noopener', text: post.title } ),
							$( '<span/>', { 'class': 'aiisp-badge', text: post.status, css: { marginLeft: '6px' } } )
						)
					);

					// Editable per-row prompt, prefilled with the title.
					$tr.append(
						$( '<td/>' ).append(
							$( '<input/>', { type: 'text', 'class': 'aiisp-bulk-prompt', value: post.title } )
						)
					);

					$tr.append( $( '<td/>', { 'class': 'aiisp-bulk-status' } ).append( $( '<span/>', { 'class': 'aiisp-muted', text: '—' } ) ) );

					$rows.append( $tr );
				} );

				$( '#aiisp-bulk-results' ).prop( 'hidden', false );
				$( '#aiisp-bulk-progress' ).css( 'width', '0%' );
			},
			function ( message ) {
				$btn.prop( 'disabled', false );
				bulkMessage( message );
			}
		);
	} );

	// Select-all checkbox.
	$( document ).on( 'change', '#aiisp-bulk-select-all', function () {
		$( '.aiisp-bulk-check' ).prop( 'checked', $( this ).prop( 'checked' ) );
	} );

	// Step 3: run the queue — strictly one post at a time so paid
	// APIs are never hit with parallel bursts.
	$( document ).on( 'click', '#aiisp-bulk-generate', function () {
		// Check: prevent double-starting the queue.
		if ( bulkRunning ) {
			return;
		}

		var $rows = $( '#aiisp-bulk-rows tr' ).filter( function () {
			return $( this ).find( '.aiisp-bulk-check' ).prop( 'checked' );
		} );

		// Check: at least one row must be selected.
		if ( ! $rows.length ) {
			bulkMessage( i18n.selectPosts );
			return;
		}

		bulkQueue = $rows.toArray();
		bulkRunning = true;
		bulkMessage( '' );

		$( '#aiisp-bulk-generate, #aiisp-bulk-scan' ).prop( 'disabled', true );

		var total = bulkQueue.length;
		var done  = 0;

		function next() {
			// Queue finished → restore the UI.
			if ( ! bulkQueue.length ) {
				bulkRunning = false;
				$( '#aiisp-bulk-generate, #aiisp-bulk-scan' ).prop( 'disabled', false );
				bulkMessage( i18n.completed );
				return;
			}

			var row     = bulkQueue.shift();
			var $row    = $( row );
			var $status = $row.find( '.aiisp-bulk-status' ).empty();

			$status.append( $( '<span/>', { 'class': 'aiisp-badge', text: i18n.generating } ) );

			ajax(
				'aiisp_bulk_generate',
				{
					post_id: $row.data( 'post-id' ),
					prompt: $row.find( '.aiisp-bulk-prompt' ).val(),
					style: $( '#aiisp-bulk-style' ).val() || 'none'
				},
				function ( data ) {
					$status.empty().append(
						$( '<img/>', { src: data.thumb || data.url, css: { width: '36px', height: '36px', borderRadius: '6px', objectFit: 'cover', verticalAlign: 'middle', marginRight: '6px' } } ),
						$( '<span/>', { 'class': 'aiisp-badge aiisp-badge-ok', text: i18n.completed } )
					);
					step();
				},
				function ( message ) {
					$status.empty().append(
						$( '<span/>', { 'class': 'aiisp-badge aiisp-badge-fail', text: message, attr: { title: message } } )
					);
					step();
				}
			);
		}

		function step() {
			done += 1;
			$( '#aiisp-bulk-progress' ).css( 'width', Math.round( ( done / total ) * 100 ) + '%' );
			next();
		}

		next();
	} );

	/* =====================================================================
	 * Shared modal / lightbox component
	 * ================================================================== */

	/**
	 * Open a modal. Pass a jQuery element for the body; the overlay,
	 * close button, ESC key and outside-click handling are managed
	 * here so every popup behaves identically.
	 *
	 * @param {jQuery} $content Modal inner content.
	 */
	function openModal( $content ) {
		closeModal();

		var $overlay = $( '<div/>', { 'class': 'aiisp-modal-overlay' } );
		var $modal   = $( '<div/>', { 'class': 'aiisp-modal' } );
		var $close   = $( '<button/>', { type: 'button', 'class': 'aiisp-modal-close', 'aria-label': 'Close', html: '&times;' } );

		$modal.append( $close, $content );
		$overlay.append( $modal );
		$( 'body' ).append( $overlay );

		// Close on X, on clicking the dark backdrop, and on ESC.
		$close.on( 'click', closeModal );
		$overlay.on( 'click', function ( e ) {
			if ( e.target === this ) {
				closeModal();
			}
		} );
		$( document ).on( 'keydown.aiispModal', function ( e ) {
			if ( 'Escape' === e.key ) {
				closeModal();
			}
		} );
	}

	function closeModal() {
		$( '.aiisp-modal-overlay' ).remove();
		$( document ).off( 'keydown.aiispModal' );
	}

	/**
	 * Full-size image lightbox.
	 *
	 * @param {string} src     Image source.
	 * @param {string} caption Optional caption line.
	 */
	function openLightbox( src, caption ) {
		// Check: never open an empty lightbox.
		if ( ! src ) {
			return;
		}

		var $wrap = $( '<div/>' );
		$wrap.append( $( '<img/>', { 'class': 'aiisp-modal-img', src: src, alt: caption || '' } ) );

		if ( caption ) {
			$wrap.append( $( '<div/>', { 'class': 'aiisp-modal-caption', text: caption } ) );
		}

		openModal( $wrap );
	}

	/**
	 * "Saved to gallery" success popup: thumbnail, permanent site
	 * URL with a copy button, and links to the Media Library.
	 *
	 * @param {Object} data { url, thumb, library_link }.
	 */
	function openSavedPopup( data ) {
		var $body = $( '<div/>', { 'class': 'aiisp-modal-body' } );

		if ( data.thumb || data.url ) {
			$body.append( $( '<img/>', { 'class': 'aiisp-modal-thumb', src: data.thumb || data.url, alt: '' } ) );
		}

		$body.append( $( '<h3/>', { text: i18n.saved } ) );

		// URL + copy row.
		var $input = $( '<input/>', { type: 'text', 'class': 'aiisp-input', readonly: true, value: data.url || '' } );
		var $copy  = $( '<button/>', { type: 'button', 'class': 'aiisp-btn aiisp-btn-primary', text: i18n.copy } );

		$copy.on( 'click', function () {
			$input.trigger( 'select' );

			// Modern clipboard API with a safe fallback for http admins.
			if ( navigator.clipboard && navigator.clipboard.writeText ) {
				navigator.clipboard.writeText( $input.val() );
			} else {
				document.execCommand( 'copy' );
			}

			$copy.text( i18n.copied );
			window.setTimeout( function () {
				$copy.text( i18n.copy );
			}, 1600 );
		} );

		$body.append( $( '<div/>', { 'class': 'aiisp-copy-row' } ).append( $input, $copy ) );

		if ( data.library_link ) {
			$body.append(
				$( '<div/>', { 'class': 'aiisp-modal-actions' } ).append(
					$( '<a/>', { 'class': 'aiisp-btn aiisp-btn-secondary', href: data.library_link, text: 'Open in Media Library' } )
				)
			);
		}

		openModal( $body );
	}

	// Meta box preview → lightbox.
	$( document ).on( 'click', '.aiisp-open-lightbox', function () {
		openLightbox( $( '#aiisp-download' ).attr( 'href' ) || $( '#aiisp-preview-img' ).attr( 'src' ), $( '#aiisp-preview-provider' ).text() );
	} );

	/* =====================================================================
	 * Settings: Test key buttons
	 * ================================================================== */

	/**
	 * A Test button is usable when its input has a typed value OR a key
	 * is already saved on the server (data-saved="1") — the endpoint
	 * falls back to the stored key when none is typed.
	 *
	 * @param {jQuery} $input Key input.
	 * @return {boolean}
	 */
	function keyTestable( $input ) {
		var typed = '' !== $.trim( String( $input.val() || '' ) );
		var saved = '1' === String( $input.data( 'saved' ) || '0' );

		return typed || saved;
	}

	// Re-evaluate the button state live as the admin types.
	$( document ).on( 'input', '.aiisp-input-group .aiisp-input[type="password"]', function () {
		var $input = $( this );

		$input.closest( '.aiisp-input-group' )
			.find( '.aiisp-test-key' )
			.prop( 'disabled', ! keyTestable( $input ) );
	} );

	$( document ).on( 'click', '.aiisp-test-key', function ( e ) {
		e.preventDefault();
		e.stopPropagation();

		var $btn    = $( this );
		var $group  = $btn.closest( '.aiisp-input-group' );
		var $wrap   = $group.parent(); // .aiisp-key-field or .aiisp-cred-controls
		var $input  = $group.find( 'input[type="password"]' ).first();
		var $result = $wrap.find( '.aiisp-test-result' ).first();
		var key     = $.trim( String( $input.val() || '' ) );

		// Check: nothing typed and nothing saved → nothing to test.
		if ( ! keyTestable( $input ) ) {
			return;
		}

		$btn.prop( 'disabled', true ).text( i18n.testing );
		$result.prop( 'hidden', true ).removeClass( 'is-ok is-fail is-warn' );

		ajax(
			'aiisp_test_key',
			{
				kind: $btn.data( 'kind' ),
				slug: $btn.data( 'slug' ),
				key: key // Empty string = "test the saved key" server-side.
			},
			function ( data ) {
				$btn.prop( 'disabled', false ).text( i18n.testKey );

				// Three states: ok (green), warn (amber, unverifiable).
				var cls = 'warn' === data.state ? 'is-warn' : 'is-ok';
				$result.prop( 'hidden', false ).addClass( cls ).text( data.message || 'OK' );
			},
			function ( message ) {
				$btn.prop( 'disabled', false ).text( i18n.testKey );
				$result.prop( 'hidden', false ).addClass( 'is-fail' ).text( message );
			}
		);
	} );

	/* =====================================================================
	 * Image Workspace (Media → AI Image Workspace)
	 * ================================================================== */

	var $studioGrid = $( '#aiisp-studio-grid' );

	function studioActive() {
		return $studioGrid.length > 0;
	}

	function studioError( message ) {
		$( '#aiisp-studio-error' ).prop( 'hidden', ! message ).text( message || '' );
	}

	function studioStatus( text ) {
		$( '#aiisp-studio-status' ).prop( 'hidden', ! text );
		$( '#aiisp-studio-status-text' ).text( text || '' );
	}

	// Mode switcher (Generate / Stock).
	$( document ).on( 'click', '.aiisp-studio-tab', function () {
		var mode = $( this ).data( 'mode' );

		$( '.aiisp-studio-tab' ).removeClass( 'is-active' );
		$( this ).addClass( 'is-active' );

		$( '.aiisp-studio-controls' ).removeClass( 'is-active' );
		$( '.aiisp-studio-controls[data-mode="' + mode + '"]' ).addClass( 'is-active' );

		studioError( '' );
	} );

	/**
	 * Append one result card to the studio grid.
	 *
	 * @param {Object} item { src, meta, prompt } — src may be a URL or data URI.
	 * @return {jQuery} The card element.
	 */
	function studioCard( item ) {
		$( '#aiisp-studio-empty' ).hide();

		var $card    = $( '<div/>', { 'class': 'aiisp-studio-card' } );
		var $img     = $( '<img/>', { src: item.src, alt: item.meta || '' } );
		var $overlay = $( '<div/>', { 'class': 'aiisp-card-overlay' } );

		// Eye: full-size lightbox.
		var $eye = $( '<button/>', { type: 'button', 'class': 'aiisp-card-eye', title: 'View large' } )
			.append( $( '<span/>', { 'class': 'dashicons dashicons-visibility' } ) )
			.on( 'click', function () {
				openLightbox( item.src, item.meta );
			} );

		// Save to gallery.
		var $save = $( '<button/>', { type: 'button', 'class': 'aiisp-btn aiisp-btn-primary aiisp-btn-sm', text: i18n.saveGallery } )
			.on( 'click', function () {
				var $b = $( this ).prop( 'disabled', true ).text( i18n.saving );

				ajax(
					'aiisp_import_preview',
					{ src: item.src, prompt: item.prompt || item.meta || '' },
					function ( data ) {
						$b.text( i18n.saved );
						$card.prepend( $( '<span/>', { 'class': 'aiisp-badge aiisp-badge-ok aiisp-badge-saved', text: i18n.saved } ) );
						openSavedPopup( data );
					},
					function ( message ) {
						$b.prop( 'disabled', false ).text( i18n.saveGallery );
						studioError( message );
					}
				);
			} );

		$overlay.append(
			$eye,
			$( '<div/>', { 'class': 'aiisp-card-footer' } ).append(
				$( '<span/>', { 'class': 'aiisp-card-meta', text: item.meta || '' } ),
				$save
			)
		);

		$card.append( $img, $overlay );
		$studioGrid.prepend( $card );

		return $card;
	}

	/**
	 * Append a skeleton placeholder card while a generation runs, so
	 * the grid gives instant feedback for every image in the batch.
	 *
	 * @return {jQuery} The placeholder (replace or remove when done).
	 */
	function studioSkeleton() {
		$( '#aiisp-studio-empty' ).hide();

		var $card = $( '<div/>', { 'class': 'aiisp-studio-card is-loading' } )
			.append( $( '<img/>', { src: 'data:image/gif;base64,R0lGODlhAQABAAAAACw=', alt: '' } ) )
			.append(
				$( '<div/>', { 'class': 'aiisp-card-loading' } )
					.append( $( '<span/>', { 'class': 'aiisp-spinner' } ) )
					.append( $( '<span/>', { text: i18n.generating } ) )
			);

		$studioGrid.prepend( $card );
		return $card;
	}

	// Generate N images through a small parallel pool (see below).
	$( document ).on( 'click', '#aiisp-studio-generate', function () {
		var prompt = $.trim( $( '#aiisp-studio-prompt' ).val() || '' );
		// The workspace generates one image per click by design.
		var count  = 1;
		var style  = $( '#aiisp-studio-style' ).val() || 'none';
		var $btn   = $( this );

		studioError( '' );

		// Check: prompt is required.
		if ( ! prompt ) {
			studioError( i18n.emptyPrompt );
			$( '#aiisp-studio-prompt' ).focus();
			return;
		}

		$btn.prop( 'disabled', true );

		/*
		 * Concurrency pool: run up to 3 generations in parallel instead
		 * of one at a time. AI engines take 10–40s per image, so a
		 * sequential batch of 8 could take five minutes; pooled, the
		 * wall time is roughly a third. The cap stays at 3 so paid
		 * APIs aren't hammered and rate limits aren't tripped.
		 */
		var POOL_SIZE = 3;
		var started   = 0;
		var finished  = 0;
		var errors    = [];

		function updateStatus() {
			studioStatus( i18n.generating + ' ' + Math.min( finished + 1, count ) + ' / ' + count );
		}

		function onSettled() {
			finished += 1;

			// Batch complete → restore controls, surface any failures.
			if ( finished >= count ) {
				$btn.prop( 'disabled', false );
				studioStatus( '' );

				if ( errors.length ) {
					// Show one representative error with a failure count.
					studioError( errors[ 0 ] + ( errors.length > 1 ? ' (×' + errors.length + ')' : '' ) );
				}
				return;
			}

			updateStatus();
			launchNext();
		}

		function launchNext() {
			// Check: never start more jobs than requested.
			if ( started >= count ) {
				return;
			}
			started += 1;

			// Instant feedback: a skeleton card appears immediately and
			// is swapped for the real image (or removed on failure).
			var $skeleton = studioSkeleton();

			ajax(
				'aiisp_generate_image',
				{ prompt: prompt, style: style, post_id: 0, preview: 1 },
				function ( data ) {
					studioCard( { src: data.src, meta: data.provider || '', prompt: prompt } ).insertBefore( $skeleton );
					$skeleton.remove();
					onSettled();
				},
				function ( message ) {
					// Collect the failure but keep the rest of the batch going.
					$skeleton.remove();
					errors.push( message );
					onSettled();
				}
			);
		}

		updateStatus();

		// Prime the pool: min(POOL_SIZE, count) parallel workers.
		for ( var w = 0; w < Math.min( POOL_SIZE, count ); w++ ) {
			launchNext();
		}
	} );

	// Stock search → grid of cards.
	function studioStockSearch() {
		var query = $.trim( $( '#aiisp-studio-query' ).val() || '' );

		studioError( '' );

		// Check: search term is required.
		if ( ! query ) {
			$( '#aiisp-studio-query' ).focus();
			return;
		}

		var $btn = $( '#aiisp-studio-search' ).prop( 'disabled', true );
		studioStatus( i18n.sending );

		ajax(
			'aiisp_stock_search',
			{ source: $( '#aiisp-studio-source' ).val(), query: query },
			function ( data ) {
				$btn.prop( 'disabled', false );
				studioStatus( '' );

				var results = data.results || [];

				// Check: friendly message when nothing was found.
				if ( ! results.length ) {
					studioError( i18n.noResults );
					return;
				}

				results.forEach( function ( item ) {
					studioCard( { src: item.full, meta: item.credit || '', prompt: item.credit || '' } );
				} );
			},
			function ( message ) {
				$btn.prop( 'disabled', false );
				studioStatus( '' );
				studioError( message );
			}
		);
	}

	$( document ).on( 'click', '#aiisp-studio-search', studioStockSearch );
	$( document ).on( 'keydown', '#aiisp-studio-query', function ( e ) {
		if ( 'Enter' === e.key ) {
			e.preventDefault();
			studioStockSearch();
		}
	} );

} )( jQuery );
