<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/lib/addons/openai/moderation/AIPKit_Moderation_Executor.php
// Status: NEW FILE

namespace WPAICG\Lib\Addons\OpenAI\Moderation;

use WPAICG\AIPKit_Providers;
use WPAICG\Core\Providers\ProviderStrategyFactory;
use WP_Error;
// Depend on the new components for checking requirement and getting messages
use WPAICG\Lib\Addons\OpenAI\Moderation\AIPKit_Moderation_IsRequired;
use WPAICG\Lib\Addons\OpenAI\Moderation\AIPKit_Moderation_MessageProvider;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * AIPKit_Moderation_Executor
 *
 * Handles the execution of the OpenAI moderation API call.
 */
class AIPKit_Moderation_Executor
{
    /**
     * Performs OpenAI moderation check on the provided text IF moderation is required.
     *
     * @param string $text The input text to moderate.
     * @return string|false|null Returns the flagged message string if flagged,
     *                           false if not flagged,
     *                           null if moderation is not required or an API error occurred.
     */
    public static function execute(string $text): string|false|null
    {
        // 1. Check if moderation is required using the dedicated checker class
        // Ensure checker class is loaded (though wpaicg__premium_only.php should handle it)
        if (!class_exists(AIPKit_Moderation_IsRequired::class)) {
            $is_required_path = __DIR__ . '/AIPKit_Moderation_IsRequired.php';
            if (file_exists($is_required_path)) {
                require_once $is_required_path;
            } else {
                return null;
            }
        }

        if (!AIPKit_Moderation_IsRequired::check()) {
            return null; // Moderation not required
        }

        // 2. Get OpenAI Strategy and Credentials
        // Ensure Provider classes are available
        $factory_path = WPAICG_PLUGIN_DIR . 'classes/core/providers/provider-strategy-factory.php';
        $providers_path = WPAICG_PLUGIN_DIR . 'classes/dashboard/class-aipkit_providers.php';

        if (!class_exists(ProviderStrategyFactory::class)) {
            if (file_exists($factory_path)) {
                require_once $factory_path;
            } else {
                return null;
            }
        }
        if (!class_exists(AIPKit_Providers::class)) {
            if (file_exists($providers_path)) {
                require_once $providers_path;
            } else {
                return null;
            }
        }


        $strategy = ProviderStrategyFactory::get_strategy('OpenAI');
        $api_params = AIPKit_Providers::get_provider_data('OpenAI');

        if (is_wp_error($strategy)) {
            return null; // Cannot proceed, treat as non-blocking
        }
        if (!method_exists($strategy, 'moderate_text')) {
            return null; // Cannot proceed, treat as non-blocking
        }

        // 3. Call the moderation method on the strategy
        $moderation_result = $strategy->moderate_text($text, $api_params);

        // 4. Handle the result
        if (is_wp_error($moderation_result)) {
            return null; // Treat API error as non-blocking for the user flow
        }

        if ($moderation_result === true) { // Message Flagged
            // Ensure message provider class is loaded
            if (!class_exists(AIPKit_Moderation_MessageProvider::class)) {
                $message_provider_path = __DIR__ . '/AIPKit_Moderation_MessageProvider.php';
                if (file_exists($message_provider_path)) {
                    require_once $message_provider_path;
                } else {
                    return __('Your message was flagged.', 'gpt3-ai-content-generator');
                }
            }
            return AIPKit_Moderation_MessageProvider::get(); // Return the message to display to the user
        } else { // Message Passed
            return false; // Indicate check passed
        }
    }
}
