/**
 * File: assets/js/adbsa-checkout.js
 * Purpose: Enable Same-Day delivery fields in Woo Blocks checkout with verbose logging
 * Version: 1.3.0
 */
(function() {
	const DEBUG_THIS_FILE = true;
	const log = (...args) => { if (DEBUG_THIS_FILE) console.debug('[ADBSA-Checkout]', ...args); };

	const { dispatch } = window.wp.data || {};
	if (!dispatch) {
		console.error('[ADBSA-Checkout] wp.data not available');
		return;
	}

	function todayYmdLocal() {
		const d = new Date();
		const pad = (n) => String(n).padStart(2,'0');
		return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;
	}

	const fields = [
		{ id: 'adbsa/delivery-date', required:true },
		{ id: 'adbsa/delivery-time', required:true },
	];

	function syncField(fieldId, value) {
		try {
			dispatch('wc/store/checkout').__internalSetExtensionData({
				namespace: fieldId, 
        data: value,
			});
			log('Synced to store:', { fieldId, value });
		} catch(e) {
			console.error('[ADBSA-Checkout] Failed to sync', fieldId, e);
		}
	}

	function initFields() {
		fields.forEach(f => {
			if (f.id === 'adbsa/delivery-date') {
				const todayVal = todayYmdLocal();
				syncField(f.id, todayVal);

				const el = document.querySelector(`#order-${f.id.replace('/','-')}`);
				if (el) {
					for (const opt of el.options) {
						if (opt.value === todayVal) {
							el.value = todayVal;
							el.dispatchEvent(new Event('change',{bubbles:true}));
							log('Forced delivery-date element to Today', todayVal);
						}
					}
				}
				return;
			}

			const el = document.querySelector(`#order-${f.id.replace('/','-')}`);
			if (el) {
				if (el.value) {
					syncField(f.id, el.value);
					log('Initial sync', { field: f.id, value: el.value });
				}
				el.addEventListener('change', e => {
					syncField(f.id, e.target.value);
					log('Change event sync', { field: f.id, value: e.target.value });
				});
				log('Attached listener for', f.id);
			} else {
				log('Field not found for', f.id);
			}
		});
	}

	document.addEventListener('DOMContentLoaded', () => {
		log('InitFields start');
		initFields();
	});
})();
