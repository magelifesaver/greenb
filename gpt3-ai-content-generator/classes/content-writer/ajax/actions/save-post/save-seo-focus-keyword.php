<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/content-writer/ajax/actions/save-post/save-seo-focus-keyword.php
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
 * Saves the SEO focus keyword for a given post.
 *
 * @param int    $post_id The ID of the post.
 * @param string $focus_keyword The focus keyword to save.
 * @return void
 */
function save_seo_focus_keyword_logic(int $post_id, string $focus_keyword): void
{
    if ($post_id > 0 && !empty($focus_keyword) && class_exists('\WPAICG\SEO\AIPKit_SEO_Helper')) {
        \WPAICG\SEO\AIPKit_SEO_Helper::update_focus_keyword($post_id, $focus_keyword);
    }
}
