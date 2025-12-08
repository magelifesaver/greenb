<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/shortcodes/token-usage/render/render_shortcode.php
// Status: NEW FILE

namespace WPAICG\Shortcodes\TokenUsage\Render;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the render_shortcode method.
 *
 * @param \WPAICG\Shortcodes\AIPKit_Token_Usage_Shortcode $facade The facade instance.
 * @param array $atts Shortcode attributes.
 * @return string HTML output.
 */
function render_shortcode_logic(\WPAICG\Shortcodes\AIPKit_Token_Usage_Shortcode $facade, $atts = []): string
{
    // Check if user is logged in
    if (!is_user_logged_in()) {
        return '<p class="aipkit-login-prompt">' . esc_html__('Please log in to view your token usage.', 'gpt3-ai-content-generator') . '</p>';
    }

    // Role Manager Check
    if (class_exists('\\WPAICG\\AIPKit_Role_Manager')) {
        if (!\WPAICG\AIPKit_Role_Manager::user_can_access_module('token_usage_shortcode')) {
            return '<p class="aipkit-permission-denied">' . esc_html__('You do not have permission to view token usage.', 'gpt3-ai-content-generator') . '</p>';
        }
    }

    $default_atts = [
        'chatbot'        => 'true',
        'aiforms'        => 'true',
        'imagegenerator' => 'true',
    ];
    $atts = shortcode_atts($default_atts, $atts, 'aipkit_token_usage');

    $show_chatbot = filter_var($atts['chatbot'], FILTER_VALIDATE_BOOLEAN);
    $show_aiforms = filter_var($atts['aiforms'], FILTER_VALIDATE_BOOLEAN);
    $show_imagegenerator = filter_var($atts['imagegenerator'], FILTER_VALIDATE_BOOLEAN);

    $user_id = get_current_user_id();

    // The facade's private methods are delegated, so we can call them via the facade instance.
    $usage_data = \WPAICG\Shortcodes\TokenUsage\Data\get_user_token_usage_data_logic($facade, $user_id);
    $usage_data = apply_filters('aipkit_token_usage_data', $usage_data, $user_id);

    return \WPAICG\Shortcodes\TokenUsage\Render\render_dashboard_logic($facade, $usage_data, $show_chatbot, $show_aiforms, $show_imagegenerator);
}
