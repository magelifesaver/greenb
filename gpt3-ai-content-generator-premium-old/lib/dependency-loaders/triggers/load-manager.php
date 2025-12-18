<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/lib/dependency-loaders/triggers/load-manager.php
// Status: NEW FILE

namespace WPAICG\Lib\DependencyLoaders\Triggers;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Loads trigger manager component classes.
 */
function load_manager(): void {
    if (!defined('WPAICG_LIB_DIR')) {
        return;
    }
    $manager_base_path = WPAICG_LIB_DIR . 'chat/triggers/manager/';

    $manager_files = [
        'class-aipkit-trigger-fetcher.php',
        'class-aipkit-trigger-context-updater.php',
        'class-aipkit-trigger-event-processor.php',
    ];

    foreach ($manager_files as $file) {
        $full_path = $manager_base_path . $file;
        $class_name_from_file = str_replace(['class-', '.php'], ['', ''], $file);
        $class_name_parts = explode('-', str_replace('aipkit-', 'AIPKit_', $class_name_from_file));
        $class_name_camel_case_parts = array_map('ucfirst', $class_name_parts);
        $class_name_camel_case = implode('', $class_name_camel_case_parts);
        $full_class_name = '\\WPAICG\\Lib\\Chat\\Triggers\\Manager\\' . $class_name_camel_case;

        if (file_exists($full_path)) {
            if (!class_exists($full_class_name)) {
                require_once $full_path;
            }
        }
    }
}