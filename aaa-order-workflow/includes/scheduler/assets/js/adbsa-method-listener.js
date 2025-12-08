/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/scheduler/assets/js/adbsa-method-listener.js
 * Purpose: Detect checkout delivery mode (Same-Day or Scheduled), broadcast adbsa:mode-change,
 *          fetch slot data via AJAX, and emit adbsa:slots-updated for UI refresh.
 * Version: 2.3.0
 */

(() => {
	const DEBUG_THIS_FILE = true;
	const log = (...a) => DEBUG_THIS_FILE && console.log('[ADBSA-Listener]', ...a);

	log('Script loaded (mode-switch + slot fetch)');

	const sameDayMethod   = window.adbsaSettings?.sameDayMethod   || '';
	const scheduledMethod = window.adbsaSettings?.scheduledMethod || '';

	const wcSelectCheckout = window.wp?.data?.select('wc/store/checkout');
	const wcSubscribe      = window.wp?.data?.subscribe;

	if (!wcSelectCheckout || !wcSubscribe) {
		log('wp.data not ready – retrying...');
		setTimeout(() => window.dispatchEvent(new Event('DOMContentLoaded')), 1000);
		return;
	}

	let lastRate = '';
	let lastMode = '';

	function detectMode(selected) {
		if (sameDayMethod && selected.includes(String(sameDayMethod))) return 'sameday';
		if (scheduledMethod && selected.includes(String(scheduledMethod))) return 'scheduled';
		return 'none';
	}

	function broadcast(mode, selected) {
		if (mode === lastMode) return; // avoid duplicate events
		lastMode = mode;
		log('→ Broadcasting mode change:', mode, '| method:', selected);

		document.dispatchEvent(
			new CustomEvent('adbsa:mode-change', { detail: { mode, method: selected } })
		);
		sessionStorage.setItem('adbsa_active_mode', mode);

		if (typeof window.ajaxurl !== 'undefined' && mode !== 'none') {
			fetch(`${window.ajaxurl}?action=adbsa_get_slots&mode=${mode}`, {
				credentials: 'same-origin',
			})
				.then((r) => r.json())
				.then((res) => {
					if (!res.success) return;
					const data = res.data || {};
					log('Received slot data for', data.mode, data);
					document.dispatchEvent(
						new CustomEvent('adbsa:slots-updated', { detail: data })
					);
				})
				.catch((err) => console.error('[ADBSA-Listener] Slot fetch error', err));
		}
	}

	// Poll selected rate every 500 ms to ensure reliable detection
	setInterval(() => {
		try {
			const selectedRate = wcSelectCheckout.getSelectedShippingRate();
			const rateId =
				(selectedRate && (selectedRate.rate_id || selectedRate.id || '')) || '';

			if (rateId && rateId !== lastRate) {
				lastRate = rateId;
				const mode = detectMode(rateId);
				broadcast(mode, rateId);
			}
		} catch (err) {
			log('Poll error', err);
		}
	}, 500);

	// Fire initial detection once checkout is stable
	setTimeout(() => {
		try {
			const selectedRate = wcSelectCheckout.getSelectedShippingRate();
			const rateId =
				(selectedRate && (selectedRate.rate_id || selectedRate.id || '')) || '';
			const mode = detectMode(rateId);
			if (mode !== 'none') {
				broadcast(mode, rateId);
				log('Initial detected mode:', mode, rateId);
			} else {
				log('Initial detected none');
			}
		} catch (e) {
			log('Initial detect error', e);
		}
	}, 1000);

	window.addEventListener('beforeunload', () => {
		if (typeof unsubscribe === 'function') unsubscribe();
	});
})();
