/**
 * External dependencies
 */


import { extensionCartUpdate } from '@woocommerce/blocks-checkout';
import { useEffect, useState, useCallback } from '@wordpress/element';

import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import { debounce } from 'lodash';
import  _ from 'lodash';

import { sprintf } from '@wordpress/i18n';

import { createInterpolateElement, renderToString } from '@wordpress/element';

import {
	ValidatedTextInput,
	Spinner,
	TextInput,
} from '@woocommerce/blocks-checkout';

import { useEffectOnce, useUpdateEffect } from 'usehooks-ts';
import { useIsMounted } from 'usehooks-ts';

import { getSetting } from '@woocommerce/settings';



export const Block = ({ checkoutExtensionData, extensions }) => {

	const { cartStore } = window.wc.wcBlocksData;

	const { checkoutStore } = window.wc.wcBlocksData;

	

	// Get data analog 'localized'
	const { szbd_precise_address_mandatory, szbd_precise_address, szbd_precise_address_plus_code } = getSetting('szbd-shipping-map_data', '');

	const { selectShippingRate, setIsCartDataStale, setCartData,setBillingAddress,setShippingAddress,shippingRatesBeingSelected } =
		useDispatch( cartStore );




	const { __internalIncrementCalculating, __internalDecrementCalculating } =
		useDispatch('wc/store/checkout');

	const debouncedSetExtensionData = useCallback(
		debounce((namespace, data,dirty) => {
			extensionCartUpdate(namespace, data,dirty);
		}, 1000),
		[extensionCartUpdate]
	);

	const { setExtensionData } = checkoutExtensionData;

	const [latlng, setLatLng] = useState({});

	const [pluscode, setPluscode] = useState('');

	const [debug, setDebug] = useState('');
	const validationErrorId = 'szbd-no-picked-location';
	const { setValidationErrors, clearValidationError,showValidationError } = useDispatch(
		'wc/store/validation'
	);
	//const {setIsCartDataStale, selectShippingRate,updatingCustomerData,applyExtensionCartUpdate, shippingRatesBeingSelected} = useDispatch( CART_STORE_KEY );

	const validationError = useSelect((select) => {
		const store = select('wc/store/validation');

		return store.getValidationError(validationErrorId);


	});
	

	const hasCalculatedShipping = useSelect((select) => {
		let store = select(cartStore);


		return store.getHasCalculatedShipping();
	});

	const customerdata = useSelect((select) => {
		let store = select(cartStore);


		return store.getCustomerData();
	});

	const getNeedsShipping = useSelect((select) => {
		let store = select(cartStore);


		return store.getNeedsShipping();
	});
	
	const prefersCollection = useSelect( ( select ) => {
		let store = select( checkoutStore  );

		return store.prefersCollection();
	} );

	const isCalculating = useSelect( ( select ) => {
		let store = select(checkoutStore  );

		return store.isCalculating();
	} );

	

	
	

	const onChangePluscode = (val) => {
		
		if (val == 'updateserver') {
			setPluscode('');
			extensionCartUpdate({
				namespace: 'szbd-shipping-map-update',
				data: {
					lat: null,
					lng: null

				},
			});
		}
		else if (val == 'empty') {
			setPluscode('');

			return;
		} else {
			setPluscode(val);
		}


		
	};
	
	function isJsonString(str) {
		try {
			JSON.parse(str);
		} catch (e) {
			return false;
		}
		return true;
	}



		
		
	

	const latlng2 = useSelect((select) => {
		const store = select(cartStore);

		
		return store.getCartData();
	});



	const isMounted = useIsMounted();




	



	// Set extensiondata when point updates
	useEffect(() => {
		
		setExtensionData('szbd', 'pluscode', pluscode);


	}, [setExtensionData, pluscode]);




	useEffect(() => {

		const handleUpdate = () => {
			



			
			const loc = !_.isUndefined(latlng2) && !_.isNull(latlng2) && !_.isUndefined(latlng2.extensions) && !_.isUndefined((latlng2.extensions)['szbd-shipping-map']) ? (latlng2.extensions)['szbd-shipping-map'].shipping_point : null;
			// Return if location data has it´s origin from user interaction
			if (!_.isNull(loc) && _.has(loc, 'fromUI')) {

				return;
			}
			let has_address = has_full_address(latlng2);

			// Trigger event when a new point comes from the server
			jQuery(document).trigger('szbd_map_update_blocks', [latlng2, has_address, loc]);

		};
		// Perform actions after the document has loaded
		const handleLoad = () => {

	
			if (isMounted()) {
				
				// Reset point
				setBillingAddress({'szbd/shipping_point':''});
				setShippingAddress({'szbd/shipping_point':''});

				let has_address = has_full_address(latlng2);
				// Trigger event to update map on page load
				
				jQuery(document).trigger('szbd_map_loaded_blocks', [
					latlng2, has_address
				]);
			}
			//maybe_set_validation_error();
		};

		window.addEventListener('load', handleLoad);
		window.addEventListener('szbd_map_loaded', handleLoad);
		handleUpdate();

		return () => {
			window.removeEventListener('load', handleLoad);
			window.removeEventListener('szbd_map_loaded', handleLoad);
		};
	}, [latlng2, isMounted]);





	useEffect( () => {
		const point = customerdata.shippingAddress['szbd/shipping_point'];


		



		
			if (!isJsonString(point)) {
				return;
			}
			let location = JSON.parse(point);
			setLatLng(location);
			//shippingRatesBeingSelected( true );
		
	}, [
		customerdata,
		
	] );

	// Primitive check if address if filled
	function has_full_address(data) {

		return data.shippingAddress.address_1 == '' ? false : true;

	}

	
  

	useEffectOnce(() => {
		

		if (getNeedsShipping) {

			const event = new Event("szbd_map_loaded");


			window.dispatchEvent(event);
			
		}



	}, [getNeedsShipping]);

	

	function map_hidden() {

		if (szbd_precise_address == 'always') {
			return false;
		}
		
		const el = document.getElementById('szbd_checkout_field');
		const style = window.getComputedStyle(el);
		const mapHidden = (style.display === 'none');
		
		if (mapHidden) {
			return true;
		} else {
			return false;
		}


	}
	

	if (true) {

		const map_title = szbd_precise_address_mandatory == 'yes' ? __('Please Precise Your Shipping Location', 'szbd') : __('Precise Address?', 'szbd');

		// Return the block
		return (
			<>
			<div className='szbd-shipping-details-block'>


				
				
				<div id="szbd_checkout_field" className={(szbd_precise_address == 'at_fail'
					? ' at_fail'
					: '')}>

					{szbd_precise_address_plus_code == 'yes' ? (

						<div className={'szbd-pluscode'}>
							<TextInput
								id="szbd-plus-code"
								type="text"
								required={false}
								className={'szbd-plus-code-form'}
								label={__(
									'Find Location with Google Plus Code',
									'szbd'
								)}
								value={pluscode}
								onChange={(e) => {
									
									onChangePluscode(e);
								}}
							/>
						</div>
					) : ''}

					{validationError?.hidden ? null : (
					<div className="wc-block-components-validation-error" role="alert">
						<p>
							{validationError?.message}
						</p>

					</div>
				)}

					<div className='wc-block-components-title szbd-map-title-block'>
						{
							map_title
						}
					</div>
					<div id="szbd-pick-content">
						<div className={'szbd-checkout-map'}>
							<div id={'szbd_map'}></div>
						</div>
					</div>
				</div>

			</div>
			</>	);
		}


};