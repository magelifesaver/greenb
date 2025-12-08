<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/ai-forms/storage/methods/fn-get-forms-list.php
// Status: MODIFIED

namespace WPAICG\AIForms\Storage\Methods;

use WPAICG\AIForms\Admin\AIPKit_AI_Form_Admin_Setup;
use WP_Query;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for retrieving a list of all AI Forms.
 * UPDATED: Now supports pagination, searching, and sorting.
 * UPDATED: Optimized to prevent N+1 queries by fetching all post meta in a single query.
 *
 * @param \WPAICG\AIForms\Storage\AIPKit_AI_Form_Storage $storageInstance The instance of the storage class.
 * @param array $args WP_Query arguments extended with 'search' and 'filter_provider'.
 * @return array An array containing 'forms' and 'pagination' data.
 */
function get_forms_list_logic(\WPAICG\AIForms\Storage\AIPKit_AI_Form_Storage $storageInstance, array $args = []): array
{
    if (!class_exists(AIPKit_AI_Form_Admin_Setup::class)) {
        return ['forms' => [], 'pagination' => ['total_forms' => 0, 'total_pages' => 0]];
    }
    $defaults = [
        'post_type'      => AIPKit_AI_Form_Admin_Setup::POST_TYPE,
        'post_status'    => ['publish', 'draft'],
        'posts_per_page' => 20,
        'paged'          => 1,
        's'              => '', // for search
        'orderby'        => 'title',
        'order'          => 'ASC',
    ];

    // Map 'search' arg to 's' for WP_Query
    if (isset($args['search'])) {
        $args['s'] = $args['search'];
        unset($args['search']);
    }

    $query_args = wp_parse_args($args, $defaults);
    $meta_query = [];

    // Handle provider filter
    if (!empty($args['filter_provider']) && $args['filter_provider'] !== 'all') {
        $meta_query[] = [
            'key'     => '_aipkit_ai_form_ai_provider',
            'value'   => $args['filter_provider'],
            'compare' => '=',
        ];
    }

    // Handle sorting by post meta (provider, model)
    if (isset($args['orderby']) && in_array($args['orderby'], ['provider', 'model'])) {
        // WP_Query can handle a single meta_key for sorting directly.
        // If we also have a filter, we need to use a more complex meta_query.
        $sort_key = '_aipkit_ai_form_ai_' . $args['orderby'];
        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Reason: The meta/tax query is essential for the feature's functionality. Its performance impact is considered acceptable as the query is highly specific, paginated, cached, or runs in a non-critical admin/cron context.
        $query_args['meta_key'] = $sort_key;
        $query_args['orderby'] = 'meta_value';
    }

    // Assign meta query to query args if it's not empty
    if (!empty($meta_query)) {
        if (count($meta_query) > 1) {
            $meta_query['relation'] = 'AND';
        }
        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Reason: The meta/tax query is essential for the feature's functionality. Its performance impact is considered acceptable as the query is highly specific, paginated, cached, or runs in a non-critical admin/cron context.
        $query_args['meta_query'] = $meta_query;
    }

    $query = new WP_Query($query_args);
    $forms_data = [];
    $post_ids = [];

    if ($query->have_posts()) {
        // First, collect all post IDs from the query result
        $post_ids = wp_list_pluck($query->posts, 'ID');
    }

    $meta_map = [];
    if (!empty($post_ids)) {
        global $wpdb;

        // --- Caching for post meta query ---
        $cache_key = 'aipkit_ai_forms_list_meta_' . md5(implode(',', $post_ids));
        $cache_group = 'aipkit_ai_forms';
        $all_meta_results = wp_cache_get($cache_key, $cache_group);

        if (false === $all_meta_results) {
            // Construct the placeholders for the IN clause
            $id_placeholders = implode(', ', array_fill(0, count($post_ids), '%d'));
            $meta_keys_to_fetch = ['_aipkit_ai_form_ai_provider', '_aipkit_ai_form_ai_model'];
            $meta_key_placeholders = implode(', ', array_fill(0, count($meta_keys_to_fetch), '%s'));

            // Prepare the query to fetch all meta data in one go
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- This is the correct and safe way to handle a dynamic number of items in an IN clause. The placeholders are generated correctly before being passed to prepare().
            $meta_query_sql = $wpdb->prepare("SELECT post_id, meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id IN ($id_placeholders) AND meta_key IN ($meta_key_placeholders)", array_merge($post_ids, $meta_keys_to_fetch));

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Efficiently fetching specific meta for a list of posts. Caching is implemented and the query is prepared on the line above.
            $all_meta_results = $wpdb->get_results($meta_query_sql, ARRAY_A);

            // Cache the result for a short period (e.g., 1 minute)
            wp_cache_set($cache_key, $all_meta_results, $cache_group, MINUTE_IN_SECONDS);
        }
        // --- End Caching ---

        // Map the results for easy lookup
        if (is_array($all_meta_results)) {
            foreach ($all_meta_results as $meta_row) {
                $meta_map[$meta_row['post_id']][$meta_row['meta_key']] = $meta_row['meta_value'];
            }
        }
    }

    // Now, build the final data array using the fetched posts and meta map
    if ($query->have_posts()) {
        foreach ($query->posts as $post) {
            $form_id = $post->ID;
            $forms_data[] = [
                'id' => $form_id,
                'title' => $post->post_title,
                'shortcode' => '[aipkit_ai_form id=' . $form_id . ']',
                'status' => $post->post_status,
                'provider' => $meta_map[$form_id]['_aipkit_ai_form_ai_provider'] ?? null,
                'model' => $meta_map[$form_id]['_aipkit_ai_form_ai_model'] ?? null,
            ];
        }
        wp_reset_postdata(); // Important after custom loops to restore the global post object
    }


    return [
        'forms' => $forms_data,
        'pagination' => [
            'total_forms' => (int) $query->found_posts,
            'total_pages' => (int) $query->max_num_pages,
        ]
    ];
}
