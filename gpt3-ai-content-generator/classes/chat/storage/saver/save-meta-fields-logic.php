<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/chat/storage/saver/save-meta-fields-logic.php
// Status: MODIFIED

namespace WPAICG\Chat\Storage\SaverMethods;

use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Saves the sanitized bot settings as post meta fields.
 * UPDATED: Saves new custom theme settings.
 * NEW: Saves triggers JSON using AIPKit_Trigger_Storage::META_KEY.
 *
 * @param int $botId The ID of the bot post.
 * @param array $sanitized_settings The array of sanitized settings.
 * @return bool|WP_Error Returns true on success, or WP_Error if JSON for triggers is invalid.
 */
function save_meta_fields_logic(int $botId, array $sanitized_settings): bool|WP_Error
{
    update_post_meta($botId, '_aipkit_greeting_message', $sanitized_settings['greeting']);
    update_post_meta($botId, '_aipkit_provider', $sanitized_settings['provider']);
    update_post_meta($botId, '_aipkit_theme', $sanitized_settings['theme']);
    update_post_meta($botId, '_aipkit_instructions', $sanitized_settings['instructions']);
    update_post_meta($botId, '_aipkit_popup_enabled', $sanitized_settings['popup_enabled']);
    update_post_meta($botId, '_aipkit_popup_position', $sanitized_settings['popup_position']);
    update_post_meta($botId, '_aipkit_popup_delay', $sanitized_settings['popup_delay']);
    update_post_meta($botId, '_aipkit_site_wide_enabled', $sanitized_settings['site_wide_enabled']);
    update_post_meta($botId, '_aipkit_popup_icon_size', $sanitized_settings['popup_icon_size']);
    update_post_meta($botId, '_aipkit_popup_icon_type', $sanitized_settings['popup_icon_type']);
    update_post_meta($botId, '_aipkit_popup_icon_style', $sanitized_settings['popup_icon_style']);
    update_post_meta($botId, '_aipkit_popup_icon_value', $sanitized_settings['popup_icon_value']);
    update_post_meta($botId, '_aipkit_stream_enabled', $sanitized_settings['stream_enabled']);
    update_post_meta($botId, '_aipkit_footer_text', $sanitized_settings['footer_text']);
    update_post_meta($botId, '_aipkit_enable_fullscreen', $sanitized_settings['enable_fullscreen']);
    update_post_meta($botId, '_aipkit_enable_download', $sanitized_settings['enable_download']);
    update_post_meta($botId, '_aipkit_enable_copy_button', $sanitized_settings['enable_copy_button']);
    update_post_meta($botId, '_aipkit_enable_feedback', $sanitized_settings['enable_feedback']);
    update_post_meta($botId, '_aipkit_enable_conversation_sidebar', $sanitized_settings['enable_conversation_sidebar']);
    update_post_meta($botId, '_aipkit_custom_typing_text', $sanitized_settings['custom_typing_text']);
    update_post_meta($botId, '_aipkit_input_placeholder', $sanitized_settings['input_placeholder']);
    update_post_meta($botId, '_aipkit_temperature', (string)$sanitized_settings['temperature']);
    update_post_meta($botId, '_aipkit_max_completion_tokens', $sanitized_settings['max_completion_tokens']);
    update_post_meta($botId, '_aipkit_max_messages', $sanitized_settings['max_messages']);
    update_post_meta($botId, '_aipkit_reasoning_effort', $sanitized_settings['reasoning_effort']);
    update_post_meta($botId, '_aipkit_enable_conversation_starters', $sanitized_settings['enable_conversation_starters']);
    update_post_meta($botId, '_aipkit_conversation_starters', $sanitized_settings['conversation_starters']); // Already JSON
    update_post_meta($botId, '_aipkit_content_aware_enabled', $sanitized_settings['content_aware_enabled']);
    update_post_meta($botId, '_aipkit_openai_conversation_state_enabled', $sanitized_settings['openai_conversation_state_enabled']);
    update_post_meta($botId, '_aipkit_token_limit_mode', $sanitized_settings['token_limit_mode']);
    if ($sanitized_settings['token_guest_limit'] === '') {
        delete_post_meta($botId, '_aipkit_token_guest_limit');
    } else {
        update_post_meta($botId, '_aipkit_token_guest_limit', $sanitized_settings['token_guest_limit']);
    }
    if ($sanitized_settings['token_user_limit'] === '') {
        delete_post_meta($botId, '_aipkit_token_user_limit');
    } else {
        update_post_meta($botId, '_aipkit_token_user_limit', $sanitized_settings['token_user_limit']);
    }
    if (empty(json_decode($sanitized_settings['token_role_limits'], true))) {
        delete_post_meta($botId, '_aipkit_token_role_limits');
    } else {
        update_post_meta($botId, '_aipkit_token_role_limits', $sanitized_settings['token_role_limits']);
    }
    update_post_meta($botId, '_aipkit_token_reset_period', $sanitized_settings['token_reset_period']);
    if (empty($sanitized_settings['token_limit_message'])) {
        delete_post_meta($botId, '_aipkit_token_limit_message');
    } else {
        update_post_meta($botId, '_aipkit_token_limit_message', $sanitized_settings['token_limit_message']);
    }
    if (!empty($sanitized_settings['model'])) {
        update_post_meta($botId, '_aipkit_model', $sanitized_settings['model']);
    } else {
        delete_post_meta($botId, '_aipkit_model');
    }
    delete_post_meta($botId, '_aipkit_azure_endpoint');
    delete_post_meta($botId, '_aipkit_azure_deployment');
    update_post_meta($botId, '_aipkit_tts_enabled', $sanitized_settings['tts_enabled']);
    update_post_meta($botId, '_aipkit_tts_provider', $sanitized_settings['tts_provider']);
    update_post_meta($botId, '_aipkit_tts_google_voice_id', $sanitized_settings['tts_google_voice_id']);
    update_post_meta($botId, '_aipkit_tts_openai_voice_id', $sanitized_settings['tts_openai_voice_id']);
    update_post_meta($botId, '_aipkit_tts_openai_model_id', $sanitized_settings['tts_openai_model_id']);
    update_post_meta($botId, '_aipkit_tts_elevenlabs_voice_id', $sanitized_settings['tts_elevenlabs_voice_id']);
    update_post_meta($botId, '_aipkit_tts_elevenlabs_model_id', $sanitized_settings['tts_elevenlabs_model_id']);
    update_post_meta($botId, '_aipkit_tts_auto_play', $sanitized_settings['tts_auto_play']);
    update_post_meta($botId, '_aipkit_enable_voice_input', $sanitized_settings['enable_voice_input']);
    update_post_meta($botId, '_aipkit_stt_provider', $sanitized_settings['stt_provider']);
    update_post_meta($botId, '_aipkit_stt_openai_model_id', $sanitized_settings['stt_openai_model_id']);
    update_post_meta($botId, '_aipkit_stt_azure_model_id', $sanitized_settings['stt_azure_model_id']);
    update_post_meta($botId, '_aipkit_image_triggers', $sanitized_settings['image_triggers']);
    update_post_meta($botId, '_aipkit_chat_image_model_id', $sanitized_settings['chat_image_model_id']);
    update_post_meta($botId, '_aipkit_enable_file_upload', $sanitized_settings['enable_file_upload']);
    update_post_meta($botId, '_aipkit_enable_image_upload', $sanitized_settings['enable_image_upload']);
    update_post_meta($botId, '_aipkit_enable_vector_store', $sanitized_settings['enable_vector_store']);
    update_post_meta($botId, '_aipkit_vector_store_provider', $sanitized_settings['vector_store_provider']);
    if ($sanitized_settings['vector_store_provider'] === 'openai') {
        update_post_meta($botId, '_aipkit_openai_vector_store_ids', $sanitized_settings['openai_vector_store_ids']);
        delete_post_meta($botId, '_aipkit_pinecone_index_name');
        delete_post_meta($botId, '_aipkit_qdrant_collection_name');
        delete_post_meta($botId, '_aipkit_qdrant_collection_names');
        delete_post_meta($botId, '_aipkit_vector_embedding_provider');
        delete_post_meta($botId, '_aipkit_vector_embedding_model');
    } elseif ($sanitized_settings['vector_store_provider'] === 'pinecone') {
        update_post_meta($botId, '_aipkit_pinecone_index_name', $sanitized_settings['pinecone_index_name']);
        update_post_meta($botId, '_aipkit_vector_embedding_provider', $sanitized_settings['vector_embedding_provider']);
        update_post_meta($botId, '_aipkit_vector_embedding_model', $sanitized_settings['vector_embedding_model']);
        delete_post_meta($botId, '_aipkit_openai_vector_store_ids');
        delete_post_meta($botId, '_aipkit_qdrant_collection_name');
        delete_post_meta($botId, '_aipkit_qdrant_collection_names');
    } elseif ($sanitized_settings['vector_store_provider'] === 'qdrant') {
        update_post_meta($botId, '_aipkit_qdrant_collection_name', $sanitized_settings['qdrant_collection_name']);
        update_post_meta($botId, '_aipkit_qdrant_collection_names', $sanitized_settings['qdrant_collection_names']);
        update_post_meta($botId, '_aipkit_vector_embedding_provider', $sanitized_settings['vector_embedding_provider']);
        update_post_meta($botId, '_aipkit_vector_embedding_model', $sanitized_settings['vector_embedding_model']);
        delete_post_meta($botId, '_aipkit_openai_vector_store_ids');
        delete_post_meta($botId, '_aipkit_pinecone_index_name');
    } else {
        delete_post_meta($botId, '_aipkit_openai_vector_store_ids');
        delete_post_meta($botId, '_aipkit_pinecone_index_name');
        delete_post_meta($botId, '_aipkit_qdrant_collection_name');
        delete_post_meta($botId, '_aipkit_qdrant_collection_names');
        delete_post_meta($botId, '_aipkit_vector_embedding_provider');
        delete_post_meta($botId, '_aipkit_vector_embedding_model');
    }
    update_post_meta($botId, '_aipkit_vector_store_top_k', $sanitized_settings['vector_store_top_k']);
    update_post_meta($botId, '_aipkit_vector_store_confidence_threshold', $sanitized_settings['vector_store_confidence_threshold']); // NEW
    update_post_meta($botId, '_aipkit_openai_web_search_enabled', $sanitized_settings['openai_web_search_enabled']);
    if ($sanitized_settings['openai_web_search_enabled'] === '1') {
        update_post_meta($botId, '_aipkit_openai_web_search_context_size', $sanitized_settings['openai_web_search_context_size']);
        update_post_meta($botId, '_aipkit_openai_web_search_loc_type', $sanitized_settings['openai_web_search_loc_type']);
        if ($sanitized_settings['openai_web_search_loc_type'] === 'approximate') {
            update_post_meta($botId, '_aipkit_openai_web_search_loc_country', $sanitized_settings['openai_web_search_loc_country']);
            update_post_meta($botId, '_aipkit_openai_web_search_loc_city', $sanitized_settings['openai_web_search_loc_city']);
            update_post_meta($botId, '_aipkit_openai_web_search_loc_region', $sanitized_settings['openai_web_search_loc_region']);
            update_post_meta($botId, '_aipkit_openai_web_search_loc_timezone', $sanitized_settings['openai_web_search_loc_timezone']);
        } else {
            delete_post_meta($botId, '_aipkit_openai_web_search_loc_country');
            delete_post_meta($botId, '_aipkit_openai_web_search_loc_city');
            delete_post_meta($botId, '_aipkit_openai_web_search_loc_region');
            delete_post_meta($botId, '_aipkit_openai_web_search_loc_timezone');
        }
    } else {
        delete_post_meta($botId, '_aipkit_openai_web_search_context_size');
        delete_post_meta($botId, '_aipkit_openai_web_search_loc_type');
        delete_post_meta($botId, '_aipkit_openai_web_search_loc_country');
        delete_post_meta($botId, '_aipkit_openai_web_search_loc_city');
        delete_post_meta($botId, '_aipkit_openai_web_search_loc_region');
        delete_post_meta($botId, '_aipkit_openai_web_search_loc_timezone');
    }
    update_post_meta($botId, '_aipkit_google_search_grounding_enabled', $sanitized_settings['google_search_grounding_enabled']);
    if ($sanitized_settings['google_search_grounding_enabled'] === '1') {
        update_post_meta($botId, '_aipkit_google_grounding_mode', $sanitized_settings['google_grounding_mode']);
        if ($sanitized_settings['google_grounding_mode'] === 'MODE_DYNAMIC') {
            update_post_meta($botId, '_aipkit_google_grounding_dynamic_threshold', (string)$sanitized_settings['google_grounding_dynamic_threshold']);
        } else {
            delete_post_meta($botId, '_aipkit_google_grounding_dynamic_threshold');
        }
    } else {
        delete_post_meta($botId, '_aipkit_google_grounding_mode');
        delete_post_meta($botId, '_aipkit_google_grounding_dynamic_threshold');
    }

    if (isset($sanitized_settings['custom_theme_settings']) && is_array($sanitized_settings['custom_theme_settings'])) {
        foreach ($sanitized_settings['custom_theme_settings'] as $key => $value) {
            update_post_meta($botId, '_aipkit_cts_' . $key, $value);
        }
    }
    
    // --- Save Realtime Voice Agent settings ---
    update_post_meta($botId, '_aipkit_enable_realtime_voice', $sanitized_settings['enable_realtime_voice']);
    update_post_meta($botId, '_aipkit_direct_voice_mode', $sanitized_settings['direct_voice_mode']);
    update_post_meta($botId, '_aipkit_realtime_model', $sanitized_settings['realtime_model']);
    update_post_meta($botId, '_aipkit_realtime_voice', $sanitized_settings['realtime_voice']);
    update_post_meta($botId, '_aipkit_turn_detection', $sanitized_settings['turn_detection']);
    update_post_meta($botId, '_aipkit_speed', (string)$sanitized_settings['speed']);
    update_post_meta($botId, '_aipkit_input_audio_format', $sanitized_settings['input_audio_format']);
    update_post_meta($botId, '_aipkit_output_audio_format', $sanitized_settings['output_audio_format']);
    update_post_meta($botId, '_aipkit_input_audio_noise_reduction', $sanitized_settings['input_audio_noise_reduction']);
    // --- END ---

    // --- NEW: Popup Label (Hint) above trigger ---
    update_post_meta($botId, '_aipkit_popup_label_enabled', $sanitized_settings['popup_label_enabled']);
    update_post_meta($botId, '_aipkit_popup_label_text', $sanitized_settings['popup_label_text']);
    update_post_meta($botId, '_aipkit_popup_label_mode', $sanitized_settings['popup_label_mode']);
    update_post_meta($botId, '_aipkit_popup_label_delay_seconds', $sanitized_settings['popup_label_delay_seconds']);
    update_post_meta($botId, '_aipkit_popup_label_auto_hide_seconds', $sanitized_settings['popup_label_auto_hide_seconds']);
    update_post_meta($botId, '_aipkit_popup_label_dismissible', $sanitized_settings['popup_label_dismissible']);
    update_post_meta($botId, '_aipkit_popup_label_frequency', $sanitized_settings['popup_label_frequency']);
    update_post_meta($botId, '_aipkit_popup_label_show_on_mobile', $sanitized_settings['popup_label_show_on_mobile']);
    update_post_meta($botId, '_aipkit_popup_label_show_on_desktop', $sanitized_settings['popup_label_show_on_desktop']);
    update_post_meta($botId, '_aipkit_popup_label_version', $sanitized_settings['popup_label_version']);
    update_post_meta($botId, '_aipkit_popup_label_size', $sanitized_settings['popup_label_size']);
    // --- END NEW ---
    
    // --- ADDED: Save embed allowed domains ---
    update_post_meta($botId, '_aipkit_embed_allowed_domains', $sanitized_settings['embed_allowed_domains']);
    // --- END ADDED ---

    $trigger_meta_key = '_aipkit_chatbot_triggers'; // Fallback key
    $trigger_storage_class_name = '\WPAICG\Lib\Chat\Triggers\AIPKit_Trigger_Storage';

    if (class_exists($trigger_storage_class_name)) {
        $trigger_meta_key = $trigger_storage_class_name::META_KEY;
    }

    $triggers_json_string = $sanitized_settings['triggers_json'] ?? '[]';
    $decoded_triggers_for_validation = json_decode($triggers_json_string, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return new WP_Error('invalid_trigger_json_meta', __('Invalid JSON format for triggers. Triggers were not saved.', 'gpt3-ai-content-generator'));
    }
    if (!is_array($decoded_triggers_for_validation)) {
        $triggers_json_string = '[]';
    }
    update_post_meta($botId, $trigger_meta_key, $triggers_json_string);

    // --- NEW: Save WhatsApp connector mapping ---
    if (isset($sanitized_settings['whatsapp_connector_ids']) && is_array($sanitized_settings['whatsapp_connector_ids'])) {
        update_post_meta($botId, '_aipkit_whatsapp_connector_ids', $sanitized_settings['whatsapp_connector_ids']);
    } else {
        delete_post_meta($botId, '_aipkit_whatsapp_connector_ids');
    }

    return true;
}
