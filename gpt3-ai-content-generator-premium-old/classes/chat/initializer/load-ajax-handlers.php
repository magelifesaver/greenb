<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/chat/initializer/load-ajax-handlers.php
// Status: NEW FILE

namespace WPAICG\Chat\Initializer;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for loading Chat AJAX Handler dependencies.
 * Called by WPAICG\Chat\Initializer::load_dependencies().
 */
function load_ajax_handlers_logic(): void {
    $base_path = WPAICG_PLUGIN_DIR . 'classes/chat/';
    $ajax_handlers_paths = [
        'admin/ajax/chatbot_ajax_handler.php',
        'admin/ajax/log_ajax_handler.php',
        'admin/ajax/conversation_ajax_handler.php',
        'admin/ajax/chatbot_export_ajax_handler.php',
        'admin/ajax/chatbot_import_ajax_handler.php',
        'admin/ajax/class-aipkit-chatbot-image-ajax-handler.php',
    ];

    // BaseAjaxHandler should be loaded by Base_Ajax_Handlers_Loader, not here directly.
    // But if it's a specific dependency for these handlers, ensure it's noted or handled.

    foreach ($ajax_handlers_paths as $handler_path_relative) {
        $full_path = $base_path . $handler_path_relative;
        $class_name_base = basename($handler_path_relative, '.php');
        // Basic class name derivation (might need adjustment for prefixed classes)
        $class_name_parts = explode('-', str_replace('class-aipkit-', '', $class_name_base));
        $class_name = '\\WPAICG\\Chat\\Admin\\Ajax\\';
        foreach ($class_name_parts as $part) {
            $class_name .= ucfirst($part);
        }
        // Specific class name for ChatbotImageAjaxHandler
        if ($handler_path_relative === 'admin/ajax/class-aipkit-chatbot-image-ajax-handler.php') {
            $class_name = '\\WPAICG\\Chat\\Admin\\Ajax\\ChatbotImageAjaxHandler';
        } elseif ($handler_path_relative === 'admin/ajax/chatbot_ajax_handler.php') {
             $class_name = '\\WPAICG\\Chat\\Admin\\Ajax\\ChatbotAjaxHandler';
        } elseif ($handler_path_relative === 'admin/ajax/log_ajax_handler.php') {
             $class_name = '\\WPAICG\\Chat\\Admin\\Ajax\\LogAjaxHandler';
        } elseif ($handler_path_relative === 'admin/ajax/conversation_ajax_handler.php') {
             $class_name = '\\WPAICG\\Chat\\Admin\\Ajax\\ConversationAjaxHandler';
        } elseif ($handler_path_relative === 'admin/ajax/chatbot_export_ajax_handler.php') {
             $class_name = '\\WPAICG\\Chat\\Admin\\Ajax\\ChatbotExportAjaxHandler';
        } elseif ($handler_path_relative === 'admin/ajax/chatbot_import_ajax_handler.php') {
             $class_name = '\\WPAICG\\Chat\\Admin\\Ajax\\ChatbotImportAjaxHandler';
        }


        if (file_exists($full_path) && !class_exists($class_name)) {
            require_once $full_path;
        }
    }
}