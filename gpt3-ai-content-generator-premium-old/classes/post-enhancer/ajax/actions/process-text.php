<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/post-enhancer/ajax/actions/process-text.php
// Status: MODIFIED
// I have added a preg_replace call to convert markdown-style links into HTML <a> tags before the content is sent back to the editor.

namespace WPAICG\PostEnhancer\Ajax\Actions;

use WPAICG\PostEnhancer\Ajax\Base\AIPKit_Post_Enhancer_Base_Ajax_Action;
use WPAICG\Core\AIPKit_AI_Caller;
use WPAICG\AIPKit_Providers;
use WPAICG\AIPKIT_AI_Settings;
use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

class AIPKit_PostEnhancer_Process_Text extends AIPKit_Post_Enhancer_Base_Ajax_Action
{
    public function handle(): void
    {
        $permission_check = $this->check_permissions('aipkit_process_enhancer_text_nonce');
        if (is_wp_error($permission_check)) {
            $this->send_error_response($permission_check);
            return;
        }

        // --- MODIFIED: Expect final_prompt instead of process_action ---
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Reason: Nonce is checked in check_permissions.
        $final_prompt = isset($_POST['final_prompt']) ? wp_kses_post(wp_unslash($_POST['final_prompt'])) : '';
        // text_to_process is still useful for context but the prompt is king
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Reason: Nonce is checked in check_permissions.
        $text_to_process = isset($_POST['text_to_process']) ? wp_kses_post(wp_unslash($_POST['text_to_process'])) : '';

        if (empty($text_to_process) || empty($final_prompt)) {
            $this->send_error_response(new WP_Error('missing_params', __('Text and a prompt are required.', 'gpt3-ai-content-generator'), ['status' => 400]));
            return;
        }
        // --- END MODIFICATION ---

        // AI Call setup
        $global_config = AIPKit_Providers::get_default_provider_config();
        $ai_params = AIPKIT_AI_Settings::get_ai_parameters();
        $provider = $global_config['provider'];
        $model = $global_config['model'];
        $ai_caller = new AIPKit_AI_Caller();
        $messages = [['role' => 'user', 'content' => $final_prompt]];

        $result = $ai_caller->make_standard_call($provider, $model, $messages, $ai_params);
        if (is_wp_error($result)) {
            $this->send_error_response($result);
            return;
        }

        $new_text_raw = $result['content'] ?? '';

        // --- START: Convert markdown to HTML ---
        $html_content = $new_text_raw;
        $html_content = preg_replace('/^#\s+(.*)$/m', '<h1>$1</h1>', $html_content);
        $html_content = preg_replace('/^##\s+(.*)$/m', '<h2>$1</h2>', $html_content);
        $html_content = preg_replace('/^###\s+(.*)$/m', '<h3>$1</h3>', $html_content);
        $html_content = preg_replace('/^####\s+(.*)$/m', '<h4>$1</h4>', $html_content);
        $html_content = preg_replace('/\*\*(.*?)\*\*/s', '<strong>$1</strong>', $html_content);
        $html_content = preg_replace('/(?<!\*)\*(?!\*|_)(.*?)(?<!\*|_)\*(?!\*)/s', '<em>$1</em>', $html_content);
        // Convert links: [text](url) -> <a href="url">text</a>
        $html_content = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2">$1</a>', $html_content);
        // --- END: Convert markdown to HTML ---

        wp_send_json_success(['text' => $html_content]);
    }
}
