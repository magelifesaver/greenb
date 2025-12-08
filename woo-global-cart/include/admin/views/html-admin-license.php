<?php
/**
 * Admin license template
 *
 */

defined( 'ABSPATH' ) || exit;

?>

    <div class="wrap"> 
        <p>&nbsp;</p>
        <form id="form_data" name="form" method="post">
               
            <?php wp_nonce_field('woogc_licence','woogc_license_nonce'); ?>
            <input type="hidden" name="woogc_licence_form_submit" value="true" />
            
            <div class="start-container licence-key">
                <div class="text">
               
                    <h3><?php _e( "License Key", 'wp-hide-security-enhancer' ) ?></h3>
                    <div class="option">
                        <div class="controls">
                            <p><input type="text" value="" name="licence_key" class="text-input"></p>
                        </div>
                        <div class="explain"><?php _e( "Enter the Licence Key you received when purchased this product. If you lost the key, you can always retrieve it from", 'woo-global-cart' ) ?> <a href="https://wooglobalcart.com/my-account/" target="_blank"><?php _e( "My Account", 'woo-global-cart' ) ?></a></div>
                    </div>
                    <p class="submit">
                        <input type="submit" name="Submit" class="button-primary" value="<?php _e('Save', 'wp-hide-security-enhancer') ?>">
                    </p> 
                </div>
            </div>
            
            <p><span class="dashicons dashicons-flag"></span> <i> <?php _e( "Rememebr, once activated, a new login session is required. The cookies and cache data is recommended to be cleared and a browser restart might also be required", 'woo-global-cart' ) ?>. <?php _e( "More details at", 'woo-global-cart' ) ?> <a href="https://wooglobalcart.com/documentation/plugin-installation/" target="_blank"><?php _e( "Plugin Instalation", 'woo-global-cart' ) ?></a>.</i></p>
            
            
        </form> 
    </div> 