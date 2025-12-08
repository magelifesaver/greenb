<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/ajax/ai-forms/ajax-delete-all-forms.php
// Status: NEW FILE

namespace WPAICG\Admin\Ajax\AIForms;

use WP_Error;
use WPAICG\AIForms\Admin\AIPKit_AI_Form_Ajax_Handler;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles the logic for deleting all AI forms.
 * Called by AIPKit_AI_Form_Ajax_Handler::ajax_delete_all_ai_forms().
 *
 * @param AIPKit_AI_Form_Ajax_Handler $handler_instance
 * @return void
 */
function do_ajax_delete_all_forms_logic(AIPKit_AI_Form_Ajax_Handler $handler_instance): void
{
    $form_storage = $handler_instance->get_form_storage();

    if (!$form_storage) {
        $handler_instance->send_wp_error(new WP_Error('storage_missing', __('Form storage component is not available.', 'gpt3-ai-content-generator')), 500);
        return;
    }

    $deleted = $form_storage->delete_all_forms();
    if (is_wp_error($deleted)) {
        $handler_instance->send_wp_error($deleted);
    } elseif ($deleted === false) {
        $handler_instance->send_wp_error(new WP_Error('delete_all_failed', __('Failed to delete all forms.', 'gpt3-ai-content-generator')), 500);
    } else {
        /* translators: %d is the number of forms deleted */
        wp_send_json_success(['message' => sprintf(__('%d forms deleted successfully.', 'gpt3-ai-content-generator'), $deleted)]);
    }
}
