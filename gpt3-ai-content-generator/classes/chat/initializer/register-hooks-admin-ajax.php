<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/chat/initializer/register-hooks-admin-ajax.php
// Status: MODIFIED

namespace WPAICG\Chat\Initializer;

use WPAICG\Chat\Admin\Ajax; // Namespace for AJAX Handlers

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for registering admin-specific AJAX hooks for the Chat module.
 * Called by WPAICG\Chat\Initializer::register_hooks().
 *
 * @param Ajax\ChatbotAjaxHandler $chatbot_ajax_handler
 * @param Ajax\LogAjaxHandler $log_ajax_handler
 * @param Ajax\ConversationAjaxHandler $conversation_ajax_handler
 * @param Ajax\ChatbotExportAjaxHandler $chatbot_export_ajax_handler
 * @param Ajax\ChatbotImportAjaxHandler $chatbot_import_ajax_handler
 * @return void
 */
function register_hooks_admin_ajax_logic(
    Ajax\ChatbotAjaxHandler $chatbot_ajax_handler,
    Ajax\LogAjaxHandler $log_ajax_handler,
    Ajax\ConversationAjaxHandler $conversation_ajax_handler,
    Ajax\ChatbotExportAjaxHandler $chatbot_export_ajax_handler,
    Ajax\ChatbotImportAjaxHandler $chatbot_import_ajax_handler
): void {
    add_action('wp_ajax_aipkit_create_chatbot', [$chatbot_ajax_handler, 'ajax_create_chatbot']);
    add_action('wp_ajax_aipkit_save_chatbot_settings', [$chatbot_ajax_handler, 'ajax_save_chatbot_settings']);
    add_action('wp_ajax_aipkit_delete_chatbot', [$chatbot_ajax_handler, 'ajax_delete_chatbot']);
    add_action('wp_ajax_aipkit_get_chatbot_shortcode', [$chatbot_ajax_handler, 'ajax_get_chatbot_shortcode']);
    add_action('wp_ajax_aipkit_reset_chatbot_settings', [$chatbot_ajax_handler, 'ajax_reset_chatbot_settings']);
    add_action('wp_ajax_aipkit_rename_chatbot', [$chatbot_ajax_handler, 'ajax_rename_chatbot']);

    add_action('wp_ajax_aipkit_get_chat_logs_html', [$log_ajax_handler, 'ajax_get_chat_logs_html']);
    add_action('wp_ajax_aipkit_export_chat_logs', [$log_ajax_handler, 'ajax_export_chat_logs']);
    add_action('wp_ajax_aipkit_delete_chat_logs', [$log_ajax_handler, 'ajax_delete_chat_logs']);
    add_action('wp_ajax_aipkit_save_log_settings', [$log_ajax_handler, 'ajax_save_log_settings']);
    add_action('wp_ajax_aipkit_prune_logs_now', [$log_ajax_handler, 'ajax_prune_logs_now']); // NEW
    add_action('wp_ajax_aipkit_get_log_cron_status', [$log_ajax_handler, 'ajax_get_log_cron_status']); // NEW
    add_action('wp_ajax_aipkit_toggle_ip_block_status', [$log_ajax_handler, 'ajax_toggle_ip_block_status']); // NEW

    add_action('wp_ajax_aipkit_admin_get_conversation_history', [$conversation_ajax_handler, 'ajax_admin_get_conversation_history']);

    add_action('wp_ajax_aipkit_export_all_chatbots', [$chatbot_export_ajax_handler, 'ajax_export_all_chatbots']);
    add_action('wp_ajax_aipkit_import_chatbots', [$chatbot_import_ajax_handler, 'ajax_import_chatbots']);
}