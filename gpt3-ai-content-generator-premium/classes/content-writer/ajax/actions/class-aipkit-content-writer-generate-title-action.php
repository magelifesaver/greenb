<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/content-writer/ajax/actions/class-aipkit-content-writer-generate-title-action.php
// Status: MODIFIED

namespace WPAICG\ContentWriter\Ajax\Actions;

use WPAICG\ContentWriter\Ajax\AIPKit_Content_Writer_Base_Ajax_Action;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Load the new modular logic files
$logic_path = __DIR__ . '/generate-title/';
require_once $logic_path . 'validate-and-normalize-input.php';
require_once $logic_path . 'build-title-prompt.php';
require_once $logic_path . 'prepare-ai-params.php';
require_once $logic_path . 'call-title-generator.php';
require_once $logic_path . 'handle-title-response.php';


/**
 * Handles the AJAX action for generating a new title for content.
 * This class now orchestrates calls to modularized logic functions.
 */
class AIPKit_Content_Writer_Generate_Title_Action extends AIPKit_Content_Writer_Base_Ajax_Action
{
    /**
     * Handles the AJAX request to generate a title.
     */
    public function handle()
    {
        // 1. Validate input and permissions
        $validated_params = GenerateTitle\validate_and_normalize_input_logic($this);
        if (is_wp_error($validated_params)) {
            $this->send_wp_error($validated_params);
            return;
        }

        // 2. Build the prompt for the AI
        $prompts = GenerateTitle\build_title_prompt_logic($validated_params);

        // 3. Prepare AI-specific parameters
        $ai_params_override = GenerateTitle\prepare_ai_params_logic($validated_params);

        // 4. Call the AI provider
        $ai_result = GenerateTitle\call_title_generator_logic(
            $this,
            $validated_params['provider'],
            $validated_params['model'],
            [['role' => 'user', 'content' => $prompts['user_prompt']]],
            $ai_params_override,
            $prompts['system_instruction'],
            $validated_params // Pass the full form data for vector support
        );

    // 5. Handle the AI response (success or error) and log under conversation if provided
    GenerateTitle\handle_title_response_logic($this, $ai_result, $validated_params, $prompts, $ai_params_override);
    }
}
