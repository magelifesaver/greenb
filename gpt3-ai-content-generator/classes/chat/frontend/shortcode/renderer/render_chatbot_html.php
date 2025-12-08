<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/chat/frontend/shortcode/renderer/render_chatbot_html.php

namespace WPAICG\Chat\Frontend\Shortcode\RendererMethods;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for rendering the main chatbot HTML structure.
 *
 * @param \WPAICG\Chat\Frontend\Shortcode\Renderer $rendererInstance The instance of the Renderer class.
 * @param int $bot_id
 * @param array $settings Bot Settings.
 * @param array $feature_flags Determined feature flags.
 * @param array $frontend_config Prepared frontend config data.
 * @return string Rendered HTML.
 */
function render_chatbot_html_logic(\WPAICG\Chat\Frontend\Shortcode\Renderer $rendererInstance, int $bot_id, array $settings, array $feature_flags, array $frontend_config): string {
    ob_start();

    $json_encoded_data = wp_json_encode($frontend_config, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
    $theme = $frontend_config['theme'];
    $voice_input_enabled_ui = $feature_flags['enable_voice_input_ui'] ?? false;
    $allow_openai_web_search_tool = $feature_flags['allowWebSearchTool'] ?? false;
    $allow_google_search_grounding = $feature_flags['allowGoogleSearchGrounding'] ?? false;

    if ($feature_flags['popup_enabled']) {
        // Call the render_popup_mode_html logic via the instance
        $rendererInstance->render_popup_mode_html_internal($bot_id, $theme, $json_encoded_data, $feature_flags, $frontend_config, $voice_input_enabled_ui, $allow_openai_web_search_tool, $allow_google_search_grounding);
    } else {
        // Call the render_inline_mode_html logic via the instance
        $rendererInstance->render_inline_mode_html_internal($bot_id, $theme, $json_encoded_data, $feature_flags, $frontend_config, $voice_input_enabled_ui, $allow_openai_web_search_tool, $allow_google_search_grounding);
    }

    return ob_get_clean();
}