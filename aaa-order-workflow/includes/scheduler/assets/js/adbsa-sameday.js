/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/scheduler/assets/js/adbsa-sameday.js
 * Purpose: Handle Same-Day delivery UI behavior on checkout.
 * Version: 1.0.0
 */

(() => {
	const DEBUG_THIS_FILE = true;
	const log = (...args) => {
		if (DEBUG_THIS_FILE) console.debug('[ADBSA-SameDay]', ...args);
	};

	/**
	 * Toggle Same-Day delivery fields based on active mode.
	 * When mode='sameday' → show/enable fields.
	 * Otherwise → hide/disable.
	 */
	function setSameDayVisibility(mode) {
		const dateField = document.querySelector('.wc-block-components-select-input-adbsa-delivery-date');
		const timeField = document.querySelector('.wc-block-components-select-input-adbsa-delivery-time');
		if (!dateField || !timeField) {
			log('Same-Day fields not found yet');
			return;
		}

		const visible = mode === 'sameday';
		dateField.closest('.wc-block-components-checkout-step__content').style.display = visible ? '' : 'none';
		timeField.closest('.wc-block-components-checkout-step__content').style.display = visible ? '' : 'none';

		log(visible ? 'Showing Same-Day fields' : 'Hiding Same-Day fields');
	}

	/**
	 * Reinitialize Same-Day field data if needed (future AJAX refresh placeholder)
	 */
	function refreshSameDayData() {
		log('Refreshing Same-Day slot data (placeholder)');
		// Later: add AJAX or Store API refresh here
	}

	/**
	 * Initialize the Same-Day mode listener.
	 */
	function initSameDayListener() {
		document.addEventListener('adbsa:mode-change', (e) => {
			const { mode, method } = e.detail;
			log('Received mode change event:', mode, method);
			setSameDayVisibility(mode);
			if (mode === 'sameday') refreshSameDayData();
		});

		// Fire once on page load (based on sessionStorage)
		const initMode = sessionStorage.getItem('adbsa_active_mode');
		if (initMode) {
			log('Initial mode on page load:', initMode);
			setSameDayVisibility(initMode);
		}
	}

	document.addEventListener('DOMContentLoaded', initSameDayListener);
})();
