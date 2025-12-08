<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/vector/providers/pinecone/create-index-if-not-exists.php
// Status: NEW

namespace WPAICG\Vector\Providers\Pinecone\Methods;

use WPAICG\Vector\Providers\AIPKit_Vector_Pinecone_Strategy;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the create_index_if_not_exists method of AIPKit_Vector_Pinecone_Strategy.
 *
 * @param AIPKit_Vector_Pinecone_Strategy $strategyInstance The instance of the strategy class.
 * @param string $index_name The name of the index to create.
 * @param array $index_config Configuration for the index.
 * @return array|WP_Error The index object or WP_Error on failure.
 */
function create_index_if_not_exists_logic(AIPKit_Vector_Pinecone_Strategy $strategyInstance, string $index_name, array $index_config): array|WP_Error {
    $list_response = list_indexes_logic($strategyInstance); // Use externalized list_indexes_logic
    if (!is_wp_error($list_response) && is_array($list_response)) {
        foreach ($list_response as $existing_index) {
            if (isset($existing_index['name']) && $existing_index['name'] === $index_name) {
                return describe_index_logic($strategyInstance, $index_name); // Use externalized describe_index_logic
            }
        }
    }

    $path = '/indexes';
    $body = [
        'name' => $index_name,
        'dimension' => absint($index_config['dimension'] ?? 1536),
        'metric' => strtolower($index_config['metric'] ?? 'cosine'),
        'spec' => $index_config['spec'] ?? [
            'serverless' => [
                'cloud' => $index_config['cloud'] ?? 'aws',
                'region' => $index_config['region'] ?? 'us-east-1'
            ]
        ]
    ];
    if (isset($index_config['deletion_protection'])) $body['deletion_protection'] = $index_config['deletion_protection'];

    $response = _request_logic($strategyInstance, 'POST', $path, $body);

    if (is_wp_error($response)) {
        return $response;
    }

    if (is_array($response) && isset($response['name'])) {
        sleep(15); // Wait for index to become available
        return describe_index_logic($strategyInstance, $index_name); // Use externalized describe_index_logic
    }
    return new WP_Error('pinecone_create_unknown_error', __('Unknown error creating Pinecone index.', 'gpt3-ai-content-generator'));
}