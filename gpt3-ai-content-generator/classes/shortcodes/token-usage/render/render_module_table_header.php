<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/shortcodes/token-usage/render/render_module_table_header.php
// Status: NEW FILE

namespace WPAICG\Shortcodes\TokenUsage\Render;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for rendering the HTML for a module usage table header.
 *
 * @param string $first_column_label The label for the first column (e.g., 'Chatbot', 'Module').
 * @return string HTML output for the table header.
 */
function render_module_table_header_logic(string $first_column_label): string
{
    ob_start();
    ?>
    <thead>
        <tr>
            <th><?php echo esc_html($first_column_label); ?></th>
            <th><?php esc_html_e('Used', 'gpt3-ai-content-generator'); ?></th>
            <th><?php esc_html_e('Limit', 'gpt3-ai-content-generator'); ?></th>
            <th><?php esc_html_e('Remaining', 'gpt3-ai-content-generator'); ?></th>
            <th><?php esc_html_e('Progress', 'gpt3-ai-content-generator'); ?></th>
        </tr>
    </thead>
    <?php
    return ob_get_clean();
}
