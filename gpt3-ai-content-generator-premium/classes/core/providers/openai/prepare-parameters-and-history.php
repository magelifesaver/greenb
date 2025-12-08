<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/core/providers/openai/prepare-parameters-and-history.php
// Status: NEW FILE

namespace WPAICG\Core\Providers\OpenAI\Methods;

use WPAICG\AIPKit_Providers; // Required for get_provider_data

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the prepare_parameters_and_history static method of OpenAIStatefulConversationHelper.
 */
function prepare_parameters_and_history_logic(
    array $ai_params,
    array $history,
    array $bot_settings,
    ?string $frontend_previous_openai_response_id
): array {
    if (!class_exists(\WPAICG\AIPKit_Providers::class)) {
        $providers_path = WPAICG_PLUGIN_DIR . 'classes/dashboard/class-aipkit_providers.php';
        if (file_exists($providers_path)) {
            require_once $providers_path;
        } else {
            return ['ai_params' => $ai_params, 'history' => $history];
        }
    }

    $provDataOpenAI = AIPKit_Providers::get_provider_data('OpenAI');
    $ai_params['store_conversation'] = $provDataOpenAI['store_conversation'] ?? '0';
    $ai_params['use_openai_conversation_state'] = ($bot_settings['openai_conversation_state_enabled'] ?? '0') === '1';

    if ($ai_params['use_openai_conversation_state']) {
        $actual_previous_response_id_to_use = null;
        if (!empty($frontend_previous_openai_response_id)) {
            $actual_previous_response_id_to_use = $frontend_previous_openai_response_id;
        } elseif (!empty($history)) {
            $last_bot_msg_with_id = null;
            for ($i = count($history) - 1; $i >= 0; $i--) {
                if (($history[$i]['role'] === 'bot' || $history[$i]['role'] === 'assistant') && !empty($history[$i]['openai_response_id'])) {
                    $last_bot_msg_with_id = $history[$i]['openai_response_id'];
                    break;
                }
            }
            if ($last_bot_msg_with_id) {
                $actual_previous_response_id_to_use = $last_bot_msg_with_id;
            }
        }

        if ($actual_previous_response_id_to_use) {
            $ai_params['previous_response_id'] = $actual_previous_response_id_to_use;
            $latest_user_message_obj = end($history);
            if ($latest_user_message_obj && ($latest_user_message_obj['role'] === 'user' || $latest_user_message_obj['role'] === 'customer')) {
                $history = [$latest_user_message_obj];
            }
        }
    }

    return ['ai_params' => $ai_params, 'history' => $history];
}
