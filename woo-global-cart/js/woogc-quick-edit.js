/*global inlineEditPost, woocommerce_admin, woocommerce_quick_edit */

var woogc_ps_data   =   [];

jQuery(
    function( $ ) {
        
        $( document ).on(
            'click',
            '#the-list .editinline',
            function() {
                
                var post_id = $( this ).closest( 'tr' ).attr( 'id' );

                post_id = post_id.replace( 'post-', '' );
                woogc_ps_data.post_id   =   post_id

                jQuery('#edit-' + post_id ).find('.options_group td[colspan]').each( function() {
                    jQuery(this).removeAttr('colspan');
                })
                
                var $wc_inline_data = $( '#inline_' + post_id );
                woogc_ps_data.wc_inline_data   =   $wc_inline_data;

                var is_main_product        = $wc_inline_data.find( '._woogc_ps_is_main_product' ).text(),
                sync_to  = $wc_inline_data.find( '._woogc_ps_sync_to' ).text();
                
                jQuery('#edit-' + post_id ).find('.options_group .shop_ps_options').each( function() {
                    jQuery(this).removeAttr('colspan');
                })
                
                is_main_product         = $wc_inline_data.find( '._woogc_ps_is_main_product' ).text();
                sync_to                 = $wc_inline_data.find( '._woogc_ps_sync_to' ).text();
                maintain_child          = $wc_inline_data.find( '._woogc_ps_maintain_child' ).text();
                maintain_categories     = $wc_inline_data.find( '._woogc_ps_maintain_categories' ).text();
                maintain_stock          = $wc_inline_data.find( '._woogc_ps_maintain_stock' ).text();
                
                woogc_ps_data.is_main_product   =   is_main_product;
                
                if ( sync_to.length > 0 )
                    {
                        sync_to =   sync_to.split(",");
                        sync_to.map(e => e.trim());
                        
                        woogc_ps_data.sync_to   =   sync_to;
                    }
                    else
                    woogc_ps_data.sync_to   =   false;
                if ( maintain_child.length > 0 )
                    {
                        maintain_child =   maintain_child.split(",");
                        maintain_child.map(e => e.trim());
                        
                        woogc_ps_data.maintain_child   =   maintain_child;
                    }
                    else
                    woogc_ps_data.maintain_child   =   false;
                if ( maintain_categories.length > 0 )
                    {
                        maintain_categories =   maintain_categories.split(",");
                        maintain_categories.map(e => e.trim());
                        
                        woogc_ps_data.maintain_categories   =   maintain_categories;
                    }
                    else
                    woogc_ps_data.maintain_categories   =   false;
                if ( maintain_stock.length > 0 )
                    {
                        maintain_stock =   maintain_stock.split(",");
                        maintain_stock.map(e => e.trim());
                        
                        woogc_ps_data.maintain_stock   =   maintain_stock;
                    }
                    else
                    woogc_ps_data.maintain_stock   =   false;
                
                jQuery('#edit-' + post_id ).find('.options_group input.toggle_input').each( function() {
                    
                    jQuery(this).val('no');
                    
                    var input_name  =   jQuery(this).attr("name");
                    
                    var blog_id;
                    regex = /([\d]+)/gmi;
                    if ((m = regex.exec(input_name)) !== null) {
                        // The result can be accessed through the `m`-variable.
                        blog_id =   m[0];
                    }

                    regex = /\[[\d]+\]/gmi;
                    input_name  =   input_name.replace( regex, '');
                    
                    switch ( input_name )
                        {
                            case '_woogc_ps_sync_to'    :
                                                            if ( woogc_ps_data.sync_to !== false && woogc_ps_data.sync_to.includes( blog_id  ))
                                                                jQuery(this).val('yes');
                                                            break;   
                            case '_woogc_ps_maintain_child'    :
                                                            if ( woogc_ps_data.maintain_child !== false && woogc_ps_data.maintain_child.includes( blog_id  ))
                                                                jQuery(this).val('yes');
                                                            break;
                            case '_woogc_ps_maintain_categories'    :
                                                            if ( woogc_ps_data.maintain_categories !== false && woogc_ps_data.maintain_categories.includes( blog_id  ))
                                                                jQuery(this).val('yes');
                                                            break;
                            case '_woogc_ps_maintain_stock'    :
                                                            if ( woogc_ps_data.maintain_stock !== false && woogc_ps_data.maintain_stock.includes( blog_id  ))
                                                                jQuery(this).val('yes');
                                                            break;
                        }
                    
                    //refresh the fancy toggle
                    $toggle = jQuery(this).parent().find('.woocommerce-input-toggle');
                    $toggle.removeClass( 'woocommerce-input-toggle--enabled woocommerce-input-toggle--disabled' );
                    
                    if ( jQuery(this).val() ==  'yes' )
                        $toggle.addClass( 'woocommerce-input-toggle--enabled' );
                        else
                        $toggle.addClass( 'woocommerce-input-toggle--disabled' );
                        
                    if ( jQuery( this ).closest('table').hasClass('shop_ps_items') )
                        {
                            if ( jQuery(this).val() ==  'yes' )
                                jQuery( this ).closest('.shop_ps').find(' > .details').removeClass('hide');
                                else
                                jQuery( this ).closest('.shop_ps').find(' > .details').addClass('hide');
                        }
                        
                })
                
                //if child, hide other blogs from the selection
                if ( woogc_ps_data.is_main_product  ==  'no' )
                    {
                        $parent_blog_id =   woogc_ps_data.wc_inline_data.find( '._woogc_ps_parent_bid' ).text();;   
                        jQuery('#edit-' + post_id ).find('.options_group .shop_ps').filter(':not([data-blog-id=' + $parent_blog_id +'])').addClass('hide');
                    }
               
            }
        );
        
        
        $( document ).on(
            'click',
            '#wpbody #doaction, #wpbody #doaction2',
            function() {
                
                jQuery('#bulk-edit').find('#woogc-fields .options_group td[colspan]').each( function() {
                    jQuery(this).removeAttr('colspan');
                })
                
                $( '#woogc-fields select').prop( 'selectedIndex', 0 );
                
            }
        );

     
    }
);

