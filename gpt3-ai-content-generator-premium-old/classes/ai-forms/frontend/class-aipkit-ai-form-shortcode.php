<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/ai-forms/frontend/class-aipkit-ai-form-shortcode.php
// Status: MODIFIED

namespace WPAICG\AIForms\Frontend;

use WPAICG\AIForms\Storage\AIPKit_AI_Form_Storage;
// --- NEW: Import settings handler to get custom CSS ---
use WPAICG\AIForms\Admin\AIPKit_AI_Form_Settings_Ajax_Handler;
// --- END NEW ---
use WP_Error;

// Load the new modular logic files
require_once __DIR__ . '/shortcode/validator.php';
require_once __DIR__ . '/shortcode/data-provider.php';
require_once __DIR__ . '/shortcode/renderer.php';

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Orchestrates rendering the [aipkit_ai_form] shortcode.
 * Delegates logic to validator, data-provider, and renderer functions.
 */
class AIPKit_AI_Form_Shortcode
{
    private static $rendered_form_ids = [];
    private $form_storage;

    public function __construct()
    {
        if (class_exists(AIPKit_AI_Form_Storage::class)) {
            $this->form_storage = new AIPKit_AI_Form_Storage();
        } else {
            $this->form_storage = null;
        }
    }

    /**
     * Render the shortcode output.
     *
     * @param array $atts Shortcode attributes, expecting 'id' and optional 'theme', 'save_button'.
     * @return string HTML output for the AI form or an error message.
     */
    public function render_shortcode($atts)
    {
        if (!$this->form_storage) {
            return $this->handle_error(new WP_Error('storage_missing', '[AIPKit AI Form Error: Storage component missing.]'));
        }

        // Parse attributes with defaults
        $default_atts = [
            'id'    => 0,
            'theme' => 'light',
            'show_provider' => 'false',
            'show_model'    => 'false',
            'save_button'   => 'false',
            'pdf_download'  => 'false',
            'copy_button'   => 'false',
        ];
        $atts = shortcode_atts($default_atts, $atts, 'aipkit_ai_form');

        // Validate theme attribute
        $valid_themes = ['light', 'dark', 'custom'];
        $theme = in_array($atts['theme'], $valid_themes, true) ? $atts['theme'] : 'light';

        // Parse boolean flags for new attributes
        $show_provider = filter_var($atts['show_provider'], FILTER_VALIDATE_BOOLEAN);
        $show_model = filter_var($atts['show_model'], FILTER_VALIDATE_BOOLEAN);
        $show_save_button = filter_var($atts['save_button'], FILTER_VALIDATE_BOOLEAN);
        $show_pdf_download = filter_var($atts['pdf_download'], FILTER_VALIDATE_BOOLEAN);
        $show_copy_button = filter_var($atts['copy_button'], FILTER_VALIDATE_BOOLEAN);

        // 1. Validate ID Attribute
        $validation_result = Shortcode\validate_atts_logic($atts, self::$rendered_form_ids);
        if (is_wp_error($validation_result)) {
            return $this->handle_error($validation_result);
        }
        $form_id = $validation_result;

        // 2. Get Form Data
        $form_data = Shortcode\get_form_data_logic($this->form_storage, $form_id);
        if (is_wp_error($form_data)) {
            return $this->handle_error($form_data);
        }

        // --- NEW: Fetch frontend display settings ---
        $frontend_display_settings = [];
        if (class_exists(AIPKit_AI_Form_Settings_Ajax_Handler::class)) {
            $all_settings = AIPKit_AI_Form_Settings_Ajax_Handler::get_settings();
            $frontend_display_settings = $all_settings['frontend_display'] ?? [];
        }
        $allowed_providers_str = $frontend_display_settings['allowed_providers'] ?? '';
        // --- END NEW ---

        // 3. Localize Model Data (once per page)
        static $models_localized = false;
        if (!$models_localized) {
            // We enqueue public-main.bundle.js for any AI Form, so we can localize against it.
            // The WP_AI_Content_Generator_Public class handles enqueueing logic.
            $public_main_js_handle = 'aipkit-public-main';
        if (wp_script_is($public_main_js_handle, 'registered') && class_exists('\\WPAICG\\AIPKit_Providers')) {
                $all_models = [
            'openai'     => \WPAICG\AIPKit_Providers::get_openai_models(),
            'openrouter' => \WPAICG\AIPKit_Providers::get_openrouter_models(),
            'google'     => \WPAICG\AIPKit_Providers::get_google_models(),
            'azure'      => \WPAICG\AIPKit_Providers::get_azure_deployments(),
            'deepseek'   => \WPAICG\AIPKit_Providers::get_deepseek_models(),
                ];
                // Add Ollama models only for Pro plan and when addon is active
                if (
                    class_exists('\\WPAICG\\aipkit_dashboard') &&
                    \WPAICG\aipkit_dashboard::is_pro_plan() &&
                    \WPAICG\aipkit_dashboard::is_addon_active('ollama')
                ) {
            $all_models['ollama'] = \WPAICG\AIPKit_Providers::get_ollama_models();
                }
                wp_localize_script($public_main_js_handle, 'aipkit_ai_forms_models', $all_models);
                $models_localized = true;
            }
        }

        // 4. Conditionally enqueue jsPDF
        if ($show_pdf_download && class_exists('\\WPAICG\\aipkit_dashboard') && \WPAICG\aipkit_dashboard::is_pro_plan() && \WPAICG\aipkit_dashboard::is_addon_active('pdf_download')) {
            if (wp_script_is('aipkit_jspdf', 'registered') && !wp_script_is('aipkit_jspdf', 'enqueued')) {
                wp_enqueue_script('aipkit_jspdf');
            }
        }

        // 5. Mark as rendered
        self::$rendered_form_ids[$form_id] = true;

        // --- NEW: Get Custom CSS ---
        $custom_css = '';
        if ($theme === 'custom' && class_exists(AIPKit_AI_Form_Settings_Ajax_Handler::class)) {
            $settings = AIPKit_AI_Form_Settings_Ajax_Handler::get_settings();
            $custom_css = $settings['custom_theme']['custom_css'] ?? '';
        }
        // --- END NEW ---

        // 6. Prepare data for the renderer
        $unique_form_html_id = 'aipkit-ai-form-' . esc_attr($form_id);
        $ajax_nonce = wp_create_nonce('aipkit_process_ai_form_' . $form_id);

        // 7. Render HTML, passing the new theme and display flags
        return Shortcode\render_form_html_logic($form_data, $unique_form_html_id, $ajax_nonce, $theme, $show_provider, $show_model, $show_save_button, $show_pdf_download, $show_copy_button, $custom_css, $allowed_providers_str);
    }

    /**
     * Handles rendering errors, showing messages to admins only.
     *
     * @param WP_Error $error The error object.
     * @return string HTML error message or empty string.
     */
    private function handle_error(WP_Error $error): string
    {
        if (current_user_can('manage_options')) {
            $message = $error->get_error_message();
            $code = $error->get_error_code();
            return '<p style="color:' . ($code === 'already_rendered' ? 'orange' : 'red') . '; font-style: italic; margin: 1em 0;">' . esc_html($message) . '</p>';
        }
        return ''; // Silently fail for regular users
    }
}
