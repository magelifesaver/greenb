<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/content-writer/ajax/actions/create-task/validate-permissions.php
// Status: NEW FILE

namespace WPAICG\ContentWriter\Ajax\Actions\CreateTask;

use WPAICG\ContentWriter\Ajax\Actions\AIPKit_Content_Writer_Create_Task_Action;
use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

/**
* Validates nonce and module access permissions.
*
* @param AIPKit_Content_Writer_Create_Task_Action $handler The handler instance.
* @return true|WP_Error True on success, WP_Error on failure.
*/
function validate_permissions_logic(AIPKit_Content_Writer_Create_Task_Action $handler): bool|WP_Error
{
    return $handler->check_module_access_permissions('content-writer', 'aipkit_content_writer_nonce');
}
