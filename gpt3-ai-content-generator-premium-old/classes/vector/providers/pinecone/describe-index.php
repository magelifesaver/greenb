<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/vector/providers/pinecone/describe-index.php
// Status: NEW

namespace WPAICG\Vector\Providers\Pinecone\Methods;

use WPAICG\Vector\Providers\AIPKit_Vector_Pinecone_Strategy;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the describe_index method of AIPKit_Vector_Pinecone_Strategy.
 *
 * @param AIPKit_Vector_Pinecone_Strategy $strategyInstance The instance of the strategy class.
 * @param string $index_name The name of the index to describe.
 * @return array|WP_Error Index details or WP_Error.
 */
function describe_index_logic(AIPKit_Vector_Pinecone_Strategy $strategyInstance, string $index_name): array|WP_Error {
    $path = '/indexes/' . urlencode($index_name);
    $description = _request_logic($strategyInstance, 'GET', $path);
    if (is_wp_error($description)) {
        return $description;
    }

    // Now get stats from the data plane
    $host = $description['host'] ?? null;
    if (empty($host)) {
        // This is not a fatal error, just means we can't get stats. Return what we have.
        $description['total_vector_count'] = 'No Host';
        return $description;
    }

    // The stats endpoint is /describe_index_stats
    $stats_response = _request_logic($strategyInstance, 'POST', '/describe_index_stats', [], 'https://' . $host);
    if (is_wp_error($stats_response)) {
        $description['total_vector_count'] = 'Error';
    } else {
        // Merge stats into the description object
        $description['total_vector_count'] = $stats_response['totalVectorCount'] ?? $stats_response['total_vector_count'] ?? 0;
        if (isset($stats_response['namespaces'])) {
            $description['namespaces'] = $stats_response['namespaces'];
        }
    }

    return $description;
}