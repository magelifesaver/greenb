<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/lib/addons/consent-compliance/AIPKit_Consent_IsActiveChecker.php
// Status: NEW FILE

namespace WPAICG\Lib\Addons\ConsentCompliance;

use WPAICG\aipkit_dashboard;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * AIPKit_Consent_IsActiveChecker
 *
 * Checks if the Consent Compliance addon is currently active (toggled ON).
 */
class AIPKit_Consent_IsActiveChecker {

    const ADDON_KEY = 'consent_compliance';

    /**
     * Checks if the Consent Compliance addon toggle is active in plugin settings.
     *
     * @return bool True if the addon toggle is active, false otherwise.
     */
    public static function check(): bool {
        // Ensure the dashboard class is available for checking addon status
        if (!class_exists('\\WPAICG\\aipkit_dashboard')) {
            $dashboard_path = defined('WPAICG_PLUGIN_DIR') ? WPAICG_PLUGIN_DIR . 'classes/dashboard/class-aipkit_dashboard.php' : null;
            if ($dashboard_path && file_exists($dashboard_path)) {
                require_once $dashboard_path;
            } else {
                 return false; // Fail safe: assume inactive if dependencies missing
            }
        }

        // Check only the addon toggle status saved in options
        return aipkit_dashboard::is_addon_active(self::ADDON_KEY);
    }
}