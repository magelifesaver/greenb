<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/shortcodes/token-usage/render/render_progress_bar.php
// Status: NEW FILE

namespace WPAICG\Shortcodes\TokenUsage\Render;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic to render the progress bar HTML.
 *
 * @param int $percentage The percentage to display.
 * @return string HTML for the progress bar.
 */
function render_progress_bar_logic($percentage): string
{
    $percentage = max(0, min(100, (int)$percentage));
    $color = '#4CAF50'; // Green
    if ($percentage > 90) {
        $color = '#f44336';
    } // Red
    elseif ($percentage > 70) {
        $color = '#ff9800';
    } // Orange

    return sprintf(
        '<div class="aipkit_progress_bar_container" title="%1$d%%">' .
            '<div class="aipkit_progress_bar_filled" style="width: %1$d%%; background-color: %2$s;"></div>' .
            '<span class="aipkit_progress_bar_text">%1$d%%</span>' .
        '</div>',
        esc_attr($percentage),
        esc_attr($color)
    );
}
