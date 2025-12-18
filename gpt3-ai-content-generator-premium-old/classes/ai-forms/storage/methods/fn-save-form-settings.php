<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/ai-forms/storage/methods/fn-save-form-settings.php
// Status: MODIFIED

namespace WPAICG\AIForms\Storage\Methods;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for saving AI Form settings.
 *
 * @param \WPAICG\AIForms\Storage\AIPKit_AI_Form_Storage $storageInstance The instance of the storage class.
 * @param int $form_id The ID of the form CPT.
 * @param array $settings An array containing settings.
 * @return bool True on success, false on failure.
 */
function save_form_settings_logic(\WPAICG\AIForms\Storage\AIPKit_AI_Form_Storage $storageInstance, int $form_id, array $settings): bool
{
    if (isset($settings['prompt_template'])) {
        update_post_meta($form_id, '_aipkit_ai_form_prompt_template', sanitize_textarea_field($settings['prompt_template']));
    }
    if (isset($settings['form_structure'])) {
        $structure_json = $settings['form_structure'];
        $decoded_structure = json_decode($structure_json, true);
        if (is_array($decoded_structure)) {
            update_post_meta($form_id, '_aipkit_ai_form_structure', wp_kses_post($structure_json));
        }
    }
    if (isset($settings['ai_provider'])) {
        update_post_meta($form_id, '_aipkit_ai_form_ai_provider', sanitize_text_field($settings['ai_provider']));
    }
    if (isset($settings['ai_model'])) {
        update_post_meta($form_id, '_aipkit_ai_form_ai_model', sanitize_text_field($settings['ai_model']));
    }
    if (isset($settings['temperature'])) {
        update_post_meta($form_id, '_aipkit_ai_form_temperature', sanitize_text_field($settings['temperature']));
    }
    if (isset($settings['max_tokens'])) {
        update_post_meta($form_id, '_aipkit_ai_form_max_tokens', absint($settings['max_tokens']));
    }
    if (isset($settings['top_p'])) {
        update_post_meta($form_id, '_aipkit_ai_form_top_p', sanitize_text_field($settings['top_p']));
    }
    if (isset($settings['frequency_penalty'])) {
        update_post_meta($form_id, '_aipkit_ai_form_frequency_penalty', sanitize_text_field($settings['frequency_penalty']));
    }
    if (isset($settings['presence_penalty'])) {
        update_post_meta($form_id, '_aipkit_ai_form_presence_penalty', sanitize_text_field($settings['presence_penalty']));
    }
    if (isset($settings['reasoning_effort'])) {
        update_post_meta($form_id, '_aipkit_ai_form_reasoning_effort', sanitize_key($settings['reasoning_effort']));
    }

    // --- Save Vector Settings ---
    if (isset($settings['enable_vector_store'])) {
        update_post_meta($form_id, '_aipkit_ai_form_enable_vector_store', $settings['enable_vector_store'] === '1' ? '1' : '0');
    }
    if (isset($settings['vector_store_provider'])) {
        update_post_meta($form_id, '_aipkit_ai_form_vector_store_provider', sanitize_key($settings['vector_store_provider']));
    }
    if (isset($settings['openai_vector_store_ids'])) {
        $sanitized_ids = is_array($settings['openai_vector_store_ids']) ? array_map('sanitize_text_field', $settings['openai_vector_store_ids']) : [];
        update_post_meta($form_id, '_aipkit_ai_form_openai_vector_store_ids', wp_json_encode(array_values(array_unique($sanitized_ids))));
    }
    if (isset($settings['pinecone_index_name'])) {
        update_post_meta($form_id, '_aipkit_ai_form_pinecone_index_name', sanitize_text_field($settings['pinecone_index_name']));
    }
    if (isset($settings['qdrant_collection_name'])) {
        update_post_meta($form_id, '_aipkit_ai_form_qdrant_collection_name', sanitize_text_field($settings['qdrant_collection_name']));
    }
    if (isset($settings['vector_embedding_provider'])) {
        update_post_meta($form_id, '_aipkit_ai_form_vector_embedding_provider', sanitize_key($settings['vector_embedding_provider']));
    }
    if (isset($settings['vector_embedding_model'])) {
        update_post_meta($form_id, '_aipkit_ai_form_vector_embedding_model', sanitize_text_field($settings['vector_embedding_model']));
    }
    if (isset($settings['vector_store_top_k'])) {
        update_post_meta($form_id, '_aipkit_ai_form_vector_store_top_k', absint($settings['vector_store_top_k']));
    }
    if (isset($settings['vector_store_confidence_threshold'])) {
        update_post_meta($form_id, '_aipkit_ai_form_vector_store_confidence_threshold', absint($settings['vector_store_confidence_threshold']));
    }
    // --- END Vector Settings ---

    // --- NEW: Save Web Search & Grounding Settings ---
    if (isset($settings['openai_web_search_enabled'])) {
        update_post_meta($form_id, '_aipkit_ai_form_openai_web_search_enabled', $settings['openai_web_search_enabled'] === '1' ? '1' : '0');
    }
    if (isset($settings['google_search_grounding_enabled'])) {
        update_post_meta($form_id, '_aipkit_ai_form_google_search_grounding_enabled', $settings['google_search_grounding_enabled'] === '1' ? '1' : '0');
    }
    
    // --- Save OpenAI Web Search Sub-Settings ---
    if (isset($settings['openai_web_search_context_size'])) {
        update_post_meta($form_id, '_aipkit_ai_form_openai_web_search_context_size', sanitize_text_field($settings['openai_web_search_context_size']));
    }
    if (isset($settings['openai_web_search_loc_type'])) {
        update_post_meta($form_id, '_aipkit_ai_form_openai_web_search_loc_type', sanitize_text_field($settings['openai_web_search_loc_type']));
    }
    if (isset($settings['openai_web_search_loc_country'])) {
        update_post_meta($form_id, '_aipkit_ai_form_openai_web_search_loc_country', sanitize_text_field($settings['openai_web_search_loc_country']));
    }
    if (isset($settings['openai_web_search_loc_city'])) {
        update_post_meta($form_id, '_aipkit_ai_form_openai_web_search_loc_city', sanitize_text_field($settings['openai_web_search_loc_city']));
    }
    if (isset($settings['openai_web_search_loc_region'])) {
        update_post_meta($form_id, '_aipkit_ai_form_openai_web_search_loc_region', sanitize_text_field($settings['openai_web_search_loc_region']));
    }
    if (isset($settings['openai_web_search_loc_timezone'])) {
        update_post_meta($form_id, '_aipkit_ai_form_openai_web_search_loc_timezone', sanitize_text_field($settings['openai_web_search_loc_timezone']));
    }
    
    // --- Save Google Search Grounding Sub-Settings ---
    if (isset($settings['google_grounding_mode'])) {
        update_post_meta($form_id, '_aipkit_ai_form_google_grounding_mode', sanitize_text_field($settings['google_grounding_mode']));
    }
    if (isset($settings['google_grounding_dynamic_threshold'])) {
        update_post_meta($form_id, '_aipkit_ai_form_google_grounding_dynamic_threshold', floatval($settings['google_grounding_dynamic_threshold']));
    }
    // --- END NEW ---


    // --- Save Labels ---
    if (isset($settings['labels']) && is_array($settings['labels'])) {
            $sanitized_labels = [];
        $allowed_keys = ['generate_button', 'stop_button', 'download_button', 'save_button', 'copy_button', 'provider_label', 'model_label'];
        foreach ($settings['labels'] as $key => $value) {
            if (in_array($key, $allowed_keys, true)) {
                $sanitized_labels[$key] = sanitize_text_field($value);
            }
        }
        // FIX: Use JSON_UNESCAPED_UNICODE to prevent encoding issues with special characters.
        $json_to_save = wp_json_encode($sanitized_labels, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        update_post_meta($form_id, '_aipkit_ai_form_labels', $json_to_save);
    }

    return true;
}