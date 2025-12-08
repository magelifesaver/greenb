<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/chat/frontend/asset_registrars/class-chat-action-scripts-registrar.php

namespace WPAICG\Chat\Frontend\AssetRegistrars;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Chat_Action_Scripts_Registrar {
    public static function register(string $version, string $public_chat_js_url, array $dependencies = []): array {
        $public_chat_actions_js_url = $public_chat_js_url . 'actions/';
        $public_chat_clear_js_url = $public_chat_js_url . 'clear/';
        $action_handles = [
            // Clear Actions
            'clear-messages' => 'aipkit-chat-clear-messages',
            'clear-chat' => 'aipkit-chat-ui-clear',
            // General Actions
            'focus-input' => 'aipkit-chat-action-focus-input',
            'handle-completion' => 'aipkit-chat-action-handle-completion',
            'handle-error' => 'aipkit-chat-action-handle-error',
            'send-message' => 'aipkit-chat-action-send-message',
            'set-button-state' => 'aipkit-chat-action-set-button-state',
        ];

        // Ensure all expected keys are present in $dependencies, provide fallbacks if necessary or log error
        $dep_scroll_to_bottom = $dependencies['dom-scroll-to-bottom'] ?? null;
        $dep_remove_typing_indicator = $dependencies['dom-remove-typing-indicator'] ?? null;
        $dep_append_message = $dependencies['dom-append-message'] ?? null;
        $dep_util_auto_resize = $dependencies['auto-resize-textarea'] ?? null;
        $dep_util_gen_id = $dependencies['generate-client-message-id'] ?? null;
        $dep_chat_image_upload_init = $dependencies['chat-image-upload-init'] ?? null;
        $dep_api_frontend_request = $dependencies['api-frontend-request'] ?? null;
        $dep_show_typing_indicator = $dependencies['dom-show-typing-indicator'] ?? null;
        $dep_show_image_loader = $dependencies['dom-show-image-loader'] ?? null;
        $dep_feature_tts = $dependencies['feature-tts'] ?? null;
        $dep_feature_image_generation = $dependencies['feature-image-generation'] ?? null;
        $dep_feature_moderation = $dependencies['feature-moderation'] ?? null;
        $dep_feature_stream = $dependencies['feature-stream'] ?? null;


        wp_register_script($action_handles['clear-messages'], $public_chat_clear_js_url . 'clear-messages.js', array_filter([$dep_scroll_to_bottom]), $version, true);
        wp_register_script($action_handles['clear-chat'], $public_chat_clear_js_url . 'clear-chat-action.js', array_filter([
            $action_handles['clear-messages'], $dep_remove_typing_indicator, $dep_append_message,
            // --- MODIFIED: Use new util dependencies ---
            $dep_util_auto_resize, $dep_util_gen_id,
            // --- END MODIFICATION ---
            $dep_chat_image_upload_init,
        ]), $version, true);

        wp_register_script($action_handles['focus-input'], $public_chat_actions_js_url . 'focus-input.js', [], $version, true);
        wp_register_script($action_handles['set-button-state'], $public_chat_actions_js_url . 'set-button-state.js', [], $version, true);
        wp_register_script($action_handles['handle-completion'], $public_chat_actions_js_url . 'handle-completion.js', array_filter([$action_handles['set-button-state'], $action_handles['focus-input'], $dep_feature_tts, $dep_chat_image_upload_init, $dep_append_message]), $version, true);
        wp_register_script($action_handles['handle-error'], $public_chat_actions_js_url . 'handle-error.js', array_filter([$dep_append_message, $dep_remove_typing_indicator, $action_handles['set-button-state'], $action_handles['focus-input'], $dep_util_gen_id]), $version, true);
        
        $send_message_deps = array_filter([
            $dep_api_frontend_request, 'aipkit-chat-ui-ajax', $dep_feature_stream, 
            $dep_append_message, $dep_show_typing_indicator, $dep_show_image_loader, 
            $action_handles['set-button-state'], $action_handles['handle-error'], 
            $dep_feature_image_generation, $dep_feature_moderation,
            // --- MODIFIED: Use new util dependencies ---
            $dep_util_auto_resize, $dep_util_gen_id,
            // --- END MODIFICATION ---
            $dep_chat_image_upload_init
        ]);
        wp_register_script($action_handles['send-message'], $public_chat_actions_js_url . 'send-message-action.js', $send_message_deps, $version, true);
        
        return $action_handles;
    }
}