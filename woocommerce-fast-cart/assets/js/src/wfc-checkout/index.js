document.addEventListener( 'DOMContentLoaded', () => {

	const settings = window.parent.FastCart.getSettings();

	const debugMode = settings.debug || false;

	const debug = ( ...params ) => {
		if ( ! debugMode ) {
			return;
		}
		if ( parseInt( debugMode ) === 2 ) {
			console.trace( ...params );
		} else {
			console.log( ...params );
		}
	};

	if ( document.body.classList.contains( 'wfc-checkout--receipt' ) ) {

		let redirectUrl = window.location.href;
		if ( wc_fast_cart_checkout_params && wc_fast_cart_checkout_params.receiptUrl ) {
			redirectUrl = wc_fast_cart_checkout_params.receiptUrl;
		}
		if ( settings && settings.options && settings.options.redirectReceipt ) {
			redirectUrl = settings.options.redirectReceipt;
		}
		redirectUrl = new URL( redirectUrl );
		redirectUrl.searchParams.delete( 'wfc-checkout' );
		window.parent.FastCart.completeCheckout( redirectUrl );

	} else {

		window.parent.FastCart.loadCheckout( jQuery( 'html' ).height() );

		jQuery(document).on( 'click', 'a', ( e ) => {
			const target = e.target.closest( 'a' );
			let href = target.href;
			if ( target.classList.contains('wc-block-components-checkout-return-to-cart-button') || window.parent.FastCart.showCartIfHrefMatches( e ) === true ) {
				window.parent.FastCart.setStatus( 'loading' );
				window.parent.FastCart.refresh( true );
				return;
			}
			if ( href.indexOf('#') === -1 ) {
				window.parent.FastCart.redirectTo( e.currentTarget.href );
				e.preventDefault();
				e.stopPropagation();
				return false;
			} else {
				setTimeout( () => {
					window.parent.FastCart.loadCheckout( jQuery( 'html' ).height() );
				}, 1000 );
			}
		} );

		jQuery( document.body ).on( 'click change init_checkout checkout_error update_checkout updated_checkout', () => {
			window.parent.FastCart.loadCheckout( jQuery( 'html' ).height() );
			setTimeout( () => {
				window.parent.FastCart.loadCheckout( jQuery( 'html' ).height() );
			}, 500 );
		} );

		jQuery( document.body ).on( 'checkout_error', () => {
			window.parent.FastCart.resetScroll();
		} );

		const checkoutForm = document.querySelector( 'form.woocommerce-checkout, form.wc-block-checkout__form' );
		if ( checkoutForm ) {
			const observer = new MutationObserver( () => {
				setTimeout( () => {
					window.parent.FastCart.loadCheckout( jQuery( 'html' ).height() );
				}, 100 );
			} );
			observer.observe( checkoutForm, { attributes:true, childList: true, subtree: true } );


			const usedWfc = checkoutForm.querySelector( 'input[name="_used_wfc"]' ) || document.createElement( 'input' );
			usedWfc.type = 'hidden';
			usedWfc.name = '_used_wfc';
			usedWfc.value = 'true';
			checkoutForm.append( usedWfc );
		}

		if ( wc_fast_cart_checkout_params && wc_fast_cart_checkout_params.autocomplete_api && typeof google === 'undefined' ) {
			let acScript = document.createElement( 'script' );
			acScript.type = 'text/javascript';
			acScript.src = `https://maps.googleapis.com/maps/api/js?key=${wc_fast_cart_checkout_params.autocomplete_api}&libraries=places&callback=wfcAutocompleteReady`;

			document.body.append( acScript );
		}

		let addressFields, acApiStatus = null;

		/*const resetACSession = ( autocomplete ) => {
			const sessionToken = new google.maps.places.AutocompleteSessionToken();
			autocomplete.sessionToken = sessionToken;
		};*/

		const installACField = ( addressInput ) => {

			const acSettings = settings.autocomplete ?? {
				fields: [ "address_components" ],
				strictBounds: false,
			};

			const addressParent       = addressInput.closest( '.woocommerce-billing-fields,.woocommerce-shipping-fields,.wc-block-components-address-form-wrapper' );
			const addressCountryInput = addressParent.querySelector( 'select[autocomplete="country"],select[name="billing_country"]' );

			if ( addressCountryInput && addressCountryInput.value !== '' ) {
				acSettings.componentRestrictions = { country: addressCountryInput.value.toLowerCase() };
			}

			debug( 'default settings:', acSettings );

			addressInput.setAttribute( 'autocomplete', 'off' );
			//input.addEventListener( 'keyup', checkAutoComplete );
			let autocomplete;
			try {
				autocomplete = new google.maps.places.Autocomplete( addressInput, acSettings );
			} catch( e ) {
				debug( 'autocomplete error:', e );
				addressInput.dataset.acInstalled = 'fail';
				return;
			}

			//resetACSession( autocomplete );
			addressInput.gmpACListener = autocomplete.addListener( 'place_changed', ( args ) => {
				debug( 'place changed', args );
				addressChanged( addressInput, autocomplete );
				//resetACSession( autocomplete );
			} );
			if ( ! addressInput.dataset.acInstalled ) {
				jQuery( document.body ).on( 'country_to_state_changing', ( event, country ) => {
					console.log( 'country changed', event, country );
					autocomplete.setComponentRestrictions( { country: country.toLowerCase() } );
				} );
				document.addEventListener( 'wc-fast-cart|block_country_changed', ( { detail } ) => {
					const { addressStore, country } = detail;
					if ( addressInput.closest( addressStore ) ) {
						autocomplete.setComponentRestrictions( { country: country.toLowerCase() } );
						window.FastCart.debug( 'updated autocomplete country', country, addressStore );
					}
				} );
			}
			addressInput.gmpAC = autocomplete;
			addressInput.dataset.acInstalled = 'yes';

			document.dispatchEvent( new CustomEvent( 'wc-fast-cart|autocomplete_installed', { detail: { addressInput, autocomplete } } ) );

		};

		document.addEventListener( 'focus', ( e ) => {

			if ( ! e.target.closest( 'input' ) ) {
				return;
			}
			if ( e.target.getAttribute( 'autocomplete' ) !== 'address-line1' ) {
				return;
			}
			if ( e.target.dataset.acInstalled === 'yes' || e.target.dataset.acInstalled === 'fail' ) {
				return;
			}

			installACField( e.target );

		} );

		window.gm_authFailure = ( err ) => {
			aciApiStatus = 'error';
			debug( 'gm_authFailure', err );

			document.querySelectorAll( '.gm-err-autocomplete' ).forEach( ( input ) => {

				if ( input.gmpACListener && google ) {
					google.maps.event.removeListener( input.gmpACListener );
					google.maps.event.clearInstanceListeners( input );
				}

				input.disabled = false;
				input.dataset.acInstalled = 'fail';
				input.autocomplete = 'street-address';
				input.placeholder = input.dataset.placeholder;
				input.style.backgroundImage = '';
				input.focus();
			} );
		};

		window.wfcAutocompleteReady = ( status ) => {

			if ( acApiStatus === 'error' ) {
				return;
			}

			addressFields = document.querySelectorAll( 'input[autocomplete="address-line1"]' );

			window.FastCart.debug( 'found address fields:', addressFields );
			if ( ! addressFields || addressFields.length === 0 ) {
				window.FastCart.debug( 'found address fields:', addressFields );
				window.FastCart.debug( 'no address fields found, trying again in 500ms' );
				setTimeout( () => {
					wfcAutocompleteReady( status );
				}, 500 );
				return;
			}

			for ( let input of addressFields ) {
				installACField( input );
			}
		};

		const addressChanged = ( input, autocomplete ) => {

			try {
				let place = autocomplete.getPlace();
				if ( ! place ) {
					return;
				}
				setPlaceDetails( place, input.closest( '.wc-block-components-address-form,.woocommerce-billing-fields,.woocommerce-shipping-fields' ) );
			} catch( e ) {
				debug( 'autocomplete error:', e );
				window.gm_authFailure();
			}

		}

		const setPlaceDetails = ( place, addressParent ) => {

			if ( addressParent.classList.contains('wc-block-components-address-form') ) {
				// this is the WC gutenberg block, fire an event handled by wfc-block-checkout.js
				document.dispatchEvent( new CustomEvent( 'wc-fast-cart|update_checkout_values', { detail: { place, addressParent } } ) );
				return;
			}

			let addressLine1        = addressParent.querySelector( '[autocomplete="address-line1"],[name="billing_address_1"],[name="shipping_address_1"]'),
				addressLine2        = addressParent.querySelector( '[autocomplete="address-line2"],[name="billing_address_2"],[name="shipping_address_2"]'),
				addressCityInput    = addressParent.querySelector( '[autocomplete="address-level2"],[name="billing_city"],[name="shipping_city"]'),
				addressStateInput   = addressParent.querySelector( '#wc-block-components-state-input input,[autocomplete="address-level1"],[name="billing_state"],[name="shipping_state"]'),
				addressZipInput     = addressParent.querySelector( '[autocomplete="postal-code"],[name="billing_postcode"],[name="shipping_postcode"]');
				//addressCountryInput = addressParent.querySelector( '#shipping-country input,#billing-country input,[autocomplete="country"],[name="billing_country"],[name="shipping_country"]');

			let address1 = '', postcode = '';

			for (const component of place.address_components) {
				const componentType = component.types[0];

				switch (componentType) {
					case "street_number":
						address1 = `${component.long_name} ${address1}`;
						break;

					case "route":
						address1 += component.short_name;
						break;

					case "postal_code":
						postcode = `${component.long_name}${postcode}`;
						break;

					case "postal_code_suffix":
						postcode = `${postcode}-${component.long_name}`;
						break;

					case "postal_town":
					case "locality":
						if ( ! addressCityInput ) {
							break;
						}
						addressCityInput.value = component.long_name;
						jQuery(addressCityInput).change();
						break;
					
					/*case "shipping_country":
					case "billing_country":
					case "country":
						if ( ! addressCountryInput || ! addressCountryInput.selectedOptions ) {
							break;
						}
						let reset = false;

						if ( addressCountryInput.selectedOptions.length === 0 || addressCountryInput.selectedOptions[0].value !== component.short_name ) {
							jQuery( document ).one( 'updated_checkout', () => {
								setPlaceDetails( place, addressParent );
							} );
							reset = true;
						}

						for ( let index in addressCountryInput.options ) {
							if ( addressCountryInput.options[index].value == component.short_name ) {
								addressCountryInput.selectedIndex = index;
								jQuery(addressCountryInput).trigger('change');
								break;
							}
						}

						if ( reset ) {
							return;
						}

						break;
					*/
					case "administrative_area_level_1": {
						if ( ! addressStateInput ) {
							break;
						}
						if ( addressStateInput.tagName === 'SELECT' ) {
							for ( let index in addressStateInput.options ) {
								if ( addressStateInput.options[index].value == component.short_name ) {
									addressStateInput.selectedIndex = index;
									jQuery(addressStateInput).change();
									break;
								}
							}
						} else {
							addressStateInput.value = component.long_name;
						}
						
						break;
					}
					//case "country":
					//	document.querySelector("#country").value = component.long_name;
					//	break;
				}
			}

			addressLine1.value = address1;
			addressZipInput.value = postcode;

			jQuery(addressLine1).change();
			jQuery(addressZipInput).change();

			if ( addressLine2 ) {
				addressLine2.focus();
			}
			
			
		};

	}

} );

