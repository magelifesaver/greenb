<?php

namespace WPAICG\AIForms\Storage\Methods;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for deleting an AI Form CPT.
 *
 * @param \WPAICG\AIForms\Storage\AIPKit_AI_Form_Storage $storageInstance The instance of the storage class.
 * @param int $form_id The ID of the form to delete.
 * @return bool True on success, false on failure.
 */
function delete_form_logic(\WPAICG\AIForms\Storage\AIPKit_AI_Form_Storage $storageInstance, int $form_id): bool
{
    $deleted = wp_delete_post($form_id, true);
    return (bool) $deleted;
}
