<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/content-writer/prompt/class-aipkit-content-writer-user-prompt-builder.php
// Status: MODIFIED

namespace WPAICG\ContentWriter\Prompt;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Builds the user prompt for the Content Writer module.
 * UPDATED: Simplified to only use custom prompts, as Guided Mode has been removed.
 */
class AIPKit_Content_Writer_User_Prompt_Builder
{
    /**
     * Builds the main user prompt based on the custom prompt setting.
     *
     * @param array $settings User-defined settings from the Content Writer form.
     *                        Expected keys: 'custom_content_prompt'.
     * @return string The user prompt.
     */
    public static function build(array $settings): string
    {
        // Always use the custom content prompt. The caller will handle replacing placeholders.
        return trim($settings['custom_content_prompt'] ?? '');
    }
}
