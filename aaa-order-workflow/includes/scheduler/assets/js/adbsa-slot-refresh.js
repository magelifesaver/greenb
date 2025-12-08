/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/scheduler/assets/js/adbsa-slot-refresh.js
 * Purpose: Listen for adbsa:slots-updated and rebuild date/time <select> options.
 * Version: 1.1.0
 */

(() => {
	const DEBUG_THIS_FILE = true;
	const log = (...a) => DEBUG_THIS_FILE && console.log('[ADBSA-Refresh]', ...a);

	/**
	 * Replace all <option> tags inside a <select>.
	 */
	function replaceOptions(select, list, placeholder) {
		if (!select) return;
		select.innerHTML = '';

		if (!Array.isArray(list) || !list.length) {
			const empty = document.createElement('option');
			empty.value = '';
			empty.textContent = placeholder;
			empty.disabled = true;
			select.appendChild(empty);
			return;
		}

		list.forEach(opt => {
			const o = document.createElement('option');
			o.value = opt.value ?? '';
			o.textContent = opt.label ?? opt.value ?? '';
			select.appendChild(o);
		});
	}

	/**
	 * When new slots arrive (from AJAX in adbsa-method-listener.js),
	 * rebuild the <select> menus shown on checkout.
	 */
	document.addEventListener('adbsa:slots-updated', (e) => {
		try {
			const data = e.detail || {};
			if (!data.mode) return;
			log(`Rebuilding slot lists for mode: ${data.mode}`);

			const dateSel = document.querySelector('#order-adbsa-delivery-date');
			const timeSel = document.querySelector('#order-adbsa-delivery-time');

			// Dates (Scheduled = multiple; Same-Day = one)
			if (dateSel) {
				replaceOptions(dateSel, data.dates || [], 'Select a date');
			}

			// Times
			if (timeSel) {
				replaceOptions(timeSel, data.times || [], 'Select a time');
			}

			log('Updated selects', {
				dateCount: data.dates?.length || 0,
				timeCount: data.times?.length || 0,
			});
		} catch (err) {
			console.error('[ADBSA-Refresh] Failed to rebuild slot lists:', err);
		}
	});
})();
