<?php

namespace WPAICG\Admin\Ajax\AIForms;

use WP_Error;
use WPAICG\AIForms\Admin\AIPKit_AI_Form_Ajax_Handler;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles the logic for saving an AI form.
 * Called by AIPKit_AI_Form_Ajax_Handler::ajax_save_ai_form().
 *
 * @param AIPKit_AI_Form_Ajax_Handler $handler_instance
 * @return void
 */
function do_ajax_save_form_logic(AIPKit_AI_Form_Ajax_Handler $handler_instance): void
{

    $form_storage = $handler_instance->get_form_storage();

    if (!$form_storage) {
        $handler_instance->send_wp_error(new WP_Error('storage_missing', __('Form storage component is not available.', 'gpt3-ai-content-generator')), 500);
        return;
    }

    // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is verified in the calling class method.
    $post_data = wp_unslash($_POST);

    $form_id = isset($post_data['form_id']) && !empty($post_data['form_id']) ? absint($post_data['form_id']) : null;
    $title = isset($post_data['title']) ? sanitize_text_field($post_data['title']) : '';
    $prompt_template = isset($post_data['prompt_template']) ? sanitize_textarea_field($post_data['prompt_template']) : '';
    $form_structure_json = isset($post_data['form_structure']) ? wp_kses_post($post_data['form_structure']) : '[]';

    // Process labels - now receiving as a JSON string from JavaScript
    $default_labels = [
        'generate_button' => __('Generate', 'gpt3-ai-content-generator'),
        'stop_button'     => __('Stop', 'gpt3-ai-content-generator'),
        'download_button' => __('Download', 'gpt3-ai-content-generator'),
        'save_button'     => __('Save', 'gpt3-ai-content-generator'),
        'copy_button'     => __('Copy', 'gpt3-ai-content-generator'),
        'provider_label'  => __('AI Provider', 'gpt3-ai-content-generator'),
        'model_label'     => __('AI Model', 'gpt3-ai-content-generator'),
    ];

    $submitted_labels = [];
    if (isset($post_data['labels']) && !empty($post_data['labels'])) {
        $labels_json = $post_data['labels'];
        $decoded_labels = json_decode($labels_json, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded_labels)) {
            $submitted_labels = $decoded_labels;
        }
    }

    // Merge defaults: Use submitted value if not empty, otherwise use default.
    $final_labels = [];
    foreach ($default_labels as $key => $default_value) {
        $submitted_value = isset($submitted_labels[$key]) ? trim($submitted_labels[$key]) : '';
        $final_labels[sanitize_key($key)] = sanitize_text_field(!empty($submitted_value) ? $submitted_value : $default_value);
    }


    // --- Get AI config fields from POST ---
    $ai_provider = isset($post_data['ai_provider']) ? sanitize_text_field($post_data['ai_provider']) : null;
    $ai_model = isset($post_data['ai_model']) ? sanitize_text_field($post_data['ai_model']) : null;
    $temperature = isset($post_data['temperature']) ? sanitize_text_field($post_data['temperature']) : null;
    $max_tokens = isset($post_data['max_tokens']) ? absint($post_data['max_tokens']) : null;
    $top_p = isset($post_data['top_p']) ? sanitize_text_field($post_data['top_p']) : null;
    $frequency_penalty = isset($post_data['frequency_penalty']) ? sanitize_text_field($post_data['frequency_penalty']) : null;
    $presence_penalty = isset($post_data['presence_penalty']) ? sanitize_text_field($post_data['presence_penalty']) : null;
    $reasoning_effort = isset($post_data['reasoning_effort']) ? sanitize_key($post_data['reasoning_effort']) : 'low';

    // --- Get Vector config fields from POST ---
    $enable_vector_store = isset($post_data['enable_vector_store']) && $post_data['enable_vector_store'] === '1' ? '1' : '0';
    $vector_store_provider = isset($post_data['vector_store_provider']) ? sanitize_key($post_data['vector_store_provider']) : 'openai';
    $openai_vector_store_ids = isset($post_data['openai_vector_store_ids']) && is_array($post_data['openai_vector_store_ids']) ? array_map('sanitize_text_field', $post_data['openai_vector_store_ids']) : [];
    $pinecone_index_name = isset($post_data['pinecone_index_name']) ? sanitize_text_field($post_data['pinecone_index_name']) : '';
    $qdrant_collection_name = isset($post_data['qdrant_collection_name']) ? sanitize_text_field($post_data['qdrant_collection_name']) : '';
    $vector_embedding_provider = isset($post_data['vector_embedding_provider']) ? sanitize_key($post_data['vector_embedding_provider']) : 'openai';
    $vector_embedding_model = isset($post_data['vector_embedding_model']) ? sanitize_text_field($post_data['vector_embedding_model']) : '';
    $vector_store_top_k = isset($post_data['vector_store_top_k']) ? absint($post_data['vector_store_top_k']) : 3;
    $vector_store_confidence_threshold = isset($post_data['vector_store_confidence_threshold']) ? absint($post_data['vector_store_confidence_threshold']) : 20;

    // --- Get Web Search config fields from POST ---
    $openai_web_search_enabled = isset($post_data['openai_web_search_enabled']) && $post_data['openai_web_search_enabled'] === '1' ? '1' : '0';
    $google_search_grounding_enabled = isset($post_data['google_search_grounding_enabled']) && $post_data['google_search_grounding_enabled'] === '1' ? '1' : '0';
    
    // OpenAI Web Search sub-settings
    $openai_web_search_context_size = isset($post_data['openai_web_search_context_size']) ? sanitize_text_field($post_data['openai_web_search_context_size']) : 'medium';
    $openai_web_search_loc_type = isset($post_data['openai_web_search_loc_type']) ? sanitize_text_field($post_data['openai_web_search_loc_type']) : 'none';
    $openai_web_search_loc_country = isset($post_data['openai_web_search_loc_country']) ? sanitize_text_field($post_data['openai_web_search_loc_country']) : '';
    $openai_web_search_loc_city = isset($post_data['openai_web_search_loc_city']) ? sanitize_text_field($post_data['openai_web_search_loc_city']) : '';
    $openai_web_search_loc_region = isset($post_data['openai_web_search_loc_region']) ? sanitize_text_field($post_data['openai_web_search_loc_region']) : '';
    $openai_web_search_loc_timezone = isset($post_data['openai_web_search_loc_timezone']) ? sanitize_text_field($post_data['openai_web_search_loc_timezone']) : '';
    
    // Google Search Grounding sub-settings
    $google_grounding_mode = isset($post_data['google_grounding_mode']) ? sanitize_text_field($post_data['google_grounding_mode']) : 'DEFAULT_MODE';
    $google_grounding_dynamic_threshold = isset($post_data['google_grounding_dynamic_threshold']) ? floatval($post_data['google_grounding_dynamic_threshold']) : 0.30;

    $decoded_structure = json_decode($form_structure_json, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded_structure)) {
        $handler_instance->send_wp_error(new WP_Error('invalid_structure_json', __('Invalid form structure data submitted.', 'gpt3-ai-content-generator')), 400);
        return;
    }

    if (empty($title)) {
        $handler_instance->send_wp_error(new WP_Error('title_required', __('Form title cannot be empty.', 'gpt3-ai-content-generator')), 400);
        return;
    }
    if (empty($prompt_template)) {
        $handler_instance->send_wp_error(new WP_Error('prompt_required', __('Prompt template is required.', 'gpt3-ai-content-generator')), 400);
        return;
    }

    $settings = [
        'prompt_template' => $prompt_template,
        'form_structure'  => $form_structure_json,
        'ai_provider' => $ai_provider,
        'ai_model' => $ai_model,
        'temperature' => $temperature,
        'max_tokens' => $max_tokens,
        'top_p' => $top_p,
        'frequency_penalty' => $frequency_penalty,
        'presence_penalty' => $presence_penalty,
        'reasoning_effort' => $reasoning_effort,
        // Vector settings
        'enable_vector_store' => $enable_vector_store,
        'vector_store_provider' => $vector_store_provider,
        'openai_vector_store_ids' => $openai_vector_store_ids,
        'pinecone_index_name' => $pinecone_index_name,
        'qdrant_collection_name' => $qdrant_collection_name,
        'vector_embedding_provider' => $vector_embedding_provider,
        'vector_embedding_model' => $vector_embedding_model,
        'vector_store_top_k' => $vector_store_top_k,
        'vector_store_confidence_threshold' => $vector_store_confidence_threshold,
        // Web Search settings
        'openai_web_search_enabled' => $openai_web_search_enabled,
        'google_search_grounding_enabled' => $google_search_grounding_enabled,
        // OpenAI Web Search sub-settings
        'openai_web_search_context_size' => $openai_web_search_context_size,
        'openai_web_search_loc_type' => $openai_web_search_loc_type,
        'openai_web_search_loc_country' => $openai_web_search_loc_country,
        'openai_web_search_loc_city' => $openai_web_search_loc_city,
        'openai_web_search_loc_region' => $openai_web_search_loc_region,
        'openai_web_search_loc_timezone' => $openai_web_search_loc_timezone,
        // Google Search Grounding sub-settings
        'google_grounding_mode' => $google_grounding_mode,
        'google_grounding_dynamic_threshold' => $google_grounding_dynamic_threshold,
        // Labels
        'labels' => $final_labels,
    ];

    if ($form_id) {
        $updated_post_id = wp_update_post([
            'ID' => $form_id,
            'post_title' => $title,
        ], true);

        if (is_wp_error($updated_post_id)) {
            $handler_instance->send_wp_error($updated_post_id);
            return;
        }
        $form_storage->save_form_settings($form_id, $settings);
        wp_send_json_success(['message' => __('Form updated successfully.', 'gpt3-ai-content-generator'), 'form_id' => $form_id]);
    } else {
        $result = $form_storage->create_form($title, $settings);
        if (is_wp_error($result)) {
            $handler_instance->send_wp_error($result);
        } else {
            wp_send_json_success(['message' => __('Form created successfully.', 'gpt3-ai-content-generator'), 'form_id' => $result]);
        }
    }
}