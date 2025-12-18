<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/content-writer/ajax/actions/generate-title/handle-title-response.php
// Status: NEW FILE

namespace WPAICG\ContentWriter\Ajax\Actions\GenerateTitle;

use WPAICG\ContentWriter\Ajax\Actions\AIPKit_Content_Writer_Generate_Title_Action;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Handles the response from the AI call, cleaning it and sending a JSON response.
 * Also logs the request/response under the same conversation if conversation_uuid is provided.
 *
 * @param AIPKit_Content_Writer_Generate_Title_Action $handler The handler instance.
 * @param array|WP_Error $result The result from the AI Caller.
 * @param array $validated_params The validated request parameters.
 * @param array $prompts The prompts array with 'user_prompt' and 'system_instruction'.
 * @param array $ai_params_override The AI params used.
 * @return void
 */
function handle_title_response_logic(
    AIPKit_Content_Writer_Generate_Title_Action $handler,
    array|WP_Error $result,
    array $validated_params,
    array $prompts,
    array $ai_params_override
): void
{
    if (is_wp_error($result)) {
        $handler->send_wp_error($result);
        return;
    }

    $generated_title = trim($result['content'] ?? '');

    // Clean up potential extra formatting from the AI
    if (preg_match('/^"(.*)"$/', $generated_title, $matches)) {
        $generated_title = $matches[1];
    }
    $generated_title = trim(str_replace(["\n", "\r"], ' ', $generated_title));
    $generated_title = preg_replace('/\s+/', ' ', $generated_title);

    if (empty($generated_title)) {
        $handler->send_wp_error(new WP_Error('title_gen_empty', __('AI did not return a valid title.', 'gpt3-ai-content-generator')), 500);
        return;
    }

    // Ensure logging under a conversation. Generate UUID if missing so first-run title is captured.
    // phpcs:ignore WordPress.Security.NonceVerification.Missing
    $conversation_uuid = isset($_POST['conversation_uuid']) ? sanitize_text_field(wp_unslash($_POST['conversation_uuid'])) : '';
    if ($handler->log_storage) {
        if (empty($conversation_uuid)) {
            if (function_exists('wp_generate_uuid4')) {
                $conversation_uuid = wp_generate_uuid4();
            } else {
                $conversation_uuid = uniqid('aipkit-', true);
            }
        }
        $current_user = wp_get_current_user();
        $provider = $validated_params['provider'] ?? '';
        $model = $validated_params['model'] ?? '';
        $base = [
            'bot_id' => null,
            'user_id' => get_current_user_id(),
            'session_id' => null,
            'conversation_uuid' => $conversation_uuid,
            'module' => 'content_writer',
            'is_guest' => 0,
            'role' => is_a($current_user, 'WP_User') ? implode(', ', $current_user->roles) : '',
            'ip_address' => isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : null,
            'timestamp' => time(),
            'ai_provider' => $provider,
            'ai_model' => $model,
        ];
        // User intent log
        $handler->log_storage->log_message(array_merge($base, [
            'message_role' => 'user',
            'message_content' => 'Generate Title',
            'request_payload' => [
                'original_topic' => $validated_params['content_title'] ?? '',
                'inline_keywords' => $validated_params['inline_keywords'] ?? '',
                'custom_title_prompt' => $validated_params['custom_title_prompt'] ?? '',
            ],
        ]));
        // Bot response log (surface vector_search_scores top-level like SSE)
        $botLog = array_merge($base, [
            'message_role' => 'bot',
            'message_content' => $generated_title,
            'usage' => $result['usage'] ?? null,
            'request_payload' => [
                'provider' => $provider,
                'model' => $model,
                'payload_sent' => [
                    'messages' => [['role' => 'user', 'content' => $prompts['user_prompt'] ?? '']],
                    'ai_params' => $ai_params_override,
                    'system_instruction' => $prompts['system_instruction'] ?? '',
                ],
            ],
        ]);
        if (!empty($result['vector_search_scores'])) {
            $botLog['vector_search_scores'] = $result['vector_search_scores'];
        }
        $handler->log_storage->log_message($botLog);
    }

    wp_send_json_success([
        'new_title' => $generated_title,
        'usage' => $result['usage'] ?? null,
        'conversation_uuid' => $conversation_uuid,
    ]);
}
