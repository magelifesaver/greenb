<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/lib/dependency-loaders/triggers/load-core.php
// Status: NEW FILE

namespace WPAICG\Lib\DependencyLoaders\Triggers;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Loads core trigger facade classes and handler function.
 */
function load_core(): void {
    if (!defined('WPAICG_LIB_DIR')) {
        return;
    }
    $triggers_base_path = WPAICG_LIB_DIR . 'chat/triggers/';

    $core_trigger_files = [
        'class-aipkit-trigger-storage.php',
        'class-aipkit-trigger-condition-evaluator.php',
        'class-aipkit-trigger-action-executor.php',
        'class-aipkit-trigger-manager.php',
        'trigger_handler.php',
    ];

    foreach ($core_trigger_files as $file) {
        $full_path = $triggers_base_path . $file;
        if (file_exists($full_path)) {
            if (substr($file, -4) === '.php' && substr($file, 0, 6) === 'class-') {
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
                $full_class_name = '\\WPAICG\\Lib\\Chat\\Triggers\\' . $class_name_camel_case;

                if (!class_exists($full_class_name)) {
                    require_once $full_path;
                }
            } elseif ($file === 'trigger_handler.php') {
                if (!function_exists('\WPAICG\Lib\Chat\Triggers\process_chat_triggers')) {
                    require_once $full_path;
                }
            } else {
                 require_once $full_path;
            }
        }
    }
}