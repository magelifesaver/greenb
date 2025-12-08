<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/chat/frontend/asset_registrars/class-chat-sidebar-asset-registrar.php
// Status: NEW FILE

namespace WPAICG\Chat\Frontend\AssetRegistrars;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Chat_Sidebar_Asset_Registrar {
    public static function register(string $version, string $public_chat_js_url, array $dependencies = []): array {
        $public_chat_sidebar_js_url = $public_chat_js_url . 'sidebar/';
        $sidebar_script_definitions = [
            'get-storage-key'           => ['aipkit-chat-sidebar-get-storage-key', $public_chat_sidebar_js_url . 'get-storage-key.js', []],
            'sync-footer-visibility'    => ['aipkit-chat-sidebar-sync-footer-visibility', $public_chat_sidebar_js_url . 'sync-footer-visibility.js', []],
            'manage-mobile-overlay'     => ['aipkit-chat-sidebar-manage-mobile-overlay', $public_chat_sidebar_js_url . 'manage-mobile-overlay.js', []],
            'apply-current-state'       => ['aipkit-chat-sidebar-apply-current-state', $public_chat_sidebar_js_url . 'apply-current-state.js', ['aipkit-chat-sidebar-sync-footer-visibility', 'aipkit-chat-sidebar-manage-mobile-overlay']],
            'toggle-sidebar'            => ['aipkit-chat-sidebar-toggle-sidebar', $public_chat_sidebar_js_url . 'toggle-sidebar.js', []],
            'handle-new-chat'           => ['aipkit-chat-sidebar-handle-new-chat', $public_chat_sidebar_js_url . 'handle-new-chat.js', []],
            'fetch-conversation-list'   => ['aipkit-chat-sidebar-fetch-conversation-list', $public_chat_sidebar_js_url . 'fetch-conversation-list.js', [$dependencies['api-frontend-request']]],
            'render-conversation-list'  => ['aipkit-chat-sidebar-render-conversation-list', $public_chat_sidebar_js_url . 'render-conversation-list.js', [$dependencies['api-frontend-request']]],
            'handle-delete'             => ['aipkit-chat-sidebar-handle-delete', $public_chat_sidebar_js_url . 'handle-delete.js', [$dependencies['api-frontend-request']]],
            'load-conversation'         => ['aipkit-chat-sidebar-load-conversation', $public_chat_sidebar_js_url . 'load-conversation.js', [$dependencies['api-frontend-request'], $dependencies['dom-append-message'], $dependencies['dom-scroll-to-bottom']]]
        ];
        
        $sidebar_handles = [];
        foreach ($sidebar_script_definitions as $key => $script_data) {
            list($handle, $path, $deps) = $script_data;
            if (!wp_script_is($handle, 'registered')) {
                wp_register_script($handle, $path, $deps, $version, true);
            }
            $sidebar_handles[$key] = $handle;
        }
        return $sidebar_handles;
    }
}