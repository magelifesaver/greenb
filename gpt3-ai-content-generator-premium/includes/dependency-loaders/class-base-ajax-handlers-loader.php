<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/includes/dependency-loaders/class-base-ajax-handlers-loader.php
// Status: MODIFIED

namespace WPAICG\Includes\DependencyLoaders;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Base_Ajax_Handlers_Loader
{
    public static function load()
    {
        $traits_path = WPAICG_PLUGIN_DIR . 'classes/chat/admin/ajax/traits/';
        $trait_files = [
            'Trait_CheckAdminPermissions.php',
            'Trait_CheckModuleAccess.php',
            'Trait_CheckFrontendPermissions.php',
            'Trait_SendWPError.php',
        ];

        foreach ($trait_files as $trait_file) {
            $full_trait_path = $traits_path . $trait_file;
            if (file_exists($full_trait_path)) {
                require_once $full_trait_path;
            }
        }
        // Now load the base handler class that uses these traits
        $base_chat_ajax_handler_path = WPAICG_PLUGIN_DIR . 'classes/chat/admin/ajax/base_ajax_handler.php';
        if (file_exists($base_chat_ajax_handler_path)) {
            require_once $base_chat_ajax_handler_path;
        }

        // Continue loading other base handlers if any
        $base_dashboard_ajax_handler_path = WPAICG_PLUGIN_DIR . 'classes/dashboard/ajax/class-aipkit-base-dashboard-ajax-handler.php';
        if (file_exists($base_dashboard_ajax_handler_path)) {
            require_once $base_dashboard_ajax_handler_path;
        }

        // --- ADDED: Load Dashboard specific AJAX handlers here ---
        $dashboard_ajax_path = WPAICG_PLUGIN_DIR . 'classes/dashboard/ajax/';
        $settings_ajax_handler_path = $dashboard_ajax_path . 'class-aipkit-settings-ajax-handler.php';
        if (file_exists($settings_ajax_handler_path)) {
            require_once $settings_ajax_handler_path;
        }
        $models_ajax_handler_path = $dashboard_ajax_path . 'class-aipkit-models-ajax-handler.php';
        if (file_exists($models_ajax_handler_path)) {
            require_once $models_ajax_handler_path;
        }
        // --- END ADDED ---


        $core_ajax_handler_path = WPAICG_PLUGIN_DIR . 'classes/core/ajax/class-aipkit-core-ajax-handler.php';
        if (file_exists($core_ajax_handler_path)) {
            require_once $core_ajax_handler_path;
        }

        // --- NEW: Load Semantic Search AJAX Handler ---
        $semantic_search_ajax_handler_path = WPAICG_PLUGIN_DIR . 'classes/core/ajax/class-aipkit-semantic-search-ajax-handler.php';
        if (file_exists($semantic_search_ajax_handler_path)) {
            require_once $semantic_search_ajax_handler_path;
        }
        // --- END NEW ---
    }
}