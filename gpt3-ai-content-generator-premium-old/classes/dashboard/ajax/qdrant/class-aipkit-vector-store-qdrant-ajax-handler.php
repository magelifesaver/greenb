<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/dashboard/ajax/qdrant/class-aipkit-vector-store-qdrant-ajax-handler.php
// Status: MODIFIED

namespace WPAICG\Dashboard\Ajax;

use WP_Error;
use WPAICG\AIPKit_Providers;
use WPAICG\Vector\AIPKit_Vector_Store_Manager;
use WPAICG\Vector\AIPKit_Vector_Store_Registry;
use WPAICG\Core\AIPKit_AI_Caller;
use WPAICG\Includes\AIPKit_Upload_Utils;
use WPAICG\aipkit_dashboard; // For Pro check

// REMOVED: Direct require_once for old fn-*.php files

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Handles AJAX requests for Qdrant Vector Store operations.
 * Delegates logic to namespaced functions.
 */
class AIPKit_Vector_Store_Qdrant_Ajax_Handler extends BaseDashboardAjaxHandler
{
    private $vector_store_manager;
    private $vector_store_registry;
    private $ai_caller;
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

        if (!class_exists(\WPAICG\Core\AIPKit_AI_Caller::class)) {
            $ai_caller_path = WPAICG_PLUGIN_DIR . 'classes/core/class-aipkit_ai_caller.php';
            if (file_exists($ai_caller_path)) {
                require_once $ai_caller_path;
            }
        }
        if (class_exists(\WPAICG\Core\AIPKit_AI_Caller::class)) {
            $this->ai_caller = new \WPAICG\Core\AIPKit_AI_Caller();
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

    public function _get_qdrant_config(): array|WP_Error
    {
        if (!class_exists(\WPAICG\AIPKit_Providers::class)) {
            $providers_path = WPAICG_PLUGIN_DIR . 'classes/dashboard/class-aipkit_providers.php';
            if (file_exists($providers_path)) {
                require_once $providers_path;
            } else {
                return new WP_Error('dependency_missing', 'AIPKit_Providers class not found for Qdrant config.');
            }
        }
        $qdrant_data = AIPKit_Providers::get_provider_data('Qdrant');
        if (empty($qdrant_data['url'])) {
            return new WP_Error('missing_qdrant_url', __('Qdrant URL is not configured in global settings.', 'gpt3-ai-content-generator'));
        }
        if (empty($qdrant_data['api_key'])) {
            return new WP_Error('missing_qdrant_api_key', __('Qdrant API Key is not configured in global settings (required for Qdrant Cloud).', 'gpt3-ai-content-generator'));
        }
        return ['url' => $qdrant_data['url'], 'api_key' => $qdrant_data['api_key']];
    }

    /**
     * Wrapper for the logging function, to be called from the standalone logic files.
     * @param array $log_data
     */
    public function _log_vector_data_source_entry(array $log_data): void
    {
        // Ensure the log function file is loaded before calling it
        $log_fn_path = __DIR__ . '/handler-collections/ajax-get-vector-data-source-logs.php'; // Corrected path
        if (file_exists($log_fn_path)) {
            // Ensure the function itself is included if not already
            if (!function_exists('\WPAICG\Dashboard\Ajax\Qdrant\HandlerCollections\_aipkit_qdrant_log_vector_data_source_entry_logic')) {
                require_once $log_fn_path;
            }
            \WPAICG\Dashboard\Ajax\Qdrant\HandlerCollections\_aipkit_qdrant_log_vector_data_source_entry_logic(
                $this->wpdb,
                $this->data_source_table_name,
                $log_data
            );
        }
    }


    public function ajax_list_collections_qdrant()
    {
        $permission_check = $this->check_module_access_permissions('ai-training', 'aipkit_vector_store_qdrant_nonce');
        if (is_wp_error($permission_check)) {
            $this->send_wp_error($permission_check);
            return;
        }
        require_once __DIR__ . '/handler-collections/ajax-list-collections.php';
        \WPAICG\Dashboard\Ajax\Qdrant\HandlerCollections\_aipkit_qdrant_ajax_list_collections_logic($this);
    }

    public function ajax_create_collection_qdrant()
    {
        $permission_check = $this->check_module_access_permissions('ai-training', 'aipkit_vector_store_qdrant_nonce');
        if (is_wp_error($permission_check)) {
            $this->send_wp_error($permission_check);
            return;
        }
        require_once __DIR__ . '/handler-collections/ajax-create-collection.php';
        \WPAICG\Dashboard\Ajax\Qdrant\HandlerCollections\_aipkit_qdrant_ajax_create_collection_logic($this);
    }

    public function ajax_delete_collection_qdrant()
    {
        $permission_check = $this->check_module_access_permissions('ai-training', 'aipkit_vector_store_qdrant_nonce');
        if (is_wp_error($permission_check)) {
            $this->send_wp_error($permission_check);
            return;
        }
        require_once __DIR__ . '/handler-collections/ajax-delete-collection.php';
        \WPAICG\Dashboard\Ajax\Qdrant\HandlerCollections\_aipkit_qdrant_ajax_delete_collection_logic($this);
    }

    public function ajax_upsert_to_qdrant_collection()
    {
        $permission_check = $this->check_module_access_permissions('ai-training', 'aipkit_vector_store_qdrant_nonce');
        if (is_wp_error($permission_check)) {
            $this->send_wp_error($permission_check);
            return;
        }
        require_once __DIR__ . '/handler-collections/ajax-upsert-to-collection.php';
        \WPAICG\Dashboard\Ajax\Qdrant\HandlerCollections\_aipkit_qdrant_ajax_upsert_to_collection_logic($this);
    }

    public function ajax_upload_file_and_upsert_to_qdrant()
    {
        $permission_check = $this->check_module_access_permissions('ai-training', 'aipkit_vector_store_qdrant_nonce');
        if (is_wp_error($permission_check)) {
            $this->send_wp_error($permission_check);
            return;
        }
        if (!$this->vector_store_manager || !$this->ai_caller || !class_exists(\WPAICG\Includes\AIPKit_Upload_Utils::class)) {
            $this->send_wp_error(new WP_Error('deps_missing_qdrant_upload', __('Required components for Qdrant file upload are missing.', 'gpt3-ai-content-generator'), ['status' => 500]));
            return;
        }

        // --- Pro Check ---
        if (!aipkit_dashboard::is_pro_plan()) {
            $this->send_wp_error(new WP_Error('pro_feature_qdrant_upload', __('File upload to Qdrant is a Pro feature. Please upgrade.', 'gpt3-ai-content-generator'), ['status' => 403]));
            return;
        }
        // --- End Pro Check ---

        $qdrant_config = $this->_get_qdrant_config();
        if (is_wp_error($qdrant_config)) {
            $this->send_wp_error($qdrant_config);
            return;
        }

        $fn_file_path = WPAICG_LIB_DIR . 'vector-stores/file-upload/qdrant/fn-upload-file-and-upsert.php';
        if (file_exists($fn_file_path)) {
            require_once $fn_file_path;
            // *** MODIFIED: Pass $this as the fourth argument ***
            $result = \WPAICG\Lib\VectorStores\FileUpload\Qdrant\_aipkit_qdrant_ajax_upload_file_and_upsert_logic(
                $this->vector_store_manager,
                $this->ai_caller,
                $qdrant_config,
                $this // Pass the handler instance
            );
            // *** END MODIFICATION ***
        } else {
            $result = new WP_Error('missing_file_upload_logic_qdrant_lib', __('File upload processing component for Qdrant is missing.', 'gpt3-ai-content-generator'), ['status' => 500]);
        }

        if (is_wp_error($result)) {
            // Log if error object contains log_data
            $log_data_on_error = $result->get_error_data();
            if (is_array($log_data_on_error) && isset($log_data_on_error['log_data'])) {
                $this->_log_vector_data_source_entry($log_data_on_error['log_data']);
            }
            $this->send_wp_error($result);
        } else {
            // Log if success result contains log_data
            if (isset($result['log_data']) && is_array($result['log_data'])) {
                $this->_log_vector_data_source_entry($result['log_data']);
                unset($result['log_data']); // Don't send full log data back to client
            }
            wp_send_json_success($result);
        }
    }

    public function ajax_search_qdrant_collection()
    {
        $permission_check = $this->check_module_access_permissions('ai-training', 'aipkit_vector_store_qdrant_nonce');
        if (is_wp_error($permission_check)) {
            $this->send_wp_error($permission_check);
            return;
        }
        require_once __DIR__ . '/handler-collections/ajax-search-collection.php';
        \WPAICG\Dashboard\Ajax\Qdrant\HandlerCollections\_aipkit_qdrant_ajax_search_collection_logic($this);
    }

    public function ajax_get_qdrant_collection_stats()
    {
        $permission_check = $this->check_module_access_permissions('ai-training', 'aipkit_vector_store_qdrant_nonce');
        if (is_wp_error($permission_check)) {
            $this->send_wp_error($permission_check);
            return;
        }
        require_once __DIR__ . '/handler-collections/ajax-get-collection-stats.php';
        \WPAICG\Dashboard\Ajax\Qdrant\HandlerCollections\_aipkit_qdrant_ajax_get_collection_stats_logic($this);
    }

    public function ajax_get_vector_data_source_logs_for_store()
    {
        $permission_check = $this->check_module_access_permissions('ai-training', 'aipkit_vector_store_qdrant_nonce');
        if (is_wp_error($permission_check)) {
            $this->send_wp_error($permission_check);
            return;
        }
        require_once __DIR__ . '/handler-collections/ajax-get-vector-data-source-logs.php';
        \WPAICG\Dashboard\Ajax\Qdrant\HandlerCollections\_aipkit_qdrant_ajax_get_vector_data_source_logs_logic($this);
    }

    // Getter methods for dependencies needed by the new standalone functions
    public function get_vector_store_manager(): ?\WPAICG\Vector\AIPKit_Vector_Store_Manager
    {
        return $this->vector_store_manager;
    }
    public function get_ai_caller(): ?\WPAICG\Core\AIPKit_AI_Caller
    {
        return $this->ai_caller;
    }
    public function get_wpdb(): \wpdb
    {
        return $this->wpdb;
    }
    public function get_data_source_table_name(): string
    {
        return $this->data_source_table_name;
    }
    public function get_vector_store_registry(): ?\WPAICG\Vector\AIPKit_Vector_Store_Registry
    {
        return $this->vector_store_registry;
    }

}
