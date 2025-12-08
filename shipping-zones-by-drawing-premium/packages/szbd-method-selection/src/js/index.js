/**
 * External dependencies
 */
import { registerPlugin } from '@wordpress/plugins';

const render = () => {};

registerPlugin( 'szbd-method-selection', {
	render,
	scope: 'woocommerce-checkout',
} );
