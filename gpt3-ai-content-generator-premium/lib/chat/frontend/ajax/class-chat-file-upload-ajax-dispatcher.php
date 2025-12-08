<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/lib/chat/frontend/ajax/class-chat-file-upload-ajax-dispatcher.php
// Status: NEW FILE

namespace WPAICG\Lib\Chat\Frontend\Ajax;

use WPAICG\Chat\Admin\Ajax\Traits\Trait_CheckFrontendPermissions;
use WPAICG\Chat\Admin\Ajax\Traits\Trait_SendWPError;
use WPAICG\Lib\Chat\Frontend\Ajax\Handlers\ChatFileUploadHandler;
use WPAICG\Lib\Chat\Frontend\Ajax\Utils\ChatUploadConfigLoader;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * ChatFileUploadAjaxDispatcher
 *
 * Acts as the main entry point for the frontend file upload AJAX action.
 * It verifies permissions and then delegates the core processing to the ChatFileUploadHandler.
 */
class ChatFileUploadAjaxDispatcher
{
    use Trait_CheckFrontendPermissions;
    use Trait_SendWPError;

    private $file_upload_handler;

    public function __construct()
    {
        // The config loader will ensure dependencies for the handler are available
        // or throw an error if critical ones are missing.
        $config_loader = new ChatUploadConfigLoader();

        // Pass the config loader to the main handler.
        // The handler will then use it to get its necessary dependencies.
        $this->file_upload_handler = new ChatFileUploadHandler($config_loader);
    }

    /**
     * AJAX handler for frontend file uploads.
     * Hooked to 'wp_ajax_aipkit_frontend_chat_upload_file' and 'wp_ajax_nopriv_aipkit_frontend_chat_upload_file'.
     */
    public function ajax_handle_frontend_file_upload(): void
    {
        $permission_check = $this->check_frontend_permissions(); // Uses Trait
        if (is_wp_error($permission_check)) {
            $this->send_wp_error($permission_check); // Uses Trait
            return;
        }

        // Delegate to the main handler, passing the $_POST and $_FILES superglobals
        // The handler will be responsible for further validation and processing.
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked by check_frontend_permissions() above.
        $result = $this->file_upload_handler->handle_upload($_POST, $_FILES);

        if (is_wp_error($result)) {
            $this->send_wp_error($result);
        } else {
            wp_send_json_success($result);
        }
    }
}
