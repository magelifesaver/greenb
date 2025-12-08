<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/includes/dependency-loaders/class-migration-ajax-handlers-loader.php
// Status: MODIFIED
// I have added the new migration and deletion action classes for indexed data to the loader.

namespace WPAICG\Includes\DependencyLoaders;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * AIPKit_Migration_Ajax_Handlers_Loader
 * Handles loading all the AJAX action handler classes for the migration tool.
 */
class Migration_Ajax_Handlers_Loader
{
    public static function load()
    {
        // --- ADDED: Load the main handler first ---
        $main_handler_path = WPAICG_PLUGIN_DIR . 'admin/class-aipkit-migration-handler.php';
        if (file_exists($main_handler_path)) {
            require_once $main_handler_path;
        }
        // --- END ADDED ---

        $migration_ajax_path = WPAICG_PLUGIN_DIR . 'admin/ajax/migration/';

        $ajax_action_files = [
            'class-aipkit-migration-base-ajax-action.php',
            'class-aipkit-analyze-old-data-action.php',
            // --- NEW: Updated paths for migrate actions ---
            'migrate/class-aipkit-migrate-global-settings-action.php',
            'migrate/class-aipkit-migrate-cpt-data-action.php',
            'migrate/class-aipkit-migrate-chatbot-data-action.php',
            'migrate/class-aipkit-migrate-indexed-data-action.php',
            // --- NEW: Updated paths for delete actions ---
            'delete/class-aipkit-delete-old-global-settings-action.php',
            'delete/class-aipkit-delete-old-chatbot-data-action.php',
            'delete/class-aipkit-delete-old-cpt-data-action.php',
            'delete/class-aipkit-delete-old-indexed-data-action.php',
        ];

        foreach ($ajax_action_files as $file) {
            $full_path = $migration_ajax_path . $file;
            if (file_exists($full_path)) {
                require_once $full_path;
            }
        }
    }
}