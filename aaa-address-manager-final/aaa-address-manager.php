<?php
/*
 * Plugin Name: AAA Address Manager
 * Description: Combines background address verification for user profiles and bulk backfilling of order coordinates. Includes a unified settings page and optional Sunshine Autocomplete integration.
 * Version: 1.0.0
 * Author: Workflow Delivery
 * Text Domain: aaa-address-manager
 */

// Block direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Require classes. Each module is kept under 150 lines to honour a wide‑and‑thin architecture.
require_once __DIR__ . '/includes/class-settings.php';
require_once __DIR__ . '/includes/class-bulkverify-core.php';
require_once __DIR__ . '/includes/class-bulkverify-cron.php';
require_once __DIR__ . '/includes/class-bulkverify-useractions.php';
require_once __DIR__ . '/includes/class-bulkverify-adminqueue.php';
require_once __DIR__ . '/includes/class-bulkverify-adminmass.php';
require_once __DIR__ . '/includes/class-bulkverify-exclude.php';
require_once __DIR__ . '/includes/class-orderbackfill-core.php';
require_once __DIR__ . '/includes/class-orderbackfill-useractions.php';
require_once __DIR__ . '/includes/class-orderbackfill-admin.php';

/**
 * Bootstrap all modules once WordPress is ready. The individual classes hook
 * themselves into WordPress as needed.
 */
add_action( 'plugins_loaded', function() {
    // Settings page
    AAA_ADBC_Settings::init();
    // User address verification
    AAA_BulkVerify_Core::init();
    AAA_BulkVerify_Cron::init();
    AAA_BulkVerify_UserActions::init();
    AAA_BulkVerify_AdminQueue::init();
    AAA_BulkVerify_AdminMass::init();
    AAA_BulkVerify_Exclude::init();
    // Order backfill
    AAA_OrderBackfill_Core::init();
    AAA_OrderBackfill_UserActions::init();
    AAA_OrderBackfill_Admin::init();
});