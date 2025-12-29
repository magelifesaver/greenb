// Accessibility helpers for Fast Cart. See accompanying PHP for details.
/**
 * Accessibility helpers for both Fast Cart plugins (WPXtension and Barn2).
 *
 * This script watches for the cart drawer or overlay elements created by
 * different Fast Cart implementations. When the cart is open, it makes
 * the rest of the page inert and hides it from screen readers. When the
 * cart closes, the inert state is removed and focus is restored to
 * whichever element had focus before the cart opened. The script also
 * respects keyboard navigation inside the cart for the WPXtension
 * implementation, while relying on the built-in focus trapping of the
 * Barn2 plugin.
 */
(function() {
    'use strict';

    // Keep track of the previously focused element so we can restore it
    // when the cart closes.
    let previousFocus = null;

    /**
     * Fast Cart (WPXtension) handlers
     *
     * The WPXtension implementation uses a `.fc-container` element and
     * toggles the `loaded` class to indicate whether the cart is open.
     */
    const fcSelector = '.fc-container';
    const getFcContainer = () => document.querySelector( fcSelector );

    // Return all top-level page elements except the Fast Cart container.
    const getPageElements = () => Array.from( document.body.children ).filter( el => ! el.classList.contains( 'fc-container' ) );

    /**
     * Disable the rest of the page (WPXtension).
     */
    const disableFcPage = () => {
        const container = getFcContainer();
        if ( ! container ) {
            return;
        }
        getPageElements().forEach( el => {
            el.setAttribute( 'inert', '' );
            el.setAttribute( 'aria-hidden', 'true' );
        } );
        container.removeAttribute( 'inert' );
        container.setAttribute( 'aria-hidden', 'false' );
    };

    /**
     * Enable the rest of the page (WPXtension).
     */
    const enableFcPage = () => {
        const container = getFcContainer();
        if ( ! container ) {
            return;
        }
        getPageElements().forEach( el => {
            el.removeAttribute( 'inert' );
            el.removeAttribute( 'aria-hidden' );
        } );
        // Hide the cart for screen readers until it opens again.
        container.setAttribute( 'inert', '' );
        container.setAttribute( 'aria-hidden', 'true' );
    };

    /**
     * Trap keyboard focus within the cart container (WPXtension).
     * This replicates the behaviour of a modal dialog.
     *
     * @param {HTMLElement} container The Fast Cart container.
     */
    const trapFcFocus = ( container ) => {
        const focusableSelector = 'a[href],area[href],input:not([disabled]),select:not([disabled]),textarea:not([disabled]),button:not([disabled]),[tabindex]:not([tabindex="-1"])';
        const nodes = container.querySelectorAll( focusableSelector );
        if ( ! nodes.length ) {
            return;
        }
        const first = nodes[0];
        const last  = nodes[nodes.length - 1];
        container.addEventListener( 'keydown', ( e ) => {
            if ( e.key !== 'Tab' ) {
                return;
            }
            if ( e.shiftKey ) {
                if ( document.activeElement === first ) {
                    e.preventDefault();
                    last.focus();
                }
            } else {
                if ( document.activeElement === last ) {
                    e.preventDefault();
                    first.focus();
                }
            }
        } );
    };

    /**
     * Open the WPXtension cart: make page inert, move focus inside the cart and trap it.
     */
    const openFcCart = () => {
        const container = getFcContainer();
        if ( ! container ) {
            return;
        }
        previousFocus = document.activeElement;
        disableFcPage();
        const first = container.querySelector( 'button,a,input,[tabindex]:not([tabindex="-1"])' );
        if ( first ) {
            first.focus();
        }
        trapFcFocus( container );
    };

    /**
     * Close the WPXtension cart: remove inert attributes and restore focus.
     */
    const closeFcCart = () => {
        enableFcPage();
        if ( previousFocus && typeof previousFocus.focus === 'function' ) {
            try {
                previousFocus.focus();
            } catch ( ex ) {
                // Ignore errors when restoring focus.
            }
        }
        previousFocus = null;
    };

    /**
     * Attach observers for the WPXtension implementation.
     */
    const watchFcCart = () => {
        const container = getFcContainer();
        if ( ! container ) {
            return;
        }
        // Set initial state.
        if ( container.classList.contains( 'loaded' ) ) {
            openFcCart();
        } else {
            enableFcPage();
        }
        // Observe class changes to detect when the cart opens/closes.
        const observer = new MutationObserver( ( mutations ) => {
            mutations.forEach( ( mutation ) => {
                if ( mutation.type === 'attributes' && mutation.attributeName === 'class' ) {
                    if ( container.classList.contains( 'loaded' ) ) {
                        openFcCart();
                    } else {
                        closeFcCart();
                    }
                }
            } );
        } );
        observer.observe( container, { attributes: true, attributeFilter: [ 'class' ] } );
        // Close when ESC is pressed.
        document.addEventListener( 'keydown', ( e ) => {
            if ( e.key === 'Escape' ) {
                const cInst = getFcContainer();
                if ( cInst && cInst.classList.contains( 'loaded' ) ) {
                    // Trigger the built-in close mechanism.
                    if ( window.jQuery ) {
                        window.jQuery( cInst ).trigger( 'click' );
                    } else {
                        cInst.classList.remove( 'loaded' );
                    }
                }
            }
        } );
    };

    /**
     * Barn2 Fast Cart handlers
     *
     * Barn2's implementation inserts a `.wc-fast-cart__page-overlay` element. The
     * overlay wraps the `<aside id="wc-fast-cart">` cart. The overlay
     * toggles its `aria-hidden` attribute between `true` (closed) and
     * `false` (open). When the cart is open, we make the rest of the page
     * inert and hide it from screen readers; when closed we revert this.
     */
    const wfcOverlaySelector = '.wc-fast-cart__page-overlay';
    const wfcOverlayBackgroundSelector = '.wc-fast-cart__page-overlay-background';

    /**
     * Make all non-cart elements inert for the Barn2 implementation.
     *
     * @param {boolean} inert Whether to set or remove inert state on the page.
     */
    const setWfcPageInert = ( inert ) => {
        Array.from( document.body.children ).forEach( ( el ) => {
            if ( el.matches( wfcOverlaySelector ) || el.matches( wfcOverlayBackgroundSelector ) ) {
                // Never make the overlay or its background inert.
                el.removeAttribute( 'inert' );
                el.setAttribute( 'aria-hidden', inert ? 'false' : 'false' );
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
     * Handle changes to the overlay's `aria-hidden` attribute for Barn2 Fast Cart.
     *
     * @param {HTMLElement} overlay The overlay element.
     */
    const observeWfcOverlay = ( overlay ) => {
        // Set initial inert state based on current `aria-hidden` value.
        if ( overlay.getAttribute( 'aria-hidden' ) === 'false' ) {
            previousFocus = document.activeElement;
            setWfcPageInert( true );
            // Move focus into the cart if possible.
            const cart = overlay.querySelector( '#wc-fast-cart' );
            if ( cart ) {
                const target = cart.querySelector( 'button,a,[tabindex]:not([tabindex="-1"])' );
                if ( target ) {
                    target.focus();
                }
            }
        }
        const overlayObserver = new MutationObserver( ( mutations ) => {
            mutations.forEach( ( mutation ) => {
                if ( mutation.type === 'attributes' && mutation.attributeName === 'aria-hidden' ) {
                    const isHidden = overlay.getAttribute( 'aria-hidden' ) === 'true';
                    if ( ! isHidden ) {
                        // Overlay open: disable page and focus cart.
                        previousFocus = document.activeElement;
                        setWfcPageInert( true );
                        const cart = overlay.querySelector( '#wc-fast-cart' );
                        if ( cart ) {
                            const firstFocusable = cart.querySelector( 'button,a,[tabindex]:not([tabindex="-1"])' );
                            if ( firstFocusable ) {
                                firstFocusable.focus();
                            }
                        }
                    } else {
                        // Overlay closed: enable page and restore focus.
                        setWfcPageInert( false );
                        if ( previousFocus && typeof previousFocus.focus === 'function' ) {
                            try {
                                previousFocus.focus();
                            } catch ( e ) {
                                // ignore
                            }
                        }
                        previousFocus = null;
                    }
                }
            } );
        } );
        overlayObserver.observe( overlay, { attributes: true, attributeFilter: [ 'aria-hidden' ] } );
    };

    /**
     * Watch for the Barn2 Fast Cart overlay and attach observers when it appears.
     */
    const watchWfcCart = () => {
        const overlay = document.querySelector( wfcOverlaySelector );
        if ( overlay ) {
            observeWfcOverlay( overlay );
        } else {
            // The overlay may be injected after DOM ready, so observe the body.
            const bodyObserver = new MutationObserver( () => {
                const ov = document.querySelector( wfcOverlaySelector );
                if ( ov ) {
                    bodyObserver.disconnect();
                    observeWfcOverlay( ov );
                }
            } );
            bodyObserver.observe( document.body, { childList: true, subtree: true } );
        }
    };

    // Initialise watchers once the DOM is ready.
    document.addEventListener( 'DOMContentLoaded', () => {
        // WPXtension Fast Cart.
        if ( document.querySelector( fcSelector ) ) {
            watchFcCart();
        }
        // Barn2 Fast Cart.
        watchWfcCart();
    } );
})();