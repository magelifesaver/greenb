<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/chat/frontend/asset_registrars/class-chat-dom-utils-asset-registrar.php
// Status: MODIFIED

namespace WPAICG\Chat\Frontend\AssetRegistrars;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Chat_Dom_Utils_Asset_Registrar {
    public static function register(string $version, string $public_chat_js_url, array $dependencies = []): array {
        $public_chat_dom_js_url = $public_chat_js_url . 'dom/';
        $dom_handles = [
            'find-elements' => 'aipkit-chat-dom-find-elements',
            'scroll-to-bottom' => 'aipkit-chat-dom-scroll-to-bottom',
            'remove-typing-indicator' => 'aipkit-chat-dom-remove-typing-indicator',
            'remove-image-loader' => 'aipkit-chat-dom-remove-image-loader',
            'show-typing-indicator' => 'aipkit-chat-dom-show-typing-indicator',
            'show-image-loader' => 'aipkit-chat-dom-show-image-loader',
            'append-message' => 'aipkit-chat-dom-append-message',
            'position-stream-indicator' => 'aipkit-chat-dom-position-stream-indicator',
            'append-or-update-message' => 'aipkit-chat-dom-append-or-update-message',
            'render-chat-form' => 'aipkit-chat-dom-render-chat-form', // NEW
        ];

        wp_register_script($dom_handles['find-elements'], $public_chat_dom_js_url . 'find-elements.js', [], $version, true);
        wp_register_script($dom_handles['scroll-to-bottom'], $public_chat_dom_js_url . 'scroll-to-bottom.js', [], $version, true);
        wp_register_script($dom_handles['remove-typing-indicator'], $public_chat_dom_js_url . 'remove-typing-indicator.js', [], $version, true);
        wp_register_script($dom_handles['remove-image-loader'], $public_chat_dom_js_url . 'remove-image-loader.js', [], $version, true);
        wp_register_script($dom_handles['show-typing-indicator'], $public_chat_dom_js_url . 'show-typing-indicator.js', [$dom_handles['remove-typing-indicator'], $dom_handles['remove-image-loader'], $dom_handles['scroll-to-bottom']], $version, true);
        wp_register_script($dom_handles['show-image-loader'], $public_chat_dom_js_url . 'show-image-loader.js', [$dom_handles['remove-typing-indicator'], $dom_handles['remove-image-loader'], $dom_handles['scroll-to-bottom']], $version, true);
        wp_register_script($dom_handles['append-message'], $public_chat_dom_js_url . 'append-message.js', array_filter([$dependencies['html-escaper'], $dom_handles['scroll-to-bottom'], 'aipkit-chat-create-actions-html']), $version, true);
        wp_register_script($dom_handles['position-stream-indicator'], $public_chat_dom_js_url . 'position-stream-indicator.js', [], $version, true);
        wp_register_script($dom_handles['append-or-update-message'], $public_chat_dom_js_url . 'append-or-update-message.js', array_filter(['aipkit_markdown-it', $dom_handles['position-stream-indicator'], 'aipkit-chat-create-actions-html', $dom_handles['scroll-to-bottom']]), $version, true);
        // --- NEW: Register render-chat-form.js ---
        wp_register_script($dom_handles['render-chat-form'], $public_chat_dom_js_url . 'render-chat-form.js', array_filter([$dependencies['html-escaper']]), $version, true);
        // --- END NEW ---

        return $dom_handles;
    }
}