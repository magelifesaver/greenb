<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/lib/addons/class-aipkit-openai-moderation.php
// Status: MODIFIED (Facade)

namespace WPAICG\Lib\Addons;

// Use the new component classes
use WPAICG\Lib\Addons\OpenAI\Moderation\AIPKit_Moderation_IsRequired;
use WPAICG\Lib\Addons\OpenAI\Moderation\AIPKit_Moderation_MessageProvider;
use WPAICG\Lib\Addons\OpenAI\Moderation\AIPKit_Moderation_Executor;
use WP_Error; // Keep for type hinting if any internal method would return it, though static public ones won't.

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * AIPKit_OpenAI_Moderation (Facade)
 *
 * Facade class that delegates OpenAI Moderation addon checks and execution
 * to specialized component classes.
 * Preserves the original static public API.
 */
class AIPKit_OpenAI_Moderation {

    // Constants remain here as part of the Facade's public contract/definition
    const ADDON_KEY = 'openai_moderation';
    const SECURITY_OPTION_NAME = 'aipkit_security';

    /**
     * Checks if the OpenAI Moderation addon is currently active AND enabled in settings.
     * Delegates to AIPKit_Moderation_IsRequired::check().
     *
     * @return bool True if moderation should be applied, false otherwise.
     */
    public static function is_required(): bool {
        // Ensure component is loaded (should be by wpaicg__premium_only.php)
        if (!class_exists(AIPKit_Moderation_IsRequired::class)) {
             return false; // Fail safe
        }
        return AIPKit_Moderation_IsRequired::check();
    }

    /**
     * Gets the custom notification message for flagged content.
     * Delegates to AIPKit_Moderation_MessageProvider::get().
     *
     * @return string The message to display.
     */
    public static function get_flagged_message(): string {
        // Ensure component is loaded
        if (!class_exists(AIPKit_Moderation_MessageProvider::class)) {
             return __('Your message was flagged by the moderation system and could not be sent.', 'gpt3-ai-content-generator'); // Fallback
        }
        return AIPKit_Moderation_MessageProvider::get();
    }

     /**
      * Performs OpenAI moderation check on the provided text.
      * Delegates to AIPKit_Moderation_Executor::execute().
      *
      * @param string $text The input text to moderate.
      * @return string|false|null Returns the flagged message string if flagged,
      *                           false if not flagged,
      *                           null if moderation is not required or an API error occurred.
      */
    public static function perform_moderation(string $text): string|false|null {
        // Ensure component is loaded
        if (!class_exists(AIPKit_Moderation_Executor::class)) {
             return null; // Fail safe (moderation not applied)
        }
        return AIPKit_Moderation_Executor::execute($text);
    }
}