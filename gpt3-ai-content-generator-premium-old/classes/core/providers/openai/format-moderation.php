<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/core/providers/openai/format-moderation.php
// Status: NEW FILE

namespace WPAICG\Core\Providers\OpenAI\Methods;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the format_moderation static method of OpenAIPayloadFormatter.
 */
function format_moderation_logic_for_payload_formatter(string $text): array {
    return ['input' => $text];
}