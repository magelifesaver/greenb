<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/chat/class-aipkit_chat_initializer.php

namespace WPAICG\Chat;

// Core classes instantiated in register_hooks
use WPAICG\Chat\Admin\AdminSetup;
use WPAICG\Chat\Core;
use WPAICG\Chat\Frontend; // Keep this as the new Frontend\Assets is here
use WPAICG\Chat\Storage;
use WPAICG\Chat\Utils;
use WPAICG\Chat\Admin\Ajax;
use WPAICG\Core\Stream\Handler\SSEHandler;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Require the new initializer method files
$initializer_methods_path = WPAICG_PLUGIN_DIR . 'classes/chat/initializer/';
require_once $initializer_methods_path . 'load-core-services.php';
require_once $initializer_methods_path . 'load-admin-setup.php';
require_once $initializer_methods_path . 'load-ajax-handlers.php';
require_once $initializer_methods_path . 'load-frontend.php';
require_once $initializer_methods_path . 'load-utils.php';
require_once $initializer_methods_path . 'load-sse-handler.php';
require_once $initializer_methods_path . 'register-hooks-core.php';
require_once $initializer_methods_path . 'register-hooks-admin-ajax.php';
require_once $initializer_methods_path . 'register-hooks-general-ajax.php';
require_once $initializer_methods_path . 'register-hooks-sse-ajax.php';


/**
 * Initializes the AIPKit Chat functionality by loading dependencies and registering hooks.
 * Logic for methods is now in separate files under the Initializer namespace.
 */
class Initializer
{
    /**
     * Ensure dependencies specific to Chat module hooks are loaded.
     * Note: This is largely redundant if Chat_Dependencies_Loader has already run.
     */
    public static function load_dependencies()
    {
        // Call the externalized logic functions
        Initializer\load_core_services_logic();
        Initializer\load_admin_setup_logic();
        Initializer\load_ajax_handlers_logic();
        Initializer\load_frontend_logic(); // This loads Frontend\Assets orchestrator
        Initializer\load_utils_logic();
        Initializer\load_sse_handler_logic();
    }

    /**
     * Register WordPress hooks conditionally.
     * Called by the main plugin class via Module_Initializer_Hooks_Registrar.
     */
    public static function register_hooks()
    {
        // self::load_dependencies(); // Dependencies should be loaded by AIPKit_Hook_Manager or earlier

        // Instantiate handlers needed for hook registration
        $admin_setup     = new AdminSetup();
        $ajax_processor  = class_exists(Core\AjaxProcessor::class) ? new Core\AjaxProcessor() : null;
        $sse_handler     = class_exists(SSEHandler::class) ? new SSEHandler() : null;
        $shortcode       = new Frontend\Shortcode();
        // --- MODIFIED: Frontend\Assets is now the orchestrator ---
        $assets          = new Frontend\Assets();
        // --- END MODIFICATION ---

        // NEW: Register log pruning cron hook
        if (class_exists(Storage\LogCronManager::class)) {
            add_action(Storage\LogCronManager::HOOK_NAME, ['WPAICG\Chat\Storage\LogCronManager', 'run_pruning']);
        }


        // Instantiate specific Admin AJAX Handlers
        if (!class_exists('\\WPAICG\\Chat\\Admin\\Ajax\\BaseAjaxHandler')) {
            return;
        }
        $chatbot_ajax_handler = new Ajax\ChatbotAjaxHandler();
        $log_ajax_handler = new Ajax\LogAjaxHandler();
        $conversation_ajax_handler = new Ajax\ConversationAjaxHandler();
        $chatbot_export_ajax_handler = new Ajax\ChatbotExportAjaxHandler();
        $chatbot_import_ajax_handler = new Ajax\ChatbotImportAjaxHandler();
        $chatbot_image_ajax_handler = null;
        if (class_exists(\WPAICG\Chat\Admin\Ajax\ChatbotImageAjaxHandler::class)) {
            $chatbot_image_ajax_handler = new Ajax\ChatbotImageAjaxHandler();
        }

        // Call externalized hook registration logic
        if (is_admin() || wp_doing_ajax()) {
            Initializer\register_hooks_admin_ajax_logic(
                $chatbot_ajax_handler,
                $log_ajax_handler,
                $conversation_ajax_handler,
                $chatbot_export_ajax_handler,
                $chatbot_import_ajax_handler
            );
            // General AJAX hooks (frontend messages, speech, etc.) that also need admin context
            Initializer\register_hooks_general_ajax_logic(
                $ajax_processor,
                $conversation_ajax_handler,
                $chatbot_image_ajax_handler
            );
            // SSE AJAX hooks
            Initializer\register_hooks_sse_ajax_logic($sse_handler);
        } else {
            // For frontend-only (non-AJAX) specific hook registration if any in the future.
            // Currently, core hooks cover CPT (init), shortcode (init), and frontend assets.
        }
        // Core hooks (CPT, Shortcode, Assets)
        Initializer\register_hooks_core_logic($admin_setup, $shortcode, $assets);
    }
}