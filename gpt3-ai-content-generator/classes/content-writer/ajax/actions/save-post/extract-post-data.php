<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/content-writer/ajax/actions/save-post/extract-post-data.php
// Status: MODIFIED
// I have added logic to extract the `generated_tags` from the POST data.

namespace WPAICG\ContentWriter\Ajax\Actions\SavePost;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Extracts and sanitizes post data from the $_POST superglobal.
 *
 * @return array The sanitized post data.
 */
function extract_post_data_logic(): array
{
    // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked by the calling handler in validate_permissions_logic().
    $raw_data = isset($_POST) ? wp_unslash($_POST) : [];

    $sanitized = [];
    $sanitized['post_title']   = isset($raw_data['post_title']) ? sanitize_text_field($raw_data['post_title']) : 'AI Generated Content';
    $sanitized['post_content'] = isset($raw_data['post_content']) ? wp_kses_post($raw_data['post_content']) : '';
    $sanitized['excerpt'] = isset($raw_data['generated_excerpt']) ? wp_kses_post($raw_data['generated_excerpt']) : ''; // ADDED
    $sanitized['tags'] = isset($raw_data['generated_tags']) ? sanitize_text_field($raw_data['generated_tags']) : '';
    $sanitized['meta_description'] = isset($raw_data['meta_description']) ? sanitize_textarea_field($raw_data['meta_description']) : '';
    $sanitized['focus_keyword'] = isset($raw_data['focus_keyword']) ? sanitize_text_field($raw_data['focus_keyword']) : '';
    $sanitized['post_type']    = isset($raw_data['post_type']) ? sanitize_key($raw_data['post_type']) : 'post';
    $sanitized['post_author']  = isset($raw_data['post_author']) ? absint($raw_data['post_author']) : get_current_user_id();
    $sanitized['post_status']  = isset($raw_data['post_status']) ? sanitize_key($raw_data['post_status']) : 'draft';
    $sanitized['schedule_date'] = isset($raw_data['post_schedule_date']) ? sanitize_text_field($raw_data['post_schedule_date']) : '';
    $sanitized['schedule_time'] = isset($raw_data['post_schedule_time']) ? sanitize_text_field($raw_data['post_schedule_time']) : '';
    $sanitized['generate_toc'] = isset($raw_data['generate_toc']) && $raw_data['generate_toc'] === '1' ? '1' : '0';
    $sanitized['generate_seo_slug'] = isset($raw_data['generate_seo_slug']) && $raw_data['generate_seo_slug'] === '1' ? '1' : '0'; // NEW

    $category_ids_from_post = isset($raw_data['post_categories']) && is_array($raw_data['post_categories'])
        ? array_map('absint', $raw_data['post_categories'])
        : [];
    $sanitized['category_ids'] = array_filter($category_ids_from_post, function ($id) {
        return $id > 0;
    });

    // --- ADDED: Extract image data and alignment ---
    $sanitized['image_data'] = isset($raw_data['image_data']) && !empty($raw_data['image_data']) ? json_decode(wp_unslash($raw_data['image_data']), true) : null;
    $sanitized['image_alignment'] = isset($raw_data['image_alignment']) ? sanitize_key($raw_data['image_alignment']) : 'none';
    $sanitized['image_size'] = isset($raw_data['image_size']) ? sanitize_key($raw_data['image_size']) : 'large';
    // --- END ADDED ---

    return $sanitized;
}
