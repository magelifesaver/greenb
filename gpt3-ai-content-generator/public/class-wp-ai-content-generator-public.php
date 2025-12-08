<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/public/class-wp-ai-content-generator-public.php
// Status: MODIFIED

namespace WPAICG\Public;

use WPAICG\Chat\Frontend\Assets as ChatAssetsOrchestrator;
use WPAICG\aipkit_dashboard;

if (! defined('ABSPATH')) {
    exit;
}

/**
* The public-facing functionality of the plugin.
* REVISED: Enqueues bundled assets instead of individual files.
* Localization for AI Forms now attached to public-main bundle.
*/
class WP_AI_Content_Generator_Public
{
    private $plugin_name;
    private $version;
    private $is_public_main_js_enqueued = false;
    // --- MODIFIED: Added CSS enqueue trackers ---
    private $is_public_ai_forms_css_enqueued = false;
    // --- END MODIFICATION ---


    /**
    * Register public-specific hooks.
    */
    public function init_hooks()
    {
        $this->plugin_name = 'gpt3-ai-content-generator';
        $this->version = defined('WPAICG_VERSION') ? WPAICG_VERSION : '1.9.15';

        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']); // Combined for styles & scripts
        $this->initialize_chat_assets();
    }

    /**
    * Initialize Chat Frontend Assets handler if chat module is enabled.
    */
    private function initialize_chat_assets()
    {
        $dashboard_path = WPAICG_PLUGIN_DIR . 'classes/dashboard/class-aipkit_dashboard.php';
        if (file_exists($dashboard_path)) {
            if (!class_exists('\\WPAICG\\aipkit_dashboard')) {
                require_once $dashboard_path;
            }
            if (class_exists('\\WPAICG\\aipkit_dashboard')) {
                $modules = aipkit_dashboard::get_module_settings();
                $is_chat_enabled = !empty($modules['chat_bot']);

                if ($is_chat_enabled) {
                    $chat_assets_orchestrator_path = WPAICG_PLUGIN_DIR . 'classes/chat/frontend/chat_assets.php';
                    if (file_exists($chat_assets_orchestrator_path)) {
                        if (!class_exists(ChatAssetsOrchestrator::class)) {
                            require_once $chat_assets_orchestrator_path;
                        }
                        if (class_exists(ChatAssetsOrchestrator::class)) {
                            $chat_assets = new ChatAssetsOrchestrator();
                            $chat_assets->register_hooks();
                        }
                    }
                }
            }
        }
    }

    /**
    * Register and enqueue stylesheets and scripts for the public-facing side.
    */
    public function enqueue_assets()
    {
        // --- Register Bundled CSS ---
        $dist_css_url = WPAICG_PLUGIN_URL . 'dist/css/';
        $public_ai_forms_css_handle = 'aipkit-public-ai-forms'; // Keep existing handle for AI Forms
        $public_image_generator_css_handle = 'aipkit-public-image-generator'; // For Image Generator shortcode

        // AI Forms CSS
        if (!wp_style_is($public_ai_forms_css_handle, 'registered')) {
            wp_register_style(
                $public_ai_forms_css_handle,
                $dist_css_url . 'public-ai-forms.bundle.css',
                [], // AI Forms CSS is self-contained or depends on theme's base styles
                $this->version
            );
        }
        // Image Generator CSS
        if (!wp_style_is($public_image_generator_css_handle, 'registered')) {
            wp_register_style(
                $public_image_generator_css_handle,
                $dist_css_url . 'public-image-generator.bundle.css',
                [],
                $this->version
            );
        }


        // --- Register Bundled JS ---
        $dist_js_url = WPAICG_PLUGIN_URL . 'dist/js/';
        $public_main_js_handle = 'aipkit-public-main';

        if (!wp_script_is($public_main_js_handle, 'registered')) {
            wp_register_script(
                $public_main_js_handle,
                $dist_js_url . 'public-main.bundle.js',
                ['wp-i18n', 'aipkit_markdown-it'], // Markdown-it is a vendor script
                $this->version,
                true
            );
        }

        // --- Conditional Enqueueing based on shortcode presence ---
        global $post;
        $content = is_a($post, 'WP_Post') ? $post->post_content : '';
        $should_enqueue_public_main_js = false;

        // AI Forms
        if (has_shortcode($content, 'aipkit_ai_form')) {
            // --- MODIFIED: Enqueue specific CSS if not already done ---
            if (!$this->is_public_ai_forms_css_enqueued && !wp_style_is($public_ai_forms_css_handle, 'enqueued')) {
                wp_enqueue_style($public_ai_forms_css_handle);
                $this->is_public_ai_forms_css_enqueued = true;
            }
            // --- END MODIFICATION ---
            $should_enqueue_public_main_js = true;
            // Localize for AI Forms (attached to the main public bundle)
            static $ai_forms_localized = false;
            if (!$ai_forms_localized) {
                // ADDED: Get settings and add to localization data
                $frontend_display_settings = [];
                if (class_exists('\\WPAICG\\AIForms\\Admin\\AIPKit_AI_Form_Settings_Ajax_Handler')) {
                    $all_settings = \WPAICG\AIForms\Admin\AIPKit_AI_Form_Settings_Ajax_Handler::get_settings();
                    $frontend_display_settings = $all_settings['frontend_display'] ?? [];
                }

                wp_localize_script($public_main_js_handle, 'aipkit_ai_forms_public_config', [
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'ajaxNonce' => wp_create_nonce('aipkit_frontend_chat_nonce'), // Re-using chat nonce, consider specific AI Forms nonce
                    'is_user_logged_in' => is_user_logged_in(),
                    'save_as_post_nonce' => wp_create_nonce('aipkit_ai_form_save_as_post_nonce'),
                    'allowed_providers' => $frontend_display_settings['allowed_providers'] ?? '', // NEW
                    'allowed_models' => $frontend_display_settings['allowed_models'] ?? '', // NEW
                    'text' => [
                        'processing' => __('Processing...', 'gpt3-ai-content-generator'),
                        'error' => __('An error occurred.', 'gpt3-ai-content-generator'),
                        'saveAsPost' => __('Save', 'gpt3-ai-content-generator'),
                    ]
                ]);

                // ADDED: Localize model data
                if (class_exists('\\WPAICG\\AIPKit_Providers')) {
                    $all_models = [
                        'openai'     => \WPAICG\AIPKit_Providers::get_openai_models(),
                        'openrouter' => \WPAICG\AIPKit_Providers::get_openrouter_models(),
                        'google'     => \WPAICG\AIPKit_Providers::get_google_models(),
                        'azure'      => \WPAICG\AIPKit_Providers::get_azure_deployments(),
                        'deepseek'   => \WPAICG\AIPKit_Providers::get_deepseek_models(),
                    ];
                    if (
                        class_exists('\\WPAICG\\aipkit_dashboard') &&
                        \WPAICG\aipkit_dashboard::is_pro_plan() &&
                        \WPAICG\aipkit_dashboard::is_addon_active('ollama')
                    ) {
                        $all_models['ollama'] = \WPAICG\AIPKit_Providers::get_ollama_models();
                    }
                    wp_localize_script($public_main_js_handle, 'aipkit_ai_forms_models', $all_models);
                }
                $ai_forms_localized = true;
            }
        }

        // Image Generator (Shortcode handled by AIPKit_Shortcodes_Manager)
        // This class only handles assets for its *own* shortcodes or general public assets.
        // The Shortcodes_Manager will handle enqueuing for aipkit_image_generator.

        // Enqueue public-main.bundle.js if any relevant shortcode is present OR if ChatAssetsOrchestrator signals need
        $chat_assets_needed = class_exists(ChatAssetsOrchestrator::class) && (ChatAssetsOrchestrator::$shortcode_rendered || ChatAssetsOrchestrator::$site_wide_injection_needed);

        if (($should_enqueue_public_main_js || $chat_assets_needed) && !$this->is_public_main_js_enqueued) {
            if (!wp_script_is($public_main_js_handle, 'enqueued')) {
                wp_enqueue_script($public_main_js_handle);
                wp_set_script_translations($public_main_js_handle, 'gpt3-ai-content-generator', WPAICG_PLUGIN_DIR . 'languages');
            }
            $this->is_public_main_js_enqueued = true;
        }
    }
}
