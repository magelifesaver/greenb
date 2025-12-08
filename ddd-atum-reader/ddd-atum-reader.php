<?php
/**
 * Plugin Name: DDD ATUM Log Viewer
 * Description: Development tool to query ATUM logs by Product ID and display results in a sortable, searchable table.
 * Version: 0.1
 * Author: Dev Tool
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'DDD_ATUM_READER_VERSION', '1.3.0' );
define( 'DDD_ATUM_READER_PATH', plugin_dir_path( __FILE__ ) );
define( 'DDD_ATUM_READER_URL',  plugin_dir_url( __FILE__ ) );

// Include classes
require_once DDD_ATUM_READER_PATH . 'includes/class-ddd-atum-logs.php';
require_once DDD_ATUM_READER_PATH . 'includes/admin/class-ddd-atum-page.php';

// Bootstrap admin page
add_action( 'plugins_loaded', function() {
    if ( is_admin() ) {
        DDD_ATUM_Page::init();
    }
} );
