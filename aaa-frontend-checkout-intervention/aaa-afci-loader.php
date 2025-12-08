<?php
/**
 * File: /aaa-afci-loader.php
 * Purpose: Unified loader for all AFCI subsystems (guarded defines).
 * Version: 1.4.0
 */
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! defined( 'AAA_FCI_PATH' ) )     define( 'AAA_FCI_PATH', plugin_dir_path( __FILE__ ) );
if ( ! defined( 'AAA_FCI_URL' ) )      define( 'AAA_FCI_URL',  plugin_dir_url( __FILE__ ) );
if ( ! defined( 'AAA_FCI_VERSION' ) )  define( 'AAA_FCI_VERSION', '1.4.0' );

// Debug: loader bootstrap
if ( function_exists( 'aaa_fci_debug_log' ) ) {
	aaa_fci_debug_log( 'Loader bootstrap', [ 'version' => AAA_FCI_VERSION ] );
}

/**
 * Index (database) classes
 * ------------------------------------------------------------ */
require_once AAA_FCI_PATH . 'index/class-aaa-afci-detail-manager.php';
require_once AAA_FCI_PATH . 'index/class-aaa-afci-table-installer.php';
require_once AAA_FCI_PATH . 'index/class-aaa-afci-table-manager.php';

/**
 * Logger (REST + cookies + file)
 * ------------------------------------------------------------ */
require_once AAA_FCI_PATH . 'includes/aaa-afci-logger-core.php';

/**
 * Admin: Unified Session Log page
 * ------------------------------------------------------------ */
require_once AAA_FCI_PATH . 'admin/aaa-afci-session-log-page.php';
require_once AAA_FCI_PATH . 'admin/aaa-afci-export-page.php';
require_once AAA_FCI_PATH . 'admin/aaa-afci-maintenance-page.php';

