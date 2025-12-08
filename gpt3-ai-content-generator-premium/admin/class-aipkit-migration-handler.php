<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/class-aipkit-migration-handler.php
// Status: MODIFIED
// I have added the new action handlers for indexed data to the registration method.

namespace WPAICG\Admin;

// --- MODIFIED: Updated use statements for new paths ---
use WPAICG\Admin\Ajax\Migration\Migrate\AIPKit_Migrate_Global_Settings_Action;
use WPAICG\Admin\Ajax\Migration\Migrate\AIPKit_Migrate_CPT_Data_Action;
use WPAICG\Admin\Ajax\Migration\Migrate\AIPKit_Migrate_Chatbot_Data_Action;
use WPAICG\Admin\Ajax\Migration\Migrate\AIPKit_Migrate_Indexed_Data_Action; // NEW
use WPAICG\Admin\Ajax\Migration\Delete\AIPKit_Delete_Old_Indexed_Data_Action; // NEW
// --- END MODIFICATION ---
use WPAICG\Chat\Admin\AdminSetup as ChatAdminSetup; // For POST_TYPE in helper methods
use WPAICG\Chat\Storage\BotSettingsManager; // For BotSettingsManager in helper methods
use WP_Error; // Ensure WP_Error is available if any helper directly returns it

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * AIPKit_Migration_Handler
 * Orchestrates the registration of AJAX hooks for the migration tool.
 * The actual AJAX handling logic is now in dedicated action classes.
 * Helper methods remain here or are moved to the relevant action class.
 */
class AIPKit_Migration_Handler
{
    // NONCE_ACTION is now in AIPKit_Migration_Base_Ajax_Action

    public function __construct()
    {
        // Constructor can be empty if not needed for initialization.
        // Dependencies for action classes are loaded by Migration_Ajax_Handlers_Loader.
    }

    public function register_ajax_hooks()
    {
        // Instantiate each new action handler
        $migrate_global_settings_action = new AIPKit_Migrate_Global_Settings_Action();
        $migrate_cpt_data_action        = new AIPKit_Migrate_CPT_Data_Action();
        $migrate_chatbot_data_action    = new AIPKit_Migrate_Chatbot_Data_Action();
        $migrate_indexed_data_action    = new AIPKit_Migrate_Indexed_Data_Action(); // NEW
        $delete_indexed_data_action     = new AIPKit_Delete_Old_Indexed_Data_Action(); // NEW

        // Register AJAX actions pointing to the handle_request method of each new class
        add_action('wp_ajax_aipkit_migrate_global_settings', [$migrate_global_settings_action, 'handle_request']);
        add_action('wp_ajax_aipkit_migrate_cpt_data', [$migrate_cpt_data_action, 'handle_request']);
        add_action('wp_ajax_aipkit_migrate_chatbot_data', [$migrate_chatbot_data_action, 'handle_request']);
        add_action('wp_ajax_aipkit_migrate_indexed_data', [$migrate_indexed_data_action, 'handle_request']); // NEW
        add_action('wp_ajax_aipkit_delete_old_indexed_data', [$delete_indexed_data_action, 'handle_request']); // NEW
    }

    // Helper methods `map_old_chat_settings` and `get_old_chat_meta_keys_map`
    // are now moved to `AIPKit_Migrate_Chatbot_Data_Action` as private methods.
}