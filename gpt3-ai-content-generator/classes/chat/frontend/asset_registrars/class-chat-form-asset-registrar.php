<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/chat/frontend/asset_registrars/class-chat-form-asset-registrar.php
// Status: NEW FILE

namespace WPAICG\Chat\Frontend\AssetRegistrars;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Handles registering JS assets related to dynamic chat forms.
 */
class Chat_Form_Asset_Registrar {

    /**
     * Registers JavaScript files needed for chat form functionality.
     *
     * @param string $version Plugin version.
     * @param string $public_chat_js_url Base URL for public chat JS files.
     * @param array $dependencies Array of already registered script handles this module might depend on.
     *                            Expected keys: 'api-frontend-request', 'dom-append-message', 'generate-client-message-id'.
     * @return array Handles of the registered scripts for this module.
     */
    public static function register(string $version, string $public_chat_js_url, array $dependencies = []): array {
        $public_chat_form_js_url = $public_chat_js_url . 'form/'; // Subdirectory for form scripts
        $handles = [];

        $script_definitions = [
            'handle-form-submission' => [
                'aipkit-chat-form-handle-submission',
                $public_chat_form_js_url . 'handle-form-submission.js',
                array_filter([
                    $dependencies['api-frontend-request'] ?? null,
                    $dependencies['dom-append-message'] ?? null,
                    $dependencies['generate-client-message-id'] ?? null,
                ]),
            ],
        ];

        foreach ($script_definitions as $key => $script_data) {
            list($handle, $path, $deps) = $script_data;
            if (!wp_script_is($handle, 'registered')) {
                wp_register_script($handle, $path, $deps, $version, true);
            }
            $handles[$key] = $handle;
        }

        return $handles;
    }
}