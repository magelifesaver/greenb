<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/chat/frontend/asset_registrars/class-chat-stt-asset-registrar.php

namespace WPAICG\Chat\Frontend\AssetRegistrars;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Chat_Stt_Asset_Registrar {
    public static function register(string $version, string $public_js_url): string {
        $public_chat_stt_js_url = $public_js_url . 'chat/stt/'; // Base path for new STT files

        $stt_handles = [
            'state'                         => 'aipkit-stt-state',
            'is-media-recorder-supported'   => 'aipkit-stt-is-media-recorder-supported',
            'set-recording-callbacks'       => 'aipkit-stt-set-recording-callbacks',
            'start-recording'               => 'aipkit-stt-start-recording',
            'stop-recording'                => 'aipkit-stt-stop-recording',
            // New UI related STT functions moved from chat-ui-stt.js
            'transcribe-audio'              => 'aipkit-chat-stt-transcribe-audio',
            'handle-voice-action'           => 'aipkit-chat-stt-handle-voice-action',
            // Main init handle
            'init'                          => 'aipkit-public-stt',
        ];

        // Register core STT components (no changes here)
        if (!wp_script_is($stt_handles['state'], 'registered')) {
            wp_register_script($stt_handles['state'], $public_chat_stt_js_url . 'stt-state.js', [], $version, true);
        }
        if (!wp_script_is($stt_handles['is-media-recorder-supported'], 'registered')) {
            wp_register_script($stt_handles['is-media-recorder-supported'], $public_chat_stt_js_url . 'is-media-recorder-supported.js', [], $version, true);
        }
        if (!wp_script_is($stt_handles['set-recording-callbacks'], 'registered')) {
            wp_register_script($stt_handles['set-recording-callbacks'], $public_chat_stt_js_url . 'set-recording-callbacks.js', [$stt_handles['state']], $version, true);
        }
        if (!wp_script_is($stt_handles['start-recording'], 'registered')) {
            wp_register_script($stt_handles['start-recording'], $public_chat_stt_js_url . 'start-recording.js', [$stt_handles['state'], $stt_handles['is-media-recorder-supported']], $version, true);
        }
        if (!wp_script_is($stt_handles['stop-recording'], 'registered')) {
            wp_register_script($stt_handles['stop-recording'], $public_chat_stt_js_url . 'stop-recording.js', [$stt_handles['state']], $version, true);
        }

        // Register new UI-level STT functions
        $core_stt_dependencies = [
            $stt_handles['state'], $stt_handles['is-media-recorder-supported'],
            $stt_handles['set-recording-callbacks'], $stt_handles['start-recording'],
            $stt_handles['stop-recording']
        ];

        if (!wp_script_is($stt_handles['transcribe-audio'], 'registered')) {
            wp_register_script(
                $stt_handles['transcribe-audio'],
                $public_chat_stt_js_url . 'transcribe-audio.js',
                // --- MODIFIED: Update dependencies ---
                ['aipkit-api-frontend-request', 'aipkit-chat-util-auto-resize'], 
                // --- END MODIFICATION ---
                $version,
                true
            );
        }

        if (!wp_script_is($stt_handles['handle-voice-action'], 'registered')) {
            wp_register_script(
                $stt_handles['handle-voice-action'],
                $public_chat_stt_js_url . 'handle-voice-input-action.js',
                array_merge($core_stt_dependencies, [$stt_handles['transcribe-audio']]),
                $version,
                true
            );
        }

        // Register the main init/orchestrator script, making it depend on ALL STT components
        $init_dependencies = array_merge(
            $core_stt_dependencies,
            [$stt_handles['transcribe-audio'], $stt_handles['handle-voice-action']]
        );
        if (!wp_script_is($stt_handles['init'], 'registered')) {
            wp_register_script($stt_handles['init'], $public_chat_stt_js_url . 'stt-init.js', array_unique($init_dependencies), $version, true);
        }

        return $stt_handles['init']; // Return the main handle that loads everything
    }
}