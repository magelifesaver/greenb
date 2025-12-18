<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/images/class-aipkit-image-storage-helper.php
// Status: MODIFIED

namespace WPAICG\Images;

use WP_Error;
use WP_User;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * AIPKit_Image_Storage_Helper
 *
 * Helper class for saving generated images to the WordPress Media Library
 * and managing related metadata.
 */
class AIPKit_Image_Storage_Helper
{
    /**
     * Saves a generated image to the media library.
     *
     * @param array   $image_data_item    An item from the API response's 'images' array (containing 'url' or 'b64_json').
     * @param string  $original_prompt    The original prompt used for generation.
     * @param array   $generation_options The final options used for generation (provider, model, size, etc.).
     * @param int|null $wp_user_id         The WordPress user ID to associate with the attachment, or null for system.
     *
     * @return int|WP_Error The attachment ID on success, or WP_Error on failure.
     */
    public static function save_image_to_media_library(array $image_data_item, string $original_prompt, array $generation_options, ?int $wp_user_id): int|WP_Error
    {
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $image_content_binary = null;
        $filename_base = sanitize_title_with_dashes(substr($original_prompt, 0, 50)) ?: 'ai-generated-image';
        $extension = 'png'; // Default extension
        $mime_type = 'image/png'; // Default mime type

        // Determine content and type
        if (!empty($image_data_item['b64_json'])) {
            $image_content_binary = base64_decode($image_data_item['b64_json']);
            if ($image_content_binary === false) {
                return new WP_Error('image_decode_failed', __('Failed to decode base64 image data.', 'gpt3-ai-content-generator'));
            }
            // Determine extension from output_format if gpt-image-1
            if (($generation_options['model'] ?? '') === 'gpt-image-1' && !empty($generation_options['output_format'])) {
                $extension = strtolower($generation_options['output_format']); // png, jpeg, webp
                if ($extension === 'jpeg') {
                    $extension = 'jpg';
                } // common alias
                $mime_type = 'image/' . ($extension === 'jpg' ? 'jpeg' : $extension);
            }
            // For DALL-E 2/3, if b64_json is used, it's typically PNG.
        } elseif (!empty($image_data_item['url'])) {
            $response = wp_safe_remote_get($image_data_item['url'], ['timeout' => 60]);
            if (is_wp_error($response)) {
                return new WP_Error('image_download_failed', __('Failed to download image from URL: ', 'gpt3-ai-content-generator') . $response->get_error_message());
            }
            $image_content_binary = wp_remote_retrieve_body($response);
            if (empty($image_content_binary)) {
                return new WP_Error('image_download_empty', __('Downloaded image content is empty.', 'gpt3-ai-content-generator'));
            }
            // Try to determine extension from URL or Content-Type header
            $url_parts = wp_parse_url($image_data_item['url']);
            $url_path = $url_parts['path'] ?? '';
            $url_extension = pathinfo($url_path, PATHINFO_EXTENSION);
            if (!empty($url_extension) && in_array(strtolower($url_extension), ['png', 'jpg', 'jpeg', 'webp', 'gif'])) {
                $extension = strtolower($url_extension);
                $mime_type = 'image/' . ($extension === 'jpg' ? 'jpeg' : $extension);
            } else {
                $content_type_header = wp_remote_retrieve_header($response, 'content-type');
                if ($content_type_header) {
                    if (str_contains($content_type_header, 'image/png')) {
                        $extension = 'png';
                        $mime_type = 'image/png';
                    } elseif (str_contains($content_type_header, 'image/jpeg')) {
                        $extension = 'jpg';
                        $mime_type = 'image/jpeg';
                    } elseif (str_contains($content_type_header, 'image/webp')) {
                        $extension = 'webp';
                        $mime_type = 'image/webp';
                    } elseif (str_contains($content_type_header, 'image/gif')) {
                        $extension = 'gif';
                        $mime_type = 'image/gif';
                    }
                }
            }
        } else {
            return new WP_Error('no_image_data', __('No image URL or base64 data provided.', 'gpt3-ai-content-generator'));
        }

        $filename = $filename_base . '-' . time() . '.' . $extension;

        // Upload bits
        $upload = wp_upload_bits($filename, null, $image_content_binary);
        if (!empty($upload['error'])) {
            return new WP_Error('image_upload_failed', __('Failed to upload image: ', 'gpt3-ai-content-generator') . $upload['error']);
        }

        // Prepare attachment data
        $attachment_title = substr($original_prompt, 0, 100) ?: __('AI Generated Image', 'gpt3-ai-content-generator');
        $attachment = [
            'guid'           => $upload['url'],
            'post_mime_type' => $mime_type,
            'post_title'     => $attachment_title,
            'post_content'   => $original_prompt,
            'post_status'    => 'inherit',
        ];
        if ($wp_user_id && $wp_user_id > 0) {
            $attachment['post_author'] = $wp_user_id;
        }

        // Insert attachment
        $attach_id = wp_insert_attachment($attachment, $upload['file']);
        if (is_wp_error($attach_id)) {
            wp_delete_file($upload['file']); // Clean up uploaded file
            return $attach_id;
        }

        // Generate attachment metadata
        $attach_data = wp_generate_attachment_metadata($attach_id, $upload['file']);
        wp_update_attachment_metadata($attach_id, $attach_data);

        // Add custom meta data
        update_post_meta($attach_id, '_aipkit_generated_image', '1');
        update_post_meta($attach_id, '_aipkit_image_prompt', $original_prompt);
        if (isset($generation_options['provider'])) {
            update_post_meta($attach_id, '_aipkit_image_provider', sanitize_text_field($generation_options['provider']));
        }
        if (isset($generation_options['model'])) {
            update_post_meta($attach_id, '_aipkit_image_model', sanitize_text_field($generation_options['model']));
        }
        if (isset($generation_options['size'])) {
            update_post_meta($attach_id, '_aipkit_image_size', sanitize_text_field($generation_options['size']));
        }
        if (isset($generation_options['quality'])) {
            update_post_meta($attach_id, '_aipkit_image_quality', sanitize_text_field($generation_options['quality']));
        }
        if (isset($generation_options['style'])) {
            update_post_meta($attach_id, '_aipkit_image_style', sanitize_text_field($generation_options['style']));
        }
        if (isset($image_data_item['revised_prompt'])) {
            update_post_meta($attach_id, '_aipkit_image_revised_prompt', sanitize_textarea_field($image_data_item['revised_prompt']));
        }
        if (isset($image_data_item['photographer'])) {
            update_post_meta($attach_id, '_aipkit_image_photographer', sanitize_text_field($image_data_item['photographer']));
        }
        if (isset($image_data_item['alt'])) {
            update_post_meta($attach_id, '_aipkit_image_alt_text', sanitize_text_field($image_data_item['alt']));
            update_post_meta($attach_id, '_wp_attachment_image_alt', sanitize_text_field($image_data_item['alt']));
        }

        return $attach_id;
    }
}
