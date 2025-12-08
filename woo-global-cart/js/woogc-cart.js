

    jQuery( function( $ ) {
    
        // wc_cart_params is required to continue, ensure the object exists
        if ( typeof wc_cart_params === 'undefined' ) {
            return false;
        }

        // Utility functions for the file.

        /**
         * Gets a url for a given AJAX endpoint.
         *
         * @param {String} endpoint The AJAX Endpoint
         * @return {String} The URL to use for the request
         */
        var get_url = function( endpoint ) {
            return wc_cart_params.wc_ajax_url.toString().replace(
                '%%endpoint%%',
                endpoint
            );
        };        
        
        /**
         * Check if a node is blocked for processing.
         *
         * @param {JQuery Object} $node
         * @return {bool} True if the DOM Element is UI Blocked, false if not.
         */
        var is_blocked = function( $node ) {
            return $node.is( '.processing' ) || $node.parents( '.processing' ).length;
        };

        /**
         * Block a node visually for processing.
         *
         * @param {JQuery Object} $node
         */
        var block = function( $node ) {
            if ( ! is_blocked( $node ) ) {
                $node.addClass( 'processing' ).block( {
                    message: null,
                    overlayCSS: {
                        background: '#fff',
                        opacity: 0.6
                    }
                } );
            }
        };

        /**
         * Unblock a node after processing is complete.
         *
         * @param {JQuery Object} $node
         */
        var unblock = function( $node ) {
            $node.removeClass( 'processing' ).unblock();
        };
        
        function woogc_cart_unbinds()
            {
                jQuery( document ).off( 'change', 'select.shipping_method, :input[name^=shipping_method]' );
                jQuery( document ).on( 'change', 'select.shipping_method, :input[name^=shipping_method]', woogc_cart_shipping_change );
            }
            
            
        function woogc_cart_shipping_change()
            {

                var shipping_methods = {};

                // eslint-disable-next-line max-len
                /*
                jQuery( 'select.shipping_method, :input[name^=shipping_method][type=radio]:checked, :input[name^=shipping_method][type=hidden]' ).each( function() {
                    shipping_methods[ jQuery( this ).data( 'index' ) ] = jQuery( this ).val();
                } );
                */
                jQuery( 'select.shipping_method, input[name^="shipping_method"][type="radio"]:checked, input[name^="shipping_method"][type="hidden"]' ).each( function() {
                    if ( jQuery( this ).data( 'blog_id' )  > 0 )
                        {
                            if( typeof shipping_methods[ jQuery( this ).data( 'index' ) ] === 'undefined' )
                                shipping_methods[ jQuery( this ).data( 'index' ) ]   =   {};
                            shipping_methods[ jQuery( this ).data( 'index' ) ][ jQuery( this ).data( 'blog_id' ) ] = jQuery( this ).val();
                        }
                        else
                        shipping_methods[ jQuery( this ).data( 'index' ) ] = jQuery( this ).val();
                } );

                block( jQuery( 'div.cart_totals' ) );

                var data = {
                    security: wc_cart_params.update_shipping_method_nonce,
                    shipping_method: shipping_methods
                };

                jQuery.ajax( {
                    type:     'post',
                    url:      get_url( 'update_shipping_method' ),
                    data:     data,
                    dataType: 'html',
                    success:  function( response ) {
                        update_cart_totals_div( response );
                    },
                    complete: function() {
                        unblock( jQuery( 'div.cart_totals' ) );
                        jQuery( document.body ).trigger( 'updated_shipping_method' );
                    }
                } );
            }
            
            
        /**
         * Update the .cart_totals div with a string of html.
         *
         * @param {String} html_str The HTML string with which to replace the div.
         */
        var update_cart_totals_div = function( html_str ) {
            jQuery( '.cart_totals' ).replaceWith( html_str );
            jQuery( document.body ).trigger( 'updated_cart_totals' );
        };
            
        
        jQuery(document).ready(function() {
            woogc_cart_unbinds();
        });
        
    })