/**
 * Listen to value changes into the setup wizard
 * and toggle steps when needed.
 */
 window.addEventListener('barn2_setup_wizard_changed', (dispatchedEvent) => {

    const cartEnabled     = dispatchedEvent.detail.fast_cart_display
			? dispatchedEvent.detail.fast_cart_display !== 'checkout'
			: true;

	const checkoutEnabled = dispatchedEvent.detail.fast_cart_display 
		? dispatchedEvent.detail.fast_cart_display !== 'cart'
		: true;

    const showStep = dispatchedEvent.detail.showStep
    const hideStep = dispatchedEvent.detail.hideStep

    if ( cartEnabled === false ) {
		hideStep( 'cart' );
	} else {
		showStep( 'cart' );
	}
	if ( cartEnabled === false && checkoutEnabled === false ) {
		hideStep( 'pages' );
	} else {
		showStep( 'pages' );
	}

}, false);