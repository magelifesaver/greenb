


    jQuery( document ).ready(function() {
        
        // Toggle buttons on/off.
        jQuery( document ).on( 'click', '#woogc_ps .woogc-input-toggle, .inline-edit-row .woogc-input-toggle', function() {
            var $link   = jQuery( this ),
                $row    = $link.closest( 'tr' ),
                $cel    = $link.closest( 'td' ),  
                $toggle = $link.find( '.woocommerce-input-toggle' );

            
            if ( $toggle.hasClass( 'woocommerce-input-toggle--disabled' ) )
                {
                    $toggle.removeClass( 'woocommerce-input-toggle--enabled woocommerce-input-toggle--disabled' );
                    $toggle.addClass( 'woocommerce-input-toggle--enabled' ); 
                    $cel.find('input.toggle_input').val('yes');
                    
                    //$link.closest( '.shop_ps' ).find('.details').removeClass('hide');
                    if ( $link.closest('table').hasClass('shop_ps_items') )
                        $link.closest('.shop_ps').find(' > .details').removeClass('hide');
                    
                }
            else if ( $toggle.hasClass( 'woocommerce-input-toggle--enabled' ) )
                {
                    $toggle.removeClass( 'woocommerce-input-toggle--enabled woocommerce-input-toggle--disabled' );
                    $toggle.addClass( 'woocommerce-input-toggle--disabled' );
                    $cel.find('input.toggle_input').val('no');
                    
                    //$link.closest( '.shop_ps' ).find('.details').addClass('hide');
                    if ( $link.closest('table').hasClass('shop_ps_items') )
                        $link.closest('.shop_ps').find(' > .details').addClass('hide');
                }    
  
            return false;
        });
        
        
    });

        