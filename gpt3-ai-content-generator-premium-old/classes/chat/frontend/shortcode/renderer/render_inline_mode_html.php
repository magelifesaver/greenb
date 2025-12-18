<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/chat/frontend/shortcode/renderer/render_inline_mode_html.php
// Status: MODIFIED

namespace WPAICG\Chat\Frontend\Shortcode\RendererMethods;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for rendering the Inline mode HTML.
 * UPDATED: Add data-custom-theme attribute and aipkit-theme-custom class.
 *
 * @param \WPAICG\Chat\Frontend\Shortcode\Renderer $rendererInstance The instance of the Renderer class.
 * @param int $bot_id
 * @param string $theme
 * @param string $json_encoded_data
 * @param array $feature_flags
 * @param array $frontend_config
 * @param bool $voice_input_enabled_ui
 * @param bool $allow_openai_web_search_tool
 * @param bool $allow_google_search_grounding
 * @return void Echos HTML.
 */
function render_inline_mode_html_logic(
    \WPAICG\Chat\Frontend\Shortcode\Renderer $rendererInstance,
    int $bot_id,
    string $theme, // This is the selected theme ('light', 'dark', 'custom')
    string $json_encoded_data,
    array $feature_flags,
    array $frontend_config,
    bool $voice_input_enabled_ui,
    bool $allow_openai_web_search_tool,
    bool $allow_google_search_grounding
) {
    $voice_input_class = $voice_input_enabled_ui ? 'aipkit-voice-input-enabled' : '';
    $web_search_class = $allow_openai_web_search_tool ? 'aipkit-web-search-tool-allowed' : '';
    $google_grounding_class = $allow_google_search_grounding ? 'aipkit-google-search-grounding-allowed' : '';

    // --- NEW: Add custom theme class and data attribute ---
    $custom_theme_class = '';
    $custom_theme_data_attr = '';
    if ($theme === 'custom' && !empty($frontend_config['customThemeSettings'])) {
        $custom_theme_class = 'aipkit-theme-custom';
        $custom_theme_data_attr = 'data-custom-theme=\'' . esc_attr(wp_json_encode($frontend_config['customThemeSettings'])) . '\'';
    } elseif ($theme === 'custom') { // Custom theme selected, but no settings (will fallback to light/base)
        $custom_theme_class = 'aipkit-theme-custom';
    }
    // --- END NEW ---

    ?>
    <div class="aipkit_chat_container aipkit-theme-<?php echo esc_attr($theme); ?> <?php echo esc_attr($custom_theme_class); ?> aipkit-sidebar-state-closed <?php echo esc_attr($voice_input_class); ?> <?php echo esc_attr($web_search_class); ?> <?php echo esc_attr($google_grounding_class); ?>" id="aipkit_chat_container_<?php echo esc_attr($bot_id); ?>" data-bot-id="<?php echo esc_attr($bot_id); ?>" data-config='<?php echo esc_attr($json_encoded_data); ?>' <?php echo $custom_theme_data_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $custom_theme_data_attr is properly escaped ?> >
        <?php if ($feature_flags['sidebar_ui_enabled']): ?>
            <?php $rendererInstance->render_sidebar_html_internal($frontend_config); ?>
        <?php endif; ?>
        <div class="aipkit_chat_main">
             <?php if ($feature_flags['show_header']): ?>
                <?php $rendererInstance->render_header_html_internal($feature_flags, $frontend_config, false); ?>
             <?php endif; ?>
            <div class="aipkit_chat_messages"></div>
             <?php if ($feature_flags['starters_ui_enabled']): ?>
                <div class="aipkit_conversation_starters"></div>
             <?php endif; ?>
            <?php $rendererInstance->render_input_area_html_internal($frontend_config, true, $feature_flags, $allow_openai_web_search_tool, $allow_google_search_grounding); ?>
            <?php $rendererInstance->render_footer_html_internal($frontend_config['footerText']); ?>
        </div>
    </div>
    <?php
}