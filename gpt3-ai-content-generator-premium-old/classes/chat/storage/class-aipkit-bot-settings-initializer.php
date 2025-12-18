<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/chat/storage/class-aipkit-bot-settings-initializer.php
// Status: MODIFIED

namespace WPAICG\Chat\Storage;

use WPAICG\AIPKit_Providers;
use WPAICG\Chat\Storage\BotSettingsManager;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class AIPKit_Bot_Settings_Initializer
{
    public static function initialize(int $post_id, string $botName)
    {
        if (!class_exists('\WPAICG\AIPKit_Providers')) {
            $providers_path = WPAICG_PLUGIN_DIR . 'classes/dashboard/class-aipkit_providers.php';
            if (file_exists($providers_path)) {
                require_once $providers_path;
            } else {
                return;
            }
        }
        if (!class_exists(BotSettingsManager::class)) {
            $manager_path = __DIR__ . '/class-aipkit_bot_settings_manager.php';
            if (file_exists($manager_path)) {
                require_once $manager_path;
            } else {
                return;
            }
        }

        // --- MODIFIED: Conditional access to Trigger Storage META_KEY ---
        $trigger_meta_key = '_aipkit_chatbot_triggers'; // Fallback key
        if (class_exists('\WPAICG\Lib\Chat\Triggers\AIPKit_Trigger_Storage')) { // Check for the class in its new Pro location
            $trigger_meta_key = \WPAICG\Lib\Chat\Triggers\AIPKit_Trigger_Storage::META_KEY;
        }
        // --- END MODIFICATION ---

        $default_greeting = sprintf(__('Hello! How can I assist you?', 'gpt3-ai-content-generator'), esc_html($botName));
        update_post_meta($post_id, '_aipkit_greeting_message', $default_greeting);
        $global = AIPKit_Providers::get_default_provider_config();
        $global_provider = $global['provider'];
        $global_model = $global['model'];
        update_post_meta($post_id, '_aipkit_provider', $global_provider);
        if (!empty($global_model)) {
            update_post_meta($post_id, '_aipkit_model', $global_model);
        } else {
            delete_post_meta($post_id, '_aipkit_model');
        }
        delete_post_meta($post_id, '_aipkit_azure_deployment');
        delete_post_meta($post_id, '_aipkit_azure_endpoint');
        update_post_meta($post_id, '_aipkit_theme', 'light');
        $default_instructions = __("You are a helpful AI Assistant. Please be friendly. Today's date is [date].", 'gpt3-ai-content-generator');
        update_post_meta($post_id, '_aipkit_instructions', $default_instructions);
        update_post_meta($post_id, '_aipkit_popup_enabled', '0');
        update_post_meta($post_id, '_aipkit_popup_position', 'bottom-right');
        update_post_meta($post_id, '_aipkit_popup_delay', BotSettingsManager::DEFAULT_POPUP_DELAY);
        update_post_meta($post_id, '_aipkit_site_wide_enabled', '0');
        update_post_meta($post_id, '_aipkit_popup_icon_type', BotSettingsManager::DEFAULT_POPUP_ICON_TYPE);
        update_post_meta($post_id, '_aipkit_popup_icon_style', BotSettingsManager::DEFAULT_POPUP_ICON_STYLE);
        update_post_meta($post_id, '_aipkit_popup_icon_value', BotSettingsManager::DEFAULT_POPUP_ICON_VALUE);
        update_post_meta($post_id, '_aipkit_popup_icon_size', BotSettingsManager::DEFAULT_POPUP_ICON_SIZE);
        // Initialize Popup Hint/Label defaults
        update_post_meta($post_id, '_aipkit_popup_label_enabled', BotSettingsManager::DEFAULT_POPUP_LABEL_ENABLED);
        update_post_meta($post_id, '_aipkit_popup_label_text', BotSettingsManager::DEFAULT_POPUP_LABEL_TEXT);
        update_post_meta($post_id, '_aipkit_popup_label_mode', BotSettingsManager::DEFAULT_POPUP_LABEL_MODE);
        update_post_meta($post_id, '_aipkit_popup_label_delay_seconds', BotSettingsManager::DEFAULT_POPUP_LABEL_DELAY_SECONDS);
        update_post_meta($post_id, '_aipkit_popup_label_auto_hide_seconds', BotSettingsManager::DEFAULT_POPUP_LABEL_AUTO_HIDE_SECONDS);
        update_post_meta($post_id, '_aipkit_popup_label_dismissible', BotSettingsManager::DEFAULT_POPUP_LABEL_DISMISSIBLE);
        update_post_meta($post_id, '_aipkit_popup_label_frequency', BotSettingsManager::DEFAULT_POPUP_LABEL_FREQUENCY);
        update_post_meta($post_id, '_aipkit_popup_label_show_on_mobile', BotSettingsManager::DEFAULT_POPUP_LABEL_SHOW_ON_MOBILE);
        update_post_meta($post_id, '_aipkit_popup_label_show_on_desktop', BotSettingsManager::DEFAULT_POPUP_LABEL_SHOW_ON_DESKTOP);
        update_post_meta($post_id, '_aipkit_popup_label_version', BotSettingsManager::DEFAULT_POPUP_LABEL_VERSION);
        update_post_meta($post_id, '_aipkit_popup_label_size', BotSettingsManager::DEFAULT_POPUP_LABEL_SIZE);
        update_post_meta($post_id, '_aipkit_stream_enabled', BotSettingsManager::DEFAULT_STREAM_ENABLED);
        update_post_meta($post_id, '_aipkit_footer_text', '');
        update_post_meta($post_id, '_aipkit_enable_fullscreen', '1');
        update_post_meta($post_id, '_aipkit_enable_download', '1');
        update_post_meta($post_id, '_aipkit_enable_copy_button', BotSettingsManager::DEFAULT_ENABLE_COPY_BUTTON);
        update_post_meta($post_id, '_aipkit_enable_feedback', BotSettingsManager::DEFAULT_ENABLE_FEEDBACK);
        update_post_meta($post_id, '_aipkit_enable_conversation_sidebar', BotSettingsManager::DEFAULT_ENABLE_CONVERSATION_SIDEBAR);
        $default_placeholder = __('Type your message...', 'gpt3-ai-content-generator');
        update_post_meta($post_id, '_aipkit_input_placeholder', $default_placeholder);
        // Typing indicator customization defaults
        update_post_meta($post_id, '_aipkit_custom_typing_text', BotSettingsManager::DEFAULT_CUSTOM_TYPING_TEXT);
        update_post_meta($post_id, '_aipkit_temperature', (string)BotSettingsManager::DEFAULT_TEMPERATURE);
        update_post_meta($post_id, '_aipkit_max_completion_tokens', BotSettingsManager::DEFAULT_MAX_COMPLETION_TOKENS);
        update_post_meta($post_id, '_aipkit_max_messages', BotSettingsManager::DEFAULT_MAX_MESSAGES);
        update_post_meta($post_id, '_aipkit_reasoning_effort', BotSettingsManager::DEFAULT_REASONING_EFFORT);
        update_post_meta($post_id, '_aipkit_enable_conversation_starters', BotSettingsManager::DEFAULT_ENABLE_CONVERSATION_STARTERS);
        update_post_meta($post_id, '_aipkit_conversation_starters', '[]');
        update_post_meta($post_id, '_aipkit_content_aware_enabled', BotSettingsManager::DEFAULT_CONTENT_AWARE_ENABLED);
        update_post_meta($post_id, '_aipkit_openai_conversation_state_enabled', BotSettingsManager::DEFAULT_OPENAI_CONVERSATION_STATE_ENABLED);
        $default_guest_limit_value = (BotSettingsManager::DEFAULT_TOKEN_GUEST_LIMIT === null) ? '' : (string)BotSettingsManager::DEFAULT_TOKEN_GUEST_LIMIT;
        $default_user_limit_value = (BotSettingsManager::DEFAULT_TOKEN_USER_LIMIT === null) ? '' : (string)BotSettingsManager::DEFAULT_TOKEN_USER_LIMIT;
        update_post_meta($post_id, '_aipkit_token_guest_limit', $default_guest_limit_value);
        update_post_meta($post_id, '_aipkit_token_user_limit', $default_user_limit_value);
        update_post_meta($post_id, '_aipkit_token_reset_period', BotSettingsManager::DEFAULT_TOKEN_RESET_PERIOD);
        update_post_meta($post_id, '_aipkit_token_limit_message', __('You have reached your token limit for this period.', 'gpt3-ai-content-generator'));
        update_post_meta($post_id, '_aipkit_token_limit_mode', BotSettingsManager::DEFAULT_TOKEN_LIMIT_MODE);
        update_post_meta($post_id, '_aipkit_token_role_limits', '[]');
        update_post_meta($post_id, '_aipkit_tts_enabled', BotSettingsManager::DEFAULT_TTS_ENABLED);
        update_post_meta($post_id, '_aipkit_tts_provider', BotSettingsManager::DEFAULT_TTS_PROVIDER);
        update_post_meta($post_id, '_aipkit_tts_google_voice_id', '');
        update_post_meta($post_id, '_aipkit_tts_openai_voice_id', '');
        update_post_meta($post_id, '_aipkit_tts_openai_model_id', BotSettingsManager::DEFAULT_TTS_OPENAI_MODEL_ID);
        update_post_meta($post_id, '_aipkit_tts_elevenlabs_voice_id', '');
        update_post_meta($post_id, '_aipkit_tts_elevenlabs_model_id', BotSettingsManager::DEFAULT_TTS_ELEVENLABS_MODEL_ID);
        update_post_meta($post_id, '_aipkit_tts_auto_play', BotSettingsManager::DEFAULT_TTS_AUTO_PLAY);
        update_post_meta($post_id, '_aipkit_enable_voice_input', BotSettingsManager::DEFAULT_ENABLE_VOICE_INPUT);
        update_post_meta($post_id, '_aipkit_stt_provider', BotSettingsManager::DEFAULT_STT_PROVIDER);
        update_post_meta($post_id, '_aipkit_stt_openai_model_id', BotSettingsManager::DEFAULT_STT_OPENAI_MODEL_ID);
        update_post_meta($post_id, '_aipkit_stt_azure_model_id', BotSettingsManager::DEFAULT_STT_AZURE_MODEL_ID);
        update_post_meta($post_id, '_aipkit_image_triggers', BotSettingsManager::DEFAULT_IMAGE_TRIGGERS);
        update_post_meta($post_id, '_aipkit_chat_image_model_id', BotSettingsManager::DEFAULT_CHAT_IMAGE_MODEL_ID);
        update_post_meta($post_id, '_aipkit_enable_file_upload', BotSettingsManager::DEFAULT_ENABLE_FILE_UPLOAD);
        update_post_meta($post_id, '_aipkit_enable_image_upload', BotSettingsManager::DEFAULT_ENABLE_IMAGE_UPLOAD);
        update_post_meta($post_id, '_aipkit_enable_vector_store', BotSettingsManager::DEFAULT_ENABLE_VECTOR_STORE);
        update_post_meta($post_id, '_aipkit_vector_store_provider', BotSettingsManager::DEFAULT_VECTOR_STORE_PROVIDER);
        update_post_meta($post_id, '_aipkit_openai_vector_store_ids', '[]');
        delete_post_meta($post_id, '_aipkit_openai_vector_store_id');
        update_post_meta($post_id, '_aipkit_pinecone_index_name', BotSettingsManager::DEFAULT_PINECONE_INDEX_NAME);
        update_post_meta($post_id, '_aipkit_qdrant_collection_name', BotSettingsManager::DEFAULT_QDRANT_COLLECTION_NAME);
        update_post_meta($post_id, '_aipkit_qdrant_collection_names', '[]');
        update_post_meta($post_id, '_aipkit_vector_embedding_provider', BotSettingsManager::DEFAULT_VECTOR_EMBEDDING_PROVIDER);
        update_post_meta($post_id, '_aipkit_vector_embedding_model', BotSettingsManager::DEFAULT_VECTOR_EMBEDDING_MODEL);
        update_post_meta($post_id, '_aipkit_vector_store_top_k', BotSettingsManager::DEFAULT_VECTOR_STORE_TOP_K);
        update_post_meta($post_id, '_aipkit_vector_store_confidence_threshold', BotSettingsManager::DEFAULT_VECTOR_STORE_CONFIDENCE_THRESHOLD); // NEW
        update_post_meta($post_id, '_aipkit_openai_web_search_enabled', BotSettingsManager::DEFAULT_OPENAI_WEB_SEARCH_ENABLED);
        update_post_meta($post_id, '_aipkit_openai_web_search_context_size', BotSettingsManager::DEFAULT_OPENAI_WEB_SEARCH_CONTEXT_SIZE);
        update_post_meta($post_id, '_aipkit_openai_web_search_loc_type', BotSettingsManager::DEFAULT_OPENAI_WEB_SEARCH_LOC_TYPE);
        update_post_meta($post_id, '_aipkit_openai_web_search_loc_country', '');
        update_post_meta($post_id, '_aipkit_openai_web_search_loc_city', '');
        update_post_meta($post_id, '_aipkit_openai_web_search_loc_region', '');
        update_post_meta($post_id, '_aipkit_openai_web_search_loc_timezone', '');
        update_post_meta($post_id, '_aipkit_google_search_grounding_enabled', BotSettingsManager::DEFAULT_GOOGLE_SEARCH_GROUNDING_ENABLED);
        update_post_meta($post_id, '_aipkit_google_grounding_mode', BotSettingsManager::DEFAULT_GOOGLE_GROUNDING_MODE);
        update_post_meta($post_id, '_aipkit_google_grounding_dynamic_threshold', (string)BotSettingsManager::DEFAULT_GOOGLE_GROUNDING_DYNAMIC_THRESHOLD);

        // --- NEW: Initialize Realtime Voice Agent settings ---
        update_post_meta($post_id, '_aipkit_enable_realtime_voice', BotSettingsManager::DEFAULT_ENABLE_REALTIME_VOICE);
        update_post_meta($post_id, '_aipkit_direct_voice_mode', BotSettingsManager::DEFAULT_DIRECT_VOICE_MODE);
        update_post_meta($post_id, '_aipkit_realtime_model', BotSettingsManager::DEFAULT_REALTIME_MODEL);
        update_post_meta($post_id, '_aipkit_realtime_voice', BotSettingsManager::DEFAULT_REALTIME_VOICE);
        update_post_meta($post_id, '_aipkit_turn_detection', BotSettingsManager::DEFAULT_TURN_DETECTION);
        update_post_meta($post_id, '_aipkit_speed', (string)BotSettingsManager::DEFAULT_SPEED);
        update_post_meta($post_id, '_aipkit_input_audio_format', BotSettingsManager::DEFAULT_INPUT_AUDIO_FORMAT);
        update_post_meta($post_id, '_aipkit_output_audio_format', BotSettingsManager::DEFAULT_OUTPUT_AUDIO_FORMAT);
        update_post_meta($post_id, '_aipkit_input_audio_noise_reduction', BotSettingsManager::DEFAULT_INPUT_AUDIO_NOISE_REDUCTION);
        // --- END NEW ---

        $custom_theme_defaults = BotSettingsManager::get_custom_theme_defaults();
        foreach ($custom_theme_defaults as $key => $default_value) {
            if (strpos($key, '_placeholder') === false) {
                update_post_meta($post_id, '_aipkit_cts_' . $key, $default_value);
            }
        }

        update_post_meta($post_id, $trigger_meta_key, '[]'); // Use the determined meta key

        if (get_post_meta($post_id, '_aipkit_default_bot', true) === '1') {
            update_post_meta($post_id, '_aipkit_theme', 'dark');
        }
    }
}
