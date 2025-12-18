<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/core/stream/handler/ajax/fn-ajax-cache-sse-message.php
// Status: MODIFIED

namespace WPAICG\Core\Stream\Handler\Ajax;

use WPAICG\Core\Stream\Cache\AIPKit_SSE_Message_Cache; // Corrected namespace for Cache
use WPAICG\Utils\AIPKit_CORS_Manager; // For CORS handling
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
* AJAX handler for caching the user message or context data before starting the SSE stream.
*
* @param \WPAICG\Core\Stream\Handler\SSEHandler $handlerInstance The instance of the SSEHandler class.
* @return void Sends JSON response.
*/
function ajax_cache_sse_message_logic(\WPAICG\Core\Stream\Handler\SSEHandler $handlerInstance): void
{
    // --- Handle preflight OPTIONS request ---
    AIPKit_CORS_Manager::handle_preflight_request();

    // --- CORS Check ---
    $bot_id = 0;
    if (isset($_POST['bot_id'])) {
        $bot_id = absint(wp_unslash($_POST['bot_id']));
    }

    if ($bot_id > 0) {
        // Check if this is a cross-origin request (actual embed usage)
        $is_cross_origin = false;
        if (isset($_SERVER['HTTP_ORIGIN']) && !empty($_SERVER['HTTP_ORIGIN'])) {
            $origin = $_SERVER['HTTP_ORIGIN'];
            $site_url = get_site_url();
            $site_parsed = parse_url($site_url);
            $origin_parsed = parse_url($origin);
            
            // Check if origin is different from site domain
            if ($origin_parsed && $site_parsed && 
                ($origin_parsed['host'] !== $site_parsed['host'] || 
                 ($origin_parsed['scheme'] ?? 'http') !== ($site_parsed['scheme'] ?? 'http'))) {
                $is_cross_origin = true;
            }
        }
        
        if ($is_cross_origin) {
            // This is a cross-origin request, check embed feature availability
            if (class_exists('\WPAICG\aipkit_dashboard') && 
                \WPAICG\aipkit_dashboard::is_pro_plan() && 
                \WPAICG\aipkit_dashboard::is_addon_active('embed_anywhere')) {
                
                $origin_allowed = AIPKit_CORS_Manager::check_and_set_cors_headers($bot_id);
                if (!$origin_allowed) {
                    wp_send_json_error([
                        'message' => __('This domain is not permitted to access the chatbot.', 'gpt3-ai-content-generator'),
                        'code'    => 'cors_denied'
                    ], 403);
                    return;
                }
            } else {
                // Embed feature not available but this is a cross-origin request
                wp_send_json_error([
                    'message' => __('Embed feature is not available with your current plan.', 'gpt3-ai-content-generator'),
                    'code'    => 'embed_not_available'
                ], 403);
                return;
            }
        }
        // For same-origin requests, no additional CORS checks needed
    }

    // --- MODIFICATION: Improved Nonce Check and Error Reporting ---
    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- $_POST['_ajax_nonce'] is sanitized with sanitize_key before use.
    if (!isset($_POST['_ajax_nonce']) || !wp_verify_nonce(sanitize_key($_POST['_ajax_nonce']), 'aipkit_frontend_chat_nonce')) {
        $error_data_for_response = [
            'message' => __('Your session has expired or the request is invalid. Please refresh the page and try again.', 'gpt3-ai-content-generator'),
            'code'    => 'nonce_failure_cache_sse' // Specific code for nonce failure
        ];
        wp_send_json_error($error_data_for_response, 403);
        return;
    }
    // --- END MODIFICATION ---

    // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked above.
    $raw_user_message = isset($_POST['message']) ? wp_unslash($_POST['message']) : '';
    
    // Custom sanitization for code content - preserve code structure while ensuring security
    $user_message = wp_check_invalid_utf8($raw_user_message);
    $user_message = str_replace(chr(0), '', $user_message); // Remove null bytes
    
    // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked above.
    $image_inputs_json = isset($_POST['image_inputs']) ? wp_kses_post(wp_unslash($_POST['image_inputs'])) : null;
    // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked above.
    $client_user_message_id = isset($_POST['user_client_message_id']) ? sanitize_key(wp_unslash($_POST['user_client_message_id'])) : null;
    // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked above.
    $active_openai_vs_id = isset($_POST['active_openai_vs_id']) ? sanitize_text_field(wp_unslash($_POST['active_openai_vs_id'])) : null;

    $image_inputs_data = null;
    if ($image_inputs_json) {
        $decoded_outer_array = json_decode($image_inputs_json, true);
        if (is_array($decoded_outer_array) && isset($decoded_outer_array[0]) && is_array($decoded_outer_array[0])) {
            $transformed_image_inputs = [];
            $item = $decoded_outer_array[0];
            if (is_array($item) && isset($item['mime_type']) && isset($item['base64_data'])) {
                if (strpos($item['mime_type'], 'image/') === 0) {
                    $transformed_image_inputs[] = ['type' => $item['mime_type'], 'base64' => $item['base64_data']];
                }
            }
            if (!empty($transformed_image_inputs)) {
                $image_inputs_data = $transformed_image_inputs;
            }
        }
    }

    if (empty($user_message) && empty($image_inputs_data)) {
        $error_data_for_response = [
            'message' => __('Data to cache (message or image) cannot be empty.', 'gpt3-ai-content-generator'),
            'code' => 'empty_data_to_cache'
        ];
        wp_send_json_error($error_data_for_response, 400);
        return;
    }

    $data_to_cache_structured = [
        'user_message' => $user_message,
        'image_inputs' => $image_inputs_data,
        'client_user_message_id' => $client_user_message_id
    ];
    if ($active_openai_vs_id) {
        $data_to_cache_structured['active_openai_vs_id'] = $active_openai_vs_id;
    }
    
    $data_to_cache = wp_json_encode($data_to_cache_structured);

    if ($data_to_cache === false) {
        $error_data_for_response = [
            'message' => __('Failed to encode data for caching. The message may contain invalid characters.', 'gpt3-ai-content-generator'),
            'code' => 'json_encode_failed'
        ];
        wp_send_json_error($error_data_for_response, 400);
        return;
    }


    if (!class_exists(AIPKit_SSE_Message_Cache::class)) {
        $cache_path = WPAICG_PLUGIN_DIR . 'classes/core/stream/cache/class-sse-message-cache.php';
        if (file_exists($cache_path)) {
            require_once $cache_path;
        } else {
            $error_data_for_response = [
                'message' => __('Cache component missing.', 'gpt3-ai-content-generator'),
                'code' => 'cache_component_missing'
            ];
            wp_send_json_error($error_data_for_response, 500);
            return;
        }
    }
    $sse_message_cache = new AIPKit_SSE_Message_Cache();
    
    $cache_key_result = $sse_message_cache->set($data_to_cache);

    if (is_wp_error($cache_key_result)) {
        $error_data_for_response = [
            'message' => $cache_key_result->get_error_message(),
            'code'    => $cache_key_result->get_error_code()
        ];
        $error_status = $cache_key_result->get_error_data()['status'] ?? 500;
        wp_send_json_error($error_data_for_response, $error_status);
    } else {
        wp_send_json_success(['cache_key' => $cache_key_result]);
    }
}