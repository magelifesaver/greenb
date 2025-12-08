<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/ajax/migration/migrate/class-aipkit-migrate-indexed-data-action.php
// Status: NEW FILE

namespace WPAICG\Admin\Ajax\Migration\Migrate;

use WPAICG\Admin\Ajax\Migration\AIPKit_Migration_Base_Ajax_Action;
use WP_Query;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Handles the AJAX action for migrating old indexed data (wpaicg_embeddings, wpaicg_pdfadmin, wpaicg_builder)
 * to the new wp_aipkit_vector_data_source table.
 */
class AIPKit_Migrate_Indexed_Data_Action extends AIPKit_Migration_Base_Ajax_Action
{
    public function handle_request()
    {
        $permission_check = $this->check_module_access_permissions('settings', self::MIGRATION_NONCE_ACTION);
        if (is_wp_error($permission_check)) {
            $this->send_wp_error($permission_check);
            return;
        }

        $this->update_category_status('indexed_data', 'in_progress');
        global $wpdb;

        $post_types_to_migrate = ['wpaicg_embeddings', 'wpaicg_pdfadmin', 'wpaicg_builder'];
        $data_source_table_name = $wpdb->prefix . 'aipkit_vector_data_source';
        $total_found = 0;
        $migrated_count = 0;
        $failed_count = 0;

        try {
            $query = new WP_Query([
                'post_type' => $post_types_to_migrate,
                'post_status' => 'any',
                'posts_per_page' => -1,
                'no_found_rows' => false,
            ]);

            $total_found = $query->found_posts;
            if ($query->have_posts()) {
                while ($query->have_posts()) {
                    $query->the_post();
                    $post = get_post();
                    $post_id = $post->ID;

                    // Skip if already migrated in a previous run
                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: This is a one-time migration.
                    $existing_log = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$data_source_table_name} WHERE post_id = %d AND file_id = %s", $post_id, (string)$post_id));
                    if ($existing_log) {
                        continue;
                    }

                    $wpaicg_index = get_post_meta($post_id, 'wpaicg_index', true) ?: '';
                    $wpaicg_provider = get_post_meta($post_id, 'wpaicg_provider', true);

                    // Determine provider and index/collection name
                    $provider = 'Pinecone';
                    $vector_store_id = '';
                    if (strpos($wpaicg_index, 'pinecone.io') !== false) {
                        $svc_pos = strpos($wpaicg_index, '.svc');
                        if ($svc_pos !== false) {
                            $sub_string = substr($wpaicg_index, 0, $svc_pos);
                            $last_dash = strrpos($sub_string, '-');
                            $vector_store_id = ($last_dash !== false) ? substr($wpaicg_index, 0, $last_dash) : $sub_string;
                        } else {
                            $vector_store_id = $wpaicg_index;
                        }
                    } else {
                        $provider = 'Qdrant';
                        $vector_store_id = $wpaicg_index; // For Qdrant, it's the collection name
                    }

                    // Map post type to source type for logging
                    $source_type_map = [
                        'wpaicg_embeddings' => 'text_entry',
                        'wpaicg_pdfadmin' => 'pdf_upload',
                        'wpaicg_builder' => 'auto_scan'
                    ];
                    $source_type = $source_type_map[$post->post_type] ?? 'unknown_legacy';

                    $log_data = [
                        'user_id' => $post->post_author,
                        'timestamp' => $post->post_date,
                        'provider' => $provider,
                        'vector_store_id' => $vector_store_id,
                        'vector_store_name' => $vector_store_id,
                        'post_id' => $post_id,
                        'post_title' => $post->post_title,
                        'status' => 'success',
                        'message' => 'Migrated from legacy post type: ' . $post->post_type,
                        'indexed_content' => $post->post_content,
                        'file_id' => (string)$post_id, // The old post ID was the vector ID
                        'batch_id' => null,
                        'embedding_provider' => $wpaicg_provider ?: 'OpenAI',
                        'embedding_model' => get_post_meta($post_id, 'wpaicg_model', true) ?: 'text-embedding-ada-002',
                    ];

                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time migration write.
                    $inserted = $wpdb->insert($data_source_table_name, $log_data);
                    if ($inserted === false) {
                        $failed_count++;
                    } else {
                        $migrated_count++;
                    }
                }
                wp_reset_postdata();
            }

            $this->update_category_status('indexed_data', 'migrated');

            wp_send_json_success([
                /* translators: %1$d is the total number of indexed data found, %2$d is the number of successfully migrated items, %3$d is the number of failed migrations */
                'message' => sprintf(__('Indexed data migration complete. Processed: %1$d, Migrated: %2$d, Failed: %3$d.', 'gpt3-ai-content-generator'),$total_found,$migrated_count,$failed_count),
                'category_status' => 'migrated'
            ]);

        } catch (\Exception $e) {
            $this->handle_exception($e, 'indexed_data_migration_failed', 'indexed_data');
        }
    }
}