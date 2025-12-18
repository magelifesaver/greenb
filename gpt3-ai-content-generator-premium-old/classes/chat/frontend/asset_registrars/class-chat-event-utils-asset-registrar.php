<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/chat/frontend/asset_registrars/class-chat-event-utils-asset-registrar.php
// Status: MODIFIED

namespace WPAICG\Chat\Frontend\AssetRegistrars;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Chat_Event_Utils_Asset_Registrar
{
    public static function register(string $version, string $public_chat_js_url, array $dependencies = []): array
    {
        $public_chat_events_js_url = $public_chat_js_url . 'events/';
        $public_chat_attach_js_url = $public_chat_events_js_url . 'attach/'; // Path to new modularized listeners

        $event_handles = [];

        // --- Register individual listener attachment scripts ---
        $listener_scripts = [
            'action-button'           => ['aipkit-chat-event-attach-action-button', $public_chat_attach_js_url . 'action-button.js', []],
            'input-field'             => ['aipkit-chat-event-attach-input-field', $public_chat_attach_js_url . 'input-field.js', [$dependencies['auto-resize-textarea'] ?? null]],
            'fullscreen-button'       => ['aipkit-chat-event-attach-fullscreen-button', $public_chat_attach_js_url . 'fullscreen-button.js', []],
            'close-button'            => ['aipkit-chat-event-attach-close-button', $public_chat_attach_js_url . 'close-button.js', ['aipkit-chat-ui-popup-open','aipkit-chat-ui-popup-close']], // Depends on popup utils
            'web-search-toggle'       => ['aipkit-chat-event-attach-web-search-toggle', $public_chat_attach_js_url . 'web-search-toggle.js', ['aipkit-chat-event-close-download-menu', 'aipkit-chat-event-close-input-action-menu', 'aipkit-chat-util-toggle-web-search']],
            'google-grounding-toggle' => ['aipkit-chat-event-attach-google-grounding-toggle', $public_chat_attach_js_url . 'google-grounding-toggle.js', ['aipkit-chat-event-close-download-menu', 'aipkit-chat-event-close-input-action-menu', 'aipkit-chat-util-toggle-google-grounding']],
            'download-menu'           => ['aipkit-chat-event-attach-download-menu', $public_chat_attach_js_url . 'download-menu.js', ['aipkit-chat-event-close-input-action-menu']],
            'input-action-menu'       => ['aipkit-chat-event-attach-input-action-menu', $public_chat_attach_js_url . 'input-action-menu.js', ['aipkit-chat-event-close-input-action-menu']],
            'voice-input-button'      => ['aipkit-chat-event-attach-voice-input-button', $public_chat_attach_js_url . 'voice-input-button.js', ['aipkit-chat-stt-handle-voice-action']],
            'file-upload-input'       => ['aipkit-chat-event-attach-file-upload-input', $public_chat_attach_js_url . 'file-upload-input.js', [$dependencies['chat-file-upload-init'] ?? null]],
            'play-button'             => ['aipkit-chat-event-attach-play-button', $public_chat_attach_js_url . 'play-button.js', []], // Play button handler is in actions
        ];

        $listener_script_handles = [];
        foreach ($listener_scripts as $key => $script_data) {
            list($handle, $path, $deps) = $script_data;
            if (!wp_script_is($handle, 'registered')) {
                wp_register_script($handle, $path, array_filter($deps), $version, true);
            }
            $event_handles[$key] = $handle;
            $listener_script_handles[] = $handle; // Collect handles for the main orchestrator
        }
        // --- End Register individual listener attachment scripts ---

        // Original event utils (close menus, setup input action button toggle logic)
        // These are still needed by some of the new modular listeners or the orchestrator itself.
        $event_handles['close-download-menu'] = 'aipkit-chat-event-close-download-menu';
        wp_register_script($event_handles['close-download-menu'], $public_chat_events_js_url . 'close-download-menu.js', [], $version, true);

        $event_handles['close-input-action-menu'] = 'aipkit-chat-event-close-input-action-menu';
        wp_register_script($event_handles['close-input-action-menu'], $public_chat_events_js_url . 'close-input-action-menu.js', [], $version, true);

        $setup_input_action_btn_deps = array_filter([
            $event_handles['close-download-menu'],
            $event_handles['close-input-action-menu'],
            $dependencies['chat-image-upload-init'] ?? null,
            $dependencies['chat-file-upload-init'] ?? null // Added file upload dep
        ]);
        $event_handles['setup-input-action-button'] = 'aipkit-chat-event-setup-input-action-button';
        wp_register_script($event_handles['setup-input-action-button'], $public_chat_events_js_url . 'setup-input-action-button.js', $setup_input_action_btn_deps, $version, true);


        // Main orchestrator for attaching event listeners
        $event_handles['attach-event-listeners'] = 'aipkit-chat-event-attach-event-listeners';
        $attach_listeners_dependencies = array_merge(
            [$event_handles['close-download-menu'], $event_handles['close-input-action-menu'], $event_handles['setup-input-action-button']],
            $listener_script_handles // Add all individual listener scripts as dependencies
        );
        wp_register_script($event_handles['attach-event-listeners'], $public_chat_events_js_url . 'attach-event-listeners.js', array_filter(array_unique($attach_listeners_dependencies)), $version, true);

        return $event_handles;
    }
}
