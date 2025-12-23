<?php
/**
 * File: /aaa-oc-product-attribute-visibility/includes/aaa-oc-attrvis-loader.php
 * Purpose: Central loader for OC Product Attribute Visibility.
 *
 * Loads core classes, admin pages, bulk actions, cron handlers and CLI
 * integrations. The loader ensures classes are only loaded once and hooks
 * initializers at the appropriate times.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( defined( 'AAA_OC_ATTRVIS_LOADER_READY' ) ) {
    return;
}
define( 'AAA_OC_ATTRVIS_LOADER_READY', true );

// Local debug switch for loader.
if ( ! defined( 'AAA_OC_ATTRVIS_DEBUG_LOADER' ) ) {
    define( 'AAA_OC_ATTRVIS_DEBUG_LOADER', true );
}

if ( AAA_OC_ATTRVIS_DEBUG_LOADER ) {
    error_log( '[AAA_OC_ATTRVIS][loader] start' );
}

// Core logic for scanning and fixing attributes.
require_once __DIR__ . '/core/class-aaa-oc-attrvis-fixer.php';
// Cron handlers for asynchronous jobs.
require_once __DIR__ . '/core/class-aaa-oc-attrvis-cron.php';

// Admin UI components.
if ( is_admin() ) {
    require_once __DIR__ . '/admin/class-aaa-oc-attrvis-admin-page.php';
    require_once __DIR__ . '/admin/class-aaa-oc-attrvis-admin-actions.php';
    require_once __DIR__ . '/admin/class-aaa-oc-attrvis-bulk-actions.php';
    AAA_OC_AttrVis_Admin_Page::init();
    AAA_OC_AttrVis_Admin_Actions::init();
    AAA_OC_AttrVis_Bulk_Actions::init();
}

// CLI support for backwards compatibility (optional). It uses the existing
// command name `wc-attr-visibility` so that previous workflows remain
// functional. This file is loaded only when WP_CLI is present.
if ( defined( 'WP_CLI' ) && WP_CLI ) {
    require_once __DIR__ . '/cli/class-aaa-oc-attrvis-cli.php';
    AAA_OC_AttrVis_CLI::init();
}

// Register cron handlers regardless of admin context.
AAA_OC_AttrVis_Cron::init();