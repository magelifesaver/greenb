<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/chat/frontend/asset_registrars/class-chat-stream-asset-registrar.php
// Status: MODIFIED

namespace WPAICG\Chat\Frontend\AssetRegistrars;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Chat_Stream_Asset_Registrar {
    public static function register(string $version, string $public_chat_js_url, array $core_dependencies): array {
        $public_chat_stream_js_url = $public_chat_js_url . 'stream/';
        $handles = [];

        $script_definitions = [
            'cache-sse-message'              => ['aipkit-chat-stream-cache-message', $public_chat_stream_js_url . 'cache-sse-message.js', []],
            'create-event-source'            => ['aipkit-chat-stream-create-event-source', $public_chat_stream_js_url . 'create-event-source.js', []],
            'handle-message-start-event'     => ['aipkit-chat-stream-handle-message-start', $public_chat_stream_js_url . 'handle-message-start-event.js', []],
            'handle-openai-response-id-event'=> ['aipkit-chat-stream-handle-openai-id', $public_chat_stream_js_url . 'handle-openai-response-id-event.js', []],
            'handle-grounding-metadata-event'=> ['aipkit-chat-stream-handle-grounding-metadata', $public_chat_stream_js_url . 'handle-grounding-metadata-event.js', []],
            'handle-onmessage-event'         => ['aipkit-chat-stream-handle-onmessage', $public_chat_stream_js_url . 'handle-onmessage-event.js', [$core_dependencies['dom-remove-typing-indicator'], $core_dependencies['dom-append-or-update-message'], $core_dependencies['dom-scroll-to-bottom']]],
            'handle-done-event'              => ['aipkit-chat-stream-handle-done', $public_chat_stream_js_url . 'handle-done-event.js', [$core_dependencies['dom-append-or-update-message'], $core_dependencies['dom-scroll-to-bottom']]],
            'handle-warning-event'           => ['aipkit-chat-stream-handle-warning', $public_chat_stream_js_url . 'handle-warning-event.js', [$core_dependencies['dom-append-or-update-message'], $core_dependencies['dom-scroll-to-bottom']]],
            'handle-error-event'             => ['aipkit-chat-stream-handle-error', $public_chat_stream_js_url . 'handle-error-event.js', [$core_dependencies['dom-remove-typing-indicator']]],
            // --- NEW: Register handle-sse-event-display-form.js ---
            'handle-display-form-event'      => ['aipkit-chat-stream-handle-display-form', $public_chat_stream_js_url . 'handle-sse-event-display-form.js', [$core_dependencies['dom-render-chat-form'] ?? null, $core_dependencies['dom-scroll-to-bottom'] ?? null]],
            // --- END NEW ---
        ];

        foreach ($script_definitions as $key => $script_data) {
            list($handle, $path, $deps) = $script_data;
            if (!wp_script_is($handle, 'registered')) {
                wp_register_script($handle, $path, array_filter($deps), $version, true);
            }
            $handles[$key] = $handle;
        }
        
        $stream_message_orchestrator_handle = 'aipkit-chat-stream-message';
        $stream_message_deps = array_values($handles); // All the above are dependencies
        if (!wp_script_is($stream_message_orchestrator_handle, 'registered')) {
            wp_register_script($stream_message_orchestrator_handle, $public_chat_stream_js_url . 'stream-message.js', $stream_message_deps, $version, true);
        }
        $handles['stream-message'] = $stream_message_orchestrator_handle;

        return $handles;
    }
}