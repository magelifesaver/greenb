<?php

namespace WPAICG\Includes\DependencyLoaders;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Automated_Task_Cron_Helpers_Loader
{
    public static function load()
    {
        $cron_base_path = WPAICG_PLUGIN_DIR . 'classes/autogpt/cron/';
        $cron_helpers = [
            'class-aipkit-automated-task-scheduler.php',
            'class-aipkit-automated-task-content-queuer.php',
            'class-aipkit-automated-task-event-processor.php',
        ];
        foreach ($cron_helpers as $file) {
            $full_path = $cron_base_path . $file;
            if (file_exists($full_path)) {
                require_once $full_path;
            }
        }
    }
}
