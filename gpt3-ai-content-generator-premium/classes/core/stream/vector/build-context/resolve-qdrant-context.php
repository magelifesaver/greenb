<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/core/stream/vector/build-context/resolve-qdrant-context.php
// Status: MODIFIED

namespace WPAICG\Core\Stream\Vector\BuildContext;

use WPAICG\AIPKit_Providers;
use WPAICG\Vector\AIPKit_Vector_Store_Manager;
use WPAICG\Core\AIPKit_AI_Caller;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Resolves context from Qdrant vector store.
 *
 * @param \WPAICG\Core\AIPKit_AI_Caller $ai_caller
 * @param \WPAICG\Vector\AIPKit_Vector_Store_Manager $vector_store_manager
 * @param string $user_message
 * @param array $bot_settings
 * @param string|null $frontend_active_qdrant_collection_name
 * @param string|null $frontend_active_qdrant_file_upload_context_id
 * @param int $vector_top_k
 * @param \wpdb $wpdb
 * @param string $data_source_table_name
 * @param array|null &$vector_search_scores_output Optional reference to capture scores for logging.
 * @return string Formatted Qdrant context results.
 */
function resolve_qdrant_context_logic(
    AIPKit_AI_Caller $ai_caller,
    AIPKit_Vector_Store_Manager $vector_store_manager,
    string $user_message,
    array $bot_settings,
    ?string $frontend_active_qdrant_collection_name, // Note: Qdrant doesn't use frontend-defined collection name for bot-level context, bot setting is primary
    ?string $frontend_active_qdrant_file_upload_context_id,
    int $vector_top_k,
    \wpdb $wpdb,
    string $data_source_table_name,
    ?array &$vector_search_scores_output = null
): string {
    $qdrant_results = "";
    $qdrant_collection_name_from_settings = $bot_settings['qdrant_collection_name'] ?? '';
    $qdrant_collection_names_multi = [];
    if (!empty($bot_settings['qdrant_collection_names']) && is_array($bot_settings['qdrant_collection_names'])) {
        $qdrant_collection_names_multi = array_values(array_unique(array_filter(array_map('strval', $bot_settings['qdrant_collection_names']))));
    } elseif (!empty($qdrant_collection_name_from_settings)) {
        $qdrant_collection_names_multi = [$qdrant_collection_name_from_settings];
    }
    $vector_embedding_provider = $bot_settings['vector_embedding_provider'] ?? '';
    $vector_embedding_model = $bot_settings['vector_embedding_model'] ?? ''; // This is the correctly defined variable
    $confidence_threshold_percent = (int)($bot_settings['vector_store_confidence_threshold'] ?? 20);
    $qdrant_score_threshold = round($confidence_threshold_percent / 100, 4); // Normalize 0-100 to 0-1 scale and round to avoid precision issues

    if (empty($qdrant_collection_names_multi) || empty($vector_embedding_provider) || empty($vector_embedding_model)) {
        // If $vector_embedding_model is empty, this condition is met, and it returns early.
        // This prevents "Undefined variable" if the model is essential and missing.
        return "";
    }

    if (!class_exists(AIPKit_Providers::class)) {
        $providers_path = WPAICG_PLUGIN_DIR . 'classes/dashboard/class-aipkit_providers.php';
        if (file_exists($providers_path)) {
            require_once $providers_path;
        } else {
            return "";
        }
    }
    $qdrant_api_config = AIPKit_Providers::get_provider_data('Qdrant');
    if (empty($qdrant_api_config['url']) || empty($qdrant_api_config['api_key'])) {
        return "";
    }

    $embedding_provider_normalized = normalize_embedding_provider_logic($vector_embedding_provider);

    $query_vector_values_or_error = resolve_embedding_vector_logic(
        $ai_caller,
        $user_message,
        $embedding_provider_normalized,
        $vector_embedding_model // Use the defined $vector_embedding_model
    );

    if (is_wp_error($query_vector_values_or_error)) {
        // Error is already logged by resolve_embedding_vector_logic if it fails.
        return "";
    }
    $query_vector_values = $query_vector_values_or_error;

    // We'll search across all selected collections and aggregate
    $collections_to_query = $qdrant_collection_names_multi;
    $qdrant_results_aggregate = "";

    // 1. Search with file-specific context ID if provided
    if (!empty($frontend_active_qdrant_file_upload_context_id)) {
        foreach ($collections_to_query as $collection_to_query) {
            $file_specific_filter = [
                'must' => [
                    ['key' => 'payload.file_upload_context_id', 'match' => ['value' => $frontend_active_qdrant_file_upload_context_id]]
                ]
            ];
            $query_vector_for_file_context = ['vector' => $query_vector_values];
            $file_search_results = $vector_store_manager->query_vectors('Qdrant', $collection_to_query, $query_vector_for_file_context, $vector_top_k, $file_specific_filter, $qdrant_api_config);

            if (!is_wp_error($file_search_results) && !empty($file_search_results)) {
                $formatted_file_results = "";
                foreach ($file_search_results as $item) {
                    if (isset($item['score']) && (float)$item['score'] < $qdrant_score_threshold) {
                        continue;
                    }
                    $content_snippet = $item['metadata']['original_content'] ?? ($item['metadata']['text_content'] ?? null);
                    if (!empty($content_snippet)) {
                        $formatted_file_results .= "- " . trim($content_snippet) . "\n";

                        if ($vector_search_scores_output !== null && isset($item['score'])) {
                            $vector_search_scores_output[] = [
                                'provider' => 'Qdrant',
                                'collection_name' => $collection_to_query,
                                'file_context_id' => $frontend_active_qdrant_file_upload_context_id,
                                'result_id' => $item['id'] ?? null,
                                'score' => $item['score'],
                                'content_preview' => wp_trim_words(trim($content_snippet), 10, '...')
                            ];
                        }
                    }
                }
                if (!empty($formatted_file_results)) {
                    $qdrant_results_aggregate .= "Context from Uploaded File (Collection {$collection_to_query}, File Context ID: {$frontend_active_qdrant_file_upload_context_id}):\n" . $formatted_file_results . "\n";
                }
            }
        }
    }

    // 2. Search general bot knowledge
    $general_knowledge_filter = [];
    if (!empty($frontend_active_qdrant_file_upload_context_id)) {
        $general_knowledge_filter = [
            'must_not' => [
                ['key' => 'payload.file_upload_context_id', 'match' => ['value' => $frontend_active_qdrant_file_upload_context_id]]
            ]
        ];
    }

    foreach ($collections_to_query as $collection_to_query) {
        $query_vector_for_general_context = ['vector' => $query_vector_values];
        $general_search_results = $vector_store_manager->query_vectors('Qdrant', $collection_to_query, $query_vector_for_general_context, $vector_top_k, $general_knowledge_filter, $qdrant_api_config);

        if (!is_wp_error($general_search_results) && !empty($general_search_results)) {
            $formatted_general_results = "";
            foreach ($general_search_results as $item) {
                if (isset($item['score']) && (float)$item['score'] < $qdrant_score_threshold) {
                    continue;
                }
                $content_snippet = $item['metadata']['original_content'] ?? ($item['metadata']['text_content'] ?? null);
                if (empty($content_snippet) && isset($item['id'])) {
                    $cache_key = 'aipkit_vds_content_' . md5('qdrant_general_' . $collection_to_query . $item['id']);
                    $cache_group = 'aipkit_vector_source_content';
                    $log_entry = wp_cache_get($cache_key, $cache_group);

                    if (false === $log_entry) {
                        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: Direct query to a custom table. Caches will be invalidated.
                        $log_entry = $wpdb->get_row($wpdb->prepare("SELECT indexed_content FROM {$data_source_table_name} WHERE provider = 'Qdrant' AND vector_store_id = %s AND file_id = %s AND (batch_id IS NULL OR batch_id = '' OR batch_id NOT LIKE %s) ORDER BY timestamp DESC LIMIT 1", $collection_to_query, $item['id'], 'qdrant_chat_file_%'), ARRAY_A);
                        wp_cache_set($cache_key, $log_entry, $cache_group, HOUR_IN_SECONDS);
                    }
                    
                    if ($log_entry && !empty($log_entry['indexed_content'])) {
                        $content_snippet = $log_entry['indexed_content'];
                    }
                }
                if (!empty($content_snippet)) {
                    $formatted_general_results .= "- " . trim($content_snippet) . "\n";
                    
                    if ($vector_search_scores_output !== null && isset($item['score'])) {
                        $vector_search_scores_output[] = [
                            'provider' => 'Qdrant',
                            'collection_name' => $collection_to_query,
                            'file_context_id' => null,
                            'result_id' => $item['id'] ?? null,
                            'score' => $item['score'],
                            'content_preview' => wp_trim_words(trim($content_snippet), 10, '...')
                        ];
                    }
                }
            }
            if (!empty($formatted_general_results)) {
                $qdrant_results_aggregate .= "General Knowledge from Bot (Collection {$collection_to_query}):\n" . $formatted_general_results . "\n";
            }
        }
    }

    if (!empty($qdrant_results_aggregate)) {
        $qdrant_results = $qdrant_results_aggregate;
    }

    return $qdrant_results;
}
