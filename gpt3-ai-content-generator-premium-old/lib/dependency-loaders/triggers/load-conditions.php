<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/lib/dependency-loaders/triggers/load-conditions.php
// Status: NEW FILE

namespace WPAICG\Lib\DependencyLoaders\Triggers;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Loads trigger condition component classes.
 */
function load_conditions(): void {
    if (!defined('WPAICG_LIB_DIR')) {
        return;
    }
    $conditions_base_path = WPAICG_LIB_DIR . 'chat/triggers/conditions/';

    $condition_component_files = [
        'class-aipkit-condition-value-resolver.php',
        'class-aipkit-condition-comparator.php',
        'class-aipkit-condition-runner.php',
    ];

    foreach ($condition_component_files as $file) {
        $full_path = $conditions_base_path . $file;
        $class_name_from_file = str_replace(['class-', '.php'], ['', ''], $file);
        $class_name_parts = explode('-', $class_name_from_file);
        $class_name_camel_case_parts = [];
        foreach($class_name_parts as $part_idx => $part) {
            if (strtolower($part) === 'aipkit' && $part_idx === 0) {
                $class_name_camel_case_parts[] = 'AIPKit';
            } else {
                $class_name_camel_case_parts[] = ucfirst($part);
            }
        }
        $class_name_camel_case = implode('', $class_name_camel_case_parts);
        $full_class_name = '\\WPAICG\\Lib\\Chat\\Triggers\\Conditions\\' . $class_name_camel_case;

        if (file_exists($full_path)) {
            if (!class_exists($full_class_name)) {
                require_once $full_path;
            }
        }
    }
}