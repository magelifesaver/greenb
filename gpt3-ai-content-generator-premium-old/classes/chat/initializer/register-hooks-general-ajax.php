<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/chat/initializer/register-hooks-general-ajax.php
// Status: NEW FILE

namespace WPAICG\Chat\Initializer;

use WPAICG\Chat\Core\AjaxProcessor;
use WPAICG\Chat\Admin\Ajax\ConversationAjaxHandler;
use WPAICG\Chat\Admin\Ajax\ChatbotImageAjaxHandler; // Assuming this is the correct handler for chat_generate_image
use WPAICG\Chat\Frontend\Ajax as FrontendAjax; // For nonce refresh logic

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for registering general AJAX hooks for the Chat module (frontend and admin).
 * Called by WPAICG\Chat\Initializer::register_hooks().
 *
 * @param AjaxProcessor|null $ajax_processor
 * @param ConversationAjaxHandler $conversation_ajax_handler
 * @param ChatbotImageAjaxHandler|null $chatbot_image_ajax_handler
 * @return void
 */
function register_hooks_general_ajax_logic(
    ?AjaxProcessor $ajax_processor,
    ConversationAjaxHandler $conversation_ajax_handler,
    ?ChatbotImageAjaxHandler $chatbot_image_ajax_handler
): void {
    // Ensure the nonce refresh function is available
    $nonce_refresh_path = WPAICG_PLUGIN_DIR . 'classes/chat/frontend/ajax/fn-ajax-get-frontend-chat-nonce.php';
    if (file_exists($nonce_refresh_path) && !function_exists('WPAICG\\Chat\\Frontend\\Ajax\\ajax_get_frontend_chat_nonce_logic')) {
        require_once $nonce_refresh_path;
    }
    // Hooks handled by ConversationAjaxHandler (potentially frontend & admin)
    add_action('wp_ajax_aipkit_get_conversations_list', [$conversation_ajax_handler, 'ajax_get_conversations_list']);
    add_action('wp_ajax_nopriv_aipkit_get_conversations_list', [$conversation_ajax_handler, 'ajax_get_conversations_list']);
    add_action('wp_ajax_aipkit_get_conversation_history', [$conversation_ajax_handler, 'ajax_get_conversation_history']);
    add_action('wp_ajax_nopriv_aipkit_get_conversation_history', [$conversation_ajax_handler, 'ajax_get_conversation_history']);
    add_action('wp_ajax_aipkit_store_feedback', [$conversation_ajax_handler, 'ajax_store_feedback']);
    add_action('wp_ajax_nopriv_aipkit_store_feedback', [$conversation_ajax_handler, 'ajax_store_feedback']);
    add_action('wp_ajax_aipkit_generate_speech', [$conversation_ajax_handler, 'ajax_generate_speech']);
    add_action('wp_ajax_nopriv_aipkit_generate_speech', [$conversation_ajax_handler, 'ajax_generate_speech']);
    add_action('wp_ajax_aipkit_delete_single_conversation', [$conversation_ajax_handler, 'ajax_delete_single_conversation']);
    add_action('wp_ajax_nopriv_aipkit_delete_single_conversation', [$conversation_ajax_handler, 'ajax_delete_single_conversation']);

    // Hooks handled by AjaxProcessor (primarily frontend non-streaming messages)
    if ($ajax_processor) {
        add_action('wp_ajax_aipkit_frontend_chat_message', [$ajax_processor, 'ajax_frontend_chat_message']);
        add_action('wp_ajax_nopriv_aipkit_frontend_chat_message', [$ajax_processor, 'ajax_frontend_chat_message']);
    }

    // Hooks for image generation within chat
    if ($chatbot_image_ajax_handler) {
         add_action('wp_ajax_aipkit_chat_generate_image', [$chatbot_image_ajax_handler, 'ajax_chat_generate_image']);
         add_action('wp_ajax_nopriv_aipkit_chat_generate_image', [$chatbot_image_ajax_handler, 'ajax_chat_generate_image']);
    }

    // Frontend utility: Get fresh nonce for chat actions (anonymous allowed)
    if (function_exists('WPAICG\\Chat\\Frontend\\Ajax\\ajax_get_frontend_chat_nonce_logic')) {
        add_action('wp_ajax_aipkit_get_frontend_chat_nonce', 'WPAICG\\Chat\\Frontend\\Ajax\\ajax_get_frontend_chat_nonce_logic');
        add_action('wp_ajax_nopriv_aipkit_get_frontend_chat_nonce', 'WPAICG\\Chat\\Frontend\\Ajax\\ajax_get_frontend_chat_nonce_logic');
    }
}
