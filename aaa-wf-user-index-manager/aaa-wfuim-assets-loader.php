<?php
if ( ! defined('ABSPATH') ) exit;

/**
 * Robust admin asset loader:
 * - Works on Master, All Tables, Add/Edit Table
 * - Works in Network Admin
 * - Tolerates custom screen IDs/hook suffixes/minifiers
 */
add_action('admin_enqueue_scripts', function($hook){
    $ok = false;

    // 1) Prefer current screen when available
    if ( function_exists('get_current_screen') ) {
        $screen = get_current_screen();
        if ( $screen && is_object($screen) ) {
            // Any screen id that contains 'wfuim' (handles top-level + submenus + network)
            if ( strpos($screen->id, 'wfuim') !== false ) {
                $ok = true;
            }
        }
    }

    // 2) Fallback: query arg check (most reliable across setups)
    // Accept our three pages: wfuim-master, wfuim-tables, wfuim-table-edit
    if ( ! $ok && isset($_GET['page']) ) {
        $page = sanitize_text_field($_GET['page']);
        if ( $page === 'wfuim-master' || $page === 'wfuim-tables' || $page === 'wfuim-table-edit' ) {
            $ok = true;
        }
    }

    // 3) Legacy explicit hook suffixes (keep as last safety net)
    if ( ! $ok ) {
        $ok = in_array($hook, [
            'toplevel_page_wfuim-master',
            'wfuim_page_wfuim-tables',
            'wfuim_page_wfuim-table-edit',
            // Network admin variants sometimes get prefixed differently:
            'settings_page_wfuim-tables',
            'settings_page_wfuim-table-edit',
        ], true);
    }

    if ( ! $ok ) return;

    // Enqueue
    wp_enqueue_style('aaa-wfuim-admin', AAA_WFUIM_URL.'assets/css/admin.css', [], AAA_WFUIM_VERSION);
    wp_enqueue_script('jquery-ui-sortable');
    wp_enqueue_script('aaa-wfuim-admin', AAA_WFUIM_URL.'assets/js/admin-tables.js', ['jquery','jquery-ui-sortable'], AAA_WFUIM_VERSION, true);

    // Optional: one-time debug to verify what matched (comment out in prod)
    if ( defined('AAA_WFUIM_DEBUG') && AAA_WFUIM_DEBUG ) {
        $sid = ( isset($screen) && $screen ) ? $screen->id : '(no screen)';
        $page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '(no page)';
        error_log('[WFUIM] assets loaded | hook='.$hook.' | screen='.$sid.' | page='.$page);
    }
});
