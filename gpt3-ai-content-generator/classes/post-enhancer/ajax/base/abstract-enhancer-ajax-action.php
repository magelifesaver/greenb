<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/post-enhancer/ajax/base/abstract-enhancer-ajax-action.php
// Status: NEW FILE

namespace WPAICG\PostEnhancer\Ajax\Base;

use WP_Error;
use WPAICG\AIPKit_Role_Manager;

if (!defined('ABSPATH')) {
    exit;
}

abstract class AIPKit_Post_Enhancer_Base_Ajax_Action
{
    public const MODULE_SLUG = 'ai_post_enhancer';

    abstract public function handle(): void;

    protected function check_permissions(string $nonce_action): bool|\WP_Error
    {
        if (!isset($_POST['_ajax_nonce']) || !wp_verify_nonce(sanitize_key($_POST['_ajax_nonce']), $nonce_action)) {
            return new WP_Error('nonce_failure', __('Security check failed.', 'gpt3-ai-content-generator'), ['status' => 403]);
        }
        if (!AIPKit_Role_Manager::user_can_access_module(self::MODULE_SLUG)) {
            return new WP_Error('permission_denied_module', __('You do not have permission to use this feature.', 'gpt3-ai-content-generator'), ['status' => 403]);
        }
        return true;
    }

    protected function get_post(): \WP_Post|\WP_Error
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Reason: Nonce is checked in check_permissions.
        $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
        if (!$post_id) {
            return new WP_Error('missing_post_id', __('Missing post ID.', 'gpt3-ai-content-generator'), ['status' => 400]);
        }
        $post = get_post($post_id);
        if (!$post) {
            return new WP_Error('post_not_found', __('Post not found.', 'gpt3-ai-content-generator'), ['status' => 404]);
        }
        if (!current_user_can('edit_post', $post_id)) {
            return new WP_Error('permission_denied_post', __('You do not have permission to edit this post.', 'gpt3-ai-content-generator'), ['status' => 403]);
        }
        return $post;
    }

    protected function send_error_response(WP_Error $error): void
    {
        $status = is_array($error->get_error_data()) && isset($error->get_error_data()['status']) ? $error->get_error_data()['status'] : 400;
        wp_send_json_error(['message' => $error->get_error_message()], $status);
    }
}
