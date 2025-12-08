<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/ajax/migration/delete/class-aipkit-delete-old-indexed-data-action.php
// Status: NEW FILE

namespace WPAICG\Admin\Ajax\Migration\Delete;

use WPAICG\Admin\Ajax\Migration\AIPKit_Migration_Base_Ajax_Action;
use WP_Query;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Handles the AJAX action for deleting old Indexed data (wpaicg_embeddings, wpaicg_pdfadmin, wpaicg_builder posts).
 */
class AIPKit_Delete_Old_Indexed_Data_Action extends AIPKit_Migration_Base_Ajax_Action
{
    public function handle_request()
    {
        $permission_check = $this->check_module_access_permissions('settings', self::MIGRATION_NONCE_ACTION);
        if (is_wp_error($permission_check)) {
            $this->send_wp_error($permission_check);
            return;
        }

        $deleted_counts = ['posts' => 0];
        $post_types_to_delete = ['wpaicg_embeddings', 'wpaicg_pdfadmin', 'wpaicg_builder'];

        try {
            $query = new WP_Query([
                'post_type' => $post_types_to_delete,
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
                        $deleted_counts['posts']++;
                    }
                }
            }

            $this->update_category_status('indexed_data', 'deleted');

            wp_send_json_success([
                /* translators: %d is the number of posts deleted */
                'message' => sprintf(__('Old indexed data deleted: %d posts removed.', 'gpt3-ai-content-generator'), $deleted_counts['posts']),
                'category_status' => 'deleted'
            ]);

        } catch (\Exception $e) {
            $this->handle_exception($e, 'indexed_data_deletion_failed', 'indexed_data');
        }
    }
}