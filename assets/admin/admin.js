/**
 * Auto Multi-Meta — Admin JavaScript
 *
 * Provides hash-based tab navigation, checklist helpers, and manager page
 * AJAX actions (generate, preview, bulk generate) for the plugin admin pages.
 *
 * @package Auto_Multi_Meta
 */

/* global ammAdmin */

document.addEventListener( 'DOMContentLoaded', () => {
	initTabs();
	initChecklistButtons();
	initTestConnection();
	initManagerPage();
	initBatchPanel();
} );

/**
 * Initialises hash-based tabbed navigation.
 *
 * Active tab state is persisted in the URL hash, supporting page reload,
 * browser back/forward, and deep-linking to specific tabs.
 */
function initTabs() {
	const tabs   = document.querySelectorAll( '.nav-tab' );
	const panels = document.querySelectorAll( '.amm-tab-panel' );

	if ( ! tabs.length || ! panels.length ) {
		return;
	}

	const defaultTab = ( ammAdmin && ammAdmin.defaultTab ) ? ammAdmin.defaultTab : 'settings';

	/**
	 * Activates a tab by name, showing its panel and hiding all others.
	 *
	 * @param {string} tabName
	 */
	function activateTab( tabName ) {
		const matchingTab = document.querySelector( `[data-tab="${ tabName }"]` );
		const resolvedTab = matchingTab ? tabName : defaultTab;

		tabs.forEach( ( tab ) => {
			tab.classList.toggle( 'nav-tab-active', tab.dataset.tab === resolvedTab );
		} );

		panels.forEach( ( panel ) => {
			const isActive = panel.id === `${ resolvedTab }-panel`;
			panel.style.display = isActive ? 'block' : 'none';
			panel.classList.toggle( 'active', isActive );
		} );
	}

	// Activate from current URL hash (strip leading #).
	const initialTab = window.location.hash.replace( '#', '' ) || defaultTab;
	activateTab( initialTab );

	// Tab click handlers — update hash, which triggers hashchange.
	tabs.forEach( ( tab ) => {
		tab.addEventListener( 'click', ( event ) => {
			event.preventDefault();
			window.location.hash = tab.dataset.tab;
			activateTab( tab.dataset.tab );
		} );
	} );

	// Handle browser back/forward navigation.
	window.addEventListener( 'hashchange', () => {
		const tabName = window.location.hash.replace( '#', '' ) || defaultTab;
		activateTab( tabName );
	} );

	// Preserve the active tab hash through settings form submission.
	// WordPress options.php reads _wp_http_referer to build the redirect URL.
	// The browser strips the fragment from the Referer header, so we inject it
	// into the hidden _wp_http_referer field before the form is submitted.
	const settingsForm = document.getElementById( 'amm-settings-form' );

	if ( settingsForm ) {
		settingsForm.addEventListener( 'submit', () => {
			const refererInput = settingsForm.querySelector( 'input[name="_wp_http_referer"]' );

			if ( refererInput ) {
				const currentHash = window.location.hash || ( '#' + defaultTab );
				const baseUrl     = refererInput.value.replace( /#.*$/, '' );
				refererInput.value = baseUrl + currentHash;
			}
		} );
	}
}

/**
 * Initialises "Check All" / "Uncheck All" buttons for checklist groups.
 */
function initChecklistButtons() {
	const container = document.getElementById( 'amm-settings-form' );

	if ( ! container ) {
		return;
	}

	container.addEventListener( 'click', ( event ) => {
		const target = event.target;

		if ( target.classList.contains( 'amm-check-all' ) ) {
			toggleCheckboxGroup( target.dataset.group, true );
		}

		if ( target.classList.contains( 'amm-uncheck-all' ) ) {
			toggleCheckboxGroup( target.dataset.group, false );
		}
	} );
}

/**
 * Checks or unchecks all checkboxes within a named group panel.
 *
 * @param {string}  group   Data group name matching the panel id prefix.
 * @param {boolean} checked Whether to check or uncheck.
 */
function toggleCheckboxGroup( group, checked ) {
	const panel      = document.getElementById( `${ group }-panel` );
	const checkboxes = panel ? panel.querySelectorAll( 'input[type="checkbox"]' ) : [];

	checkboxes.forEach( ( checkbox ) => {
		checkbox.checked = checked;
	} );
}

/**
 * Initialises the Test Connection AJAX button.
 *
 * Sends a minimal prompt to the configured AI provider and reports
 * success or failure in the result span.
 */
function initTestConnection() {
	const button    = document.getElementById( 'amm-test-connection' );
	const resultEl  = document.getElementById( 'amm-test-result' );

	if ( ! button || ! resultEl ) {
		return;
	}

	button.addEventListener( 'click', () => {
		const originalText = button.textContent;

		button.disabled    = true;
		button.textContent = button.dataset.testing || 'Testing\u2026';

		resultEl.style.display = 'none';
		resultEl.className     = 'amm-test-result';
		resultEl.textContent   = '';

		const formData = new FormData();
		formData.append( 'action', 'amm_test_connection' );
		formData.append( 'nonce', ammAdmin.nonce );

		fetch( ammAdmin.ajaxUrl, { method: 'POST', body: formData } )
			.then( ( response ) => response.json() )
			.then( ( data ) => {
				if ( data.success ) {
					resultEl.textContent = data.data.message;
					resultEl.classList.add( 'is-success' );
				} else {
					const msg = ( data.data && data.data.message ) ? data.data.message : 'An unknown error occurred.';
					resultEl.textContent = msg;
					resultEl.classList.add( 'is-error' );
				}

				resultEl.style.display = '';
			} )
			.catch( () => {
				resultEl.textContent = 'Request failed. Please try again.';
				resultEl.classList.add( 'is-error' );
				resultEl.style.display = '';
			} )
			.finally( () => {
				button.disabled    = false;
				button.textContent = originalText;
			} );
	} );
}

/**
 * Initialises the Term Manager and Post Manager admin pages.
 *
 * Handles:
 * - Per-row Generate / Regenerate buttons (AJAX)
 * - Per-row Preview buttons (AJAX, shows preview area)
 * - Preview area Save and Dismiss
 * - Bulk "Generate Missing Descriptions" action (sequential AJAX)
 *
 * Rows are updated in-place after successful generation so the user
 * can see status changes without a page reload.
 */
function initManagerPage() {
	const managerForm = document.getElementById( 'amm-term-manager-form' ) ||
	                    document.getElementById( 'amm-post-manager-form' );

	if ( ! managerForm ) {
		return;
	}

	const noticeEl       = document.getElementById( 'amm-manager-notice' );
	const previewArea    = document.getElementById( 'amm-preview-area' );
	const previewContent = document.getElementById( 'amm-preview-content' );
	const previewSaveBtn = document.getElementById( 'amm-preview-save' );
	const previewDismiss = document.getElementById( 'amm-preview-dismiss' );

	// Stores context for the currently visible preview so Save knows what to generate.
	let previewContext = null;

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	/**
	 * Escapes a string for safe insertion as HTML text content.
	 *
	 * @param {string} str Raw string.
	 * @return {string} HTML-escaped string.
	 */
	function escHtml( str ) {
		return String( str )
			.replace( /&/g, '&amp;' )
			.replace( /</g, '&lt;' )
			.replace( />/g, '&gt;' )
			.replace( /"/g, '&quot;' );
	}

	/**
	 * Shows a notice above the list table.
	 *
	 * @param {string}  message  Message text.
	 * @param {boolean} isError  True for error styling, false for success.
	 */
	function showNotice( message, isError ) {
		if ( ! noticeEl ) {
			return;
		}

		noticeEl.textContent  = message;
		noticeEl.className    = 'amm-manager-notice notice notice-' + ( isError ? 'error' : 'success' );
		noticeEl.style.display = '';
	}

	/**
	 * Updates a table row's description and status cells in-place.
	 *
	 * @param {string} type     Item type: 'term' or 'post'.
	 * @param {string} id       Item ID.
	 * @param {string} taxonomy Taxonomy slug (terms only).
	 * @param {string} desc     Generated meta description.
	 */
	function updateRow( type, id, taxonomy, desc ) {
		let row = null;

		if ( 'term' === type ) {
			row = document.querySelector(
				`tr[data-type="term"][data-id="${ id }"][data-taxonomy="${ taxonomy }"]`
			);
		} else {
			row = document.querySelector( `tr[data-type="post"][data-id="${ id }"]` );
		}

		if ( ! row || ! desc ) {
			return;
		}

		// Description cell.
		const descCell = row.querySelector( '.column-description' );
		if ( descCell ) {
			const len     = desc.length;
			const preview = len > 80 ? desc.substring( 0, 80 ) + '\u2026' : desc;

			descCell.innerHTML = '<span class="amm-desc-text" title="' + escHtml( desc ) + '">' +
			                     escHtml( preview ) + '</span>' +
			                     ' <span class="amm-desc-chars">(' + len + ' chars)</span>';
		}

		// Status cell.
		const statusCell = row.querySelector( '.column-status' );
		if ( statusCell ) {
			const len = desc.length;
			const min = 120;
			const max = 160;

			if ( len >= min && len <= max ) {
				statusCell.innerHTML = '<span class="amm-status amm-status-good" title="Good length (120\u2013160 chars)">&#9989;</span>';
			} else {
				statusCell.innerHTML = '<span class="amm-status amm-status-warn" title="Exists but outside optimal length (120\u2013160 chars)">&#9888;&#65039;</span>';
			}
		}

		// Update generate button label to "Regenerate".
		const genBtn = row.querySelector( '.amm-generate-btn' );
		if ( genBtn ) {
			genBtn.textContent = ammAdmin.i18n.regenerate || 'Regenerate';
		}
	}

	/**
	 * Sends an AJAX request to generate a single meta description.
	 *
	 * @param {string}   type     Item type: 'term' or 'post'.
	 * @param {string}   id       Item ID.
	 * @param {string}   taxonomy Taxonomy slug (terms only; pass '' for posts).
	 * @param {boolean}  force    Whether to overwrite existing descriptions.
	 * @param {Function} callback Called with (error, responseData).
	 */
	function ajaxGenerate( type, id, taxonomy, force, callback ) {
		const formData = new FormData();
		formData.append( 'action', 'amm_generate_single' );
		formData.append( 'nonce', ammAdmin.nonce );
		formData.append( 'type', type );
		formData.append( 'id', id );
		formData.append( 'force', force ? '1' : '0' );

		if ( taxonomy ) {
			formData.append( 'taxonomy', taxonomy );
		}

		fetch( ammAdmin.ajaxUrl, { method: 'POST', body: formData } )
			.then( ( r ) => r.json() )
			.then( ( data ) => callback( null, data ) )
			.catch( ( err ) => callback( err, null ) );
	}

	/**
	 * Sends an AJAX request to preview a generated meta description (no save).
	 *
	 * @param {string}   type     Item type: 'term' or 'post'.
	 * @param {string}   id       Item ID.
	 * @param {string}   taxonomy Taxonomy slug (terms only; pass '' for posts).
	 * @param {Function} callback Called with (error, responseData).
	 */
	function ajaxPreview( type, id, taxonomy, callback ) {
		const formData = new FormData();
		formData.append( 'action', 'amm_preview' );
		formData.append( 'nonce', ammAdmin.nonce );
		formData.append( 'type', type );
		formData.append( 'id', id );

		if ( taxonomy ) {
			formData.append( 'taxonomy', taxonomy );
		}

		fetch( ammAdmin.ajaxUrl, { method: 'POST', body: formData } )
			.then( ( r ) => r.json() )
			.then( ( data ) => callback( null, data ) )
			.catch( ( err ) => callback( err, null ) );
	}

	// -----------------------------------------------------------------------
	// Generate button
	// -----------------------------------------------------------------------

	managerForm.addEventListener( 'click', ( event ) => {
		const btn = event.target.closest( '.amm-generate-btn' );

		if ( ! btn ) {
			return;
		}

		const type     = btn.dataset.type     || '';
		const id       = btn.dataset.id       || '';
		const taxonomy = btn.dataset.taxonomy || '';
		const original = btn.textContent;

		btn.disabled    = true;
		btn.textContent = ammAdmin.i18n.generating || 'Generating\u2026';

		ajaxGenerate( type, id, taxonomy, true, ( err, data ) => {
			btn.disabled    = false;
			btn.textContent = original;

			if ( err || ! data ) {
				showNotice( ammAdmin.i18n.requestFailed || 'Request failed. Please try again.', true );
				return;
			}

			if ( data.success ) {
				updateRow( type, id, taxonomy, data.data.description || '' );
				showNotice( data.data.message || 'Generated.', false );
			} else {
				const msg = ( data.data && data.data.message ) ? data.data.message : 'An error occurred.';
				showNotice( msg, true );
			}
		} );
	} );

	// -----------------------------------------------------------------------
	// Preview button
	// -----------------------------------------------------------------------

	managerForm.addEventListener( 'click', ( event ) => {
		const btn = event.target.closest( '.amm-preview-btn' );

		if ( ! btn ) {
			return;
		}

		const type     = btn.dataset.type     || '';
		const id       = btn.dataset.id       || '';
		const taxonomy = btn.dataset.taxonomy || '';
		const original = btn.textContent;

		btn.disabled    = true;
		btn.textContent = ammAdmin.i18n.previewing || 'Previewing\u2026';

		ajaxPreview( type, id, taxonomy, ( err, data ) => {
			btn.disabled    = false;
			btn.textContent = original;

			if ( err || ! data ) {
				showNotice( ammAdmin.i18n.requestFailed || 'Request failed. Please try again.', true );
				return;
			}

			if ( data.success ) {
				const desc = data.data.description || '';
				const len  = desc.length;

				previewContext = { type, id, taxonomy };

				if ( previewContent ) {
					previewContent.innerHTML =
						'<p class="amm-preview-desc">' + escHtml( desc ) + '</p>' +
						'<p class="amm-preview-meta">' + len + ' characters</p>';
				}

				if ( previewArea ) {
					previewArea.style.display = '';
					previewArea.scrollIntoView( { behavior: 'smooth', block: 'nearest' } );
				}
			} else {
				const msg = ( data.data && data.data.message ) ? data.data.message : 'An error occurred.';
				showNotice( msg, true );
			}
		} );
	} );

	// -----------------------------------------------------------------------
	// Preview area: Save This Description
	// -----------------------------------------------------------------------

	if ( previewSaveBtn ) {
		previewSaveBtn.addEventListener( 'click', () => {
			if ( ! previewContext ) {
				return;
			}

			const { type, id, taxonomy } = previewContext;
			const originalText           = previewSaveBtn.textContent;

			previewSaveBtn.disabled    = true;
			previewSaveBtn.textContent = ammAdmin.i18n.generating || 'Generating\u2026';

			ajaxGenerate( type, id, taxonomy, true, ( err, data ) => {
				previewSaveBtn.disabled    = false;
				previewSaveBtn.textContent = originalText;

				if ( previewArea ) {
					previewArea.style.display = 'none';
				}

				previewContext = null;

				if ( err || ! data ) {
					showNotice( ammAdmin.i18n.requestFailed || 'Request failed. Please try again.', true );
					return;
				}

				if ( data.success ) {
					updateRow( type, id, taxonomy, data.data.description || '' );
					showNotice( data.data.message || 'Saved.', false );
				} else {
					const msg = ( data.data && data.data.message ) ? data.data.message : 'An error occurred.';
					showNotice( msg, true );
				}
			} );
		} );
	}

	// -----------------------------------------------------------------------
	// Preview area: Dismiss
	// -----------------------------------------------------------------------

	if ( previewDismiss ) {
		previewDismiss.addEventListener( 'click', () => {
			if ( previewArea ) {
				previewArea.style.display = 'none';
			}

			previewContext = null;
		} );
	}

	// -----------------------------------------------------------------------
	// Bulk action: Generate Missing Descriptions
	// -----------------------------------------------------------------------

	managerForm.addEventListener( 'submit', ( event ) => {
		const topSelect    = managerForm.querySelector( 'select[name="action"]' );
		const bottomSelect = managerForm.querySelector( 'select[name="action2"]' );
		const topVal       = topSelect    ? topSelect.value    : '-1';
		const bottomVal    = bottomSelect ? bottomSelect.value : '-1';
		const action       = '-1' !== topVal ? topVal : bottomVal;

		if ( 'generate_missing' !== action ) {
			return;
		}

		event.preventDefault();

		const typeInput = managerForm.querySelector( 'input[name="amm_type"]' );
		const type      = typeInput ? typeInput.value : '';

		const checkedBoxes = managerForm.querySelectorAll( 'input[name="item_ids[]"]:checked' );

		if ( 0 === checkedBoxes.length ) {
			showNotice( 'No items selected.', true );
			return;
		}

		// Only include items that are missing a description.
		const allIds = Array.from( checkedBoxes ).map( ( cb ) => cb.value );

		const missingIds = allIds.filter( ( rawId ) => {
			let row = null;

			if ( 'term' === type ) {
				const parts    = rawId.split( '|' );
				const termId   = parts[ 0 ];
				const taxonomy = parts[ 1 ] || '';
				row = document.querySelector(
					`tr[data-type="term"][data-id="${ termId }"][data-taxonomy="${ taxonomy }"]`
				);
			} else {
				row = document.querySelector( `tr[data-type="post"][data-id="${ rawId }"]` );
			}

			if ( ! row ) {
				return true;
			}

			return null !== row.querySelector( '.amm-status-missing' );
		} );

		const idsToProcess = missingIds.length > 0 ? missingIds : allIds;
		const total        = idsToProcess.length;
		let generated      = 0;
		let skipped        = 0;
		let errors         = 0;

		showNotice(
			( ammAdmin.i18n.bulkProgress || 'Generating %1$d of %2$d\u2026' )
				.replace( '%1$d', '0' )
				.replace( '%2$d', String( total ) ),
			false
		);

		/**
		 * Processes items one at a time to avoid hammering the API.
		 *
		 * @param {number} index Current item index.
		 */
		function processNext( index ) {
			if ( index >= idsToProcess.length ) {
				showNotice(
					( ammAdmin.i18n.bulkComplete || '%1$d generated, %2$d skipped, %3$d errors.' )
						.replace( '%1$d', String( generated ) )
						.replace( '%2$d', String( skipped ) )
						.replace( '%3$d', String( errors ) ),
					errors > 0
				);

				return;
			}

			const rawId = idsToProcess[ index ];
			let id      = rawId;
			let taxonomy = '';

			if ( 'term' === type ) {
				const parts = rawId.split( '|' );
				id          = parts[ 0 ];
				taxonomy    = parts[ 1 ] || '';
			}

			showNotice(
				( ammAdmin.i18n.bulkProgress || 'Generating %1$d of %2$d\u2026' )
					.replace( '%1$d', String( index + 1 ) )
					.replace( '%2$d', String( total ) ),
				false
			);

			ajaxGenerate( type, id, taxonomy, false, ( err, data ) => {
				if ( err || ! data ) {
					errors++;
				} else if ( data.success ) {
					const status = data.data.status || '';
					const desc   = data.data.description || '';

					if ( 'generated' === status ) {
						generated++;
						updateRow( type, id, taxonomy, desc );
					} else if ( 'skipped' === status ) {
						skipped++;
					} else {
						errors++;
					}
				} else {
					errors++;
				}

				processNext( index + 1 );
			} );
		}

		processNext( 0 );
	} );
}

/**
 * Initialises the background batch generation panel.
 *
 * Handles:
 * - "Generate All Missing" button: starts a background batch via amm_start_batch AJAX
 * - Progress bar and status text: polls amm_batch_progress every 3 seconds
 * - "Cancel Batch" button: stops the running batch via amm_cancel_batch AJAX
 * - Page-load check: if a batch is already running, shows progress automatically
 */
function initBatchPanel() {
	const startBtn       = document.getElementById( 'amm-batch-start' );
	const cancelBtn      = document.getElementById( 'amm-batch-cancel' );
	const progressWrap   = document.getElementById( 'amm-batch-progress-wrap' );
	const progressBar    = document.getElementById( 'amm-batch-bar' );
	const statusEl       = document.getElementById( 'amm-batch-status' );
	const batchTypeInput = document.getElementById( 'amm-batch-type' );

	if ( ! startBtn ) {
		return;
	}

	let pollInterval = null;

	/**
	 * Clears the progress polling interval.
	 */
	function clearPoll() {
		if ( pollInterval ) {
			clearInterval( pollInterval );
			pollInterval = null;
		}
	}

	/**
	 * Shows or hides the progress bar area.
	 *
	 * @param {boolean} visible True to show, false to hide.
	 */
	function setProgressVisible( visible ) {
		if ( progressWrap ) {
			progressWrap.style.display = visible ? '' : 'none';
		}
	}

	/**
	 * Updates the batch panel UI based on a progress response object.
	 *
	 * @param {Object} data Progress object from amm_batch_progress or amm_start_batch.
	 */
	function updateProgress( data ) {
		const status    = data.status || 'idle';
		const total     = data.total || 0;
		const completed = data.completed || 0;
		const failed    = data.failed || 0;
		const pct       = total > 0 ? Math.round( ( completed / total ) * 100 ) : 0;
		let statusText  = '';

		if ( progressBar ) {
			progressBar.style.width = pct + '%';
		}

		if ( 'running' === status ) {
			startBtn.disabled = true;
			if ( cancelBtn ) {
				cancelBtn.style.display = '';
				cancelBtn.disabled      = false;
				cancelBtn.textContent   = cancelBtn.dataset.label || 'Cancel Batch';
			}

			setProgressVisible( true );

			statusText = ( ammAdmin.i18n.batchRunning || 'Processing %1$d of %2$d\u2026 (%3$d failed)' )
				.replace( '%1$d', String( completed ) )
				.replace( '%2$d', String( total ) )
				.replace( '%3$d', String( failed ) );

			if ( ! pollInterval ) {
				startPolling();
			}
		} else if ( 'completed' === status ) {
			const generated = completed - failed;

			statusText = ( ammAdmin.i18n.batchComplete || 'Complete: %1$d of %2$d generated (%3$d failed).' )
				.replace( '%1$d', String( generated ) )
				.replace( '%2$d', String( total ) )
				.replace( '%3$d', String( failed ) );

			clearPoll();
			startBtn.disabled = false;
			if ( cancelBtn ) {
				cancelBtn.style.display = 'none';
			}
		} else if ( 'cancelled' === status ) {
			statusText = ( ammAdmin.i18n.batchCancelled || 'Cancelled after processing %1$d of %2$d items.' )
				.replace( '%1$d', String( completed ) )
				.replace( '%2$d', String( total ) );

			clearPoll();
			startBtn.disabled = false;
			if ( cancelBtn ) {
				cancelBtn.style.display = 'none';
			}
		} else {
			// idle or unknown — ensure UI is in a resting state.
			clearPoll();
			startBtn.disabled = false;
			if ( cancelBtn ) {
				cancelBtn.style.display = 'none';
			}

			setProgressVisible( false );
		}

		if ( statusEl && statusText ) {
			statusEl.textContent = statusText;
		}
	}

	/**
	 * Polls the current batch progress via AJAX.
	 */
	function pollProgress() {
		const formData = new FormData();
		formData.append( 'action', 'amm_batch_progress' );
		formData.append( 'nonce', ammAdmin.nonce );

		fetch( ammAdmin.ajaxUrl, { method: 'POST', body: formData } )
			.then( ( r ) => r.json() )
			.then( ( data ) => {
				if ( data.success ) {
					updateProgress( data.data );
				}
			} )
			.catch( () => {} );
	}

	/**
	 * Starts the 3-second polling interval.
	 */
	function startPolling() {
		clearPoll();
		pollInterval = setInterval( pollProgress, 3000 );
	}

	// -----------------------------------------------------------------------
	// Sync type select → hidden input
	// -----------------------------------------------------------------------

	const batchTypeSelect = document.getElementById( 'amm-batch-type-select' );

	if ( batchTypeSelect && batchTypeInput ) {
		batchTypeSelect.addEventListener( 'change', () => {
			batchTypeInput.value = batchTypeSelect.value;
		} );
	}

	// -----------------------------------------------------------------------
	// Start batch button
	// -----------------------------------------------------------------------

	startBtn.addEventListener( 'click', () => {
		const type = batchTypeInput ? batchTypeInput.value : 'all';

		startBtn.disabled = true;
		if ( cancelBtn ) {
			cancelBtn.style.display = '';
		}

		setProgressVisible( true );

		if ( progressBar ) {
			progressBar.style.width = '0%';
		}

		if ( statusEl ) {
			statusEl.textContent = ammAdmin.i18n.batchStarting || 'Starting\u2026';
		}

		const formData = new FormData();
		formData.append( 'action', 'amm_start_batch' );
		formData.append( 'nonce', ammAdmin.nonce );
		formData.append( 'type', type );
		formData.append( 'force', '0' );

		fetch( ammAdmin.ajaxUrl, { method: 'POST', body: formData } )
			.then( ( r ) => r.json() )
			.then( ( data ) => {
				if ( data.success ) {
					updateProgress( data.data );
				} else {
					const msg = ( data.data && data.data.message )
						? data.data.message
						: ( ammAdmin.i18n.batchFailed || 'Failed to start batch.' );

					if ( statusEl ) {
						statusEl.textContent = msg;
					}

					startBtn.disabled = false;
					if ( cancelBtn ) {
						cancelBtn.style.display = 'none';
					}
				}
			} )
			.catch( () => {
				if ( statusEl ) {
					statusEl.textContent = ammAdmin.i18n.batchFailed || 'Failed to start batch.';
				}

				startBtn.disabled = false;
				if ( cancelBtn ) {
					cancelBtn.style.display = 'none';
				}
			} );
	} );

	// -----------------------------------------------------------------------
	// Cancel batch button
	// -----------------------------------------------------------------------

	if ( cancelBtn ) {
		cancelBtn.dataset.label = cancelBtn.textContent;

		cancelBtn.addEventListener( 'click', () => {
			cancelBtn.disabled    = true;
			cancelBtn.textContent = ammAdmin.i18n.batchCancelling || 'Cancelling\u2026';

			const formData = new FormData();
			formData.append( 'action', 'amm_cancel_batch' );
			formData.append( 'nonce', ammAdmin.nonce );

			fetch( ammAdmin.ajaxUrl, { method: 'POST', body: formData } )
				.then( ( r ) => r.json() )
				.then( ( data ) => {
					cancelBtn.disabled    = false;
					cancelBtn.textContent = cancelBtn.dataset.label || 'Cancel Batch';

					if ( data.success ) {
						updateProgress( data.data );
					}
				} )
				.catch( () => {
					cancelBtn.disabled    = false;
					cancelBtn.textContent = cancelBtn.dataset.label || 'Cancel Batch';
				} );
		} );
	}

	// -----------------------------------------------------------------------
	// Page-load check: resume UI if a batch is already running.
	// -----------------------------------------------------------------------

	pollProgress();
}
