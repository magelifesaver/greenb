<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/autogpt/cron/event-processor/processor/content-writing/insert-post.php

namespace WPAICG\AutoGPT\Cron\EventProcessor\Processor\ContentWriting;

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
 * @param string      $final_title         The final title for the post.
 * @param string      $generated_content   The main content of the post.
 * @param array       $cw_config           The specific configuration for the content writing item.
 * @param string|null $meta_description    Optional SEO meta description to save.
 * @param string|null $focus_keyword       Optional SEO focus keyword to save.
 * @param array|null  $image_data          Optional data for generated images.
 * @param string|null $excerpt             Optional post excerpt.
 * @param string|null $schedule_gmt_time   Optional GMT time string to schedule the post.
 * @return int|WP_Error The new post ID or a WP_Error on failure.
 */
function insert_post_logic(string $final_title, string $generated_content, array $cw_config, ?string $meta_description = null, ?string $focus_keyword = null, ?array $image_data = null, ?string $excerpt = null, ?string $schedule_gmt_time = null): int|WP_Error
{
    $post_author = $cw_config['post_author'] ?? get_current_user_id() ?: 1;
    if (!user_can($post_author, 'edit_posts') || !user_can($post_author, get_post_type_object($cw_config['post_type'])->cap->create_posts)) {
        $post_author = 1; // Fallback to admin if user can't create posts
    }

    // --- START: Convert markdown to HTML ---
    $html_content = $generated_content;

    // First, convert markdown block elements like headings
    $html_content = preg_replace('/^#\s+(.*)$/m', '<h1>$1</h1>', $html_content);
    $html_content = preg_replace('/^##\s+(.*)$/m', '<h2>$1</h2>', $html_content);
    $html_content = preg_replace('/^###\s+(.*)$/m', '<h3>$1</h3>', $html_content);
    $html_content = preg_replace('/^####\s+(.*)$/m', '<h4>$1</h4>', $html_content);

    // Convert inline markdown elements like bold and italic.
    $html_content = preg_replace('/\*\*(.*?)\*\*/s', '<strong>$1</strong>', $html_content);
    $html_content = preg_replace('/(?<!\*)\*(?!\*|_)(.*?)(?<!\*|_)\*(?!\*)/s', '<em>$1</em>', $html_content);
    // Convert links: [text](url) -> <a href="url">text</a>
    $html_content = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2">$1</a>', $html_content);

    // --- START FIX: Apply wpautop to create paragraph tags for image injector ---
    // This is the key fix. It ensures <p> tags exist for the image injector to work with.
    $html_content = wpautop($html_content);
    // --- END FIX ---

    // Inject in-content images before ToC generation
    if (!empty($image_data['in_content_images']) && class_exists(AIPKit_Image_Injector::class)) {
        $image_injector = new AIPKit_Image_Injector();
        $image_alignment = $cw_config['image_alignment'] ?? 'none';
        $image_size = $cw_config['image_size'] ?? 'large';
        $html_content = $image_injector->inject_images(
            $html_content,
            $image_data['in_content_images'],
            $image_data['placement_settings']['placement'] ?? 'after_first_h2',
            absint($image_data['placement_settings']['param_x'] ?? 2),
            $image_alignment,
            $image_size
        );
    }

    // Generate ToC after images have been placed
    if (isset($cw_config['generate_toc']) && $cw_config['generate_toc'] === '1' && class_exists(AIPKit_TOC_Generator::class)) {
        $toc_result = AIPKit_TOC_Generator::generate($html_content);
        if (!empty($toc_result['toc'])) {
            $html_content = $toc_result['toc'] . $toc_result['content'];
        }
    }

    $postarr = [
        'post_title'   => $final_title,
        'post_content' => $html_content, // Save the content before wpautop for wp_insert_post
        'post_type'    => $cw_config['post_type'] ?? 'post',
        'post_author'  => $post_author,
        'post_status'  => $cw_config['post_status'] ?? 'draft',
    ];

    // --- MODIFIED: Handle new scheduling logic ---
    if (!empty($schedule_gmt_time)) {
        $schedule_timestamp_gmt = strtotime($schedule_gmt_time);
        $current_timestamp_gmt = current_time('timestamp', true);
        if ($schedule_timestamp_gmt > $current_timestamp_gmt) {
            $postarr['post_status'] = 'future';
            $postarr['post_date_gmt'] = $schedule_gmt_time;
            $postarr['post_date'] = get_date_from_gmt($schedule_gmt_time, 'Y-m-d H:i:s');
        }
    }
    // --- END MODIFICATION ---

    if (!empty($excerpt)) {
        $postarr['post_excerpt'] = $excerpt;
    }

    $category_ids = $cw_config['post_categories'] ?? [];
    if (!empty($category_ids) && $postarr['post_type'] === 'post') {
        $postarr['post_category'] = $category_ids;
    }

    $new_post_id = wp_insert_post($postarr, true);

    if (is_wp_error($new_post_id)) {
        return new WP_Error('post_insert_failed', 'Failed to save post: ' . $new_post_id->get_error_message());
    }

    // --- ADDED: Set Featured Image after post insertion ---
    if (!empty($image_data['featured_image_id'])) {
        set_post_thumbnail($new_post_id, $image_data['featured_image_id']);
    }
    // --- END ADDED ---

    if ($postarr['post_type'] !== 'post' && !empty($category_ids)) {
        $taxonomy = 'category';
        if (is_object_in_taxonomy($postarr['post_type'], $taxonomy)) {
            wp_set_post_terms($new_post_id, $category_ids, $taxonomy);
        }
    }

    if (!empty($meta_description) && class_exists('\\WPAICG\\SEO\\AIPKit_SEO_Helper')) {
        \WPAICG\SEO\AIPKit_SEO_Helper::update_meta_description($new_post_id, $meta_description);
    }

    if (!empty($focus_keyword) && class_exists('\\WPAICG\\SEO\\AIPKit_SEO_Helper')) {
        \WPAICG\SEO\AIPKit_SEO_Helper::update_focus_keyword($new_post_id, $focus_keyword);
    }
    
    // --- NEW: Update Slug based on checkbox ---
    if (isset($cw_config['generate_seo_slug']) && $cw_config['generate_seo_slug'] === '1' && class_exists('\\WPAICG\\SEO\\AIPKit_SEO_Helper')) {
        \WPAICG\SEO\AIPKit_SEO_Helper::update_post_slug_for_seo($new_post_id);
    }
    // --- END NEW ---

    return $new_post_id;
}