<?php

namespace WPAICG\Vector\PostProcessor;

use WPAICG\AIPKit_Role_Manager;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Handles the post list screen features for the vector post processor:
 * - Index Status column
 * - Filter dropdown
 * - Status caching
 */
class AIPKit_Vector_Post_Processor_List_Screen
{
    public const MODULE_SLUG = 'vector_content_indexer';
    private static $posts_status_cache = [];
    private $supported_post_types = [];

    public function __construct()
    {
        // Supported post types are initialized on `init` after all CPTs are registered.
    }

    /**
     * Initialize supported post types - call this late enough so all post types are registered
     */
    public function init_supported_post_types() {
        // Keep it simple: show for posts, pages, and WooCommerce products (if available)
        $supported = ['post', 'page'];
        if (post_type_exists('product')) {
            $supported[] = 'product';
        }

        // Allow external customization if needed
        $supported = apply_filters('aipkit_vector_post_processor_supported_post_types', $supported);
        $this->supported_post_types = array_values(array_unique($supported));
    }

    /**
     * Get the list of supported post types
     */
    public function get_supported_post_types() {
        return $this->supported_post_types;
    }

    public function register_hooks()
    {
        // Initialize supported post types after CPTs are registered
        add_action('init', [$this, 'init_supported_post_types'], 20);

        // Hide our column by default; users can enable via Screen Options
        add_filter('default_hidden_columns', [$this, 'filter_default_hidden_columns'], 10, 2);

        add_action('admin_init', function() {
            if (!AIPKit_Role_Manager::user_can_access_module(self::MODULE_SLUG)) {
                return;
            }

            $general_settings = get_option('aipkit_training_general_settings', []);
            $show_features_on_list_screen = $general_settings['show_index_button'] ?? true;

            if ($show_features_on_list_screen) {
                // Register these hooks with higher priority to ensure post types are ready
                add_action('current_screen', [$this, 'register_list_screen_features'], 10);
            }
        });
    }

    /** Register features for post list screens - called when screen is determined */
    public function register_list_screen_features() {
        $screen = get_current_screen();
        if (!$screen || $screen->base !== 'edit') {
            return;
        }
        // Only for supported post types
        if (!in_array($screen->post_type, $this->supported_post_types, true)) {
            return;
        }
        
        $this->register_indexing_status_columns();
        add_filter('the_posts', [$this, 'cache_posts_indexing_status'], 10, 2);

        // Add hooks for the filter dropdown and query modification
        add_action('restrict_manage_posts', [$this, 'add_filter_dropdown']);
        add_action('pre_get_posts', [$this, 'filter_posts_by_ai_status']);
    }

    /** Renders the filter dropdown on post list screens */
    public function add_filter_dropdown($post_type) {
        if (!in_array($post_type, $this->supported_post_types, true)) {
            return;
        }

        $current_filter = isset($_GET['ai_indexed_status']) ? sanitize_text_field($_GET['ai_indexed_status']) : 'all';
        ?>
        <select name="ai_indexed_status" id="ai_indexed_status_filter">
            <option value="all"><?php esc_html_e('Index Status', 'gpt3-ai-content-generator'); ?></option>
            <option value="indexed" <?php selected($current_filter, 'indexed'); ?>><?php esc_html_e('Indexed', 'gpt3-ai-content-generator'); ?></option>
            <option value="not_indexed" <?php selected($current_filter, 'not_indexed'); ?>><?php esc_html_e('Not Indexed', 'gpt3-ai-content-generator'); ?></option>
        </select>
        <?php
    }

    /** Modifies the main query based on the selected filter */
    public function filter_posts_by_ai_status($query) {
        // Only modify the main query on admin list screens
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }

        // Determine the current post type without relying on get_current_screen
        $post_type = $query->get('post_type');
        if (empty($post_type)) {
            $post_type = isset($_GET['post_type']) ? sanitize_key($_GET['post_type']) : 'post';
        }
        // If an array or unsupported type, bail
        if (is_array($post_type) || !in_array($post_type, $this->supported_post_types, true)) {
            return;
        }

        $filter_status = isset($_GET['ai_indexed_status']) ? sanitize_text_field($_GET['ai_indexed_status']) : 'all';
        if ($filter_status !== 'indexed' && $filter_status !== 'not_indexed') {
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'aipkit_vector_data_source';
        
        // Get all post IDs that have been indexed successfully
        $indexed_post_ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT DISTINCT post_id FROM {$table_name} WHERE post_id IS NOT NULL AND status = %s",
                'indexed'
            )
        );

        if ($filter_status === 'indexed') {
            if (empty($indexed_post_ids)) {
                // If no posts are indexed, we force the query to return nothing.
                $query->set('post__in', [0]);
            } else {
                $query->set('post__in', $indexed_post_ids);
            }
        } elseif ($filter_status === 'not_indexed') {
            if (!empty($indexed_post_ids)) {
                $query->set('post__not_in', $indexed_post_ids);
            }
            // If $indexed_post_ids is empty, we don't need to do anything, as all posts are "not indexed".
        }
    }

    public function register_indexing_status_columns() {
        foreach ($this->supported_post_types as $post_type) {
            add_filter("manage_{$post_type}_posts_columns", [$this, 'add_ai_status_column']);
            add_action("manage_{$post_type}_posts_custom_column", [$this, 'render_ai_status_column'], 10, 2);
        }
    }

    public function add_ai_status_column($columns) {
        $columns['ai_indexed_status'] = esc_html__('Index Status', 'gpt3-ai-content-generator');
        return $columns;
    }

    /** Hide the Index Status column by default; users can enable it in Screen Options */
    public function filter_default_hidden_columns($hidden, $screen) {
        if (!is_array($hidden)) {
            $hidden = [];
        }
        if ($screen && $screen->base === 'edit' && isset($screen->post_type) && in_array($screen->post_type, $this->supported_post_types, true)) {
            if (!in_array('ai_indexed_status', $hidden, true)) {
                $hidden[] = 'ai_indexed_status';
            }
        }
        return $hidden;
    }

    public function render_ai_status_column($column_name, $post_id) {
        if ($column_name !== 'ai_indexed_status') return;

        if (isset(self::$posts_status_cache[$post_id])) {
            echo '<div class="aipkit-indexed-status-list">';
            foreach (self::$posts_status_cache[$post_id] as $status) {
                $provider_class = 'aipkit_provider_tag_' . strtolower(esc_attr($status['provider']));
                $display_name = $this->get_vector_store_display_name($status);
                echo '<span class="aipkit-status-tag aipkit-status-indexed ' . $provider_class . '" title="' . esc_attr($status['provider']) . ': ' . $display_name . '">' . $display_name . '</span>';
            }
            echo '</div>';
        } else {
            echo '<span class="aipkit-status-tag aipkit-status-not-indexed">—</span>';
        }
    }

    /**
     * Get the proper display name for a vector store
     * For OpenAI, looks up the name from the registry if not stored in the database
     */
    private function get_vector_store_display_name($status) {
        $provider = $status['provider'] ?? '';
        $vector_store_id = $status['vector_store_id'] ?? '';
        $vector_store_name = $status['vector_store_name'] ?? '';

        // If we already have a name and it's not just the ID, use it
        if (!empty($vector_store_name) && $vector_store_name !== $vector_store_id) {
            return esc_html($vector_store_name);
        }

        // For OpenAI, try to get the name from the registry
        if ($provider === 'OpenAI' && !empty($vector_store_id)) {
            $registry = get_option('aipkit_vector_stores_registry', []);
            
            if (isset($registry['OpenAI']) && is_array($registry['OpenAI'])) {
                foreach ($registry['OpenAI'] as $store) {
                    if (isset($store['id']) && $store['id'] === $vector_store_id) {
                        if (!empty($store['name'])) {
                            return esc_html($store['name']);
                        }
                        break;
                    }
                }
            }
        }

        // Fallback to the original logic
        return esc_html($vector_store_name ?: $vector_store_id);
    }

    public function cache_posts_indexing_status($posts, $query) {
        if (!is_admin() || !$query->is_main_query() || empty($posts) || !is_array($posts)) return $posts;
        $post_ids = wp_list_pluck($posts, 'ID');
        if (empty($post_ids)) return $posts;
        if (isset(self::$posts_status_cache[$post_ids[0]])) return $posts;

        global $wpdb;
        $table_name = $wpdb->prefix . 'aipkit_vector_data_source';
        $ids_placeholder = implode(',', array_fill(0, count($post_ids), '%d'));
        
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery
        $results = $wpdb->get_results($wpdb->prepare("SELECT post_id, vector_store_id, vector_store_name, provider FROM {$table_name} WHERE post_id IN ({$ids_placeholder}) AND status = 'indexed'", $post_ids), ARRAY_A);

        $grouped_results = [];
        if ($results) {
            foreach ($results as $row) {
                $grouped_results[$row['post_id']][] = $row;
            }
        }
        self::$posts_status_cache = $grouped_results;
        return $posts;
    }
}
