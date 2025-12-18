<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/content-writer/ajax/actions/class-aipkit-content-writer-generate-tags-action.php
// Status: NEW FILE

namespace WPAICG\ContentWriter\Ajax\Actions;

use WPAICG\ContentWriter\Ajax\AIPKit_Content_Writer_Base_Ajax_Action;
use WPAICG\ContentWriter\Prompt\AIPKit_Content_Writer_Summarizer;
use WPAICG\ContentWriter\Prompt\AIPKit_Content_Writer_Tags_Prompt_Builder;
use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles the dedicated AJAX action for generating post tags.
 */
class AIPKit_Content_Writer_Generate_Tags_Action extends AIPKit_Content_Writer_Base_Ajax_Action
{
    public function handle(): void
    {
        $permission_check = $this->check_module_access_permissions('content-writer', 'aipkit_content_writer_nonce');
        if (is_wp_error($permission_check)) {
            $this->send_error_response($permission_check);
            return;
        }
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Reason: Nonce is checked in check_module_access_permissions.
        $generated_content = isset($_POST['generated_content']) ? wp_kses_post(wp_unslash($_POST['generated_content'])) : '';
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Reason: Nonce is checked in check_module_access_permissions.
        $final_title = isset($_POST['final_title']) ? sanitize_text_field(wp_unslash($_POST['final_title'])) : '';
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Reason: Nonce is checked in check_module_access_permissions.
        $keywords = isset($_POST['keywords']) ? sanitize_text_field(wp_unslash($_POST['keywords'])) : '';
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Reason: Nonce is checked in check_module_access_permissions.
        $provider_raw = isset($_POST['provider']) ? sanitize_text_field(wp_unslash($_POST['provider'])) : '';
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Reason: Nonce is checked in check_module_access_permissions.
        $model = isset($_POST['model']) ? sanitize_text_field(wp_unslash($_POST['model'])) : '';
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Reason: Nonce is checked in check_module_access_permissions.
        $prompt_mode = isset($_POST['prompt_mode']) ? sanitize_key($_POST['prompt_mode']) : 'standard';
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Reason: Nonce is checked in check_module_access_permissions.
        $custom_tags_prompt = isset($_POST['custom_tags_prompt']) ? sanitize_textarea_field(wp_unslash($_POST['custom_tags_prompt'])) : null;
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Reason: Nonce is checked in check_module_access_permissions.
        $content_max_tokens = isset($_POST['content_max_tokens']) ? intval($_POST['content_max_tokens']) : null;


        if (empty($generated_content) || empty($final_title) || empty($provider_raw) || empty($model)) {
            $this->send_error_response(new WP_Error('missing_tags_data', 'Missing required data for tags generation.', ['status' => 400]));
            return;
        }

        $provider = match (strtolower($provider_raw)) {
            'openai' => 'OpenAI', 'openrouter' => 'OpenRouter', 'google' => 'Google', 'azure' => 'Azure', 'deepseek' => 'DeepSeek', 'ollama' => 'Ollama',
            default => $provider_raw
        };

        if (!class_exists(AIPKit_Content_Writer_Summarizer::class) || !class_exists(AIPKit_Content_Writer_Tags_Prompt_Builder::class) || !$this->get_ai_caller()) {
            $this->send_wp_error(new WP_Error('missing_tags_dependencies', 'A component required for tags generation is missing.', ['status' => 500]));
            return;
        }

        $content_summary = AIPKit_Content_Writer_Summarizer::summarize($generated_content);
        $tags_user_prompt = AIPKit_Content_Writer_Tags_Prompt_Builder::build($final_title, $content_summary, $keywords, $prompt_mode, $custom_tags_prompt);
        $tags_system_instruction = 'You are an SEO expert. Your task is to provide a comma-separated list of relevant tags for a piece of content.';
        
        // Use the max tokens from template/form settings, or default to 100 for tags generation
        $max_tokens = isset($content_max_tokens) && $content_max_tokens > 0 ? $content_max_tokens : 4000;
        $tags_ai_params = ['max_completion_tokens' => $max_tokens];

        // DRY vector preparation via shared helper
        $ai_caller = $this->get_ai_caller();
        $vector_store_manager = $this->get_vector_store_manager();
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above
        $form_data = $_POST;
        if (!function_exists('WPAICG\\Core\\Stream\\Vector\\prepare_vector_standard_call')) {
            $helper_path = WPAICG_PLUGIN_DIR . 'classes/core/stream/vector/fn-prepare-vector-standard-call.php';
            if (file_exists($helper_path)) {
                require_once $helper_path;
            }
        }
        $tags_instruction_context = [];
        if (function_exists('WPAICG\\Core\\Stream\\Vector\\prepare_vector_standard_call')) {
            $prep = \WPAICG\Core\Stream\Vector\prepare_vector_standard_call(
                $ai_caller,
                $vector_store_manager,
                $tags_user_prompt,
                $form_data,
                $provider,
                $tags_system_instruction,
                $tags_ai_params
            );
            $tags_system_instruction = $prep['system_instruction'] ?? $tags_system_instruction;
            $tags_ai_params = $prep['ai_params'] ?? $tags_ai_params;
            $tags_instruction_context = $prep['instruction_context'] ?? [];
        }

        $tags_result = $this->get_ai_caller()->make_standard_call(
            $provider,
            $model,
            [['role' => 'user', 'content' => $tags_user_prompt]],
            $tags_ai_params,
            $tags_system_instruction,
            $tags_instruction_context
        );

        if (is_wp_error($tags_result)) {
            $this->send_wp_error($tags_result);
            return;
        }

        $tags = !empty($tags_result['content']) ? trim(str_replace(['"', "'"], '', $tags_result['content'])) : null;
        if (empty($tags)) {
            $this->send_wp_error(new WP_Error('tags_gen_empty', 'AI did not return any valid tags.', ['status' => 500]));
            return;
        }
        // Log using the same approach as title step
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $conversation_uuid = isset($_POST['conversation_uuid']) ? sanitize_text_field(wp_unslash($_POST['conversation_uuid'])) : '';
        if ($this->log_storage) {
            if (empty($conversation_uuid)) {
                if (function_exists('wp_generate_uuid4')) {
                    $conversation_uuid = wp_generate_uuid4();
                } else {
                    $conversation_uuid = uniqid('aipkit-', true);
                }
            }
            $current_user = wp_get_current_user();
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
            // User intent
            $this->log_storage->log_message(array_merge($base, [
                'message_role' => 'user',
                'message_content' => 'Generate Tags',
                'request_payload' => [
                    'title' => $final_title,
                    'keywords' => $keywords,
                    'prompt_mode' => $prompt_mode,
                    'custom_tags_prompt' => $custom_tags_prompt,
                ],
            ]));
            // Bot response
            $botLog = array_merge($base, [
                'message_role' => 'bot',
                'message_content' => $tags,
                'usage' => $tags_result['usage'] ?? null,
                'request_payload' => [
                    'provider' => $provider,
                    'model' => $model,
                    'payload_sent' => [
                        'messages' => [['role' => 'user', 'content' => $tags_user_prompt]],
                        'ai_params' => $tags_ai_params,
                        'system_instruction' => $tags_system_instruction,
                    ],
                ],
            ]);
            if (!empty($tags_result['vector_search_scores'])) {
                $botLog['vector_search_scores'] = $tags_result['vector_search_scores'];
            }
            $this->log_storage->log_message($botLog);
        }

        wp_send_json_success([
            'tags' => $tags,
            'usage' => $tags_result['usage'] ?? null,
            'conversation_uuid' => $conversation_uuid,
        ]);
    }
}
