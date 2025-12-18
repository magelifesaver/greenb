<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/includes/dependency-loaders/class-ai-forms-dependencies-loader.php
// Status: MODIFIED

namespace WPAICG\Includes\DependencyLoaders;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * AIPKit_AI_Forms_Dependencies_Loader
 * Handles loading all necessary PHP class dependencies for the AI Forms module.
 */
class AI_Forms_Dependencies_Loader
{
    public static function load()
    {
        $ai_forms_base_path = WPAICG_PLUGIN_DIR . 'classes/ai-forms/';

        // Define paths to AI Forms classes
        $paths = [
            'admin/class-aipkit-ai-form-admin-setup.php',
            'admin/class-aipkit-ai-form-ajax-handler.php', // This handles CRUD on forms
            'admin/class-aipkit-ai-form-settings-ajax-handler.php', // NEW: This handles token settings
            'admin/class-aipkit-ai-form-defaults.php', // --- ADDED ---
            'frontend/class-aipkit-ai-form-shortcode.php',
            // REMOVED old processor logic files
            // Then load the facade and storage
            'core/class-aipkit-ai-form-processor.php',
            'storage/class-aipkit-ai-form-storage.php',
            'class-aipkit-ai-form-initializer.php',
        ];

        foreach ($paths as $file) {
            $full_path = $ai_forms_base_path . $file;
            if (file_exists($full_path)) {
                require_once $full_path;
            }
        }
    }
}
