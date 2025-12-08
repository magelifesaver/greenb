<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/seo/aioseo/update-meta-description.php
// Status: NEW FILE

namespace WPAICG\SEO\AIOSEO;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Logic to update the AIOSEO meta description for a post.
 *
 * @param int $post_id The ID of the post.
 * @param string $description The new meta description.
 * @return bool True on success, false on failure.
 */
function update_meta_description_logic(int $post_id, string $description): bool
{
    if (empty($post_id) || !is_string($description)) {
        return false;
    }
    $result = update_post_meta($post_id, '_aioseo_description', sanitize_text_field($description));
    return $result !== false;
}
