        
                
    jQuery( document ).ready(function() {
         
        jQuery( '#cart_checkout_type select' ).on( 'change', function () {
            
            var remove_classes = jQuery('#form_data.options').attr("class").split(' ');
            jQuery.each( remove_classes, function( i, val ) { 
                if ( val.match(/checkout_type_(\w+)/) )
                    jQuery('#form_data.options').removeClass( val );
            })
            
            if ( this.value  == 'single_checkout')
                jQuery('#form_data.options').addClass('checkout_type_single_checkout');
            if ( this.value  == 'each_store')
                jQuery('#form_data.options').addClass('checkout_type_each_store');
                    
        });
        
    });
        
        
        
    class WoGC_Class {
            
            constructor() {
               
                this.relocateNotices();                  
            }
            
            relocateNotices() {
                
                document.addEventListener('DOMContentLoaded', function() {
                    const notices = document.querySelectorAll('.notice');

                    const targetDiv = document.getElementById('wpgc-notices');

                    notices.forEach(notice => {
                        if (targetDiv) {
                            targetDiv.appendChild(notice);
                        }
                    });
                });

            }
            
            
            click_action( element_type, el ) {
                switch ( element_type ) {
                    
                    case 'synchronization_for_shops'    :
                                                            var el_blog_id   =   jQuery( el ).data('shop_id');
                                                            
                                                            //esnure the option is not selected in the order_synchronization_to_shop
                                                            var selected_sts    =   jQuery( "input[name='order_synchronization_to_shop']:checked").val();
                                                            if ( selected_sts   ==  el_blog_id )
                                                                jQuery ( el ).prop( 'checked', false );
                                                            
                                                            break;
                    
                    case 'synchronization_to_shop'    :
                                                            var el_blog_id   =   jQuery( el ).data('shop_id');
                                                            
                                                            //esnure the option is not selected in the synchronization_for_shops
                                                            var selected_sts    =   jQuery( "input[name='order_synchronization_for_shops\\[" + el_blog_id + "\\]']");
                                                            if ( selected_sts )
                                                                {
                                                                    jQuery("#order_synchronization_for_shops input").prop( 'disabled', false );
                                                                    
                                                                    jQuery ( selected_sts ).prop( 'checked', false );
                                                                    jQuery ( selected_sts ).prop( 'disabled', true );
                                                                }
                                                            break;
                    
                }
                    
            }
    }
        
    var WoGC = new WoGC_Class();