<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/ai-forms/storage/methods/fn-get-form-data.php
// Status: MODIFIED

namespace WPAICG\AIForms\Storage\Methods;

use WPAICG\AIForms\Admin\AIPKit_AI_Form_Admin_Setup;
use WPAICG\AIPKit_Providers;
use WPAICG\AIPKIT_AI_Settings;
use WP_Error;
use WP_Query;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for retrieving AI Form data and settings.
 *
 * @param \WPAICG\AIForms\Storage\AIPKit_AI_Form_Storage $storageInstance The instance of the storage class.
 * @param int $form_id The ID of the AI Form post.
 * @return array|WP_Error Form data array or WP_Error if not found or invalid.
 */
function get_form_data_logic(\WPAICG\AIForms\Storage\AIPKit_AI_Form_Storage $storageInstance, int $form_id): array|WP_Error
{
    if (!class_exists(AIPKit_AI_Form_Admin_Setup::class)) {
        return new WP_Error('dependency_missing', 'AI Form Admin Setup class not found.');
    }
    $post = get_post($form_id);
    if (!$post || $post->post_type !== AIPKit_AI_Form_Admin_Setup::POST_TYPE) {
        return new WP_Error('form_not_found', 'AI Form not found or invalid ID.');
    }
    if ($post->post_status !== 'publish' && $post->post_status !== 'draft') {
        return new WP_Error('form_not_active', 'AI Form is not currently active.');
    }

    $form_structure_json = get_post_meta($form_id, '_aipkit_ai_form_structure', true);
    $form_structure = json_decode($form_structure_json, true);

    // --- Backward Compatibility Migration ---
    if (is_array($form_structure) && !empty($form_structure) && (!isset($form_structure[0]['type']) || $form_structure[0]['type'] !== 'layout-row')) {
        $migrated_structure = [];
        foreach ($form_structure as $element) {
            // Wrap each old element in its own 1-column row
            $timestamp = time() + count($migrated_structure); // Ensure unique timestamps
            $migrated_structure[] = [
                'internalId' => 'row-' . $timestamp,
                'type' => 'layout-row',
                'columns' => [
                    [
                        'internalId' => 'col-' . $timestamp . '-1',
                        'width' => '100%',
                        'elements' => [$element] // Place the old element inside
                    ]
                ]
            ];
        }
        $form_structure = $migrated_structure;
    } elseif (!is_array($form_structure)) {
        $form_structure = []; // Default to empty array if invalid JSON or not an array
    }
    // --- End Migration ---


    $default_provider_config = [];
    if (class_exists(\WPAICG\AIPKit_Providers::class)) {
        $default_provider_config = \WPAICG\AIPKit_Providers::get_default_provider_config();
    }

    $global_ai_params = [];
    if (class_exists(\WPAICG\AIPKIT_AI_Settings::class)) {
        $global_ai_params = \WPAICG\AIPKIT_AI_Settings::get_ai_parameters();
    }

    $form_temp = get_post_meta($form_id, '_aipkit_ai_form_temperature', true);
    $form_max_tokens = get_post_meta($form_id, '_aipkit_ai_form_max_tokens', true);
    $form_top_p = get_post_meta($form_id, '_aipkit_ai_form_top_p', true);
    $form_frequency_penalty = get_post_meta($form_id, '_aipkit_ai_form_frequency_penalty', true);
    $form_presence_penalty = get_post_meta($form_id, '_aipkit_ai_form_presence_penalty', true);

    $data = [
        'id' => $form_id,
        'title' => $post->post_title,
        'status' => $post->post_status,
        'prompt_template' => get_post_meta($form_id, '_aipkit_ai_form_prompt_template', true) ?: '',
        'structure' => $form_structure,
        'ai_provider' => get_post_meta($form_id, '_aipkit_ai_form_ai_provider', true) ?: ($default_provider_config['provider'] ?? 'OpenAI'),
        'ai_model' => get_post_meta($form_id, '_aipkit_ai_form_ai_model', true) ?: ($default_provider_config['model'] ?? ''),
        'temperature' => (is_numeric($form_temp) && $form_temp !== '') ? floatval($form_temp) : ($global_ai_params['temperature'] ?? 1.0),
        'max_tokens' => (is_numeric($form_max_tokens) && $form_max_tokens !== '') ? absint($form_max_tokens) : ($global_ai_params['max_completion_tokens'] ?? 4000),
        'top_p' => (is_numeric($form_top_p) && $form_top_p !== '') ? floatval($form_top_p) : ($global_ai_params['top_p'] ?? 1.0),
        'frequency_penalty' => (is_numeric($form_frequency_penalty) && $form_frequency_penalty !== '') ? floatval($form_frequency_penalty) : ($global_ai_params['frequency_penalty'] ?? 0.0),
        'presence_penalty' => (is_numeric($form_presence_penalty) && $form_presence_penalty !== '') ? floatval($form_presence_penalty) : ($global_ai_params['presence_penalty'] ?? 0.0),
        'reasoning_effort' => get_post_meta($form_id, '_aipkit_ai_form_reasoning_effort', true) ?: 'low',
    ];

    // --- Add Vector Settings ---
    $data['enable_vector_store'] = get_post_meta($form_id, '_aipkit_ai_form_enable_vector_store', true) ?: '0';
    $data['vector_store_provider'] = get_post_meta($form_id, '_aipkit_ai_form_vector_store_provider', true) ?: 'openai';

    $openai_vs_ids_json = get_post_meta($form_id, '_aipkit_ai_form_openai_vector_store_ids', true) ?: '[]';
    $openai_vs_ids_array = json_decode($openai_vs_ids_json, true);
    $data['openai_vector_store_ids'] = is_array($openai_vs_ids_array) ? $openai_vs_ids_array : [];

    $data['pinecone_index_name'] = get_post_meta($form_id, '_aipkit_ai_form_pinecone_index_name', true) ?: '';
    $data['qdrant_collection_name'] = get_post_meta($form_id, '_aipkit_ai_form_qdrant_collection_name', true) ?: '';

    $vector_embedding_provider = get_post_meta($form_id, '_aipkit_ai_form_vector_embedding_provider', true) ?: 'openai';
    if (!in_array($vector_embedding_provider, ['openai', 'google', 'azure'])) {
        $vector_embedding_provider = 'openai';
    }
    $data['vector_embedding_provider'] = $vector_embedding_provider;
    $data['vector_embedding_model'] = get_post_meta($form_id, '_aipkit_ai_form_vector_embedding_model', true) ?: '';
    $data['vector_store_top_k'] = get_post_meta($form_id, '_aipkit_ai_form_vector_store_top_k', true) ?: 3;
    $data['vector_store_confidence_threshold'] = get_post_meta($form_id, '_aipkit_ai_form_vector_store_confidence_threshold', true) ?: 20;
    // --- END ---

    // --- NEW: Get Web Search & Grounding Settings ---
    $data['openai_web_search_enabled'] = get_post_meta($form_id, '_aipkit_ai_form_openai_web_search_enabled', true) ?: '0';
    $data['google_search_grounding_enabled'] = get_post_meta($form_id, '_aipkit_ai_form_google_search_grounding_enabled', true) ?: '0';
    
    // OpenAI Web Search sub-settings
    $data['openai_web_search_context_size'] = get_post_meta($form_id, '_aipkit_ai_form_openai_web_search_context_size', true) ?: 'medium';
    $data['openai_web_search_loc_type'] = get_post_meta($form_id, '_aipkit_ai_form_openai_web_search_loc_type', true) ?: 'none';
    $data['openai_web_search_loc_country'] = get_post_meta($form_id, '_aipkit_ai_form_openai_web_search_loc_country', true) ?: '';
    $data['openai_web_search_loc_city'] = get_post_meta($form_id, '_aipkit_ai_form_openai_web_search_loc_city', true) ?: '';
    $data['openai_web_search_loc_region'] = get_post_meta($form_id, '_aipkit_ai_form_openai_web_search_loc_region', true) ?: '';
    $data['openai_web_search_loc_timezone'] = get_post_meta($form_id, '_aipkit_ai_form_openai_web_search_loc_timezone', true) ?: '';
    
    // Google Search Grounding sub-settings
    $data['google_grounding_mode'] = get_post_meta($form_id, '_aipkit_ai_form_google_grounding_mode', true) ?: 'DEFAULT_MODE';
    $data['google_grounding_dynamic_threshold'] = get_post_meta($form_id, '_aipkit_ai_form_google_grounding_dynamic_threshold', true) ?: 0.30;

    // --- Add Labels ---
    $labels_json = get_post_meta($form_id, '_aipkit_ai_form_labels', true);
    $saved_labels = json_decode($labels_json, true);
    if (!is_array($saved_labels)) {
        $saved_labels = [];
    }
    $default_labels = [
        'generate_button' => __('Generate', 'gpt3-ai-content-generator'),
        'stop_button'     => __('Stop', 'gpt3-ai-content-generator'),
        'download_button' => __('Download', 'gpt3-ai-content-generator'),
        'save_button'     => __('Save', 'gpt3-ai-content-generator'),
        'copy_button'     => __('Copy', 'gpt3-ai-content-generator'),
        'provider_label'  => __('AI Provider', 'gpt3-ai-content-generator'),
        'model_label'     => __('AI Model', 'gpt3-ai-content-generator'),
    ];

    // Merge defaults: Use saved value if not empty, otherwise use default. This handles old forms with empty strings saved.
    $final_labels = [];
    foreach ($default_labels as $key => $default_value) {
        $saved_value = isset($saved_labels[$key]) ? trim($saved_labels[$key]) : '';
        $final_labels[$key] = !empty($saved_value) ? $saved_value : $default_value;
    }

    $data['labels'] = $final_labels;

    return $data;
}