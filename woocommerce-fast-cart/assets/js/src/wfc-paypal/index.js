( function() {
	"use strict";

	let isAttached = false;

	let originalButtonsParent = null,
		paypalContainer,
		minicartButtons;

	const moveButtonsIntoCart = () => {

		if ( isAttached ) {
			return;
		}

		paypalContainer = document.querySelector( '.wfc-checkout-buttons__paypal' );
		if ( ! paypalContainer ) {
			return;
		}

		minicartButtons = document.querySelector( '#ppc-button-minicart' );
		if ( ! minicartButtons ) {
			return;
		}

		isAttached = true;

		if ( paypalContainer.children.length > 0 ) {
			return;
		}

		originalButtonsParent = minicartButtons.parentElement;

		paypalContainer.append( minicartButtons );

	};

	const moveButtonsOutofCart = () => {

		if ( ! originalButtonsParent  || ! minicartButtons ) {
			return;
		}

		originalButtonsParent.append( minicartButtons );

		isAttached = false;

	}

	document.addEventListener( 'wc-fast-cart|open', moveButtonsIntoCart );
	document.addEventListener( 'wc-fast-cart|after_refresh', moveButtonsIntoCart );

	document.addEventListener( 'wc-fast-cart|close', moveButtonsOutofCart );
	document.addEventListener( 'wc-fast-cart|before_refresh', moveButtonsOutofCart );

} )();