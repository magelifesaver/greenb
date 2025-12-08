<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/ajax/ai-forms/ajax-export-all-forms.php
// Status: NEW FILE

namespace WPAICG\Admin\Ajax\AIForms;

use WP_Error;
use WPAICG\AIForms\Admin\AIPKit_AI_Form_Ajax_Handler;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles the logic for exporting all AI forms.
 * Called by AIPKit_AI_Form_Ajax_Handler::ajax_export_all_ai_forms().
 *
 * @param AIPKit_AI_Form_Ajax_Handler $handler_instance
 * @return void
 */
function do_ajax_export_all_forms_logic(AIPKit_AI_Form_Ajax_Handler $handler_instance): void
{
    $form_storage = $handler_instance->get_form_storage();

    if (!$form_storage) {
        $handler_instance->send_wp_error(new WP_Error('storage_missing', __('Form storage component is not available.', 'gpt3-ai-content-generator')), 500);
        return;
    }

    // Get all forms without pagination
    $all_forms_list = $form_storage->get_forms_list(['posts_per_page' => -1]);
    $form_ids = wp_list_pluck($all_forms_list['forms'], 'id');

    if (empty($form_ids)) {
        wp_send_json_error(['message' => __('No forms found to export.', 'gpt3-ai-content-generator')], 404);
        return;
    }

    $exported_forms = [];
    foreach ($form_ids as $form_id) {
        $form_data = $form_storage->get_form_data($form_id);
        if (!is_wp_error($form_data)) {
            // Remove keys that are not needed for export/import
            unset($form_data['id']);
            unset($form_data['status']);
            $exported_forms[] = $form_data;
        }
    }

    wp_send_json_success(['forms' => $exported_forms]);
}
