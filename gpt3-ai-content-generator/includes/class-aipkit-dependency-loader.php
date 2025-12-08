<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/includes/class-aipkit-dependency-loader.php
// Status: MODIFIED

namespace WPAICG\Includes;

// Use statements for the new loader classes
use WPAICG\Includes\DependencyLoaders\Admin_Asset_Handlers_Loader;
use WPAICG\Includes\DependencyLoaders\Provider_Dependencies_Loader;
use WPAICG\Includes\DependencyLoaders\Core_Services_Loader;
use WPAICG\Includes\DependencyLoaders\Dashboard_Base_Classes_Loader;
use WPAICG\Includes\DependencyLoaders\Base_Ajax_Handlers_Loader;
use WPAICG\Includes\DependencyLoaders\Chat_Dependencies_Loader;
use WPAICG\Includes\DependencyLoaders\Speech_Dependencies_Loader;
use WPAICG\Includes\DependencyLoaders\Stt_Dependencies_Loader;
use WPAICG\Includes\DependencyLoaders\Rest_Dependencies_Loader;
use WPAICG\Includes\DependencyLoaders\Image_Dependencies_Loader;
use WPAICG\Includes\DependencyLoaders\Vector_Store_Dependencies_Loader;
use WPAICG\Includes\DependencyLoaders\Vector_Store_Ajax_Handlers_Loader;
use WPAICG\Includes\DependencyLoaders\Vector_Post_Processor_Classes_Loader;
use WPAICG\Includes\DependencyLoaders\Content_Writer_Dependencies_Loader;
use WPAICG\Includes\DependencyLoaders\Addon_Dependencies_Loader;
use WPAICG\Includes\DependencyLoaders\Post_Enhancer_Core_Loader;
use WPAICG\Includes\DependencyLoaders\Woocommerce_Writer_Loader;
use WPAICG\Includes\DependencyLoaders\Automated_Task_Dependencies_Loader;
use WPAICG\Includes\DependencyLoaders\Automated_Task_Ajax_Handlers_Loader;
use WPAICG\Includes\DependencyLoaders\Automated_Task_Cron_Helpers_Loader;
use WPAICG\Includes\DependencyLoaders\Hook_Registrars_Loader;
use WPAICG\Includes\DependencyLoaders\AI_Forms_Dependencies_Loader;
use WPAICG\Includes\DependencyLoaders\Core_Moderation_Dependencies_Loader;
// --- ADDED: Use statement for Migration AJAX Loader ---
use WPAICG\Includes\DependencyLoaders\Migration_Ajax_Handlers_Loader;

// --- END ADDED ---


// Ensure this file is only loaded by WordPress
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * AIPKit_Dependency_Loader
 * Handles loading all necessary plugin dependencies.
 * Loads core plugin files and then delegates to specialized loader classes.
 */
class AIPKit_Dependency_Loader
{
    /**
     * Load all required dependencies for the plugin.
     */
    public static function load()
    {
        // Core Plugin Files (Loaded directly before specialized loaders)
        require_once WPAICG_PLUGIN_DIR . 'includes/class-wp-ai-content-generator-i18n.php';
        require_once WPAICG_PLUGIN_DIR . 'public/class-wp-ai-content-generator-public.php';
        require_once WPAICG_PLUGIN_DIR . 'includes/class-aipkit-shortcodes-manager.php';
        require_once WPAICG_PLUGIN_DIR . 'includes/database-schema.php';
        require_once WPAICG_PLUGIN_DIR . 'classes/seo/seo-helper.php';
        require_once WPAICG_PLUGIN_DIR . 'includes/class-aipkit-upload-utils.php';
        // --- ADDED: Load new TOC Generator class ---
        $toc_generator_path = WPAICG_PLUGIN_DIR . 'includes/utils/class-aipkit-toc-generator.php';
        if (file_exists($toc_generator_path)) {
            require_once $toc_generator_path;
        }
        // --- END ADDED ---
        // --- ADDED: Load new Identifier Utils class ---
        $identifier_utils_path = WPAICG_PLUGIN_DIR . 'includes/utils/class-aipkit-identifier-utils.php';
        if (file_exists($identifier_utils_path)) {
            require_once $identifier_utils_path;
        }
        // --- END ADDED ---
        // --- ADDED: Load shared Admin Header Action Buttons util ---
        $header_buttons_util_path = WPAICG_PLUGIN_DIR . 'includes/utils/class-aipkit-admin-header-action-buttons.php';
        if (file_exists($header_buttons_util_path)) {
            require_once $header_buttons_util_path;
        }
        // --- END ADDED ---
        // --- ADDED: Load new CORS Manager class ---
        $cors_manager_path = WPAICG_PLUGIN_DIR . 'includes/utils/class-aipkit-cors-manager.php';
        if (file_exists($cors_manager_path)) {
            require_once $cors_manager_path;
            // Initialize the CORS manager
            \WPAICG\Utils\AIPKit_CORS_Manager::init();
        }
        // --- END ADDED ---


        // --- Load the new specialized loader class files ---
        $loaders_path = WPAICG_PLUGIN_DIR . 'includes/dependency-loaders/';
        require_once $loaders_path . 'class-admin-asset-handlers-loader.php';
        require_once $loaders_path . 'class-provider-dependencies-loader.php';
        require_once $loaders_path . 'class-core-services-loader.php';
        require_once $loaders_path . 'class-dashboard-base-classes-loader.php';
        require_once $loaders_path . 'class-base-ajax-handlers-loader.php';
        require_once $loaders_path . 'class-chat-dependencies-loader.php';
        require_once $loaders_path . 'class-speech-dependencies-loader.php';
        require_once $loaders_path . 'class-stt-dependencies-loader.php';
        require_once $loaders_path . 'class-rest-dependencies-loader.php';
        require_once $loaders_path . 'class-image-dependencies-loader.php';
        require_once $loaders_path . 'class-vector-store-dependencies-loader.php';
        require_once $loaders_path . 'class-vector-store-ajax-handlers-loader.php';
        require_once $loaders_path . 'class-vector-post-processor-classes-loader.php';
        require_once $loaders_path . 'class-content-writer-dependencies-loader.php';
        require_once $loaders_path . 'class-addon-dependencies-loader.php';
        require_once $loaders_path . 'class-post-enhancer-core-loader.php';
        require_once $loaders_path . 'class-woocommerce-writer-loader.php';
        require_once $loaders_path . 'class-automated-task-dependencies-loader.php';
        require_once $loaders_path . 'class-automated-task-ajax-handlers-loader.php';
        require_once $loaders_path . 'class-automated-task-cron-helpers-loader.php';
        require_once $loaders_path . 'class-hook-registrars-loader.php';
        require_once $loaders_path . 'class-ai-forms-dependencies-loader.php';
        require_once $loaders_path . 'class-core-moderation-dependencies-loader.php';
        // --- ADDED: require_once for Migration AJAX Handlers Loader ---
        require_once $loaders_path . 'class-migration-ajax-handlers-loader.php';
        // --- END ADDED ---
        // --- END Load the new specialized loader class files ---

        // Call specialized loaders
        Admin_Asset_Handlers_Loader::load();
        Provider_Dependencies_Loader::load();
        Core_Services_Loader::load();
        Dashboard_Base_Classes_Loader::load();
        Base_Ajax_Handlers_Loader::load();
        Chat_Dependencies_Loader::load();
        Speech_Dependencies_Loader::load();
        Stt_Dependencies_Loader::load();
        Rest_Dependencies_Loader::load();
        Image_Dependencies_Loader::load();
        Vector_Store_Dependencies_Loader::load();
        Vector_Store_Ajax_Handlers_Loader::load();
        Vector_Post_Processor_Classes_Loader::load();
        Content_Writer_Dependencies_Loader::load();
        Addon_Dependencies_Loader::load();
        Post_Enhancer_Core_Loader::load();
        Woocommerce_Writer_Loader::load();
        Automated_Task_Dependencies_Loader::load();
        Automated_Task_Ajax_Handlers_Loader::load();
        Automated_Task_Cron_Helpers_Loader::load();
        Hook_Registrars_Loader::load();
        AI_Forms_Dependencies_Loader::load();
        Core_Moderation_Dependencies_Loader::load();
        // --- ADDED: Call to Migration AJAX Handlers Loader ---
        Migration_Ajax_Handlers_Loader::load();
        // --- END ADDED ---
    }
}