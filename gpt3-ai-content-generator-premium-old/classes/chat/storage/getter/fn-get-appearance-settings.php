<?php

// File: classes/chat/storage/getter/fn-get-appearance-settings.php
// Status: MODIFIED

namespace WPAICG\Chat\Storage\GetterMethods;

use WPAICG\Chat\Storage\BotSettingsManager; // For default constants

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Retrieves appearance-related settings.
 * UPDATED: Includes custom theme settings.
 * ADDED: Logging and a defensive fix for bubble_border_radius.
 *
 * @param int $bot_id The ID of the bot post.
 * @param string $bot_name The name of the bot (for default popup icon value).
 * @param callable $get_meta_fn A function to retrieve post meta.
 * @return array Associative array of appearance settings.
 */
function get_appearance_settings_logic(int $bot_id, string $bot_name, callable $get_meta_fn): array
{
    $settings = [];
    $custom_theme_defaults = [];

    if (!class_exists(BotSettingsManager::class)) {
        $bsm_path = dirname(__DIR__) . '/class-aipkit_bot_settings_manager.php';
        if (file_exists($bsm_path)) {
            require_once $bsm_path;
            $custom_theme_defaults = BotSettingsManager::get_custom_theme_defaults();
        } else {
            // Define minimal defaults if class is missing to avoid undefined index errors later
            $custom_theme_defaults = [
                 'font_family' => 'inherit', 'bubble_border_radius' => 18,
                 'container_bg_color' => '#FFFFFF', /* ... other minimal defaults */
                 // --- NEW DIMENSION DEFAULTS (Fallback) ---
                 'container_max_width' => 650, 'popup_width' => 400,
                 'container_height' => 450, 'container_max_height' => 70,
                 'container_min_height' => 250, 'popup_height' => 450,
                 'popup_min_height' => 250, 'popup_max_height' => 70,
                 // --- END NEW DIMENSION DEFAULTS (Fallback) ---
            ];
        }
    } else {
        $custom_theme_defaults = BotSettingsManager::get_custom_theme_defaults();
    }


    // --- MODIFIED: Add new themes to validation ---
    $valid_themes = ['light', 'dark', 'custom', 'chatgpt'];
    // --- END MODIFICATION ---
    $settings['theme'] = in_array($get_meta_fn('_aipkit_theme', 'light'), $valid_themes)
        ? $get_meta_fn('_aipkit_theme', 'light')
        : 'light';
    $settings['footer_text'] = $get_meta_fn('_aipkit_footer_text');
    $settings['input_placeholder'] = $get_meta_fn('_aipkit_input_placeholder', __('Type your message...', 'gpt3-ai-content-generator'));
    $settings['enable_fullscreen'] = in_array($get_meta_fn('_aipkit_enable_fullscreen', '0'), ['0','1'])
        ? $get_meta_fn('_aipkit_enable_fullscreen', '0')
        : '0';
    $settings['enable_download'] = in_array($get_meta_fn('_aipkit_enable_download', '0'), ['0','1'])
        ? $get_meta_fn('_aipkit_enable_download', '0')
        : '0';
    $settings['enable_copy_button'] = in_array($get_meta_fn('_aipkit_enable_copy_button', BotSettingsManager::DEFAULT_ENABLE_COPY_BUTTON), ['0','1'])
        ? $get_meta_fn('_aipkit_enable_copy_button', BotSettingsManager::DEFAULT_ENABLE_COPY_BUTTON)
        : BotSettingsManager::DEFAULT_ENABLE_COPY_BUTTON;
    $settings['enable_feedback'] = in_array($get_meta_fn('_aipkit_enable_feedback', BotSettingsManager::DEFAULT_ENABLE_FEEDBACK), ['0','1'])
        ? $get_meta_fn('_aipkit_enable_feedback', BotSettingsManager::DEFAULT_ENABLE_FEEDBACK)
        : BotSettingsManager::DEFAULT_ENABLE_FEEDBACK;
    $settings['enable_conversation_sidebar'] = in_array($get_meta_fn('_aipkit_enable_conversation_sidebar', BotSettingsManager::DEFAULT_ENABLE_CONVERSATION_SIDEBAR), ['0','1'])
        ? $get_meta_fn('_aipkit_enable_conversation_sidebar', BotSettingsManager::DEFAULT_ENABLE_CONVERSATION_SIDEBAR)
        : BotSettingsManager::DEFAULT_ENABLE_CONVERSATION_SIDEBAR;

    // Typing indicator customization
    $settings['custom_typing_text'] = $get_meta_fn('_aipkit_custom_typing_text', BotSettingsManager::DEFAULT_CUSTOM_TYPING_TEXT);

    // Popup settings
    $settings['popup_enabled'] = in_array($get_meta_fn('_aipkit_popup_enabled', '0'), ['0','1'])
        ? $get_meta_fn('_aipkit_popup_enabled', '0')
        : '0';
    $settings['popup_position'] = in_array($get_meta_fn('_aipkit_popup_position', 'bottom-right'), ['bottom-right','bottom-left','top-right','top-left'])
        ? $get_meta_fn('_aipkit_popup_position', 'bottom-right')
        : 'bottom-right';
    $settings['popup_delay'] = absint($get_meta_fn('_aipkit_popup_delay', BotSettingsManager::DEFAULT_POPUP_DELAY));
    $settings['site_wide_enabled'] = in_array($get_meta_fn('_aipkit_site_wide_enabled', '0'), ['0','1'])
        ? $get_meta_fn('_aipkit_site_wide_enabled', '0')
        : '0';
    // NEW: Popup icon size
    $allowed_icon_sizes = ['small','medium','large','xlarge'];
    $icon_size_meta = $get_meta_fn('_aipkit_popup_icon_size', BotSettingsManager::DEFAULT_POPUP_ICON_SIZE);
    $settings['popup_icon_size'] = in_array($icon_size_meta, $allowed_icon_sizes, true) ? $icon_size_meta : BotSettingsManager::DEFAULT_POPUP_ICON_SIZE;
    $settings['popup_icon_style'] = $get_meta_fn('_aipkit_popup_icon_style', BotSettingsManager::DEFAULT_POPUP_ICON_STYLE);
    if (!in_array($settings['popup_icon_style'], ['circle', 'square', 'none'])) {
        $settings['popup_icon_style'] = BotSettingsManager::DEFAULT_POPUP_ICON_STYLE;
    }
    $settings['popup_icon_type'] = $get_meta_fn('_aipkit_popup_icon_type', BotSettingsManager::DEFAULT_POPUP_ICON_TYPE);
    $settings['popup_icon_value'] = $get_meta_fn('_aipkit_popup_icon_value', BotSettingsManager::DEFAULT_POPUP_ICON_VALUE);
    if (!in_array($settings['popup_icon_type'], ['default', 'custom'])) {
        $settings['popup_icon_type'] = BotSettingsManager::DEFAULT_POPUP_ICON_TYPE;
    }
    if ($settings['popup_icon_type'] === 'default' && !in_array($settings['popup_icon_value'], ['chat-bubble', 'plus', 'question-mark'])) {
        $settings['popup_icon_value'] = BotSettingsManager::DEFAULT_POPUP_ICON_VALUE;
    }
    if ($settings['popup_icon_type'] === 'custom' && empty($settings['popup_icon_value'])) {
        $settings['popup_icon_value'] = '';
    }

    // --- NEW: Popup Label (Hint) settings ---
    $settings['popup_label_enabled'] = in_array($get_meta_fn('_aipkit_popup_label_enabled', BotSettingsManager::DEFAULT_POPUP_LABEL_ENABLED), ['0','1'])
        ? $get_meta_fn('_aipkit_popup_label_enabled', BotSettingsManager::DEFAULT_POPUP_LABEL_ENABLED)
        : BotSettingsManager::DEFAULT_POPUP_LABEL_ENABLED;
    $settings['popup_label_text'] = $get_meta_fn('_aipkit_popup_label_text', BotSettingsManager::DEFAULT_POPUP_LABEL_TEXT);
    $allowed_modes = ['always','on_delay','until_open','until_dismissed'];
    $label_mode = $get_meta_fn('_aipkit_popup_label_mode', BotSettingsManager::DEFAULT_POPUP_LABEL_MODE);
    $settings['popup_label_mode'] = in_array($label_mode, $allowed_modes, true) ? $label_mode : BotSettingsManager::DEFAULT_POPUP_LABEL_MODE;
    $settings['popup_label_delay_seconds'] = absint($get_meta_fn('_aipkit_popup_label_delay_seconds', BotSettingsManager::DEFAULT_POPUP_LABEL_DELAY_SECONDS));
    $settings['popup_label_auto_hide_seconds'] = absint($get_meta_fn('_aipkit_popup_label_auto_hide_seconds', BotSettingsManager::DEFAULT_POPUP_LABEL_AUTO_HIDE_SECONDS));
    $settings['popup_label_dismissible'] = in_array($get_meta_fn('_aipkit_popup_label_dismissible', BotSettingsManager::DEFAULT_POPUP_LABEL_DISMISSIBLE), ['0','1'])
        ? $get_meta_fn('_aipkit_popup_label_dismissible', BotSettingsManager::DEFAULT_POPUP_LABEL_DISMISSIBLE)
        : BotSettingsManager::DEFAULT_POPUP_LABEL_DISMISSIBLE;
    $allowed_freq = ['always','once_per_session','once_per_visitor'];
    $label_freq = $get_meta_fn('_aipkit_popup_label_frequency', BotSettingsManager::DEFAULT_POPUP_LABEL_FREQUENCY);
    $settings['popup_label_frequency'] = in_array($label_freq, $allowed_freq, true) ? $label_freq : BotSettingsManager::DEFAULT_POPUP_LABEL_FREQUENCY;
    $settings['popup_label_show_on_mobile'] = in_array($get_meta_fn('_aipkit_popup_label_show_on_mobile', BotSettingsManager::DEFAULT_POPUP_LABEL_SHOW_ON_MOBILE), ['0','1'])
        ? $get_meta_fn('_aipkit_popup_label_show_on_mobile', BotSettingsManager::DEFAULT_POPUP_LABEL_SHOW_ON_MOBILE)
        : BotSettingsManager::DEFAULT_POPUP_LABEL_SHOW_ON_MOBILE;
    $settings['popup_label_show_on_desktop'] = in_array($get_meta_fn('_aipkit_popup_label_show_on_desktop', BotSettingsManager::DEFAULT_POPUP_LABEL_SHOW_ON_DESKTOP), ['0','1'])
        ? $get_meta_fn('_aipkit_popup_label_show_on_desktop', BotSettingsManager::DEFAULT_POPUP_LABEL_SHOW_ON_DESKTOP)
        : BotSettingsManager::DEFAULT_POPUP_LABEL_SHOW_ON_DESKTOP;
    $settings['popup_label_version'] = $get_meta_fn('_aipkit_popup_label_version', BotSettingsManager::DEFAULT_POPUP_LABEL_VERSION);
    // NEW: Popup hint size
    $allowed_sizes = ['small','medium','large','xlarge'];
    $label_size = $get_meta_fn('_aipkit_popup_label_size', BotSettingsManager::DEFAULT_POPUP_LABEL_SIZE);
    $settings['popup_label_size'] = in_array($label_size, $allowed_sizes, true) ? $label_size : BotSettingsManager::DEFAULT_POPUP_LABEL_SIZE;
    // --- END NEW ---

    // --- Retrieve Custom Theme Settings ---
    $custom_theme_settings_retrieved = [];
    foreach (array_keys($custom_theme_defaults) as $key) {
        if (strpos($key, '_placeholder') !== false) {
            continue;
        }
        $meta_key_name = '_aipkit_cts_' . $key;
        $value_from_meta = $get_meta_fn($meta_key_name);

        if ($value_from_meta === '' || $value_from_meta === null) {
            $custom_theme_settings_retrieved[$key] = $custom_theme_defaults[$key];
        } else {
            // Specific handling for numeric dimension settings
            if (in_array($key, [
                'bubble_border_radius', 'container_max_width', 'popup_width',
                'container_height', 'container_min_height',
                'popup_height', 'popup_min_height'
            ])) {
                $custom_theme_settings_retrieved[$key] = is_numeric($value_from_meta) ? max(0, absint($value_from_meta)) : $custom_theme_defaults[$key];
            } elseif (in_array($key, ['container_max_height', 'popup_max_height'])) {
                $custom_theme_settings_retrieved[$key] = is_numeric($value_from_meta) ? max(1, min(absint($value_from_meta), 100)) : $custom_theme_defaults[$key];
            } else {
                $custom_theme_settings_retrieved[$key] = $value_from_meta;
            }
        }
    }
    $settings['custom_theme_settings'] = $custom_theme_settings_retrieved;
    // --- END Retrieve Custom Theme Settings ---

    return $settings;
}
