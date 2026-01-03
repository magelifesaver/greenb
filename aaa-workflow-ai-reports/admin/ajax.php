<?php
/**
 * ============================================================================
 * File Path: /wp-content/plugins/aaa-workflow-ai-reports/admin/ajax.php
 * Description: Handles admin actions and AJAX operations for the plugin.
 *              Supports secure nonce handling, OpenAI key verification,
 *              permission saving, AI-powered report analysis and
 *              configuration of advanced OpenAI parameters.
 * Version: 2.1.0
 * Updated: 2025-12-28
 * Author: AAA Workflow DevOps
 * ============================================================================
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * --------------------------------------------------------------------------
 * 🔐 Save OpenAI Settings (API Key, Model, Debug, Prompt, Temperature, Tokens)
 * --------------------------------------------------------------------------
 */
add_action( 'admin_post_aaa_wf_ai_save_openai', function() {
    if ( ! current_user_can( 'manage_woocommerce' ) ) wp_die( 'Access denied' );
    check_admin_referer( 'aaa_wf_ai_save_openai', 'aaa_wf_ai_nonce_field' );

    // Retrieve values from POST
    $key   = sanitize_text_field( $_POST['aaa_wf_ai_openai_key'] ?? '' );
    $debug = ! empty( $_POST['aaa_wf_ai_debug_enabled'] ) ? 1 : 0;
    // Development mode saves full session logs to /wp-content/aaa-logs/
    $dev_mode = ! empty( $_POST['aaa_wf_ai_dev_mode'] ) ? 1 : 0;
    $model = sanitize_text_field( $_POST['aaa_wf_ai_default_model'] ?? '' );
    $prompt_template = isset( $_POST['aaa_wf_ai_prompt_template'] ) ? wp_unslash( $_POST['aaa_wf_ai_prompt_template'] ) : '';
    $temperature     = isset( $_POST['aaa_wf_ai_temperature'] ) ? floatval( $_POST['aaa_wf_ai_temperature'] ) : 0.3;
    $max_tokens      = isset( $_POST['aaa_wf_ai_max_tokens'] ) ? intval( $_POST['aaa_wf_ai_max_tokens'] ) : 800;

    // Persist options via custom helper
    aaa_wf_ai_save_option( 'aaa_wf_ai_openai_key', $key );
    // Persist debug flag both to the custom options table and the WP options
    // table.  The debug helper reads from the WP option to avoid recursion.
    aaa_wf_ai_save_option( 'aaa_wf_ai_debug_enabled', $debug );
    update_option( 'aaa_wf_ai_debug_enabled', (int) $debug );
    aaa_wf_ai_save_option( 'aaa_wf_ai_dev_mode', $dev_mode );
    if ( $model ) {
        aaa_wf_ai_save_option( 'aaa_wf_ai_default_model', $model );
    }
    if ( $prompt_template ) {
        aaa_wf_ai_save_option( 'aaa_wf_ai_prompt_template', wp_kses_post( $prompt_template ) );
    }
    aaa_wf_ai_save_option( 'aaa_wf_ai_temperature', $temperature );
    aaa_wf_ai_save_option( 'aaa_wf_ai_max_tokens', $max_tokens );

    aaa_wf_ai_debug( '✅ Saved OpenAI settings (key, debug, model, prompt, temperature, tokens).', basename( __FILE__ ), 'ajax' );

    wp_safe_redirect( admin_url( 'admin.php?page=aaa-workflow-ai-reports&tab=openai&updated=1' ) );
    exit;
});

/**
 * --------------------------------------------------------------------------
 * 🧾 Save User Permissions (Allowed Roles)
 * --------------------------------------------------------------------------
 */
add_action( 'admin_post_aaa_wf_ai_save_permissions', function() {
    if ( ! current_user_can( 'manage_woocommerce' ) ) wp_die( 'Access denied' );
    check_admin_referer( 'aaa_wf_ai_save_permissions' );

    $roles = array_map( 'sanitize_text_field', (array) ( $_POST['aaa_wf_ai_allowed_roles'] ?? [] ) );
    aaa_wf_ai_save_option( 'aaa_wf_ai_allowed_roles', $roles );

    aaa_wf_ai_debug( '✅ Saved permissions roles list: ' . implode( ',', $roles ), basename( __FILE__ ), 'ajax' );

    wp_safe_redirect( admin_url( 'admin.php?page=aaa-workflow-ai-reports&tab=permissions&updated=1' ) );
    exit;
});

/**
 * --------------------------------------------------------------------------
 * 🧠 AJAX: Verify OpenAI API Key
 * --------------------------------------------------------------------------
 */
add_action( 'wp_ajax_aaa_wf_ai_verify_openai_key', function() {
    check_ajax_referer( 'aaa_wf_ai_nonce', 'nonce' );
    $key = aaa_wf_ai_get_option( 'aaa_wf_ai_openai_key', '', 'global' );
    if ( empty( $key ) ) {
        wp_send_json_error( [ 'message' => '❌ No API key saved. Please enter and save it first.' ] );
    }

    $response = wp_remote_get(
        'https://api.openai.com/v1/models',
        [
            'headers' => [
                'Authorization' => "Bearer {$key}",
                'Content-Type'  => 'application/json',
            ],
            'timeout' => 20,
        ]
    );
    if ( is_wp_error( $response ) ) {
        $msg = $response->get_error_message();
        aaa_wf_ai_debug( "❌ OpenAI verify network error: {$msg}", basename( __FILE__ ), 'ajax' );
        wp_send_json_error( [ 'message' => 'Network error: ' . esc_html( $msg ) ] );
    }
    $code = wp_remote_retrieve_response_code( $response );
    $body = json_decode( wp_remote_retrieve_body( $response ), true );
    if ( $code === 200 && isset( $body['data'] ) ) {
        $models = wp_list_pluck( $body['data'], 'id' );
        $models = array_values( array_filter( $models, fn( $m ) => preg_match( '/^(gpt\-4o|gpt\-3\.5)/', $m ) ) );
        aaa_wf_ai_save_option( 'aaa_wf_ai_models_list', $models );
        aaa_wf_ai_save_option( 'aaa_wf_ai_verified_at', current_time( 'mysql' ) );
        $current_model = aaa_wf_ai_get_option( 'aaa_wf_ai_default_model', 'gpt-4o-mini' );
        if ( ! in_array( $current_model, $models, true ) ) {
            aaa_wf_ai_save_option( 'aaa_wf_ai_default_model', $models[0] ?? 'gpt-4o-mini' );
        }
        aaa_wf_ai_debug( '✅ API key verified. Models found: ' . implode( ', ', $models ), basename( __FILE__ ), 'ajax' );
        wp_send_json_success( [
            'message' => 'API key verified successfully (' . count( $models ) . ' models found)',
            'models'  => $models,
        ] );
    }
    $error_message = $body['error']['message'] ?? "Unknown error (HTTP {$code})";
    aaa_wf_ai_debug( "❌ Verification failed: {$error_message}", basename( __FILE__ ), 'ajax' );
    wp_send_json_error( [ 'message' => $error_message ] );
});

/**
 * --------------------------------------------------------------------------
 * 📊 AJAX: Run AI Analysis on WooCommerce Sales Summary
 * --------------------------------------------------------------------------
 * This action receives either pre-fetched REST data from the client or
 * parameters for the date range.  It ensures valid data exists, augments
 * it with top product information, and passes the result to the OpenAI
 * analysis function.  Both the AI summary and raw data (including top
 * products) are returned to the browser.
 */
add_action( 'wp_ajax_aaa_wf_ai_run_report', function() {
    check_ajax_referer( 'aaa_wf_ai_nonce', 'nonce' );

    // Identify the report type (summary, products, categories, inventory, etc.)
    $type = isset( $_POST['type'] ) ? sanitize_text_field( $_POST['type'] ) : 'summary';

    // Decode pre-fetched REST data if provided
    $data_json = stripslashes( $_POST['data'] ?? '' );
    $data      = json_decode( $data_json, true );

    // Retrieve from/to dates from POST for logging and top-product fetching
    $from_param = sanitize_text_field( $_POST['from'] ?? date( 'Y-m-d' ) );
    $to_param   = sanitize_text_field( $_POST['to']   ?? date( 'Y-m-d' ) );

    // If no data is supplied and the type is summary, attempt to fetch summary directly.
    if ( empty( $data ) && $type === 'summary' ) {
        aaa_wf_ai_debug( "⚠️ No REST data received — fetching manually for {$from_param} → {$to_param}", basename( __FILE__ ), 'ajax' );
        $data = aaa_wf_ai_get_sales_summary( $from_param, $to_param );
    }

    // Validate that we have some data to work with.  For summary reports we
    // expect a 'totals' array; for other types we accept any non-empty array.
    $is_valid = is_array( $data ) && ! empty( $data );
    if ( $type === 'summary' ) {
        $is_valid = $is_valid && ! empty( $data['totals'] );
    }

    if ( ! $is_valid ) {
        aaa_wf_ai_debug( '❌ Invalid or empty report data — skipping AI analysis.', basename( __FILE__ ), 'ajax' );
        wp_send_json_success( [
            'summary' => '⚠️ No valid data found for analysis. Ensure the endpoint returns valid results.',
            'raw'     => $data,
        ] );
        exit;
    }

    // Determine the date range for top-product fetching and logging.  Use data
    // values if available; otherwise default to incoming parameters.  Some
    // endpoints (e.g. inventory summary) may not have date fields.
    $from_date = isset( $data['from'] ) ? $data['from'] : $from_param;
    $to_date   = isset( $data['to'] )   ? $data['to']   : $to_param;

    // For summary reports, augment data with a list of top products.  Other
    // report types (products, categories, inventory) include their own rows.
    if ( $type === 'summary' ) {
        $top_products = aaa_wf_ai_get_top_products( $from_date, $to_date, 5 );
        if ( ! empty( $top_products ) ) {
            $data['top_products'] = $top_products;
        }
    }

    // Analyze via OpenAI.  The same function handles generic report data.
    $result = aaa_wf_ai_analyze_sales( $data );
    aaa_wf_ai_debug( '✅ AI report analysis complete.', basename( __FILE__ ), 'ajax' );

    // Persist a session log if development mode is enabled.  Each run is
    // recorded as a JSON file to aid debugging and auditing.
    $dev_mode = (bool) aaa_wf_ai_get_option( 'aaa_wf_ai_dev_mode', false );
    if ( $dev_mode ) {
        $log_dir = WP_CONTENT_DIR . '/aaa-logs';
        if ( ! is_dir( $log_dir ) ) {
            wp_mkdir_p( $log_dir );
        }
        $timestamp = date( 'Y-m-d_His' );
        // Build a filename containing type and date range for clarity
        $fname = sprintf( 'ai-report-%s_%s_%s-to-%s.json', $timestamp, sanitize_title( $type ), $from_date ?: 'na', $to_date ?: 'na' );
        $log_path = trailingslashit( $log_dir ) . $fname;
        $log_payload = [
            'timestamp'  => current_time( 'mysql' ),
            'type'       => $type,
            'from'       => $from_date,
            'to'         => $to_date,
            'rest_data'  => $data,
            'ai_summary' => $result,
        ];
        file_put_contents( $log_path, json_encode( $log_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) );
        aaa_wf_ai_debug( "🧾 Session log written: {$log_path}", basename( __FILE__ ), 'ajax' );
    }

    wp_send_json_success( [
        'summary' => $result,
        'raw'     => $data,
    ] );
});