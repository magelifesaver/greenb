/**
 * External dependencies
 */
import { registerPlugin } from '@wordpress/plugins';

const render = () => {};

registerPlugin( "check-my-address", {
	render,
	scope: 'woocommerce-checkout',
} );




