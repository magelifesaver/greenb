<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/chat/storage/saver/sanitize-settings-logic.php
// Status: MODIFIED

namespace WPAICG\Chat\Storage\SaverMethods;

use WPAICG\Chat\Storage\BotSettingsManager;
use WPAICG\aipkit_dashboard;
use WPAICG\AIPKit_Providers;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Sanitizes the raw bot settings array.
 * UPDATED: Includes sanitization for new custom theme settings.
 * UPDATED: Validates new theme names.
 * FIXED: Ensures color fields default to valid hex codes from $custom_theme_defaults if input is invalid/empty.
 * ADDED: Handles 'triggers_json' from form.
 *
 * @param array $raw_settings The raw settings array from $_POST or similar.
 * @param int $bot_id The ID of the bot (for context, e.g., default values).
 * @return array The sanitized settings array.
 */
function sanitize_settings_logic(array $raw_settings, int $bot_id): array
{
    $sanitized = [];
    $custom_theme_defaults = [];

    if (!class_exists(BotSettingsManager::class)) {
        // Fallback defaults if class is missing
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
    } else {
        $custom_theme_defaults = BotSettingsManager::get_custom_theme_defaults();
    }


    $sanitized['greeting'] = isset($raw_settings['greeting']) ? sanitize_textarea_field($raw_settings['greeting']) : '';
    $sanitized['provider'] = isset($raw_settings['provider']) ? sanitize_text_field($raw_settings['provider']) : '';
    $valid_themes = ['light', 'dark', 'custom', 'chatgpt'];
    $sanitized['theme'] = isset($raw_settings['theme']) && in_array($raw_settings['theme'], $valid_themes) ? sanitize_text_field($raw_settings['theme']) : 'light';
    $sanitized['instructions'] = isset($raw_settings['instructions']) ? sanitize_textarea_field($raw_settings['instructions']) : '';
    $sanitized['popup_enabled'] = (isset($raw_settings['popup_enabled']) && $raw_settings['popup_enabled'] === '1') ? '1' : '0';
    $sanitized['popup_position'] = isset($raw_settings['popup_position']) ? sanitize_key($raw_settings['popup_position']) : 'bottom-right';
    $sanitized['popup_delay'] = isset($raw_settings['popup_delay']) ? absint($raw_settings['popup_delay']) : BotSettingsManager::DEFAULT_POPUP_DELAY;
    $sanitized['site_wide_enabled'] = (isset($raw_settings['site_wide_enabled']) && $raw_settings['site_wide_enabled'] === '1') ? '1' : '0';
    $sanitized['popup_icon_style'] = isset($raw_settings['popup_icon_style']) && in_array($raw_settings['popup_icon_style'], ['circle', 'square', 'none']) ? sanitize_key($raw_settings['popup_icon_style']) : BotSettingsManager::DEFAULT_POPUP_ICON_STYLE;
    // NEW: Popup icon size
    $allowed_icon_sizes = ['small','medium','large','xlarge'];
    $sanitized['popup_icon_size'] = isset($raw_settings['popup_icon_size']) && in_array($raw_settings['popup_icon_size'], $allowed_icon_sizes, true)
        ? sanitize_key($raw_settings['popup_icon_size'])
        : (defined('WPAICG\\Chat\\Storage\\BotSettingsManager::DEFAULT_POPUP_ICON_SIZE') ? BotSettingsManager::DEFAULT_POPUP_ICON_SIZE : 'medium');
    $sanitized['popup_icon_type'] = isset($raw_settings['popup_icon_type']) && in_array($raw_settings['popup_icon_type'], ['default', 'custom']) ? $raw_settings['popup_icon_type'] : BotSettingsManager::DEFAULT_POPUP_ICON_TYPE;
    $sanitized['popup_icon_value'] = '';
    if ($sanitized['popup_icon_type'] === 'default') {
        $default_icon_key = isset($raw_settings['popup_icon_default']) && in_array($raw_settings['popup_icon_default'], ['chat-bubble', 'plus', 'question-mark']) ? $raw_settings['popup_icon_default'] : BotSettingsManager::DEFAULT_POPUP_ICON_VALUE;
        $sanitized['popup_icon_value'] = $default_icon_key;
    } elseif ($sanitized['popup_icon_type'] === 'custom') {
        $sanitized['popup_icon_value'] = isset($raw_settings['popup_icon_custom_url']) ? esc_url_raw(trim($raw_settings['popup_icon_custom_url'])) : '';
    }
    $sanitized['stream_enabled'] = (isset($raw_settings['stream_enabled']) && $raw_settings['stream_enabled'] === '1') ? '1' : '0';
    $sanitized['footer_text'] = isset($raw_settings['footer_text']) ? wp_kses_post($raw_settings['footer_text']) : '';
    $sanitized['enable_fullscreen'] = (isset($raw_settings['enable_fullscreen']) && $raw_settings['enable_fullscreen'] === '1') ? '1' : '0';
    $sanitized['enable_download'] = (isset($raw_settings['enable_download']) && $raw_settings['enable_download'] === '1') ? '1' : '0';
    $sanitized['enable_copy_button'] = (isset($raw_settings['enable_copy_button']) && $raw_settings['enable_copy_button'] === '1') ? '1' : '0';
    $sanitized['enable_feedback'] = (isset($raw_settings['enable_feedback']) && $raw_settings['enable_feedback'] === '1') ? '1' : '0';
    $sanitized['enable_conversation_sidebar'] = (isset($raw_settings['enable_conversation_sidebar']) && $raw_settings['enable_conversation_sidebar'] === '1') ? '1' : '0';
    // Typing indicator customization
    $sanitized['custom_typing_text'] = isset($raw_settings['custom_typing_text']) ? sanitize_text_field($raw_settings['custom_typing_text']) : '';
    $sanitized['input_placeholder'] = isset($raw_settings['input_placeholder']) ? sanitize_text_field($raw_settings['input_placeholder']) : __('Type your message...', 'gpt3-ai-content-generator');
    $sanitized['temperature'] = isset($raw_settings['temperature']) ? floatval($raw_settings['temperature']) : BotSettingsManager::DEFAULT_TEMPERATURE;
    $sanitized['max_completion_tokens'] = isset($raw_settings['max_completion_tokens']) ? absint($raw_settings['max_completion_tokens']) : BotSettingsManager::DEFAULT_MAX_COMPLETION_TOKENS;
    $sanitized['max_messages'] = isset($raw_settings['max_messages']) ? absint($raw_settings['max_messages']) : BotSettingsManager::DEFAULT_MAX_MESSAGES;
    $sanitized['reasoning_effort'] = isset($raw_settings['reasoning_effort']) && in_array($raw_settings['reasoning_effort'], ['minimal', 'low', 'medium', 'high']) ? sanitize_key($raw_settings['reasoning_effort']) : BotSettingsManager::DEFAULT_REASONING_EFFORT;
    $sanitized['enable_conversation_starters'] = (isset($raw_settings['enable_conversation_starters']) && $raw_settings['enable_conversation_starters'] === '1') ? '1' : '0';
    $starters_raw = isset($raw_settings['conversation_starters']) ? $raw_settings['conversation_starters'] : ''; // Textarea value
    $starters_array = [];
    if (!empty($starters_raw)) {
        $lines = explode("\n", $starters_raw);
        foreach ($lines as $line) {
            $trimmed_line = trim($line);
            if (!empty($trimmed_line)) {
                $starters_array[] = $trimmed_line;
            }
        }
        $starters_array = array_slice($starters_array, 0, 6);
    }
    $sanitized['conversation_starters'] = wp_json_encode($starters_array, JSON_UNESCAPED_UNICODE);
    $sanitized['content_aware_enabled'] = (isset($raw_settings['content_aware_enabled']) && $raw_settings['content_aware_enabled'] === '1') ? '1' : '0';
    $sanitized['openai_conversation_state_enabled'] = (isset($raw_settings['openai_conversation_state_enabled']) && $raw_settings['openai_conversation_state_enabled'] === '1') ? '1' : '0';
    $sanitized['token_limit_mode'] = isset($raw_settings['token_limit_mode']) && in_array($raw_settings['token_limit_mode'], ['general', 'role_based']) ? $raw_settings['token_limit_mode'] : BotSettingsManager::DEFAULT_TOKEN_LIMIT_MODE;
    $raw_guest_limit = isset($raw_settings['token_guest_limit']) ? trim($raw_settings['token_guest_limit']) : '';
    $sanitized['token_guest_limit'] = ($raw_guest_limit === '0' || (ctype_digit($raw_guest_limit) && $raw_guest_limit > 0)) ? (string)absint($raw_guest_limit) : ''; // Store as string or empty
    $raw_user_limit = isset($raw_settings['token_user_limit']) ? trim($raw_settings['token_user_limit']) : '';
    $sanitized['token_user_limit'] = ($raw_user_limit === '0' || (ctype_digit($raw_user_limit) && $raw_user_limit > 0)) ? (string)absint($raw_user_limit) : '';
    $role_limits_to_save = [];
    if (isset($raw_settings['token_role_limits']) && is_array($raw_settings['token_role_limits'])) {
        $editable_roles = get_editable_roles();
        foreach ($editable_roles as $role_slug => $role_info) {
            if (isset($raw_settings['token_role_limits'][$role_slug])) {
                $raw_limit = trim($raw_settings['token_role_limits'][$role_slug]);
                if ($raw_limit === '0' || (ctype_digit($raw_limit) && $raw_limit > 0)) {
                    $role_limits_to_save[$role_slug] = (string)absint($raw_limit);
                } else {
                    $role_limits_to_save[$role_slug] = '';
                }
            }
        }
    }
    $sanitized['token_role_limits'] = wp_json_encode($role_limits_to_save, JSON_UNESCAPED_UNICODE);
    $sanitized['token_reset_period'] = isset($raw_settings['token_reset_period']) && in_array($raw_settings['token_reset_period'], ['never', 'daily', 'weekly', 'monthly']) ? sanitize_key($raw_settings['token_reset_period']) : BotSettingsManager::DEFAULT_TOKEN_RESET_PERIOD;
    $sanitized['token_limit_message'] = isset($raw_settings['token_limit_message']) ? sanitize_text_field($raw_settings['token_limit_message']) : '';
    $sanitized['model'] = isset($raw_settings['model']) ? sanitize_text_field($raw_settings['model']) : '';
    $sanitized['tts_enabled'] = (isset($raw_settings['tts_enabled']) && $raw_settings['tts_enabled'] === '1') ? '1' : '0';
    $sanitized['tts_provider'] = isset($raw_settings['tts_provider']) ? sanitize_text_field($raw_settings['tts_provider']) : BotSettingsManager::DEFAULT_TTS_PROVIDER;
    if (!in_array($sanitized['tts_provider'], ['Google', 'OpenAI', 'ElevenLabs'])) {
        $sanitized['tts_provider'] = BotSettingsManager::DEFAULT_TTS_PROVIDER;
    }
    $sanitized['tts_google_voice_id'] = isset($raw_settings['tts_google_voice_id']) ? sanitize_text_field($raw_settings['tts_google_voice_id']) : '';
    $sanitized['tts_openai_voice_id'] = isset($raw_settings['tts_openai_voice_id']) ? sanitize_text_field($raw_settings['tts_openai_voice_id']) : 'alloy';
    $sanitized['tts_openai_model_id'] = isset($raw_settings['tts_openai_model_id']) ? sanitize_text_field($raw_settings['tts_openai_model_id']) : BotSettingsManager::DEFAULT_TTS_OPENAI_MODEL_ID;
    $sanitized['tts_elevenlabs_voice_id'] = isset($raw_settings['tts_elevenlabs_voice_id']) ? sanitize_text_field($raw_settings['tts_elevenlabs_voice_id']) : '';
    $sanitized['tts_elevenlabs_model_id'] = isset($raw_settings['tts_elevenlabs_model_id']) ? sanitize_text_field($raw_settings['tts_elevenlabs_model_id']) : '';
    $sanitized['tts_auto_play'] = (isset($raw_settings['tts_auto_play']) && $raw_settings['tts_auto_play'] === '1') ? '1' : '0';
    $sanitized['enable_voice_input'] = (isset($raw_settings['enable_voice_input']) && $raw_settings['enable_voice_input'] === '1') ? '1' : '0';
    $sanitized['stt_provider'] = isset($raw_settings['stt_provider']) ? sanitize_text_field($raw_settings['stt_provider']) : BotSettingsManager::DEFAULT_STT_PROVIDER;
    if (!in_array($sanitized['stt_provider'], ['OpenAI', 'Azure'])) {
        $sanitized['stt_provider'] = BotSettingsManager::DEFAULT_STT_PROVIDER;
    }
    $sanitized['stt_openai_model_id'] = isset($raw_settings['stt_openai_model_id']) ? sanitize_text_field($raw_settings['stt_openai_model_id']) : BotSettingsManager::DEFAULT_STT_OPENAI_MODEL_ID;
    $sanitized['stt_azure_model_id'] = isset($raw_settings['stt_azure_model_id']) ? sanitize_text_field($raw_settings['stt_azure_model_id']) : BotSettingsManager::DEFAULT_STT_AZURE_MODEL_ID;
    $raw_image_triggers = isset($raw_settings['image_triggers']) ? sanitize_text_field($raw_settings['image_triggers']) : BotSettingsManager::DEFAULT_IMAGE_TRIGGERS;
    $triggers_array = array_map('trim', explode(',', $raw_image_triggers));
    $triggers_array = array_filter($triggers_array, function ($trigger) { return !empty($trigger) && preg_match('/^\/[a-zA-Z0-9_]+$/', $trigger); });
    $sanitized['image_triggers'] = !empty($triggers_array) ? implode(',', $triggers_array) : BotSettingsManager::DEFAULT_IMAGE_TRIGGERS;
    $sanitized['chat_image_model_id'] = isset($raw_settings['chat_image_model_id']) ? sanitize_text_field($raw_settings['chat_image_model_id']) : BotSettingsManager::DEFAULT_CHAT_IMAGE_MODEL_ID;
    $sanitized['enable_file_upload'] = (isset($raw_settings['enable_file_upload']) && $raw_settings['enable_file_upload'] === '1') ? '1' : '0';
    $sanitized['enable_image_upload'] = (isset($raw_settings['enable_image_upload']) && $raw_settings['enable_image_upload'] === '1') ? '1' : '0';
    $sanitized['enable_vector_store'] = (isset($raw_settings['enable_vector_store']) && $raw_settings['enable_vector_store'] === '1') ? '1' : '0';
    $sanitized['vector_store_provider'] = isset($raw_settings['vector_store_provider']) && in_array($raw_settings['vector_store_provider'], ['openai', 'pinecone', 'qdrant']) ? sanitize_text_field($raw_settings['vector_store_provider']) : BotSettingsManager::DEFAULT_VECTOR_STORE_PROVIDER;
    $openai_vs_ids_raw = isset($raw_settings['openai_vector_store_ids']) && is_array($raw_settings['openai_vector_store_ids']) ? $raw_settings['openai_vector_store_ids'] : [];
    $openai_vs_ids_to_save = [];
    foreach ($openai_vs_ids_raw as $vs_id) {
        $sanitized_id = sanitize_text_field(trim($vs_id));
        if (!empty($sanitized_id) && strpos($sanitized_id, 'vs_') === 0) {
            $openai_vs_ids_to_save[] = $sanitized_id;
        }
    }
    $sanitized['openai_vector_store_ids'] = wp_json_encode(array_values(array_unique($openai_vs_ids_to_save)));
    $sanitized['pinecone_index_name'] = ($sanitized['vector_store_provider'] === 'pinecone' && isset($raw_settings['pinecone_index_name'])) ? sanitize_text_field($raw_settings['pinecone_index_name']) : '';
    // Qdrant: accept multiple collections; also maintain legacy single for compatibility
    $qdrant_names_raw = [];
    if ($sanitized['vector_store_provider'] === 'qdrant') {
        if (isset($raw_settings['qdrant_collection_names'])) {
            $qdrant_names_raw = is_array($raw_settings['qdrant_collection_names']) ? $raw_settings['qdrant_collection_names'] : [];
        } elseif (isset($raw_settings['qdrant_collection_names']) && is_string($raw_settings['qdrant_collection_names'])) {
            // If sent as JSON string for any reason
            $decoded = json_decode($raw_settings['qdrant_collection_names'], true);
            if (is_array($decoded)) { $qdrant_names_raw = $decoded; }
        }
        // Fallback to single field
        if (empty($qdrant_names_raw) && isset($raw_settings['qdrant_collection_name'])) {
            $single = sanitize_text_field($raw_settings['qdrant_collection_name']);
            if (!empty($single)) { $qdrant_names_raw = [$single]; }
        }
    }
    $qdrant_names_clean = [];
    foreach ($qdrant_names_raw as $name) {
        $sn = sanitize_text_field(trim((string)$name));
        if ($sn !== '') { $qdrant_names_clean[] = $sn; }
    }
    $qdrant_names_clean = array_values(array_unique($qdrant_names_clean));
    $sanitized['qdrant_collection_names'] = wp_json_encode($qdrant_names_clean);
    $sanitized['qdrant_collection_name'] = $qdrant_names_clean[0] ?? '';
    $sanitized['vector_embedding_provider'] = (($sanitized['vector_store_provider'] === 'pinecone' || $sanitized['vector_store_provider'] === 'qdrant') && isset($raw_settings['vector_embedding_provider'])) ? sanitize_key($raw_settings['vector_embedding_provider']) : BotSettingsManager::DEFAULT_VECTOR_EMBEDDING_PROVIDER;
    $sanitized['vector_embedding_model'] = (($sanitized['vector_store_provider'] === 'pinecone' || $sanitized['vector_store_provider'] === 'qdrant') && isset($raw_settings['vector_embedding_model'])) ? sanitize_text_field($raw_settings['vector_embedding_model']) : '';
    $raw_top_k = isset($raw_settings['vector_store_top_k']) ? absint($raw_settings['vector_store_top_k']) : BotSettingsManager::DEFAULT_VECTOR_STORE_TOP_K;
    $sanitized['vector_store_top_k'] = max(1, min($raw_top_k, 20));
    // NEW: Sanitize confidence threshold
    $raw_threshold = isset($raw_settings['vector_store_confidence_threshold']) ? absint($raw_settings['vector_store_confidence_threshold']) : BotSettingsManager::DEFAULT_VECTOR_STORE_CONFIDENCE_THRESHOLD;
    $sanitized['vector_store_confidence_threshold'] = max(0, min($raw_threshold, 100));
    // END NEW
    $sanitized['openai_web_search_enabled'] = (isset($raw_settings['openai_web_search_enabled']) && $raw_settings['openai_web_search_enabled'] === '1') ? '1' : '0';
    $sanitized['openai_web_search_context_size'] = isset($raw_settings['openai_web_search_context_size']) && in_array($raw_settings['openai_web_search_context_size'], ['low', 'medium', 'high']) ? $raw_settings['openai_web_search_context_size'] : BotSettingsManager::DEFAULT_OPENAI_WEB_SEARCH_CONTEXT_SIZE;
    $sanitized['openai_web_search_loc_type'] = isset($raw_settings['openai_web_search_loc_type']) && in_array($raw_settings['openai_web_search_loc_type'], ['none', 'approximate']) ? $raw_settings['openai_web_search_loc_type'] : BotSettingsManager::DEFAULT_OPENAI_WEB_SEARCH_LOC_TYPE;
    $sanitized['openai_web_search_loc_country'] = isset($raw_settings['openai_web_search_loc_country']) ? sanitize_text_field($raw_settings['openai_web_search_loc_country']) : '';
    $sanitized['openai_web_search_loc_city'] = isset($raw_settings['openai_web_search_loc_city']) ? sanitize_text_field($raw_settings['openai_web_search_loc_city']) : '';
    $sanitized['openai_web_search_loc_region'] = isset($raw_settings['openai_web_search_loc_region']) ? sanitize_text_field($raw_settings['openai_web_search_loc_region']) : '';
    $sanitized['openai_web_search_loc_timezone'] = isset($raw_settings['openai_web_search_loc_timezone']) ? sanitize_text_field($raw_settings['openai_web_search_loc_timezone']) : '';
    $sanitized['google_search_grounding_enabled'] = (isset($raw_settings['google_search_grounding_enabled']) && $raw_settings['google_search_grounding_enabled'] === '1') ? '1' : '0';
    $sanitized['google_grounding_mode'] = isset($raw_settings['google_grounding_mode']) && in_array($raw_settings['google_grounding_mode'], ['DEFAULT_MODE', 'MODE_DYNAMIC']) ? $raw_settings['google_grounding_mode'] : BotSettingsManager::DEFAULT_GOOGLE_GROUNDING_MODE;
    $raw_google_threshold = isset($raw_settings['google_grounding_dynamic_threshold']) ? floatval($raw_settings['google_grounding_dynamic_threshold']) : BotSettingsManager::DEFAULT_GOOGLE_GROUNDING_DYNAMIC_THRESHOLD;
    $sanitized['google_grounding_dynamic_threshold'] = max(0.0, min($raw_google_threshold, 1.0));

    // --- Sanitize Realtime Voice Agent settings ---
    $sanitized['enable_realtime_voice'] = (isset($raw_settings['enable_realtime_voice']) && $raw_settings['enable_realtime_voice'] === '1') ? '1' : '0';
    $sanitized['direct_voice_mode'] = (isset($raw_settings['direct_voice_mode']) && $raw_settings['direct_voice_mode'] === '1') ? '1' : '0';
    $sanitized['realtime_model'] = isset($raw_settings['realtime_model']) && in_array($raw_settings['realtime_model'], ['gpt-4o-realtime-preview', 'gpt-4o-mini-realtime']) ? $raw_settings['realtime_model'] : 'gpt-4o-realtime-preview';
    $sanitized['realtime_voice'] = isset($raw_settings['realtime_voice']) && in_array($raw_settings['realtime_voice'], ['alloy', 'ash', 'ballad', 'coral', 'echo', 'fable', 'onyx', 'nova', 'shimmer', 'verse']) ? $raw_settings['realtime_voice'] : 'alloy';
    $sanitized['turn_detection'] = isset($raw_settings['turn_detection']) && in_array($raw_settings['turn_detection'], ['none', 'server_vad', 'semantic_vad']) ? $raw_settings['turn_detection'] : 'server_vad';
    $sanitized['speed'] = isset($raw_settings['speed']) ? max(0.25, min(1.5, floatval($raw_settings['speed']))) : 1.0;
    $valid_audio_formats = ['pcm16', 'g711_ulaw', 'g711_alaw'];
    $sanitized['input_audio_format'] = isset($raw_settings['input_audio_format']) && in_array($raw_settings['input_audio_format'], $valid_audio_formats) ? $raw_settings['input_audio_format'] : 'pcm16';
    $sanitized['output_audio_format'] = isset($raw_settings['output_audio_format']) && in_array($raw_settings['output_audio_format'], $valid_audio_formats) ? $raw_settings['output_audio_format'] : 'pcm16';
    $sanitized['input_audio_noise_reduction'] = (isset($raw_settings['input_audio_noise_reduction']) && $raw_settings['input_audio_noise_reduction'] === '1') ? '1' : '0';
    // --- END Sanitize Realtime Voice Agent settings ---

    // --- NEW: Popup Label (Hint) above trigger ---
    $sanitized['popup_label_enabled'] = (isset($raw_settings['popup_label_enabled']) && $raw_settings['popup_label_enabled'] === '1') ? '1' : '0';
    $allowed_modes = ['always','on_delay','until_open','until_dismissed'];
    $sanitized['popup_label_mode'] = isset($raw_settings['popup_label_mode']) && in_array($raw_settings['popup_label_mode'], $allowed_modes, true)
        ? $raw_settings['popup_label_mode']
        : 'on_delay';
    $sanitized['popup_label_text'] = isset($raw_settings['popup_label_text']) ? sanitize_text_field($raw_settings['popup_label_text']) : '';
    $sanitized['popup_label_delay_seconds'] = isset($raw_settings['popup_label_delay_seconds']) ? max(0, absint($raw_settings['popup_label_delay_seconds'])) : 2;
    $sanitized['popup_label_auto_hide_seconds'] = isset($raw_settings['popup_label_auto_hide_seconds']) ? max(0, absint($raw_settings['popup_label_auto_hide_seconds'])) : 0;
    $sanitized['popup_label_dismissible'] = (isset($raw_settings['popup_label_dismissible']) && $raw_settings['popup_label_dismissible'] === '1') ? '1' : '0';
    $allowed_freq = ['always','once_per_session','once_per_visitor'];
    $sanitized['popup_label_frequency'] = isset($raw_settings['popup_label_frequency']) && in_array($raw_settings['popup_label_frequency'], $allowed_freq, true)
        ? $raw_settings['popup_label_frequency']
        : 'once_per_visitor';
    $sanitized['popup_label_show_on_mobile'] = (isset($raw_settings['popup_label_show_on_mobile']) && $raw_settings['popup_label_show_on_mobile'] === '1') ? '1' : '0';
    $sanitized['popup_label_show_on_desktop'] = (isset($raw_settings['popup_label_show_on_desktop']) && $raw_settings['popup_label_show_on_desktop'] === '1') ? '1' : '0';
    $sanitized['popup_label_version'] = isset($raw_settings['popup_label_version']) ? sanitize_text_field($raw_settings['popup_label_version']) : '';
    // NEW: Popup Hint Size
    $allowed_sizes = ['small','medium','large','xlarge'];
    $sanitized['popup_label_size'] = isset($raw_settings['popup_label_size']) && in_array($raw_settings['popup_label_size'], $allowed_sizes, true)
        ? $raw_settings['popup_label_size']
        : 'medium';
    
    // --- ADDED: Sanitize embed allowed domains ---
    $raw_domains = isset($raw_settings['embed_allowed_domains']) ? trim($raw_settings['embed_allowed_domains']) : '';
    $domains_array = preg_split('/[\s,]+/', $raw_domains, -1, PREG_SPLIT_NO_EMPTY);
    $sanitized_domains = [];
    foreach ($domains_array as $domain) {
        $sanitized_url = esc_url_raw(trim($domain));
        if (!empty($sanitized_url)) {
            $sanitized_domains[] = rtrim($sanitized_url, '/');
        }
    }
    $sanitized['embed_allowed_domains'] = implode("\n", array_unique($sanitized_domains));
    // --- END ADDED ---

    // Sanitize Custom Theme Settings
    $custom_theme_settings_raw = $raw_settings['custom_theme_settings'] ?? [];
    $custom_theme_settings_sanitized = [];

    foreach (array_keys($custom_theme_defaults) as $key) {
        if (strpos($key, '_placeholder') !== false) {
            continue;
        }

        $value = $custom_theme_settings_raw[$key] ?? '';
        if (str_ends_with($key, '_color')) {
            if (preg_match('/^#(?:[0-9a-fA-F]{3}){1,2}$/', $value) || preg_match('/^rgba?\((\d{1,3}%?,\s*){2,3}\d{1,3}%?(,\s*(0|1|0?\.\d+))?\)$/', $value)) {
                $custom_theme_settings_sanitized[$key] = $value;
            } else {
                $custom_theme_settings_sanitized[$key] = $custom_theme_defaults[$key] ?? '#FFFFFF';
            }
        } elseif ($key === 'font_family') {
            $allowed_fonts = [
                '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif',
                'Arial, Helvetica, sans-serif', 'Verdana, Geneva, sans-serif', 'Tahoma, Geneva, sans-serif',
                '"Trebuchet MS", Helvetica, sans-serif', '"Times New Roman", Times, serif', 'Georgia, serif',
                'Garamond, serif', '"Courier New", Courier, monospace', '"Brush Script MT", cursive', 'inherit'
            ];
            $custom_theme_settings_sanitized[$key] = in_array($value, $allowed_fonts, true) ? $value : ($custom_theme_defaults['font_family'] ?? 'inherit');
        } elseif ($key === 'bubble_border_radius' ||
                   $key === 'container_max_width' ||
                   $key === 'popup_width' ||
                   $key === 'container_height' ||
                   $key === 'container_min_height' ||
                   $key === 'popup_height' ||
                   $key === 'popup_min_height'
        ) {
            $custom_theme_settings_sanitized[$key] = ($value === '' || !is_numeric($value)) ? '' : (string)max(0, absint($value));
        } elseif ($key === 'container_max_height' || $key === 'popup_max_height') {
            $custom_theme_settings_sanitized[$key] = ($value === '' || !is_numeric($value)) ? '' : (string)max(1, min(absint($value), 100));
        } else {
            $custom_theme_settings_sanitized[$key] = sanitize_text_field($value);
        }
    }
    $sanitized['custom_theme_settings'] = $custom_theme_settings_sanitized;
    
    $sanitized['triggers_json'] = isset($raw_settings['triggers_json']) ? trim(wp_unslash($raw_settings['triggers_json'])) : '[]';

    // --- NEW: Sanitize WhatsApp connector mapping ---
    $wa_ids = [];
    if (isset($raw_settings['whatsapp_connector_ids'])) {
        $raw_wa = $raw_settings['whatsapp_connector_ids'];
        if (is_string($raw_wa)) {
            $decoded = json_decode($raw_wa, true);
            if (is_array($decoded)) { $raw_wa = $decoded; }
        }
        if (is_array($raw_wa)) {
            foreach ($raw_wa as $id) {
                $sid = sanitize_key((string)$id);
                if ($sid !== '') { $wa_ids[] = $sid; }
            }
            $wa_ids = array_values(array_unique($wa_ids));
        }
    }
    $sanitized['whatsapp_connector_ids'] = $wa_ids;
    
    return $sanitized;
}
