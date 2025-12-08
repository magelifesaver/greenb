<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/core/providers/openai/parse-moderation.php
// Status: NEW FILE

namespace WPAICG\Core\Providers\OpenAI\Methods;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the parse_moderation static method of OpenAIResponseParser.
 */
function parse_moderation_logic_for_response_parser(array $decoded_response): bool {
    return isset($decoded_response['results'][0]['flagged']) && $decoded_response['results'][0]['flagged'] === true;
}