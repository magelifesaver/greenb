<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/ai-forms/class-aipkit-ai-form-initializer.php
// Status: MODIFIED

namespace WPAICG\AIForms;

// Dependencies for AI Forms module
use WPAICG\AIForms\Admin\AIPKit_AI_Form_Admin_Setup;
use WPAICG\AIForms\Frontend\AIPKit_AI_Form_Shortcode;
use WPAICG\AIForms\Core\AIPKit_AI_Form_Processor;
// --- ADDED: AJAX Handler for admin operations ---
use WPAICG\AIForms\Admin\AIPKit_AI_Form_Ajax_Handler;
use WPAICG\AIForms\Admin\AIPKit_AI_Form_Settings_Ajax_Handler;
// --- ADDED: Defaults Handler ---
use WPAICG\AIForms\Admin\AIPKit_AI_Form_Defaults;

// --- END ADDED ---

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Initializes the AIPKit AI Forms module by loading dependencies and registering hooks.
 */
class AIPKit_AI_Form_Initializer
{
    /**
     * Loads dependencies specific to the AI Forms module.
     * This method is called by the central AIPKit_Dependency_Loader.
     */
    public static function load_dependencies()
    {
        $base_path = WPAICG_PLUGIN_DIR . 'classes/ai-forms/';
        $paths = [
            'admin/class-aipkit-ai-form-admin-setup.php',
            'admin/class-aipkit-ai-form-ajax-handler.php', // ADDED: Ensure AJAX handler is loaded
            'admin/class-aipkit-ai-form-settings-ajax-handler.php', // ADDED: Ensure settings AJAX handler is loaded
            'admin/class-aipkit-ai-form-defaults.php', // ADDED: Ensure defaults handler is loaded
            'frontend/class-aipkit-ai-form-shortcode.php',
            'core/class-aipkit-ai-form-processor.php',
            'storage/class-aipkit-ai-form-storage.php',
            // Add other AI Forms specific class files here as they are created
        ];
        foreach ($paths as $file) {
            $full_path = $base_path . $file;
            if (file_exists($full_path)) {
                require_once $full_path;
            }
        }
    }

    /**
     * Registers WordPress hooks for the AI Forms module.
     * Called by the AIPKit_Hook_Manager via Module_Initializer_Hooks_Registrar.
     */
    public static function register_hooks()
    {
        // Dependencies are loaded by the central AIPKit_Dependency_Loader.
        // This method should only register hooks.

        // Instantiate classes needed for hooks
        if (class_exists(AIPKit_AI_Form_Admin_Setup::class)) {
            $admin_setup = new AIPKit_AI_Form_Admin_Setup();
            add_action('init', [$admin_setup, 'register_cpt']);
        }

        // The default form creation is now called from the main plugin activator to ensure it runs
        // on new installs, reactivations, and version updates.

        if (class_exists(AIPKit_AI_Form_Shortcode::class)) {
            $shortcode_handler = new AIPKit_AI_Form_Shortcode();
            add_shortcode('aipkit_ai_form', [$shortcode_handler, 'render_shortcode']);
        }

        // AJAX action hooks for form submissions (frontend)
        if (class_exists(AIPKit_AI_Form_Processor::class)) {
            $form_processor = new AIPKit_AI_Form_Processor();
            // Note: The aipkit_process_ai_form hook is removed as it's unused.
            add_action('wp_ajax_aipkit_ai_form_upload_and_parse_file', [$form_processor, 'ajax_upload_and_parse_file']);
            add_action('wp_ajax_nopriv_aipkit_ai_form_upload_and_parse_file', [$form_processor, 'ajax_upload_and_parse_file']);
            add_action('wp_ajax_aipkit_ai_form_save_as_post', [$form_processor, 'ajax_save_as_post']);
        }

        // --- ADDED: AJAX action hooks for form management (admin) ---
        if (class_exists(AIPKit_AI_Form_Ajax_Handler::class)) {
            $form_ajax_handler = new AIPKit_AI_Form_Ajax_Handler();
            // The AJAX handler will register its own hooks, so we just need to ensure it's instantiated
            // OR if it has a specific method to call for hook registration, call it here.
            // For now, assuming it registers its own hooks upon instantiation or via a static init method.
            // Let's assume it has a register_ajax_hooks() method as per the guide.
            if (method_exists($form_ajax_handler, 'register_ajax_hooks')) {
                $form_ajax_handler->register_ajax_hooks();
            }
        }
    }
}
