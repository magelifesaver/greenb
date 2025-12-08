<?php
/**
 * Admin View: Bulk Edit Products
 */

defined( 'ABSPATH' ) || exit;

global $WooGC;

?>

<fieldset class="inline-edit-col">
    <div id="woogc-fields" class="inline-edit-col">

        <h4><?php esc_html_e( 'Product Synchronization', 'woo-global-cart' ); ?></h4>

            <div id="woogc_ps" class="panel woocommerce_options_panel">
                <p><?php _e( "In this section, you can control the synchronisation shops and options.", 'woo-global-cart' ) ?></p>
                
                <div class="options_group">
                    
                    <?php
                        
                        global $blog_id, $WooGC;
                        
                        $sites  =   $WooGC->functions->get_gc_sites( TRUE );
                        $sites  =   apply_filters( 'woogc/ps/interfaces/synchronize_to_sites', $sites );
                        
                        $first  =   TRUE;
                        
                        foreach ( $sites    as  $site )
                            {
                                if ( $site->blog_id ==  $blog_id )
                                    continue;
                                                
                                $blog_details = get_blog_details( $site->blog_id );
                                
                                ?>
                                <div class="shop_ps" data-blog-id="<?php echo $site->blog_id ?>">
                                    <table class="shop_ps_items">
                                        <?php 
                                        if ( $first ) {
                                            $first  =   FALSE;
                                        ?>
                                        <thead>
                                            <tr>
                
                                                <th class="shop_title"><?php _e( "Shop Title", 'woo-global-cart' ) ?></th>
                                                <th><?php _e( "Enable Synchronization", 'woo-global-cart' ) ?></th>
                                            </tr>            
                                        </thead>
                                        <?php } ?>
                                        <tbody>
                                            <tr class="even">
                           
                                                <td class="shop_title">
                                                    <h4><?php echo  $blog_details->blogname ?></h4><small class="site-url"><?php echo  $blog_details->domain ?></small> 
                                                </td>
                                                <td class="holder">
                                                    <select name="_woogc_ps_sync_to[<?php echo $blog_details->blog_id ?>]">
                                                        <option value="">— <?php _e( "No change", 'woo-global-cart' ) ?> —</option>
                                                        <option value="yes"><?php _e( "Yes", 'woo-global-cart' ) ?></option>
                                                        <option value="no"><?php _e( "No", 'woo-global-cart' ) ?></option>
                                                    </select>                                                    
                                                </td>
                                            </tr>
                                               
                                        </tbody>
                                    </table>
                                
                                    <div class="details">
                                        
                                        <table class="shop_ps_options">
                                            <tbody>
                                                <tr>
                               
                                                    <td class="option_title"></td>
                                                    <td>
                                                        <?php _e( "Maintain Child Product Synchronization", 'woo-global-cart' ) ?><br />
                                                        <select name="_woogc_ps_maintain_child[<?php echo $blog_details->blog_id ?>]">
                                                            <option value="">— <?php _e( "No change", 'woo-global-cart' ) ?> —</option>
                                                            <option value="yes"><?php _e( "Yes", 'woo-global-cart' ) ?></option>
                                                            <option value="no"><?php _e( "No", 'woo-global-cart' ) ?></option>
                                                        </select>
                                                        <br /><small><?php _e( "When the current Product change, the Child Product get updated as well.", 'woo-global-cart' ) ?></small>
                                                    </td>
                                                </tr>
                                                <tr>
                               
                                                    <td class="option_title"></td>
                                                    <td>
                                                        <?php _e( "Categories and Tags Synchronization", 'woo-global-cart' ) ?><br />
                                                        <select name="_woogc_ps_maintain_categories[<?php echo $blog_details->blog_id ?>]">
                                                            <option value="">— <?php _e( "No change", 'woo-global-cart' ) ?> —</option>
                                                            <option value="yes"><?php _e( "Yes", 'woo-global-cart' ) ?></option>
                                                            <option value="no"><?php _e( "No", 'woo-global-cart' ) ?></option>
                                                        </select>
                                                        <br /><small><?php _e( "Synchronize the product categories and terms. If the term is not found on the child store, it will be created.", 'woo-global-cart' ) ?></small>
                                                    </td>
                                                </tr>
                                                <tr>
                               
                                                    <td class="option_title"></td>
                                                    <td>
                                                        <?php _e( "Stock Synchronization", 'woo-global-cart' ) ?><br />
                                                        <select name="_woogc_ps_maintain_stock[<?php echo $blog_details->blog_id ?>]">
                                                            <option value="">— <?php _e( "No change", 'woo-global-cart' ) ?> —</option>
                                                            <option value="yes"><?php _e( "Yes", 'woo-global-cart' ) ?></option>
                                                            <option value="no"><?php _e( "No", 'woo-global-cart' ) ?></option>
                                                        </select>
                                                        <br /><small><?php _e( "Any stock changes on the products across the network using \"Stock Synchronization\", updates others stock too.", 'woo-global-cart' ) ?></small>
                                                    </td>
                                                </tr>
                                                   
                                            </tbody>
                                        </table>
               
                                    </div>
                                </div>    
                                <?php

                            } 
                    
                    ?>
                   
                </div>
            </div>   
    </div>
</fieldset>

