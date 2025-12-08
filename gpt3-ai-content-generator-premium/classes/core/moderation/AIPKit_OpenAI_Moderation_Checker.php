<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/core/moderation/AIPKit_OpenAI_Moderation_Checker.php
// Status: NEW FILE

namespace WPAICG\Core\Moderation;

use WPAICG\Lib\Addons\AIPKit_OpenAI_Moderation as ProOpenAIModerationFacade;
use WPAICG\AIPKit_Providers;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * AIPKit_OpenAI_Moderation_Checker
 *
 * Checks if OpenAI Moderation should be performed and, if so, calls the Pro addon helper.
 */
class AIPKit_OpenAI_Moderation_Checker {

    /**
     * Checks if text should be moderated by OpenAI and performs the check if applicable.
     *
     * @param string $text The text content to check.
     * @param array $bot_settings Settings of the current bot (used for provider check).
     * @return WP_Error|null WP_Error if flagged by OpenAI, null otherwise or if not applicable.
     */
    public static function check(string $text, array $bot_settings): ?WP_Error {
        // 1. Check if the Pro OpenAI Moderation Facade class exists
        if (!class_exists(ProOpenAIModerationFacade::class)) {
            // This means the Pro addon files (in /lib/) are not loaded. This is normal for free version.
            // No error_log needed here, as it's an expected state in free version.
            return null;
        }

        // 2. Determine the AI provider for the current context (bot settings)
        $provider_from_bot = $bot_settings['provider'] ?? null;
        $global_default_provider = null;
        if (class_exists(\WPAICG\AIPKit_Providers::class)) { // Ensure Providers class is loaded
            $global_default_provider = \WPAICG\AIPKit_Providers::get_current_provider();
        }
        $current_provider = $provider_from_bot ?: $global_default_provider;

        // 3. OpenAI Moderation only applies if the selected provider is OpenAI
        if ($current_provider !== 'OpenAI') {
            return null;
        }

        // 4. Use the Pro Facade's perform_moderation method.
        // This method internally calls ProOpenAIModerationFacade::is_required() and then the executor.
        $moderation_result = ProOpenAIModerationFacade::perform_moderation($text);

        // 5. Analyze the result from the Pro Facade:
        // - null: Moderation wasn't required by Pro Facade's internal checks, or an API error occurred (Pro Facade handles logging).
        // - false: Moderation check passed.
        // - string: Moderation flagged the message, and the string is the user-facing message.
        if (is_string($moderation_result)) {
            // Message was flagged by the Pro Facade. The result is the user-facing message.
            return new WP_Error('content_flagged_by_openai', $moderation_result, ['status' => 400]); // Bad Request
        }

        // If null (not required/API error) or false (passed), return null from this checker.
        return null;
    }
}