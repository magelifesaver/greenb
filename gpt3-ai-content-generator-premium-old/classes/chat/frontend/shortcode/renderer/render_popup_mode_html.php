<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/chat/frontend/shortcode/renderer/render_popup_mode_html.php
// Status: MODIFIED

namespace WPAICG\Chat\Frontend\Shortcode\RendererMethods;

use WPAICG\Chat\Storage\BotSettingsManager;
use WPAICG\Chat\Utils\AIPKit_SVG_Icons;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for rendering the Popup mode HTML.
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
function render_popup_mode_html_logic(
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
    $popup_position = $frontend_config['popupPosition'];
    $popup_icon_type = $frontend_config['popupIconType'] ?? BotSettingsManager::DEFAULT_POPUP_ICON_TYPE;
    $popup_icon_style = $frontend_config['popupIconStyle'] ?? 'circle';
    $popup_icon_value = $frontend_config['popupIconValue'] ?? BotSettingsManager::DEFAULT_POPUP_ICON_VALUE;
    $popup_icon_size  = (isset($frontend_config['popupIconSize']) && in_array($frontend_config['popupIconSize'], ['small','medium','large','xlarge'], true))
        ? $frontend_config['popupIconSize']
        : BotSettingsManager::DEFAULT_POPUP_ICON_SIZE;
    $icon_html = '';

    if ($popup_icon_type === 'custom' && !empty($popup_icon_value)) {
        // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage -- Reason: The image source is correctly retrieved using a WordPress function (e.g., `wp_get_attachment_image_url`). The `<img>` tag is constructed manually to build a custom HTML structure with specific wrappers, classes, or attributes that are not achievable with the standard `wp_get_attachment_image()` function.
        $icon_html = '<img src="' . esc_url($popup_icon_value) . '" alt="' . esc_attr__('Open Chat', 'gpt3-ai-content-generator') . '" class="aipkit_popup_custom_icon" />';
    } else {
        switch ($popup_icon_value) {
            case 'plus': $icon_html = AIPKit_SVG_Icons::get_plus_svg();
                break;
            case 'question-mark': $icon_html = AIPKit_SVG_Icons::get_question_mark_svg();
                break;
            case 'chat-bubble': default: $icon_html = AIPKit_SVG_Icons::get_chat_bubble_svg();
                break;
        }
    }
    $voice_input_class = $voice_input_enabled_ui ? 'aipkit-voice-input-enabled' : '';
    $web_search_class = $allow_openai_web_search_tool ? 'aipkit-web-search-tool-allowed' : '';
    $google_grounding_class = $allow_google_search_grounding ? 'aipkit-google-search-grounding-allowed' : '';

    // --- NEW: Add custom theme class and data attribute ---
    $custom_theme_class = '';
    $custom_theme_data_attr = '';
    if ($theme === 'custom' && !empty($frontend_config['customThemeSettings'])) {
        $custom_theme_class = 'aipkit-theme-custom';
        $custom_theme_data_attr = 'data-custom-theme=\'' . esc_attr(wp_json_encode($frontend_config['customThemeSettings'])) . '\'';
    } elseif ($theme === 'custom') {
        $custom_theme_class = 'aipkit-theme-custom';
    }
    // --- END NEW ---

    ?>
    <div class="aipkit_popup_wrapper" id="aipkit_popup_wrapper_<?php echo esc_attr($bot_id); ?>" data-config='<?php echo esc_attr($json_encoded_data); ?>' data-bot-id="<?php echo esc_attr($bot_id); ?>" data-icon-size="<?php echo esc_attr($popup_icon_size); ?>">
        <button class="aipkit_popup_trigger aipkit_popup_position-<?php echo esc_attr($popup_position); ?> aipkit_popup_trigger--size-<?php echo esc_attr($popup_icon_size); ?>" id="aipkit_popup_trigger_<?php echo esc_attr($bot_id); ?>" aria-label="<?php esc_attr_e('Open Chat', 'gpt3-ai-content-generator'); ?>" data-icon-style="<?php echo esc_attr($popup_icon_style); ?>">
            <?php echo $icon_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped?>
        </button>
        <?php
        // --- NEW: Optional popup hint above trigger ---
        $hint_enabled = !empty($frontend_config['popupLabelEnabled']) && !empty($frontend_config['popupLabelText']);
        if ($hint_enabled) {
            $dismissible = !empty($frontend_config['popupLabelDismissible']);
            // Plain text only
            $hint_text = wp_strip_all_tags((string)$frontend_config['popupLabelText']);
            $hint_size = isset($frontend_config['popupLabelSize']) && in_array($frontend_config['popupLabelSize'], ['small','medium','large','xlarge'], true)
                ? $frontend_config['popupLabelSize']
                : 'medium';
            ?>
            <div
                class="aipkit_popup_hint aipkit_popup_position-<?php echo esc_attr($popup_position); ?> aipkit_popup_hint--size-<?php echo esc_attr($hint_size); ?>"
                id="aipkit_popup_hint_<?php echo esc_attr($bot_id); ?>"
                role="status"
                aria-live="polite"
                aria-hidden="true"
                data-bot-id="<?php echo esc_attr($bot_id); ?>"
            >
                <span class="aipkit_popup_hint_text"><?php echo esc_html($hint_text); ?></span>
                <?php if ($dismissible): ?>
                    <button type="button" class="aipkit_popup_hint_close" aria-label="<?php echo esc_attr($frontend_config['text']['dismissHint'] ?? 'Dismiss'); ?>">&times;</button>
                <?php endif; ?>
            </div>
        <?php } // end hint_enabled ?>
        <div class="aipkit_chat_container aipkit_popup_content aipkit-theme-<?php echo esc_attr($theme); ?> <?php echo esc_attr($custom_theme_class); ?> aipkit_popup_position-<?php echo esc_attr($popup_position); ?> aipkit-sidebar-state-closed <?php echo esc_attr($voice_input_class); ?> <?php echo esc_attr($web_search_class); ?> <?php echo esc_attr($google_grounding_class); ?>" id="aipkit_chat_container_<?php echo esc_attr($bot_id); ?>" aria-hidden="true" <?php echo $custom_theme_data_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped?> >
            <div class="aipkit_chat_main">
                <?php if ($feature_flags['show_header']): ?>
                    <?php $rendererInstance->render_header_html_internal($feature_flags, $frontend_config, true); ?>
                <?php endif; ?>
                <div class="aipkit_chat_messages"></div>
                <?php if ($feature_flags['starters_ui_enabled']): ?>
                    <div class="aipkit_conversation_starters"></div>
                <?php endif; ?>
                <?php $rendererInstance->render_input_area_html_internal($frontend_config, false, $feature_flags, $allow_openai_web_search_tool, $allow_google_search_grounding); ?>
                <?php $rendererInstance->render_footer_html_internal($frontend_config['footerText']); ?>
            </div>
        </div>
    </div>
    <?php
}
