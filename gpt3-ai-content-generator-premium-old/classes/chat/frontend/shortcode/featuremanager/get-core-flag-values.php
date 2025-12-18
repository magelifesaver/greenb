<?php

// File: classes/chat/frontend/shortcode/featuremanager/get-core-flag-values.php
// Status: MODIFIED

namespace WPAICG\Chat\Frontend\Shortcode\FeatureManagerMethods;

use WPAICG\Chat\Storage\BotSettingsManager; // For default constants

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Retrieves core feature flag values directly from bot settings.
 * These are intermediate values used by other flag determination logic.
 *
 * @param array $settings Bot settings array.
 * @return array An array of core flag values.
 */
function get_core_flag_values_logic(array $settings): array {
    if (!class_exists(BotSettingsManager::class)) {
        // This is a critical dependency for defaults. If it's not loaded,
        // the behavior might be unexpected. Consider logging an error.
        // Provide hardcoded fallbacks if class is missing, though this indicates a deeper issue.
        $defaults = [
            'DEFAULT_ENABLE_COPY_BUTTON' => '1',
            'DEFAULT_ENABLE_FEEDBACK' => '1',
            'DEFAULT_ENABLE_CONVERSATION_STARTERS' => '1',
            'DEFAULT_ENABLE_CONVERSATION_SIDEBAR' => '0',
            'DEFAULT_TTS_ENABLED' => '0',
            'DEFAULT_ENABLE_VOICE_INPUT' => '0',
            'DEFAULT_ENABLE_FILE_UPLOAD' => '0',
            'DEFAULT_ENABLE_IMAGE_UPLOAD' => '0',
            'DEFAULT_OPENAI_WEB_SEARCH_ENABLED' => '0',
            'DEFAULT_GOOGLE_SEARCH_GROUNDING_ENABLED' => '0',
        ];
    } else {
        $defaults = [
            'DEFAULT_ENABLE_COPY_BUTTON' => BotSettingsManager::DEFAULT_ENABLE_COPY_BUTTON,
            'DEFAULT_ENABLE_FEEDBACK' => BotSettingsManager::DEFAULT_ENABLE_FEEDBACK,
            'DEFAULT_ENABLE_CONVERSATION_STARTERS' => BotSettingsManager::DEFAULT_ENABLE_CONVERSATION_STARTERS,
            'DEFAULT_ENABLE_CONVERSATION_SIDEBAR' => BotSettingsManager::DEFAULT_ENABLE_CONVERSATION_SIDEBAR,
            'DEFAULT_TTS_ENABLED' => BotSettingsManager::DEFAULT_TTS_ENABLED,
            'DEFAULT_ENABLE_VOICE_INPUT' => BotSettingsManager::DEFAULT_ENABLE_VOICE_INPUT,
            'DEFAULT_ENABLE_FILE_UPLOAD' => BotSettingsManager::DEFAULT_ENABLE_FILE_UPLOAD,
            'DEFAULT_ENABLE_IMAGE_UPLOAD' => BotSettingsManager::DEFAULT_ENABLE_IMAGE_UPLOAD,
            'DEFAULT_OPENAI_WEB_SEARCH_ENABLED' => BotSettingsManager::DEFAULT_OPENAI_WEB_SEARCH_ENABLED,
            'DEFAULT_GOOGLE_SEARCH_GROUNDING_ENABLED' => BotSettingsManager::DEFAULT_GOOGLE_SEARCH_GROUNDING_ENABLED,
        ];
    }

    return [
        // Directly derived flags (boolean)
        'popup_enabled'      => ($settings['popup_enabled'] ?? '0') === '1',
        'stream_enabled'     => ($settings['stream_enabled'] ?? '0') === '1',
        'enable_fullscreen'  => ($settings['enable_fullscreen'] ?? '0') === '1',
        'enable_download'    => ($settings['enable_download'] ?? '0') === '1',
        'enable_copy_button' => ($settings['enable_copy_button'] ?? $defaults['DEFAULT_ENABLE_COPY_BUTTON']) === '1',
        'enable_feedback'    => ($settings['enable_feedback'] ?? $defaults['DEFAULT_ENABLE_FEEDBACK']) === '1',
        'enable_voice_input_ui' => ($settings['enable_voice_input'] ?? $defaults['DEFAULT_ENABLE_VOICE_INPUT']) === '1', // Direct UI flag

        // Intermediate setting values (to be combined with addon status)
        'enable_starters_setting' => ($settings['enable_conversation_starters'] ?? $defaults['DEFAULT_ENABLE_CONVERSATION_STARTERS']) === '1',
        'enable_sidebar_setting'  => ($settings['enable_conversation_sidebar'] ?? $defaults['DEFAULT_ENABLE_CONVERSATION_SIDEBAR']) === '1',
        'enable_tts_setting'      => ($settings['tts_enabled'] ?? $defaults['DEFAULT_TTS_ENABLED']) === '1',
        'enable_file_upload_setting'  => ($settings['enable_file_upload'] ?? $defaults['DEFAULT_ENABLE_FILE_UPLOAD']) === '1',
        'enable_image_upload_setting' => ($settings['enable_image_upload'] ?? $defaults['DEFAULT_ENABLE_IMAGE_UPLOAD']) === '1',
        'enable_realtime_voice_setting' => ($settings['enable_realtime_voice'] ?? '0') === '1',
        'allow_openai_web_search_tool_setting'  => ($settings['openai_web_search_enabled'] ?? $defaults['DEFAULT_OPENAI_WEB_SEARCH_ENABLED']) === '1',
        'allow_google_search_grounding_setting' => ($settings['google_search_grounding_enabled'] ?? $defaults['DEFAULT_GOOGLE_SEARCH_GROUNDING_ENABLED']) === '1',
    ];
}