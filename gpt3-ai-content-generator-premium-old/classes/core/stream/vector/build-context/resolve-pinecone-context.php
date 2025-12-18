<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/core/stream/vector/build-context/resolve-pinecone-context.php
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
 * Resolves Pinecone vector search context.
 *
 * @param AIPKit_AI_Caller $ai_caller Instance of AI Caller.
 * @param AIPKit_Vector_Store_Manager $vector_store_manager Instance of Vector Store Manager.
 * @param string $user_message The user's current message.
 * @param array $bot_settings The settings of the current bot.
 * @param string|null $frontend_active_pinecone_index_name Optional active Pinecone index name from frontend.
 * @param string|null $frontend_active_pinecone_namespace Optional active Pinecone namespace from frontend.
 * @param int $vector_top_k Number of results to fetch.
 * @param \wpdb $wpdb WordPress database instance.
 * @param string $data_source_table_name Vector data source table name.
 * @param array|null &$vector_search_scores_output Optional reference to capture scores for logging.
 * @return string Formatted Pinecone context results.
 */
function resolve_pinecone_context_logic(
    AIPKit_AI_Caller $ai_caller,
    AIPKit_Vector_Store_Manager $vector_store_manager,
    string $user_message,
    array $bot_settings,
    ?string $frontend_active_pinecone_index_name,
    ?string $frontend_active_pinecone_namespace,
    int $vector_top_k,
    \wpdb $wpdb,
    string $data_source_table_name,
    ?array &$vector_search_scores_output = null
): string {
    $pinecone_results = "";
    $pinecone_index_name_from_settings = $bot_settings['pinecone_index_name'] ?? '';
    $vector_embedding_provider = $bot_settings['vector_embedding_provider'] ?? '';
    $vector_embedding_model = $bot_settings['vector_embedding_model'] ?? '';
    $confidence_threshold_percent = (int)($bot_settings['vector_store_confidence_threshold'] ?? 20);
    $pinecone_score_threshold = round($confidence_threshold_percent / 100, 4); // Normalize 0-100 to 0-1 scale and round to avoid precision issues

    if (empty($pinecone_index_name_from_settings) || empty($vector_embedding_provider) || empty($vector_embedding_model)) {
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
    $pinecone_api_config = AIPKit_Providers::get_provider_data('Pinecone');
    if (empty($pinecone_api_config['api_key'])) {
        return "";
    }

    $embedding_provider_normalized = normalize_embedding_provider_logic($vector_embedding_provider);
    $query_vector_values_or_error = resolve_embedding_vector_logic($ai_caller, $user_message, $embedding_provider_normalized, $vector_embedding_model);

    if (is_wp_error($query_vector_values_or_error)) {
        return ""; // Error already logged by resolver
    }
    $query_vector_values = $query_vector_values_or_error;

    $index_to_query = $pinecone_index_name_from_settings;
    $pinecone_results_this_pass = "";

    // 1. Search with file-specific namespace if provided
    if (!empty($frontend_active_pinecone_namespace)) {
        $query_vector_for_file_context = ['vector' => $query_vector_values, 'namespace' => $frontend_active_pinecone_namespace];
        $file_search_results = $vector_store_manager->query_vectors('Pinecone', $index_to_query, $query_vector_for_file_context, $vector_top_k, [], $pinecone_api_config);
        if (!is_wp_error($file_search_results) && !empty($file_search_results)) {
            $formatted_file_results = "";
            foreach ($file_search_results as $item) {
                // NEW: Confidence Threshold Check
                if (isset($item['score']) && (float)$item['score'] < $pinecone_score_threshold) {
                    continue;
                }
                // END NEW
                $content_snippet = $item['metadata']['original_content'] ?? ($item['metadata']['text_content'] ?? null);
                if (empty($content_snippet) && isset($item['id'])) {
                    $cache_key = 'aipkit_vds_content_' . md5('pinecone_file_' . $index_to_query . $frontend_active_pinecone_namespace . $item['id']);
                    $cache_group = 'aipkit_vector_source_content';
                    $log_entry = wp_cache_get($cache_key, $cache_group);

                    if (false === $log_entry) {
                        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: Direct query to a custom table. Caches will be invalidated.
                        $log_entry = $wpdb->get_row($wpdb->prepare("SELECT indexed_content FROM {$data_source_table_name} WHERE provider = %s AND vector_store_id = %s AND batch_id = %s AND file_id = %s ORDER BY timestamp DESC LIMIT 1", 'Pinecone', $index_to_query, $frontend_active_pinecone_namespace, $item['id']), ARRAY_A);
                        wp_cache_set($cache_key, $log_entry, $cache_group, HOUR_IN_SECONDS);
                    }

                    if ($log_entry && !empty($log_entry['indexed_content'])) {
                        $content_snippet = $log_entry['indexed_content'];
                    }
                }
                if (!empty($content_snippet)) {
                    $formatted_file_results .= "- " . trim($content_snippet) . "\n";
                    
                    // Capture score data if reference provided
                    if ($vector_search_scores_output !== null && isset($item['score'])) {
                        $vector_search_scores_output[] = [
                            'provider' => 'Pinecone',
                            'index_name' => $index_to_query,
                            'namespace' => $frontend_active_pinecone_namespace,
                            'result_id' => $item['id'] ?? null,
                            'score' => $item['score'],
                            'content_preview' => wp_trim_words(trim($content_snippet), 10, '...')
                        ];
                    }
                }
            }
            if (!empty($formatted_file_results)) {
                $pinecone_results_this_pass .= "Context from Uploaded File (Index {$index_to_query}, Namespace: {$frontend_active_pinecone_namespace}):\n" . $formatted_file_results . "\n";
            }
        }
    }

    // 2. Search general bot knowledge (default/empty namespace)
    $query_vector_for_general_context = ['vector' => $query_vector_values]; // No namespace implies default
    $general_search_results = $vector_store_manager->query_vectors('Pinecone', $index_to_query, $query_vector_for_general_context, $vector_top_k, [], $pinecone_api_config);
    if (!is_wp_error($general_search_results) && !empty($general_search_results)) {
        $formatted_general_results = "";
        foreach ($general_search_results as $item) {
            // NEW: Confidence Threshold Check
            if (isset($item['score']) && (float)$item['score'] < $pinecone_score_threshold) {
                continue;
            }
            // END NEW
            // Skip if this result was already part of the file-specific context (if a namespace was used)
            if (!empty($frontend_active_pinecone_namespace) && ($item['metadata']['namespace'] ?? null) === $frontend_active_pinecone_namespace) {
                continue;
            }
            $content_snippet = $item['metadata']['original_content'] ?? ($item['metadata']['text_content'] ?? null);
            if (empty($content_snippet) && isset($item['id'])) {
                $cache_key = 'aipkit_vds_content_' . md5('pinecone_general_' . $index_to_query . $item['id']);
                $cache_group = 'aipkit_vector_source_content';
                $log_entry = wp_cache_get($cache_key, $cache_group);

                if (false === $log_entry) {
                    // Preferred query for legacy general records (no batch)
                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Direct query to custom table
                    $log_entry = $wpdb->get_row($wpdb->prepare(
                        "SELECT indexed_content FROM {$data_source_table_name} WHERE provider = 'Pinecone' AND vector_store_id = %s AND (batch_id IS NULL OR batch_id = '') AND file_id = %s ORDER BY timestamp DESC LIMIT 1",
                        $index_to_query,
                        $item['id']
                    ), ARRAY_A);

                    // Fallback: allow any batch_id (covers file uploads where batch_id is set)
                    if (!$log_entry) {
                        $log_entry = $wpdb->get_row($wpdb->prepare(
                            "SELECT indexed_content FROM {$data_source_table_name} WHERE provider = 'Pinecone' AND vector_store_id = %s AND file_id = %s ORDER BY timestamp DESC LIMIT 1",
                            $index_to_query,
                            $item['id']
                        ), ARRAY_A);
                    }

                    // Fallback 2: if metadata contains batch_id, query by it explicitly for precision
                    if (!$log_entry && !empty($item['metadata']['batch_id'])) {
                        $log_entry = $wpdb->get_row($wpdb->prepare(
                            "SELECT indexed_content FROM {$data_source_table_name} WHERE provider = 'Pinecone' AND vector_store_id = %s AND batch_id = %s AND file_id = %s ORDER BY timestamp DESC LIMIT 1",
                            $index_to_query,
                            $item['metadata']['batch_id'],
                            $item['id']
                        ), ARRAY_A);
                    }

                    wp_cache_set($cache_key, $log_entry, $cache_group, HOUR_IN_SECONDS);
                }

                if ($log_entry && !empty($log_entry['indexed_content'])) {
                    $content_snippet = $log_entry['indexed_content'];
                }
            }
            if (!empty($content_snippet)) {
                $formatted_general_results .= "- " . trim($content_snippet) . "\n";
                
                // Capture score data if reference provided
                if ($vector_search_scores_output !== null && isset($item['score'])) {
                    $vector_search_scores_output[] = [
                        'provider' => 'Pinecone',
                        'index_name' => $index_to_query,
                        'namespace' => null, // General context has no specific namespace
                        'result_id' => $item['id'] ?? null,
                        'score' => $item['score'],
                        'content_preview' => wp_trim_words(trim($content_snippet), 10, '...')
                    ];
                }
            }
        }
        if (!empty($formatted_general_results)) {
            $pinecone_results_this_pass .= "General Knowledge from Bot (Index {$index_to_query}):\n" . $formatted_general_results . "\n";
        }
    }

    if (!empty($pinecone_results_this_pass)) {
        $pinecone_results = $pinecone_results_this_pass;
    }

    return $pinecone_results;
}
