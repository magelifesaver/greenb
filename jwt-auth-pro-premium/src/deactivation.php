<?php

declare(strict_types=1);

namespace Tmeister\JWTAuthPro;

use Tmeister\JWTAuthPro\Services\SettingsService;

/**
 * Plugin deactivation handler
 */
function deactivate(): void
{
    if (!defined('JWT_AUTH_PRO_TOKENS_TABLE')) {
        require_once dirname(__DIR__) . '/jwt-auth-pro.php';
    }

    // Clear scheduled events
    wp_clear_scheduled_hook('jwt_auth_daily_analytics');
    wp_clear_scheduled_hook('jwt_auth_weekly_consolidation');

    // Check if we should delete data
    $settingsService = new SettingsService();
    $shouldDeleteData = $settingsService->getSetting('data_management', 'delete_on_deactivation');

    if ($shouldDeleteData) {
        global $wpdb;

        // Tables to remove in correct order (respecting foreign keys)
        $tables = [
            JWT_AUTH_PRO_ANALYTICS_SUMMARY_TABLE,
            JWT_AUTH_PRO_ANALYTICS_TABLE,
            JWT_AUTH_PRO_TOKENS_TABLE,
        ];

        // Drop tables in order (foreign keys will be automatically removed)
        foreach ($tables as $table) {
            $table_name = $wpdb->prefix . $table;
            $wpdb->query($wpdb->prepare("DROP TABLE IF EXISTS %i", $table_name));
        }

        // Clean up options and transients
        delete_option('jwt_auth_pro_db_version');
        delete_option('jwt_auth_pro_version');
        delete_option(SettingsService::OPTION_KEY);
        delete_option('jwt_auth_pro_settings_version');
    }
}
