<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/images/manager/ajax/ajax_check_video_status.php
// Status: NEW FILE

namespace WPAICG\Images\Manager\Ajax;

use WPAICG\Images\AIPKit_Image_Manager;
use WPAICG\Images\Providers\Google\GoogleVideoResponseParser;
use WPAICG\AIPKit_Role_Manager;
use WPAICG\Core\TokenManager\Constants\GuestTableConstants;
use WPAICG\aipkit_dashboard;
use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

function ajax_check_video_status_logic(AIPKit_Image_Manager $managerInstance): void
{
    // Unslash all POST data at the beginning for security
    $post_data = wp_unslash($_POST);
    
    $user_id = get_current_user_id();
    $is_logged_in = $user_id > 0;

    // Check nonce
    if (!isset($post_data['_ajax_nonce']) || !wp_verify_nonce(sanitize_key($post_data['_ajax_nonce']), 'aipkit_image_generator_nonce')) {
        wp_send_json_error(['message' => __('Security check failed (nonce).', 'gpt3-ai-content-generator')], 403);
        return;
    }

    // Check permissions (same as generate_image)
    if ($is_logged_in && !AIPKit_Role_Manager::user_can_access_module($managerInstance::MODULE_SLUG)) {
        wp_send_json_error(['message' => __('You do not have permission to check video status.', 'gpt3-ai-content-generator')], 403);
        return;
    }

    // Get required parameters
    $operation_name = isset($post_data['operation_name']) ? sanitize_text_field($post_data['operation_name']) : '';
    $model_id = isset($post_data['model_id']) ? sanitize_text_field($post_data['model_id']) : '';
    $prompt = isset($post_data['prompt']) ? sanitize_textarea_field($post_data['prompt']) : '';

    if (empty($operation_name) || empty($model_id)) {
        wp_send_json_error(['message' => __('Missing required parameters for video status check.', 'gpt3-ai-content-generator')], 400);
        return;
    }

    // Get API key from server-side provider configuration (secure)
    if (!class_exists('WPAICG\AIPKit_Providers')) {
        $providers_path = WPAICG_PLUGIN_DIR . 'classes/class-aipkit_providers.php';
        if (file_exists($providers_path)) {
            require_once $providers_path;
        }
    }

    if (!class_exists('WPAICG\AIPKit_Providers')) {
        wp_send_json_error(['message' => __('Provider configuration not available.', 'gpt3-ai-content-generator')], 500);
        return;
    }

    $api_params = \WPAICG\AIPKit_Providers::get_provider_data('Google');
    
    if (empty($api_params['api_key'])) {
        wp_send_json_error(['message' => __('Google API Key is not configured.', 'gpt3-ai-content-generator')], 500);
        return;
    }

    // Prepare API params with defaults
    $api_params = array_merge($api_params, [
        'base_url' => $api_params['base_url'] ?? 'https://generativelanguage.googleapis.com',
        'api_version' => $api_params['api_version'] ?? 'v1beta'
    ]);

    // Load all required Google video classes
    $google_video_dir = WPAICG_PLUGIN_DIR . 'classes/images/providers/google/';

    // Load GoogleVideoUrlBuilder (required for polling)
    $url_builder_file = $google_video_dir . 'GoogleVideoUrlBuilder.php';
    
    if (!class_exists('WPAICG\Images\Providers\Google\GoogleVideoUrlBuilder')) {
        if (file_exists($url_builder_file)) {
            require_once $url_builder_file;
        }
    }
    
    // Load GoogleVideoResponseParser 
    $response_parser_file = $google_video_dir . 'GoogleVideoResponseParser.php';
    
    if (!class_exists(GoogleVideoResponseParser::class)) {
        if (file_exists($response_parser_file)) {
            require_once $response_parser_file;
        }
    }

    // Verify all required classes are loaded
    $response_parser_exists = class_exists(GoogleVideoResponseParser::class);
    $url_builder_exists = class_exists('WPAICG\Images\Providers\Google\GoogleVideoUrlBuilder');
    
    if (!$response_parser_exists) {
        wp_send_json_error(['message' => __('Video response parser not available.', 'gpt3-ai-content-generator')], 500);
        return;
    }
    
    if (!$url_builder_exists) {
        wp_send_json_error(['message' => __('Video URL builder not available.', 'gpt3-ai-content-generator')], 500);
        return;
    }
    
    // Check the operation status - now includes prompt and user information
    try {
        $status_result = GoogleVideoResponseParser::check_operation_status(
            $operation_name, 
            $model_id, 
            $api_params, 
            $prompt, 
            $is_logged_in ? $user_id : null
        );
    } catch (Error $e) {
        wp_send_json_error(['message' => 'Internal error during video status check: ' . $e->getMessage()]);
        return;
    } catch (Exception $e) {
        wp_send_json_error(['message' => 'Error during video status check: ' . $e->getMessage()]);
        return;
    }

    if (is_wp_error($status_result)) {
        wp_send_json_error(['message' => $status_result->get_error_message()]);
        return;
    }
    // Handle the different response types
    if (isset($status_result['status'])) {
        if ($status_result['status'] === 'processing') {
            // Still processing
            wp_send_json_success([
                'status' => 'processing',
                'message' => $status_result['message']
            ]);
        } elseif ($status_result['status'] === 'completed') {
            // Completed - record token usage and return video data
            $videos_array = $status_result['videos'] ?? [];
            $usage_data = $status_result['usage'] ?? null;
            
            // Record token usage when video generation completes
            $token_manager = $managerInstance->get_token_manager();
            if (aipkit_dashboard::is_addon_active('token_management') && $token_manager && !empty($videos_array)) {
                $videos_generated_count = count($videos_array);
                $tokens_to_record = $usage_data['total_tokens'] ?? ($videos_generated_count * $managerInstance::TOKENS_PER_IMAGE);
                
                if ($tokens_to_record > 0) {
                    $context_id_for_token_record = $is_logged_in ? GuestTableConstants::IMG_GEN_GUEST_CONTEXT_ID : GuestTableConstants::IMG_GEN_GUEST_CONTEXT_ID;
                    
                    // Get session ID for guests (this should match the session from the original generation request)
                    $session_id_for_guest = null;
                    if (!$is_logged_in) {
                        $session_id_for_guest = isset($post_data['session_id']) ? sanitize_text_field($post_data['session_id']) : null;
                        if (empty($session_id_for_guest) && class_exists('\WPAICG\AIPKit\Addons\AIPKit_IP_Anonymization')) {
                            $client_ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : null;
                            $session_id_for_guest = \WPAICG\AIPKit\Addons\AIPKit_IP_Anonymization::maybe_anonymize($client_ip);
                        }
                    }
                    
                    $token_manager->record_token_usage($user_id ?: null, $session_id_for_guest, $context_id_for_token_record, $tokens_to_record, 'image_generator');
                }
            }
            
            wp_send_json_success([
                'status' => 'completed',
                'videos' => $videos_array,
                'usage' => $usage_data,
                'message' => __('Video generation completed successfully!', 'gpt3-ai-content-generator')
            ]);
        } else {
            // Unknown status
            wp_send_json_error(['message' => __('Unknown video generation status.', 'gpt3-ai-content-generator')]);
        }
    } else {
        // Unexpected response format
        wp_send_json_error(['message' => __('Unexpected response format from video status check.', 'gpt3-ai-content-generator')]);
    }
} 