<?php
/**
 * Plugin Name: AAA WF User Index Manager
 * Description: Multi-entity (Users/Orders/Products) wide-table indexer with per-site settings, triggers, computed columns, and global reindex API.
 * Version: 2.1.0
 * Author: Webmaster Workflow
 * Text Domain: aaa-wfuim
 */
if ( ! defined('ABSPATH') ) exit;

define('AAA_WFUIM_VERSION', '2.1.0');
define('AAA_WFUIM_DEBUG', true);
define('AAA_WFUIM_FILE', __FILE__);
define('AAA_WFUIM_DIR', plugin_dir_path(__FILE__));
define('AAA_WFUIM_URL', plugin_dir_url(__FILE__));

require_once AAA_WFUIM_DIR.'aaa-wfuim-loader.php';

register_activation_hook(__FILE__, function($network_wide){
    \AAA_WFUIM_Capabilities::add_caps_network_wide($network_wide);
    $ensure = function(){
        foreach (\AAA_WFUIM_Registry::tables() as $t) { \AAA_WFUIM_Schema::ensure_table($t); }
    };
    if ( is_multisite() && $network_wide ) {
        foreach ( get_sites(['fields'=>'ids']) as $bid ){ switch_to_blog($bid); $ensure(); restore_current_blog(); }
    } else {
        $ensure();
    }
});
