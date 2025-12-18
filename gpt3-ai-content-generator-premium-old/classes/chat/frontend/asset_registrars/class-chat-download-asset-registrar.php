<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/chat/frontend/asset_registrars/class-chat-download-asset-registrar.php
// Status: MODIFIED

namespace WPAICG\Chat\Frontend\AssetRegistrars;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Chat_Download_Asset_Registrar {
    public static function register(string $version, string $public_chat_js_url, array $dependencies = []): array {
        $public_chat_download_js_url = $public_chat_js_url . 'download/';
        $lib_chat_download_js_dir_path = WPAICG_LIB_DIR . 'js/chat/download/';
        $lib_chat_download_js_url = WPAICG_PLUGIN_URL . 'lib/js/chat/download/';

        $final_registered_handles = []; // Store successfully registered handles

        $download_helper_deps = [];
        $download_script_definitions = [
            'extract-text-from-bubble'=> ['aipkit-chat-extract-text-from-bubble', $public_chat_js_url . 'message-actions/extract-text-from-bubble.js', []],
            'generate-filename'  => ['aipkit-chat-download-generate-filename', $public_chat_download_js_url . 'generate-filename.js', []],
            'trigger-blob'       => ['aipkit-chat-download-trigger-blob', $public_chat_download_js_url . 'trigger-blob-download.js', []],
        ];

        foreach ($download_script_definitions as $key => $script_data) {
            list($handle, $path, $deps) = $script_data;
            if (!wp_script_is($handle, 'registered')) {
                wp_register_script($handle, $path, $deps, $version, true);
            }
            $final_registered_handles[$key] = $handle; // Assume these helpers are always available
            $download_helper_deps[$key] = $handle;
        }
        
        $txt_download_handle = 'aipkit-chat-download-as-txt';
        $pdf_download_handle = 'aipkit-chat-download-as-pdf';
        
        // TXT Download (always available)
        if (!wp_script_is($txt_download_handle, 'registered')) {
            wp_register_script($txt_download_handle, $public_chat_download_js_url . 'download-as-txt.js', array_values($download_helper_deps), $version, true);
        }
        $final_registered_handles['download-as-txt'] = $txt_download_handle;
        
        // PDF Download (Pro feature)
        $pdf_download_script_path = $lib_chat_download_js_dir_path . 'download-as-pdf.js';
        $pdf_download_script_url = $lib_chat_download_js_url . 'download-as-pdf.js';

        if (file_exists($pdf_download_script_path)) {
            $pdf_script_actual_deps = array_values($download_helper_deps);
            if (isset($dependencies['jspdf']) && wp_script_is($dependencies['jspdf'], 'registered')) {
                $pdf_script_actual_deps[] = $dependencies['jspdf'];
            }
            if (!wp_script_is($pdf_download_handle, 'registered')) {
                wp_register_script($pdf_download_handle, $pdf_download_script_url, $pdf_script_actual_deps, $version, true);
            }
            $final_registered_handles['download-as-pdf'] = $pdf_download_handle; // Add only if registered
        }
        
        return $final_registered_handles; 
    }
}