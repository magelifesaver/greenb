<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/shortcodes/token-usage/render/render_usage_row.php
// Status: MODIFIED

namespace WPAICG\Shortcodes\TokenUsage\Render;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic to render a single row in a usage table.
 *
 * @param \WPAICG\Shortcodes\AIPKit_Token_Usage_Shortcode $facade The facade instance.
 * @param array $item The usage data item.
 * @param string $first_column_label The label for the first column ('Bot Name' or 'Module').
 * @return string HTML for the table row.
 */
function render_usage_row_logic(\WPAICG\Shortcodes\AIPKit_Token_Usage_Shortcode $facade, array $item, string $first_column_label): string
{
    $used = (int) ($item['used'] ?? 0);
    $limit = $item['limit'] ?? null;
    $module = $item['module'] ?? '';
    $context_id = $item['context_id'] ?? 0;

    $remaining_display = '∞';
    $progress_percent = 0;
    $progress_display = '';
    $limit_display = esc_html__('Unlimited', 'gpt3-ai-content-generator');

    if (is_numeric($limit) && $limit > 0) {
        $limit_display = number_format_i18n($limit);
        $remaining = max(0, $limit - $used);
        $remaining_display = number_format_i18n($remaining);
        $progress_percent = round(($used / $limit) * 100);
        $progress_percent = min(100, $progress_percent);
        $progress_display = \WPAICG\Shortcodes\TokenUsage\Render\render_progress_bar_logic($progress_percent);
    }

    ob_start();
    ?>
    <tr class="aipkit-usage-main-row">
        <td data-label="<?php echo esc_attr($first_column_label); ?>"><?php echo esc_html($item['title']); ?></td>
        <td data-label="<?php esc_attr_e('Used Tokens', 'gpt3-ai-content-generator'); ?>">
             <button
                type="button"
                class="aipkit-usage-details-btn aipkit-btn-as-link"
                title="<?php esc_attr_e('Click to view details', 'gpt3-ai-content-generator'); ?>"
                data-module="<?php echo esc_attr($module); ?>"
                data-context-id="<?php echo esc_attr($context_id); ?>"
            >
                <?php echo esc_html(number_format_i18n($used)); ?>
            </button>
        </td>
        <td data-label="<?php esc_attr_e('Limit', 'gpt3-ai-content-generator'); ?>"><?php echo wp_kses_post($limit_display); ?></td>
        <td data-label="<?php esc_attr_e('Remaining Tokens', 'gpt3-ai-content-generator'); ?>"><?php echo wp_kses_post($remaining_display); ?></td>
        <td data-label="<?php esc_attr_e('Progress', 'gpt3-ai-content-generator'); ?>"><?php echo wp_kses_post($progress_display); ?></td>
    </tr>
    <?php
    return ob_get_clean();
}
