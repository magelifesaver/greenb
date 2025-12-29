// Accessibility helper for Barn2's WooCommerce Fast Cart.
//
// This version is deliberately conservative. It listens for Fast Cart’s
// custom events (`wc-fast-cart|open` and `wc-fast-cart|close`) and
// applies `aria-hidden` to the rest of the page when the cart is
// visible. It does not set the `inert` attribute, which can freeze
// unrelated UI elements or break third‑party scripts. When the cart
// closes, `aria-hidden` is removed and focus is restored to the
// previously active element. A small MutationObserver fallback handles
// older versions of Fast Cart that may not dispatch the events.

( function () {
    'use strict';

    const overlaySel   = '.wc-fast-cart__page-overlay';
    const overlayBgSel = '.wc-fast-cart__page-overlay-background';
    let previousFocus = null;

    /**
     * Toggle aria-hidden on all body children except the overlay and its background.
     * This hides the rest of the page from screen readers without affecting
     * pointer events or script execution.
     *
     * @param {boolean} hide Whether to hide or show the rest of the page
     */
    const setPageHidden = ( hide ) => {
        const children = Array.from( document.body.children );
        children.forEach( el => {
            if ( el.matches( overlaySel ) || el.matches( overlayBgSel ) ) {
                el.setAttribute( 'aria-hidden', 'false' );
                return;
            }
            if ( hide ) {
                el.setAttribute( 'aria-hidden', 'true' );
            } else {
                el.removeAttribute( 'aria-hidden' );
            }
        } );
    };

    const onOpen = () => {
        previousFocus = document.activeElement;
        setPageHidden( true );
        // Rely on Fast Cart’s internal focus trap for keyboard navigation.
    };

    const onClose = () => {
        setPageHidden( false );
        if ( previousFocus && typeof previousFocus.focus === 'function' ) {
            try { previousFocus.focus(); } catch ( e ) {}
        }
        previousFocus = null;
    };

    /**
     * Attach a MutationObserver to watch the overlay’s aria-hidden attribute.
     * If the attribute changes to `false`, call onOpen. If it changes back
     * to `true`, call onClose. This is a fallback for older plugin versions.
     *
     * @param {HTMLElement} overlay The overlay element
     */
    const watchOverlay = ( overlay ) => {
        if ( overlay.getAttribute( 'aria-hidden' ) === 'false' ) {
            onOpen();
        }
        const obs = new MutationObserver( mutations => {
            mutations.forEach( m => {
                if ( m.attributeName === 'aria-hidden' ) {
                    const isHidden = overlay.getAttribute( 'aria-hidden' ) === 'true';
                    isHidden ? onClose() : onOpen();
                }
            } );
        } );
        obs.observe( overlay, { attributes: true, attributeFilter: [ 'aria-hidden' ] } );
    };

    /**
     * Set up event listeners and fallback observers. Runs on DOMContentLoaded.
     */
    const init = () => {
        // Listen for the custom open/close events dispatched by Fast Cart.
        document.addEventListener( 'wc-fast-cart|open', onOpen );
        document.addEventListener( 'wc-fast-cart|close', onClose );
        // Fallback: watch for overlay attribute changes.
        const overlay = document.querySelector( overlaySel );
        if ( overlay ) {
            watchOverlay( overlay );
        } else {
            const bodyObserver = new MutationObserver( () => {
                const ov = document.querySelector( overlaySel );
                if ( ov ) {
                    bodyObserver.disconnect();
                    watchOverlay( ov );
                }
            } );
            bodyObserver.observe( document.body, { childList: true, subtree: true } );
        }
    };

    if ( document.readyState === 'loading' ) {
        document.addEventListener( 'DOMContentLoaded', init );
    } else {
        init();
    }
} )();