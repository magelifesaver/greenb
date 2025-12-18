<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/chat/frontend/shortcode/configurator/get-conversation-starters.php
// Status: NEW FILE

namespace WPAICG\Chat\Frontend\Shortcode\ConfiguratorMethods;

use WPAICG\Chat\Storage\BotSettingsManager; // For default constants

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Prepares the conversation starters array.
 *
 * @param array $settings Bot settings.
 * @param bool $starters_ui_enabled Flag indicating if starters UI is enabled.
 * @return array The array of conversation starter strings.
 */
function get_conversation_starters_logic(array $settings, bool $starters_ui_enabled): array {
    $starters_array = [];
    if ($starters_ui_enabled) {
        $starters_raw = $settings['conversation_starters'] ?? [];
        if (!empty($starters_raw) && is_array($starters_raw)) { // Check if it's already an array
            $starters_array = $starters_raw;
        } elseif (!empty($starters_raw) && is_string($starters_raw)) { // Handle JSON string if somehow passed
            $decoded_starters = json_decode($starters_raw, true);
            if (is_array($decoded_starters)) {
                $starters_array = $decoded_starters;
            }
        }

        if (empty($starters_array)) {
            // Fallback to default starters if the setting is empty or invalid
            $starters_array = [
                __('What can you do?', 'gpt3-ai-content-generator'),
                __('Tell me a fun fact', 'gpt3-ai-content-generator'),
                __('Help me write something', 'gpt3-ai-content-generator')
            ];
        }
    }
    return $starters_array;
}