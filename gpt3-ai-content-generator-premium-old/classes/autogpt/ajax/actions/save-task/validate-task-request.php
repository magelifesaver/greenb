<?php

namespace WPAICG\AutoGPT\Ajax\Actions\SaveTask;

use WPAICG\AutoGPT\Ajax\AIPKit_Save_Automated_Task_Action;
use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

/**
* Validates the AJAX request for saving an automated task.
*
* @param AIPKit_Save_Automated_Task_Action $handler The handler instance.
* @param array $post_data The raw POST data.
* @return array|WP_Error An array of validated parameters or a WP_Error on failure.
*/
function validate_task_request_logic(AIPKit_Save_Automated_Task_Action $handler, array $post_data): array|WP_Error
{
    // Permission and nonce checks are now handled by the caller.
    // This function now only validates the presence and format of required parameters.

    $task_id = isset($post_data['task_id']) && !empty($post_data['task_id']) ? absint($post_data['task_id']) : 0;
    $task_name = isset($post_data['task_name']) ? sanitize_text_field($post_data['task_name']) : '';
    $task_type = isset($post_data['task_type']) ? sanitize_key($post_data['task_type']) : '';

    if (empty($task_name)) {
        return new WP_Error('missing_task_name', __('Task name is required.', 'gpt3-ai-content-generator'), ['status' => 400]);
    }
    if (empty($task_type)) {
        return new WP_Error('missing_task_type', __('Task type is required.', 'gpt3-ai-content-generator'), ['status' => 400]);
    }

    return [
        'task_id' => $task_id,
        'task_name' => $task_name,
        'task_type' => $task_type,
    ];
}