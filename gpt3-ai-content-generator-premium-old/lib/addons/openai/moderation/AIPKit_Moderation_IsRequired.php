<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/lib/addons/openai/moderation/AIPKit_Moderation_IsRequired.php
// Status: NEW FILE

namespace WPAICG\Lib\Addons\OpenAI\Moderation;

use WPAICG\aipkit_dashboard;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * AIPKit_Moderation_IsRequired
 *
 * Checks if OpenAI Moderation is required based on plan, addon status, and settings.
 */
class AIPKit_Moderation_IsRequired {

    // Constants specific to this checker's logic
    const ADDON_KEY = 'openai_moderation';
    const SECURITY_OPTION_NAME = 'aipkit_security';

    /**
     * Checks if OpenAI Moderation is currently active AND enabled in settings.
     *
     * @return bool True if moderation should be applied, false otherwise.
     */
    public static function check(): bool {
        // Ensure dashboard class is available
        if (!class_exists('\WPAICG\aipkit_dashboard')) {
            $dashboard_path = WPAICG_PLUGIN_DIR . 'classes/dashboard/class-aipkit_dashboard.php';
            if (file_exists($dashboard_path)) {
                require_once $dashboard_path;
            } else {
                 return false; // Fail safe
            }
        }

        // 1. Check Pro Plan status
        if (!aipkit_dashboard::is_pro_plan()) {
            return false;
        }

        // 2. Check Addon active status
        if (!aipkit_dashboard::is_addon_active(self::ADDON_KEY)) {
            return false;
        }

        // 3. Check if enabled in Chat Settings -> Security
        $security_options = get_option(self::SECURITY_OPTION_NAME, []);
        $enabled_in_settings = isset($security_options['openai_moderation_enabled']) && $security_options['openai_moderation_enabled'] === '1';

        return $enabled_in_settings;
    }
}