<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/chat/frontend/asset_registrars/class-chat-feature-handler-registrar.php
// Status: MODIFIED

namespace WPAICG\Chat\Frontend\AssetRegistrars;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Chat_Feature_Handler_Registrar
{
    public static function register(string $version, string $public_chat_js_url, array $dependencies = [], string $plugin_base_url = '', string $plugin_dir = ''): array
    {
        $public_chat_message_actions_js_url = $public_chat_js_url . 'message-actions/';
        $public_chat_popup_js_url = $public_chat_js_url . 'popup/';
        $public_chat_tts_js_url = $public_chat_js_url . 'tts/';
        $public_chat_stt_js_url = $public_chat_js_url . 'stt/';

        $lib_js_chat_consent_url = $plugin_base_url . 'lib/js/chat/consent/';
        $lib_js_chat_consent_dir = $plugin_dir . 'lib/js/chat/consent/';

        $final_registered_handles = []; // Store only successfully registered handles

        $base_handles_map = [ // Map keys to actual handle names
            'ajax' => 'aipkit-chat-ui-ajax',
            'stream' => 'aipkit-chat-stream-message',
            'fullscreen' => 'aipkit-chat-ui-fullscreen',
            'message-actions-init' => 'aipkit-chat-init-message-actions',
            'starters' => 'aipkit-chat-ui-starters', 'sidebar' => 'aipkit-chat-ui-sidebar',
            'tts-state-init' => 'aipkit-chat-tts-state-init',
            'tts-stop-audio' => 'aipkit-chat-tts-stop-audio',
            'tts-handlers' => 'aipkit-chat-tts-handlers',
            'tts-play-audio-from-base64' => 'aipkit-chat-tts-play-audio-from-base64',
            'tts-play-audio' => 'aipkit-chat-action-tts-play',
            'stt-ui' => 'aipkit-chat-stt-handle-voice-action',
            'image-generation' => 'aipkit-chat-ui-image-generation',
            'consent-show' => 'aipkit-chat-consent-show-box',
            'consent-hide' => 'aipkit-chat-consent-hide-box',
            'consent-agree' => 'aipkit-chat-consent-handle-agree',
            'consent' => 'aipkit-chat-ui-consent-init',
            'moderation' => 'aipkit-chat-ui-moderation',
            // Message action sub-components
            'extract-text-from-bubble' => 'aipkit-chat-extract-text-from-bubble',
            'show-success-icon'       => 'aipkit-chat-show-success-icon',
            'handle-copy-action'      => 'aipkit-chat-handle-copy-action',
            'handle-feedback-action'  => 'aipkit-chat-handle-feedback-action',
            'create-actions-html'     => 'aipkit-chat-create-actions-html',
            // Popup sub-components
            'popup-open' => 'aipkit-chat-ui-popup-open',
            'popup-close' => 'aipkit-chat-ui-popup-close',
            'popup-setup-handlers' => 'aipkit-chat-ui-popup-setup-handlers',
        ];
        // Ensure 'tts' maps to the correct play audio handle
        $base_handles_map['tts'] = $base_handles_map['tts-play-audio'];


        $dep_api_frontend = $dependencies['api-frontend-request'] ?? null;
        $dep_html_escaper = $dependencies['html-escaper'] ?? null;
        $dep_stt_public = $dependencies['stt-public'] ?? null;
        $dep_dom_show_typing = $dependencies['dom-show-typing-indicator'] ?? null;
        $dep_dom_remove_typing = $dependencies['dom-remove-typing-indicator'] ?? null;
        $dep_dom_append_update = $dependencies['dom-append-or-update-message'] ?? null;
        $dep_dom_scroll = $dependencies['dom-scroll-to-bottom'] ?? null;
        $dep_dom_show_img_loader = $dependencies['dom-show-image-loader'] ?? null;
        $dep_dom_remove_img_loader = $dependencies['dom-remove-image-loader'] ?? null;
        $dep_dom_append_msg = $dependencies['dom-append-message'] ?? null;
        $dep_action_clear_chat = $dependencies['action-clear-chat'] ?? null;
        $dep_sidebar_components = is_array($dependencies['sidebar-components'] ?? null) ? $dependencies['sidebar-components'] : [];

        // Scripts that are always available (not in /lib/)
        $always_available_scripts = [
            // Key in $base_handles_map => [ path_relative_to_public_chat_js_url, [dependency_keys_from_base_handles_map_or_dependencies_array] ]
            'ajax'                     => ['chat-ui-ajax.js', [$dep_dom_show_typing, $dep_dom_remove_typing, $dep_api_frontend]],
            'tts-state-init'           => ['tts/state-init.js', []],
            'tts-stop-audio'           => ['tts/stop-audio.js', ['tts-state-init']],
            'tts-handlers'             => ['tts/tts-handlers.js', ['tts-state-init', 'tts-stop-audio']],
            'tts-play-audio-from-base64' => ['tts/play-audio-from-base64.js', ['tts-state-init', 'tts-stop-audio', 'tts-handlers']],
            'tts-play-audio'           => ['tts/play-audio.js', ['tts-state-init', 'tts-stop-audio', 'tts-play-audio-from-base64', $dep_api_frontend]],
            'popup-open'               => ['popup/open-popup.js', [$dep_dom_scroll]],
            'popup-close'              => ['popup/close-popup.js', []],
            'popup-setup-handlers'     => ['popup/setup-popup-handlers.js', ['popup-open', 'popup-close']],
            'fullscreen'               => ['chat-ui-fullscreen.js', [$dep_dom_scroll]],
            'extract-text-from-bubble' => ['message-actions/extract-text-from-bubble.js', []],
            'show-success-icon'        => ['message-actions/show-success-icon.js', []],
            'handle-copy-action'       => ['message-actions/handle-copy-action.js', ['extract-text-from-bubble', 'show-success-icon']],
            'handle-feedback-action'   => ['message-actions/handle-feedback-action.js', [$dep_api_frontend]],
            'create-actions-html'      => ['message-actions/create-actions-html.js', []],
            'message-actions-init'     => ['message-actions/init-message-actions.js', ['handle-copy-action', 'handle-feedback-action']],
            'starters'                 => ['chat-ui-starters.js', [$dep_api_frontend]],
            'sidebar'                  => ['chat-ui-sidebar.js', array_unique(array_filter(array_merge([$dep_api_frontend, $dep_action_clear_chat], $dep_sidebar_components)))],
            'moderation'               => ['chat-ui-moderation.js', []],
            'image-generation'         => ['chat-ui-image-generation.js', [$dep_api_frontend, $dep_dom_show_img_loader, $dep_dom_remove_img_loader, $dep_dom_append_msg]],
            'stt-ui'                   => ['stt/handle-voice-input-action.js', array_filter([$dep_stt_public, 'aipkit-chat-stt-transcribe-audio'])],
        ];

        foreach ($always_available_scripts as $key => $script_info) {
            list($path, $deps_keys) = $script_info;
            $handle = $base_handles_map[$key] ?? null;
            if ($handle && !wp_script_is($handle, 'registered')) {
                $actual_deps = array_map(function ($dep_key) use ($base_handles_map) {
                    return $base_handles_map[$dep_key] ?? $dep_key; // Resolve keys to actual handles
                }, $deps_keys);
                wp_register_script($handle, $public_chat_js_url . $path, array_filter($actual_deps), $version, true);
                $final_registered_handles[$key] = $handle;
            }
        }

        // Consent Scripts (from /lib/)
        $consent_scripts_to_register = [
            'consent-show'  => 'show-consent-box.js',
            'consent-hide'  => 'hide-consent-box.js',
            'consent-agree' => 'handle-consent-agree.js',
            'consent'       => 'init-consent-ui.js',
        ];
        $consent_script_dependencies = [ // Dependencies defined by their *keys* in $base_handles_map
            'consent-show' => [],
            'consent-hide' => [],
            'consent-agree' => ['consent-hide'],
            'consent' => ['consent-show', 'consent-hide', 'consent-agree'],
        ];

        $registered_consent_handles_for_main_init = [];

        foreach ($consent_scripts_to_register as $key => $filename) {
            $file_full_path = $lib_js_chat_consent_dir . $filename;
            $file_url = $lib_js_chat_consent_url . $filename;
            $current_handle = $base_handles_map[$key] ?? null;
            $current_script_dep_keys = $consent_script_dependencies[$key] ?? [];

            if (file_exists($file_full_path) && $current_handle && !wp_script_is($current_handle, 'registered')) {
                $actual_deps_for_this_script = [];
                foreach ($current_script_dep_keys as $dep_key) {
                    // Check if this dependency (which must be one of the consent sub-scripts)
                    // was itself successfully registered and its handle is in $final_registered_handles.
                    if (isset($base_handles_map[$dep_key]) && isset($final_registered_handles[$dep_key])) {
                        $actual_deps_for_this_script[] = $final_registered_handles[$dep_key];
                    }
                }
                wp_register_script($current_handle, $file_url, array_filter($actual_deps_for_this_script), $version, true);
                $final_registered_handles[$key] = $current_handle; // Add successfully registered handle
                if (in_array($key, ['consent-show', 'consent-hide', 'consent-agree'])) {
                    $registered_consent_handles_for_main_init[] = $current_handle;
                }
            }
        }
        // Specifically register the main 'consent' init script ('aipkit-chat-ui-consent-init')
        // only if ALL its direct dependencies were registered.
        $main_consent_init_handle = $base_handles_map['consent'] ?? null;
        if ($main_consent_init_handle && !wp_script_is($main_consent_init_handle, 'registered')) { // Check if not already processed (e.g. file_exists was true above)
            $main_consent_init_file_path = $lib_js_chat_consent_dir . 'init-consent-ui.js';
            if (file_exists($main_consent_init_file_path)) {
                // Ensure direct dependencies were registered
                $direct_deps_keys_for_main_consent = $consent_script_dependencies['consent'] ?? [];
                $all_direct_deps_are_registered = true;
                $actual_main_consent_deps = [];
                foreach ($direct_deps_keys_for_main_consent as $dep_key) {
                    if (isset($final_registered_handles[$dep_key])) {
                        $actual_main_consent_deps[] = $final_registered_handles[$dep_key];
                    } else {
                        $all_direct_deps_are_registered = false;
                        break;
                    }
                }
                if ($all_direct_deps_are_registered) {
                    wp_register_script($main_consent_init_handle, $lib_js_chat_consent_url . 'init-consent-ui.js', array_filter(array_unique($actual_main_consent_deps)), $version, true);
                    $final_registered_handles['consent'] = $main_consent_init_handle;
                }
            }
        }

        return $final_registered_handles; // Return only successfully registered handles
    }
}
