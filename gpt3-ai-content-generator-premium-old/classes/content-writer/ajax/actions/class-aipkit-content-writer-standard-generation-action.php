<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/content-writer/ajax/actions/class-aipkit-content-writer-standard-generation-action.php
// Status: MODIFIED

namespace WPAICG\ContentWriter\Ajax\Actions;

use WPAICG\ContentWriter\Ajax\AIPKit_Content_Writer_Base_Ajax_Action;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Load the new modular logic files
$shared_path = __DIR__ . '/shared/';
require_once $shared_path . 'validate-and-normalize-input.php';
require_once $shared_path . 'build-prompts.php';
require_once $shared_path . 'prepare-ai-params.php';
require_once $shared_path . 'log-initial-request.php';

$standard_gen_path = __DIR__ . '/standard-generation/';
require_once $standard_gen_path . 'call-ai-provider.php';
require_once $standard_gen_path . 'handle-error-response.php';
require_once $standard_gen_path . 'handle-success-response.php';


/**
 * Handles the AJAX action for standard (non-streaming) content generation.
 * This class now acts as an orchestrator for modularized logic functions.
 */
class AIPKit_Content_Writer_Standard_Generation_Action extends AIPKit_Content_Writer_Base_Ajax_Action
{
    /**
     * Handles the AJAX request for standard content generation by orchestrating calls to modular functions.
     */
    public function handle()
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked in Shared\validate_and_normalize_input_logic().
        $settings = isset($_POST) ? wp_unslash($_POST) : [];

        // 1. Validate input and check permissions
        $validated_params = Shared\validate_and_normalize_input_logic($this, $settings);
        if (is_wp_error($validated_params)) {
            $this->send_wp_error($validated_params);
            return;
        }

        // 2. Check for required dependencies (AI Caller, Logger)
        if (!$this->ai_caller) {
            $this->send_wp_error(new WP_Error('ai_caller_missing', __('AI processing component is unavailable.', 'gpt3-ai-content-generator')), 500);
            return;
        }

        // 3. Build prompts
        $prompts = Shared\build_prompts_logic($validated_params);
        if (is_wp_error($prompts)) {
            $this->send_wp_error($prompts);
            return;
        }

        // --- NEW: Replace {topic} placeholder with the final title ---
        $final_title = $validated_params['content_title'];
        $final_user_prompt = str_replace('{topic}', $final_title, $prompts['user_prompt']);
        // --- END NEW ---

        // 4. Prepare AI parameters
        $ai_params_override = Shared\prepare_ai_params_logic($validated_params);

        // 5. Determine conversation UUID (reuse if provided, else create)
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $conversation_uuid = isset($_POST['conversation_uuid']) && !empty($_POST['conversation_uuid'])
            ? sanitize_text_field(wp_unslash($_POST['conversation_uuid']))
            : wp_generate_uuid4();
        // Attach to params so the initial log uses the same conversation
        $validated_params['conversation_uuid'] = $conversation_uuid;
        Shared\log_initial_request_logic($this, $validated_params, 'AJAX');

        // 6. Make the AI call
        $ai_result = StandardGeneration\call_ai_provider_logic(
            $this,
            $validated_params['provider'],
            $validated_params['model'],
            [['role' => 'user', 'content' => $final_user_prompt]], // Use the final prompt
            $ai_params_override,
            $prompts['system_instruction']
        );

        // 7. Handle the response (success or error)
        if (is_wp_error($ai_result)) {
            StandardGeneration\handle_error_response_logic($this, $ai_result, $validated_params, $conversation_uuid);
        } else {
            StandardGeneration\handle_success_response_logic($this, $ai_result, $validated_params, $conversation_uuid);
        }
    }
}
