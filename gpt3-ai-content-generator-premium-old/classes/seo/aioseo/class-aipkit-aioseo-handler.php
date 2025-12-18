<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/seo/aioseo/class-aipkit-aioseo-handler.php
// Status: MODIFIED

namespace WPAICG\SEO\AIOSEO;

use WPAICG\SEO\AIPKit_SEO_Handler_Interface;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handler for All in One SEO (AIOSEO) plugin interactions.
 * Delegates logic to namespaced functions.
 */
class AIPKit_AIOSEO_Handler implements AIPKit_SEO_Handler_Interface
{
    public function update_meta_description(int $post_id, string $description): bool
    {
        $file_path = __DIR__ . '/update-meta-description.php';
        if (file_exists($file_path)) {
            require_once $file_path;
            return update_meta_description_logic($post_id, $description);
        }
        return false;
    }

    public function update_focus_keyword(int $post_id, string $keyword): bool
    {
        $file_path = __DIR__ . '/update-focus-keyword.php';
        if (file_exists($file_path)) {
            require_once $file_path;
            return update_focus_keyword_logic($post_id, $keyword);
        }
        return false;
    }

    public function get_focus_keyword(int $post_id): ?string
    {
        $file_path = __DIR__ . '/get-focus-keyword.php';
        if (file_exists($file_path)) {
            require_once $file_path;
            return get_focus_keyword_logic($post_id);
        }
        return null;
    }
}
