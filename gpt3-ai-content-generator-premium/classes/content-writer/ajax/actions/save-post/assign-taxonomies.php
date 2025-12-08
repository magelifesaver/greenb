<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/content-writer/ajax/actions/save-post/assign-taxonomies.php
// Status: NEW FILE

namespace WPAICG\ContentWriter\Ajax\Actions\SavePost;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Assigns taxonomies (like categories) to non-standard post types after creation.
 *
 * @param int $post_id The ID of the newly created post.
 * @param array $data The sanitized post data containing 'post_type' and 'category_ids'.
 * @return void
 */
function assign_taxonomies_logic(int $post_id, array $data): void
{
    if ($data['post_type'] !== 'post' && !empty($data['category_ids'])) {
        $taxonomy = 'category'; // Hardcoded for now, could be made dynamic if needed
        if (is_object_in_taxonomy($data['post_type'], $taxonomy)) {
            wp_set_post_terms($post_id, $data['category_ids'], $taxonomy);
        }
    }
}
