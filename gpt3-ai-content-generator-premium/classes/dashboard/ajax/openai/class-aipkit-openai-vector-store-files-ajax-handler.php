<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/dashboard/ajax/openai/class-aipkit-openai-vector-store-files-ajax-handler.php
// Status: MODIFIED

namespace WPAICG\Dashboard\Ajax; // Corrected namespace

use WPAICG\AIPKit_Providers;
use WPAICG\Vector\AIPKit_Vector_Store_Manager;
use WPAICG\Vector\AIPKit_Vector_Store_Registry;
use WPAICG\Includes\AIPKit_Upload_Utils;
use WPAICG\aipkit_dashboard; // For Pro check
use WP_Error;

// DO NOT require_once the fn-*.php files from here; they are loaded by Vector_Store_Ajax_Handlers_Loader
// However, the new handler-files/ajax-*.php WILL be required by the methods below.

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Handles AJAX requests for OpenAI Vector Store file operations.
 * Delegates logic to namespaced functions defined in separate files.
 */
class AIPKit_OpenAI_Vector_Store_Files_Ajax_Handler extends BaseDashboardAjaxHandler
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

        // Ensure other dependencies used by logic functions are loaded
        if (!class_exists(\WPAICG\AIPKit_Providers::class)) {
            $providers_path = WPAICG_PLUGIN_DIR . 'classes/dashboard/class-aipkit_providers.php';
            if (file_exists($providers_path)) {
                require_once $providers_path;
            }
        }
        if (!class_exists(\WPAICG\Vector\AIPKit_Vector_Provider_Strategy_Factory::class)) {
            $factory_path = WPAICG_PLUGIN_DIR . 'classes/vector/class-aipkit-vector-provider-strategy-factory.php';
            if (file_exists($factory_path)) {
                require_once $factory_path;
            }
        }
        if (!class_exists(\WPAICG\Includes\AIPKit_Upload_Utils::class)) {
            $upload_utils_path = WPAICG_PLUGIN_DIR . 'includes/class-aipkit-upload-utils.php';
            if (file_exists($upload_utils_path)) {
                require_once $upload_utils_path;
            }
        }
        if (!class_exists(aipkit_dashboard::class)) {
            $dashboard_path = WPAICG_PLUGIN_DIR . 'classes/dashboard/class-aipkit_dashboard.php';
            if (file_exists($dashboard_path)) {
                require_once $dashboard_path;
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


    public function ajax_upload_file_to_openai()
    {
        $permission_check = $this->check_module_access_permissions('ai-training', 'aipkit_vector_store_nonce_openai');
        if (is_wp_error($permission_check)) {
            $this->send_wp_error($permission_check);
            return;
        }
        require_once __DIR__ . '/handler-files/ajax-upload-file-to-openai.php';
        \WPAICG\Dashboard\Ajax\OpenAI\HandlerFiles\do_ajax_upload_file_to_openai_logic($this);
    }

    public function ajax_add_files_to_vector_store_openai()
    {
        $permission_check = $this->check_module_access_permissions('ai-training', 'aipkit_vector_store_nonce_openai');
        if (is_wp_error($permission_check)) {
            $this->send_wp_error($permission_check);
            return;
        }
        require_once __DIR__ . '/handler-files/ajax-add-files-to-vector-store-openai.php';
        \WPAICG\Dashboard\Ajax\OpenAI\HandlerFiles\do_ajax_add_files_to_vector_store_openai_logic($this);
    }

    public function ajax_list_files_in_vector_store_openai()
    {
        $permission_check = $this->check_module_access_permissions('ai-training', 'aipkit_vector_store_nonce_openai');
        if (is_wp_error($permission_check)) {
            $this->send_wp_error($permission_check);
            return;
        }
        require_once __DIR__ . '/handler-files/ajax-list-files-in-vector-store-openai.php';
        \WPAICG\Dashboard\Ajax\OpenAI\HandlerFiles\do_ajax_list_files_in_vector_store_openai_logic($this);
    }

    public function ajax_get_openai_indexing_logs()
    {
        $permission_check = $this->check_module_access_permissions('ai-training', 'aipkit_vector_store_nonce_openai');
        if (is_wp_error($permission_check)) {
            $this->send_wp_error($permission_check);
            return;
        }
        require_once __DIR__ . '/handler-files/ajax-get-indexing-logs.php';
        \WPAICG\Dashboard\Ajax\OpenAI\HandlerFiles\do_ajax_get_indexing_logs_logic($this);
    }

    public function ajax_delete_file_from_vector_store_openai()
    {
        $permission_check = $this->check_module_access_permissions('ai-training', 'aipkit_vector_store_nonce_openai');
        if (is_wp_error($permission_check)) {
            $this->send_wp_error($permission_check);
            return;
        }
        require_once __DIR__ . '/handler-files/ajax-delete-file-from-vector-store-openai.php';
        \WPAICG\Dashboard\Ajax\OpenAI\HandlerFiles\do_ajax_delete_file_from_vector_store_openai_logic($this);
    }

    public function ajax_add_text_to_vector_store_openai()
    {
        $permission_check = $this->check_module_access_permissions('ai-training', 'aipkit_vector_store_nonce_openai');
        if (is_wp_error($permission_check)) {
            $this->send_wp_error($permission_check);
            return;
        }
        require_once __DIR__ . '/handler-files/ajax-add-text-to-vector-store-openai.php';
        \WPAICG\Dashboard\Ajax\OpenAI\HandlerFiles\do_ajax_add_text_to_vector_store_openai_logic($this);
    }

    public function ajax_upload_and_add_file_to_store_direct_openai()
    {
        $permission_check = $this->check_module_access_permissions('ai-training', 'aipkit_vector_store_nonce_openai');
        if (is_wp_error($permission_check)) {
            $this->send_wp_error($permission_check);
            return;
        }
        require_once __DIR__ . '/handler-files/ajax-upload-and-add-file-to-store-direct-openai.php';
        \WPAICG\Dashboard\Ajax\OpenAI\HandlerFiles\do_ajax_upload_and_add_file_to_store_direct_openai_logic($this);
    }
}