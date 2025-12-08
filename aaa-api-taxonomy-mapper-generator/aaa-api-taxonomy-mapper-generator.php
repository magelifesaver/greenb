<?php
/**
 * File: wp-content/plugins/aaa-api-taxonomy-mapper-generator/aaa-api-taxonomy-mapper-generator.php
 *
 * Plugin Name: AAA API Taxonomy Mapper Generator
 * Description: Generates static JSON mapping files for brands, categories, suppliers, locations, and attributes with customizable settings, logging, and multi-site dynamic path support.
 * Version: 1.7.0
 * Author: Lokey Delivery
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
    error_log( '[AAA_API_TaxonomyMapperGenerator] Loader initialized.' );
}

if ( ! defined( 'AAA_API_MAPPER_PLUGIN_FILE' ) ) {
    define( 'AAA_API_MAPPER_PLUGIN_FILE', __FILE__ );
}
if ( ! defined( 'AAA_API_MAPPER_PLUGIN_DIR' ) ) {
    define( 'AAA_API_MAPPER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'AAA_API_MAPPER_PLUGIN_URL' ) ) {
    define( 'AAA_API_MAPPER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

/**
 * Core taxonomy mapper.
 */
require_once AAA_API_MAPPER_PLUGIN_DIR . 'includes/class-aaa-api-taxonomy-mapper-generator.php';

/**
 * Product category export module.
 */
require_once AAA_API_MAPPER_PLUGIN_DIR . 'includes/class-aaa-api-product-category-export.php';

/**
 * Bootstrap classes.
 */
if ( class_exists( 'AAA_API_TaxonomyMapperGenerator' ) ) {
    new AAA_API_TaxonomyMapperGenerator();
} else {
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        error_log( '[AAA_API_TaxonomyMapperGenerator] Core class not found after include.' );
    }
}

if ( class_exists( 'AAA_API_ProductCategoryExport' ) ) {
    new AAA_API_ProductCategoryExport();
} else {
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        error_log( '[AAA_API_ProductCategoryExport] Class not found after include.' );
    }
}
