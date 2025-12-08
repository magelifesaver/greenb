<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/dashboard/ajax/openai/class-aipkit-openai-wp-content-indexing-ajax-handler.php
// Status: MODIFIED

namespace WPAICG\Dashboard\Ajax; // Corrected namespace

use WPAICG\AIPKit_Providers;
use WPAICG\Vector\AIPKit_Vector_Store_Manager;
use WPAICG\Vector\AIPKit_Vector_Store_Registry;
// --- MODIFIED: Use new PostProcessor namespace ---
use WPAICG\Vector\PostProcessor\OpenAI\OpenAIPostProcessor;
// --- END MODIFICATION ---
use WP_Error;
use WP_Query;

// DO NOT require_once the fn-*.php files from here; they are loaded by Vector_Store_Ajax_Handlers_Loader
// However, the new handler-indexing/ajax-*.php WILL be required by the methods below.

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Handles AJAX requests for fetching and indexing WordPress content into OpenAI Vector Stores.
 */
class AIPKit_OpenAI_WP_Content_Indexing_Ajax_Handler extends BaseDashboardAjaxHandler
{
    private $vector_store_manager;
    private $vector_store_registry;
    // --- MODIFIED: Type hint for new PostProcessor ---
    private $openai_post_processor;
    // --- END MODIFICATION ---
    private $data_source_table_name;
    private $wpdb;


    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->data_source_table_name = $wpdb->prefix . 'aipkit_vector_data_source';

        if (!class_exists(\WPAICG\Vector\AIPKit_Vector_Store_Manager::class)) {
            $manager_path = WPAICG_PLUGIN_DIR . 'classes/vector/class-aipkit-vector-store-manager.php';
            if (file_exists($manager_path)) {
                require_once $manager_path;
            }
        }
        if (class_exists(\WPAICG\Vector\AIPKit_Vector_Store_Manager::class)) {
            $this->vector_store_manager = new \WPAICG\Vector\AIPKit_Vector_Store_Manager();
        }

        if (!class_exists(\WPAICG\Vector\AIPKit_Vector_Store_Registry::class)) {
            $registry_path = WPAICG_PLUGIN_DIR . 'classes/vector/class-aipkit-vector-store-registry.php';
            if (file_exists($registry_path)) {
                require_once $registry_path;
            }
        }
        if (class_exists(\WPAICG\Vector\AIPKit_Vector_Store_Registry::class)) {
            $this->vector_store_registry = new \WPAICG\Vector\AIPKit_Vector_Store_Registry();
        }

        // --- MODIFIED: Ensure and instantiate new PostProcessor ---
        if (!class_exists(\WPAICG\Vector\PostProcessor\OpenAI\OpenAIPostProcessor::class)) {
            $processor_path = WPAICG_PLUGIN_DIR . 'classes/vector/post-processor/openai/class-openai-post-processor.php';
            if (file_exists($processor_path)) {
                require_once $processor_path;
            }
        }
        if (class_exists(\WPAICG\Vector\PostProcessor\OpenAI\OpenAIPostProcessor::class)) {
            $this->openai_post_processor = new \WPAICG\Vector\PostProcessor\OpenAI\OpenAIPostProcessor();
        }
        // --- END MODIFICATION ---
    }

    /** Helper to get OpenAI API configuration. */
    public function _get_openai_config(): array|WP_Error
    {
        $openai_data = AIPKit_Providers::get_provider_data('OpenAI');
        if (empty($openai_data['api_key'])) {
            return new WP_Error('missing_openai_key', __('OpenAI API Key is not configured in global settings.', 'gpt3-ai-content-generator'));
        }
        return [
            'api_key' => $openai_data['api_key'],
            'base_url' => $openai_data['base_url'] ?? 'https://api.openai.com',
            'api_version' => $openai_data['api_version'] ?? 'v1',
        ];
    }

    // --- Getter methods for dependencies needed by the new standalone functions ---
    // --- MODIFIED: Getter for new PostProcessor ---
    public function get_openai_post_processor(): ?\WPAICG\Vector\PostProcessor\OpenAI\OpenAIPostProcessor
    {
        return $this->openai_post_processor;
    }
    // --- END MODIFICATION ---
    public function get_vector_store_manager(): ?\WPAICG\Vector\AIPKit_Vector_Store_Manager
    {
        return $this->vector_store_manager;
    }
    public function get_vector_store_registry(): ?\WPAICG\Vector\AIPKit_Vector_Store_Registry
    {
        return $this->vector_store_registry;
    }
    // --- End Getters ---


    public function ajax_fetch_wp_content_for_indexing()
    {
        require_once __DIR__ . '/handler-indexing/ajax-fetch-wp-content-for-indexing.php';
        \WPAICG\Dashboard\Ajax\OpenAI\HandlerIndexing\do_ajax_fetch_wp_content_for_indexing_logic($this);
    }

    public function ajax_index_selected_wp_content()
    {
        require_once __DIR__ . '/handler-indexing/ajax-index-selected-wp-content.php';
        \WPAICG\Dashboard\Ajax\OpenAI\HandlerIndexing\do_ajax_index_selected_wp_content_logic($this);
    }
}