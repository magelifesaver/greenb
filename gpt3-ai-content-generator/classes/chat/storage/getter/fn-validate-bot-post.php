<?php
// File: classes/chat/storage/getter/fn-validate-bot-post.php
// Status: NEW FILE

namespace WPAICG\Chat\Storage\GetterMethods;

use WPAICG\Chat\Admin\AdminSetup;
use WP_Error;
use WP_Post;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Validates the bot post ID, type, and status.
 *
 * @param int $bot_id The ID of the bot post.
 * @return WP_Post|WP_Error The WP_Post object on success, or WP_Error on failure.
 */
function validate_bot_post_logic(int $bot_id): WP_Post|WP_Error {
    if (!class_exists(AdminSetup::class)) {
        $admin_setup_path = WPAICG_PLUGIN_DIR . 'classes/chat/admin/chat_admin_setup.php';
        if (file_exists($admin_setup_path)) {
            require_once $admin_setup_path;
        } else {
            return new WP_Error('dependency_missing_validator', __('AdminSetup class missing.', 'gpt3-ai-content-generator'));
        }
    }

    $post = get_post($bot_id);
    if (!$post) {
        return new WP_Error('post_not_found_validator', __('Chatbot post not found.', 'gpt3-ai-content-generator'));
    }

    if ($post->post_type !== AdminSetup::POST_TYPE) {
        return new WP_Error('invalid_post_type_validator', __('Invalid chatbot post type.', 'gpt3-ai-content-generator'));
    }

    if (!in_array($post->post_status, ['publish', 'draft'], true)) {
        return new WP_Error('invalid_post_status_validator', __('Chatbot post has an invalid status.', 'gpt3-ai-content-generator'));
    }
    return $post;
}