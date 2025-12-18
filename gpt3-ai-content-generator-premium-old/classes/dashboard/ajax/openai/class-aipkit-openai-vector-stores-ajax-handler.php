<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/dashboard/ajax/openai/class-aipkit-openai-vector-stores-ajax-handler.php
// Status: MODIFIED

namespace WPAICG\Dashboard\Ajax; // Corrected namespace

use WPAICG\AIPKit_Providers;
use WPAICG\Vector\AIPKit_Vector_Store_Manager;
use WPAICG\Vector\AIPKit_Vector_Store_Registry;
use WP_Error;

// DO NOT require_once the fn-*.php files from here; they are loaded by Vector_Store_Ajax_Handlers_Loader
// However, the new handler-stores/ajax-*.php WILL be required by the methods below.


if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Handles AJAX requests for OpenAI Vector Store management (list, create, delete, search).
 * Delegates logic to namespaced functions.
 */
class AIPKit_OpenAI_Vector_Stores_Ajax_Handler extends BaseDashboardAjaxHandler
{
    private $vector_store_manager;
    private $vector_store_registry;
    private $data_source_table_name;
    private $wpdb;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->data_source_table_name = $wpdb->prefix . 'aipkit_vector_data_source';

        // Ensure Vector_Store_Manager is loaded
        if (!class_exists(\WPAICG\Vector\AIPKit_Vector_Store_Manager::class)) {
            $manager_path = WPAICG_PLUGIN_DIR . 'classes/vector/class-aipkit-vector-store-manager.php';
            if (file_exists($manager_path)) {
                require_once $manager_path;
            }
        }
        if (class_exists(\WPAICG\Vector\AIPKit_Vector_Store_Manager::class)) {
            $this->vector_store_manager = new \WPAICG\Vector\AIPKit_Vector_Store_Manager();
        }

        // Ensure Vector_Store_Registry is loaded
        if (!class_exists(\WPAICG\Vector\AIPKit_Vector_Store_Registry::class)) {
            $registry_path = WPAICG_PLUGIN_DIR . 'classes/vector/class-aipkit-vector-store-registry.php';
            if (file_exists($registry_path)) {
                require_once $registry_path;
            }
        }
        if (class_exists(\WPAICG\Vector\AIPKit_Vector_Store_Registry::class)) {
            $this->vector_store_registry = new \WPAICG\Vector\AIPKit_Vector_Store_Registry();
        }
        if (!class_exists(\WPAICG\AIPKit_Providers::class)) {
            $providers_path = WPAICG_PLUGIN_DIR . 'classes/dashboard/class-aipkit_providers.php';
            if (file_exists($providers_path)) {
                require_once $providers_path;
            }
        }
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
    public function get_vector_store_manager(): ?\WPAICG\Vector\AIPKit_Vector_Store_Manager
    {
        return $this->vector_store_manager;
    }
    public function get_vector_store_registry(): ?\WPAICG\Vector\AIPKit_Vector_Store_Registry
    {
        return $this->vector_store_registry;
    }
    public function get_wpdb(): \wpdb
    {
        return $this->wpdb;
    }
    public function get_data_source_table_name(): string
    {
        return $this->data_source_table_name;
    }
    // --- End Getters ---


    public function ajax_list_vector_stores_openai()
    {
        require_once __DIR__ . '/handler-stores/ajax-list-vector-stores-openai.php';
        \WPAICG\Dashboard\Ajax\OpenAI\HandlerStores\do_ajax_list_vector_stores_openai_logic($this);
    }

    public function ajax_create_vector_store_openai()
    {
        require_once __DIR__ . '/handler-stores/ajax-create-vector-store-openai.php';
        \WPAICG\Dashboard\Ajax\OpenAI\HandlerStores\do_ajax_create_vector_store_openai_logic($this);
    }

    public function ajax_delete_vector_store_openai()
    {
        require_once __DIR__ . '/handler-stores/ajax-delete-vector-store-openai.php';
        \WPAICG\Dashboard\Ajax\OpenAI\HandlerStores\do_ajax_delete_vector_store_openai_logic($this);
    }

    public function ajax_search_vector_store_openai()
    {
        require_once __DIR__ . '/handler-stores/ajax-search-vector-store-openai.php';
        \WPAICG\Dashboard\Ajax\OpenAI\HandlerStores\do_ajax_search_vector_store_openai_logic($this);
    }
}
