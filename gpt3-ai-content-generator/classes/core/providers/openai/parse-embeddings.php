<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/core/providers/openai/parse-embeddings.php
// Status: NEW FILE

namespace WPAICG\Core\Providers\OpenAI\Methods;

use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the parse_embeddings static method of OpenAIResponseParser.
 */
function parse_embeddings_logic_for_response_parser(array $decoded_response): array|WP_Error {
    $embeddings = [];
    if (isset($decoded_response['data']) && is_array($decoded_response['data'])) {
        foreach ($decoded_response['data'] as $item) {
            if (isset($item['embedding']) && is_array($item['embedding'])) {
                $embeddings[] = $item['embedding'];
            }
        }
    }

    if (empty($embeddings)) {
        return new WP_Error('openai_embedding_no_data_logic', __('No embedding data found in OpenAI response.', 'gpt3-ai-content-generator'));
    }

    $usage = null;
    if (isset($decoded_response['usage']) && is_array($decoded_response['usage'])) {
        $usage = [
            'input_tokens'  => $decoded_response['usage']['prompt_tokens'] ?? 0,
            'total_tokens'  => $decoded_response['usage']['total_tokens'] ?? 0,
            'provider_raw' => $decoded_response['usage'],
        ];
    }

    return ['embeddings' => $embeddings, 'usage' => $usage];
}