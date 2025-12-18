<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/chat/initializer/load-admin-setup.php
// Status: NEW FILE

namespace WPAICG\Chat\Initializer;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for loading Chat Admin Setup dependencies.
 * Called by WPAICG\Chat\Initializer::load_dependencies().
 */
function load_admin_setup_logic(): void {
    $base_path = WPAICG_PLUGIN_DIR . 'classes/chat/';
    $admin_setup_path = $base_path . 'admin/chat_admin_setup.php';

    if (file_exists($admin_setup_path) && !class_exists(\WPAICG\Chat\Admin\AdminSetup::class)) {
        require_once $admin_setup_path;
    }
}