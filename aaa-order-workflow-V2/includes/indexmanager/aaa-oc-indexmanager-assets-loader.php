<?php
/**
 * File: plugins/aaa-order-workflow/includes/indexmanager/aaa-oc-indexmanager-assets-loader.php
 */
if ( ! defined('ABSPATH') ) exit;

add_action('admin_enqueue_scripts', function($hook){
    // Only on core settings page + our tabs
    if ( ! isset($_GET['page']) || $_GET['page'] !== 'aaa-oc-core-settings' ) return;
    $tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : '';
    $allowed = ['aaa-oc-indexmanager-users','aaa-oc-indexmanager-products','aaa-oc-indexmanager-orders'];
    if ( ! in_array($tab, $allowed, true) ) return;

    $base = plugins_url('', __FILE__);
    wp_enqueue_style('aaa-oc-im-admin', $base . '/assets/css/oc-indexmanager-admin.css', [], '1.0.0');
    wp_enqueue_script('jquery-ui-sortable');
    wp_enqueue_script('aaa-oc-im-admin', $base . '/assets/js/oc-indexmanager-admin.js', ['jquery','jquery-ui-sortable'], '1.0.0', true);
});
