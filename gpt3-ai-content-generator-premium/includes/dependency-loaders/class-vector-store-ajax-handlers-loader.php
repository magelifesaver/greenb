<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/includes/dependency-loaders/class-vector-store-ajax-handlers-loader.php
// Status: MODIFIED

namespace WPAICG\Includes\DependencyLoaders;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Vector_Store_Ajax_Handlers_Loader
{
    public static function load()
    {
        $ajax_handlers_path = WPAICG_PLUGIN_DIR . 'classes/dashboard/ajax/';
        $openai_ajax_base_path = $ajax_handlers_path . 'openai/';
        // $pinecone_ajax_path = $ajax_handlers_path . 'pinecone/'; // No longer needed for direct fn-* requires
        // $qdrant_ajax_path = $ajax_handlers_path . 'qdrant/'; // No longer needed for direct fn-* requires

        // Load OpenAI handler classes (these are now thin wrappers)
        $openai_handler_classes_to_load = [
            'class-aipkit-openai-vector-stores-ajax-handler.php',
            'class-aipkit-openai-vector-store-files-ajax-handler.php',
            'class-aipkit-openai-wp-content-indexing-ajax-handler.php',
        ];
        foreach ($openai_handler_classes_to_load as $handler_file) {
            $full_path = $openai_ajax_base_path . $handler_file;
            if (file_exists($full_path)) {
                require_once $full_path;
            }
        }

        // Load Pinecone and Qdrant main handler classes from their new locations
        $other_handlers_to_load = [
            'pinecone/class-aipkit-vector-store-pinecone-ajax-handler.php', // Updated path
            'qdrant/class-aipkit-vector-store-qdrant-ajax-handler.php',   // Updated path
        ];
        foreach ($other_handlers_to_load as $handler_file_rel_path) {
            $full_path = $ajax_handlers_path . $handler_file_rel_path;
            if (file_exists($full_path)) {
                require_once $full_path;
            }
        }

        // REMOVED loading of old fn-*.php files for OpenAI, Pinecone, and Qdrant.
        // The logic is now in handler-files/, handler-stores/, handler-indexing/ (for OpenAI)
        // or handler-indexes/ (for Pinecone), or handler-collections/ (for Qdrant)
        // and these are included by the respective handler class methods.

        // Load OpenAI helper function files (these were in the old fn-*.php structure but might still be needed as general utils within OpenAI Ajax scope)
        // These are loaded here because they are used across different OpenAI handler methods.
        $openai_utility_functions_to_load = [
            'fn-log-entry.php',
            'fn-temp-file.php',
            'fn-stores-log-entry.php',
        ];
        foreach ($openai_utility_functions_to_load as $util_fn_file) {
            $full_path = $openai_ajax_base_path . $util_fn_file;
            if (file_exists($full_path)) {
                require_once $full_path;
            }
        }
    }
}
