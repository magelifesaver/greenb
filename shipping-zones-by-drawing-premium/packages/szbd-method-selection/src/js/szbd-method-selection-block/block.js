/**
 * External dependencies
 */

//import { CART_STORE_KEY } from '@woocommerce/block-data';
//import { CHECKOUT_STORE_KEY } from '@woocommerce/block-data';
import { extensionCartUpdate } from '@woocommerce/blocks-checkout';
import { useEffect, useState, useCallback } from '@wordpress/element';
import { find,isUndefined,debounce} from "lodash";
import { getBlockType } from '@wordpress/blocks';

import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import { getSetting } from '@woocommerce/settings';



export const Block = ( { checkoutExtensionData, extensions } ) => {

	const { cartStore } = window.wc.wcBlocksData;

	const { checkoutStore } = window.wc.wcBlocksData;

	const { map_feature_active } = getSetting('szbd-method-selection_data', '');
	
	const _ = require('lodash');
	const { setExtensionData } = checkoutExtensionData;

	

	const validationErrorId = 'szbd-no-picked-location';

	const { setValidationErrors, clearValidationError, showValidationError } =
		useDispatch( 'wc/store/validation' );

	const validationError = useSelect( ( select ) => {
		const store = select( 'wc/store/validation' );

		return store.getValidationError( validationErrorId );
	} );

	const prefersCollection = useSelect( ( select ) => {
		let store = select( checkoutStore  );

		return store.prefersCollection();
	} );

	const isCalculating= useSelect( ( select ) => {
		let store = select( checkoutStore  );

		return store.isCalculating();
	} );
	const isBeforeProcessing  = useSelect( ( select ) => {
		let store = select( checkoutStore  );

		return store.isBeforeProcessing();
	} );

	const getShippingRates = useSelect( ( select ) => {
		let store = select( cartStore );

		return store.getShippingRates();
	} );

	const isShippingRateBeingSelected = useSelect( ( select ) => {
		let store = select( cartStore);

		return store.isShippingRateBeingSelected();
	} );

	const { selectShippingRate, setIsCartDataStale, setCartData,setBillingAddress,setShippingAddress,shippingRatesBeingSelected } =
		useDispatch( cartStore );


		const debouncedSetShippingRate = useCallback(
			debounce((rate,package1) => {
				selectShippingRate(rate,package1);
			}, 1000),
			[selectShippingRate]
		);

	useEffect( () => {
		
		
		
		//When shipping rates changes, check if it is not collection and then select new method
		if (    !isShippingRateBeingSelected ) {
			if( prefersCollection  || _.isUndefined(getShippingRates[ 0 ])){
				
				return;
			}
			const localPickupIsSelected = _.find(
				getShippingRates[ 0 ].shipping_rates,
				{ method_id: 'pickup_location', selected: true }
			);

			

			
			const legal_method = _.find(
				getShippingRates[ 0 ].shipping_rates,
				function ( rate ) {
					return rate.method_id != 'pickup_location';
				}
			);

			
		
			if (
			! _.isUndefined( localPickupIsSelected )   &&
				! _.isUndefined( legal_method )
			) {
				
				// If mode is shipping and there are valid shipping rates -> select first one
				debouncedSetShippingRate(
					legal_method.rate_id,

					getShippingRates[ 0 ].package_id

				);
			}
		}
	}, [  isShippingRateBeingSelected, getShippingRates] );
	

	useEffect( () => {
		
		
		// Clear shipping data when it is pickup location mode
		if ( prefersCollection ) {
			try{

			if(map_feature_active ){
				
				
				
				setBillingAddress({'szbd/shipping_point':''});
				setShippingAddress({'szbd/shipping_point':''});
				setExtensionData( 'szbd', 'pluscode', '' );
				
			}
			
		}catch(e){}
			if ( validationError ) {
				//clearValidationError( validationErrorId );
			}
		}else if(prefersCollection == false){
			//setShippingAddress({'szbd/shipping_point':''});
		
			
			

		}
	}, [
		prefersCollection,
		
	] );

	return (<></>);
};

