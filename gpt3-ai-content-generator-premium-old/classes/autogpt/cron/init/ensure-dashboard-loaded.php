<?php

namespace WPAICG\AutoGPT\Cron\Init;

use WPAICG\aipkit_dashboard;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Ensures the aipkit_dashboard class is loaded.
 *
 * @return bool True on success, false if class cannot be loaded.
 */
function ensure_dashboard_loaded_logic(): bool
{
    if (!class_exists(aipkit_dashboard::class)) {
        $dashboard_path = WPAICG_PLUGIN_DIR . 'classes/dashboard/class-aipkit_dashboard.php';
        if (file_exists($dashboard_path)) {
            require_once $dashboard_path;
        } else {
            return false;
        }
    }
    return true;
}
