<?php
// File: classes/core/providers/google/ajax-sync-tts-voices.php
// Status: NEW FILE

namespace WPAICG\Core\Providers\Google\Methods;

use WPAICG\AIPKit_Providers;
use WPAICG\AIPKit_Role_Manager;
use WPAICG\Speech\AIPKit_TTS_Provider_Strategy_Factory;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the ajax_sync_google_tts_voices static method of GoogleSettingsHandler.
 *
 * @param string $option_name The name of the WordPress option to store synced voices.
 */
function ajax_sync_google_tts_voices_logic(string $option_name) {
    if (!\WPAICG\AIPKit_Role_Manager::user_can_access_module('settings')) {
        wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'gpt3-ai-content-generator')], 403);
        return;
    }
    if (!check_ajax_referer('aipkit_nonce', '_ajax_nonce', false)) {
        wp_send_json_error(['message' => __('Security check failed.', 'gpt3-ai-content-generator')], 403);
        return;
    }

    if (!class_exists(\WPAICG\AIPKit_Providers::class)) {
         wp_send_json_error(['message' => __('Provider configuration missing.', 'gpt3-ai-content-generator')], 500);
         return;
    }
    $google_data = \WPAICG\AIPKit_Providers::get_provider_data('Google');
    $api_key = $google_data['api_key'] ?? null;
    if (empty($api_key)) {
        wp_send_json_error(['message' => __('Google API key is required to sync voices.', 'gpt3-ai-content-generator')], 400);
        return;
    }

    if (!class_exists(\WPAICG\Speech\AIPKit_TTS_Provider_Strategy_Factory::class)) {
        wp_send_json_error(['message' => __('TTS components missing.', 'gpt3-ai-content-generator')], 500);
        return;
    }

    $strategy = \WPAICG\Speech\AIPKit_TTS_Provider_Strategy_Factory::get_strategy('Google');
    if (is_wp_error($strategy)) {
        wp_send_json_error(['message' => $strategy->get_error_message()], 500);
        return;
    }

    $voices = $strategy->get_voices(['api_key' => $api_key]);
    if (is_wp_error($voices)) {
         $error_data = $voices->get_error_data();
         $status_code = isset($error_data['status']) ? (int)$error_data['status'] : 500;
         wp_send_json_error(['message' => $voices->get_error_message()], $status_code);
        return;
    }

    update_option($option_name, $voices, 'no');

    wp_send_json_success([
        'message' => __('Google voices synced successfully.', 'gpt3-ai-content-generator'),
        'voices'  => $voices
    ]);
}