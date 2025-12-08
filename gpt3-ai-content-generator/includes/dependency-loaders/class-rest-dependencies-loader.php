<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/includes/dependency-loaders/class-rest-dependencies-loader.php
// Status: MODIFIED
// I have updated this loader to include the new Logs REST handler.

namespace WPAICG\Includes\DependencyLoaders;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Rest_Dependencies_Loader
{
    public static function load()
    {
        $rest_base_path = WPAICG_PLUGIN_DIR . 'classes/rest/';
        $base_handler_path = $rest_base_path . 'handlers/class-aipkit-rest-base-handler.php';
        if (file_exists($base_handler_path)) {
            require_once $base_handler_path;
        }
        $handlers_to_load = [
            'class-aipkit-rest-text-handler.php',
            'class-aipkit-rest-image-handler.php',
            'class-aipkit-rest-embeddings-handler.php',
            'class-aipkit-rest-chat-handler.php',
            'class-aipkit-rest-vector-store-handler.php',
            'class-aipkit-rest-chatbot-embed-handler.php', // NEW: Embed handler
            'class-aipkit-rest-logs-handler.php', // NEW: Logs handler
        ];
        foreach ($handlers_to_load as $handler_file) {
            $full_path = $rest_base_path . 'handlers/' . $handler_file;
            if (file_exists($full_path)) {
                require_once $full_path;
            }
        }
        $rest_controller_path = $rest_base_path . 'class-aipkit-rest-controller.php';
        if (file_exists($rest_controller_path)) {
            require_once $rest_controller_path;
        }
    }
}