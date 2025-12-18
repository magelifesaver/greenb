<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/chat/frontend/shortcode/renderer/render_footer_html.php

namespace WPAICG\Chat\Frontend\Shortcode\RendererMethods;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for rendering the chat footer HTML.
 *
 * @param string $footer_text
 * @return void Echos HTML.
 */
function render_footer_html_logic(string $footer_text) {
    if (!empty($footer_text)) {
        ?>
        <div class="aipkit_chat_footer"><?php echo wp_kses_post($footer_text); ?></div>
        <?php
    }
}