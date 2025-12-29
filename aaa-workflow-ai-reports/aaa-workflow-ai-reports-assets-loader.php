<?php
/**
 * ============================================================================
 * File Path: /wp-content/plugins/aaa-workflow-ai-reports/aaa-workflow-ai-reports-assets-loader.php
 * Description: Handles enqueueing all JS / CSS assets for the admin dashboard.
 *              Provides REST + AJAX nonce injection for secure API requests.
 * Version: 2.1.0
 * Updated: 2025-12-28
 * Author: AAA Workflow DevOps
 * ============================================================================
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * --------------------------------------------------------------------------
 * 🎨 Enqueue Admin Assets
 * --------------------------------------------------------------------------
 * The assets are only enqueued on pages belonging to this plugin.  We also
 * localize the script with REST and AJAX endpoints as well as security
 * nonces.  The version constant is used to bust caches when files change.
 */
add_action( 'admin_enqueue_scripts', function( $hook ) {
    // Load assets only on our plugin’s pages
    if ( strpos( $hook, 'aaa-workflow-ai-reports' ) === false ) {
        return;
    }

    // --- CSS ---
    wp_enqueue_style(
        'aaa-wf-ai-admin-css',
        AAA_WF_AI_URL . 'assets/css/admin.css',
        [],
        AAA_WF_AI_VERSION
    );

    // --- JS ---
    wp_enqueue_script(
        'aaa-wf-ai-admin-js',
        AAA_WF_AI_URL . 'assets/js/admin.js',
        [ 'jquery' ],
        AAA_WF_AI_VERSION,
        true
    );

    // --- Localized Variables ---
    wp_localize_script( 'aaa-wf-ai-admin-js', 'AAA_WFAI', [
        // 🔗 REST endpoint (internal LokeyReports root)
        'restUrl'   => esc_url( rest_url( 'lokeyreports/v1/' ) ),

        // 🔗 AJAX endpoint (WordPress admin-ajax)
        'ajaxUrl'   => admin_url( 'admin-ajax.php' ),

        // 🔒 Security tokens
        'nonce'     => wp_create_nonce( 'aaa_wf_ai_nonce' ),  // AJAX nonce
        'restNonce' => wp_create_nonce( 'wp_rest' ),          // REST nonce

        // 🧠 Plugin info
        'pluginVer' => AAA_WF_AI_VERSION,
        'restPrefix' => 'lokeyreports/v1',
        'siteUrl'    => home_url(),
    ] );

    aaa_wf_ai_debug( 'Admin assets enqueued successfully.', basename( __FILE__ ), 'assets' );
});