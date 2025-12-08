<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/lib/addons/consent-compliance/AIPKit_Consent_IsRequiredChecker.php
// Status: NEW FILE

namespace WPAICG\Lib\Addons\ConsentCompliance;

use WPAICG\aipkit_dashboard;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * AIPKit_Consent_IsRequiredChecker
 *
 * Checks if consent compliance is required based on addon status and plan.
 */
class AIPKit_Consent_IsRequiredChecker {

    const ADDON_KEY = 'consent_compliance';

    /**
     * Checks if consent is required (addon active AND Pro plan).
     *
     * @return bool True if consent is required, false otherwise.
     */
    public static function check(): bool {
        // Ensure the dashboard class is available for checks
        if (!class_exists('\\WPAICG\\aipkit_dashboard')) {
            $dashboard_path = defined('WPAICG_PLUGIN_DIR') ? WPAICG_PLUGIN_DIR . 'classes/dashboard/class-aipkit_dashboard.php' : null;
            if ($dashboard_path && file_exists($dashboard_path)) {
                require_once $dashboard_path;
            } else {
                 return false; // Fail safe: assume not required if dependencies missing
            }
        }

        // Consent is required ONLY if the addon is active AND the plan is Pro.
        $is_addon_enabled = aipkit_dashboard::is_addon_active(self::ADDON_KEY);
        $is_pro_plan      = aipkit_dashboard::is_pro_plan();

        return $is_addon_enabled && $is_pro_plan;
    }
}