<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/assets/class-aipkit-image-generator-assets.php
// Status: MODIFIED

namespace WPAICG\Admin\Assets;

use WPAICG\AIPKit_Providers;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Handles enqueueing assets for the AIPKit Image Generator module.
 */
class ImageGeneratorAssets
{
    private $version;
    private $is_admin_main_js_enqueued = false;
    private $is_public_main_js_enqueued = false;
    private $is_admin_image_generator_css_enqueued = false;
    private $is_public_image_generator_css_enqueued = false;

    public function __construct()
    {
        $this->version = defined('WPAICG_VERSION') ? WPAICG_VERSION : '1.0.0';
    }

    public function register_hooks()
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_image_generator_assets']);
    }

    public function enqueue_image_generator_assets($hook_suffix)
    {
        $screen = get_current_screen();
        $is_aipkit_page = $screen && strpos($screen->id, 'page_wpaicg') !== false;

        if (!$is_aipkit_page) {
            return;
        }

        $this->enqueue_styles();
        $this->enqueue_scripts();

        // Ensure core dashboard data is localized if admin-main.js was enqueued
        if ($this->is_admin_main_js_enqueued && class_exists(DashboardAssets::class) && method_exists(DashboardAssets::class, 'localize_core_data')) {
            DashboardAssets::localize_core_data($this->version);
        }

        $this->localize_data();
    }

    private function enqueue_styles()
    {
        $dist_css_url = WPAICG_PLUGIN_URL . 'dist/css/';
        $admin_main_css_handle = 'aipkit-admin-main-css';

        $admin_img_gen_css_handle = 'aipkit-admin-image-generator-css';
        if (!wp_style_is($admin_img_gen_css_handle, 'registered')) {
            wp_register_style(
                $admin_img_gen_css_handle,
                $dist_css_url . 'admin-image-generator.bundle.css',
                [$admin_main_css_handle],
                $this->version
            );
        }
        if (!$this->is_admin_image_generator_css_enqueued && !wp_style_is($admin_img_gen_css_handle, 'enqueued')) {
            wp_enqueue_style($admin_img_gen_css_handle);
            $this->is_admin_image_generator_css_enqueued = true;
        }

        $public_img_gen_css_handle = 'aipkit-public-image-generator-css';
        if (!wp_style_is($public_img_gen_css_handle, 'registered')) {
            wp_register_style(
                $public_img_gen_css_handle,
                $dist_css_url . 'public-image-generator.bundle.css',
                [],
                $this->version
            );
        }
        if (!$this->is_public_image_generator_css_enqueued && !wp_style_is($public_img_gen_css_handle, 'enqueued')) {
            wp_enqueue_style($public_img_gen_css_handle);
            $this->is_public_image_generator_css_enqueued = true;
        }
    }

    private function enqueue_scripts()
    {
        $dist_js_url = WPAICG_PLUGIN_URL . 'dist/js/';
        $admin_main_js_handle = 'aipkit-admin-main';
        $public_main_js_handle = 'aipkit-public-main';

        if (!wp_script_is($admin_main_js_handle, 'registered')) {
            wp_register_script(
                $admin_main_js_handle,
                $dist_js_url . 'admin-main.bundle.js',
                ['wp-i18n', 'aipkit_markdown-it'],
                $this->version,
                true
            );
        }
        if (!$this->is_admin_main_js_enqueued && !wp_script_is($admin_main_js_handle, 'enqueued')) {
            wp_enqueue_script($admin_main_js_handle);
            wp_set_script_translations($admin_main_js_handle, 'gpt3-ai-content-generator', WPAICG_PLUGIN_DIR . 'languages');
            $this->is_admin_main_js_enqueued = true;
        }

        if (!wp_script_is($public_main_js_handle, 'registered')) {
            wp_register_script(
                $public_main_js_handle,
                $dist_js_url . 'public-main.bundle.js',
                ['wp-i18n', 'aipkit_markdown-it'],
                $this->version,
                true
            );
        }
        if (!$this->is_public_main_js_enqueued && !wp_script_is($public_main_js_handle, 'enqueued')) {
            wp_enqueue_script($public_main_js_handle);
            wp_set_script_translations($public_main_js_handle, 'gpt3-ai-content-generator', WPAICG_PLUGIN_DIR . 'languages');
            $this->is_public_main_js_enqueued = true;
        }
    }

    private function localize_data()
    {
        $public_main_js_handle = 'aipkit-public-main';

        if (!wp_script_is($public_main_js_handle, 'enqueued')) {
            if (!wp_script_is($public_main_js_handle, 'registered')) {
                $dist_js_url = WPAICG_PLUGIN_URL . 'dist/js/';
                wp_register_script($public_main_js_handle, $dist_js_url . 'public-main.bundle.js', ['wp-i18n', 'aipkit_markdown-it'], $this->version, true);
            }
        }

        $script_data = wp_scripts()->get_data($public_main_js_handle, 'data');
        if (!empty($script_data) && strpos($script_data, 'aipkit_image_generator_config_public') !== false) {
            return;
        }

        wp_localize_script($public_main_js_handle, 'aipkit_image_generator_config_public', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aipkit_image_generator_nonce'),
            'text' => [
                'generating' => __('Generating...', 'gpt3-ai-content-generator'),
                'error'      => __('Error generating image.', 'gpt3-ai-content-generator'),
                'generateButton' => __('Generate Image', 'gpt3-ai-content-generator'),
                'noPrompt' => __('Please enter a prompt.', 'gpt3-ai-content-generator'),
                'initialPlaceholder' => __('Generated images will appear here.', 'gpt3-ai-content-generator'),
                'viewFullImage' => __('Click to view full image', 'gpt3-ai-content-generator'),
            ],
             'openai_models' => [
                ['id' => 'gpt-image-1', 'name' => 'GPT Image 1'],
                ['id' => 'dall-e-3', 'name' => 'DALL-E 3'],
                ['id' => 'dall-e-2', 'name' => 'DALL-E 2'],
             ],
             'azure_models' => class_exists('\\WPAICG\\AIPKit_Providers') ? AIPKit_Providers::get_azure_image_models() : [], 'google_models' => [
                'image' => (class_exists('\\WPAICG\\AIPKit_Providers') ? AIPKit_Providers::get_google_image_models() : []),
                'video' => (class_exists('\\WPAICG\\AIPKit_Providers') ? AIPKit_Providers::get_google_video_models() : []),
             ],
             'replicate_models' => AIPKit_Providers::get_replicate_models()
        ]);
    }
}
