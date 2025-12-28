<?php
/**
 * Loader: activation, DB setup, admin page, assets bootstrap.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// --- Activation hook (prepare options or custom table) ---
register_activation_hook( __FILE__, function() {
    add_option( 'aaa_wf_ai_openai_key', '' );
    add_option( 'aaa_wf_ai_allowed_roles', ['administrator','shop_manager'] );
});

// --- Load assets loader ---
require_once __DIR__ . '/aaa-workflow-ai-reports-debug.php';
require_once __DIR__ . '/includes/helpers/options-helpers.php';
require_once __DIR__ . '/includes/api/openai-client.php';
require_once __DIR__ . '/includes/api/lokey-client.php';
require_once __DIR__ . '/admin/ajax.php';
require_once __DIR__ . '/admin/admin-page.php';


// --- Admin menu ---
add_action('admin_menu', function(){
    add_menu_page(
        'AAA Workflow AI Reports',
        'AI Reports',
        'manage_woocommerce',
        'aaa-workflow-ai-reports',
        'aaa_wf_ai_admin_page_render',
        'dashicons-chart-bar',
        55
    );
});
