<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/lib/dependency-loaders/triggers/load-storage.php
// Status: NEW FILE

namespace WPAICG\Lib\DependencyLoaders\Triggers;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Loads trigger storage component classes.
 */
function load_storage(): void {
    if (!defined('WPAICG_LIB_DIR')) {
        return;
    }
    $storage_base_path = WPAICG_LIB_DIR . 'chat/triggers/storage/';

    $storage_files = [
        'class-aipkit-trigger-loader.php',
        'class-aipkit-trigger-saver.php',
        'class-aipkit-trigger-adder.php',
        'class-aipkit-trigger-updater.php',
        'class-aipkit-trigger-deleter.php',
    ];

    foreach ($storage_files as $file) {
        $full_path = $storage_base_path . $file;
        $class_name_from_file = str_replace(['class-', '.php'], ['', ''], $file);
        $class_name_parts = explode('-', str_replace('aipkit-', 'AIPKit_', $class_name_from_file));
        $class_name_camel_case_parts = array_map('ucfirst', $class_name_parts);
        $class_name_camel_case = implode('', $class_name_camel_case_parts);
        $full_class_name = '\\WPAICG\\Lib\\Chat\\Triggers\\Storage\\' . $class_name_camel_case;

        if (file_exists($full_path)) {
            if (!class_exists($full_class_name)) {
                require_once $full_path;
            }
        }
    }
}