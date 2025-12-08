<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/dashboard/ajax/pinecone/class-aipkit-vector-store-pinecone-ajax-handler.php
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
 * Handles AJAX requests for Pinecone Vector Store operations.
 * Delegates logic to namespaced functions within handler-indexes directory.
 */
class AIPKit_Vector_Store_Pinecone_Ajax_Handler extends BaseDashboardAjaxHandler
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

    public function _get_pinecone_config(): array|WP_Error
    {
        if (!class_exists(\WPAICG\AIPKit_Providers::class)) {
            $providers_path = WPAICG_PLUGIN_DIR . 'classes/dashboard/class-aipkit_providers.php';
            if (file_exists($providers_path)) {
                require_once $providers_path;
            } else {
                return new WP_Error('dependency_missing', 'AIPKit_Providers class not found for Pinecone config.');
            }
        }
        $pinecone_data = AIPKit_Providers::get_provider_data('Pinecone');
        if (empty($pinecone_data['api_key'])) {
            return new WP_Error('missing_pinecone_config', __('Pinecone API Key is not configured in global settings.', 'gpt3-ai-content-generator'));
        }
        return ['api_key' => $pinecone_data['api_key']];
    }

    public function _log_vector_data_source_entry(array $log_data): void
    {
        $defaults = [
            'user_id' => get_current_user_id(), 'timestamp' => current_time('mysql', 1),
            'provider' => 'Pinecone',
            'vector_store_id' => 'unknown', 'vector_store_name' => null,
            'post_id' => null, 'post_title' => null, 'status' => 'info', 'message' => '',
            'indexed_content' => null,
            'file_id' => null,
            'batch_id' => null,
            'embedding_provider' => null, 'embedding_model' => null,
            'source_type_for_log' => null,
        ];
        $data_to_insert = wp_parse_args($log_data, $defaults);

        $source_type = $data_to_insert['source_type_for_log'] ?? ($data_to_insert['post_id'] ? 'wordpress_post' : 'unknown');
        $should_truncate = true;
        if (in_array($source_type, ['text_entry_global_form', 'file_upload_global_form', 'text_entry_pinecone_direct'])) {
            $should_truncate = false;
        }

        if ($should_truncate && is_string($data_to_insert['indexed_content']) && mb_strlen($data_to_insert['indexed_content']) > 1000) {
            $data_to_insert['indexed_content'] = mb_substr($data_to_insert['indexed_content'], 0, 997) . '...';
        }
        unset($data_to_insert['source_type_for_log']);

        $result = $this->wpdb->insert($this->data_source_table_name, $data_to_insert);

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
    // --- End Getters ---

    public function ajax_list_indexes_pinecone()
    {
        $permission_check = $this->check_module_access_permissions('ai-training', 'aipkit_vector_store_pinecone_nonce');
        if (is_wp_error($permission_check)) {
            $this->send_wp_error($permission_check);
            return;
        }
        require_once __DIR__ . '/handler-indexes/ajax-list-indexes.php'; // MODIFIED PATH
        \WPAICG\Dashboard\Ajax\Pinecone\HandlerIndexes\do_ajax_list_indexes_logic($this);
    }
    
    // NEW AJAX ACTION
    public function ajax_get_pinecone_index_details()
    {
        $permission_check = $this->check_module_access_permissions('ai-training', 'aipkit_vector_store_pinecone_nonce');
        if (is_wp_error($permission_check)) {
            $this->send_wp_error($permission_check);
            return;
        }
        require_once __DIR__ . '/handler-indexes/ajax-get-index-details.php';
        \WPAICG\Dashboard\Ajax\Pinecone\HandlerIndexes\do_ajax_get_index_details_logic($this);
    }

    public function ajax_create_index_pinecone()
    {
        $permission_check = $this->check_module_access_permissions('ai-training', 'aipkit_vector_store_pinecone_nonce');
        if (is_wp_error($permission_check)) {
            $this->send_wp_error($permission_check);
            return;
        }
        require_once __DIR__ . '/handler-indexes/ajax-create-index.php'; // MODIFIED PATH
        \WPAICG\Dashboard\Ajax\Pinecone\HandlerIndexes\do_ajax_create_index_logic($this);
    }

    public function ajax_upsert_to_pinecone_index()
    {
        $permission_check = $this->check_module_access_permissions('ai-training', 'aipkit_vector_store_pinecone_nonce');
        if (is_wp_error($permission_check)) {
            $this->send_wp_error($permission_check);
            return;
        }
        require_once __DIR__ . '/handler-indexes/ajax-upsert-to-index.php'; // MODIFIED PATH
        \WPAICG\Dashboard\Ajax\Pinecone\HandlerIndexes\do_ajax_upsert_to_index_logic($this);
    }

    public function ajax_search_pinecone_index()
    {
        $permission_check = $this->check_module_access_permissions('ai-training', 'aipkit_vector_store_pinecone_nonce');
        if (is_wp_error($permission_check)) {
            $this->send_wp_error($permission_check);
            return;
        }
        require_once __DIR__ . '/handler-indexes/ajax-search-index.php'; // MODIFIED PATH
        \WPAICG\Dashboard\Ajax\Pinecone\HandlerIndexes\do_ajax_search_index_logic($this);
    }

    public function ajax_upload_file_and_upsert_to_pinecone()
    {
        $permission_check = $this->check_module_access_permissions('ai-training', 'aipkit_vector_store_pinecone_nonce');
        if (is_wp_error($permission_check)) {
            $this->send_wp_error($permission_check);
            return;
        }

        // --- Pro Check ---
        if (!aipkit_dashboard::is_pro_plan()) {
            $this->send_wp_error(new WP_Error('pro_feature_pinecone_upload', __('File upload to Pinecone is a Pro feature. Please upgrade.', 'gpt3-ai-content-generator'), ['status' => 403]));
            return;
        }
        // --- End Pro Check ---

        $fn_file_path = WPAICG_LIB_DIR . 'vector-stores/file-upload/pinecone/fn-upload-file-and-upsert.php';
        if (file_exists($fn_file_path)) {
            require_once $fn_file_path;
            $result = \WPAICG\Lib\VectorStores\FileUpload\Pinecone\_aipkit_pinecone_ajax_upload_file_and_upsert_logic(
                $this->vector_store_manager,
                $this->ai_caller,
                $this->_get_pinecone_config(), // Ensure config is passed
                $this // Pass the handler instance
            );
            if (is_wp_error($result)) {
                // Log if error object contains log_data
                $log_data_on_error = $result->get_error_data();
                if (is_array($log_data_on_error) && isset($log_data_on_error['log_data'])) {
                    $this->_log_vector_data_source_entry($log_data_on_error['log_data']);
                }
                $this->send_wp_error($result);
            } else {
                // Log the result from the Pro function's 'log_data'
                if (isset($result['log_data']) && is_array($result['log_data'])) {
                    $this->_log_vector_data_source_entry($result['log_data']);
                }
                unset($result['log_data']); // Don't send full log data back to client
                wp_send_json_success($result);
            }
        } else {
            $this->send_wp_error(new WP_Error('missing_file_upload_logic_pinecone', __('File upload processing component for Pinecone is missing.', 'gpt3-ai-content-generator'), ['status' => 500]));
        }
    }

    public function ajax_get_pinecone_indexing_logs()
    {
        $permission_check = $this->check_module_access_permissions('ai-training', 'aipkit_vector_store_pinecone_nonce');
        if (is_wp_error($permission_check)) {
            $this->send_wp_error($permission_check);
            return;
        }
        require_once __DIR__ . '/handler-indexes/ajax-get-indexing-logs.php'; // MODIFIED PATH
        \WPAICG\Dashboard\Ajax\Pinecone\HandlerIndexes\do_ajax_get_indexing_logs_logic($this);
    }

    public function ajax_delete_index_pinecone()
    {
        $permission_check = $this->check_module_access_permissions('ai-training', 'aipkit_vector_store_pinecone_nonce');
        if (is_wp_error($permission_check)) {
            $this->send_wp_error($permission_check);
            return;
        }
        require_once __DIR__ . '/handler-indexes/ajax-delete-index.php'; // MODIFIED PATH
        \WPAICG\Dashboard\Ajax\Pinecone\HandlerIndexes\do_ajax_delete_index_logic($this);
    }
}