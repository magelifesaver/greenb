// Accessibility enhancements for Barn2's WooCommerce Fast Cart.
//
// This script listens for the custom events `wc-fast-cart|open` and
// `wc-fast-cart|close` that Barn2's Fast Cart dispatches when the cart
// drawer opens and closes. When the cart opens the rest of the page
// becomes inert and hidden from assistive technologies. When the cart
// closes the inert state is removed and focus is restored to the
// element that was active before the cart opened. As a fallback, the
// script also watches the overlay's `aria-hidden` attribute in case
// the events are not fired (e.g. older versions of Fast Cart). The
// code is kept concise (<150 lines) to comply with the wide‑and‑thin
// architecture guidelines.

( function () {
    'use strict';

    // CSS selectors used by Fast Cart. The overlay contains the cart and
    // toggles its `aria-hidden` attribute between `true` and `false`.
    const overlaySel   = '.wc-fast-cart__page-overlay';
    const overlayBgSel = '.wc-fast-cart__page-overlay-background';

    // Track the element that had focus before the cart was opened so
    // focus can be restored when the cart closes.
    let previousFocus = null;

    /**
     * Apply or remove inert and aria-hidden on the rest of the page.
     *
     * When `apply` is true, all direct children of `body` except the cart
     * overlay and its background are made inert and hidden from screen
     * readers. Certain tags (script, style, link, meta) are ignored.
     *
     * @param {boolean} apply Whether to apply inert.
     */
    const setPageInert = ( apply ) => {
        const children = Array.from( document.body.children );
        children.forEach( el => {
            // Skip the overlay and its background; these remain interactive
            if ( el.matches( overlaySel ) || el.matches( overlayBgSel ) ) {
                el.removeAttribute( 'inert' );
                el.setAttribute( 'aria-hidden', 'false' );
                return;
            }
            // Skip non-visual elements which should not be made inert.
            const tag = el.tagName;
            if ( tag === 'SCRIPT' || tag === 'STYLE' || tag === 'LINK' || tag === 'META' ) {
                return;
            }
            if ( apply ) {
                el.setAttribute( 'inert', '' );
                el.setAttribute( 'aria-hidden', 'true' );
            } else {
                el.removeAttribute( 'inert' );
                el.removeAttribute( 'aria-hidden' );
            }
        } );
    };

    /**
     * Called when the cart opens. Stores the current focus and applies inert.
     */
    const handleOpen = () => {
        previousFocus = document.activeElement;
        setPageInert( true );
        // Focus trapping within the cart is handled by Fast Cart itself.
    };

    /**
     * Called when the cart closes. Removes inert and restores focus.
     */
    const handleClose = () => {
        setPageInert( false );
        if ( previousFocus && typeof previousFocus.focus === 'function' ) {
            try {
                previousFocus.focus();
            } catch ( e ) {
                // Ignore focus errors
            }
        }
        previousFocus = null;
    };

    /**
     * Attach a MutationObserver to the overlay to monitor its `aria-hidden`
     * attribute. If `aria-hidden` changes to `false`, the cart has opened;
     * if it changes to `true`, the cart has closed.
     *
     * @param {HTMLElement} overlay The overlay element
     */
    const observeOverlay = ( overlay ) => {
        // Initialise state based on current attribute value.
        if ( overlay.getAttribute( 'aria-hidden' ) === 'false' ) {
            handleOpen();
        }
        const obs = new MutationObserver( mutations => {
            mutations.forEach( m => {
                if ( m.attributeName === 'aria-hidden' ) {
                    overlay.getAttribute( 'aria-hidden' ) === 'false' ? handleOpen() : handleClose();
                }
            } );
        } );
        obs.observe( overlay, { attributes: true, attributeFilter: [ 'aria-hidden' ] } );
    };

    /**
     * Initialise listeners and fallback observers. This runs on
     * DOMContentLoaded to ensure the body exists.
     */
    const init = () => {
        // Add listeners for Fast Cart’s custom events if they are fired.
        document.addEventListener( 'wc-fast-cart|open', handleOpen );
        document.addEventListener( 'wc-fast-cart|close', handleClose );

        // Fallback: observe the overlay’s aria-hidden attribute in case
        // custom events aren’t dispatched (older plugin versions).
        const overlay = document.querySelector( overlaySel );
        if ( overlay ) {
            observeOverlay( overlay );
        } else {
            // Watch for the overlay to be inserted into the DOM.
            const bodyObserver = new MutationObserver( () => {
                const ov = document.querySelector( overlaySel );
                if ( ov ) {
                    bodyObserver.disconnect();
                    observeOverlay( ov );
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