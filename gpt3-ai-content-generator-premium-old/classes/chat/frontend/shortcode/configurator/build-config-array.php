<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/chat/frontend/shortcode/configurator/build-config-array.php
// Status: MODIFIED

namespace WPAICG\Chat\Frontend\Shortcode\ConfiguratorMethods;

use WPAICG\Chat\Storage\BotSettingsManager;
use WPAICG\AIPKit_Providers;
use WPAICG\Lib\Addons\AIPKit_Consent_Compliance; // For consent required check
use WPAICG\aipkit_dashboard; // For addon/plan status checks

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Load the individual logic files this orchestrator depends on
require_once __DIR__ . '/get-client-ip.php';
require_once __DIR__ . '/get-conversation-starters.php';
require_once __DIR__ . '/get-consent-settings.php';
require_once __DIR__ . '/get-tts-settings.php';
require_once __DIR__ . '/get-grounding-flags.php';
require_once __DIR__ . '/get-text-labels.php';

/**
 * Main orchestrator function to build the frontend configuration array.
 * This replaces the body of the original Configurator::prepare_config().
 * UPDATED: Include custom theme settings.
 * ADDED: Logging and a defensive fix for bubble_border_radius in custom theme settings.
 * ADDED: fileUploadEnabledUI flag.
 * MODIFIED: Added vectorStoreProvider to the returned config.
 *
 * @param int $bot_id
 * @param \WP_Post $bot_post
 * @param array $settings Bot settings.
 * @param array $feature_flags Determined feature flags.
 * @return array Frontend configuration data.
 */
function build_config_array_logic(int $bot_id, \WP_Post $bot_post, array $settings, array $feature_flags): array
{
    // Ensure dependencies are loaded
    if (!class_exists(BotSettingsManager::class)) {
        $bsm_path = WPAICG_PLUGIN_DIR . 'classes/chat/storage/class-aipkit_bot_settings_manager.php';
        if (file_exists($bsm_path)) {
            require_once $bsm_path;
        }
    }
    if (!class_exists(AIPKit_Providers::class)) {
        $providers_path = WPAICG_PLUGIN_DIR . 'classes/dashboard/class-aipkit_providers.php';
        if (file_exists($providers_path)) {
            require_once $providers_path;
        }
    }
    if (!class_exists(AIPKit_Consent_Compliance::class)) {
        $consent_path = WPAICG_LIB_DIR . 'addons/class-aipkit-consent-compliance.php';
        if (file_exists($consent_path)) {
            require_once $consent_path;
        }
    }
    if (!class_exists(aipkit_dashboard::class)) {
        $dashboard_path = WPAICG_PLUGIN_DIR . 'classes/dashboard/class-aipkit_dashboard.php';
        if (file_exists($dashboard_path)) {
            require_once $dashboard_path;
        }
    }

    $client_ip = get_client_ip_logic();
    $starters_array = get_conversation_starters_logic($settings, $feature_flags['starters_ui_enabled']);
    $consent_texts = get_consent_settings_logic();
    $tts_settings = get_tts_settings_logic($settings);
    $google_grounding_settings = get_google_grounding_settings_logic($settings, $feature_flags);

    $nonce = wp_create_nonce('aipkit_frontend_chat_nonce');

    $consent_required = false;
    if (class_exists(AIPKit_Consent_Compliance::class)) {
        $consent_required = AIPKit_Consent_Compliance::is_required();
    }

    $current_post_id = 0;
    if (is_singular()) {
        $current_post_id = get_the_ID();
    }

    $image_triggers = $settings['image_triggers'] ?? (class_exists(BotSettingsManager::class) ? BotSettingsManager::DEFAULT_IMAGE_TRIGGERS : '/image');
    if (empty($image_triggers)) {
        $image_triggers = (class_exists(BotSettingsManager::class) ? BotSettingsManager::DEFAULT_IMAGE_TRIGGERS : '/image');
    }

    $enable_openai_conv_state = ($settings['openai_conversation_state_enabled'] ?? (class_exists(BotSettingsManager::class) ? BotSettingsManager::DEFAULT_OPENAI_CONVERSATION_STATE_ENABLED : '0')) === '1';
    $allow_openai_web_search_tool = $feature_flags['allowWebSearchTool'] ?? false;

    $text_labels = get_text_labels_logic($settings, $consent_texts);

    // --- Add custom theme settings to frontend config ---
    $custom_theme_settings_for_js = [];
    if (($settings['theme'] ?? 'light') === 'custom') {
        $custom_theme_settings_for_js = $settings['custom_theme_settings'] ?? [];
        $custom_theme_settings_for_js = array_filter($custom_theme_settings_for_js, function ($value) {
            return $value !== '';
        });
    }
    // --- END ---
    
    // --- ADDED: Direct Voice Mode flag ---
    $direct_voice_mode_flag = ($feature_flags['popup_enabled'] ?? false) &&
                              ($feature_flags['enable_realtime_voice_ui'] ?? false) &&
                              (($settings['direct_voice_mode'] ?? '0') === '1');
    // --- END ADDED ---

    return [
        'botId' => $bot_id,
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => $nonce,
        'postId' => $current_post_id,
        'theme' => $settings['theme'] ?? 'light',
        'popupEnabled' => $feature_flags['popup_enabled'],
        'popupPosition' => $settings['popup_position'] ?? 'bottom-right',
        'popupDelay' => absint($settings['popup_delay'] ?? (class_exists(BotSettingsManager::class) ? BotSettingsManager::DEFAULT_POPUP_DELAY : 1)),
        'popupIconType' => $settings['popup_icon_type'] ?? (class_exists(BotSettingsManager::class) ? BotSettingsManager::DEFAULT_POPUP_ICON_TYPE : 'default'),
        'popupIconStyle' => $settings['popup_icon_style'] ?? (class_exists(BotSettingsManager::class) ? BotSettingsManager::DEFAULT_POPUP_ICON_STYLE : 'circle'),
        'popupIconValue' => $settings['popup_icon_value'] ?? (class_exists(BotSettingsManager::class) ? BotSettingsManager::DEFAULT_POPUP_ICON_VALUE : 'chat-bubble'),
        // NEW: Icon size for trigger
        'popupIconSize' => (function() use ($settings) {
            $val = $settings['popup_icon_size'] ?? 'medium';
            return in_array($val, ['small','medium','large','xlarge'], true) ? $val : 'medium';
        })(),
        // --- NEW: Popup Hint/Bubble above trigger ---
        'popupLabelEnabled' => ($settings['popup_label_enabled'] ?? '0') === '1',
        'popupLabelText' => isset($settings['popup_label_text']) ? wp_strip_all_tags((string)$settings['popup_label_text']) : '',
        // Modes: 'always', 'on_delay', 'until_open', 'until_dismissed'
        'popupLabelMode' => in_array(($settings['popup_label_mode'] ?? 'on_delay'), ['always','on_delay','until_open','until_dismissed'], true) ? $settings['popup_label_mode'] : 'on_delay',
        'popupLabelDelaySeconds' => max(0, absint($settings['popup_label_delay_seconds'] ?? 2)),
        // 0 = never auto-hide
        'popupLabelAutoHideSeconds' => max(0, absint($settings['popup_label_auto_hide_seconds'] ?? 0)),
        'popupLabelDismissible' => ($settings['popup_label_dismissible'] ?? '1') === '1',
        // Frequency: 'always', 'once_per_session', 'once_per_visitor'
        'popupLabelFrequency' => in_array(($settings['popup_label_frequency'] ?? 'once_per_visitor'), ['always','once_per_session','once_per_visitor'], true) ? $settings['popup_label_frequency'] : 'once_per_visitor',
        'popupLabelShowOnMobile' => ($settings['popup_label_show_on_mobile'] ?? '1') === '1',
        'popupLabelShowOnDesktop' => ($settings['popup_label_show_on_desktop'] ?? '1') === '1',
        // Bump this (any string) to re-show hints for everyone
        'popupLabelVersion' => isset($settings['popup_label_version']) ? (string)$settings['popup_label_version'] : '',
        // NEW: Popup hint size option passed to frontend
        'popupLabelSize' => in_array(($settings['popup_label_size'] ?? 'medium'), ['small','medium','large','xlarge'], true) ? $settings['popup_label_size'] : 'medium',
        'streamEnabled' => $feature_flags['stream_enabled'],
        'footerText' => $settings['footer_text'] ?? '',
        'enableFullscreen' => $feature_flags['enable_fullscreen'],
        'enableDownload' => $feature_flags['enable_download'],
        'enableCopyButton' => $feature_flags['enable_copy_button'],
        'enableFeedback' => $feature_flags['feedback_ui_enabled'],
        'enableSidebar' => $feature_flags['sidebar_ui_enabled'],
        'pdfDownloadActive' => $feature_flags['pdf_ui_enabled'],
        'headerName' => $bot_post->post_title ?: '',
        'enableStarters' => $feature_flags['starters_ui_enabled'],
        'starters' => $starters_array,
        'userIp' => $client_ip,
        'requireConsentCompliance' => $consent_required,
        'ttsEnabled' => $feature_flags['tts_ui_enabled'],
        'ttsAutoPlay' => $tts_settings['tts_auto_play'],
        'ttsProvider' => $tts_settings['tts_provider'],
        'ttsVoiceId' => $tts_settings['tts_voice_id'],
        'ttsOpenAIModelId' => $tts_settings['tts_openai_model_id'],
        'ttsElevenLabsModelId' => $tts_settings['tts_elevenlabs_model_id'],
        'enableVoiceInputUI' => $feature_flags['enable_voice_input_ui'] ?? false,
        'enableRealtimeVoiceUI' => $feature_flags['enable_realtime_voice_ui'] ?? false,
        'directVoiceMode' => $direct_voice_mode_flag,
        'realtimeModel' => $settings['realtime_model'] ?? 'gpt-4o-realtime-preview',
        'sttProvider' => $settings['stt_provider'] ?? (class_exists(BotSettingsManager::class) ? BotSettingsManager::DEFAULT_STT_PROVIDER : 'OpenAI'),
        'imageTriggers' => $image_triggers,
        'fileUploadEnabledUI' => $feature_flags['file_upload_ui_enabled'] ?? false,
        'imageUploadEnabledUI' => $feature_flags['image_upload_ui_enabled'] ?? false,
        'inputActionButtonEnabled' => $feature_flags['input_action_button_enabled'] ?? false,
        'provider' => $settings['provider'] ?? 'OpenAI', // This is the Main AI provider for the bot
        // This line ensures vectorStoreProvider (e.g., 'openai', 'pinecone', 'qdrant') is passed to JS
        'vectorStoreProvider' => $settings['vector_store_provider'] ?? (class_exists(BotSettingsManager::class) ? BotSettingsManager::DEFAULT_VECTOR_STORE_PROVIDER : 'openai'),
        'enableOpenAIConversationState' => $enable_openai_conv_state,
        'allowWebSearchTool' => $allow_openai_web_search_tool,
        'allowGoogleSearchGrounding' => $google_grounding_settings['allowGoogleSearchGrounding'],
        'googleGroundingMode' => $google_grounding_settings['googleGroundingMode'],
        'googleGroundingDynamicThreshold' => $google_grounding_settings['googleGroundingDynamicThreshold'],
        'customThemeSettings' => $custom_theme_settings_for_js,
        'text' => $text_labels,
        'customTypingText' => (function() use ($settings, $text_labels) {
            $txt = isset($settings['custom_typing_text']) ? trim((string)$settings['custom_typing_text']) : '';
            // If empty, frontend shows dots; no auto-fallback
            return $txt;
        })(),
    ];
}
