<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/chat/frontend/shortcode/configurator/get-consent-settings.php
// Status: NEW FILE

namespace WPAICG\Chat\Frontend\Shortcode\ConfiguratorMethods;

use WPAICG\AIPKIT_AI_Settings; // Use main settings class for constant

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Prepares the consent-related text fields.
 *
 * @return array An array containing consent_title, consent_message, and consent_button texts.
 */
function get_consent_settings_logic(): array {
    if (!class_exists(AIPKIT_AI_Settings::class)) {
        return [
            'consent_title' => __('Consent Required', 'gpt3-ai-content-generator'),
            'consent_message' => __('Before starting the conversation, please agree to our Terms of Service and Privacy Policy.', 'gpt3-ai-content-generator'),
            'consent_button' => __('I Agree', 'gpt3-ai-content-generator'),
        ];
    }

    $security_options = AIPKIT_AI_Settings::get_security_settings();
    $consent_settings_from_db = $security_options['consent'] ?? [];

    $default_title = AIPKIT_AI_Settings::$default_security_settings['consent']['title'] ?: __('Consent Required', 'gpt3-ai-content-generator');
    $default_message = AIPKIT_AI_Settings::$default_security_settings['consent']['message'] ?: __('Before starting the conversation, please agree to our Terms of Service and Privacy Policy.', 'gpt3-ai-content-generator');
    $default_button = AIPKIT_AI_Settings::$default_security_settings['consent']['button'] ?: __('I Agree', 'gpt3-ai-content-generator');

    return [
        'consent_title' => !empty($consent_settings_from_db['title']) ? $consent_settings_from_db['title'] : $default_title,
        'consent_message' => !empty($consent_settings_from_db['message']) ? $consent_settings_from_db['message'] : $default_message,
        'consent_button' => !empty($consent_settings_from_db['button']) ? $consent_settings_from_db['button'] : $default_button,
    ];
}