<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/ajax/migration/delete/class-aipkit-delete-old-cpt-data-action.php
// Status: MODIFIED

namespace WPAICG\Admin\Ajax\Migration\Delete;

use WPAICG\Admin\Ajax\Migration\AIPKit_Migration_Base_Ajax_Action;
use WPAICG\WP_AI_Content_Generator_Activator;
use WP_Query;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Handles the AJAX action for deleting old CPT and AI Forms data.
 */
class AIPKit_Delete_Old_CPT_Data_Action extends AIPKit_Migration_Base_Ajax_Action
{
    public function handle_request()
    {
        $permission_check = $this->check_module_access_permissions('settings', self::MIGRATION_NONCE_ACTION);
        if (is_wp_error($permission_check)) {
            $this->send_wp_error($permission_check);
            return;
        }

        global $wpdb;
        $deleted_counts = ['cpt_posts' => 0, 'tables' => 0];
        $old_cpts_to_delete = [
            'wpaicg_mtemplate', 'wpaicg_pdfadmin', 'wpaicg_file',
            'wpaicg_finetune', 'wpaicg_form'
        ];

        try {
            // Delete CPT posts
            foreach ($old_cpts_to_delete as $cpt_slug) {
                $query = new WP_Query([
                    'post_type' => $cpt_slug,
                    'post_status' => 'any',
                    'posts_per_page' => -1,
                    'fields' => 'ids',
                    'no_found_rows' => true,
                    'update_post_meta_cache' => false,
                    'update_post_term_cache' => false,
                ]);
                if ($query->have_posts()) {
                    foreach ($query->posts as $post_id) {
                        if (wp_delete_post($post_id, true)) {
                            $deleted_counts['cpt_posts']++;
                        }
                    }
                }
            }

            // Drop old AI Forms tables
            $old_tables = ['wpaicg_form_logs', 'wpaicg_form_feedback', 'wpaicg_formtokens'];
            foreach ($old_tables as $table_suffix) {
                $table_name = $wpdb->prefix . $table_suffix;
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Reason: Direct query to check if the table exists.
                if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) === $table_name) {$wpdb->query("DROP TABLE IF EXISTS " . esc_sql($table_name));
                    $deleted_counts['tables']++;
                }
            }

            // Update category status
            $this->update_category_status('cpt_data', 'deleted');

            wp_send_json_success([
                /* translators: %1$d is the number of posts, %2$d is the number of tables */
                'message' => sprintf(__('Old AI Forms & associated legacy data deleted: %1$d posts and %2$d database tables removed.', 'gpt3-ai-content-generator'), $deleted_counts['cpt_posts'], $deleted_counts['tables']),
                'category_status' => 'deleted'
            ]);

        } catch (\Exception $e) {
            $this->handle_exception($e, 'cpt_data_deletion_failed', 'cpt_data');
        }
    }
}