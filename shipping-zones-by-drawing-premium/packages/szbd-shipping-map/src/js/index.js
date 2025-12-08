/**
 * External dependencies
 */
import { registerPlugin } from '@wordpress/plugins';

const render = () => {};

registerPlugin( 'szbd-shipping-map', {
	render,
	scope: 'woocommerce-checkout',
} );



