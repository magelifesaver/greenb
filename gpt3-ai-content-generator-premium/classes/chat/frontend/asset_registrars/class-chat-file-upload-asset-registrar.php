<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/chat/frontend/asset_registrars/class-chat-file-upload-asset-registrar.php
// Status: NEW FILE

namespace WPAICG\Chat\Frontend\AssetRegistrars;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Chat_File_Upload_Asset_Registrar {
    public static function register(string $version, string $public_chat_js_url): string {
        $public_chat_file_upload_js_url = $public_chat_js_url . 'file-upload/';
        $file_upload_deps = [];
        $file_upload_scripts = [
            'constants'      => ['aipkit-chat-file-upload-constants', $public_chat_file_upload_js_url . 'constants.js', []],
            'state'          => ['aipkit-chat-file-upload-state', $public_chat_file_upload_js_url . 'state.js', []],
            'validate-file'  => ['aipkit-chat-file-upload-validate', $public_chat_file_upload_js_url . 'validate-file.js', ['aipkit-chat-file-upload-constants']],
            'clear-status'   => ['aipkit-chat-file-upload-clear-status', $public_chat_file_upload_js_url . 'clear-status.js', []],
            'display-status' => ['aipkit-chat-file-upload-display-status', $public_chat_file_upload_js_url . 'display-status.js', ['aipkit-chat-file-upload-clear-status']],
            // 'reset-upload' is part of state.js now
        ];
        foreach ($file_upload_scripts as $script_data) {
            list($handle, $path, $deps) = $script_data;
            if (!wp_script_is($handle, 'registered')) {
                wp_register_script($handle, $path, $deps, $version, true);
            }
            $file_upload_deps[] = $handle;
        }
        // Main init script for file uploads
        $chat_file_upload_init_handle = 'aipkit-chat-file-upload-init';
        if (!wp_script_is($chat_file_upload_init_handle, 'registered')) {
            wp_register_script($chat_file_upload_init_handle, $public_chat_file_upload_js_url . 'init.js', array_unique(array_merge($file_upload_deps, ['aipkit-api-frontend-request'])), $version, true);
        }
        return $chat_file_upload_init_handle;
    }
}