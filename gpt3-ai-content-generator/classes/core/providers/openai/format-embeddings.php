<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/core/providers/openai/format-embeddings.php
// Status: NEW FILE

namespace WPAICG\Core\Providers\OpenAI\Methods;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the format_embeddings static method of OpenAIPayloadFormatter.
 */
function format_embeddings_logic_for_payload_formatter($input, array $options): array {
    $payload = [
        'input' => $input,
        'model' => $options['model'] ?? 'text-embedding-3-small',
    ];

    if (isset($options['dimensions']) && is_int($options['dimensions']) && $options['dimensions'] > 0) {
        $payload['dimensions'] = $options['dimensions'];
    }
    if (isset($options['encoding_format']) && in_array($options['encoding_format'], ['float', 'base64'])) {
        $payload['encoding_format'] = $options['encoding_format'];
    }
    if (isset($options['user']) && is_string($options['user'])) {
        $payload['user'] = $options['user'];
    }

    return $payload;
}