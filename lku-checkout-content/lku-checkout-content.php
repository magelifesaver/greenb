<?php
/*
Plugin Name: LKU Checkout Content
Plugin URI: http://yourwebsite.com/
Description: Customizes the checkout content for LKU.
Version: 1.0
Author: Webmaster LKD
License: GPL2
*/

defined('ABSPATH') or die('No script kiddies please!');

function lku_enqueue_scripts() {
    wp_enqueue_script('lku-checkout-content-js', plugin_dir_url(__FILE__) . 'assets/js/lku-checkout-content.js', array('jquery'), null, true);
    wp_localize_script('lku-checkout-content-js', 'lku_checkout_params', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('lku-checkout-nonce')
    ));
}
add_action('wp_enqueue_scripts', 'lku_enqueue_scripts');

function lku_plugin_activation() {
    // Plugin activation code here (if necessary)
}

function lku_plugin_deactivation() {
    // Plugin deactivation code here (if necessary)
}

register_activation_hook(__FILE__, 'lku_plugin_activation');
register_deactivation_hook(__FILE__, 'lku_plugin_deactivation');

require_once plugin_dir_path(__FILE__) . 'includes/lku-custom-checkout-functions.php';
