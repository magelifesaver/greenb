<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/content-writer/ajax/actions/class-aipkit-content-writer-generate-images-action.php
// Status: MODIFIED

namespace WPAICG\ContentWriter\Ajax\Actions;

use WPAICG\ContentWriter\Ajax\AIPKit_Content_Writer_Base_Ajax_Action;
use WPAICG\ContentWriter\AIPKit_Content_Writer_Image_Handler;
use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles the dedicated AJAX action for generating AI images within the Content Writer.
 * UPDATED: Strips large base64 data from the response to prevent "Request Entity Too Large" errors.
 */
class AIPKit_Content_Writer_Generate_Images_Action extends AIPKit_Content_Writer_Base_Ajax_Action
{
    public function handle()
    {
        $permission_check = $this->check_module_access_permissions('content-writer', 'aipkit_content_writer_nonce');
        if (is_wp_error($permission_check)) {
            $this->send_wp_error($permission_check);
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Reason: Nonce is checked in check_module_access_permissions.
        $settings = isset($_POST) ? wp_unslash($_POST) : [];
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Reason: Nonce is checked in check_module_access_permissions.
        $final_title = isset($settings['final_title']) ? sanitize_text_field($settings['final_title']) : '';
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Reason: Nonce is checked in check_module_access_permissions.
        $final_keywords = isset($settings['keywords']) ? sanitize_text_field($settings['keywords']) : '';
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Reason: Nonce is checked in check_module_access_permissions.
        $original_topic = isset($settings['original_topic']) ? sanitize_text_field($settings['original_topic']) : $final_title;

        if (!class_exists(AIPKit_Content_Writer_Image_Handler::class)) {
            $this->send_wp_error(new WP_Error('missing_image_handler', 'Image generation component is missing.', ['status' => 500]));
            return;
        }

        $image_handler = new AIPKit_Content_Writer_Image_Handler();
    $image_result = $image_handler->generate_and_prepare_images($settings, $final_title, $final_keywords, $original_topic);

        if (is_wp_error($image_result)) {
            $this->send_wp_error($image_result);
            return;
        }

        // --- START FIX: Strip b64_json from response to prevent 413 "Request Entity Too Large" on subsequent saves ---
        if (isset($image_result['in_content_images']) && is_array($image_result['in_content_images'])) {
            foreach ($image_result['in_content_images'] as &$image_item) {
                unset($image_item['b64_json']);
            }
            unset($image_item); // Unset the reference
        }
        // --- END FIX ---

        // Optional logging under the same conversation
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $conversation_uuid = isset($_POST['conversation_uuid']) ? sanitize_text_field(wp_unslash($_POST['conversation_uuid'])) : '';
        if (!empty($conversation_uuid) && $this->log_storage) {
            $current_user = wp_get_current_user();
            $provider = isset($settings['image_provider']) ? sanitize_text_field($settings['image_provider']) : '';
            $model = isset($settings['image_model']) ? sanitize_text_field($settings['image_model']) : '';
            $generate_in_content = ($settings['generate_images_enabled'] ?? '0') === '1';
            $image_count = absint($settings['image_count'] ?? 0);
            $generate_featured = ($settings['generate_featured_image'] ?? '0') === '1';

            $base = [
                'bot_id' => null,
                'user_id' => get_current_user_id(),
                'session_id' => null,
                'conversation_uuid' => $conversation_uuid,
                'module' => 'content_writer',
                'is_guest' => 0,
                'role' => is_a($current_user, 'WP_User') ? implode(', ', $current_user->roles) : '',
                'ip_address' => isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : null,
                'timestamp' => time(),
                'ai_provider' => $provider,
                'ai_model' => $model,
            ];

            // Build a compact list of images to avoid large payloads
            $inline_images_meta = [];
            if (!empty($image_result['in_content_images']) && is_array($image_result['in_content_images'])) {
                foreach ($image_result['in_content_images'] as $idx => $img) {
                    $inline_images_meta[] = [
                        'type' => 'inline',
                        'index' => $idx,
                        'attachment_id' => isset($img['attachment_id']) ? $img['attachment_id'] : null,
                        'url' => $img['url'] ?? ($img['src'] ?? ($img['image_url'] ?? null)),
                        'provider' => $provider,
                    ];
                }
            }
            $featured_meta = null;
            if (!empty($image_result['featured_image_id'])) {
                $featured_meta = [
                    'type' => 'featured',
                    'attachment_id' => $image_result['featured_image_id'],
                    'provider' => $provider,
                ];
            }

            // Log the user intent
        $this->log_storage->log_message(array_merge($base, [
                'message_role' => 'user',
                'message_content' => 'Generate Images',
                'request_payload' => [
                    'original_topic' => $original_topic,
                    'final_title' => $final_title,
                    'keywords' => $final_keywords,
            'image_prompt' => isset($settings['image_prompt']) ? (string) $settings['image_prompt'] : null,
            'featured_image_prompt' => isset($settings['featured_image_prompt']) ? (string) $settings['featured_image_prompt'] : null,
                    'generate_images_enabled' => $generate_in_content ? 1 : 0,
                    'image_count' => $image_count,
                    'generate_featured_image' => $generate_featured ? 1 : 0,
                    'image_provider' => $provider,
                    'image_model' => $model,
                    'placement' => $settings['image_placement'] ?? 'after_first_h2',
                    'placement_param_x' => isset($settings['image_placement_param_x']) ? absint($settings['image_placement_param_x']) : null,
                ],
            ]));

            // Log the result with compact metadata
            $this->log_storage->log_message(array_merge($base, [
                'message_role' => 'bot',
                'message_content' => 'Images Generated',
                'usage' => null,
                'request_payload' => [
                    'result_summary' => [
                        'inline_count' => count($inline_images_meta),
                        'featured_present' => !empty($featured_meta),
                    ],
                    'images' => [
                        'inline' => $inline_images_meta,
                        'featured' => $featured_meta,
                    ],
                ],
            ]));
        }

        wp_send_json_success(['image_data' => $image_result]);
    }
}
