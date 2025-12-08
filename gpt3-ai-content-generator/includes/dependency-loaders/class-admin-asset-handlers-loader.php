<?php

namespace WPAICG\Includes\DependencyLoaders;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Admin_Asset_Handlers_Loader
{
    public static function load()
    {
        require_once WPAICG_PLUGIN_DIR . 'admin/assets/class-aipkit-dashboard-assets.php';
        require_once WPAICG_PLUGIN_DIR . 'admin/assets/class-aipkit-settings-assets.php';
        require_once WPAICG_PLUGIN_DIR . 'admin/assets/class-aipkit-user-credits-assets.php';
        require_once WPAICG_PLUGIN_DIR . 'admin/assets/class-aipkit-chat-admin-assets.php';
        require_once WPAICG_PLUGIN_DIR . 'admin/assets/class-aipkit-role-manager-assets.php';
        require_once WPAICG_PLUGIN_DIR . 'admin/assets/class-aipkit-post-enhancer-assets.php';
        require_once WPAICG_PLUGIN_DIR . 'admin/assets/class-aipkit-image-generator-assets.php';
        require_once WPAICG_PLUGIN_DIR . 'admin/assets/class-aipkit-content-writer-assets.php';
        require_once WPAICG_PLUGIN_DIR . 'admin/assets/class-aipkit-ai-training-assets.php';
        require_once WPAICG_PLUGIN_DIR . 'admin/assets/class-aipkit-vector-post-processor-assets.php';
        $autogpt_assets_path = WPAICG_PLUGIN_DIR . 'admin/assets/class-aipkit-autogpt-assets.php';
        if (file_exists($autogpt_assets_path)) {
            require_once $autogpt_assets_path;
        }
    }
}
