<?php

// File: classes/core/stream/vector/build-context/resolve-embedding-vector.php
// Status: NEW FILE

namespace WPAICG\Core\Stream\Vector\BuildContext;

use WPAICG\Core\AIPKit_AI_Caller;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Generates an embedding vector for the given user message using the specified provider and model.
 *
 * @param \WPAICG\Core\AIPKit_AI_Caller $ai_caller
 * @param string $user_message
 * @param string $embedding_provider_normalized
 * @param string $embedding_model
 * @return array|WP_Error The embedding vector values or WP_Error on failure.
 */
function resolve_embedding_vector_logic(
    AIPKit_AI_Caller $ai_caller,
    string $user_message,
    string $embedding_provider_normalized,
    string $embedding_model
): array|\WP_Error {
    $embedding_options = ['model' => $embedding_model];
    $embedding_result = $ai_caller->generate_embeddings($embedding_provider_normalized, $user_message, $embedding_options);

    if (is_wp_error($embedding_result) || empty($embedding_result['embeddings'][0])) {
        $error_message = is_wp_error($embedding_result) ? $embedding_result->get_error_message() : 'No embeddings returned.';
        return new WP_Error('embedding_failed_for_query', $error_message);
    }

    return $embedding_result['embeddings'][0];
}
