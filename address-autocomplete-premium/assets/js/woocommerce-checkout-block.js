(function() {

	// Function to update an address in the WC data store
	function wps_aa_woocommerce_update_address( address, type = 'shipping' ) {

		// Ensure WooCommerce Blocks Data is available
		if (window.wc && window.wc.wcBlocksData) {
			const { CART_STORE_KEY } = window.wc.wcBlocksData;
			const { dispatch } = wp.data;
			delete address.first_name;
			delete address.last_name;

			if (type === 'shipping') {
				dispatch(CART_STORE_KEY).setShippingAddress(address);
				dispatch(CART_STORE_KEY).updateCustomerData({ shipping_address: address });
				// if billing same as shipping option checked, update billing address
				if (document.querySelector('.wc-block-checkout__use-address-for-billing input[type="checkbox"]').checked) {
					dispatch(CART_STORE_KEY).setBillingAddress(address);
					dispatch(CART_STORE_KEY).updateCustomerData({ billing_address: address });
				}
			} else if (type === 'billing') {
				dispatch(CART_STORE_KEY).setBillingAddress(address);
				dispatch(CART_STORE_KEY).updateCustomerData({ billing_address: address });
			}
		}
	}

	// Listening for the wps_aa event which has the replacement data to then update the WC data store
	document.addEventListener('wps_aa', function(event) {

		let selector_mapping = {};
		let type = '';

		if (event.detail && event.detail.init) {
			if (event.detail.init === '#shipping-address_1') {
				selector_mapping = {
					'#shipping-country': 'country',
					'#shipping-address_1': 'address_1',
					'#shipping-address_2': 'address_2',
					'#shipping-city': 'city',
					'#shipping-state': 'state',
					'#shipping-postcode': 'postcode'
				};
				type = 'shipping';
			} else if (event.detail.init === '#billing-address_1') {
				selector_mapping = {
					'#billing-country': 'country',
					'#billing-address_1': 'address_1',
					'#billing-address_2': 'address_2',
					'#billing-city': 'city',
					'#billing-state': 'state',
					'#billing-postcode': 'postcode'
				};
				type = 'billing';
			}
		}

		if (!selector_mapping) return;

		let final_address = {
			first_name: '',
			last_name: '',
			company: '',
			address_1: '',
			address_2: '',
			city: '',
			state: '',
			postcode: '',
			country: ''
		};

		event.detail.data.forEach(item => {
			const key = selector_mapping[item.selector];
			if (key) final_address[key] = item.result;
		});

		wps_aa_woocommerce_update_address(final_address, type);

	});

	// Use MutationObserver to detect when the checkout block loads
	function wps_aa_observe_checkout_block(fieldClass) {
		const targetNode = document.body;
		const config = { childList: true, subtree: true };

		const observer = new MutationObserver((mutationsList, observer) => {
			for (let mutation of mutationsList) {
				if (mutation.type === 'childList') {
					if (document.querySelector(fieldClass)) {
						wps_aa(); // Run Address Autocomplete.
						observer.disconnect();
						break;
					}
				}
			}
		});

		observer.observe(targetNode, config);
	}

	// Initialize observers for both shipping and billing fields
	document.addEventListener('DOMContentLoaded', function() {
		wps_aa_observe_checkout_block('.wc-block-checkout__shipping-fields');
		wps_aa_observe_checkout_block('.wc-block-checkout__billing-fields');
	});

	// Trigger wps_aa() when various buttons within the WC checkout block are clicked, with a 0.5s delay
	document.addEventListener('click', function(event) {
		if (
			event.target.closest('#shipping-method button') || 
			event.target.closest('.wc-block-components-address-card__edit') || 
			event.target.closest('.wc-block-checkout__shipping-method-container div') || 
			event.target.closest('.wc-block-checkout__use-address-for-billing input[type="checkbox"]')
		) {
				setTimeout(wps_aa, 500);
		}
	});

})();
