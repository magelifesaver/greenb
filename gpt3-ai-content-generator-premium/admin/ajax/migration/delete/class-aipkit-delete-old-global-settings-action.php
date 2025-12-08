<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/ajax/migration/delete/class-aipkit-delete-old-global-settings-action.php
// Status: MODIFIED
// I have added the old WooCommerce settings options to the list of options to be deleted.

namespace WPAICG\Admin\Ajax\Migration\Delete;

use WPAICG\Admin\Ajax\Migration\AIPKit_Migration_Base_Ajax_Action;
use WPAICG\WP_AI_Content_Generator_Activator;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Handles the AJAX action for deleting old Global Settings data.
 */
class AIPKit_Delete_Old_Global_Settings_Action extends AIPKit_Migration_Base_Ajax_Action
{
    public function handle_request()
    {
        $permission_check = $this->check_module_access_permissions('settings', self::MIGRATION_NONCE_ACTION);
        if (is_wp_error($permission_check)) {
            $this->send_wp_error($permission_check);
            return;
        }

        global $wpdb;
        $deleted_counts = ['options' => 0, 'tables' => 0];

        try {
            // Delete old options
            $old_options = [
                'wpaicg_options', 'wpaicg_provider', 'wpaicg_chat_widget', 'wpaicg_module_settings',
                'wpaicg_version', 'wpaicg_openai_api_key', 'wpaicg_azure_api_key', 'wpaicg_azure_endpoint',
                'wpaicg_azure_deployment', 'wpaicg_google_model_api_key', 'wpaicg_google_default_model',
                'wpaicg_openrouter_api_key', 'wpaicg_openrouter_default_model', 'wpaicg_deepseek_api_key',
                'wpaicg_elevenlabs_api', 'wpaicg_pinecone_api', 'wpaicg_qdrant_api_key', 'wpaicg_qdrant_endpoint',
                'wpaicg_image_setting_provider', 'wpaicg_image_setting_openai_model', 'wpaicg_image_setting_openai_size',
                'wpaicg_image_setting_openai_quality', 'wpaicg_image_setting_openai_style', 'wpaicg_image_setting_openai_n',
                'wpaicg_image_setting_azure_model', 'wpaicg_image_setting_azure_size', 'wpaicg_image_setting_azure_n',
                'wpaicg_image_setting_google_model', 'wpaicg_image_setting_google_size', 'wpaicg_image_setting_google_n',
                'wpaicg_chat_shortcode_options', 'wpaicg_banned_words', 'wpaicg_banned_ips',
                'wpaicg_ai_model', 'wpaicg_custom_models', 'wpaicg_google_safety_settings', 'wpaicg_sleep_time',
                'wpaicg_openai_model_list', 'wpaicg_openrouter_model_list', 'wpaicg_google_model_list',
                'wpaicg_limit_tokens_form', // Old AI Forms token settings
                'wpaicg_editor_button_menus',
                'wpaicg_editor_change_action',
                'wpaicg_woo_generate_title', 'wpaicg_woo_generate_description', 'wpaicg_woo_generate_short',
                'wpaicg_woo_generate_tags', 'wpaicg_woo_meta_description', '_wpaicg_shorten_woo_url',
                'wpaicg_generate_woo_focus_keyword', 'wpaicg_enforce_woo_keyword_in_url', 'wpaicg_woo_custom_prompt',
                'wpaicg_order_status_token'
            ];
            foreach ($old_options as $option_name) {
                if (get_option($option_name) !== false) {
                    if (delete_option($option_name)) {
                        $deleted_counts['options']++;
                    }
                }
            }

            // Drop old settings table
            $old_table_name = $wpdb->prefix . 'wpaicg';
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Reason: Direct query to check if the table exists.
            if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $old_table_name)) === $old_table_name) {$wpdb->query("DROP TABLE IF EXISTS " . esc_sql($old_table_name));
                $deleted_counts['tables']++;
            }

            // Update category status
            $this->update_category_status('global_settings', 'deleted');

            wp_send_json_success([
                /* translators: %1$d is the number of options, %2$d is the number of tables */
                'message' => sprintf(__('Old global settings deleted: %1$d options and %2$d database tables removed.', 'gpt3-ai-content-generator'), $deleted_counts['options'], $deleted_counts['tables']),
                'category_status' => 'deleted'
            ]);

        } catch (\Exception $e) {
            $this->handle_exception($e, 'global_settings_deletion_failed', 'global_settings');
        }
    }
}
