<?php
// File: classes/core/providers/google/parse-embeddings.php
// Status: NEW FILE

namespace WPAICG\Core\Providers\Google\Methods;

use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the parse_embeddings static method of GoogleResponseParser.
 *
 * @param array $decoded_response The decoded JSON response body.
 * @return array|WP_Error ['embeddings' => array, 'usage' => array|null] or WP_Error.
 */
function parse_embeddings_logic_for_response_parser(array $decoded_response): array|WP_Error {
    $embeddings = [];
    if (isset($decoded_response['embedding']['values']) && is_array($decoded_response['embedding']['values'])) { 
        $embeddings[] = $decoded_response['embedding']['values']; 
    } elseif (isset($decoded_response['embeddings']) && is_array($decoded_response['embeddings'])) {
        foreach ($decoded_response['embeddings'] as $emb_item) {
            if (isset($emb_item['values']) && is_array($emb_item['values'])) { 
                $embeddings[] = $emb_item['values']; 
            } elseif (isset($emb_item['embedding']) && is_array($emb_item['embedding'])) { 
                $embeddings[] = $emb_item['embedding'];
            }
        }
    }

    if (empty($embeddings)) {
        return new WP_Error('google_embedding_no_data_logic', __('No embedding data found in Google response.', 'gpt3-ai-content-generator'));
    }
    return ['embeddings' => $embeddings, 'usage' => null];
}