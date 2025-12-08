<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/includes/dependency-loaders/class-content-writer-dependencies-loader.php
// Status: MODIFIED
// I have added the new tags generation action and prompt builder classes to the loader.

namespace WPAICG\Includes\DependencyLoaders;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Content_Writer_Dependencies_Loader
{
    public static function load()
    {
        $content_writer_base_path = WPAICG_PLUGIN_DIR . 'classes/content-writer/';
        $files_to_load = [
            $content_writer_base_path . 'ajax/class-aipkit-content-writer-base-ajax-action.php',
            $content_writer_base_path . 'class-aipkit-content-writer-prompts.php',
            $content_writer_base_path . 'class-aipkit-content-writer-template-manager.php',
            $content_writer_base_path . 'ajax/class-aipkit-content-writer-template-ajax-handler.php',
            $content_writer_base_path . 'ajax/actions/class-aipkit-content-writer-init-stream-action.php',
            $content_writer_base_path . 'ajax/actions/class-aipkit-content-writer-standard-generation-action.php',
            $content_writer_base_path . 'ajax/actions/class-aipkit-content-writer-generate-title-action.php',
            $content_writer_base_path . 'ajax/actions/class-aipkit-content-writer-save-post-action.php',
            $content_writer_base_path . 'ajax/actions/class-aipkit-content-writer-create-task-action.php',
            // --- MODIFIED: Load new SEO action classes ---
            $content_writer_base_path . 'ajax/actions/class-aipkit-content-writer-generate-meta-action.php',
            $content_writer_base_path . 'ajax/actions/class-aipkit-content-writer-generate-keyword-action.php',
            $content_writer_base_path . 'ajax/actions/class-aipkit-content-writer-generate-excerpt-action.php',
            $content_writer_base_path . 'ajax/actions/class-aipkit-content-writer-generate-tags-action.php',
            $content_writer_base_path . 'ajax/actions/class-aipkit-content-writer-generate-images-action.php',
            $content_writer_base_path . 'ajax/actions/class-aipkit-content-writer-parse-csv-action.php',
            // --- MODIFIED: Scrape URL action is a Pro feature, moved to lib loader ---
            // --- END MODIFICATION ---
            $content_writer_base_path . 'prompt/class-aipkit-content-writer-system-instruction-builder.php',
            $content_writer_base_path . 'prompt/class-aipkit-content-writer-user-prompt-builder.php',
            $content_writer_base_path . 'prompt/class-aipkit-content-writer-summarizer.php',
            $content_writer_base_path . 'prompt/class-aipkit-content-writer-meta-prompt-builder.php',
            $content_writer_base_path . 'prompt/class-aipkit-content-writer-keyword-prompt-builder.php',
            $content_writer_base_path . 'prompt/class-aipkit-content-writer-excerpt-prompt-builder.php',
            $content_writer_base_path . 'prompt/class-aipkit-content-writer-tags-prompt-builder.php',
            // --- ADDED: New Image Handler and Injector classes ---
            $content_writer_base_path . 'class-aipkit-content-writer-image-handler.php',
            $content_writer_base_path . 'class-aipkit-image-injector.php',
            // --- END ADDED ---
        ];
        foreach ($files_to_load as $full_path) {
            if (file_exists($full_path)) {
                require_once $full_path;
            }
        }
    }
}
