<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/includes/dependency-loaders/class-vector-post-processor-classes-loader.php
// Status: MODIFIED

namespace WPAICG\Includes\DependencyLoaders;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Vector_Post_Processor_Classes_Loader
{
    public static function load()
    {
        $vpp_base_path = WPAICG_PLUGIN_DIR . 'classes/vector/post-processor/';

        // Load the Base Class first
        $base_class_path = $vpp_base_path . 'base/class-aipkit-vector-post-processor-base.php';
        if (file_exists($base_class_path) && !class_exists(\WPAICG\Vector\PostProcessor\Base\AIPKit_Vector_Post_Processor_Base::class)) {
            require_once $base_class_path;
        }

        // Load provider-specific directories and their components
        $provider_dirs = ['openai', 'pinecone', 'qdrant'];

        foreach ($provider_dirs as $provider_dir) {
            $provider_path = $vpp_base_path . $provider_dir . '/';
            $class_config_file = 'class-' . $provider_dir . '-config.php';
            $class_embedding_handler_file = 'class-' . $provider_dir . '-embedding-handler.php';
            $class_processor_file = 'class-' . $provider_dir . '-post-processor.php';

            // Config class
            $config_full_path = $provider_path . $class_config_file;
            $config_class_name = '\\WPAICG\\Vector\\PostProcessor\\' . ucfirst($provider_dir) . '\\' . ucfirst($provider_dir) . 'Config';
            if (file_exists($config_full_path) && !class_exists($config_class_name)) {
                require_once $config_full_path;
            }

            // Embedding Handler (not all providers have one)
            if ($provider_dir === 'pinecone' || $provider_dir === 'qdrant') {
                $embed_full_path = $provider_path . $class_embedding_handler_file;
                $embed_class_name = '\\WPAICG\\Vector\\PostProcessor\\' . ucfirst($provider_dir) . '\\' . ucfirst($provider_dir) . 'EmbeddingHandler';
                if (file_exists($embed_full_path) && !class_exists($embed_class_name)) {
                    require_once $embed_full_path;
                }
            }

            // Main Processor class
            $processor_full_path = $provider_path . $class_processor_file;
            $processor_class_name = '\\WPAICG\\Vector\\PostProcessor\\' . ucfirst($provider_dir) . '\\' . ucfirst($provider_dir) . 'PostProcessor';
            if (file_exists($processor_full_path) && !class_exists($processor_class_name)) {
                require_once $processor_full_path;
            }
        }

        // Load the main AJAX Handler (which uses the above processors)
        $ajax_handler_path = WPAICG_PLUGIN_DIR . 'classes/vector/post-processor/class-aipkit-vector-post-processor-ajax-handler.php';
        if (file_exists($ajax_handler_path) && !class_exists(\WPAICG\Vector\AIPKit_Vector_Post_Processor_Ajax_Handler::class)) {
            require_once $ajax_handler_path;
        }

        // Load the List Screen class (handles post list screen features)
        $list_screen_path = WPAICG_PLUGIN_DIR . 'classes/vector/post-processor/class-aipkit-vector-post-processor-list-screen.php';
        if (file_exists($list_screen_path) && !class_exists(\WPAICG\Vector\PostProcessor\AIPKit_Vector_Post_Processor_List_Screen::class)) {
            require_once $list_screen_path;
        }
    }
}
