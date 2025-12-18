<?php

namespace WPAICG\Includes\DependencyLoaders;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Dashboard_Base_Classes_Loader {
    public static function load() {
        $dashboard_path = WPAICG_PLUGIN_DIR . 'classes/dashboard/';
        require_once $dashboard_path . 'class-aipkit_providers.php';
        require_once $dashboard_path . 'class-aipkit_ai_settings.php';
        require_once $dashboard_path . 'class-aipkit_dashboard.php';
        require_once $dashboard_path . 'class-aipkit_role_manager.php';
        require_once WPAICG_PLUGIN_DIR . 'classes/dashboard/class-aipkit_stats.php';
    }
}