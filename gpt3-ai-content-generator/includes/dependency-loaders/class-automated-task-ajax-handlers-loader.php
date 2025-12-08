<?php

namespace WPAICG\Includes\DependencyLoaders;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Automated_Task_Ajax_Handlers_Loader
{
    public static function load()
    {
        $ajax_base_path = WPAICG_PLUGIN_DIR . 'classes/autogpt/ajax/';
        $ajax_actions = [
            'class-aipkit-automated-task-base-ajax-action.php',
            'class-aipkit-save-automated-task-action.php',
            'class-aipkit-get-automated-tasks-action.php',
            'class-aipkit-delete-automated-task-action.php',
            'class-aipkit-update-automated-task-status-action.php',
            'class-aipkit-run-automated-task-now-action.php',
            'class-aipkit-get-automated-task-queue-items-action.php',
            'class-aipkit-delete-automated-task-queue-item-action.php',
            'class-aipkit-delete-automated-task-queue-items-by-status-action.php',
            'class-aipkit-retry-automated-task-queue-item-action.php',
        ];
        foreach ($ajax_actions as $file) {
            $full_path = $ajax_base_path . $file;
            if (file_exists($full_path)) {
                require_once $full_path;
            }
        }
    }
}
