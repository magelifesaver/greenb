<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/chat/frontend/shortcode/renderer/render_input_area_html.php

namespace WPAICG\Chat\Frontend\Shortcode\RendererMethods;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for rendering the chat input area HTML.
 *
 * @param array $frontend_config
 * @param bool $is_inline Whether the bot is in inline mode.
 * @param array $feature_flags Determined feature flags.
 * @param bool $allow_openai_web_search_tool Whether the OpenAI web search tool is allowed for this bot.
 * @param bool $allow_google_search_grounding Whether Google Search Grounding is allowed for this bot.
 * @return void Echos HTML.
 */
function render_input_area_html_logic(array $frontend_config, bool $is_inline = false, array $feature_flags = [], bool $allow_openai_web_search_tool = false, bool $allow_google_search_grounding = false) {
    // Autofocus is disabled for now as it can cause issues with focus management in some browsers.
    // $autofocus_attr = $is_inline ? 'autofocus' : '';
    $input_action_button_enabled = $feature_flags['input_action_button_enabled'] ?? false;
    $file_upload_ui_enabled = $feature_flags['file_upload_ui_enabled'] ?? false;
    $image_upload_ui_enabled = $feature_flags['image_upload_ui_enabled'] ?? false;
    $voice_input_enabled_ui = $feature_flags['enable_voice_input_ui'] ?? false;
    $realtime_voice_enabled_ui = $feature_flags['enable_realtime_voice_ui'] ?? false;
    $bot_id = $frontend_config['botId'] ?? 'default';

    // SVG definitions
    $attachment_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-paperclip"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 7l-6.5 6.5a1.5 1.5 0 0 0 3 3l6.5 -6.5a3 3 0 0 0 -6 -6l-6.5 6.5a4.5 4.5 0 0 0 9 9l6.5 -6.5" /></svg>';
    $image_upload_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-photo-up"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 8h.01" /><path d="M12.5 21h-6.5a3 3 0 0 1 -3 -3v-12a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v6.5" /><path d="M3 16l5 -5c.928 -.893 2.072 -.893 3 0l3.5 3.5" /><path d="M14 14l1 -1c.679 -.653 1.473 -.829 2.214 -.526" /><path d="M19 22v-6" /><path d="M22 19l-3 -3l-3 3" /></svg>';
    $file_upload_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-file-upload"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4" /><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" /><path d="M12 11v6" /><path d="M9.5 13.5l2.5 -2.5l2.5 2.5" /></svg>';
    $send_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-arrow-up aipkit_send_icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14" /><path d="M18 11l-6 -6" /><path d="M6 11l6 -6" /></svg>';
    $clear_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-eraser aipkit_clear_icon" style="display:none;"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M19 20h-10.5l-4.21 -4.3a1 1 0 0 1 0 -1.41l10 -10a1 1 0 0 1 1.41 0l5 5a1 1 0 0 1 0 1.41l-9.2 9.3" /><path d="M18 13.3l-6.3 -6.3" /></svg>';
    $microphone_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-microphone"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 2m0 3a3 3 0 0 1 3 -3h0a3 3 0 0 1 3 3v5a3 3 0 0 1 -3 3h0a3 3 0 0 1 -3 -3z" /><path d="M5 10a7 7 0 0 0 14 0" /><path d="M8 21l8 0" /><path d="M12 17l0 4" /></svg>';
    $volume_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-volume"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 8a5 5 0 0 1 0 8" /><path d="M17.7 5a9 9 0 0 1 0 14" /><path d="M6 15h-2a1 1 0 0 1 -1 -1v-4a1 1 0 0 1 1 -1h2l3.5 -4.5a.8 .8 0 0 1 1.5 .5v14a.8 .8 0 0 1 -1.5 .5l-3.5 -4.5" /></svg>';
    $world_www_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-world-www"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M19.5 7a9 9 0 0 0 -7.5 -4a8.991 8.991 0 0 0 -7.484 4" /><path d="M11.5 3a16.989 16.989 0 0 0 -1.826 4" /><path d="M12.5 3a16.989 16.989 0 0 1 1.828 4" /><path d="M19.5 17a9 9 0 0 1 -7.5 4a8.991 8.991 0 0 1 -7.484 -4" /><path d="M11.5 21a16.989 16.989 0 0 1 -1.826 -4" /><path d="M12.5 21a16.989 16.989 0 0 0 1.828 -4" /><path d="M2 10l1 4l1.5 -4l1.5 4l1 -4" /><path d="M17 10l1 4l1.5 -4l1.5 4l1 -4" /><path d="M9.5 10l1 4l1.5 -4l1.5 4l1 -4" /></svg>';

    $initial_icon_html = $attachment_svg;
    $initial_aria_label = __('Attach files or use tools', 'gpt3-ai-content-generator');
    $initial_has_popup = 'true';

    if ($file_upload_ui_enabled && !$image_upload_ui_enabled) {
        $initial_icon_html = $file_upload_svg;
        $initial_aria_label = __('Upload File (TXT, PDF)', 'gpt3-ai-content-generator');
        $initial_has_popup = 'false';
    } elseif (!$file_upload_ui_enabled && $image_upload_ui_enabled) {
        $initial_icon_html = $image_upload_svg;
        $initial_aria_label = __('Upload Image', 'gpt3-ai-content-generator');
        $initial_has_popup = 'false';
    }

    ?>
    <div class="aipkit_chat_input">
        <div class="aipkit_chat_input_wrapper">
            <textarea
                id="aipkit_chat_input_field_<?php echo esc_attr($bot_id); ?>"
                name="aipkit_chat_message_<?php echo esc_attr($bot_id); ?>"
                class="aipkit_chat_input_field"
                placeholder="<?php echo esc_attr($frontend_config['text']['typeMessage']); ?>"
                aria-label="<?php esc_attr_e('Chat message input', 'gpt3-ai-content-generator'); ?>"
                rows="1"
            ></textarea>
             <div class="aipkit_chat_input_actions_bar">
                <div class="aipkit_chat_input_actions_left">
                    <button
                        type="button"
                        class="aipkit_input_action_btn aipkit_input_action_toggle"
                        aria-label="<?php echo esc_attr($initial_aria_label); ?>"
                        role="button"
                        <?php if ($initial_has_popup === 'true'): ?>
                            aria-haspopup="true"
                            aria-expanded="false"
                        <?php endif; ?>
                        style="display: <?php echo $input_action_button_enabled ? 'inline-flex' : 'none'; ?>;"
                    >
                        <?php echo $initial_icon_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    </button>
                     <?php if ($allow_openai_web_search_tool): ?>
                     <button
                        type="button"
                        class="aipkit_input_action_btn aipkit_web_search_toggle"
                        aria-label="<?php echo esc_attr($frontend_config['text']['webSearchToggle'] ?? __('Toggle Web Search', 'gpt3-ai-content-generator')); ?>"
                        title="<?php echo esc_attr($frontend_config['text']['webSearchInactive'] ?? __('Web Search Inactive', 'gpt3-ai-content-generator')); ?>"
                        role="button"
                        aria-pressed="false"
                        style="display: inline-flex;"
                    >
                        <?php echo $world_www_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    </button>
                    <?php endif; ?>
                    <?php if ($allow_google_search_grounding): ?>
                     <button
                        type="button"
                        class="aipkit_input_action_btn aipkit_google_search_grounding_toggle"
                        aria-label="<?php echo esc_attr($frontend_config['text']['googleSearchGroundingToggle'] ?? __('Toggle Google Search Grounding', 'gpt3-ai-content-generator')); ?>"
                        title="<?php echo esc_attr($frontend_config['text']['googleSearchGroundingInactive'] ?? __('Google Search Grounding Inactive', 'gpt3-ai-content-generator')); ?>"
                        role="button"
                        aria-pressed="false"
                        style="display: inline-flex;"
                    >
                        <?php echo $world_www_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    </button>
                    <?php endif; ?>
                </div>
                <div class="aipkit_chat_input_actions_right">
                    <button
                        class="aipkit_input_action_btn aipkit_realtime_voice_agent_btn"
                        aria-label="<?php esc_attr_e('Start Voice Conversation', 'gpt3-ai-content-generator'); ?>"
                        title="<?php esc_attr_e('Start Voice Conversation', 'gpt3-ai-content-generator'); ?>"
                        type="button"
                        style="display: <?php echo $realtime_voice_enabled_ui ? 'inline-flex' : 'none'; ?>;"
                    >
                        <?php echo $volume_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    </button>
                    <button
                        class="aipkit_input_action_btn aipkit_voice_input_btn"
                        aria-label="<?php esc_attr_e('Voice input', 'gpt3-ai-content-generator'); ?>"
                        title="<?php esc_attr_e('Voice input', 'gpt3-ai-content-generator'); ?>"
                        type="button"
                        style="display: <?php echo $voice_input_enabled_ui ? 'inline-flex' : 'none'; ?>;"
                    >
                        <?php echo $microphone_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    </button>
                    <button
                        class="aipkit_input_action_btn aipkit_chat_action_btn aipkit_send_btn"
                        aria-label="<?php echo esc_attr($frontend_config['text']['sendMessage']); ?>"
                        title="<?php echo esc_attr($frontend_config['text']['sendMessage']); ?>"
                        type="button"
                    >
                        <?php echo $send_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        <?php echo $clear_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        <span class="aipkit_spinner" style="display:none;"></span>
                    </button>
                </div>
            </div>
        </div>
        <?php if ($input_action_button_enabled && ($file_upload_ui_enabled && $image_upload_ui_enabled) ): ?>
            <div class="aipkit_input_action_menu" id="aipkit_input_action_menu_<?php echo esc_attr(uniqid()); ?>" role="menu" aria-hidden="true">
                <?php if ($file_upload_ui_enabled): ?>
                    <button type="button" class="aipkit_input_action_menu_item" role="menuitem" aria-label="<?php esc_attr_e('Upload File (TXT, PDF)', 'gpt3-ai-content-generator'); ?>"><?php echo $file_upload_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
                <?php endif; ?>
                <?php if ($image_upload_ui_enabled): ?>
                    <button type="button" class="aipkit_input_action_menu_item" role="menuitem" aria-label="<?php esc_attr_e('Upload image', 'gpt3-ai-content-generator'); ?>"><?php echo $image_upload_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
}