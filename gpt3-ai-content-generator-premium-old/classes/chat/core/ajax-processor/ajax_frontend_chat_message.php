<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/chat/core/ajax-processor/ajax_frontend_chat_message.php
// Status: MODIFIED

namespace WPAICG\Chat\Core\AjaxProcessor;

use WPAICG\Chat\Storage\BotStorage; // Needed by context builder
use WPAICG\Core\AIPKit_Content_Moderator; // For moderation, used by validator
use WPAICG\Utils\AIPKit_CORS_Manager; // For CORS handling
use WP_Error;
// Ensure dependencies for logic file are loaded if not by main loader
use WPAICG\Lib\Chat\Triggers\AIPKit_Trigger_Storage;
use WPAICG\Lib\Chat\Triggers\AIPKit_Trigger_Manager;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the ajax_frontend_chat_message method of AjaxProcessor.
 * This function now orchestrates the call to various sub-processors.
 *
 * @param \WPAICG\Chat\Core\AjaxProcessor $processorInstance The instance of the AjaxProcessor class.
 * @return void Sends JSON response.
 */
function ajax_frontend_chat_message(\WPAICG\Chat\Core\AjaxProcessor $processorInstance): void
{
    // --- Handle preflight OPTIONS request ---
    AIPKit_CORS_Manager::handle_preflight_request();

    // --- Extract bot_id early for CORS check ---
    $post_data = wp_unslash($_POST);
    $bot_id_from_request = isset($post_data['bot_id']) ? absint($post_data['bot_id']) : 0;

    // --- CORS Check ---
    if ($bot_id_from_request > 0) {
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
                
                $origin_allowed = AIPKit_CORS_Manager::check_and_set_cors_headers($bot_id_from_request);
                if (!$origin_allowed) {
                    wp_send_json_error(
                        ['message' => __('This domain is not permitted to access the chatbot.', 'gpt3-ai-content-generator')],
                        403
                    );
                    return;
                }
            } else {
                // Embed feature not available but this is a cross-origin request
                wp_send_json_error(
                    ['message' => __('Embed feature is not available with your current plan.', 'gpt3-ai-content-generator')],
                    403
                );
                return;
            }
        }
        // For same-origin requests, no additional CORS checks needed
    }

    // --- 0. Security Check ---
    if (!check_ajax_referer('aipkit_frontend_chat_nonce', '_ajax_nonce', false)) {
        wp_send_json_error(
            ['message' => __('Security check failed. Please refresh the page and try again.', 'gpt3-ai-content-generator')],
            403
        );
        return;
    }
    // Unslash all POST data at once (already done above, but keeping for clarity)
    $post_data = wp_unslash($_POST);

    // --- 0. Get Sub-Processors ---
    $validator = $processorInstance->get_message_validator();
    $image_processor = $processorInstance->get_image_processor();
    $trigger_runner = $processorInstance->get_trigger_runner();
    $context_builder = $processorInstance->get_context_builder();
    $history_manager = $processorInstance->get_history_manager();
    $ai_request_runner = $processorInstance->get_ai_request_runner();
    $response_logger = $processorInstance->get_response_logger();

    if (!$validator || !$image_processor || !$trigger_runner || !$context_builder || !$history_manager || !$ai_request_runner || !$response_logger) {
        wp_send_json_error(['message' => __('Chat processing service is currently unavailable (core components missing).', 'gpt3-ai-content-generator')], 503);
        return;
    }

    // --- 1. Initial Parameter Extraction and Validation ---
    $client_ip = isset($_SERVER['REMOTE_ADDR']) ? filter_var(wp_unslash($_SERVER['REMOTE_ADDR']), FILTER_VALIDATE_IP) : null;
    $post_id_from_request = isset($post_data['post_id']) ? absint($post_data['post_id']) : 0;
    $bot_id_from_request = isset($post_data['bot_id']) ? absint($post_data['bot_id']) : 0;
    $frontend_active_openai_vs_id_from_post = isset($post_data['active_openai_vs_id']) ? sanitize_text_field($post_data['active_openai_vs_id']) : null;

    $bot_storage = $processorInstance->get_bot_storage();
    if (!$bot_storage) {
        wp_send_json_error(['message' => __('Chat system (storage) not ready.', 'gpt3-ai-content-generator')], 500);
        return;
    }
    $initial_bot_settings_for_validation = $bot_storage->get_chatbot_settings($bot_id_from_request);
    if (empty($initial_bot_settings_for_validation)) {
        wp_send_json_error(['message' => __('Could not load chatbot configuration.', 'gpt3-ai-content-generator')], 500);
        return;
    }

    $validation_result = $validator->validate($post_data, $client_ip, $initial_bot_settings_for_validation);
    if (is_wp_error($validation_result)) {
        $status_code = is_array($validation_result->get_error_data()) && isset($validation_result->get_error_data()['status'])
                       ? $validation_result->get_error_data()['status']
                       : 400;
        wp_send_json_error(['message' => $validation_result->get_error_message()], $status_code);
        return;
    }

    $bot_id            = $validation_result['bot_id'];
    $user_id           = $validation_result['user_id'];
    $user_message_text = $validation_result['user_message_text'];
    $session_id        = $validation_result['session_id'];
    $conversation_uuid = $validation_result['conversation_uuid'];
    $image_inputs_json = $validation_result['image_inputs_json'];
    $validated_active_pinecone_index_name = $validation_result['active_pinecone_index_name'] ?? null;
    $validated_active_pinecone_namespace  = $validation_result['active_pinecone_namespace'] ?? null;

    // --- 2. Process Image Input ---
    $image_inputs_for_service = $image_processor->process($image_inputs_json);

    // --- 3. Build Initial Context (Bot Settings, User Info, IP, etc.) ---
    // Context builder now also handles Pinecone parameters if they exist in $validation_result
    $context = $context_builder->build_context($validation_result, $client_ip, $post_id_from_request, $frontend_active_openai_vs_id_from_post);
    $bot_settings = $context['bot_settings'];

    // --- 4. Log Initial User Message & Determine if New Session ---
    $user_log_result = $response_logger->log_user_message_initial($context['base_log_data'], $user_message_text, $image_inputs_for_service);
    if (is_wp_error($user_log_result)) {
        wp_send_json_error(['message' => $user_log_result->get_error_message()], 500);
        return;
    }
    $is_new_session = $user_log_result['is_new_session'] ?? false;

    // --- 5. Get Conversation History ---
    $history_for_ai = $history_manager->get_limited_history($user_id, $session_id, $bot_id, $conversation_uuid, $bot_settings);
    $context['current_history'] = $history_for_ai;
    $context['message_count'] = count($history_for_ai);
    $context['log_storage'] = $processorInstance->get_log_storage();

    // --- 6. Run Triggers ---
    $trigger_processing_result = $trigger_runner->run_triggers($context, $is_new_session);

    if ($trigger_processing_result['status'] === 'blocked' || ($trigger_processing_result['status'] === 'ai_stopped' && isset($trigger_processing_result['message_to_user']))) {
        $response_logger->send_trigger_json_response(
            $trigger_processing_result['status'],
            $trigger_processing_result['message_to_user'],
            $trigger_processing_result['message_id']
        );
        return;
    }

    // --- 7. Prepare AI Request Parameters (using potentially modified context from triggers) ---
    $final_user_message_for_ai       = $trigger_processing_result['final_user_message_for_ai'];
    $final_history_for_ai            = $trigger_processing_result['final_history_for_ai'];
    $final_system_instruction_for_ai = $trigger_processing_result['final_system_instruction_for_ai'];

    // --- 8. Make AI Call ---
    $frontend_previous_openai_response_id = isset($post_data['previous_openai_response_id']) ? sanitize_text_field($post_data['previous_openai_response_id']) : null;
    $frontend_openai_web_search_active = isset($post_data['frontend_web_search_active']) && $post_data['frontend_web_search_active'] === 'true';
    $frontend_google_search_grounding_active = isset($post_data['frontend_google_search_grounding_active']) && $post_data['frontend_google_search_grounding_active'] === 'true';

    $ai_result = $ai_request_runner->run_ai_request(
        $final_user_message_for_ai,
        $bot_settings,
        $final_history_for_ai,
        $final_system_instruction_for_ai,
        $context['post_id'],
        $frontend_previous_openai_response_id,
        $frontend_openai_web_search_active,
        $frontend_google_search_grounding_active,
        $image_inputs_for_service,
        $context['frontend_active_openai_vs_id'],
        $context['active_pinecone_index_name'], // Pass Pinecone index from context
        $context['active_pinecone_namespace']   // Pass Pinecone namespace from context
    );

    // --- 9. Log AI Response & Send JSON ---
    $base_log_data_for_bot_response = $context['base_log_data'];
    $response_logger->log_and_send_response($ai_result, $base_log_data_for_bot_response, $bot_settings, $user_id, $session_id);
}