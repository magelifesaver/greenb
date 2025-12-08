/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/scheduler/assets/js/adbsa-scheduled.js
 * Purpose: Handle Scheduled delivery UI behavior on checkout.
 * Version: 1.0.0
 */

(() => {
	const DEBUG_THIS_FILE = true;
	const log = (...args) => {
		if (DEBUG_THIS_FILE) console.debug('[ADBSA-Scheduled]', ...args);
	};

	/**
	 * Toggle Scheduled delivery fields based on active mode.
	 * When mode='scheduled' → show/enable fields.
	 * Otherwise → hide/disable.
	 */
	function setScheduledVisibility(mode) {
		const dateField = document.querySelector('.wc-block-components-select-input-adbsa-delivery-date');
		const timeField = document.querySelector('.wc-block-components-select-input-adbsa-delivery-time');
		if (!dateField || !timeField) {
			log('Scheduled fields not found yet');
			return;
		}

		const visible = mode === 'scheduled';
		dateField.closest('.wc-block-components-checkout-step__content').style.display = visible ? '' : 'none';
		timeField.closest('.wc-block-components-checkout-step__content').style.display = visible ? '' : 'none';

		log(visible ? 'Showing Scheduled fields' : 'Hiding Scheduled fields');
	}

	/**
	 * Reinitialize Scheduled field data (placeholder for static slot logic)
	 */
	function refreshScheduledData() {
		log('Refreshing Scheduled slot data (placeholder)');
		// Later: add logic to rebuild or validate static slot options if needed
	}

	/**
	 * Initialize Scheduled mode listener.
	 */
	function initScheduledListener() {
		document.addEventListener('adbsa:mode-change', (e) => {
			const { mode, method } = e.detail;
			log('Received mode change event:', mode, method);
			setScheduledVisibility(mode);
			if (mode === 'scheduled') refreshScheduledData();
		});

		// Fire once on page load (based on sessionStorage)
		const initMode = sessionStorage.getItem('adbsa_active_mode');
		if (initMode) {
			log('Initial mode on page load:', initMode);
			setScheduledVisibility(initMode);
		}
	}

	document.addEventListener('DOMContentLoaded', initScheduledListener);
})();
