<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/vector/providers/qdrant/upsert-vectors.php

namespace WPAICG\Vector\Providers\Qdrant\Methods;

use WPAICG\Vector\Providers\AIPKit_Vector_Qdrant_Strategy;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the upsert_vectors method of AIPKit_Vector_Qdrant_Strategy.
 *
 * @param AIPKit_Vector_Qdrant_Strategy $strategyInstance The instance of the strategy class.
 * @param string $index_name The name of the index (collection).
 * @param array $vectors_data Data containing vectors to upsert.
 * @return array|WP_Error Result of the upsert operation or WP_Error.
 */
function upsert_vectors_logic(AIPKit_Vector_Qdrant_Strategy $strategyInstance, string $index_name, array $vectors_data): array|WP_Error {
    $path = '/collections/' . urlencode($index_name) . '/points';
    $body = [];
    $query_params = [];

    if (isset($vectors_data['points']) && is_array($vectors_data['points'])) {
        $body['points'] = $vectors_data['points'];
    } else {
        $body['points'] = $vectors_data;
    }
    foreach($body['points'] as &$point) {
        if (isset($point['values']) && !isset($point['vector'])) {
            $point['vector'] = $point['values'];
            unset($point['values']);
        }
        if (isset($point['metadata']) && !isset($point['payload'])) {
            $point['payload'] = $point['metadata'];
            unset($point['metadata']);
        }
    }
    unset($point);

    // Optional: dimension pre-validation via describe_index
    if (function_exists('WPAICG\\Vector\\Providers\\Qdrant\\Methods\\describe_index_logic')) {
        $desc = describe_index_logic($strategyInstance, $index_name);
        if (!is_wp_error($desc)) {
            $vectors_cfg = $desc['config']['params']['vectors'] ?? null;
            $expected_size = null;
            if (is_array($vectors_cfg) && isset($vectors_cfg['size'])) {
                $expected_size = (int) $vectors_cfg['size'];
            }
            if ($expected_size && $expected_size > 0) {
                foreach ($body['points'] as $p) {
                    $vals = $p['vector'] ?? null;
                    if (is_array($vals) && count($vals) !== $expected_size) {
                        return new WP_Error('qdrant_vector_dimension_mismatch', sprintf(__('Vector dimension mismatch. Expected %1$d, got %2$d.', 'gpt3-ai-content-generator'), $expected_size, count($vals)));
                    }
                }
            } else {
                // Fallback: internal consistency
                $first_len = null;
                foreach ($body['points'] as $p) {
                    $vals = $p['vector'] ?? null;
                    if (!is_array($vals)) continue;
                    $len = count($vals);
                    if ($first_len === null) { $first_len = $len; }
                    if ($first_len !== $len) {
                        return new WP_Error('qdrant_vector_dimension_inconsistent', __('Vectors have inconsistent dimensions in the upsert payload.', 'gpt3-ai-content-generator'));
                    }
                }
            }
        }
    }

    // Add wait=true by default (filterable)
    $wait_default = apply_filters('aipkit_qdrant_upsert_wait', true, $index_name);
    if (isset($vectors_data['wait'])) {
        $query_params['wait'] = ($vectors_data['wait'] === true || $vectors_data['wait'] === 'true') ? 'true' : 'false';
    } elseif ($wait_default === true) {
        $query_params['wait'] = 'true';
    }

    // Batch upserts
    $batch_size = apply_filters('aipkit_qdrant_upsert_batch_size', 100, $index_name);
    $all_results = [];
    $total = count($body['points']);
    for ($i = 0; $i < $total; $i += $batch_size) {
        $chunk_points = array_slice($body['points'], $i, $batch_size);
        $chunk_body = ['points' => $chunk_points];
        $resp = _request_logic($strategyInstance, 'PUT', $path, $chunk_body, $query_params);
        if (is_wp_error($resp)) return $resp;
        $all_results[] = $resp;
    }

    $last = end($all_results);
    if (is_array($last)) {
        $last['aipkit_batches'] = count($all_results);
    }
    return $last;
}
