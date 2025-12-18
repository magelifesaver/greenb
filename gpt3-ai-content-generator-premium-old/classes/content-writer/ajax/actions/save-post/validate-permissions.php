<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/content-writer/ajax/actions/save-post/validate-permissions.php
// Status: NEW FILE

namespace WPAICG\ContentWriter\Ajax\Actions\SavePost;

use WPAICG\ContentWriter\Ajax\Actions\AIPKit_Content_Writer_Save_Post_Action;
use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Validates nonce and module access permissions for saving a post.
 *
 * @param AIPKit_Content_Writer_Save_Post_Action $handler The handler instance.
 * @return true|WP_Error True on success, WP_Error on failure.
 */
function validate_permissions_logic(AIPKit_Content_Writer_Save_Post_Action $handler): bool|WP_Error
{
    return $handler->check_module_access_permissions('content-writer', 'aipkit_content_writer_nonce');
}
