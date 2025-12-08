<?php
/**
 * File: /wp-content/plugins/aaa-attr-term-importer/aaa-attr-term-importer.php
 * Plugin Name: AAA Attribute Term Importer
 * Description: Tools → Attribute Term Importer. Bulk create/update WooCommerce attribute terms with descriptions and aliases. Standalone (works without PAIM).
 * Version: 0.1.0
 * Author: Webmaster Workflow
 * Text Domain: aaa-attr
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'AAA_ATTR_IMP_VERSION', '0.1.0' );
define( 'AAA_ATTR_IMP_FILE', __FILE__ );
define( 'AAA_ATTR_IMP_DIR', plugin_dir_path( __FILE__ ) );

require_once AAA_ATTR_IMP_DIR . 'admin/class-aaa-attr-term-importer.php';
require_once AAA_ATTR_IMP_DIR . 'aaa-attr-assets-loader.php';

add_action( 'plugins_loaded', static function () {
	// No-op: class file self-initializes.
} );
