<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/content-writer/ajax/actions/save-post/prepare-categories.php
// Status: NEW FILE

namespace WPAICG\ContentWriter\Ajax\Actions\SavePost;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Adds category information to the post array for standard 'post' types.
 *
 * @param array &$postarr Reference to the post array for wp_insert_post.
 * @param array $data The sanitized post data containing 'category_ids'.
 * @return void
 */
function prepare_categories_logic(array &$postarr, array $data): void
{
    if (!empty($data['category_ids'])) {
        if ($data['post_type'] === 'post') {
            $postarr['post_category'] = $data['category_ids'];
        }
    }
}
