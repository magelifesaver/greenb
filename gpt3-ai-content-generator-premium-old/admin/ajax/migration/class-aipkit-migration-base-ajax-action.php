<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/ajax/migration/class-aipkit-migration-base-ajax-action.php
// Status: MODIFIED

namespace WPAICG\Admin\Ajax\Migration;

use WPAICG\Dashboard\Ajax\BaseDashboardAjaxHandler;
use WPAICG\WP_AI_Content_Generator_Activator; // For MIGRATION_LAST_ERROR_OPTION
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Base class for Migration Tool AJAX actions.
 */
abstract class AIPKit_Migration_Base_Ajax_Action extends BaseDashboardAjaxHandler
{
    public const MIGRATION_NONCE_ACTION = 'aipkit_migration_tool_action'; // Consistent nonce

    public function __construct()
    {
        // Constructor can be empty or initialize common dependencies for migration actions.
        // For now, BaseDashboardAjaxHandler handles nonce/permission checks based on this constant.
    }

    /**
     * Abstract method to be implemented by child classes to handle the specific AJAX request.
     */
    abstract public function handle_request();

    /**
     * Updates the status of a specific migration category.
     * @param string $category_key The key for the category (e.g., 'global_settings').
     * @param string $status The new status (e.g., 'completed', 'deleted', 'failed').
     */
    protected function update_category_status(string $category_key, string $status): void
    {
        $category_statuses = get_option(WP_AI_Content_Generator_Activator::MIGRATION_CATEGORY_STATUS_OPTION, []);
        $category_statuses[$category_key] = $status;
        update_option(WP_AI_Content_Generator_Activator::MIGRATION_CATEGORY_STATUS_OPTION, $category_statuses, 'no');
    }

    /**
     * A unified way to handle exceptions during migration/deletion actions.
     * @param \Exception $e The exception object.
     * @param string $error_code The WP_Error code to use.
     * @param string $category_key The key of the category that failed.
     */
    protected function handle_exception(\Exception $e, string $error_code, string $category_key = ''): void
    {
        $error_message = 'Error during migration: ' . $e->getMessage();
        update_option(WP_AI_Content_Generator_Activator::MIGRATION_LAST_ERROR_OPTION, $error_message, 'no');
        if (!empty($category_key)) {
            $this->update_category_status($category_key, 'failed');
        }
        $this->send_wp_error(new WP_Error($error_code, $error_message), 500);
    }
}
