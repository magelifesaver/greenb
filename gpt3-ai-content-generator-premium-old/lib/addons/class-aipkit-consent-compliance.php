<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/lib/addons/class-aipkit-consent-compliance.php
// Status: MODIFIED (Facade)

namespace WPAICG\Lib\Addons;

// Use the new component classes from the ConsentCompliance sub-namespace
use WPAICG\Lib\Addons\ConsentCompliance\AIPKit_Consent_IsActiveChecker;
use WPAICG\Lib\Addons\ConsentCompliance\AIPKit_Consent_IsRequiredChecker;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * AIPKit_Consent_Compliance (Facade)
 *
 * Facade class that delegates Consent Compliance addon checks
 * to specialized component classes. Preserves the original static public API.
 */
class AIPKit_Consent_Compliance {

    const ADDON_KEY = 'consent_compliance'; // Keep constant as part of the Facade's public contract

    /**
     * Checks if the Consent Compliance addon is currently active (toggled ON).
     * Delegates to AIPKit_Consent_IsActiveChecker::check().
     *
     * @return bool True if the addon toggle is active, false otherwise.
     */
    public static function is_active(): bool {
        // Ensure component is loaded (should be by wpaicg__premium_only.php)
        if (!class_exists(AIPKit_Consent_IsActiveChecker::class)) {
             return false; // Fail safe
        }
        return AIPKit_Consent_IsActiveChecker::check();
    }

    /**
     * Checks if consent is required based on the addon's active state AND the user's plan.
     * Delegates to AIPKit_Consent_IsRequiredChecker::check().
     *
     * @return bool True if consent is required, false otherwise.
     */
    public static function is_required(): bool {
        // Ensure component is loaded
        if (!class_exists(AIPKit_Consent_IsRequiredChecker::class)) {
             return false; // Fail safe
        }
        return AIPKit_Consent_IsRequiredChecker::check();
    }

    // Future methods could be added here, e.g., fetching custom consent text if implemented.
}