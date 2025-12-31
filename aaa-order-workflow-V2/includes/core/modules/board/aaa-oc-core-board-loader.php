<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/core/modules/board/aaa-oc-core-board-loader.php
 * Purpose: Core Board (menu page, AJAX, indexer, helpers) + self-heal.
 *
 * This loader pulls in all of the classes and procedural files that
 * implement the Workflow Board. It uses AAA_OC_Loader_Util::require_or_log
 * to avoid fatal errors when files are missing and to log helpful messages.
 *
 * Version: 1.3.3
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/* STEP 1: Logger + fallback require */
if ( ! function_exists( 'aaa_oc_boot_log' ) ) {
    function aaa_oc_boot_log( $what ) { if ( function_exists( 'aaa_oc_log' ) ) aaa_oc_log( '[BOOT] ' . $what ); }
}
if ( ! function_exists( 'aaa_oc_require_or_log' ) ) {
    function aaa_oc_require_or_log( $file ) {
        if ( file_exists( $file ) ) { require_once $file; return true; }
        if ( function_exists( 'aaa_oc_log' ) ) aaa_oc_log( '[MISSING] ' . $file );
        return false;
    }
}

/* STEP 2: Paths */
$base = __DIR__;
if ( ! defined( 'AAA_OC_VIEWS_DIR' ) ) define( 'AAA_OC_VIEWS_DIR', $base . '/views/' );

/* STEP 3: Ensure central util (best-effort) */
$util = dirname( __DIR__, 2 ) . '/options/helpers/class-aaa-oc-loader-util.php';
if ( ! class_exists('AAA_OC_Loader_Util') && file_exists($util) ) require_once $util;

/* STEP 4: Tracked includes (tag: board) */
if ( class_exists('AAA_OC_Loader_Util') ) {
    AAA_OC_Loader_Util::require_or_log( $base . '/ajax/class-aaa-oc-board-prefs.php',            false, 'board' );
    AAA_OC_Loader_Util::require_or_log( $base . '/board-hooks-map.php',                          false, 'board' );

    AAA_OC_Loader_Util::require_or_log( $base . '/inc/class-aaa-oc-board.php',                   false, 'board' );
    AAA_OC_Loader_Util::require_or_log( $base . '/ajax/class-aaa-oc-ajax-core.php',              false, 'board' );
    AAA_OC_Loader_Util::require_or_log( $base . '/ajax/class-aaa-oc-ajax-cards.php',             false, 'board' );
    // Custom handler for updating order statuses via Next/Prev buttons. This file
    // registers the wp_ajax_aaa_oc_update_order_status action when loaded.
    if ( function_exists( 'aaa_oc_log' ) ) {
        aaa_oc_log('[BOARD LOADER] Loading update-order-status handler');
    }
    AAA_OC_Loader_Util::require_or_log( $base . '/ajax/class-aaa-oc-update-order-status.php',     false, 'board' );

    AAA_OC_Loader_Util::require_or_log( $base . '/inc/class-aaa-oc-printing.php',                false, 'board' );
    AAA_OC_Loader_Util::require_or_log( $base . '/inc/class-aaa-oc-product-lookup.php',          false, 'board' );

    AAA_OC_Loader_Util::require_or_log( $base . '/index/class-aaa-oc-indexer.php',               false, 'board' );
    AAA_OC_Loader_Util::require_or_log( $base . '/index/class-aaa-oc-reindex-functions.php',     false, 'board' );
    AAA_OC_Loader_Util::require_or_log( $base . '/index/class-aaa-oc-indexer-settings-page.php', false, 'board' );

    AAA_OC_Loader_Util::require_or_log( $base . '/helpers/class-aaa-oc-map-order-source.php',    false, 'board' );
    AAA_OC_Loader_Util::require_or_log( $base . '/helpers/class-aaa-oc-update-order-notes.php',  false, 'board' );
    AAA_OC_Loader_Util::require_or_log( $base . '/helpers/class-render-next-prev-icons.php',     false, 'board' );
    AAA_OC_Loader_Util::require_or_log( $base . '/helpers/class-time-diff-helper.php',           false, 'board' );
    AAA_OC_Loader_Util::require_or_log( $base . '/helpers/class-build-product-table.php',           false, 'board' );

    // Hooks
    AAA_OC_Loader_Util::require_or_log( $base . '/hooks/class-aaa-oc-board-border-filters.php',  false, 'board' );
    AAA_OC_Loader_Util::require_or_log( $base . '/hooks/class-aaa-oc-row1col1-toppills.php',     false, 'board' );
    AAA_OC_Loader_Util::require_or_log( $base . '/hooks/class-aaa-oc-row1col2-topcontrols.php',  false, 'board' );
    AAA_OC_Loader_Util::require_or_log( $base . '/hooks/class-aaa-oc-row2col1-main-left.php',  false, 'board' );

    AAA_OC_Loader_Util::require_or_log( $base . '/hooks/class-aaa-oc-board-products-table-hook.php', false, 'board' );
    AAA_OC_Loader_Util::require_or_log( $base . '/hooks/class-aaa-oc-printing-card-hooks.php',       false, 'board' );
    AAA_OC_Loader_Util::require_or_log( $base . '/hooks/class-aaa-oc-bottom-left-totals.php',        false, 'board' );
    AAA_OC_Loader_Util::require_or_log( $base . '/hooks/aaa-oc-sniffer-widget.php',                  false, 'board' );
    AAA_OC_Loader_Util::require_or_log( $base . '/hooks/aaa-oc-board-card-borders.php',             false, 'board' );
    // Note: As of v2 the collapsed summary right and info right hooks are handled by default implementations.
    // There are no separate classes to include here, so we do not attempt to load them to avoid noise.

    // After attempting to load all files, emit a final log entry summarising
    // whether our AJAX handler class exists.  This uses aaa_oc_log() if
    // available; otherwise it fails silently.  The class_exists() check
    // allows you to see at a glance in aaa_oc.log whether the handler was
    // successfully loaded on this page load.
    if ( function_exists( 'aaa_oc_log' ) ) {
        aaa_oc_log('[BOARD LOADER] Post-load: update handler class present = ' . ( class_exists('AAA_OC_Update_Order_Status') ? 'yes' : 'no' ) );
    }

} else {
    foreach ([
            '/ajax/class-aaa-oc-board-prefs.php',
            '/board-hooks-map.php',
            '/inc/class-aaa-oc-board.php',
            '/ajax/class-aaa-oc-ajax-core.php',
            '/ajax/class-aaa-oc-ajax-cards.php',
            // custom order status handler
            '/ajax/class-aaa-oc-update-order-status.php',
            '/inc/class-aaa-oc-printing.php',
            '/inc/class-aaa-oc-product-lookup.php',
            '/index/class-aaa-oc-indexer.php',
            '/index/class-aaa-oc-reindex-functions.php',
            '/index/class-aaa-oc-indexer-settings-page.php',
            '/helpers/class-aaa-oc-map-order-source.php',
            '/helpers/class-aaa-oc-update-order-notes.php',
            '/helpers/class-render-next-prev-icons.php',
            '/helpers/class-time-diff-helper.php',
            '/helpers/class-build-product-table.php',
            '/hooks/class-aaa-oc-board-border-filters.php',
            '/hooks/class-aaa-oc-row1col1-toppills.php',
            '/hooks/class-aaa-oc-row1col2-topcontrols.php',
            '/hooks/class-aaa-oc-row2col1-main-left.php',
            '/hooks/class-aaa-oc-board-products-table-hook.php',
            '/hooks/class-aaa-oc-printing-card-hooks.php',
            '/hooks/class-aaa-oc-bottom-left-totals.php',
            '/hooks/aaa-oc-sniffer-widget.php',
            '/hooks/aaa-oc-board-card-borders.php',
            // collapsed summary right and info right classes are omitted intentionally
        ] as $rel) {
        $f = $base . $rel; if ( file_exists($f) ) require_once $f;
    }
}

/* STEP 5: Layout selection */
add_filter( 'aaa_oc_board_layout_path', function( $path, $ctx ) {
    $default = AAA_OC_VIEWS_DIR . 'board-card-layout-shell.php';
    return is_string( $path ) && $path !== '' ? $path : $default;
}, 5, 2 );

/* STEP 6: Base table self-heal */
add_action( 'admin_init', function () use ( $base ) {
    $file = $base . '/index/class-aaa-oc-table-installer.php';
    if ( class_exists('AAA_OC_Loader_Util') ) AAA_OC_Loader_Util::require_or_log( $file, false, 'board' );
    elseif ( file_exists($file) ) require_once $file;

    if ( class_exists( 'AAA_OC_Table_Installer' ) ) {
        global $wpdb;
        $t = $wpdb->prefix . 'aaa_oc_order_index';
        $exists = $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->esc_like($t)}'" ) === $t;
        if ( ! $exists ) {
            AAA_OC_Table_Installer::create_index_table();
            aaa_oc_boot_log( 'Self-heal: recreated base order index table.' );
            do_action( 'aaa_oc_core_tables_ready' );
        }
    }
}, 12 );

/* STEP 7: Instantiate hook-owners */
add_action( 'plugins_loaded', function () {
    if ( class_exists( 'AAA_OC_Board' ) )                  new AAA_OC_Board();
    if ( class_exists( 'AAA_OC_Ajax_Core' ) )              new AAA_OC_Ajax_Core();
    if ( class_exists( 'AAA_OC_Indexer_Settings_Page' ) )  new AAA_OC_Indexer_Settings_Page();
    if ( class_exists( 'AAA_OC_Update_Order_Notes' ) )     AAA_OC_Update_Order_Notes::init();
    // The update-order-status handler hooks itself on load; no instance required.
    aaa_oc_boot_log( 'Board core loaded.' );
}, 6 );