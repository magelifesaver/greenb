/**
 * External dependencies
 */
import { registerPlugin } from '@wordpress/plugins';

const render = () => {};

registerPlugin( 'szbd-shipping-message', {
	render,
	scope: 'woocommerce-checkout',
} );
