<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/lib/addons/openai/moderation/AIPKit_Moderation_MessageProvider.php
// Status: NEW FILE

namespace WPAICG\Lib\Addons\OpenAI\Moderation;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * AIPKit_Moderation_MessageProvider
 *
 * Provides the custom notification message for flagged content.
 */
class AIPKit_Moderation_MessageProvider {

    // Constant specific to this provider's logic
    const SECURITY_OPTION_NAME = 'aipkit_security';

    /**
     * Gets the custom notification message for flagged content from settings.
     *
     * @return string The message to display.
     */
    public static function get(): string {
        $security_options = get_option(self::SECURITY_OPTION_NAME, []);
        $message = $security_options['openai_moderation_message'] ?? __('Your message was flagged by the moderation system and could not be sent.', 'gpt3-ai-content-generator');

        // Fallback if the message is empty
        if (empty(trim($message))) {
            $message = __('Your message was flagged by the moderation system and could not be sent.', 'gpt3-ai-content-generator');
        }
        return $message;
    }
}