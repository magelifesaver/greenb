<?php
// File: classes/core/providers/azure/parse-embeddings.php

namespace WPAICG\Core\Providers\Azure\Methods;

use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the parse_embeddings static method of AzureResponseParser.
 *
 * @param array $decoded_response The decoded JSON response body.
 * @return array|WP_Error ['embeddings' => array, 'usage' => array] or WP_Error.
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
        if (isset($decoded_response['error']['code']) && $decoded_response['error']['code'] === 'ContentFilter') {
            return new WP_Error('azure_embedding_content_filter_logic', __('Input blocked by Azure content filter.', 'gpt3-ai-content-generator'));
        }
        return new WP_Error('azure_embedding_no_data_logic', __('No embedding data found in Azure response.', 'gpt3-ai-content-generator'));
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