<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/content-writer/prompt/class-aipkit-content-writer-system-instruction-builder.php
// Status: MODIFIED

namespace WPAICG\ContentWriter\Prompt;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Builds the system instruction for the Content Writer module.
 * UPDATED: Simplified to a generic instruction as guided fields have been removed.
 */
class AIPKit_Content_Writer_System_Instruction_Builder
{
    /**
     * Builds the system instruction.
     *
     * @param array $settings User-defined settings from the Content Writer form.
     *                        No longer used as guided fields are removed, but kept for signature consistency.
     * @return string The system instruction.
     */
    public static function build(array $settings): string
    {
        $instruction = "You are an expert content writer specializing in creating high-quality, engaging content.";
        return trim($instruction);
    }
}
