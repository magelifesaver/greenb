<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/ai-forms/storage/methods/fn-delete-all-forms.php
// Status: NEW FILE

namespace WPAICG\AIForms\Storage\Methods;

use WPAICG\AIForms\Admin\AIPKit_AI_Form_Admin_Setup;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for deleting all AI Form CPTs.
 *
 * @param \WPAICG\AIForms\Storage\AIPKit_AI_Form_Storage $storageInstance The instance of the storage class.
 * @return int|WP_Error The number of posts deleted, or WP_Error on failure.
 */
function delete_all_forms_logic(\WPAICG\AIForms\Storage\AIPKit_AI_Form_Storage $storageInstance): int|WP_Error
{
    if (!class_exists(AIPKit_AI_Form_Admin_Setup::class)) {
        return new WP_Error('dependency_missing', 'AI Form Admin Setup class not found for CPT deletion.');
    }

    $args = array(
        'post_type'      => AIPKit_AI_Form_Admin_Setup::POST_TYPE,
        'posts_per_page' => -1,
        'post_status'    => ['publish', 'draft', 'trash'], // Also get from trash
        'fields'         => 'ids',
    );
    $all_forms = get_posts($args);

    if (empty($all_forms)) {
        return 0; // No forms to delete
    }

    $deleted_count = 0;
    foreach ($all_forms as $form_id) {
        $deleted = wp_delete_post($form_id, true); // true to force delete, bypassing trash
        if ($deleted) {
            $deleted_count++;
        }
    }

    return $deleted_count;
}
