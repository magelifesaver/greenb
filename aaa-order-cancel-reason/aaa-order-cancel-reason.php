<?php
/**
 * Plugin Name: AAA Order Cancel Reason
 * Description: Require admins to provide a cancellation reason + explanation when cancelling orders. Includes settings, reporting, and daily email.
 * Version: 2.3.2
 * Author: Webmaster
 * Text Domain: aaa-order-cancel-reason
 *
 * File Path: wp-content/plugins/aaa-order-cancel-reason/aaa-order-cancel-reason.php
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * 1. Settings Page
 */
add_action( 'admin_menu', 'aaa_cancel_reason_add_settings_page' );
function aaa_cancel_reason_add_settings_page() {
    add_submenu_page(
        'woocommerce',
        __( 'Cancellation Settings', 'aaa-order-cancel-reason' ),
        __( 'Cancellation Settings', 'aaa-order-cancel-reason' ),
        'manage_woocommerce',
        'aaa-cancellation-settings',
        'aaa_cancel_reason_render_settings_page'
    );
}

function aaa_cancel_reason_render_settings_page() {
    if ( ! current_user_can( 'manage_woocommerce' ) ) return;

    // Save canned reasons
    if ( isset($_POST['aaa_cancel_reasons_nonce']) && wp_verify_nonce($_POST['aaa_cancel_reasons_nonce'], 'aaa_save_cancel_reasons') ) {
        $input  = isset($_POST['aaa_canned_reasons']) ? trim($_POST['aaa_canned_reasons']) : '';
        $lines  = array_filter(array_map('sanitize_text_field', explode("\n", $input)));
        update_option('aaa_cancel_canned_reasons', $lines);
        echo '<div class="notice notice-success"><p>' . __('Reasons saved.', 'aaa-order-cancel-reason') . '</p></div>';
    }

    // Save email recipients
    if ( isset($_POST['aaa_email_recipients_nonce']) && wp_verify_nonce($_POST['aaa_email_recipients_nonce'], 'aaa_save_email_recipients') ) {
        $input  = isset($_POST['aaa_email_recipients']) ? trim($_POST['aaa_email_recipients']) : '';
        $lines  = array_filter(array_map('sanitize_email', explode("\n", $input)), 'is_email');
        update_option('aaa_cancel_email_recipients', $lines);
        echo '<div class="notice notice-success"><p>' . __('Recipients saved.', 'aaa-order-cancel-reason') . '</p></div>';
    }

    $canned = get_option('aaa_cancel_canned_reasons', array());
    $emails = get_option('aaa_cancel_email_recipients', array());
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Cancellation Settings', 'aaa-order-cancel-reason'); ?></h1>

        <form method="post">
            <?php wp_nonce_field('aaa_save_cancel_reasons','aaa_cancel_reasons_nonce'); ?>
            <h2><?php esc_html_e('Canned Reasons (one per line)', 'aaa-order-cancel-reason'); ?></h2>
            <textarea name="aaa_canned_reasons" rows="8" style="width:100%;"><?php echo esc_textarea(implode("\n",$canned)); ?></textarea>
            <?php submit_button(__('Save Reasons','aaa-order-cancel-reason')); ?>
            <p><em><?php esc_html_e('Example:', 'aaa-order-cancel-reason'); ?></em><br>
            Out of stock<br>
            Customer changed mind<br>
            Duplicate order<br>
            Payment issue<br>
            Wrong address</p>
        </form>

        <form method="post" style="margin-top:2em;">
            <?php wp_nonce_field('aaa_save_email_recipients','aaa_email_recipients_nonce'); ?>
            <h2><?php esc_html_e('Report Recipients (one email per line)', 'aaa-order-cancel-reason'); ?></h2>
            <textarea name="aaa_email_recipients" rows="6" style="width:100%;"><?php echo esc_textarea(implode("\n",$emails)); ?></textarea>
            <?php submit_button(__('Save Recipients','aaa-order-cancel-reason')); ?>
            <p><em><?php esc_html_e('Example:', 'aaa-order-cancel-reason'); ?></em><br>
            manager@example.com<br>
            support@example.com<br>
            ceo@example.com</p>
        </form>
    </div>
    <?php
}

/**
 * 2. Add Cancellation Fields on Order Edit
 */
add_action('woocommerce_admin_order_data_after_order_details','aaa_cancel_reason_add_fields');
function aaa_cancel_reason_add_fields($order) {
    $canned = get_option('aaa_cancel_canned_reasons',array());
    $sel = $order->get_meta('_cancel_reason');
    $txt = $order->get_meta('_cancel_reason_text');
    ?>
    <div id="aaa_cancel_reason_box" style="margin-top:1em;">
        <h4><?php esc_html_e('Cancellation Reason','aaa-order-cancel-reason'); ?></h4>

        <p class="form-field form-field-wide">
            <label for="cancel_reason_dropdown">
                <?php esc_html_e('Select a Reason','aaa-order-cancel-reason'); ?>
            </label>
            <select name="cancel_reason_dropdown" id="cancel_reason_dropdown" class="wc-enhanced-select" style="width:100%;">
                <option value=""><?php esc_html_e('Select a reason…','aaa-order-cancel-reason'); ?></option>
                <?php foreach($canned as $r): ?>
                    <option value="<?php echo esc_attr($r); ?>" <?php selected($sel,$r); ?>><?php echo esc_html($r); ?></option>
                <?php endforeach; ?>
            </select>
        </p>

        <p class="form-field form-field-wide">
            <label for="cancel_reason_text">
                <?php esc_html_e('Additional Explanation','aaa-order-cancel-reason'); ?>
            </label>
            <textarea name="cancel_reason_text" id="cancel_reason_text" rows="3" style="width:100%;"><?php echo esc_textarea($txt); ?></textarea>
        </p>

        <div id="aaa_cancel_reason_error" class="woocommerce-error" style="display:none; margin-top:8px;">
            <?php esc_html_e('Both fields are required before cancelling this order.','aaa-order-cancel-reason'); ?>
        </div>
    </div>
    <?php
}

/**
 * 3. JS Validation
 */
add_action('admin_footer','aaa_cancel_reason_js');
function aaa_cancel_reason_js() {
    global $post; if(!$post || $post->post_type!=='shop_order') return; ?>
    <script>
    jQuery(function($){
        var $s=$('#order_status'),$b=$('#aaa_cancel_reason_box'),$e=$('#aaa_cancel_reason_error'),$btn=$('#publish');
        function toggle(){ $s.val()==='wc-cancelled' ? $b.show() : ($b.hide(),$e.hide(),$btn.prop('disabled',false)); }
        function check(){
            if($s.val()==='wc-cancelled'){
                var r=$('select[name="cancel_reason_dropdown"]').val().trim(),
                    t=$('textarea[name="cancel_reason_text"]').val().trim();
                if(r===''||t===''){ $e.text("Both fields required.").show(); $btn.prop('disabled',true);}
                else{$e.hide();$btn.prop('disabled',false);}
            }
        }
        $s.on('change',function(){toggle();check();});
        $('#post').on('submit',function(){check(); if($btn.prop('disabled')) return false;});
        toggle(); check();
    });
    </script><?php
}

/**
 * 4. Save + Enforce Required
 */
add_action('woocommerce_process_shop_order_meta','aaa_cancel_reason_save');
function aaa_cancel_reason_save($post_id){
    $order=wc_get_order($post_id); $old=$order?$order->get_status():'';
    if(isset($_POST['order_status']) && $_POST['order_status']==='wc-cancelled'){
        $r=sanitize_text_field($_POST['cancel_reason_dropdown']??'');
        $t=sanitize_textarea_field($_POST['cancel_reason_text']??'');
        if(empty($r)||empty($t)){
            WC_Admin_Meta_Boxes::add_error(__('Cancellation reason + explanation are required.','aaa-order-cancel-reason'));
            if($old) $_POST['order_status']='wc-'.$old; // Revert
            return;
        }
        update_post_meta($post_id,'_cancel_reason',$r);
        update_post_meta($post_id,'_cancel_reason_text',$t);
        update_post_meta($post_id,'_cancelled_by_admin',get_current_user_id());
        $order->add_order_note(sprintf('Cancelled by %s. Reason: %s. %s',wp_get_current_user()->display_name,$r,$t));
        // Restock
        if(!$order->get_meta('_restocked_on_cancel')){
            foreach($order->get_items() as $it){ if($it instanceof WC_Order_Item_Product){ $p=$it->get_product(); if($p&&$p->managing_stock()){ $p->increase_stock($it->get_quantity()); $p->save(); } } }
            $order->update_meta_data('_restocked_on_cancel','yes'); $order->save();
        }
    }
}
