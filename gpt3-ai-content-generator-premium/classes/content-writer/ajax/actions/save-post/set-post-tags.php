<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/content-writer/ajax/actions/save-post/set-post-tags.php
// Status: NEW FILE

namespace WPAICG\ContentWriter\Ajax\Actions\SavePost;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Sets the tags for a given post.
 *
 * @param int    $post_id The ID of the post.
 * @param string $tags A comma-separated string of tags.
 * @return void
 */
function set_post_tags_logic(int $post_id, string $tags): void
{
    if ($post_id > 0 && !empty($tags)) {
        // wp_set_post_tags handles creating tags that don't exist
        // and sanitizing them. It accepts a string or an array.
        wp_set_post_tags($post_id, $tags, false);
    }
}