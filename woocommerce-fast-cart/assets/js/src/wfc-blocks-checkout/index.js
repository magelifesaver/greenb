import { CART_STORE_KEY } from '@woocommerce/block-data';
import { dispatch, select, subscribe } from '@wordpress/data';

function buildAddressComponent( indexedAddressData, types, index = 'long' ) {

	const newComponent = [];

	for ( let key of types ) {
		if ( indexedAddressData[ key ] ) {
			newComponent.push( indexedAddressData[ key ][ index ] );
		}
	}

	return newComponent.length > 0 ? newComponent.join(' ') : false;

}

function updateCheckoutValues( place, addressStore ) {

	//const { dispatch } = useDispatch( CART_STORE_KEY );
	const dataStore = select( CART_STORE_KEY );

	const customerData     = dataStore.getCustomerData();
	
	const indexedPlaceData = {};
	place.address_components.forEach( ( component ) => {
		component.types.forEach( ( type ) => {
			indexedPlaceData[ type ] = { long: component.long_name, short: component.short_name };
		} );
	} );

	window.FastCart.debug( 'gplaces_data_retrieved', indexedPlaceData );

	const newCustomerData = {};
	newCustomerData[ addressStore ] = customerData[ addressStore ];
	newCustomerData[ addressStore ].address_1 = buildAddressComponent( indexedPlaceData, [ 'street_number', 'route' ] ) || '';
	newCustomerData[ addressStore ].city = buildAddressComponent( indexedPlaceData, [ 'locality', 'postal_town' ] ) || customerData[ addressStore ].city;
	newCustomerData[ addressStore ].state = buildAddressComponent( indexedPlaceData, [ 'administrative_area_level_1' ], 'short' ) || customerData[ addressStore ].state;
	newCustomerData[ addressStore ].postcode = buildAddressComponent( indexedPlaceData, [ 'postal_code' ] ) || customerData[ addressStore ].postcode;
	if ( indexedPlaceData.postal_code_suffix ) {
		newCustomerData[ addressStore ].postcode += '-' + indexedPlaceData.postal_code_suffix.long;
	}

	indexedPlaceData.street_number;

	window.FastCart.debug( 'new_customer_data', newCustomerData );

	dispatch( CART_STORE_KEY ).updateCustomerData( newCustomerData );
}

( function() {

	// listen for changes to the cart datastore so we can update autocomplete restrictions

	const dataStore    = select( CART_STORE_KEY );
	const customerData = dataStore.getCustomerData();

	let billingCountry = customerData.billingAddress.country;
	let shippingCountry = customerData.shippingAddress.country;

	subscribe( ( action ) => {
		//window.FastCart.debug( 'update_customer_data', action );

		const newCustomerData = dataStore.getCustomerData();
		if ( newCustomerData.billingAddress.country !== billingCountry ) {
			billingCountry = newCustomerData.billingAddress.country;
			window.FastCart.debug( 'billing_country_changed', billingCountry );
			document.dispatchEvent( new CustomEvent( 'wc-fast-cart|block_country_changed', { detail: { addressStore: '#billing-fields', country: billingCountry } } ) );
		}
		if ( newCustomerData.shippingAddress.country !== shippingCountry ) {
			shippingCountry = newCustomerData.shippingAddress.country;
			window.FastCart.debug( 'shipping_country_changed', shippingCountry );
			document.dispatchEvent( new CustomEvent( 'wc-fast-cart|block_country_changed', { detail: { addressStore: '#shipping-fields', country: shippingCountry } } ) );
		}

	}, CART_STORE_KEY );

} )();

document.addEventListener( 'change', ( event ) => {

	const target = event.target;
	if ( ! target.closest( '#billing-country', '#shipping-country' ) ) {
		return;
	}

	window.FastCart.debug( 'country_changed', target );

} );

document.addEventListener( 'wc-fast-cart|autocomplete_installed', ( event ) => {

	const dataStore    = select( CART_STORE_KEY );
	const customerData = dataStore.getCustomerData();

	const billingCountry = customerData.billingAddress.country;
	const shippingCountry = customerData.shippingAddress.country;

	document.dispatchEvent( new CustomEvent( 'wc-fast-cart|block_country_changed', { detail: { addressStore: '#billing-fields', country: billingCountry } } ) );
	document.dispatchEvent( new CustomEvent( 'wc-fast-cart|block_country_changed', { detail: { addressStore: '#shipping-fields', country: shippingCountry } } ) );

} );

document.addEventListener( 'wc-fast-cart|update_checkout_values', ( event ) => {

	const { place, addressParent } = event.detail;

	window.FastCart.debug( 'update_checkout_values', place, addressParent );

	updateCheckoutValues( place, addressParent.id.includes( 'billing' ) ? 'billingAddress' : 'shippingAddress' );

} );