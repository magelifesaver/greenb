<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/content-writer/ajax/actions/save-post/save-seo-meta.php
// Status: MODIFIED

namespace WPAICG\ContentWriter\Ajax\Actions\SavePost;

if (!defined('ABSPATH')) {
    exit;
}

// Ensure the helper function is available. It is loaded by the main Dependency Loader.
if (!class_exists('\WPAICG\SEO\AIPKit_SEO_Helper')) {
    $seo_helper_path = WPAICG_PLUGIN_DIR . 'classes/seo/seo-helper.php';
    if (file_exists($seo_helper_path)) {
        require_once $seo_helper_path;
    }
}

/**
 * Saves the SEO meta description for a given post.
 *
 * @param int    $post_id The ID of the post.
 * @param string $meta_description The meta description to save.
 * @return void
 */
function save_seo_meta_logic(int $post_id, string $meta_description): void
{
    if ($post_id > 0 && !empty($meta_description) && class_exists('\WPAICG\SEO\AIPKit_SEO_Helper')) {
        \WPAICG\SEO\AIPKit_SEO_Helper::update_meta_description($post_id, $meta_description);
    }
}
