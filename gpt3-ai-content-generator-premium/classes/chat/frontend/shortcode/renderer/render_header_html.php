<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/chat/frontend/shortcode/renderer/render_header_html.php

namespace WPAICG\Chat\Frontend\Shortcode\RendererMethods;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for rendering the chat header HTML.
 *
 * @param array $feature_flags
 * @param array $frontend_config
 * @param bool $is_popup
 * @return void Echos HTML.
 */
function render_header_html_logic(array $feature_flags, array $frontend_config, bool $is_popup) {
    // SVG definitions
    $sidebar_toggle_svg = '<svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-menu-2"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 6l16 0" /><path d="M4 12l16 0" /><path d="M4 18l16 0" /></svg>';
    $fullscreen_svg = '<svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-arrows-maximize"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M16 4l4 0l0 4" /><path d="M14 10l6 -6" /><path d="M8 20l-4 0l0 -4" /><path d="M4 20l6 -6" /><path d="M16 20l4 0l0 -4" /><path d="M14 14l6 6" /><path d="M8 4l-4 0l0 4" /><path d="M4 4l6 6" /></svg>';
    $download_svg = '<svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-download"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2" /><path d="M7 11l5 5l5 -5" /><path d="M12 4l0 12" /></svg>';
    $close_svg = '<svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-x"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M18 6l-12 12" /><path d="M6 6l12 12" /></svg>';
    ?>
    <div class="aipkit_chat_header">
        <div class="aipkit_header_info">
            <?php if (!$is_popup && $feature_flags['sidebar_ui_enabled']): ?>
                <button class="aipkit_header_btn aipkit_sidebar_toggle_btn" title="<?php echo esc_attr($frontend_config['text']['sidebarToggle']); ?>" aria-label="<?php echo esc_attr($frontend_config['text']['sidebarToggle']); ?>">
                    <?php echo $sidebar_toggle_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </button>
            <?php endif; ?>
        </div>
        <div class="aipkit_header_actions">
            <?php if ($feature_flags['enable_fullscreen']): ?>
                <button class="aipkit_header_btn aipkit_fullscreen_btn" title="<?php echo esc_attr($frontend_config['text']['fullscreen']); ?>" aria-label="<?php echo esc_attr($frontend_config['text']['fullscreen']); ?>" aria-expanded="false">
                    <?php echo $fullscreen_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </button>
            <?php endif; ?>
            <?php if ($feature_flags['enable_download']): ?>
                <div class="aipkit_download_wrapper">
                    <button class="aipkit_header_btn aipkit_download_btn" title="<?php echo esc_attr($frontend_config['text']['download']); ?>" aria-label="<?php echo esc_attr($frontend_config['text']['download']); ?>">
                        <?php echo $download_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    </button>
                    <?php if ($feature_flags['pdf_ui_enabled']): ?>
                        <div class="aipkit_download_menu">
                            <div class="aipkit_download_menu_item" data-format="txt"><?php echo esc_html($frontend_config['text']['downloadTxt']); ?></div>
                            <div class="aipkit_download_menu_item" data-format="pdf"><?php echo esc_html($frontend_config['text']['downloadPdf']); ?></div>
                        </div>
                    <?php elseif ($feature_flags['enable_download']): ?>
                        <div class="aipkit_download_menu">
                            <div class="aipkit_download_menu_item" data-format="txt"><?php echo esc_html($frontend_config['text']['downloadTxt']); ?></div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <?php if ($is_popup): ?>
                <button class="aipkit_header_btn aipkit_close_btn" title="<?php echo esc_attr($frontend_config['text']['closeChat']); ?>" aria-label="<?php echo esc_attr($frontend_config['text']['closeChat']); ?>">
                    <?php echo $close_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </button>
            <?php endif; ?>
        </div>
    </div>
    <?php
}