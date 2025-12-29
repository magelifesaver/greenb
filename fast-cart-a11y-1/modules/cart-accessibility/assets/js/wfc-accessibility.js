// Accessibility enhancements for the Barn2 WooCommerce Fast Cart.
//
// Barn2's Fast Cart inserts a `.wc-fast-cart__page-overlay` element into the
// document when the cart is initialised. This overlay wraps the cart
// `<aside id="wc-fast-cart">` and toggles its `aria-hidden` attribute
// between `true` (cart hidden) and `false` (cart visible). When the cart
// becomes visible, this script makes the rest of the page inert and
// hidden from screen readers, shifts focus into the cart, and stores
// the previously focused element to restore it when the cart closes.

(function() {
    'use strict';

    const overlaySelector      = '.wc-fast-cart__page-overlay';
    const overlayBgSelector    = '.wc-fast-cart__page-overlay-background';
    let previousFocus          = null;

    /**
     * Set or remove inert and aria-hidden attributes on the page elements.
     *
     * @param {boolean} inert Whether to apply inert to the page.
     */
    const setPageInert = ( inert ) => {
        Array.from( document.body.children ).forEach( el => {
            if ( el.matches( overlaySelector ) || el.matches( overlayBgSelector ) ) {
                // Never make the overlay or its background inert.
                el.removeAttribute( 'inert' );
                el.setAttribute( 'aria-hidden', 'false' );
                return;
            }
            if ( inert ) {
                el.setAttribute( 'inert', '' );
                el.setAttribute( 'aria-hidden', 'true' );
            } else {
                el.removeAttribute( 'inert' );
                el.removeAttribute( 'aria-hidden' );
            }
        } );
    };

    /**
     * Respond to the overlay's `aria-hidden` changes.
     *
     * @param {HTMLElement} overlay
     */
    const handleOverlay = ( overlay ) => {
        // Initialise based on current state.
        if ( overlay.getAttribute( 'aria-hidden' ) === 'false' ) {
            previousFocus = document.activeElement;
            setPageInert( true );
            const cart = overlay.querySelector( '#wc-fast-cart' );
            if ( cart ) {
                const target = cart.querySelector( 'button,a,[tabindex]:not([tabindex="-1"])' );
                if ( target ) {
                    target.focus();
                }
            }
        }
        // Observe future changes.
        const observer = new MutationObserver( mutations => {
            mutations.forEach( mutation => {
                if ( mutation.attributeName === 'aria-hidden' ) {
                    const isHidden = overlay.getAttribute( 'aria-hidden' ) === 'true';
                    if ( ! isHidden ) {
                        previousFocus = document.activeElement;
                        setPageInert( true );
                        const cart = overlay.querySelector( '#wc-fast-cart' );
                        if ( cart ) {
                            const first = cart.querySelector( 'button,a,[tabindex]:not([tabindex="-1"])' );
                            if ( first ) {
                                first.focus();
                            }
                        }
                    } else {
                        setPageInert( false );
                        if ( previousFocus && typeof previousFocus.focus === 'function' ) {
                            try {
                                previousFocus.focus();
                            } catch ( e ) {
                                /* ignore focus errors */
                            }
                        }
                        previousFocus = null;
                    }
                }
            } );
        } );
        observer.observe( overlay, { attributes: true, attributeFilter: [ 'aria-hidden' ] } );
    };

    /**
     * Wait for the overlay to be inserted into the DOM, then attach observers.
     */
    const watchOverlay = () => {
        const overlay = document.querySelector( overlaySelector );
        if ( overlay ) {
            handleOverlay( overlay );
        } else {
            // Observe body for overlay insertion.
            const bodyObserver = new MutationObserver( () => {
                const ov = document.querySelector( overlaySelector );
                if ( ov ) {
                    bodyObserver.disconnect();
                    handleOverlay( ov );
                }
            } );
            bodyObserver.observe( document.body, { childList: true, subtree: true } );
        }
    };

    document.addEventListener( 'DOMContentLoaded', watchOverlay );
})();