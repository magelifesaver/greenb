<?php
/**
 * Partial loader for Content Writer RSS Mode tab.
 *
 * @since NEXT_VERSION
 */

if (!defined('ABSPATH')) {
    exit;
}

// $is_pro is available from the parent scope (loader-vars.php)
$shared_rss_partial = WPAICG_LIB_DIR . 'views/shared/content-writing/input-mode-rss.php';

if ($is_pro && file_exists($shared_rss_partial)) {
    include $shared_rss_partial;
} else {
    // Show a placeholder or upgrade message if not Pro
    echo '<p>' . esc_html__('This feature is a Pro feature. Please upgrade to use content generation from RSS feeds.', 'gpt3-ai-content-generator') . '</p>';
}