<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/chat/frontend/asset_registrars/class-chat-image-upload-asset-registrar.php
// Status: NEW FILE

namespace WPAICG\Chat\Frontend\AssetRegistrars;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Chat_Image_Upload_Asset_Registrar {
    public static function register(string $version, string $public_chat_js_url): string {
        $public_chat_image_upload_js_url = $public_chat_js_url . 'image-upload/';
        $image_upload_deps = [];
        $image_upload_scripts = [
            'constants'              => ['aipkit-chat-image-upload-constants', $public_chat_image_upload_js_url . 'constants.js', []],
            'state'                  => ['aipkit-chat-image-upload-state', $public_chat_image_upload_js_url . 'state.js', []],
            'validate-image'         => ['aipkit-chat-image-upload-validate', $public_chat_image_upload_js_url . 'validate-image.js', ['aipkit-chat-image-upload-constants']],
            'convert-to-base64'      => ['aipkit-chat-image-upload-base64', $public_chat_image_upload_js_url . 'convert-to-base64.js', []],
            'clear-error'            => ['aipkit-chat-image-upload-clear-error', $public_chat_image_upload_js_url . 'clear-error.js', []], // Moved up
            'display-error'          => ['aipkit-chat-image-upload-display-error', $public_chat_image_upload_js_url . 'display-error.js', ['aipkit-chat-image-upload-clear-error']],
            'reset-upload'           => ['aipkit-chat-image-upload-reset-upload', $public_chat_image_upload_js_url . 'reset-upload.js', ['aipkit-chat-image-upload-state', 'aipkit-chat-image-upload-clear-error']], // Depends on clear-error
            'render-preview'         => ['aipkit-chat-image-upload-render-preview', $public_chat_image_upload_js_url . 'render-preview.js', ['aipkit-chat-image-upload-reset-upload']],
            'get-image-data'         => ['aipkit-chat-image-upload-get-data', $public_chat_image_upload_js_url . 'get-image-data.js', ['aipkit-chat-image-upload-state']],
            'get-allowed-extensions' => ['aipkit-chat-image-upload-get-extensions', $public_chat_image_upload_js_url . 'get-allowed-extensions.js', ['aipkit-chat-image-upload-constants']],
            'get-max-size'           => ['aipkit-chat-image-upload-get-max-size', $public_chat_image_upload_js_url . 'get-max-size.js', ['aipkit-chat-image-upload-constants']],
        ];
        foreach ($image_upload_scripts as $script_data) {
            list($handle, $path, $deps) = $script_data;
            if (!wp_script_is($handle, 'registered')) {
                wp_register_script($handle, $path, $deps, $version, true);
            }
            $image_upload_deps[] = $handle;
        }
        $chat_image_upload_init_handle = 'aipkit-chat-image-upload-init';
        if (!wp_script_is($chat_image_upload_init_handle, 'registered')) {
            wp_register_script($chat_image_upload_init_handle, $public_chat_image_upload_js_url . 'init.js', array_unique($image_upload_deps), $version, true);
        }
        return $chat_image_upload_init_handle;
    }
}