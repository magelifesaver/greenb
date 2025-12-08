<?php
// File: includes/class-aipkit-module-initializer.php

namespace WPAICG\Includes;

use WPAICG\Dashboard\Initializer as DashboardInitializer;

// Ensure this file is only loaded by WordPress
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * AIPKit_Module_Initializer
 * Handles initializing core AIPKit modules like the Dashboard.
 */
class AIPKit_Module_Initializer {

    /**
     * Initialize core AIPKit modules.
     *
     * @param string $plugin_version The current plugin version.
     */
    public static function init(string $plugin_version) {
        // Dashboard Initializer
        $dashboard_initializer_path = WPAICG_PLUGIN_DIR . 'classes/dashboard/class-aipkit_dashboard_initializer.php';
        if (file_exists($dashboard_initializer_path)) {
            if (!class_exists(DashboardInitializer::class)) {
                require_once $dashboard_initializer_path;
            }
            if (class_exists(DashboardInitializer::class)) {
                DashboardInitializer::init($plugin_version);
            }
        }
    }
}