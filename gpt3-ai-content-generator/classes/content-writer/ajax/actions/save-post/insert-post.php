<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/content-writer/ajax/actions/save-post/insert-post.php
// Status: MODIFIED
// I have added a preg_replace call to convert markdown-style links into HTML <a> tags before the content is saved.

namespace WPAICG\ContentWriter\Ajax\Actions\SavePost;

use WPAICG\ContentWriter\TemplateManagerMethods as CwTemplateMethods;
use WP_Error;
use WPAICG\Utils\AIPKit_TOC_Generator;
// --- ADDED: Image Injector Dependency ---
use WPAICG\ContentWriter\AIPKit_Image_Injector;

// --- END ADDED ---

if (!defined('ABSPATH')) {
    exit;
}

// Ensure dependencies are loaded
if (!function_exists('WPAICG\ContentWriter\TemplateManagerMethods\calculate_schedule_datetime_logic')) {
    $path = WPAICG_PLUGIN_DIR . 'classes/content-writer/template-manager/calculate-schedule-datetime.php';
    if (file_exists($path)) {
        require_once $path;
    }
}
if (!class_exists('\WPAICG\Utils\AIPKit_TOC_Generator')) {
    $toc_generator_path = WPAICG_PLUGIN_DIR . 'includes/utils/class-aipkit-toc-generator.php';
    if (file_exists($toc_generator_path)) {
        require_once $toc_generator_path;
    }
}
if (!class_exists('\WPAICG\SEO\AIPKit_SEO_Helper')) {
    $seo_helper_path = WPAICG_PLUGIN_DIR . 'classes/seo/seo-helper.php';
    if (file_exists($seo_helper_path)) {
        require_once $seo_helper_path;
    }
}
// --- ADDED: Ensure Image Injector is loaded ---
if (!class_exists(AIPKit_Image_Injector::class)) {
    $injector_path = WPAICG_PLUGIN_DIR . 'classes/content-writer/class-aipkit-image-injector.php';
    if (file_exists($injector_path)) {
        require_once $injector_path;
    }
}
// --- END ADDED ---

/**
 * Inserts the generated content as a new post.
 *
 * @param array      $postarr           The final prepared post array.
 * @param string|null $excerpt           Optional post excerpt.
 * @param array|null $image_data        Optional data for generated images.
 * @param string     $image_alignment   Optional alignment for injected images.
 * @param string     $image_size        Optional display size for injected images.
 * @return int|WP_Error The new post ID or a WP_Error on failure.
 */
function insert_post_logic(array $postarr, ?string $excerpt = null, ?array $image_data = null, string $image_alignment = 'none', string $image_size = 'large'): int|WP_Error
{
    // --- START: Convert markdown to HTML ---
    $html_content = $postarr['post_content'];

    // Convert markdown block elements like headings
    $html_content = preg_replace('/^#\s+(.*)$/m', '<h1>$1</h1>', $html_content);
    $html_content = preg_replace('/^##\s+(.*)$/m', '<h2>$1</h2>', $html_content);
    $html_content = preg_replace('/^###\s+(.*)$/m', '<h3>$1</h3>', $html_content);
    $html_content = preg_replace('/^####\s+(.*)$/m', '<h4>$1</h4>', $html_content);

    // Convert inline markdown elements like bold and italic.
    $html_content = preg_replace('/\*\*(.*?)\*\*/s', '<strong>$1</strong>', $html_content);
    $html_content = preg_replace('/(?<!\*)\*(?!\*|_)(.*?)(?<!\*|_)\*(?!\*)/s', '<em>$1</em>', $html_content);
    // Convert links: [text](url) -> <a href="url">text</a>
    $html_content = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2">$1</a>', $html_content);

    // Update the post content in the array with the converted HTML
    $postarr['post_content'] = $html_content;
    // --- END: Convert markdown to HTML ---


    // --- ADDED: Image Injector logic before ToC generation ---
    if (!empty($image_data['in_content_images']) && class_exists(AIPKit_Image_Injector::class)) {
        $image_injector = new AIPKit_Image_Injector();
        $postarr['post_content'] = $image_injector->inject_images(
            $postarr['post_content'],
            $image_data['in_content_images'],
            $image_data['placement_settings']['placement'] ?? 'after_first_h2',
            absint($image_data['placement_settings']['param_x'] ?? 2),
            $image_alignment,
            $image_size
        );
    }
    // --- END ADDED ---

    // Generate ToC after images have been placed
    if (isset($postarr['generate_toc']) && $postarr['generate_toc'] === '1' && class_exists(AIPKit_TOC_Generator::class)) {
        $toc_result = AIPKit_TOC_Generator::generate($postarr['post_content']);
        if (!empty($toc_result['toc'])) {
            // Prepend ToC to the modified content
            $postarr['post_content'] = $toc_result['toc'] . $toc_result['content'];
        }
    }
    // Unset the custom key before passing to wp_insert_post
    unset($postarr['generate_toc']);

    // Add excerpt if provided
    if (!empty($excerpt)) {
        $postarr['post_excerpt'] = $excerpt;
    }

    $post_id_or_error = wp_insert_post($postarr, true);

    if (is_wp_error($post_id_or_error)) {
        return $post_id_or_error;
    }

    // --- ADDED: Set Featured Image after post insertion ---
    if (!empty($image_data['featured_image_id'])) {
        set_post_thumbnail($post_id_or_error, $image_data['featured_image_id']);
    }
    // --- END ADDED ---

    return $post_id_or_error;
}
