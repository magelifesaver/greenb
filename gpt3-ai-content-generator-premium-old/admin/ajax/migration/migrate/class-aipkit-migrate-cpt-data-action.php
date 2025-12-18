<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/ajax/migration/migrate/class-aipkit-migrate-cpt-data-action.php
// Status: MODIFIED

namespace WPAICG\Admin\Ajax\Migration\Migrate;

use WPAICG\Admin\Ajax\Migration\AIPKit_Migration_Base_Ajax_Action;
use WPAICG\Core\TokenManager\Constants\GuestTableConstants;
use WPAICG\Core\TokenManager\Constants\MetaKeysConstants;
use WP_Query;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Handles the AJAX action for migrating AI Forms data.
 * The migration for Content Writer templates and AutoGPT tasks has been removed as per simplification.
 */
class AIPKit_Migrate_CPT_Data_Action extends AIPKit_Migration_Base_Ajax_Action
{
    public function handle_request()
    {
        $permission_check = $this->check_module_access_permissions('settings', self::MIGRATION_NONCE_ACTION);
        if (is_wp_error($permission_check)) {
            $this->send_wp_error($permission_check);
            return;
        }
        $this->update_category_status('cpt_data', 'in_progress');
        global $wpdb;
        $processed_counts = ['forms' => 0, 'form_tokens' => 0];

        try {
            if (!class_exists(\WPAICG\AIForms\Storage\AIPKit_AI_Form_Storage::class) || !class_exists(\WPAICG\AIForms\Admin\AIPKit_AI_Form_Admin_Setup::class)) {
                throw new \Exception('AI Forms dependency classes not found for CPT migration.');
            }

            // --- REMOVED: Content Writer Templates Migration ---
            // --- REMOVED: AutoGPT Tasks Migration ---

            // --- Migrate AI Forms ---
            $old_forms_query = new WP_Query([
                'post_type' => 'wpaicg_form', 'post_status' => ['publish', 'draft', 'pending', 'private'], 'posts_per_page' => -1,
            ]);
            if ($old_forms_query->have_posts()) {
                $form_storage = new \WPAICG\AIForms\Storage\AIPKit_AI_Form_Storage();
                while ($old_forms_query->have_posts()) {
                    $old_forms_query->the_post();
                    $old_form_post = get_post();
                    $new_post_args = [
                        'post_title'   => $old_form_post->post_title, 'post_content' => '',
                        'post_status'  => $old_form_post->post_status, 'post_author'  => $old_form_post->post_author,
                        'post_type'    => \WPAICG\AIForms\Admin\AIPKit_AI_Form_Admin_Setup::POST_TYPE,
                    ];
                    $new_form_id = wp_insert_post($new_post_args, true);
                    if (is_wp_error($new_form_id)) {
                        continue;
                    }
                    $new_settings = [];
                    $new_settings['prompt_template'] = get_post_meta($old_form_post->ID, 'wpaicg_form_prompt', true) ?: '';
                    $new_settings['ai_provider'] = get_post_meta($old_form_post->ID, 'wpaicg_form_model_provider', true) ?: 'OpenAI';
                    $new_settings['ai_model'] = get_post_meta($old_form_post->ID, 'wpaicg_form_engine', true) ?: '';
                    $new_settings['temperature'] = get_post_meta($old_form_post->ID, 'wpaicg_form_temperature', true) ?: '1.0';
                    $new_settings['max_tokens'] = get_post_meta($old_form_post->ID, 'wpaicg_form_max_tokens', true) ?: '2000';
                    $old_fields_json = get_post_meta($old_form_post->ID, 'wpaicg_form_fields', true);
                    $old_fields = json_decode($old_fields_json, true);
                    $new_structure = [];
                    if (is_array($old_fields)) {
                        foreach ($old_fields as $field) {
                            $field_type_map = ['text' => 'text-input', 'textarea' => 'textarea', 'select' => 'select', 'checkbox' => 'checkbox', 'radio' => 'radio-button', 'file' => 'file-upload',];
                            $new_element = [
                                'internalId' => 'el-' . time() . '-' . uniqid('', true), 'type' => $field_type_map[$field['type'] ?? 'text'] ?? 'text-input',
                                'label' => $field['label'] ?? 'Untitled Field', 'placeholder' => $field['placeholder'] ?? '',
                                'fieldId' => $field['id'] ?? 'field_' . uniqid('', true),
                                'required' => isset($field['required']) && $field['required'] === 'yes',
                                'helpText' => $field['help'] ?? '', 'options' => []
                            ];
                            if (isset($field['options']) && is_string($field['options'])) {
                                $options_array = explode('|', $field['options']);
                                foreach ($options_array as $opt_str) {
                                    if (!empty(trim($opt_str))) {
                                        $new_element['options'][] = ['value' => trim($opt_str), 'text' => trim($opt_str)];
                                    }
                                }
                            }
                            $new_structure[] = [
                                'internalId' => 'row-' . time() . '-' . uniqid('', true), 'type' => 'layout-row',
                                'columns' => [[ 'internalId' => 'col-' . time() . '-' . uniqid('', true), 'width' => '100%', 'elements' => [$new_element] ]]
                            ];
                        }
                    }
                    $new_settings['form_structure'] = wp_json_encode($new_structure);
                    $new_settings['enable_vector_store'] = (get_post_meta($old_form_post->ID, 'wpaicg_form_embeddings', true) === 'yes') ? '1' : '0';
                    $new_settings['vector_store_provider'] = get_post_meta($old_form_post->ID, 'wpaicg_form_vectordb', true) ?: 'pinecone';
                    $new_settings['pinecone_index_name'] = get_post_meta($old_form_post->ID, 'wpaicg_form_pineconeindexes', true) ?: '';
                    $new_settings['qdrant_collection_name'] = get_post_meta($old_form_post->ID, 'wpaicg_form_collections', true) ?: '';
                    $new_settings['vector_embedding_provider'] = get_post_meta($old_form_post->ID, 'wpaicg_form_selected_embedding_provider', true) ?: 'openai';
                    $new_settings['vector_embedding_model'] = get_post_meta($old_form_post->ID, 'wpaicg_form_selected_embedding_model', true) ?: '';
                    $new_settings['vector_store_top_k'] = get_post_meta($old_form_post->ID, 'wpaicg_form_embeddings_limit', true) ?: 3;
                    $labels = [];
                    $labels['generate_button'] = get_post_meta($old_form_post->ID, 'wpaicg_form_generate_text', true) ?: '';
                    $labels['stop_button'] = get_post_meta($old_form_post->ID, 'wpaicg_form_stop_text', true) ?: '';
                    $labels['download_button'] = get_post_meta($old_form_post->ID, 'wpaicg_form_download_text', true) ?: '';
                    $labels['save_button'] = get_post_meta($old_form_post->ID, 'wpaicg_form_draft_text', true) ?: '';
                    $labels['copy_button'] = get_post_meta($old_form_post->ID, 'wpaicg_form_copy_text', true) ?: '';
                    $new_settings['labels'] = array_filter($labels);
                    $form_storage->save_form_settings($new_form_id, $new_settings);
                    $processed_counts['forms']++;
                }
                wp_reset_postdata();
            }

            // --- Migrate AI Forms Tokens ---
            $old_tokens_table = $wpdb->prefix . 'wpaicg_formtokens';
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table check for migration.
            if ($wpdb->get_var("SHOW TABLES LIKE '" . esc_sql($old_tokens_table) . "'") === $old_tokens_table) {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Read-only migration from legacy table.
                $old_tokens_data = $wpdb->get_results("SELECT * FROM " . esc_sql($old_tokens_table), ARRAY_A);
                $aggregated_tokens = [];
                foreach ($old_tokens_data as $token_row) {
                    $key_user_id = $token_row['user_id'] ? (int)$token_row['user_id'] : null;
                    $key_session_id = !$key_user_id && !empty($token_row['session_id']) ? $token_row['session_id'] : null;
                    $context_key = $key_user_id ? "user_{$key_user_id}" : "guest_{$key_session_id}";
                    if (!isset($aggregated_tokens[$context_key])) {
                        $aggregated_tokens[$context_key] = ['tokens' => 0, 'first_ts' => strtotime($token_row['created_at'])];
                    }
                    $aggregated_tokens[$context_key]['tokens'] += (int)$token_row['tokens'];
                }
                foreach ($aggregated_tokens as $context_key => $data) {
                    if (strpos($context_key, 'user_') === 0) {
                        $uid = (int)str_replace('user_', '', $context_key);
                        update_user_meta($uid, MetaKeysConstants::AIFORMS_USAGE_META_KEY, $data['tokens']);
                        update_user_meta($uid, MetaKeysConstants::AIFORMS_RESET_META_KEY, $data['first_ts']);
                    } elseif (strpos($context_key, 'guest_') === 0) {
                        $sid = str_replace('guest_', '', $context_key);
                        if (!empty($sid)) {
                            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table query for migration.
                            $wpdb->replace($wpdb->prefix . GuestTableConstants::GUEST_TABLE_NAME_SUFFIX, ['session_id' => $sid, 'bot_id' => GuestTableConstants::AI_FORMS_GUEST_CONTEXT_ID, 'tokens_used' => $data['tokens'], 'last_reset_timestamp' => $data['first_ts'], 'last_updated_at' => current_time('mysql', 1)]);
                        }
                    }
                    $processed_counts['form_tokens']++;
                }
            }
            // --- END ---

            $this->update_category_status('cpt_data', 'migrated');
            wp_send_json_success([
                /* translators: %1$d is the number of forms, %2$d is the number of token records */
                'message' => sprintf(__('AI Forms data migrated: %1$d forms and %2$d token records migrated. Other data in this category was not migrated.', 'gpt3-ai-content-generator'), $processed_counts['forms'], $processed_counts['form_tokens']),
                'category_status' => 'migrated'
            ]);

        } catch (\Exception $e) {
            $this->handle_exception($e, 'cpt_migration_failed', 'cpt_data');
        }
    }
}
