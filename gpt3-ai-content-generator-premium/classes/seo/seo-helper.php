<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/seo/seo-helper.php
// Status: MODIFIED
// I have added a new public static function `update_post_slug_for_seo` to generate an SEO-friendly slug from the focus keyword or title and update the post.

namespace WPAICG\SEO;

use WPAICG\SEO\Yoast\AIPKit_Yoast_Handler;
use WPAICG\SEO\RankMath\AIPKit_Rank_Math_Handler;
use WPAICG\SEO\AIOSEO\AIPKit_AIOSEO_Handler;
use WPAICG\SEO\Framework\AIPKit_Framework_Handler;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * AIPKit_SEO_Helper
 *
 * Main orchestrator for interacting with various SEO plugins.
 * Detects the active plugin and delegates actions to the appropriate handler.
 */
class AIPKit_SEO_Helper
{
    private static $active_plugin = null;
    private static $handler_instance = null;

    /**
     * Detects the active SEO plugin. Caches the result for the request.
     * @return string The identifier for the active plugin ('yoast', 'rank_math', 'aioseo', 'framework', 'none').
     */
    private static function get_active_plugin(): string
    {
        if (self::$active_plugin !== null) {
            return self::$active_plugin;
        }

        if (defined('WPSEO_VERSION')) {
            self::$active_plugin = 'yoast';
        } elseif (defined('RANK_MATH_VERSION')) {
            self::$active_plugin = 'rank_math';
        } elseif (defined('AIOSEO_VERSION')) {
            self::$active_plugin = 'aioseo';
        } elseif (defined('THE_SEO_FRAMEWORK_VERSION')) {
            self::$active_plugin = 'framework';
        } else {
            self::$active_plugin = 'none';
        }
        return self::$active_plugin;
    }

    /**
     * Loads and instantiates the correct handler class for the active SEO plugin.
     * @return AIPKit_SEO_Handler_Interface|null The handler instance or null if none active/found.
     */
    private static function get_handler(): ?AIPKit_SEO_Handler_Interface
    {
        if (self::$handler_instance !== null) {
            return self::$handler_instance;
        }

        $plugin = self::get_active_plugin();
        if ($plugin === 'none') {
            return null;
        }

        $handler_class = null;
        $handler_path = null;

        switch ($plugin) {
            case 'yoast':
                $handler_class = AIPKit_Yoast_Handler::class;
                $handler_path = __DIR__ . '/yoast/class-aipkit-yoast-handler.php';
                break;
            case 'rank_math':
                $handler_class = AIPKit_Rank_Math_Handler::class;
                $handler_path = __DIR__ . '/rank-math/class-aipkit-rank-math-handler.php';
                break;
            case 'aioseo':
                $handler_class = AIPKit_AIOSEO_Handler::class;
                $handler_path = __DIR__ . '/aioseo/class-aipkit-aioseo-handler.php';
                break;
            case 'framework':
                $handler_class = AIPKit_Framework_Handler::class;
                $handler_path = __DIR__ . '/framework/class-aipkit-framework-handler.php';
                break;
        }

        if ($handler_path && file_exists($handler_path)) {
            if (!class_exists($handler_class)) {
                $interface_path = __DIR__ . '/interface-aipkit-seo-handler.php';
                if (file_exists($interface_path) && !interface_exists(AIPKit_SEO_Handler_Interface::class)) {
                    require_once $interface_path;
                }
                require_once $handler_path;
            }
            if (class_exists($handler_class)) {
                self::$handler_instance = new $handler_class();
                return self::$handler_instance;
            }
        }

        return null;
    }

    /**
     * Updates the SEO meta description for a post.
     * Delegates to the active SEO plugin's handler.
     *
     * @param int $post_id The ID of the post.
     * @param string $description The new meta description.
     * @return bool True on success, false on failure.
     */
    public static function update_meta_description(int $post_id, string $description): bool
    {
        $handler = self::get_handler();
        if ($handler) {
            return $handler->update_meta_description($post_id, $description);
        }
        // Fallback for no SEO plugin: save to our own meta key.
        return update_post_meta($post_id, '_aipkit_meta_description', sanitize_text_field($description));
    }

    /**
     * Updates the focus keyword for a post.
     *
     * @param int $post_id The ID of the post.
     * @param string $keyword The new focus keyword.
     * @return bool True on success, false on failure.
     */
    public static function update_focus_keyword(int $post_id, string $keyword): bool
    {
        $handler = self::get_handler();
        if ($handler) {
            return $handler->update_focus_keyword($post_id, $keyword);
        }
        return false;
    }

    /**
     * Retrieves the focus keyword for a specific post.
     *
     * @param int $post_id The ID of the post.
     * @return string|null The focus keyword, or null if not found/supported.
     */
    public static function get_focus_keyword(int $post_id): ?string
    {
        $handler = self::get_handler();
        if ($handler) {
            return $handler->get_focus_keyword($post_id);
        }
        return null;
    }

    /**
     * Updates the tags for a post, automatically detecting the correct non-hierarchical taxonomy.
     *
     * @param int $post_id The ID of the post.
     * @param string $tags_string A comma-separated string of tags.
     * @return bool True on success, false on failure.
     */
    public static function update_tags(int $post_id, string $tags_string): bool
    {
        $post = get_post($post_id);
        if (!$post) {
            return false;
        }

        $tag_taxonomy = 'post_tag'; // Default

        // Check for WooCommerce product_tag first
        if ($post->post_type === 'product' && taxonomy_exists('product_tag') && is_object_in_taxonomy($post->post_type, 'product_tag')) {
            $tag_taxonomy = 'product_tag';
        }
        // If the default 'post_tag' is not associated, find the first non-hierarchical one
        elseif (!is_object_in_taxonomy($post->post_type, 'post_tag')) {
            $taxonomies = get_object_taxonomies($post->post_type, 'objects');
            $found_taxonomy = false;
            if (!empty($taxonomies)) {
                foreach ($taxonomies as $taxonomy) {
                    if (!$taxonomy->hierarchical) {
                        $tag_taxonomy = $taxonomy->name;
                        $found_taxonomy = true;
                        break;
                    }
                }
            }
        }

        // Parse the comma-separated tags string into an array
        $tags_array = array_map('trim', explode(',', $tags_string));
        $tags_array = array_filter($tags_array, function($tag) {
            return !empty($tag);
        });

        $result = wp_set_object_terms($post_id, $tags_array, $tag_taxonomy, false);

        if (is_wp_error($result)) {
            return false;
        }

        $success = $result !== false;

        return $success;
    }


    /**
     * Generates an SEO-friendly slug and updates the post.
     *
     * @param int $post_id The ID of the post to update.
     * @return bool True on success, false on failure.
     */
    public static function update_post_slug_for_seo(int $post_id): bool
    {
        if ($post_id <= 0) {
            return false;
        }

        $post = get_post($post_id);
        if (!$post) {
            return false;
        }

        // 1. Prioritize source for slug: Focus Keyword > Title
        $source_string = self::get_focus_keyword($post_id);
        if (empty(trim($source_string ?? ''))) {
            $source_string = $post->post_title;
        }
        if (empty(trim($source_string ?? ''))) {
            return false; // Nothing to generate slug from
        }

        // 2. Sanitize and prepare the string
        $slug = strtolower($source_string);

        // List of common stop words
        $stop_words = [
            'a', 'an', 'and', 'are', 'as', 'at', 'be', 'by', 'for', 'from', 'has', 'he', 'i',
            'in', 'is', 'it', 'its', 'of', 'on', 'that', 'the', 'to', 'was', 'were',
            'will', 'with', 'what', 'when', 'where', 'who', 'which', 'why', 'how', 'about',
            'above', 'after', 'below', 'into', 'out', 'over', 'under', 'again', 'further',
            'then', 'once', 'here', 'there', 'all', 'any', 'both', 'each', 'few', 'more',
            'most', 'other', 'some', 'such', 'no', 'nor', 'not', 'only', 'own', 'same',
            'so', 'than', 'too', 'very', 's', 't', 'can', 'just', 'don', 'should', 'now'
        ];
        $slug = preg_replace('/\b(' . implode('|', $stop_words) . ')\b/i', '', $slug);

        // Transliterate non-ASCII characters to their closest ASCII representation
        $slug = remove_accents($slug);

        // Replace non-alphanumeric with hyphens
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        // Remove multiple hyphens
        $slug = preg_replace('/-+/', '-', $slug);
        // Trim hyphens from start and end
        $slug = trim($slug, '-');

        if (empty($slug)) {
            // If everything was stripped, fall back to a sanitized title
            $slug = sanitize_title($post->post_title);
            if (empty($slug)) {
                return false; // Still nothing, can't proceed
            }
        }

        // 3. Ensure Optimal Length
        $slug_words = explode('-', $slug);
        if (count($slug_words) > 7) {
            $slug_words = array_slice($slug_words, 0, 7);
            $slug = implode('-', $slug_words);
        }
        // Final character trim
        $slug = substr($slug, 0, 75);
        $slug = trim($slug, '-'); // Trim again in case substr created a trailing hyphen

        if (empty($slug)) {
            return false;
        }

        // 4. Ensure Uniqueness
        $unique_slug = wp_unique_post_slug($slug, $post->ID, $post->post_status, $post->post_type, $post->post_parent);

        // 5. Check if a change is needed before updating
        if ($unique_slug === $post->post_name) {
            return true; // No change needed
        }

        // 6. Update the Post
        $update_result = wp_update_post([
            'ID'        => $post_id,
            'post_name' => $unique_slug
        ], true); // true to return WP_Error on failure

        if (is_wp_error($update_result)) {
            return false;
        }

        // Clear post cache after update
        clean_post_cache($post_id);

        return true;
    }

    /**
     * Gets all tags for a post as a comma-separated string.
     * @param int $post_id The ID of the post.
     * @return string A comma-separated string of tag names.
     */
    public static function get_tags_as_string(int $post_id): string
    {
        $post = get_post($post_id);
        if (!$post) {
            return '';
        }
    
        $tag_taxonomy = 'post_tag'; // Default for posts
    
        if ($post->post_type === 'product' && taxonomy_exists('product_tag') && is_object_in_taxonomy($post->post_type, 'product_tag')) {
            $tag_taxonomy = 'product_tag';
        } elseif (!is_object_in_taxonomy($post->post_type, 'post_tag')) {
            $taxonomies = get_object_taxonomies($post->post_type, 'objects');
            if (!empty($taxonomies)) {
                foreach ($taxonomies as $taxonomy) {
                    if (!$taxonomy->hierarchical) {
                        $tag_taxonomy = $taxonomy->name;
                        break;
                    }
                }
            }
        }
    
        $tags = get_the_terms($post_id, $tag_taxonomy);
        if (is_wp_error($tags) || empty($tags)) {
            return '';
        }
    
        return implode(', ', wp_list_pluck($tags, 'name'));
    }

    /**
     * Gets all categories for a post as a comma-separated string.
     * @param int $post_id The ID of the post.
     * @return string A comma-separated string of category names.
     */
    public static function get_categories_as_string(int $post_id): string
    {
        $post = get_post($post_id);
        if (!$post) {
            return '';
        }
    
        $cat_taxonomy = 'category'; // Default for posts
    
        if ($post->post_type === 'product' && taxonomy_exists('product_cat') && is_object_in_taxonomy($post->post_type, 'product_cat')) {
            $cat_taxonomy = 'product_cat';
        } elseif (!is_object_in_taxonomy($post->post_type, 'category')) {
            $taxonomies = get_object_taxonomies($post->post_type, 'objects');
            if (!empty($taxonomies)) {
                foreach ($taxonomies as $taxonomy) {
                    if ($taxonomy->hierarchical) {
                        $cat_taxonomy = $taxonomy->name;
                        break;
                    }
                }
            }
        }
    
        $categories = get_the_terms($post_id, $cat_taxonomy);
        if (is_wp_error($categories) || empty($categories)) {
            return '';
        }
    
        return implode(', ', wp_list_pluck($categories, 'name'));
    }
}