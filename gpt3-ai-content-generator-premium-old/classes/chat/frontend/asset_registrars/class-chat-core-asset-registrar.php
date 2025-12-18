<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/chat/frontend/asset_registrars/class-chat-core-asset-registrar.php
// Status: MODIFIED

namespace WPAICG\Chat\Frontend\AssetRegistrars;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Chat_Core_Asset_Registrar
{
    public static function register(string $version, string $plugin_base_url, string $plugin_dir, string $public_js_url): array
    {
        $core_handles = [];
        $public_chat_utils_js_url = $public_js_url . 'chat/utils/';
        $public_chat_themes_js_url = $public_js_url . 'chat/themes/';
        $public_chat_markdown_js_url = $public_js_url . 'chat/';

        // API Frontend Request Util
        $api_frontend_request_handle = 'aipkit-api-frontend-request';
        if (!wp_script_is($api_frontend_request_handle, 'registered')) {
            // Assuming api-frontend-request.js is part of the public-main.bundle.js or needs to be registered separately if standalone.
            // If it's part of public-main.bundle.js, this registration is not needed here.
            // If it IS a standalone utility to be registered, it should use $public_js_url, not $dist_js_url directly here.
            // For now, assuming it's either part of the main bundle or registered elsewhere if truly standalone.
            // If it were to be registered here from its original source (which is not ideal after bundling):
            // wp_register_script($api_frontend_request_handle, $public_js_url . 'utils/api-frontend-request.js', [], $version, true);
            // However, since Phase 4.1 implies bundling, this script should be imported in public-main.js.
            // For now, we'll keep this registration if it's meant to be a distinct utility handle,
            // but it should ideally point to a dist/ path if it were a separately bundled utility.
            // Given the bundling strategy, if 'utils/api-frontend-request.js' is small, it should be in public-main.js.
            // If it's large and shared, it would be its own bundle or a vendor script.
            // Let's assume for now it's small and included in public-main.js, thus this handle isn't registered here.
            // $core_handles['api-frontend-request'] = $api_frontend_request_handle;
        }

        // HTML Escaper (Shared Asset) - This handle is registered by AIPKit_Shared_Assets_Manager.
        // We just acknowledge it might be a dependency for scripts that *were* registered here.
        $html_escaper_handle = 'aipkit-html-escaper';
        if (wp_script_is($html_escaper_handle, 'registered')) {
            // This class doesn't return this handle directly anymore as it doesn't register it.
            // Dependencies on it should be handled by the scripts that need it.
        }

        // Register individual chat utility scripts that are part of public-main.bundle.js
        // These registrations are more for conceptual dependency tracking if other very granular scripts depended on them.
        // With bundling, these are mostly for logical organization within the AssetDependencyRegistrar's dependency gathering.
        $chat_util_scripts = [
            'auto-resize-textarea'       => ['aipkit-chat-util-auto-resize', $public_chat_utils_js_url . 'auto-resize-textarea.js', []],
            'generate-client-message-id' => ['aipkit-chat-util-gen-id', $public_chat_utils_js_url . 'generate-client-message-id.js', []],
            'toggle-web-search'          => ['aipkit-chat-util-toggle-web-search', $public_chat_utils_js_url . 'toggle-web-search.js', []],
            'toggle-google-grounding'    => ['aipkit-chat-util-toggle-google-grounding', $public_chat_utils_js_url . 'toggle-google-grounding.js', []],
        ];
        foreach ($chat_util_scripts as $key => $script_data) {
            list($handle, $path, $deps) = $script_data;
            // These scripts are now part of public-main.bundle.js.
            // We don't register them individually anymore.
            // We return their handles so other registrars know these "conceptual" modules are available via the main bundle.
            $core_handles[$key] = $handle;
        }

        // Register apply-custom-theme.js (part of public-main.bundle.js)
        $apply_theme_handle = 'aipkit-chat-ui-apply-custom-theme';
        // No individual registration needed if it's part of public-main.bundle.js
        $core_handles['apply-custom-theme'] = $apply_theme_handle;

        // Register markdown-initiate.js (part of public-main.bundle.js)
        $markdown_initiate_handle = 'aipkit-markdown-initiate';
        // No individual registration needed if it's part of public-main.bundle.js
        $core_handles['markdown-initiate'] = $markdown_initiate_handle;

        return $core_handles;
    }
}
