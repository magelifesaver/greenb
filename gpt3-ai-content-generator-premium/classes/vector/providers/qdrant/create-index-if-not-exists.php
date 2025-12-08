<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/vector/providers/qdrant/create-index-if-not-exists.php

namespace WPAICG\Vector\Providers\Qdrant\Methods;

use WPAICG\Vector\Providers\AIPKit_Vector_Qdrant_Strategy;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the create_index_if_not_exists method of AIPKit_Vector_Qdrant_Strategy.
 *
 * @param AIPKit_Vector_Qdrant_Strategy $strategyInstance The instance of the strategy class.
 * @param string $index_name The name of the index (collection) to create.
 * @param array $index_config Configuration for the index.
 * @return array|WP_Error The collection object or WP_Error on failure.
 */
function create_index_if_not_exists_logic(AIPKit_Vector_Qdrant_Strategy $strategyInstance, string $index_name, array $index_config): array|WP_Error {
    $describe_response = describe_index_logic($strategyInstance, $index_name);
    if (!is_wp_error($describe_response) && isset($describe_response['status'])) {
        return $describe_response;
    } elseif (is_wp_error($describe_response) && $describe_response->get_error_data()['status'] !== 404) {
        return $describe_response;
    }

    $path = '/collections/' . urlencode($index_name);
    $metric = ucfirst(strtolower($index_config['metric'] ?? 'Cosine'));
    if (!in_array($metric, ['Cosine', 'Euclid', 'Dot'])) {
        $metric = 'Cosine';
    }
    $vector_params = ['size' => absint($index_config['dimension'] ?? 1536), 'distance' => $metric];
    $body = ['vectors' => $vector_params];
    if (isset($index_config['hnsw_config'])) $body['hnsw_config'] = $index_config['hnsw_config'];
    if (isset($index_config['wal_config'])) $body['wal_config'] = $index_config['wal_config'];
    if (isset($index_config['optimizers_config'])) $body['optimizers_config'] = $index_config['optimizers_config'];

    $create_response = _request_logic($strategyInstance, 'PUT', $path, $body);
    if (is_wp_error($create_response)) {
        return $create_response;
    }
    if (is_array($create_response) && ($create_response['status'] ?? null) === 'ok') {
        sleep(1);
        return describe_index_logic($strategyInstance, $index_name);
    }
    return new WP_Error('qdrant_create_unknown_error', __('Unknown error creating Qdrant collection.', 'gpt3-ai-content-generator'));
}