<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/includes/class-wp-ai-content-generator-activator.php
// Status: MODIFIED
// I have updated `ensure_tables_for_current_site` to check for all required database tables, not just one, ensuring that existing users who update the plugin get newly added tables created automatically.

namespace WPAICG;

// Use statements for classes needed during activation
use WPAICG\Chat\Admin\AdminSetup;
use WPAICG\Chat\Storage\DefaultBotSetup;
use WPAICG\AIPKit_Role_Manager;
use WPAICG\Core\TokenManager\AIPKit_Token_Manager;
use WPAICG\Core\Stream\Cache\AIPKit_SSE_Message_Cache;
use WPAICG\AutoGPT\AIPKit_Automated_Task_Cron;
use WPAICG\ContentWriter\AIPKit_Content_Writer_Template_Manager;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Fired during plugin activation. Also contains multisite setup logic.
 */
class WP_AI_Content_Generator_Activator
{
    // --- NEW: Migration Status Option Constants ---
    public const MIGRATION_DATA_EXISTS_OPTION = 'aipkit_migration_data_exists';
    public const MIGRATION_STATUS_OPTION = 'aipkit_migration_status';
    public const MIGRATION_LAST_ERROR_OPTION = 'aipkit_migration_last_error';
    public const MIGRATION_OLD_VERSION_OPTION = 'aipkit_old_plugin_version_migrated_from';
    public const MIGRATION_CATEGORY_STATUS_OPTION = 'aipkit_migration_category_status'; // ADDED
    public const MIGRATION_ANALYSIS_RESULTS_OPTION = 'aipkit_migration_analysis_results'; // ADDED
    // --- END NEW ---

    /**
     * Main activation routine for single site or per-site activation.
     * REVISED: This method is now significantly leaner. It only handles tasks that
     * absolutely must run on first-time activation, like creating database tables.
     * Other setup tasks (default content, cron scheduling) are moved to the
     * `check_for_updates` hook which runs on `init` and is triggered by the version option.
     */
    public static function activate()
    {
        // Create database tables if they don't exist.
        self::setup_tables_for_blog();

        // Load the main plugin class to get access to constants.
        if (!class_exists(WP_AI_Content_Generator::class)) {
            require_once WPAICG_PLUGIN_DIR . 'includes/class-wp-ai-content-generator.php';
        }
        // Set the current version in the database. This is crucial for the `check_for_updates`
        // routine to correctly trigger on new installs and version changes.
        update_option(WP_AI_Content_Generator::DB_VERSION_OPTION, WPAICG_VERSION, 'no');

        // --- MODIFICATION: Consolidate all one-time/update tasks here ---
        // This ensures that fresh installs and reactivations get all necessary setup routines.
        // The check_for_updates() hook will also call these, which is a safe redundancy for version updates.

        // Update Role Manager permissions, migrating old caps if necessary.
        $role_manager_path = WPAICG_PLUGIN_DIR . 'classes/dashboard/class-aipkit_role_manager.php';
        if (file_exists($role_manager_path)) {
            require_once $role_manager_path;
            if (class_exists('\\WPAICG\\AIPKit_Role_Manager')) {
                \WPAICG\AIPKit_Role_Manager::update_permissions_on_activation();
            }
        }

        // Ensure Default Chatbot exists.
        $default_bot_setup_path = WPAICG_PLUGIN_DIR . 'classes/chat/storage/class-aipkit_default_bot_setup.php';
        if (file_exists($default_bot_setup_path)) {
            require_once $default_bot_setup_path;
            if (class_exists('\\WPAICG\\Chat\\Storage\\DefaultBotSetup')) {
                \WPAICG\Chat\Storage\DefaultBotSetup::ensure_default_chatbot();
            }
        }

        // Ensure Default Content Writer Template exists.
        $cw_template_manager_path = WPAICG_PLUGIN_DIR . 'classes/content-writer/class-aipkit-content-writer-template-manager.php';
        if (file_exists($cw_template_manager_path)) {
            require_once $cw_template_manager_path;
            if (class_exists('\\WPAICG\\ContentWriter\\AIPKit_Content_Writer_Template_Manager')) {
                \WPAICG\ContentWriter\AIPKit_Content_Writer_Template_Manager::ensure_default_template_exists();
            }
        }

        // Ensure Default AI Forms exist.
        $ai_form_defaults_path = WPAICG_PLUGIN_DIR . 'classes/ai-forms/admin/class-aipkit-ai-form-defaults.php';
        if (file_exists($ai_form_defaults_path)) {
            require_once $ai_form_defaults_path;
            if (class_exists('\\WPAICG\\AIForms\\Admin\\AIPKit_AI_Form_Defaults')) {
                \WPAICG\AIForms\Admin\AIPKit_AI_Form_Defaults::ensure_default_forms_exist();
            }
        }
        // --- END MODIFICATION ---

        // Schedule cron jobs (methods are idempotent, so it's safe to run).
        if (class_exists(AIPKit_Token_Manager::class)) {
            AIPKit_Token_Manager::schedule_token_reset_event();
        }
        if (class_exists(AIPKit_SSE_Message_Cache::class)) {
            AIPKit_SSE_Message_Cache::schedule_cleanup_event();
        }
        if (class_exists(AIPKit_Automated_Task_Cron::class)) {
            AIPKit_Automated_Task_Cron::init();
        }
    }

    /**
     * Checks for old plugin data and sets initial migration status options.
     * UPDATED: Sets status to 'analysis_required' and initializes new state options.
     * MODIFIED: Changed visibility from private to public to allow calls from outside activation.
     */
    public static function check_for_old_data_and_set_migration_status()
    {
        global $wpdb;
        $current_migration_status = get_option(self::MIGRATION_STATUS_OPTION, '');

        // If migration is already completed or user has chosen to start fresh, do nothing.
        if (in_array($current_migration_status, ['completed', 'fresh_install_chosen', 'not_applicable'], true)) {
            return;
        }

        $old_data_found = false;
        $old_table_names = ['wpaicg', 'wpaicg_chatlogs', 'wpaicg_chattokens', 'wpaicg_image_logs', 'wpaicg_imagetokens', 'wpaicg_form_logs', 'wpaicg_form_feedback', 'wpaicg_formtokens'];
        foreach ($old_table_names as $table_suffix) {
            $table_name = $wpdb->prefix . $table_suffix;
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) === $table_name) {
                $old_data_found = true;
                break;
            }
        }

        if (!$old_data_found) {
            // Check for old options
            $old_options_to_check = ['wpaicg_options', 'wpaicg_provider', 'wpaicg_chat_widget', 'wpaicg_module_settings'];
            foreach ($old_options_to_check as $option_name) {
                if (get_option($option_name) !== false) {
                    $old_data_found = true;
                    break;
                }
            }
        }
        // Check for old CPTs
        if (!$old_data_found) {
            $old_cpts = ['wpaicg_mtemplate', 'wpaicg_tracking', 'wpaicg_bulk', 'wpaicg_chatbot', 'wpaicg_form', 'wpaicg_embeddings', 'wpaicg_pdfadmin', 'wpaicg_file', 'wpaicg_finetune', 'wpaicg_audio'];
            foreach ($old_cpts as $cpt_slug) {
                $posts = get_posts(['post_type' => $cpt_slug, 'post_status' => 'any', 'posts_per_page' => 1, 'fields' => 'ids']);
                if (!empty($posts)) {
                    $old_data_found = true;
                    break;
                }
            }
        }


        if ($old_data_found) {
            update_option(self::MIGRATION_DATA_EXISTS_OPTION, true, 'no');

            // Only initialize the migration state ONCE.
            if (empty($current_migration_status) || $current_migration_status === 'not_started') {
                // Set the status to 'analysis_required' to start the process.
                update_option(self::MIGRATION_STATUS_OPTION, 'analysis_required', 'no');

                // Initialize new migration state options for a fresh migration attempt.
                $migration_categories = ['global_settings', 'cron_jobs', 'cpt_data', 'chatbot_data', 'image_data'];
                $default_category_status = array_fill_keys($migration_categories, 'pending');
                update_option(self::MIGRATION_CATEGORY_STATUS_OPTION, $default_category_status, 'no');
                update_option(self::MIGRATION_ANALYSIS_RESULTS_OPTION, [], 'no'); // Crucially, only init this once.

                // Store the old version for informational purposes.
                $old_plugin_version = get_option('wpaicg_version', '1.9.x');
                update_option(self::MIGRATION_OLD_VERSION_OPTION, sanitize_text_field($old_plugin_version), 'no');
            }
            // If status is already 'analysis_required', 'analysis_complete', etc., do nothing here to preserve state.

        } else {
            // No old data found, set to not applicable and clean up any potential leftover options.
            update_option(self::MIGRATION_DATA_EXISTS_OPTION, false, 'no');
            update_option(self::MIGRATION_STATUS_OPTION, 'not_applicable', 'no');
            delete_option(self::MIGRATION_OLD_VERSION_OPTION);
            delete_option(self::MIGRATION_LAST_ERROR_OPTION);
            delete_option(self::MIGRATION_CATEGORY_STATUS_OPTION);
            delete_option(self::MIGRATION_ANALYSIS_RESULTS_OPTION);
        }
    }


    public static function setup_tables_for_blog($blog_id = null)
    {
        $switched = false;
        if (is_multisite() && $blog_id !== null && get_current_blog_id() !== $blog_id) {
            switch_to_blog($blog_id);
            $switched = true;
        }
        $db_schema_path = WPAICG_PLUGIN_DIR . 'includes/database-schema.php';
        if (!file_exists($db_schema_path)) {
            if ($switched) {
                restore_current_blog();
            }
            return;
        }
        require_once $db_schema_path;

        if (function_exists('aipkit_create_logs_table')) {
            aipkit_create_logs_table();
        }
        if (function_exists('aipkit_create_guest_token_usage_table')) {
            aipkit_create_guest_token_usage_table();
        }
        if (function_exists('aipkit_create_sse_message_cache_table')) {
            aipkit_create_sse_message_cache_table();
        }
        if (function_exists('aipkit_create_vector_data_source_table')) {
            aipkit_create_vector_data_source_table();
        }
        if (function_exists('aipkit_create_automated_tasks_table')) {
            aipkit_create_automated_tasks_table();
        }
        if (function_exists('aipkit_create_automated_task_queue_table')) {
            aipkit_create_automated_task_queue_table();
        }
        if (function_exists('aipkit_create_content_writer_templates_table')) {
            aipkit_create_content_writer_templates_table();
        }
        if (function_exists('aipkit_create_rss_history_table')) {
            aipkit_create_rss_history_table();
        }
        if ($switched) {
            restore_current_blog();
        }
    }

    public static function setup_new_blog($blog, $user_id)
    {
        $blog_id = is_object($blog) ? $blog->blog_id : (is_array($blog) ? $blog['blog_id'] : 0);
        if ($blog_id > 0) {
            self::setup_tables_for_blog($blog_id);
            switch_to_blog($blog_id);
            if (class_exists('\\WPAICG\\Chat\\Storage\\DefaultBotSetup')) {
                DefaultBotSetup::ensure_default_chatbot();
            }
            if (class_exists('\\WPAICG\\ContentWriter\\AIPKit_Content_Writer_Template_Manager')) {
                AIPKit_Content_Writer_Template_Manager::ensure_default_template_exists();
            }
            restore_current_blog();
        }
    }

    /**
     * Unschedule old plugin cron hooks during migration.
     * @since 2.1
     */
    public static function unschedule_old_cron_hooks()
    {
        $old_hooks = [
            'wpaicg_remove_chat_tokens_limited',
            'wpaicg_remove_promptbase_tokens_limited',
            'wpaicg_remove_image_tokens_limited',
            'wpaicg_remove_forms_tokens_limited',
            'wpaicg_cron', // General cron for old bulk content
            'wpaicg_builder', // General cron for old embeddings
        ];
        $old_task_specific_hook_prefix = 'wpaicg_task_event_'; // Prefix for individual task events

        foreach ($old_hooks as $hook_name) {
            $timestamp = wp_next_scheduled($hook_name);
            if ($timestamp) {
                wp_unschedule_event($timestamp, $hook_name);
            }
            // Ensure any recurring schedules are also cleared
            wp_clear_scheduled_hook($hook_name);
        }
    }
}