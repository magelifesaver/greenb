<?php

namespace WPAICG\AIForms\Storage\Methods;

use WPAICG\AIForms\Admin\AIPKit_AI_Form_Admin_Setup;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for creating a new AI Form CPT.
 *
 * @param \WPAICG\AIForms\Storage\AIPKit_AI_Form_Storage $storageInstance The instance of the storage class.
 * @param string $title The title of the form.
 * @param array $settings Optional settings to save.
 * @return int|WP_Error The new post ID on success, or WP_Error on failure.
 */
function create_form_logic(\WPAICG\AIForms\Storage\AIPKit_AI_Form_Storage $storageInstance, string $title, array $settings = []): int|WP_Error
{
    if (empty($title)) {
        return new WP_Error('title_required', __('Form title cannot be empty.', 'gpt3-ai-content-generator'));
    }
    if (!class_exists(AIPKit_AI_Form_Admin_Setup::class)) {
        return new WP_Error('dependency_missing', 'AI Form Admin Setup class not found for CPT creation.');
    }

    $post_data = array(
        'post_title'  => sanitize_text_field($title),
        'post_type'   => AIPKit_AI_Form_Admin_Setup::POST_TYPE,
        'post_status' => 'publish',
        'post_author' => get_current_user_id() ?: 1,
    );

    $form_id = wp_insert_post($post_data, true);

    if (is_wp_error($form_id)) {
        return $form_id;
    }

    $default_settings = [
        'prompt_template' => 'Your AI prompt for {user_input}',
        'form_structure' => '[]',
        'ai_provider' => 'OpenAI',
        'ai_model' => '',
        'system_instruction' => '',
    ];
    $final_settings = array_merge($default_settings, $settings);

    // Call the logic function via the passed storage instance
    $storageInstance->save_form_settings($form_id, $final_settings);

    return $form_id;
}
