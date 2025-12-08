<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/includes/hook-registrars/class-admin-asset-hooks-registrar.php
// Status: MODIFIED

namespace WPAICG\Includes\HookRegistrars;

use WPAICG\Admin\Assets\DashboardAssets;
use WPAICG\Admin\Assets\SettingsAssets;
use WPAICG\Admin\Assets\UserCreditsAssets;
use WPAICG\Admin\Assets\ChatAdminAssets;
use WPAICG\Admin\Assets\RoleManagerAssets;
use WPAICG\Admin\Assets\PostEnhancerAssets;
use WPAICG\Admin\Assets\ImageGeneratorAssets;
use WPAICG\Admin\Assets\AITrainingAssets;
use WPAICG\Admin\Assets\AIPKit_Vector_Post_Processor_Assets;
use WPAICG\Vector\PostProcessor\AIPKit_Vector_Post_Processor_List_Screen;
use WPAICG\Admin\Assets\AIPKit_Autogpt_Assets;
use WPAICG\Admin\Assets\AIPKit_Content_Writer_Assets;
// --- ADDED: AI Forms Assets ---
use WPAICG\Admin\Assets\AIPKit_AI_Forms_Assets;
// --- END ADDED ---
// --- ADDED: WooCommerce Writer Assets ---
use WPAICG\Admin\Assets\AIPKit_Woocommerce_Writer_Assets;

// --- END ADDED ---


if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Registers hooks for admin asset handlers.
 */
class Admin_Asset_Hooks_Registrar
{
    public static function register()
    {
        $dashboard_assets    = new DashboardAssets();
        $settings_assets     = new SettingsAssets();
        $user_credits_assets = new UserCreditsAssets();
        $chat_admin_assets   = new ChatAdminAssets();
        $role_manager_assets = new RoleManagerAssets();
        $post_enhancer_assets = new PostEnhancerAssets();
        $image_generator_assets = new ImageGeneratorAssets();
        $content_writer_assets = new AIPKit_Content_Writer_Assets();
        $ai_training_assets = new AITrainingAssets();
        $vector_post_processor_assets = new AIPKit_Vector_Post_Processor_Assets();
        $vector_post_processor_list_screen = new AIPKit_Vector_Post_Processor_List_Screen();
        $autogpt_assets = null;
        if (class_exists(AIPKit_Autogpt_Assets::class)) {
            $autogpt_assets = new AIPKit_Autogpt_Assets();
        }
        // --- ADDED: Instantiate AI Forms Assets ---
        $ai_forms_assets = null;
        $ai_forms_assets_path = WPAICG_PLUGIN_DIR . 'admin/assets/class-aipkit-ai-forms-assets.php';
        if (file_exists($ai_forms_assets_path)) {
            if (!class_exists(AIPKit_AI_Forms_Assets::class)) { // Ensure it's not already loaded
                require_once $ai_forms_assets_path;
            }
            if (class_exists(AIPKit_AI_Forms_Assets::class)) {
                $ai_forms_assets = new AIPKit_AI_Forms_Assets();
            }
        }
        // --- END ADDED ---
        // --- ADDED: Instantiate WooCommerce Writer Assets ---
        $woocommerce_writer_assets = null;
        $woo_writer_assets_path = WPAICG_PLUGIN_DIR . 'admin/assets/class-aipkit-woocommerce-writer-assets.php';
        if (file_exists($woo_writer_assets_path)) { // Check if file exists before trying to load
            if (!class_exists(AIPKit_Woocommerce_Writer_Assets::class)) {
                require_once $woo_writer_assets_path;
            }
            if (class_exists(AIPKit_Woocommerce_Writer_Assets::class)) {
                $woocommerce_writer_assets = new AIPKit_Woocommerce_Writer_Assets();
            }
        }
        // --- END ADDED ---


        $dashboard_assets->register_hooks();
        $settings_assets->register_hooks();
        $user_credits_assets->register_hooks();
        $chat_admin_assets->register_hooks();
        $role_manager_assets->register_hooks();
        $post_enhancer_assets->register_hooks();
        $image_generator_assets->register_hooks();
        $content_writer_assets->register_hooks();
        $ai_training_assets->register_hooks();
        $vector_post_processor_assets->register_hooks();
        $vector_post_processor_list_screen->register_hooks();
        if ($autogpt_assets) {
            $autogpt_assets->register_hooks();
        }
        // --- ADDED: Register AI Forms Assets Hooks ---
        if ($ai_forms_assets) {
            $ai_forms_assets->register_hooks();
        }
        // --- END ADDED ---
        // --- ADDED: Register WooCommerce Writer Assets Hooks ---
        if ($woocommerce_writer_assets) {
            $woocommerce_writer_assets->register_hooks();
        }
        // --- END ADDED ---
    }
}
