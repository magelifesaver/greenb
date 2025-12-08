<?php

// File: classes/chat/storage/getter/fn-get-vector-store-config.php
// Status: MODIFIED

namespace WPAICG\Chat\Storage\GetterMethods;

use WPAICG\Chat\Storage\BotSettingsManager; // For default constants

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Retrieves vector store configuration settings.
 *
 * @param int $bot_id The ID of the bot post.
 * @param callable $get_meta_fn A function to retrieve post meta.
 * @return array Associative array of vector store settings.
 */
function get_vector_store_config_logic(int $bot_id, callable $get_meta_fn): array
{
    $settings = [];

    if (!class_exists(BotSettingsManager::class)) {
        $bsm_path = dirname(__DIR__) . '/class-aipkit_bot_settings_manager.php';
        if (file_exists($bsm_path)) {
            require_once $bsm_path;
        }
    }

    $settings['enable_vector_store'] = in_array($get_meta_fn('_aipkit_enable_vector_store', BotSettingsManager::DEFAULT_ENABLE_VECTOR_STORE), ['0','1'])
        ? $get_meta_fn('_aipkit_enable_vector_store', BotSettingsManager::DEFAULT_ENABLE_VECTOR_STORE)
        : BotSettingsManager::DEFAULT_ENABLE_VECTOR_STORE;

    $settings['vector_store_provider'] = $get_meta_fn('_aipkit_vector_store_provider', BotSettingsManager::DEFAULT_VECTOR_STORE_PROVIDER);
    if (!in_array($settings['vector_store_provider'], ['openai', 'pinecone', 'qdrant'])) {
        $settings['vector_store_provider'] = BotSettingsManager::DEFAULT_VECTOR_STORE_PROVIDER;
    }

    $openai_vs_ids_json = $get_meta_fn('_aipkit_openai_vector_store_ids', '[]');
    $openai_vs_ids_array = json_decode($openai_vs_ids_json, true);
    if (!is_array($openai_vs_ids_array)) {
        $openai_vs_ids_array = [];
    }
    $settings['openai_vector_store_ids'] = $openai_vs_ids_array;

    // Delete old singular OpenAI store ID meta if it exists
    if (get_post_meta($bot_id, '_aipkit_openai_vector_store_id', true) !== false) {
        delete_post_meta($bot_id, '_aipkit_openai_vector_store_id');
    }

    $settings['pinecone_index_name'] = $get_meta_fn('_aipkit_pinecone_index_name', BotSettingsManager::DEFAULT_PINECONE_INDEX_NAME);
    $settings['qdrant_collection_name'] = $get_meta_fn('_aipkit_qdrant_collection_name', BotSettingsManager::DEFAULT_QDRANT_COLLECTION_NAME);
    // NEW: Multi-collection support for Qdrant (JSON array)
    $qdrant_names_json = $get_meta_fn('_aipkit_qdrant_collection_names', '[]');
    $qdrant_names_array = json_decode($qdrant_names_json, true);
    if (!is_array($qdrant_names_array)) { $qdrant_names_array = []; }
    if (empty($qdrant_names_array) && !empty($settings['qdrant_collection_name'])) {
        $qdrant_names_array = [$settings['qdrant_collection_name']];
    }
    $settings['qdrant_collection_names'] = $qdrant_names_array;

    $settings['vector_embedding_provider'] = $get_meta_fn('_aipkit_vector_embedding_provider', BotSettingsManager::DEFAULT_VECTOR_EMBEDDING_PROVIDER);
    if (!in_array($settings['vector_embedding_provider'], ['openai', 'google', 'azure'])) {
        $settings['vector_embedding_provider'] = BotSettingsManager::DEFAULT_VECTOR_EMBEDDING_PROVIDER;
    }
    $settings['vector_embedding_model'] = $get_meta_fn('_aipkit_vector_embedding_model', BotSettingsManager::DEFAULT_VECTOR_EMBEDDING_MODEL);

    $top_k_val = $get_meta_fn('_aipkit_vector_store_top_k', BotSettingsManager::DEFAULT_VECTOR_STORE_TOP_K);
    $settings['vector_store_top_k'] = max(1, min(absint($top_k_val), 20));

    // NEW: Get confidence threshold
    $threshold_val = $get_meta_fn('_aipkit_vector_store_confidence_threshold', BotSettingsManager::DEFAULT_VECTOR_STORE_CONFIDENCE_THRESHOLD);
    $settings['vector_store_confidence_threshold'] = max(0, min(absint($threshold_val), 100));
    // END NEW

    return $settings;
}
