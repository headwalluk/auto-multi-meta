/**
 * Auto Multi-Meta — Admin JavaScript
 *
 * Provides hash-based tab navigation and checklist helpers for the
 * plugin settings page.
 *
 * @package Auto_Multi_Meta
 */

/* global ammAdmin */

document.addEventListener( 'DOMContentLoaded', () => {
	initTabs();
	initChecklistButtons();
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
