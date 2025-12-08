<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/content-writer/ajax/actions/save-post/validate-post-data.php
// Status: NEW FILE

namespace WPAICG\ContentWriter\Ajax\Actions\SavePost;

use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Validates the sanitized post data.
 *
 * @param array $data The sanitized post data.
 * @return true|WP_Error True if data is valid, WP_Error otherwise.
 */
function validate_post_data_logic(array $data): bool|WP_Error
{
    if (empty($data['post_title'])) {
        return new WP_Error('missing_title', __('Post title cannot be empty.', 'gpt3-ai-content-generator'), ['status' => 400]);
    }
    if (empty($data['post_content'])) {
        return new WP_Error('missing_content', __('Post content cannot be empty.', 'gpt3-ai-content-generator'), ['status' => 400]);
    }
    if (!post_type_exists($data['post_type'])) {
        return new WP_Error('invalid_post_type', __('Invalid post type specified.', 'gpt3-ai-content-generator'), ['status' => 400]);
    }
    if (!user_can($data['post_author'], 'edit_posts') || !user_can($data['post_author'], get_post_type_object($data['post_type'])->cap->create_posts)) {
        return new WP_Error('invalid_author', __('Selected author does not have permission to create this post type.', 'gpt3-ai-content-generator'), ['status' => 403]);
    }
    $valid_statuses = ['draft', 'publish', 'pending', 'private'];
    if (!in_array($data['post_status'], $valid_statuses, true)) {
        // While the data extractor defaults this, a validation step is still good practice.
        return new WP_Error('invalid_status', __('Invalid post status specified.', 'gpt3-ai-content-generator'), ['status' => 400]);
    }

    return true;
}
