<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/images/manager/ajax/ajax_load_more_image_history.php
// Status: NEW FILE

namespace WPAICG\Images\Manager\Ajax;

use WP_Query;

if (!defined('ABSPATH')) {
    exit;
}

function ajax_load_more_image_history_logic(): void
{
    check_ajax_referer('aipkit_image_generator_nonce', '_ajax_nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => __('You must be logged in to view history.', 'gpt3-ai-content-generator')], 403);
        return;
    }

    $page = isset($_POST['page']) ? absint($_POST['page']) : 2; // Start from page 2
    $user_id = get_current_user_id();

    $args = [
        'post_type'      => 'attachment',
        'post_status'    => 'inherit',
        'author'         => $user_id,
        'posts_per_page' => 20, // Keep this consistent with render_image_history
        'paged'          => $page,
        // Updated meta query to include both images and videos
        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Reason: The meta/tax query is essential for the feature's functionality. Its performance impact is considered acceptable as the query is highly specific, paginated, cached, or runs in a non-critical admin/cron context.
        'meta_query'     => [
            'relation' => 'OR',
            [
                'key'     => '_aipkit_generated_image',
                'value'   => '1',
                'compare' => '=',
            ],
            [
                'key'     => '_aipkit_generated_video',
                'value'   => '1',
                'compare' => '=',
            ]
        ],
        'orderby'        => 'date',
        'order'          => 'DESC',
    ];
    $query = new WP_Query($args);

    $html_items = '';
    ob_start();
    if ($query->have_posts()) {
        while ($query->have_posts()) : $query->the_post();
            $attachment_id = get_the_ID();
            $full_url = wp_get_attachment_url($attachment_id);
            $mime_type = get_post_mime_type($attachment_id);
            
            // Check if this is an image or video
            $is_video = (get_post_meta($attachment_id, '_aipkit_generated_video', true) === '1');
            $is_image = (get_post_meta($attachment_id, '_aipkit_generated_image', true) === '1');
            
            if ($is_video) {
                // Handle video display
                $prompt = get_post_meta($attachment_id, '_aipkit_video_prompt', true);
                $provider = get_post_meta($attachment_id, '_aipkit_video_provider', true);
                $model = get_post_meta($attachment_id, '_aipkit_video_model', true);
                $size = get_post_meta($attachment_id, '_aipkit_video_size', true);
                $duration = get_post_meta($attachment_id, '_aipkit_video_duration', true);
                
                // Format duration for display
                $duration_display = '';
                if ($duration) {
                    $duration_display = sprintf(__('Duration: %ds', 'gpt3-ai-content-generator'), intval($duration));
                }
                
                ?>
                <div class="aipkit-image-history-item aipkit-video-history-item">
                    <div class="aipkit-video-preview">
                        <video controls preload="metadata" style="width: 100%; max-width: 200px; height: auto;">
                            <source src="<?php echo esc_url($full_url); ?>" type="video/mp4">
                            <?php esc_html_e('Your browser does not support the video tag.', 'gpt3-ai-content-generator'); ?>
                        </video>
                        <div class="aipkit-video-overlay">
                            <span class="aipkit-media-type-badge"><?php esc_html_e('VIDEO', 'gpt3-ai-content-generator'); ?></span>
                        </div>
                    </div>
                    <button type="button" class="aipkit-image-history-delete-btn" data-attachment-id="<?php echo esc_attr($attachment_id); ?>" title="<?php esc_attr_e('Delete Video', 'gpt3-ai-content-generator'); ?>">
                        <span class="dashicons dashicons-trash"></span>
                    </button>
                    <div class="aipkit-image-history-info">
                        <?php if ($prompt): ?>
                            <p class="aipkit-image-history-prompt" title="<?php echo esc_attr($prompt); ?>">
                                <strong><?php esc_html_e('Prompt:', 'gpt3-ai-content-generator'); ?></strong> <?php echo esc_html(wp_trim_words($prompt, 10, '...')); ?>
                            </p>
                        <?php endif; ?>
                        <p class="aipkit-image-history-meta">
                            <?php 
                            $meta_parts = array_filter([$provider, $model, $size, $duration_display]);
                            echo esc_html(implode(' / ', $meta_parts)); 
                            ?>
                        </p>
                    </div>
                </div>
                <?php
                
            } elseif ($is_image) {
                // Handle image display (existing logic)
                $thumbnail_url = wp_get_attachment_image_url($attachment_id, 'thumbnail');
                $prompt = get_post_meta($attachment_id, '_aipkit_image_prompt', true);
                $provider = get_post_meta($attachment_id, '_aipkit_image_provider', true);
                $model = get_post_meta($attachment_id, '_aipkit_image_model', true);
                $size = get_post_meta($attachment_id, '_aipkit_image_size', true);
                ?>
                <div class="aipkit-image-history-item">
                    <a href="<?php echo esc_url($full_url); ?>" target="_blank" rel="noopener noreferrer">
                        <?php // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage -- Reason: The image source is correctly retrieved using a WordPress function (e.g., `wp_get_attachment_image_url`). The `<img>` tag is constructed manually to build a custom HTML structure with specific wrappers, classes, or attributes that are not achievable with the standard `wp_get_attachment_image()` function. ?>
                        <img src="<?php echo esc_url($thumbnail_url); ?>" alt="<?php echo esc_attr($prompt ?: 'AI Generated Image'); ?>">
                        <div class="aipkit-image-overlay">
                            <span class="aipkit-media-type-badge"><?php esc_html_e('IMAGE', 'gpt3-ai-content-generator'); ?></span>
                        </div>
                    </a>
                    <button type="button" class="aipkit-image-history-delete-btn" data-attachment-id="<?php echo esc_attr($attachment_id); ?>" title="<?php esc_attr_e('Delete Image', 'gpt3-ai-content-generator'); ?>">
                        <span class="dashicons dashicons-trash"></span>
                    </button>
                    <div class="aipkit-image-history-info">
                        <?php if ($prompt): ?>
                            <p class="aipkit-image-history-prompt" title="<?php echo esc_attr($prompt); ?>">
                                <strong><?php esc_html_e('Prompt:', 'gpt3-ai-content-generator'); ?></strong> <?php echo esc_html(wp_trim_words($prompt, 10, '...')); ?>
                            </p>
                        <?php endif; ?>
                        <?php if ($model): ?>
                             <p class="aipkit-image-history-meta">
                                <?php echo esc_html($provider . ' / ' . $model . ' / ' . $size); ?>
                             </p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php
            }
        endwhile;
    }
    $html_items = ob_get_clean();
    wp_reset_postdata();

    $has_more = ($page < $query->max_num_pages);

    wp_send_json_success([
        'html' => $html_items,
        'has_more' => $has_more
    ]);
}
