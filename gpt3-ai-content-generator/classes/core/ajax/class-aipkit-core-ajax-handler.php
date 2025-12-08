<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/core/ajax/class-aipkit-core-ajax-handler.php
// Status: MODIFIED

namespace WPAICG\Core\Ajax;

use WPAICG\Dashboard\Ajax\BaseDashboardAjaxHandler;
use WPAICG\AIPKit_Role_Manager;
use WPAICG\Includes\AIPKit_Upload_Utils;
use WPAICG\Core\AIPKit_AI_Caller; // For AI Caller
use WPAICG\AIPKit_Providers;
use WPAICG\Vector\AIPKit_Vector_Store_Manager;
use WPAICG\Chat\Utils\Utils as ChatUtils; // ADDED for time diff
use WPAICG\Vector\AIPKit_Vector_Store_Registry; // ADDED for registry access
use WPAICG\Vector\PostProcessor\OpenAI\OpenAIPostProcessor;
use WPAICG\Vector\PostProcessor\Pinecone\PineconePostProcessor;
use WPAICG\Vector\PostProcessor\Qdrant\QdrantPostProcessor;
use WP_Error; // For WP_Error usage

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles core AJAX actions not specific to a particular module.
 */
class AIPKit_Core_Ajax_Handler extends BaseDashboardAjaxHandler
{
    private $ai_caller;
    private $vector_store_manager;
    private $openai_post_processor;
    private $pinecone_post_processor;
    private $qdrant_post_processor;

    public function __construct()
    {
        if (class_exists(\WPAICG\Core\AIPKit_AI_Caller::class)) {
            $this->ai_caller = new AIPKit_AI_Caller();
        }
        if (class_exists(\WPAICG\Vector\AIPKit_Vector_Store_Manager::class)) {
            $this->vector_store_manager = new AIPKit_Vector_Store_Manager();
        }
        if (class_exists(OpenAIPostProcessor::class)) {
            $this->openai_post_processor = new OpenAIPostProcessor();
        }
        if (class_exists(PineconePostProcessor::class)) {
            $this->pinecone_post_processor = new PineconePostProcessor();
        }
        if (class_exists(QdrantPostProcessor::class)) {
            $this->qdrant_post_processor = new QdrantPostProcessor();
        }
    }

    /**
     * AJAX handler to get upload limits.
     * Ensures only users who can access AI Training module can call this.
     * @since NEXT_VERSION
     */
    public function ajax_get_upload_limits()
    {
        // Permission check: accept any valid nonce from AI Training UIs (global or provider-specific)
        $nonce_candidates = [
            'aipkit_nonce',
            'aipkit_vector_store_nonce_openai',
            'aipkit_vector_store_pinecone_nonce',
            'aipkit_vector_store_qdrant_nonce',
        ];
        $has_permission = false;
        $last_error = null;
        foreach ($nonce_candidates as $nonce_field) {
            $check = $this->check_module_access_permissions('ai-training', $nonce_field);
            if (!is_wp_error($check)) { $has_permission = true; break; }
            $last_error = $check;
        }
        if (!$has_permission) {
            $this->send_wp_error($last_error ?: new WP_Error('forbidden', __('Permission denied.', 'gpt3-ai-content-generator')));
            return;
        }

        if (class_exists(\WPAICG\Includes\AIPKit_Upload_Utils::class)) {
            $limits = AIPKit_Upload_Utils::get_upload_limits();
            wp_send_json_success($limits);
        } else {
            wp_send_json_error(['message' => __('Upload utility not available.', 'gpt3-ai-content-generator')], 500);
        }
    }

    /**
     * AJAX handler to generate embeddings for given content.
     * Requires 'ai-training' module access permission.
     * Expects 'content_to_embed', 'embedding_provider', 'embedding_model' in POST.
     * UPDATED: Uses the global 'aipkit_nonce' for nonce verification in check_module_access_permissions.
     * @since NEXT_VERSION
     */
    public function ajax_generate_embedding()
    {
        // Permission Check: Users who can access AI Training can generate embeddings.
        // Use the main dashboard nonce 'aipkit_nonce' for this core utility.
        $permission_check = $this->check_module_access_permissions('ai-training', 'aipkit_nonce');
        if (is_wp_error($permission_check)) {
            $this->send_wp_error($permission_check);
            return;
        }
        
        // Unslash the POST array at once.
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked by check_module_access_permissions().
        $post_data = wp_unslash($_POST);
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked by check_module_access_permissions().
        $content_to_embed = isset($post_data['content_to_embed']) ? wp_kses_post($post_data['content_to_embed']) : '';
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked by check_module_access_permissions().
        $embedding_provider_key = isset($post_data['embedding_provider']) ? sanitize_key($post_data['embedding_provider']) : '';
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked by check_module_access_permissions().
        $embedding_model = isset($post_data['embedding_model']) ? sanitize_text_field($post_data['embedding_model']) : '';

        if (empty($content_to_embed)) {
            $this->send_wp_error(new WP_Error('missing_content', __('Content to embed cannot be empty.', 'gpt3-ai-content-generator')));
            return;
        }
        if (empty($embedding_provider_key)) {
            $this->send_wp_error(new WP_Error('missing_embedding_provider', __('Embedding provider is required.', 'gpt3-ai-content-generator')));
            return;
        }
        if (empty($embedding_model)) {
            $this->send_wp_error(new WP_Error('missing_embedding_model', __('Embedding model is required.', 'gpt3-ai-content-generator')));
            return;
        }

        // Normalize provider name
        $provider_map = ['openai' => 'OpenAI', 'google' => 'Google', 'azure' => 'Azure'];
        $embedding_provider = $provider_map[$embedding_provider_key] ?? '';

        if (empty($embedding_provider)) {
             $this->send_wp_error(new WP_Error('invalid_embedding_provider', __('Invalid embedding provider specified.', 'gpt3-ai-content-generator')));
             return;
        }

        if (!class_exists(\WPAICG\Core\AIPKit_AI_Caller::class)) {
            $this->send_wp_error(new WP_Error('internal_error_embedding', __('Embedding service component not available.', 'gpt3-ai-content-generator')));
            return;
        }

        $ai_caller = new AIPKit_AI_Caller();
        $embedding_options = ['model' => $embedding_model];

        $result = $ai_caller->generate_embeddings($embedding_provider, $content_to_embed, $embedding_options);

        if (is_wp_error($result)) {
            $this->send_wp_error($result);
        } else {
            wp_send_json_success($result);
        }
    }
    
    /**
     * AJAX handler to delete a single vector data source entry from both the vector DB and local log.
     * @since NEXT_VERSION
     */
    public function ajax_delete_vector_data_source_entry()
    {
        $permission_check = $this->check_module_access_permissions('ai-training', 'aipkit_nonce');
        if (is_wp_error($permission_check)) {
            $this->send_wp_error($permission_check);
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked above.
        $post_data = wp_unslash($_POST);

        // --- START FIX: Use sanitize_text_field to preserve case and add validation ---
        $provider_raw = isset($post_data['provider']) ? sanitize_text_field($post_data['provider']) : '';
        $allowed_providers = ['OpenAI', 'Pinecone', 'Qdrant']; // <-- FIX: Added 'OpenAI'
        if (!in_array($provider_raw, $allowed_providers, true)) {
            $this->send_wp_error(new WP_Error('invalid_provider_delete_vector', __('Invalid or unsupported provider for this action.', 'gpt3-ai-content-generator')));
            return;
        }
        $provider = $provider_raw;
        // --- END FIX ---
        
        $store_id   = isset($post_data['store_id']) ? sanitize_text_field($post_data['store_id']) : '';
        $vector_id  = isset($post_data['vector_id']) ? sanitize_text_field($post_data['vector_id']) : '';
        $log_entry_id = isset($post_data['log_id']) ? absint($post_data['log_id']) : 0;

        if (empty($provider) || empty($store_id) || empty($vector_id) || empty($log_entry_id)) {
            $this->send_wp_error(new WP_Error('missing_params_delete_vector', __('Missing required parameters for vector deletion.', 'gpt3-ai-content-generator')));
            return;
        }

        // Ensure Vector Store Manager is loaded
        if (!class_exists('\\WPAICG\\Vector\\AIPKit_Vector_Store_Manager')) {
             $this->send_wp_error(new WP_Error('vsm_missing_delete_vector', __('Vector management component is not available.', 'gpt3-ai-content-generator')));
             return;
        }
        $vector_store_manager = new \WPAICG\Vector\AIPKit_Vector_Store_Manager();

        // Get provider config
        $provider_config = \WPAICG\AIPKit_Providers::get_provider_data($provider);
        if (empty($provider_config['api_key'])) {
            /* translators: %s: Provider name. */
             $this->send_wp_error(new WP_Error('missing_api_key_delete_vector', sprintf(__('API key for %s is missing.', 'gpt3-ai-content-generator'), $provider)));
             return;
        }

        // 1. Delete from external vector store
        $delete_result = $vector_store_manager->delete_vectors($provider, $store_id, [$vector_id], $provider_config);
        
        // We proceed even if the external deletion fails, as the vector might not exist there anymore but the log does.
        // We will log the error if one occurs.
        if (is_wp_error($delete_result)) {
            // This is not a fatal error for the process, so we just log it and continue to delete from local DB.
        }

        // 2. Delete from local database log
        global $wpdb;
        $data_source_table_name = $wpdb->prefix . 'aipkit_vector_data_source';
        $deleted_rows = $wpdb->delete(
            $data_source_table_name,
            ['id' => $log_entry_id],
            ['%d']
        );

        if ($deleted_rows === false) {
             $this->send_wp_error(new WP_Error('db_delete_failed_vector_log', __('Failed to delete the log entry from the local database.', 'gpt3-ai-content-generator')));
             return;
        }
        if ($deleted_rows === 0) {
            // This could mean it was already deleted, which is a success state for the user.
            wp_send_json_success(['message' => __('Log entry was not found, it might have been already deleted.', 'gpt3-ai-content-generator')]);
            return;
        }

        wp_send_json_success(['message' => __('Vector record and log entry deleted successfully.', 'gpt3-ai-content-generator')]);
    }

    /**
     * AJAX handler to re-index a single vector data source entry from a WordPress post.
     * @since 2.4.2
     */
    public function ajax_reindex_vector_data_source_entry() {
        $permission_check = $this->check_module_access_permissions('ai-training', 'aipkit_nonce');
        if (is_wp_error($permission_check)) {
            $this->send_wp_error($permission_check);
            return;
        }

        // Sanitize all inputs
        $post_data = wp_unslash($_POST);
        $provider = isset($post_data['provider']) ? sanitize_text_field($post_data['provider']) : '';
        $store_id = isset($post_data['store_id']) ? sanitize_text_field($post_data['store_id']) : '';
        $vector_id = isset($post_data['vector_id']) ? sanitize_text_field($post_data['vector_id']) : '';
        $log_id = isset($post_data['log_id']) ? absint($post_data['log_id']) : 0;
        $post_id = isset($post_data['post_id']) ? absint($post_data['post_id']) : 0;
        $embedding_provider = isset($post_data['embedding_provider']) ? sanitize_key($post_data['embedding_provider']) : '';
        $embedding_model = isset($post_data['embedding_model']) ? sanitize_text_field($post_data['embedding_model']) : '';

        // Validate required parameters
        if (empty($provider) || empty($store_id) || empty($vector_id) || empty($log_id) || empty($post_id)) {
            $this->send_wp_error(new WP_Error('missing_params_reindex', __('Missing required parameters for re-indexing.', 'gpt3-ai-content-generator')));
            return;
        }

        // Validate dependencies
        if (!$this->vector_store_manager || !$this->openai_post_processor || !$this->pinecone_post_processor || !$this->qdrant_post_processor) {
            $this->send_wp_error(new WP_Error('vsm_missing_reindex', __('Vector processing components are not available.', 'gpt3-ai-content-generator')));
            return;
        }

        // Step 1: Delete the existing vector and log entry
        $provider_config = AIPKit_Providers::get_provider_data($provider);
        if (empty($provider_config['api_key'])) {
            /* translators: %s: Provider name. */
             $this->send_wp_error(new WP_Error('missing_api_key_reindex', sprintf(__('API key for %s is missing.', 'gpt3-ai-content-generator'), $provider)));
             return;
        }
        $delete_result = $this->vector_store_manager->delete_vectors($provider, $store_id, [$vector_id], $provider_config);
        if (is_wp_error($delete_result)) {
            // Log this but don't fail, as the vector might already be gone from the remote.
        }

        global $wpdb;
        $data_source_table_name = $wpdb->prefix . 'aipkit_vector_data_source';
        $wpdb->delete($data_source_table_name, ['id' => $log_id], ['%d']);

        // Step 2: Re-index the post
        $reindex_result = null;
        switch ($provider) {
            case 'OpenAI':
                $reindex_result = $this->openai_post_processor->index_single_post_to_store($post_id, $store_id);
                break;
            case 'Pinecone':
                if (empty($embedding_provider) || empty($embedding_model)) {
                    $this->send_wp_error(new WP_Error('missing_embedding_config_reindex', __('Embedding provider and model are required for Pinecone re-indexing.', 'gpt3-ai-content-generator')));
                    return;
                }
                $reindex_result = $this->pinecone_post_processor->index_single_post_to_index($post_id, $store_id, $embedding_provider, $embedding_model);
                break;
            case 'Qdrant':
                if (empty($embedding_provider) || empty($embedding_model)) {
                    $this->send_wp_error(new WP_Error('missing_embedding_config_reindex', __('Embedding provider and model are required for Qdrant re-indexing.', 'gpt3-ai-content-generator')));
                    return;
                }
                $reindex_result = $this->qdrant_post_processor->index_single_post_to_collection($post_id, $store_id, $embedding_provider, $embedding_model);
                break;
            default:
                $this->send_wp_error(new WP_Error('invalid_provider_reindex', __('Invalid provider for re-indexing.', 'gpt3-ai-content-generator')));
                return;
        }

        if (isset($reindex_result['status']) && $reindex_result['status'] === 'success') {
            wp_send_json_success(['message' => __('Content successfully re-indexed.', 'gpt3-ai-content-generator')]);
        } else {
            $error_message = $reindex_result['message'] ?? __('An unknown error occurred during re-indexing.', 'gpt3-ai-content-generator');
            $this->send_wp_error(new WP_Error('reindex_failed', 'Re-indexing failed: ' . $error_message));
        }
    }

    /**
     * AJAX: Return chunk logs for a given provider/store and batch_id with pagination.
     * @since NEXT_VERSION
     */
    public function ajax_get_chunk_logs_by_batch()
    {
        $permission_check = $this->check_module_access_permissions('ai-training', 'aipkit_nonce');
        if (is_wp_error($permission_check)) {
            $this->send_wp_error($permission_check);
            return;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'aipkit_vector_data_source';
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- already checked
        $post = wp_unslash($_POST);
        $provider = isset($post['provider']) ? sanitize_text_field($post['provider']) : '';
        $store_id = isset($post['store_id']) ? sanitize_text_field($post['store_id']) : '';
        $batch_id = isset($post['batch_id']) ? sanitize_text_field($post['batch_id']) : '';
        $page = isset($post['page']) ? max(1, absint($post['page'])) : 1;
        $per_page = isset($post['per_page']) ? min(50, max(1, absint($post['per_page']))) : 10;

        if (!$provider || !$store_id || !$batch_id) {
            $this->send_wp_error(new \WP_Error('missing_params_chunk_logs', __('Missing provider, store_id or batch_id.', 'gpt3-ai-content-generator')));
            return;
        }

        $offset = ($page - 1) * $per_page;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is safe
        $total = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE provider = %s AND vector_store_id = %s AND batch_id = %s",
            $provider, $store_id, $batch_id
        ));
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is safe
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT id, file_id, indexed_content, timestamp FROM {$table} WHERE provider = %s AND vector_store_id = %s AND batch_id = %s ORDER BY id ASC LIMIT %d OFFSET %d",
            $provider, $store_id, $batch_id, $per_page, $offset
        ), ARRAY_A);

        if ($wpdb->last_error) {
            $this->send_wp_error(new \WP_Error('db_error_chunk_logs', __('Failed to fetch chunk logs.', 'gpt3-ai-content-generator'), ['status' => 500]));
            return;
        }

        wp_send_json_success([
            'chunks' => $rows,
            'pagination' => [
                'total' => $total,
                'total_pages' => $per_page ? (int) ceil($total / $per_page) : 1,
                'current_page' => $page,
                'per_page' => $per_page,
            ],
        ]);
    }

    /**
     * AJAX: Retrieves CPTs and their fields/taxonomies for indexing settings UI.
     * @since 2.4.0
     */
    public function ajax_get_cpt_indexing_options()
    {
        $permission_check = $this->check_module_access_permissions('ai-training', 'aipkit_ai_training_settings_nonce');
        if (is_wp_error($permission_check)) {
            $this->send_wp_error($permission_check);
            return;
        }

        $cpt_data = [];
        $post_types = get_post_types(['public' => true], 'objects');
        unset($post_types['attachment']);

        foreach ($post_types as $cpt) {
            $taxonomies = get_object_taxonomies($cpt->name, 'objects');
            $public_taxonomies = [];
            foreach ($taxonomies as $tax) {
                if ($tax->public) {
                    $public_taxonomies[$tax->name] = $tax->label;
                }
            }

            $cpt_data[$cpt->name] = [
                'label'      => $cpt->label,
                'fields'     => $this->get_public_meta_keys_for_post_type($cpt->name),
                'taxonomies' => $public_taxonomies,
                'basic_labels' => [
                    'source_url' => __('Source URL', 'gpt3-ai-content-generator'),
                    'title'      => __('Title', 'gpt3-ai-content-generator'),
                    'excerpt'    => __('Excerpt', 'gpt3-ai-content-generator'),
                    'content'    => __('Content', 'gpt3-ai-content-generator'),
                ]
            ];

            if ($cpt->name === 'product' && class_exists('WooCommerce')) {
                $cpt_data[$cpt->name]['woo_attributes'] = [
                    'sku'        => __('SKU', 'gpt3-ai-content-generator'),
                    'price'      => __('Price', 'gpt3-ai-content-generator'),
                    'stock'      => __('Stock Status', 'gpt3-ai-content-generator'),
                    'dimensions' => __('Weight & Dimensions', 'gpt3-ai-content-generator'),
                    'attributes' => __('Product Attributes', 'gpt3-ai-content-generator'),
                ];
            }
        }

        $saved_settings = get_option('aipkit_indexing_field_settings', []);

        $response_data = [
            'cpt_data'       => $cpt_data,
            'saved_settings' => $saved_settings,
        ];
        
        wp_send_json_success($response_data);
    }

    /**
     * AJAX: Saves the CPT indexing field settings.
     * @since 2.4.0
     */
    public function ajax_save_cpt_indexing_options()
    {
        $permission_check = $this->check_module_access_permissions('ai-training', 'aipkit_ai_training_settings_nonce');
        if (is_wp_error($permission_check)) {
            $this->send_wp_error($permission_check);
            return;
        }
        $settings_json = isset($_POST['settings']) ? wp_unslash($_POST['settings']) : '{}';
        $settings = json_decode($settings_json, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($settings)) {
            $this->send_wp_error(new WP_Error('invalid_json', __('Invalid settings format.', 'gpt3-ai-content-generator')));
            return;
        }

        // --- NEW: Handle general settings separately ---
        $general_settings = get_option('aipkit_training_general_settings', []);
        $general_dirty = false;
        if (isset($settings['hide_user_uploads'])) {
            $general_settings['hide_user_uploads'] = (bool) $settings['hide_user_uploads'];
            unset($settings['hide_user_uploads']);
            $general_dirty = true;
        }
        if (isset($settings['show_index_button'])) {
            $general_settings['show_index_button'] = (bool) $settings['show_index_button'];
            unset($settings['show_index_button']);
            $general_dirty = true;
        }
        // Chunking settings (Pro only)
        if (isset($settings['chunk_avg_chars_per_token']) || isset($settings['chunk_max_tokens_per_chunk']) || isset($settings['chunk_overlap_tokens'])) {
            // Only allow saving if Pro
            $is_pro = \WPAICG\aipkit_dashboard::is_pro_plan();
            if ($is_pro) {
                $avg = isset($settings['chunk_avg_chars_per_token']) ? (int)$settings['chunk_avg_chars_per_token'] : null;
                $max = isset($settings['chunk_max_tokens_per_chunk']) ? (int)$settings['chunk_max_tokens_per_chunk'] : null;
                $ovl = isset($settings['chunk_overlap_tokens']) ? (int)$settings['chunk_overlap_tokens'] : null;

                // Validate ranges
                if ($avg !== null) {
                    if ($avg < 2 || $avg > 10) {
                        $this->send_wp_error(new \WP_Error('invalid_chunk_avg', __('Average chars per token must be between 2 and 10.', 'gpt3-ai-content-generator')));
                        return;
                    }
                    $general_settings['chunk_avg_chars_per_token'] = $avg;
                    $general_dirty = true;
                }
                if ($max !== null) {
                    if ($max < 256 || $max > 8000) {
                        $this->send_wp_error(new \WP_Error('invalid_chunk_max', __('Max tokens per chunk must be between 256 and 8000.', 'gpt3-ai-content-generator')));
                        return;
                    }
                    $general_settings['chunk_max_tokens_per_chunk'] = $max;
                    $general_dirty = true;
                }
                if ($ovl !== null) {
                    if ($ovl < 0 || $ovl > 1000) {
                        $this->send_wp_error(new \WP_Error('invalid_chunk_overlap', __('Overlap tokens must be between 0 and 1000.', 'gpt3-ai-content-generator')));
                        return;
                    }
                    $general_settings['chunk_overlap_tokens'] = $ovl;
                    $general_dirty = true;
                }
            }
            // Remove from specific settings payload
            unset($settings['chunk_avg_chars_per_token'], $settings['chunk_max_tokens_per_chunk'], $settings['chunk_overlap_tokens']);
        }

        if ($general_dirty) {
            update_option('aipkit_training_general_settings', $general_settings);
        }
        // --- END NEW ---

        // Sanitize the settings array
        $sanitized_settings = [];
        foreach ($settings as $cpt => $cpt_settings) {
            $cpt = sanitize_key($cpt);
            $sanitized_settings[$cpt] = [
                'fields' => [],
                'taxonomies' => [],
                'woo_attributes' => [],
                'basic_labels' => [],
            ];
            
            // Handle basic labels
            if (isset($cpt_settings['basic_labels']) && is_array($cpt_settings['basic_labels'])) {
                $allowed_basic_labels = ['source_url', 'title', 'excerpt', 'content'];
                foreach ($cpt_settings['basic_labels'] as $key => $label) {
                    if (in_array($key, $allowed_basic_labels)) {
                        $sanitized_settings[$cpt]['basic_labels'][sanitize_key($key)] = sanitize_text_field($label);
                    }
                }
            }
            
            if (isset($cpt_settings['fields']) && is_array($cpt_settings['fields'])) {
                foreach ($cpt_settings['fields'] as $key => $config) {
                    // Preserve original meta key (may include ":" etc.)
                    $key = is_string($key) ? wp_unslash($key) : $key;
                    // Ensure enabled is properly converted to boolean
                    $enabled = isset($config['enabled']) && $config['enabled'];
                    $sanitized_settings[$cpt]['fields'][$key] = [
                        'enabled' => (bool) $enabled,
                        'label'   => sanitize_text_field($config['label'] ?? ''),
                    ];
                }
            }
            if (isset($cpt_settings['taxonomies']) && is_array($cpt_settings['taxonomies'])) {
                foreach ($cpt_settings['taxonomies'] as $key => $config) {
                    // Preserve original taxonomy slug key
                    $key = is_string($key) ? wp_unslash($key) : $key;
                    // Ensure enabled is properly converted to boolean
                    $enabled = isset($config['enabled']) && $config['enabled'];
                    $sanitized_settings[$cpt]['taxonomies'][$key] = [
                        'enabled' => (bool) $enabled,
                        'label'   => sanitize_text_field($config['label'] ?? ''),
                    ];
                }
            }
            if (isset($cpt_settings['woo_attributes']) && is_array($cpt_settings['woo_attributes'])) {
                foreach ($cpt_settings['woo_attributes'] as $key => $config) {
                    // Preserve original key
                    $key = is_string($key) ? wp_unslash($key) : $key;
                    // Ensure enabled is properly converted to boolean
                    $enabled = isset($config['enabled']) && $config['enabled'];
                    $sanitized_settings[$cpt]['woo_attributes'][$key] = [
                        'enabled' => (bool) $enabled,
                        'label'   => sanitize_text_field($config['label'] ?? ''),
                    ];
                }
            }
        }

        update_option('aipkit_indexing_field_settings', $sanitized_settings, 'no');

        // DEBUG: Log the saved settings structure
        wp_send_json_success(['message' => __('Indexing settings saved successfully.', 'gpt3-ai-content-generator')]);
    }

    /**
     * Fetches public meta keys for a given post type by sampling recent posts.
     * @param string $post_type
     * @param int $limit
     * @return array
     */
    private function get_public_meta_keys_for_post_type(string $post_type, int $limit = 10): array
    {
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Efficiently sampling meta keys.
        $keys = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT meta_key FROM {$wpdb->postmeta} pm JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE p.post_type = %s AND meta_key NOT LIKE %s ORDER BY pm.meta_id DESC LIMIT 100",
            $post_type,
            $wpdb->esc_like('_') . '%'
        ));
        $formatted_keys = [];
        if ($keys) {
            foreach ($keys as $key) {
                $formatted_keys[$key] = ucwords(str_replace(['_', '-'], ' ', $key));
            }
        }
        return $formatted_keys;
    }

    /**
     * AJAX: Gets stats for a single knowledge base card from the local registry/DB.
     */
    public function ajax_get_knowledge_base_stats()
    {
        $permission_check = $this->check_module_access_permissions('ai-training');
        if (is_wp_error($permission_check)) {
            $this->send_wp_error($permission_check);
            return;
        }

        $provider = isset($_POST['provider']) ? sanitize_text_field(wp_unslash($_POST['provider'])) : '';
        $store_id = isset($_POST['store_id']) ? sanitize_text_field(wp_unslash($_POST['store_id'])) : '';

        if (empty($provider) || empty($store_id)) {
            $this->send_wp_error(new WP_Error('missing_params_stats', 'Provider and Store ID are required.'), 400);
            return;
        }
        
        $all_stores_from_registry = AIPKit_Vector_Store_Registry::get_registered_stores_by_provider($provider);
        $store_data = null;
        foreach ($all_stores_from_registry as $store) {
            $id_key_to_check = ($provider === 'Pinecone' || $provider === 'Qdrant') ? 'name' : 'id';
            if (isset($store[$id_key_to_check]) && $store[$id_key_to_check] === $store_id) {
                $store_data = $store;
                break;
            }
        }
        
        $document_count = 'N/A';
        if ($store_data) {
            if ($provider === 'OpenAI' && isset($store_data['file_counts']['total'])) {
                $document_count = (int) $store_data['file_counts']['total'];
            } elseif ($provider === 'Pinecone' && isset($store_data['total_vector_count'])) {
                $document_count = (int) $store_data['total_vector_count'];
            } elseif ($provider === 'Qdrant' && isset($store_data['vectors_count'])) {
                $document_count = (int) $store_data['vectors_count'];
            }
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'aipkit_vector_data_source';
        $last_updated_timestamp = null;
        if ($wpdb instanceof \wpdb) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Table existence check
            $table_exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table_name));
            if ($table_exists === $table_name) {
                $last_updated_timestamp = $wpdb->get_var($wpdb->prepare(
                    "SELECT MAX(timestamp) FROM {$table_name} WHERE provider = %s AND vector_store_id = %s",
                    $provider,
                    $store_id
                ));
            }
        }

        $last_updated_friendly = null;
        if ($last_updated_timestamp && class_exists(ChatUtils::class)) {
            $ts_num = is_numeric($last_updated_timestamp) ? (int) $last_updated_timestamp : strtotime($last_updated_timestamp);
            if ($ts_num) {
                $last_updated_friendly = ChatUtils::aipkit_human_time_diff($ts_num);
            }
        }

        wp_send_json_success([
            'document_count' => $document_count,
            'last_updated' => $last_updated_friendly
        ]);
    }

    /**
     * AJAX: Syncs stats for a single knowledge base card from the provider API.
     */
    public function ajax_sync_knowledge_base_stats()
    {
        $permission_check = $this->check_module_access_permissions('ai-training');
        if (is_wp_error($permission_check)) {
            $this->send_wp_error($permission_check);
        }

        $provider = isset($_POST['provider']) ? sanitize_text_field(wp_unslash($_POST['provider'])) : '';
        $store_id = isset($_POST['store_id']) ? sanitize_text_field(wp_unslash($_POST['store_id'])) : '';

        if (empty($provider) || empty($store_id)) {
            $this->send_wp_error(new WP_Error('missing_params_sync', 'Provider and Store ID are required.'), 400);
        }

        if (!$this->vector_store_manager) {
            $this->send_wp_error(new WP_Error('vsm_missing_sync', 'Vector Store Manager not available.'), 500);
        }

        $provider_config = AIPKit_Providers::get_provider_data($provider);
        if (empty($provider_config['api_key'])) {
            $this->send_wp_error(new WP_Error('api_key_missing_sync', 'API Key for ' . $provider . ' is not configured.'), 500);
        }

        $details = $this->vector_store_manager->describe_single_index($provider, $store_id, $provider_config);
        if (is_wp_error($details)) {
            // Handle 404 errors gracefully - the store no longer exists
            if ($details->get_error_code() === 'openai_vector_api_error' && 
                strpos($details->get_error_message(), '404') !== false) {
                
                // Remove the store from registry since it doesn't exist anymore
                AIPKit_Vector_Store_Registry::remove_registered_store($provider, $store_id);
                
                // Return 'Not Found' status instead of error
                wp_send_json_success([
                    'document_count' => 'NA',
                    'last_updated' => 'NA'
                ]);
                return;
            }
            
            // For other errors, send the error as before
            $this->send_wp_error($details);
        }

        AIPKit_Vector_Store_Registry::add_registered_store($provider, $details);

        $document_count = 'N/A';
        if ($provider === 'OpenAI' && isset($details['file_counts']['total'])) {
            $document_count = (int) $details['file_counts']['total'];
        } elseif ($provider === 'Pinecone' && isset($details['total_vector_count'])) {
            $document_count = (int) $details['total_vector_count'];
        } elseif ($provider === 'Qdrant' && isset($details['vectors_count'])) {
            $document_count = (int) $details['vectors_count'];
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'aipkit_vector_data_source';
        $last_updated_timestamp = $wpdb->get_var($wpdb->prepare(
            "SELECT MAX(timestamp) FROM {$table_name} WHERE provider = %s AND vector_store_id = %s",
            $provider,
            $store_id
        ));

        $last_updated_friendly = null;
        if ($last_updated_timestamp && class_exists(ChatUtils::class)) {
            $last_updated_friendly = ChatUtils::aipkit_human_time_diff($last_updated_timestamp);
        }

        wp_send_json_success([
            'document_count' => $document_count,
            'last_updated' => $last_updated_friendly
        ]);
    }

    /**
     * AJAX: Refreshes the knowledge base cards HTML after sync operations.
     */
    public function ajax_refresh_knowledge_base_cards()
    {
        $permission_check = $this->check_module_access_permissions('ai-training');
        if (is_wp_error($permission_check)) {
            $this->send_wp_error($permission_check);
        }

        // Render cards view
        ob_start();
        include WPAICG_PLUGIN_DIR . 'admin/views/modules/ai-training/partials/knowledge-base-cards.php';
        $cards_html = ob_get_clean();

        // Render list view as well so client can refresh either/both
        ob_start();
        include WPAICG_PLUGIN_DIR . 'admin/views/modules/ai-training/partials/knowledge-base-list.php';
        $list_html = ob_get_clean();

        wp_send_json_success([
            // Back-compat: keep 'html' as cards
            'html' => $cards_html,
            'cards_html' => $cards_html,
            'list_html' => $list_html,
        ]);
    }
}
