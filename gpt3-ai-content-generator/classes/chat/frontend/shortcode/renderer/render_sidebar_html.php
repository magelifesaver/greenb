<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/chat/frontend/shortcode/renderer/render_sidebar_html.php

namespace WPAICG\Chat\Frontend\Shortcode\RendererMethods;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for rendering the conversation sidebar HTML.
 *
 * @param array $frontend_config
 * @return void Echos HTML.
 */
function render_sidebar_html_logic(array $frontend_config)
{
    $new_chat_svg = '<svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-plus"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14" /><path d="M5 12l14 0" /></svg>';
    ?>
    <div class="aipkit_chat_sidebar">
         <div class="aipkit_sidebar_header">
            <h4 class="aipkit_sidebar_title"><?php echo esc_html($frontend_config['text']['conversations']); ?></h4>
            <button class="aipkit_btn aipkit_btn-secondary aipkit_btn-small aipkit_sidebar_new_chat_btn">
                 <?php echo $new_chat_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo esc_html($frontend_config['text']['newChat']); ?>
            </button>
         </div>
         <div class="aipkit_sidebar_content">
             <!-- Conversation list items will be populated by JS -->
         </div>
         <div class="aipkit_sidebar_footer">
             <!-- Optional: Footer content for sidebar, e.g., clear all history -->
         </div>
    </div>
    <?php
}