<?php
/**
 * Plugin Name: AAA API Taxonomy Mapper Generator (XHV98-API)
 * Description: Generates static JSON mapping files for brands, categories, suppliers, locations, and attributes with customizable settings, logging, and multi-site dynamic path support.
 * Version: 1.7.1
 * Author: Lokey Delivery
 *
 * This version of the Taxonomy Mapper Generator adds per-file and global
 * debug toggles.  By defining the constants below in your
 * `wp-config.php` you can enable or disable logging without editing
 * plugin code:
 *
 *   - `AAA_API_MAPPER_DEBUG` – global on/off switch for all debug
 *     logging in this plugin (default: false).
 *   - `AAA_API_TAXONOMY_MAPPER_GENERATOR_DEBUG` – controls debug
 *     messages for this loader and the generator class (inherits the
 *     global value by default).
 *   - `AAA_API_PRODUCT_CATEGORY_EXPORT_DEBUG` – controls debug messages
 *     for the product category export class (inherits the global value
 *     by default).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* ------------------------------------------------------------------------
 * Debug configuration
 *
 * Define these constants in wp-config.php to enable or disable
 * error_log debugging.  If undefined, they default to false to avoid
 * verbose logging.  The per-file constants inherit the global value
 * when they are not explicitly defined.
 */
if ( ! defined( 'AAA_API_MAPPER_DEBUG' ) ) {
    define( 'AAA_API_MAPPER_DEBUG', false );
}
if ( ! defined( 'AAA_API_TAXONOMY_MAPPER_GENERATOR_DEBUG' ) ) {
    define( 'AAA_API_TAXONOMY_MAPPER_GENERATOR_DEBUG', AAA_API_MAPPER_DEBUG );
}
if ( ! defined( 'AAA_API_PRODUCT_CATEGORY_EXPORT_DEBUG' ) ) {
    define( 'AAA_API_PRODUCT_CATEGORY_EXPORT_DEBUG', AAA_API_MAPPER_DEBUG );
}

// Define plugin constants if not already defined
if ( ! defined( 'AAA_API_MAPPER_PLUGIN_FILE' ) ) {
    define( 'AAA_API_MAPPER_PLUGIN_FILE', __FILE__ );
}
if ( ! defined( 'AAA_API_MAPPER_PLUGIN_DIR' ) ) {
    define( 'AAA_API_MAPPER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'AAA_API_MAPPER_PLUGIN_URL' ) ) {
    define( 'AAA_API_MAPPER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

// Emit a loader initialization message if debugging is enabled for this file
if ( AAA_API_TAXONOMY_MAPPER_GENERATOR_DEBUG ) {
    error_log( '[AAA_API_TaxonomyMapperGenerator] Loader initialized.' );
}

// Core taxonomy mapper
require_once AAA_API_MAPPER_PLUGIN_DIR . 'includes/class-aaa-api-taxonomy-mapper-generator.php';

// Product category export module
require_once AAA_API_MAPPER_PLUGIN_DIR . 'includes/class-aaa-api-product-category-export.php';

// Bootstrap classes
if ( class_exists( 'AAA_API_TaxonomyMapperGenerator' ) ) {
    new AAA_API_TaxonomyMapperGenerator();
} else {
    if ( AAA_API_TAXONOMY_MAPPER_GENERATOR_DEBUG ) {
        error_log( '[AAA_API_TaxonomyMapperGenerator] Core class not found after include.' );
    }
}

if ( class_exists( 'AAA_API_ProductCategoryExport' ) ) {
    new AAA_API_ProductCategoryExport();
} else {
    if ( AAA_API_PRODUCT_CATEGORY_EXPORT_DEBUG ) {
        error_log( '[AAA_API_ProductCategoryExport] Class not found after include.' );
    }
}