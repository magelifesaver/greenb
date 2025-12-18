<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/chat/admin/ajax/base_ajax_handler.php
// Status: MODIFIED

namespace WPAICG\Chat\Admin\Ajax;

use WPAICG\Chat\Admin\Ajax\Traits\Trait_CheckAdminPermissions;
use WPAICG\Chat\Admin\Ajax\Traits\Trait_CheckModuleAccess;
use WPAICG\Chat\Admin\Ajax\Traits\Trait_CheckFrontendPermissions;
use WPAICG\Chat\Admin\Ajax\Traits\Trait_SendWPError;
use WP_Error; // Keep if any concrete class method directly returns it, though unlikely for protected methods here.

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Base class for Chat Admin AJAX Handlers.
 * Provides common permission checks and error handling by using traits.
 */
abstract class BaseAjaxHandler {

    use Trait_CheckAdminPermissions;
    use Trait_CheckModuleAccess;
    use Trait_CheckFrontendPermissions;
    use Trait_SendWPError;

    protected $required_capability = 'manage_options'; // Default capability
}