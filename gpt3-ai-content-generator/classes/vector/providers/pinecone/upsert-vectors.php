<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/vector/providers/pinecone/upsert-vectors.php
// Status: NEW

namespace WPAICG\Vector\Providers\Pinecone\Methods;

use WPAICG\Vector\Providers\AIPKit_Vector_Pinecone_Strategy;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the upsert_vectors method of AIPKit_Vector_Pinecone_Strategy.
 *
 * @param AIPKit_Vector_Pinecone_Strategy $strategyInstance The instance of the strategy class.
 * @param string $index_name The name of the index.
 * @param array $vectors_data Data containing vectors and optional namespace.
 * @return array|WP_Error Result of the upsert operation or WP_Error.
 */
function upsert_vectors_logic(AIPKit_Vector_Pinecone_Strategy $strategyInstance, string $index_name, array $vectors_data): array|WP_Error {
    $index_description = describe_index_logic($strategyInstance, $index_name); // Use externalized describe_index_logic
    if (is_wp_error($index_description)) return $index_description;
    $host = $index_description['host'] ?? null;
    if (empty($host)) return new WP_Error('missing_host_pinecone_upsert', __('Index host not found for upsert operation.', 'gpt3-ai-content-generator'));

    $path = '/vectors/upsert';
    $vectors_list = $vectors_data['vectors'] ?? $vectors_data;
    if (!is_array($vectors_list)) {
        return new WP_Error('pinecone_upsert_invalid_vectors', __('Invalid vectors payload for upsert.', 'gpt3-ai-content-generator'));
    }

    // Optional: Dimension pre-validation (if dimension available from index description)
    $expected_dim = isset($index_description['dimension']) ? (int) $index_description['dimension'] : null;
    if ($expected_dim && $expected_dim > 0) {
        foreach ($vectors_list as $vec) {
            $values = $vec['values'] ?? $vec['vector'] ?? null;
            if (is_array($values) && count($values) !== $expected_dim) {
                return new WP_Error('vector_dimension_mismatch', sprintf(__('Vector dimension mismatch. Expected %1$d, got %2$d.', 'gpt3-ai-content-generator'), $expected_dim, count($values)));
            }
        }
    } else {
        // Fallback internal consistency check: ensure all vectors have same length
        $first_len = null;
        foreach ($vectors_list as $vec) {
            $values = $vec['values'] ?? $vec['vector'] ?? null;
            if (!is_array($values)) continue;
            $len = count($values);
            if ($first_len === null) { $first_len = $len; }
            if ($first_len !== $len) {
                return new WP_Error('vector_dimension_inconsistent', __('Vectors have inconsistent dimensions in the upsert payload.', 'gpt3-ai-content-generator'));
            }
        }
    }

    // Batch upsert to avoid oversized requests/timeouts
    $namespace = $vectors_data['namespace'] ?? null;
    $batch_size = apply_filters('aipkit_pinecone_upsert_batch_size', 100, $index_name);
    $all_results = [];
    $total = count($vectors_list);
    for ($i = 0; $i < $total; $i += $batch_size) {
        $chunk = array_slice($vectors_list, $i, $batch_size);
        $body = ['vectors' => $chunk];
        if ($namespace) { $body['namespace'] = $namespace; }
        $resp = _request_logic($strategyInstance, 'POST', $path, $body, 'https://' . $host);
        if (is_wp_error($resp)) return $resp;
        $all_results[] = $resp;
    }

    // Return last response plus summary
    $last = end($all_results);
    if (is_array($last)) {
        $last['aipkit_batches'] = count($all_results);
    }
    return $last;
}
