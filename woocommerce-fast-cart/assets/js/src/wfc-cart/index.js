( function( $, window, document, params, undefined ) {
    'use strict';

    if ( ! params ) {
        return false;
    }

    var isIE = !!window.MSInputMethodContext && !!document.documentMode;
    if ( isIE ) {
        return;
    }

    const $body = jQuery( document.body );
    const isCartPage = $body.hasClass( 'woocommerce-cart' );

    const FastCart = ( function( debugOn ) {

        const debugMode = params.debug || debugOn;

        let cartUrl, cartRequest, qtyRequest;
        let $wfcart, cartDOM, cartDOMContents, cartOverlay, cartBackground, cartBtn;

        let shouldReopenCart = false,
            isAddingToCart = false;

        function initialize() {

            if ( $body.hasClass( 'woocommerce-checkout' ) ) {
                // don't install Fast Cart on the WC Cart or Checkout page
                return;
            }

            cartUrl = new URL( maybeAddTrailingSlash( params.endpoints.cart ) );
            cartUrl.searchParams.set( 'wfc-cart', 'true' );

            cartRequest = new XMLHttpRequest();
            cartRequest.responseType = 'document';
            cartRequest.withCredentials = true;
            cartRequest.addEventListener( 'load', completeCartRefresh );
            cartRequest.open( 'GET', cartUrl );
            cartRequest.send();
            setStatus( 'loading' );

            qtyRequest = new XMLHttpRequest();
            qtyRequest.responseType = 'document';
            qtyRequest.withCredentials = true;
            qtyRequest.addEventListener( 'load', finishUpdatingQty );

            cartBtn = document.getElementById( 'wfc-open-cart-button' );

            if ( params.options.autoOpen ) {
                unbindWoocommerceEvents();
            }

            setupDefaultParams();
            installCartDOM();
            bindGlobalEvents();
            bindCartEvents();

            maybeOpenPreviewCheckout();

        }

        const unbindWoocommerceEvents = () => {

            $( document.body )
                .off( 'click', '.add_to_cart_button' )
                .off( 'ajax_request_not_sent.adding_to_cart' );

        };

        const bindGlobalEvents = () => {

            $body.on( 'click', '.wc-fast-cart__page-overlay', ( e ) => {
                if ( e.target && ! e.target.classList.contains( 'wc-fast-cart__page-overlay' ) ) {
                    return;
                }
                hide(e);
            } );

            if ( ! isCartPage ) {
                $body.on( 'click', params.selectors.cartBtn, addItem );

                if ( params.options.replaceCart ) {
                    debug( 'replace cart' );
                    $body.on( 'click', params.selectors.cartOpen, maybeShowCart );
                    $body.on( 'click', 'a', showCartIfHrefMatches );
                }

                $body.on( 'click', 'a.wfc-open-cart-button', show );
                $body.on( 'click', 'a[data-open-wfc]', show );

            }

            $body.on( 'variation_ajax_cart', maybeOpenCart );
            $body.on( 'wc-blocks_added_to_cart', itemAddedFromBlock );
            $body.on( 'wc-blocks_removed_from_cart', itemRemovedExternally );
            $body.on( 'added_to_cart', itemAddedExternally );
            $body.on( 'removed_from_cart', itemRemovedExternally );


            $body.on( 'wc-fast-cart|item-added', maybeOpenCart );
            $body.on( 'quick_view_pro:closed', maybeReopenCart );
            $body.on( 'keydown', maybeCloseCartOnEscape );

            if ( params.options.replaceCheckout ) {
                debug( 'replace checkout' );
                $body.on( 'click', params.selectors.checkoutOpen, openCheckout );
            }


        };

        const bindCartEvents = () => {
            $wfcart
                .on( 'click', (e) => {
                    if ( e.target.closest( '[data-action="quick-view"]' ) ) {
                        //allow pass-through to existing event listeners
                        debug( 'qvp short circuit' );
                        hide();
                        flagForReopen();
                        return;
                    }
                    if ( params.selectors.allowClickEventsOn && e.target.closest( params.selectors.allowClickEventsOn ) ) {
                        return;
                    }
                    e.stopPropagation();
                    debug( 'stop it right there' );
                } )
                .on( 'click', params.selectors.qvpBtn, flagForReopen )
                .on( 'click', params.selectors.cartBtn, addItem )
                .on( 'click', params.selectors.cartItemDel, deleteItem )
                .on( 'change', params.selectors.cartItemQty, updateItem )
                .on( 'change', params.selectors.couponInput, updateCart )
                .on( 'click', params.selectors.cartLink, submitCartLink )
                .on( 'click', 'button[aria-controls="wc-fast-cart"],a[href="#close-modal"]', hide )
                .on( 'click', params.selectors.shippingBtn, (e) => {
                    e.preventDefault();
                    $( params.selectors.shippingTable ).slideToggle();
                } )
                .on( 'change', params.selectors.shippingInputs, changeShippingMethod )
                .on( 'change', params.selectors.locationInputs, changeShippingLocation )
                .on( 'click', params.selectors.carouselNav, changeCarouselSlide )
                .on( 'submit', params.selectors.shippingForm, ( e ) => {
                    e.preventDefault();
                    e.stopPropagation();
                    submitShippingForm( e );
                    return false;
                } )
                .on( 'submit', params.selectors.cartWrapper, ( e ) => {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                } )
                .on( 'keydown', trapKeyboardNavigation );

            if ( params.options.fastCheckout ) {
                $wfcart.on( 'click', params.selectors.checkoutLink, openCheckout );
            }

        };

        const trapKeyboardNavigation = (e) => {

            let isTabPressed = e.key === 'Tab' || e.keyCode === 9;

            if ( ! isTabPressed) {
                return;
            }

            let focusableButtons = cartDOM.querySelectorAll( 'button,a' );
            let firstFocusableElement = focusableButtons[0];
            let lastFocusableElement = focusableButtons[ focusableButtons.length - 1 ];

            if ( e.shiftKey && document.activeElement === firstFocusableElement ) {
                lastFocusableElement.focus();
                e.preventDefault();
                e.stopPropagation();
            } else if ( ! e.shiftKey && document.activeElement === lastFocusableElement ) { // if tab key is pressed
                firstFocusableElement.focus();
                e.preventDefault();
                e.stopPropagation();
            }
        }

        const flagForReopen = () => {
            debug( 'clicked on qvp link' );
            shouldReopenCart = true;
        };

        const maybeReopenCart = () => {

            if ( shouldReopenCart ) {
                shouldReopenCart = false;
                show();
            }

        };

        const maybeCloseCartOnEscape = ( e ) => {

            if ( cartOverlay.getAttribute( 'aria-hidden' ) !== 'false' ) {
                return;
            }

            if ( e.key !== 'Escape' ) {
                return;
            }

            hide();

        };

        const setStatus = ( newStatus ) => {
            document.body.dataset.fastCartStatus = newStatus;
        };

        const getStatus = () => {
            return document.body.dataset.fastCartStatus;
        };

        const waitForRequestToFinish = ( request, whenComplete ) => {

            debug( 'waiting for request to complete', request, whenComplete );

            if ( ! request || ! request.readyState || request.readyState === 4 ) {
                whenComplete();
                return;
            }

            setTimeout( waitForRequestToFinish, 250, request, whenComplete );

        };

        const setupDefaultParams = () => {

            let defaultSettings = {};

            for ( let setting in defaultSettings ) {
                if ( ! params[setting] ) {
                    params[setting] = defaultSettings[setting];
                }
            }

            let defaultSelectors = {
                cartOpen:           `a[href*="${params.endpoints.cart}"],a.cart-contents,button.cart-contents`,
                checkoutOpen:       `a[href*="${params.endpoints.checkout}"]:not([href*="pay_for_order=true"])`,
                cartLink:           `a[href*="${params.endpoints.cart}"]`,
                checkoutLink:       `a[href*="${params.endpoints.checkout}"]:not([href*="pay_for_order=true"])`,
                cartBtn:            'a.add_to_cart_button,a[href*="?add-to-cart"]',
                cartForm:           '.product form.cart',
                cartPageWrapper:    '#wfc-cart-page',
                cartWrapper:        'form.wfc-cart-form',
                cartEmpty:          '.wfc-cart-empty',
                cartItemDel:        'a.wfc-cart__remove,a[href*="?remove_item"]',
                cartItemQty:        'input[name*="qty"]',
                carouselNav:        'button.wfc-carousel-navigation',
                qvpBtn:             '.wc-quick-view-button',
                shippingForm:       'form.woocommerce-shipping-calculator',
                shippingTable:      '.shipping-calculator-form',
                shippingInputs:     'select.shipping_method,input[name^=shipping_method],button[name="calc_shipping"]',
                locationInputs:     '#calc_shipping_country',
                shippingCalculator: 'form.woocommerce-shipping-calculator',
                shippingBtn:        '.shipping-calculator-button',
                couponInput:        'input[name="coupon_code"]',
                updateBtn:          'button[type="submit"]'
            };

            if ( ! params.selectors ) {
                params.selectors = {};
            }
            for ( let selector in defaultSelectors ) {
                if ( params.selectors[selector] ) {
                    params.selectors[selector] += ',' + defaultSelectors[selector].trim(',');
                } else {
                    params.selectors[selector] = defaultSelectors[selector];
                }

            }

        };

        const generateCSSVars = () => {

            let wrapper = document.createElement( 'div' );
            wrapper.classList.add( 'woocommerce' );
            wrapper.style.dislay = 'none';

            let button = document.createElement( 'button' );
            button.setAttribute( 'class', params.classes.button );
            button.innerHTML = 'Test button';

            wrapper.append( button );
            document.body.append( wrapper );

            let style = window.getComputedStyle( button );

            let bgColor = style.getPropertyValue( 'background-color' );
            let borderColor = style.getPropertyValue( 'border-color' );
            let textColor = style.getPropertyValue( 'color' );
            if ( bgColor === 'transparent' || bgColor === 'rgba(0, 0, 0, 0)' ) {
                bgColor = 'rgb(255, 255, 255)';
                borderColor = textColor;
            }
            else if ( borderColor === bgColor ) {
                borderColor = textColor;
            }

            if ( borderColor === 'rgb(255, 255, 255)' ) {
                borderColor = textColor === 'rgb(255, 255, 255)' ? bgColor : textColor;
            }

            //cartDOM.style.setProperty( '--wfc-btn-bg-color', bgColor );
            //cartDOM.style.setProperty( '--wfc-btn-border-color', borderColor );
            //cartDOM.style.setProperty( '--wfc-btn-color', textColor );
            cartDOM.style.setProperty( '--wfc-btn-radius', style.getPropertyValue( 'border-radius' ) );
            cartDOM.style.setProperty( '--wfc-font', style.getPropertyValue( 'font-family' ) );

            wrapper.parentNode.removeChild( wrapper );

        };

        const installCartDOM = ( isVisible ) => {

            //let outsideWrapper = document.createElement('div');
            //outsideWrapper.style.position = 'static';
            //outsideWrapper.classList.add( 'wc-fast-cart-compatibility-wrapper' );

            let expanded = isVisible ? 'true' : 'false';
            let hidden = isVisible ? 'false' : 'true';

            cartDOM = document.createElement( 'aside' );
            cartDOM.classList.add( 'wc-fast-cart' );
            cartDOM.classList.add( 'is-style-' + ( params.options.displayMode || 'sidebar' ) );
            if ( params.classes.cart && params.classes.cart.length > 0 ) {
                for ( let classAttr of params.classes.cart ) {
                    cartDOM.classList.add( classAttr );
                }
            }
            //cartDOM.setAttribute( 'aria-hidden', 'true' );
            cartDOM.id = 'wc-fast-cart';
            cartDOM.dataset.isEmpty = true;

            cartDOM.innerHTML = `<button class="wc-fast-cart__close-btn" aria-expanded="${expanded}" aria-controls="wc-fast-cart"><span class="wfc-sr-text">Close Cart</span><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M16.192 6.344L11.949 10.586 7.707 6.344 6.293 7.758 10.535 12 6.293 16.242 7.707 17.656 11.949 13.414 16.192 17.656 17.606 16.242 13.364 12 17.606 7.758z"></path></svg></button>`;
            cartDOM.innerHTML += '<div role="presentation" class="wc-fast-cart__loading-spinner"><div class="wfc-loading-animation-element"></div></div>';
            if ( params.strings.cartTitle ) {
                cartDOM.innerHTML += `<h2>${params.strings.cartTitle}</h2>`;
            }

            cartDOMContents = document.createElement( 'div' );
            cartDOMContents.classList.add( 'wc-fast-cart__inner-contents' );
            cartDOM.append( cartDOMContents );

            $wfcart = $(cartDOM);

            cartOverlay = document.createElement( 'div' );
            cartOverlay.setAttribute( 'aria-hidden', hidden );
            cartOverlay.classList.add( 'wc-fast-cart__page-overlay' );
            cartOverlay.append( cartDOM );

            cartBackground = document.createElement( 'div' );
            cartBackground.classList.add( 'wc-fast-cart__page-overlay-background' );
            cartBackground.setAttribute( 'role', 'presentation' );

            document.body.append( cartOverlay );
            document.body.append( cartBackground );

            if ( isVisible ) {
                makeFastCartVisible();
            }

            //outsideWrapper.append( cartOverlay );
            //outsideWrapper.append( cartBackground );
            //document.body.append( outsideWrapper );


            generateCSSVars();

        };

        const setupCartCarousel = () => {

            let crossSells = cartDOM.querySelector( '.wfc-cross-sells' );

            if ( ! crossSells || crossSells.dataset.carouselInstalled || ! crossSells.classList.contains( 'more-than-one' ) ) {
                return;
            }

            let crossSellsWrapper = crossSells.querySelector( '.wfc-cross-sells__inner-container' );

            let productList = crossSells.querySelector( 'ul.products' );
            let products = productList.querySelectorAll( 'li' );

            if ( ! products || products.length < 2 ) {
                return;
            }

            products[0].classList.add( 'active' );

            if ( ! products || products.length > 2 ) {

                let prevBtn = document.createElement( 'button' );
                prevBtn.type = 'button';
                prevBtn.setAttribute( 'class', 'wfc-btn wfc-carousel-navigation wfc-carousel-navigation--prev' );
                prevBtn.dataset.direction = "prev";
                prevBtn.innerHTML = '<span class="wfc-sr-text">Previous</span><svg role="presentation" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 192 512"><path d="M4.2 247.5L151 99.5c4.7-4.7 12.3-4.7 17 0l19.8 19.8c4.7 4.7 4.7 12.3 0 17L69.3 256l118.5 119.7c4.7 4.7 4.7 12.3 0 17L168 412.5c-4.7 4.7-12.3 4.7-17 0L4.2 264.5c-4.7-4.7-4.7-12.3 0-17z"/></svg>';
                prevBtn.disabled = true;

                let nextBtn = document.createElement( 'button' );
                nextBtn.type = 'button';
                nextBtn.setAttribute( 'class', 'wfc-btn wfc-carousel-navigation wfc-carousel-navigation--next' );
                nextBtn.dataset.direction = "next";
                nextBtn.innerHTML = '<span class="wfc-sr-text">Next</span><svg aria-hidden="true" role="presentation" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 192 512"><path d="M187.8 264.5L41 412.5c-4.7 4.7-12.3 4.7-17 0L4.2 392.7c-4.7-4.7-4.7-12.3 0-17L122.7 256 4.2 136.3c-4.7-4.7-4.7-12.3 0-17L24 99.5c4.7-4.7 12.3-4.7 17 0l146.8 148c4.7 4.7 4.7 12.3 0 17z"/></svg>';

                crossSellsWrapper.append( prevBtn );
                crossSellsWrapper.append( nextBtn );

                crossSells.classList.add( 'wfc-carousel--has-nav' );

            }

            crossSells.classList.add( 'wfc-carousel' );
            crossSells.dataset.relatedCount = products.length;

            let maxHeight = 0;
            for( let slide of products ) {
                let box = slide.getBoundingClientRect();
                if ( box.height > maxHeight ) {
                    maxHeight = box.height;
                }
            }

            crossSells.dataset.carouselInstalled = 'true';

            productList.style.height = maxHeight + 'px';
            crossSells.classList.add( 'wfc-carousel--installed' );
            crossSells.style.setProperty( '--max-height', maxHeight + 'px' );


        };

        const changeCarouselSlide = ( { currentTarget } ) => {

            if ( currentTarget.disabled ) {
                return;
            }

            let currentSlide = cartDOM.querySelector( '.wfc-carousel li.product.active' );
            let prevBtn = cartDOM.querySelector( 'button[data-direction="prev"]' );
            let nextBtn = cartDOM.querySelector( 'button[data-direction="next"]' );
            let nextSlide;

            if ( currentTarget.dataset.direction === 'next' ) {
                nextSlide = currentSlide.nextElementSibling;
                prevBtn.disabled = false;
            } else {
                nextSlide = currentSlide.previousElementSibling;
                nextBtn.disabled = false;
            }

            if ( ! nextSlide ) {
                return;
            }
            nextSlide.classList.add( 'active' );
            currentSlide.classList.remove( 'active' );

            if ( ! nextSlide.previousElementSibling ) {
                prevBtn.disabled = true;
            }
            if ( ! nextSlide.nextElementSibling || ! nextSlide.nextElementSibling.nextElementSibling ) {
                nextBtn.disabled = true;
            }
            if ( window.innerWidth >= 990 && ( params.options.displayMode || 'sidebar' ) === 'modal' && ! nextSlide.nextElementSibling.nextElementSibling.nextElementSibling ) {
                nextBtn.disabled = true;
            }

            return false;

        };

        const refreshCart = ( reinstall ) => {

            if ( reinstall ) {

                cartDOM.classList.remove( 'is-checkout' );
                cartDOM.classList.remove( 'direct-checkout' );
                document.body.classList.remove( 'wfc-checkout-is-open' );

                resetCart( cartOverlay.getAttribute( 'aria-hidden' ) === 'false' );
                return;
            }

            waitForRequestToFinish( cartRequest, () => {
                cartRequest.open( 'GET', cartUrl );
                cartRequest.send();
            } );

        };

        const completeCartRefresh = ( { target } ) => {
            const { status, response } = target;
            if ( status !== 200 ) {
                debug( 'response from cart refresh failed', target );
                setStatus( 'error' );
                return;
            }

            document.dispatchEvent( new Event( 'wc-fast-cart|before_refresh' ) );

            if ( target.reinstallOnLoad ) {
                reinstallCart( true );
                cartRequest.reinstallOnLoad = false;
            } else if ( ! $wfcart ) {
                installCartDOM();
            }

            let responseContents = response.body.querySelector( params.selectors.cartWrapper );
            let result;
            if ( responseContents ) {

                debug( 'installing cart contents' );

                cartDOMContents.innerHTML = responseContents.parentNode.innerHTML;
                cartDOM.dataset.isEmpty = 'false';
                setStatus( 'ready' );
                showCartIcon();

                let checkoutWrapper = cartDOMContents.querySelector( '.wc-proceed-to-checkout' );
                if ( checkoutWrapper && params.options.displayMode === 'side' ) {
                    cartDOM.style.paddingBottom = checkoutWrapper.getBoundingClientRect().height + 'px';
                }

                let cartForm = cartDOMContents.querySelector( 'form.wfc-cart-form' );
                if ( cartForm ) {
                    updateQtyCount( cartForm );
                }

                let couponCell = cartDOMContents.querySelector( '.wfc-cart-table__actions' );
                if ( couponCell ) {
                    let box = couponCell.getBoundingClientRect();
                    cartDOM.style.setProperty( '--wfc-coupon-height', box.height + 'px' );
                }

                setupCartCarousel();

                //let notices = responseContents.querySelector( '.woocommerce-notices-wrapper' );
                //if ( notices && notices.children && notices.children.length ) {
                //    // TODO add notices
                //}

                $( document.body ).trigger( 'updated_cart_totals' );
                result = 'success';

            } else if ( response.body.querySelector( params.selectors.cartEmpty ) ) {
                cartDOMContents.innerHTML = response.body.innerHTML; //'<p>' + params.strings.emptyCart + '</p>';
                cartDOM.dataset.isEmpty = 'true';
                setStatus( 'ready' );
                hideCartIcon();
                setCartBtnCount(0);
                result = 'success';
            } else {
                debug( 'response from cart was missing woocommerce', target );
                cartDOM.dataset.isEmpty = 'true';
                setStatus( 'error' );
                hideCartIcon();
                result = 'failure';
            }

            $( document.body ).trigger( 'wc_fragment_refresh' );
            $( document.body ).trigger( 'updated_checkout', { result } );

            document.dispatchEvent( new Event( 'wc-fast-cart|after_refresh' ) );

            if ( cartOverlay.getAttribute( 'aria-hidden' ) === 'false' ) {
                allowElementsFocus();
            } else {
                disableElementsFocus();
            }

        };

        const processCartForm = ( e ) => {

            debug( 'trying to add cart form item' );

            qtyRequest.abort();
            cartRequest.abort();

            const quickviewWindow = e.target.closest( '.wc-quick-view-modal' ),
                  bulkvarWrapper = e.target.closest( '.wc-bulk-variations-table-wrapper' );

            if ( ! params.options.autoOpen && quickviewWindow ) {
                return;
            }

            let form = e.currentTarget;

            if ( ! form.action ) {
                return;
            }
            const formAction = new URL( form.action );
            if ( ! formAction || formAction.hostname !== window.location.hostname ) {
                return;
            }
                
            e.preventDefault();
            e.stopPropagation();

            
            let formData = new FormData( form );
            formData.set( 'wfc-cart', 1 );

            if ( e.originalEvent && e.originalEvent.submitter && e.originalEvent.submitter.name && e.originalEvent.submitter.value ) {
                formData.set( e.originalEvent.submitter.name, e.originalEvent.submitter.value );
            }

            let buttons = form.querySelectorAll( 'button[type="submit"],.single_add_to_cart_button' );
            buttons.forEach( el => {
                el.disabled = true;
                el.style.cursor = 'wait';
                el.classList.add('loading');
            } );

            let responseMessages, responseDocument;

            if ( params.options.autoOpen && ! quickviewWindow ) {
                setStatus( 'loading' );
                show();
            }

            let formRequest = new XMLHttpRequest();
            formRequest.responseType = 'document';
            formRequest.withCredentials = true;
            formRequest.open( 'POST', form.action );
            formRequest.send( formData );
            formRequest.addEventListener( 'load', ( e ) => {
                const { status, response } = e.target;
                if ( status === 200 ) {
                    const responseErrors = response.querySelectorAll( '.woocommerce-error' );
                    if ( quickviewWindow && responseErrors.length > 0 ) {
                        buttons.forEach( el => {
                            el.disabled = false;
                            el.style.cursor = '';
                            el.classList.remove('loading');
                        } );
                        return;
                    } else {
                        responseMessages = response.querySelectorAll( '.woocommerce-message,.woocommerce-error' );
                        responseDocument = response;
                        completeAddToCartForm();
                    }
                }

            } );

            const completeAddToCartForm = () => {

                setStatus( 'loading' );

                waitForRequestToFinish( formRequest, () => {

                    $( document ).trigger( 'wc_fragment_refresh' );
                    $( document ).trigger( 'updated_checkout' );

                    cartRequest.open( 'GET', cartUrl );
                    cartRequest.send( formData );

                    if ( params.options.directCheckout ) {

                        debug( buttons );

                        $body.trigger( 'added_to_cart', [ [], null, jQuery( buttons ) ] );
                        buttons.forEach( el => () => {
                            el.classList.add( 'added_to_cart' );
                        } );

                        cartDOM.classList.add( 'is-checkout' );
                        cartDOM.classList.add( 'direct-checkout' );
                        cartDOM.querySelector('h2').innerHTML = params.strings.checkoutTitle;
                        cartDOM.style.paddingBottom = '';

                        if ( params.options.autoOpen ) {

                            document.body.classList.add( 'wfc-checkout-is-open' );
                            $('html').addClass( `wfc-lock-scrolling` );
                            if ( window.innerWidth < 782 ) {
                                document.documentElement.style.setProperty('margin-top', '0px', 'important');
                            }

                            waitForRequestToFinish( cartRequest, () => {
                                openCheckout();
                            } );

                        }

                    } else {

                        if ( quickviewWindow ) {
                            for( let message of responseMessages ) {
                                if ( message.classList.contains( 'woocommerce-error' ) ) {
                                    buttons.forEach( el => () => {
                                        el.classList.remove( 'loading' );
                                    } );
                                    return;
                                }
                            }
                        }

                        if ( quickviewWindow ) {
                            $.modal.close();
                        }

                        if ( params.options.autoOpen ) {
                            show();
                        }

                        if ( buttons ) {

                            waitForRequestToFinish( cartRequest, () => {
                                $body.trigger( 'added_to_cart' );
                                buttons.forEach( el => () => {
                                    el.classList.add( 'added_to_cart' );
                                    el.classList.remove( 'loading' );
                                } );
                                
                            } );
                        }

                        if ( responseMessages ) {
                            waitForRequestToFinish( cartRequest, () => {
                                let noticesWrapper = cartDOM.querySelector( '.woocommerce-notices-wrapper' );
                                if ( noticesWrapper ) {
                                    for ( let message of responseMessages ) {
                                        noticesWrapper.append( message );
                                    }
                                }
                            } );
                        }

                    }

                    buttons.forEach( el => {
                        el.classList.remove( 'added_to_cart', 'loading' );
                        el.disabled = false;
                        el.style.cursor = '';
                    } );

                } );

            };

        };

        const itemRemovedExternally = ( e ) => {

            debug( 'external cart remove' );

            refreshCart();

        };

        const maybeOpenCart = () => {

            debug( 'variation added to cart' );

            let url = new URL( params.endpoints.cart );
            url.searchParams.set( 'wfc-cart', 'true' );

            refreshCart();

            let qvModals = document.querySelectorAll( '.wc-quick-view-modal' );
            let qvIsOpen = false;
            for ( let modal of qvModals ) {
                if ( modal.style.display !== 'none' ) {
                    qvIsOpen = true;
                }
            }

            if ( ! qvIsOpen ) {

                openCartOrCheckout( url );

            }

        };

        const itemAddedFromBlock = ( e ) => {

            debug( 'block cart add', isAddingToCart, e );

            if ( ! isAddingToCart ) {
                maybeOpenCart();
            }

        };

        const itemAddedExternally = ( e, fragments, hash ) => {

            debug( 'external cart add', isAddingToCart, e, fragments, hash  );

            if ( ! fragments || fragments.length === 0 ) {
                return;
            }

            if ( ! isAddingToCart ) {
                maybeOpenCart();
            }

        };

        const openCartOrCheckout = ( url, initiator ) => {

            let isCrossSell = false;
            if ( initiator ) {
                isCrossSell = initiator.closest('.wfc-cross-sells');
            }

            if ( ( params.options.autoOpen || isCrossSell ) && ! params.options.directCheckout ) {
                setStatus( 'loading' );
                show();
            }

            waitForRequestToFinish( cartRequest, () => {

                setStatus( 'loading' );
                cartRequest.open( 'GET', url );
                cartRequest.send();

                if ( ( params.options.autoOpen || isCrossSell ) && params.options.directCheckout ) {

                    cartDOM.classList.add( 'is-checkout' );
                    cartDOM.classList.add( 'direct-checkout' );
                    cartDOM.querySelector('h2').innerHTML = params.strings.checkoutTitle;
                    cartDOM.style.paddingBottom = '';

                    document.body.classList.add( 'wfc-checkout-is-open' );
                    document.documentElement.classList.add( `wfc-lock-scrolling` );
                    if ( window.innerWidth < 782 ) {
                        document.documentElement.style.setProperty('margin-top', '0px', 'important');
                    }

                    waitForRequestToFinish( cartRequest, () => {
                        openCheckout();
                    } );

                }

                if ( initiator ) {
                    waitForRequestToFinish( cartRequest, () => {
                        initiator.classList.remove('loading');
                        $body.trigger( 'added_to_cart', [ [], null, jQuery(initiator) ] );
                    } );
                }

                waitForRequestToFinish( cartRequest, () => {
                    isAddingToCart = false;
                } );

            } );

        };

        const addItem = ( e ) => {

            const self = e.target.closest( 'a' );
            if ( ! self ) {
                return;
            }

            if ( ! params.options.autoOpen && ! self.closest('.wfc-cross-sells') ) {
                return;
            }

            if ( self.dataset.action === 'quick-view' ||
                 self.classList.contains( 'wc-quick-view-button' ) ||
                 self.classList.contains( 'product_type_variable' ) ) {
                return;
            }

            const searchParams = ( new URL( self.href ) ).searchParams;

            if ( ! self.classList.contains( 'add_to_cart_button' ) && ! searchParams.has('add-to-cart') ) {
                return;
            }

            if ( self.classList.contains( 'add_to_cart_button' ) && ! self.classList.contains( 'ajax_add_to_cart' ) ) {
                return;
            }

            // find the product Id
            let productId = self.dataset.product_id;
            if ( ! productId && searchParams.has('add-to-cart') ) {
                productId = searchParams.get('add-to-cart');
            }
            if ( ! productId ) {
                // fallback to default behavior
                return;
            }

            e.preventDefault();
            e.stopPropagation();

            debug( `adding ${productId} to the cart` );
            isAddingToCart = productId;

            self.classList.add( 'loading' );
            self.classList.remove( 'added' );

            let url = new URL( params.endpoints.cart );
            url.searchParams.set( 'add-to-cart', productId );
            url.searchParams.set( 'wfc-cart', 'true' );

            let qty = parseInt( self.dataset.quantity || 1 );
            if ( isNaN( qty ) || qty < 1 ) {
                qty = 1;
            }
            url.searchParams.set( 'quantity', qty );

            openCartOrCheckout( url, self );

        };

        const deleteItem = ( e ) => {

            e.preventDefault();
            e.stopPropagation();

            $( e.target ).closest( 'tr' ).slideUp();

            let deleteItemXHR = new XMLHttpRequest();
            deleteItemXHR.responseType = 'document';
            deleteItemXHR.withCredentials = true;
            deleteItemXHR.open( 'POST', e.target.href );
            deleteItemXHR.send();
            deleteItemXHR.addEventListener( 'load', () => {

                cartRequest.abort();
                cartRequest.open( 'GET', cartUrl );
                cartRequest.send();

            } );

            setStatus( "loading" );

        };

        const updateQtyCount = ( cartForm ) => {

            if ( cartForm.dataset.count ) {
                setCartBtnCount( cartForm.dataset.count );
            }

        };

        const setCartBtnCount = ( value ) => {
            // Update all the cart buttons on the page
            let cartBtns = document.querySelectorAll( '.wfc-open-cart-button' );

            if ( cartBtns ) {
                cartBtns.forEach( function( cartBtn ) {
                    let countBtn = cartBtn.querySelector('.wfc-open-cart-button__count');
                    if ( ! countBtn ) {
                        return;
                    }
                    countBtn.innerHTML = value;
                    if ( parseInt( value ) > 99 ) {
                        countBtn.classList.add( 'is-over-99' );
                    } else {
                        countBtn.classList.remove( 'is-over-99' );
                    }
                } );
            }
            
        };

        const updateItem = ( { currentTarget } ) => {

            if ( currentTarget.value === '0' ) {
                currentTarget.value = '1';
                return;
            }

            setStatus( 'loading' );

            postCartContents();

        };

        const updateCart = ( e ) => {

            e.preventDefault();
            e.stopPropagation();

            postCartContents();

            qtyRequest.abort();
            cartRequest.abort();

            setStatus( 'loading' );

            let form = cartDOM.querySelector( 'form' );
            if ( ! form ) {
                return;
            }

            let formData = new FormData( form );
            if ( formData.get( 'coupon_code' ) ) {
                formData.set( 'apply_coupon', 'Apply coupon' );
            }
            formData.set( 'wfc-cart', 'true' );

            waitForRequestToFinish( cartRequest, () => {
                let url = new URL( form.getAttribute( 'action' ) );
                url.searchParams.set( 'wfc-cart', 'true' );
                cartRequest.open( 'POST', url );
                cartRequest.send( formData );
            } );

        };

        const reinstallCart = ( isVisible ) => {

            cartDOM.parentNode.removeChild( cartDOM );
            cartOverlay.parentNode.removeChild( cartOverlay );
            cartBackground.parentNode.removeChild( cartBackground );

            installCartDOM( isVisible );
            bindCartEvents();

        };

        const resetCart = ( isVisible ) => {

            debug( 'resetting fast cart', isVisible );

            if ( isVisible ) {
                cartRequest.reinstallOnLoad = true;
            } else {
                reinstallCart( isVisible );
            }

            cartRequest.open( 'GET', cartUrl );
            cartRequest.send();

            setStatus( 'resetting' );

        };

        const submitCartLink = ( e ) => {

            if ( ! e.currentTarget.href ) {
                return;
            }

            e.preventDefault();
            e.stopPropagation();

            cartRequest.abort();

            let url = new URL( e.currentTarget.href );
            url.searchParams.set( 'wfc-cart', 'true' );

            cartRequest.open( 'GET', url );
            cartRequest.send();

            setStatus( 'loading' );

        };

        const changeShippingMethod = ( e ) => {

            let shipping_methods = {};

            let formData = new FormData();
            formData.append( 'security', params.shippingNonce );
            formData.append( 'shipping_method', shipping_methods );
            document.querySelectorAll( 'select.shipping_method, input[name^=shipping_method][type=radio]:checked, input[name^=shipping_method][type=hidden]' ).forEach( element => {
                formData.append( 'shipping_method[' + element.dataset.index + ']', element.value );
            } );

            cartDOM.classList.add( 'is-updating-shipping' );
            setStatus( "loading" );

            let shippingRequest = new XMLHttpRequest();
            shippingRequest.responseType = 'document';
            shippingRequest.withCredentials = true;
            shippingRequest.addEventListener( 'load', finishUpdatingShipping );
            shippingRequest.open( 'POST', params.endpoints.shipping );
            shippingRequest.send( formData );

        };

        const changeShippingLocation = ( e ) => {

            let form = e.target.closest( 'form' );

            let formData = new FormData( form );
            formData.append( 'calc_shipping', '1' );
            formData.set( 'calc_shipping_state', '' );
            formData.set( 'calc_shipping_city', '' );
            formData.set( 'calc_shipping_postcode', '' );
            //formData.append( 'security', params.shippingNonce );

            let shippingRequest = new XMLHttpRequest();
            shippingRequest.responseType = 'document';
            shippingRequest.withCredentials = true;
            shippingRequest.addEventListener( 'load', finishUpdatingLocation );
            shippingRequest.open( 'POST', form.action );
            shippingRequest.send( formData );

            cartDOM.classList.add( 'is-updating-shipping' );
            setStatus( "loading" );

        };

        const finishUpdatingLocation = ( { target } ) => {

            finishUpdatingShipping( { target } );

            let calculatorForm = cartDOM.querySelector( params.selectors.shippingTable );
            if ( calculatorForm ) {
                calculatorForm.style.display = '';
            }

        }

        const finishUpdatingShipping = ( { target } ) => {

            const { status, response } = target;

            if ( status !== 200 ) {
                setStatus( "error" );
                return;
            }

            cartDOM.classList.remove( 'is-updating-shipping' );
            setStatus( "ready" );

            finishUpdatingQty( { target } );

        };

        const submitShippingForm = ( e ) => {

            let form = e.currentTarget;

            let shippingRequest = new XMLHttpRequest();
            shippingRequest.responseType = 'document';
            shippingRequest.withCredentials = true;
            shippingRequest.addEventListener( 'load', finishUpdatingShipping );

            let formData = new FormData( form );
            formData.append( 'calc_shipping', '1' );
            shippingRequest.open( 'POST', form.getAttribute( 'action' ) );
            shippingRequest.send( formData );

            cartDOM.classList.add( 'is-updating-shipping' );
            setStatus( "loading" );

        };

        const postCartContents = () => {

            qtyRequest.abort();

            let form = cartDOM.querySelector( 'form' );
            if ( ! form ) {
                return;
            }

            let url = new URL( form.getAttribute( 'action' ) );
            url.searchParams.set( 'wfc-cart', 'true' );

            let formData = new FormData( form );
            formData.append( 'update_cart', 'Update Cart' );
            qtyRequest.open( 'POST', url );
            qtyRequest.send( formData );

        };

        const finishUpdatingQty = ( { target } ) => {

            const { status, response } = target;

            if ( status !== 200 ) {
                setStatus( "error" );
                return;
            }

            cartDOM.classList.remove( 'is-updating-qty' );
            setStatus( "ready" );

            for ( let qtyInput of response.body.querySelectorAll( 'input.qty' ) ) {
                let newPrice  = qtyInput.closest('tr').querySelector('.product-subtotal'), 
                    qtyPicker = cartDOM.querySelector( `input[name="${qtyInput.name}"]` ),
                    oldPrice;
                if ( qtyPicker ) {
                    oldPrice = qtyPicker.closest('tr').querySelector('.product-subtotal');
                }

                if ( newPrice && oldPrice ) {
                    oldPrice.innerHTML = newPrice.innerHTML;
                }
            }

            let cartTotal = response.body.querySelector( '.wfc-cart-totals__table' );
            if ( cartTotal ) {
                cartDOM.querySelector( '.wfc-cart-totals__table' ).innerHTML = cartTotal.innerHTML;
            }

            let cartCheckout = response.body.querySelector( '.wfc-checkout-buttons' );
            if ( cartCheckout ) {
                cartDOM.querySelector( '.wfc-checkout-buttons' ).innerHTML = cartCheckout.innerHTML;
            }

            let cartErrors = response.body.querySelectorAll( '.woocommerce-error' );
            let notices = cartDOM.querySelector( '.woocommerce-notices-wrapper' );
            if ( notices ) {
                notices.innerHTML = '';
                if ( cartErrors.length > 0 ) {
                    for( let error of cartErrors ) {
                        notices.append( error );
                    }
                }
            }


            let cartRequestForm = response.body.querySelector( 'form.wfc' );
            if ( cartRequestForm ) {
                updateQtyCount( cartRequestForm );
            }

            $( document.body ).trigger( 'updated_cart_totals' );

            $( document.body ).trigger( 'wc_fragment_refresh' );

            window.FastCart.refresh();
        };

        const showCartIcon = () => {

            if ( params.options.floatingIcon === false ) {
                return;
            }

            if ( cartBtn ) {
                cartBtn.setAttribute( 'aria-hidden', 'false' );
            }

        };

        const hideCartIcon = () => {

            if ( params.options.floatingIcon === false ) {
                return;
            }

            if ( cartBtn ) {
                cartBtn.setAttribute( 'aria-hidden', 'true' );
            }

        };

        const openCheckout = ( e ) => {

            if ( e ) {
                e.preventDefault();
                e.stopPropagation();
            }

            if ( cartDOM.dataset.isEmpty !== 'false' ) {
                makeFastCartVisible();
                setStatus( "ready" );
                document.body.dataset.checkoutStatus = "ready";
                debug( 'cart is empty, do not proceed.' );
                return;
            }

            setStatus( "loading" );
            document.body.dataset.checkoutStatus = "loading";

            let checkoutFrame = document.createElement( 'iframe' );
            checkoutFrame.classList.add( 'wc-fast-cart__checkout-frame' );
            checkoutFrame.setAttribute( 'scrolling', 'no' );

            let url = new URL( params.endpoints.checkout );
            url.searchParams.set( 'wfc-checkout', 'true' );

            checkoutFrame.src = url;
            checkoutFrame.style.opacity = 0;
            checkoutFrame.onload = () => {
                checkoutFrame.isLoaded = true;
                if ( checkoutFrame.isAttached ) {
                    checkoutFrame.style.opacity = 1;
                    if ( checkoutFrame.nextElementSibling && checkoutFrame.nextElementSibling.tagName === 'IFRAME' ) {
                        checkoutFrame.nextElementSibling.remove();
                    }
                }
            };

            const showCheckout = () => {
                cartDOM.classList.add( 'is-checkout' );
                document.body.classList.add( 'wfc-checkout-is-open' );
                if ( window.innerWidth < 782 ) {
                    document.documentElement.style.setProperty('margin-top', '0px', 'important');
                }
                //cartDOM.classList.remove( 'is-style-' + params.mode );

                cartDOM.querySelector('h2').innerHTML = params.strings.checkoutTitle;
                cartDOM.style.paddingBottom = '';

                const oldIframe = cartDOM.querySelector('.wc-fast-cart__checkout-frame');
                if ( oldIframe ) {
                    oldIframe.isLoaded = false;
                    oldIframe.src = checkoutFrame.src;
                    //oldIframe.insertAdjacentElement('beforebegin',checkoutFrame);
                } else {
                    cartDOM.append( checkoutFrame );
                    checkoutFrame.isAttached = true;
                    if ( checkoutFrame.isLoaded ) {
                        checkoutFrame.style.opacity = 1;
                    }
                }

                allowElementsFocus();
            };

            if ( cartOverlay.getAttribute( 'aria-hidden' ) !== 'false' ) {

                $( cartDOMContents ).remove();
                $('[aria-controls="wc-fast-cart"]').attr( 'aria-expanded', 'true' );
                cartOverlay.setAttribute( 'aria-hidden', 'false' );
                document.body.classList.add( `wfc-${params.options.displayMode}-is-open` );
                $('html').addClass( `wfc-lock-scrolling` );

                showCheckout();

            } else {

                $( cartDOMContents ).fadeOut( () => {
                    $( cartDOMContents ).remove();

                    showCheckout();

                });

            }



        };

        // this function is intended to be called from inside the iframe checkout
        const loadCheckout = ( iframeHeight ) => {

            setStatus( "ready" );
            document.body.dataset.checkoutStatus = "ready";
            //debug( iframeHeight );
            if ( iframeHeight ) {
                let checkoutFrame = cartDOM.querySelector( 'iframe' );
                checkoutFrame.style.height = ( iframeHeight ) + 'px';
                if ( checkoutFrame.isAttached ) {
                    checkoutFrame.style.opacity = 1;
                }
            }
        };

        const resetScroll = () => {

            if ( ! cartOverlay ) {
                return;
            }

            cartOverlay.scrollTo( {
                behavior: 'smooth',
                top: 0,
                left: 0,
            } );

        };

        const completeCheckout = ( redirectTo ) => {
            setStatus( "locked" );
            if ( redirectTo ) {
                setTimeout( () => {
                    window.location.href = redirectTo;
                }, 1500 );
            }
        };

        const disableElementsFocus = () => {

            let focusableElements = cartDOM.querySelectorAll( 'select,textarea,button,object,button,a,input,[tabindex]' );
            for ( let el of focusableElements ) {
                if ( el.tabIndex ) {
                    el.dataset.tabIndex = el.tabIndex;
                }
                el.tabIndex = '-1';
                el.disabled = true;
            }

        };

        const hide = ( e ) => {

            //e.preventDefault();
            //e.stopPropagation();

            if ( getStatus() === 'locked' ) {
                return;
            }

            cartOverlay.classList.add( 'closing' );
            setTimeout( () => {

                cartOverlay.classList.remove( 'closing' );
                cartOverlay.setAttribute( 'aria-hidden', 'true' );

                $('html').removeClass( `wfc-lock-scrolling` );
                document.body.classList.remove( `wfc-${params.options.displayMode}-is-open` );

                disableElementsFocus();

                showCart( true );

                document.dispatchEvent( new Event( 'wc-fast-cart|close' ) );

            }, 400 );

            $('[aria-controls="wc-fast-cart"]').attr( 'aria-expanded', 'false' );
            shouldReopenCart = false;


        };

        const showCart = ( reset ) => {

            document.documentElement.style.marginTop = '';

            let notice = cartDOM.querySelector( '.woocommerce-notices-wrapper' );
            if ( notice ) {
                notice.parentNode.removeChild( notice );
            }

            if ( cartDOM.classList.contains( 'is-checkout' ) ) {

                debug( 'destroy checkout' );

                cartDOM.classList.remove( 'is-checkout' );
                cartDOM.classList.remove( 'direct-checkout' );
                document.body.classList.remove( 'wfc-checkout-is-open' );

                if ( reset ) {
                    setStatus( 'resetting' );
                    window.setTimeout( resetCart, 500 );
                }
            }

        };

        const maybeShowCart = ( e ) => {

            let self = e.target.closest( 'a,button' );
            if ( ! self ) {
                return;
            }

            let searchParams = ( new URL( self.href ) ).searchParams;
            if ( searchParams.has( 'remove_item' ) ) {
                // we should initiate the remove from cart protocols...
                return;
            }

            e.preventDefault();
            e.stopPropagation();


            if ( getStatus() === 'resetting' ) {
                return;
            }

            if ( searchParams.has( 'add-to-cart' ) ) {
                addItem( e );
                return;
            }

            show( e );
            return true;

        };

        const showCartIfHrefMatches = ( e ) => {

            if ( ! params.options.replaceCart ) {
                return;
            }

            let self = e.target.closest( 'a' );
            if ( ! self ) {
                return;
            }

            if ( getStatus() === 'loading' ) {
                return;
            }

            let url = new URL( self.href );

            if ( url.hostname === cartUrl.hostname && maybeAddTrailingSlash( url.pathname ) === cartUrl.pathname ) {
                return maybeShowCart( e );
            }

            return;

        };

        const maybeAddTrailingSlash = ( urlString ) => {

            if ( urlString.substring( urlString.length - 1 ) === '/' ) {
                return urlString;
            }
            return urlString + '/';

        };

        const allowElementsFocus = () => {

            let focusableElements = cartDOM.querySelectorAll( '[tabindex]' );
            for ( let el of focusableElements ) {
                if ( el.dataset.tabIndex ) {
                    el.tabIndex = el.dataset.tabIndex;
                } else {
                    el.tabIndex = '';
                }
                el.disabled = false;
            }

        };

        const makeFastCartVisible = () => {
            $('[aria-controls="wc-fast-cart"]').attr( 'aria-expanded', 'true' ).prop( 'disabled', false );
            cartOverlay.setAttribute( 'aria-hidden', 'false' );
            cartOverlay.setAttribute( 'aria-hidden', 'false' );
            document.body.classList.add( `wfc-${params.options.displayMode}-is-open` );
            $('html').addClass( `wfc-lock-scrolling` );
            if ( window.innerWidth < 782 ) {
                document.documentElement.style.setProperty('margin-top', '0px', 'important');
            }
            document.dispatchEvent( new Event( 'wc-fast-cart|open' ) );
        }

        const show = ( e ) => {

            debug( 'cart is opening', params );

            if ( e ) {
                e.preventDefault();
                e.stopPropagation();
            }

            waitForRequestToFinish( cartRequest, () => {

                allowElementsFocus();

                if ( params.options.directCheckout ) {
                    openCheckout();
                } else {
                    makeFastCartVisible();
                }

            } );

            let firstElement = cartDOM.querySelector( 'button,a' );
            firstElement.focus();

        };

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

        const parseUrlParams = ( url ) => {
            let start = url.indexOf( '?' ),
                end = url.lastIndexOf( '#' );

            if ( start === -1 ) {
                return {};
            }
            if ( end === -1 ) {
                end = url.length;
            }
            let search = url.substring( start + 1, end );

            return JSON.parse('{"' + decodeURI(search).replace(/"/g, '\\"').replace(/&/g, '","').replace(/=/g,'":"') + '"}');
        };

        const maybeOpenPreviewCheckout = () => {

            let pageUrl = new URL( window.location.href );
            if ( pageUrl && pageUrl.searchParams.get( '_wfc-preview' ) === 'wfc-checkout' ) {
                let openAfterLoad = () => {
                    openCheckout();
                    $( document ).off( 'wc-fast-cart|refresh', openAfterLoad );
                };
                $( document ).on( 'wc-fast-cart|refresh', openAfterLoad );
            }

        };

        const redirectTo = ( url ) => {
            window.location.href = url;
        };

        return {
            debug,
            initialize,
            loadCheckout,
            completeCheckout,
            addItem,
            hide,
            show,
            showCart,
            getStatus,
            setStatus,
            redirectTo,
            refresh: refreshCart,
            resetScroll,
            showCartIfHrefMatches,
            processCartForm,
            getSettings: () => JSON.parse( JSON.stringify( params ) )
        };

    } )();

    // this is now happening before document load so that it can be attached before QVP loads
    if ( ! isCartPage ) {
        $body.on( 'submit', 'form.cart',FastCart.processCartForm );
    }

    jQuery( document ).ready( function() {

        FastCart.initialize();

    } );

    window.FastCart = FastCart;

} )( jQuery, window, document, wc_fast_cart_params );
