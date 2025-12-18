<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/ajax/migration/analysis/count-cpt-posts.php
// Status: MODIFIED

namespace WPAICG\Admin\Ajax\Migration\Analysis;

use WP_Query;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Counts posts for given CPTs and returns a summary.
 *
 * @param array $post_types An array of post type slugs.
 * @return array ['count' => int, 'summary' => string, 'details' => array]
 */
function count_cpt_posts_logic(array $post_types): array
{
    $total_count = 0;
    $details = [];

    foreach ($post_types as $post_type) {

        $query = new WP_Query([
            'post_type' => $post_type,
            'post_status' => 'any', // 'any' is important for finding all statuses
            'posts_per_page' => -1,
            'fields' => 'ids',
            'no_found_rows' => false, // Ensure found_posts is calculated
        ]);
        $count = $query->found_posts;
        $total_count += $count;
        if ($count > 0) {
            $post_type_object = get_post_type_object($post_type);
            $label = $post_type_object ? $post_type_object->labels->singular_name : $post_type; // Use singular_name for better label
            // translators: %1$d is the number of posts, %2$s is the CPT name.
            $details[] = sprintf(__('%1$d posts found in "%2$s" CPT.', 'gpt3-ai-content-generator'), $count, $label);
        }
    }

    $summary = sprintf(
        // translators: %d is the number of legacy posts found.
        _n('%d legacy post found.', '%d legacy posts found.', $total_count, 'gpt3-ai-content-generator'),
        $total_count
    );

    return ['count' => $total_count, 'summary' => $summary, 'details' => $details];
}
