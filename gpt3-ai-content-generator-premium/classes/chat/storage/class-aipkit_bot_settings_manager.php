<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/chat/storage/class-aipkit_bot_settings_manager.php
// Status: MODIFIED

/**
 * AIPKit Chatbot - Settings Manager (Refactored)
 * Handles getting/saving/defaulting chatbot settings stored as post meta.
 * Delegates actual logic to new helper classes.
 */

namespace WPAICG\Chat\Storage;

use WPAICG\Chat\Admin\AdminSetup;
use WPAICG\Chat\Storage\SiteWideBotManager;
// use WPAICG\Chat\Storage\BotSettingsManager; // Self-reference not needed
use WP_Error;
use WPAICG\Chat\Storage\AIPKit_Bot_Settings_Getter;
use WPAICG\Chat\Storage\AIPKit_Bot_Settings_Saver; // Use the new saver class
use WPAICG\Chat\Storage\AIPKit_Bot_Settings_Initializer;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class BotSettingsManager
{
    // --- Constants for Default Settings ---
    public const DEFAULT_TEMPERATURE = 1.0;
    public const DEFAULT_MAX_COMPLETION_TOKENS = 4000;
    public const DEFAULT_MAX_MESSAGES = 15;
    public const DEFAULT_STREAM_ENABLED = '1'; // ADDED: Default for stream mode
    public const DEFAULT_ENABLE_COPY_BUTTON = '1';
    public const DEFAULT_ENABLE_FEEDBACK = '1';
    public const DEFAULT_POPUP_DELAY = 1;
    public const DEFAULT_ENABLE_CONVERSATION_STARTERS = '1';
    public const DEFAULT_ENABLE_CONVERSATION_SIDEBAR = '0';
    public const DEFAULT_POPUP_ICON_TYPE = 'default';
    public const DEFAULT_POPUP_ICON_STYLE = 'circle';
    public const DEFAULT_POPUP_ICON_VALUE = 'chat-bubble';
    public const DEFAULT_POPUP_ICON_SIZE = 'medium'; // allowed: small|medium|large|xlarge
    // --- Popup Hint/Label Defaults ---
    public const DEFAULT_POPUP_LABEL_ENABLED = '0';
    public const DEFAULT_POPUP_LABEL_TEXT = '';
    public const DEFAULT_POPUP_LABEL_MODE = 'on_delay'; // allowed: always|on_delay|until_open|until_dismissed
    public const DEFAULT_POPUP_LABEL_DELAY_SECONDS = 2;
    public const DEFAULT_POPUP_LABEL_AUTO_HIDE_SECONDS = 0; // 0 = never auto-hide
    public const DEFAULT_POPUP_LABEL_DISMISSIBLE = '1';
    public const DEFAULT_POPUP_LABEL_FREQUENCY = 'once_per_visitor'; // allowed: always|once_per_session|once_per_visitor
    public const DEFAULT_POPUP_LABEL_SHOW_ON_MOBILE = '1';
    public const DEFAULT_POPUP_LABEL_SHOW_ON_DESKTOP = '1';
    public const DEFAULT_POPUP_LABEL_VERSION = '';
    public const DEFAULT_POPUP_LABEL_SIZE = 'medium'; // allowed: small|medium|large|xlarge
    public const DEFAULT_CONTENT_AWARE_ENABLED = '0';
    public const DEFAULT_TOKEN_GUEST_LIMIT = null;
    public const DEFAULT_TOKEN_USER_LIMIT = null;
    public const DEFAULT_TOKEN_RESET_PERIOD = 'never';
    public const DEFAULT_TOKEN_LIMIT_MESSAGE = 'You have reached your token limit for this period.';
    public const DEFAULT_TOKEN_LIMIT_MODE = 'general';
    public const DEFAULT_TTS_ENABLED = '0';
    public const DEFAULT_TTS_PROVIDER = 'Google';
    public const DEFAULT_TTS_OPENAI_MODEL_ID = 'tts-1';
    public const DEFAULT_TTS_ELEVENLABS_MODEL_ID = '';
    public const DEFAULT_TTS_AUTO_PLAY = '0';
    public const DEFAULT_ENABLE_VOICE_INPUT = '1';
    public const DEFAULT_STT_PROVIDER = 'OpenAI';
    public const DEFAULT_STT_OPENAI_MODEL_ID = 'whisper-1';
    public const DEFAULT_STT_AZURE_MODEL_ID = '';
    public const DEFAULT_IMAGE_TRIGGERS = '/image, /generate';
    public const DEFAULT_CHAT_IMAGE_MODEL_ID = 'gpt-image-1';
    public const DEFAULT_ENABLE_FILE_UPLOAD = '0';
    public const DEFAULT_ENABLE_IMAGE_UPLOAD = '0';
    public const DEFAULT_OPENAI_CONVERSATION_STATE_ENABLED = '0';
    // --- Typing Indicator Defaults ---
    public const DEFAULT_CUSTOM_TYPING_TEXT = '';
    // --- Vector Store Constants ---
    public const DEFAULT_ENABLE_VECTOR_STORE = '0';
    public const DEFAULT_VECTOR_STORE_PROVIDER = 'openai';
    public const DEFAULT_OPENAI_VECTOR_STORE_ID = ''; // Legacy, will be array now
    public const DEFAULT_VECTOR_STORE_TOP_K = 3;
    public const DEFAULT_VECTOR_STORE_CONFIDENCE_THRESHOLD = 20; // NEW
    // --- Pinecone & Embedding Specific Constants ---
    public const DEFAULT_PINECONE_INDEX_NAME = '';
    public const DEFAULT_VECTOR_EMBEDDING_PROVIDER = 'openai';
    public const DEFAULT_VECTOR_EMBEDDING_MODEL = 'text-embedding-3-small';
    // --- Qdrant Specific Constants ---
    public const DEFAULT_QDRANT_COLLECTION_NAME = '';
    // --- OpenAI Web Search Constants ---
    public const DEFAULT_OPENAI_WEB_SEARCH_ENABLED = '0'; // Master switch in bot settings
    public const DEFAULT_OPENAI_WEB_SEARCH_CONTEXT_SIZE = 'medium';
    public const DEFAULT_OPENAI_WEB_SEARCH_LOC_TYPE = 'none';
    // --- NEW: Google Search Grounding Constants ---
    public const DEFAULT_GOOGLE_SEARCH_GROUNDING_ENABLED = '0'; // Master switch for bot
    public const DEFAULT_GOOGLE_GROUNDING_MODE = 'DEFAULT_MODE'; // Default: use Search as Tool for Gemini 2.0+, Retrieval for 1.5 Flash
    public const DEFAULT_GOOGLE_GROUNDING_DYNAMIC_THRESHOLD = 0.3;
    // --- NEW: Realtime Voice Agent Defaults ---
    public const DEFAULT_ENABLE_REALTIME_VOICE = '0';
    public const DEFAULT_DIRECT_VOICE_MODE = '0';
    public const DEFAULT_REALTIME_MODEL = 'gpt-4o-realtime-preview';
    public const DEFAULT_REALTIME_VOICE = 'alloy';
    public const DEFAULT_TURN_DETECTION = 'server_vad';
    public const DEFAULT_SPEED = 1.0;
    public const DEFAULT_INPUT_AUDIO_FORMAT = 'pcm16';
    public const DEFAULT_OUTPUT_AUDIO_FORMAT = 'pcm16';
    public const DEFAULT_INPUT_AUDIO_NOISE_REDUCTION = '1';
    // --- NEW: Reasoning Effort ---
    public const DEFAULT_REASONING_EFFORT = 'low';
    // --- END NEW ---

    // --- NEW: Custom Theme Defaults ---
    public const DEFAULT_CUSTOM_THEME_FONT_FAMILY = 'inherit';
    public const DEFAULT_CUSTOM_THEME_BUBBLE_BORDER_RADIUS = 18;
    public const DEFAULT_CTS_CONTAINER_BG_COLOR = '#FFFFFF';
    public const DEFAULT_CTS_CONTAINER_TEXT_COLOR = '#2D3748';
    public const DEFAULT_CTS_CONTAINER_BORDER_COLOR = '#E1E7EF';
    public const DEFAULT_CTS_CONTAINER_BORDER_RADIUS = 6; // Assuming this is a new general radius
    public const DEFAULT_CTS_HEADER_BG_COLOR = '#FFFFFF';
    public const DEFAULT_CTS_HEADER_TEXT_COLOR = '#718096';
    public const DEFAULT_CTS_HEADER_BORDER_COLOR = '#E1E7EF';
    public const DEFAULT_CTS_MESSAGES_BG_COLOR = '#F7F9FC';
    public const DEFAULT_CTS_MESSAGES_SCROLLBAR_THUMB_COLOR = '#E1E7EF'; // Actual color default
    public const DEFAULT_CTS_MESSAGES_SCROLLBAR_TRACK_COLOR = 'transparent'; // Actual color default
    public const DEFAULT_CTS_BOT_BUBBLE_BG_COLOR = '#E9ECEF';
    public const DEFAULT_CTS_BOT_BUBBLE_TEXT_COLOR = '#2D3748';
    public const DEFAULT_CTS_USER_BUBBLE_BG_COLOR = '#4A6FA5';
    public const DEFAULT_CTS_USER_BUBBLE_TEXT_COLOR = '#FFFFFF';
    public const DEFAULT_CTS_INPUT_AREA_BG_COLOR = '#FFFFFF';
    public const DEFAULT_CTS_INPUT_AREA_BORDER_COLOR = '#E1E7EF'; // Actual color default
    public const DEFAULT_CTS_INPUT_WRAPPER_BG_COLOR = '#F7F9FC';
    public const DEFAULT_CTS_INPUT_WRAPPER_BORDER_COLOR = '#E1E7EF'; // Actual color default
    public const DEFAULT_CTS_INPUT_TEXT_COLOR = '#2D3748';
    public const DEFAULT_CTS_INPUT_PLACEHOLDER_COLOR = '#9CA3AF'; // Actual color default
    public const DEFAULT_CTS_INPUT_FOCUS_BORDER_COLOR = '#4A6FA5'; // Actual color default
    public const DEFAULT_CTS_INPUT_FOCUS_SHADOW_COLOR = 'rgba(74, 111, 165, 0.2)'; // Actual color default
    public const DEFAULT_CTS_SEND_BUTTON_BG_COLOR = '#4A6FA5';
    public const DEFAULT_CTS_SEND_BUTTON_TEXT_COLOR = '#FFFFFF';
    public const DEFAULT_CTS_ACTION_BUTTON_BG_COLOR = '#FFFFFF';
    public const DEFAULT_CTS_ACTION_BUTTON_COLOR = '#718096';
    public const DEFAULT_CTS_ACTION_BUTTON_BORDER_COLOR = '#D1D5DB';
    public const DEFAULT_CTS_ACTION_BUTTON_HOVER_BG_COLOR = '#F0F4F8';
    public const DEFAULT_CTS_ACTION_BUTTON_HOVER_COLOR = '#2D3748';
    public const DEFAULT_CTS_ACTION_BUTTON_HOVER_BORDER_COLOR = '#61A0FF'; // Actual color default
    public const DEFAULT_CTS_FOOTER_BG_COLOR = '#FFFFFF';
    public const DEFAULT_CTS_FOOTER_TEXT_COLOR = '#718096';
    public const DEFAULT_CTS_FOOTER_BORDER_COLOR = '#E1E7EF';
    public const DEFAULT_CTS_SIDEBAR_BG_COLOR = '#FFFFFF';
    public const DEFAULT_CTS_SIDEBAR_TEXT_COLOR = '#718096';
    public const DEFAULT_CTS_SIDEBAR_BORDER_COLOR = '#E1E7EF';
    public const DEFAULT_CTS_SIDEBAR_ACTIVE_BG_COLOR = '#EDF2F7';
    public const DEFAULT_CTS_SIDEBAR_ACTIVE_TEXT_COLOR = '#4A6FA5';
    public const DEFAULT_CTS_SIDEBAR_HOVER_BG_COLOR = '#F0F4F8';
    public const DEFAULT_CTS_SIDEBAR_HOVER_TEXT_COLOR = '#2D3748';
    public const DEFAULT_CTS_ACTION_MENU_BG_COLOR = '#FFFFFF';
    public const DEFAULT_CTS_ACTION_MENU_BORDER_COLOR = '#E1E7EF';
    public const DEFAULT_CTS_ACTION_MENU_ITEM_TEXT_COLOR = '#2D3748';
    public const DEFAULT_CTS_ACTION_MENU_ITEM_HOVER_BG_COLOR = '#F0F4F8';
    public const DEFAULT_CTS_ACTION_MENU_ITEM_HOVER_TEXT_COLOR = '#4A6FA5';
    // --- NEW DIMENSION DEFAULTS ---
    public const DEFAULT_CTS_CONTAINER_MAX_WIDTH = 650; // px
    public const DEFAULT_CTS_POPUP_WIDTH = 400;         // px
    public const DEFAULT_CTS_CONTAINER_HEIGHT = 450;    // px
    public const DEFAULT_CTS_CONTAINER_MAX_HEIGHT = 70; // vh (number only)
    public const DEFAULT_CTS_CONTAINER_MIN_HEIGHT = 250;  // px
    public const DEFAULT_CTS_POPUP_HEIGHT = 450;        // px (can inherit from container_height)
    public const DEFAULT_CTS_POPUP_MIN_HEIGHT = 250;    // px (can inherit)
    public const DEFAULT_CTS_POPUP_MAX_HEIGHT = 70;     // vh (can inherit, number only)
    // --- END NEW DIMENSION DEFAULTS ---


    private $site_wide_manager;
    private $settings_saver;

    public function __construct()
    {
        // Load SiteWideBotManager
        if (!class_exists(SiteWideBotManager::class)) {
            $site_wide_path = __DIR__ . '/class-aipkit_site_wide_bot_manager.php';
            if (file_exists($site_wide_path)) {
                require_once $site_wide_path;
            }
        }
        if (class_exists(SiteWideBotManager::class)) {
            $this->site_wide_manager = new SiteWideBotManager();
        }

        // Load and instantiate AIPKit_Bot_Settings_Saver
        $saver_path = __DIR__ . '/class-aipkit_bot_settings_saver.php';
        if (!class_exists(AIPKit_Bot_Settings_Saver::class)) {
            if (file_exists($saver_path)) {
                require_once $saver_path;
            }
        }
        if (class_exists(AIPKit_Bot_Settings_Saver::class) && $this->site_wide_manager) {
            $this->settings_saver = new AIPKit_Bot_Settings_Saver($this->site_wide_manager);
        }
    }

    public function get_chatbot_settings(int $bot_id): array
    {
        $getter_path = __DIR__ . '/class-aipkit-bot-settings-getter.php';
        if (!class_exists(AIPKit_Bot_Settings_Getter::class)) {
            if (file_exists($getter_path)) {
                require_once $getter_path;
            } else {
                return [];
            }
        }
        return AIPKit_Bot_Settings_Getter::get($bot_id);
    }

    public function save_bot_settings(int $botId, array $settings): bool|\WP_Error
    {
        if (!$this->settings_saver) {
            return new \WP_Error('dependency_missing_manager_save', __('Settings saving component is missing.', 'gpt3-ai-content-generator'));
        }
        return $this->settings_saver->save($botId, $settings);
    }

    public static function set_initial_bot_settings(int $post_id, string $botName)
    {
        $initializer_path = __DIR__ . '/class-aipkit-bot-settings-initializer.php';
        if (!class_exists(AIPKit_Bot_Settings_Initializer::class)) {
            if (file_exists($initializer_path)) {
                require_once $initializer_path;
            } else {
                return;
            }
        }
        AIPKit_Bot_Settings_Initializer::initialize($post_id, $botName);
    }

    /**
     * Returns an array of default values for custom theme settings.
     * @return array
     */
    public static function get_custom_theme_defaults(): array
    {
        return [
            'font_family' => self::DEFAULT_CUSTOM_THEME_FONT_FAMILY,
            'bubble_border_radius' => self::DEFAULT_CUSTOM_THEME_BUBBLE_BORDER_RADIUS,
            'container_bg_color' => self::DEFAULT_CTS_CONTAINER_BG_COLOR,
            'container_text_color' => self::DEFAULT_CTS_CONTAINER_TEXT_COLOR,
            'container_border_color' => self::DEFAULT_CTS_CONTAINER_BORDER_COLOR,
            'container_border_radius' => self::DEFAULT_CTS_CONTAINER_BORDER_RADIUS, // Actual default for the CSS var
            'header_bg_color' => self::DEFAULT_CTS_HEADER_BG_COLOR,
            'header_text_color' => self::DEFAULT_CTS_HEADER_TEXT_COLOR,
            'header_border_color' => self::DEFAULT_CTS_HEADER_BORDER_COLOR,
            'messages_bg_color' => self::DEFAULT_CTS_MESSAGES_BG_COLOR,
            'messages_scrollbar_thumb_color' => self::DEFAULT_CTS_MESSAGES_SCROLLBAR_THUMB_COLOR,
            'messages_scrollbar_track_color' => self::DEFAULT_CTS_MESSAGES_SCROLLBAR_TRACK_COLOR,
            'bot_bubble_bg_color' => self::DEFAULT_CTS_BOT_BUBBLE_BG_COLOR,
            'bot_bubble_text_color' => self::DEFAULT_CTS_BOT_BUBBLE_TEXT_COLOR,
            'user_bubble_bg_color' => self::DEFAULT_CTS_USER_BUBBLE_BG_COLOR,
            'user_bubble_text_color' => self::DEFAULT_CTS_USER_BUBBLE_TEXT_COLOR,
            'input_area_bg_color' => self::DEFAULT_CTS_INPUT_AREA_BG_COLOR,
            'input_area_border_color' => self::DEFAULT_CTS_INPUT_AREA_BORDER_COLOR,
            'input_wrapper_bg_color' => self::DEFAULT_CTS_INPUT_WRAPPER_BG_COLOR,
            'input_wrapper_border_color' => self::DEFAULT_CTS_INPUT_WRAPPER_BORDER_COLOR,
            'input_text_color' => self::DEFAULT_CTS_INPUT_TEXT_COLOR,
            'input_placeholder_color' => self::DEFAULT_CTS_INPUT_PLACEHOLDER_COLOR,
            'input_focus_border_color' => self::DEFAULT_CTS_INPUT_FOCUS_BORDER_COLOR,
            'input_focus_shadow_color' => self::DEFAULT_CTS_INPUT_FOCUS_SHADOW_COLOR,
            'send_button_bg_color' => self::DEFAULT_CTS_SEND_BUTTON_BG_COLOR,
            'send_button_text_color' => self::DEFAULT_CTS_SEND_BUTTON_TEXT_COLOR,
            'action_button_bg_color' => self::DEFAULT_CTS_ACTION_BUTTON_BG_COLOR,
            'action_button_color' => self::DEFAULT_CTS_ACTION_BUTTON_COLOR,
            'action_button_border_color' => self::DEFAULT_CTS_ACTION_BUTTON_BORDER_COLOR,
            'action_button_hover_bg_color' => self::DEFAULT_CTS_ACTION_BUTTON_HOVER_BG_COLOR,
            'action_button_hover_color' => self::DEFAULT_CTS_ACTION_BUTTON_HOVER_COLOR,
            'action_button_hover_border_color' => self::DEFAULT_CTS_ACTION_BUTTON_HOVER_BORDER_COLOR,
            'footer_bg_color' => self::DEFAULT_CTS_FOOTER_BG_COLOR,
            'footer_text_color' => self::DEFAULT_CTS_FOOTER_TEXT_COLOR,
            'footer_border_color' => self::DEFAULT_CTS_FOOTER_BORDER_COLOR,
            'sidebar_bg_color' => self::DEFAULT_CTS_SIDEBAR_BG_COLOR,
            'sidebar_text_color' => self::DEFAULT_CTS_SIDEBAR_TEXT_COLOR,
            'sidebar_border_color' => self::DEFAULT_CTS_SIDEBAR_BORDER_COLOR,
            'sidebar_active_bg_color' => self::DEFAULT_CTS_SIDEBAR_ACTIVE_BG_COLOR,
            'sidebar_active_text_color' => self::DEFAULT_CTS_SIDEBAR_ACTIVE_TEXT_COLOR,
            'sidebar_hover_bg_color' => self::DEFAULT_CTS_SIDEBAR_HOVER_BG_COLOR,
            'sidebar_hover_text_color' => self::DEFAULT_CTS_SIDEBAR_HOVER_TEXT_COLOR,
            'action_menu_bg_color' => self::DEFAULT_CTS_ACTION_MENU_BG_COLOR,
            'action_menu_border_color' => self::DEFAULT_CTS_ACTION_MENU_BORDER_COLOR,
            'action_menu_item_text_color' => self::DEFAULT_CTS_ACTION_MENU_ITEM_TEXT_COLOR,
            'action_menu_item_hover_bg_color' => self::DEFAULT_CTS_ACTION_MENU_ITEM_HOVER_BG_COLOR,
            'action_menu_item_hover_text_color' => self::DEFAULT_CTS_ACTION_MENU_ITEM_HOVER_TEXT_COLOR,
            // --- NEW DIMENSION DEFAULTS ---
            'container_max_width' => self::DEFAULT_CTS_CONTAINER_MAX_WIDTH,
            'popup_width' => self::DEFAULT_CTS_POPUP_WIDTH,
            'container_height' => self::DEFAULT_CTS_CONTAINER_HEIGHT,
            'container_max_height' => self::DEFAULT_CTS_CONTAINER_MAX_HEIGHT,
            'container_min_height' => self::DEFAULT_CTS_CONTAINER_MIN_HEIGHT,
            'popup_height' => self::DEFAULT_CTS_POPUP_HEIGHT,
            'popup_min_height' => self::DEFAULT_CTS_POPUP_MIN_HEIGHT,
            'popup_max_height' => self::DEFAULT_CTS_POPUP_MAX_HEIGHT,
            // --- END NEW DIMENSION DEFAULTS ---

             // --- Keep placeholder keys for form UI generation if needed by HTML partials ---
             // These are NOT used as CSS variable defaults but can be used for <input placeholder="...">
             // However, for type="color", placeholder is not standard.
            'bubble_border_radius_placeholder' => self::DEFAULT_CUSTOM_THEME_BUBBLE_BORDER_RADIUS,
            'container_border_radius_placeholder' => self::DEFAULT_CTS_CONTAINER_BORDER_RADIUS,
            'messages_scrollbar_thumb_color_placeholder' => self::DEFAULT_CTS_MESSAGES_SCROLLBAR_THUMB_COLOR,
            'messages_scrollbar_track_color_placeholder' => self::DEFAULT_CTS_MESSAGES_SCROLLBAR_TRACK_COLOR,
            'input_area_border_color_placeholder' => self::DEFAULT_CTS_INPUT_AREA_BORDER_COLOR,
            'input_wrapper_border_color_placeholder' => self::DEFAULT_CTS_INPUT_WRAPPER_BORDER_COLOR,
            'input_placeholder_color_placeholder' => self::DEFAULT_CTS_INPUT_PLACEHOLDER_COLOR,
            'input_focus_border_color_placeholder' => self::DEFAULT_CTS_INPUT_FOCUS_BORDER_COLOR,
            'input_focus_shadow_color_placeholder' => self::DEFAULT_CTS_INPUT_FOCUS_SHADOW_COLOR,
            'action_button_hover_border_color_placeholder' => self::DEFAULT_CTS_ACTION_BUTTON_HOVER_BORDER_COLOR,
            'container_max_width_placeholder' => self::DEFAULT_CTS_CONTAINER_MAX_WIDTH,
            'popup_width_placeholder' => self::DEFAULT_CTS_POPUP_WIDTH,
            'container_height_placeholder' => self::DEFAULT_CTS_CONTAINER_HEIGHT,
            'container_max_height_placeholder' => self::DEFAULT_CTS_CONTAINER_MAX_HEIGHT,
            'container_min_height_placeholder' => self::DEFAULT_CTS_CONTAINER_MIN_HEIGHT,
            'popup_height_placeholder' => self::DEFAULT_CTS_POPUP_HEIGHT,
            'popup_min_height_placeholder' => self::DEFAULT_CTS_POPUP_MIN_HEIGHT,
            'popup_max_height_placeholder' => self::DEFAULT_CTS_POPUP_MAX_HEIGHT,
        ];
    }
}
