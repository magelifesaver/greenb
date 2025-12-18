<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/shortcodes/token-usage/render/render_dashboard.php
// Status: MODIFIED

namespace WPAICG\Shortcodes\TokenUsage\Render;

// --- NEW: Require the new helper function file ---
require_once __DIR__ . '/render_module_table_header.php';

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for rendering the HTML for the token usage dashboard.
 *
 * @param \WPAICG\Shortcodes\AIPKit_Token_Usage_Shortcode $facade The facade instance.
 * @param array $usage_data Structured usage data grouped by module.
 * @param bool $show_chatbot
 * @param bool $show_aiforms
 * @param bool $show_imagegenerator
 * @return string HTML output.
 */
function render_dashboard_logic(
    \WPAICG\Shortcodes\AIPKit_Token_Usage_Shortcode $facade,
    array $usage_data,
    bool $show_chatbot = true,
    bool $show_aiforms = true,
    bool $show_imagegenerator = true
): string {
    ob_start();
    $shop_page_url = get_option('aipkit_token_shop_page_url', '');
    if (empty($shop_page_url) && function_exists('wc_get_page_id')) {
        $shop_page_url = get_permalink(wc_get_page_id('shop'));
    }
    ?>
    <div class="aipkit_token_usage_dashboard">
        <h2 class="aipkit_token_usage_title"><?php esc_html_e('Token Usage', 'gpt3-ai-content-generator'); ?></h2>

        <!-- Enhanced Token Balance Section with Purchase Details -->
        <?php
        // Get purchase history for the current user
        $purchase_history = \WPAICG\Shortcodes\TokenUsage\Data\get_user_purchase_history_logic(get_current_user_id(), 10);
        echo wp_kses_post(\WPAICG\Shortcodes\TokenUsage\Render\render_purchase_details_logic($purchase_history, $usage_data['token_balance']));
        ?>

        <?php
        $has_periodic_usage = ($show_chatbot && !empty($usage_data['chat'])) ||
                              ($show_imagegenerator && !empty($usage_data['image_generator'])) ||
                              ($show_aiforms && !empty($usage_data['ai_forms'])) ||
                              has_action('aipkit_after_token_usage_dashboard');
    if ($has_periodic_usage) :
        ?>
        <h3 class="aipkit_usage_section_title"><?php esc_html_e('Free Usage', 'gpt3-ai-content-generator'); ?></h3>
        <?php
        // --- Render Chatbot Usage ---
        if ($show_chatbot && !empty($usage_data['chat'])) {
            ?>
            <div class="aipkit_usage_section aipkit_usage_section_chat">
                <h4 class="aipkit_usage_sub_section_title"><?php esc_html_e('Chatbot', 'gpt3-ai-content-generator'); ?></h4>
                <table class="aipkit_usage_table">
                    <?php
                        // --- MODIFIED: Use new helper function ---
                        echo wp_kses_post(render_module_table_header_logic(__('Chatbot', 'gpt3-ai-content-generator')));
            ?>
                    <tbody>
                        <?php
                foreach ($usage_data['chat'] as $bot_usage) {
                    // Call the new render_usage_row_logic function
                    echo wp_kses_post(\WPAICG\Shortcodes\TokenUsage\Render\render_usage_row_logic($facade, $bot_usage, __('Chatbot', 'gpt3-ai-content-generator')));
                }
            ?>
                    </tbody>
                </table>
            </div>
            <?php
        }

        // --- Render Image Generator Usage ---
        if ($show_imagegenerator && !empty($usage_data['image_generator'])) {
            ?>
            <div class="aipkit_usage_section aipkit_usage_section_image_generator">
                <h4 class="aipkit_usage_sub_section_title"><?php esc_html_e('Image Generator', 'gpt3-ai-content-generator'); ?></h4>
                <table class="aipkit_usage_table">
                    <?php
                        // --- MODIFIED: Use new helper function ---
                        echo wp_kses_post(render_module_table_header_logic(__('Module', 'gpt3-ai-content-generator')));
            ?>
                    <tbody>
                        <?php
                foreach ($usage_data['image_generator'] as $item) {
                    echo wp_kses_post(\WPAICG\Shortcodes\TokenUsage\Render\render_usage_row_logic($facade, $item, __('Module', 'gpt3-ai-content-generator')));
                }
            ?>
                    </tbody>
                </table>
            </div>
            <?php
        }

        // --- Render AI Forms Usage ---
        if ($show_aiforms && !empty($usage_data['ai_forms'])) {
            ?>
            <div class="aipkit_usage_section aipkit_usage_section_ai_forms">
                <h4 class="aipkit_usage_sub_section_title"><?php esc_html_e('AI Forms', 'gpt3-ai-content-generator'); ?></h4>
                 <table class="aipkit_usage_table">
                    <?php
                        // --- MODIFIED: Use new helper function ---
                        echo wp_kses_post(render_module_table_header_logic(__('Module', 'gpt3-ai-content-generator')));
            ?>
                    <tbody>
                         <?php
                foreach ($usage_data['ai_forms'] as $item) {
                    echo wp_kses_post(\WPAICG\Shortcodes\TokenUsage\Render\render_usage_row_logic($facade, $item, __('Module', 'gpt3-ai-content-generator')));
                }
            ?>
                    </tbody>
                </table>
            </div>
            <?php
        }

        do_action('aipkit_after_token_usage_dashboard', $usage_data);
    endif;
    ?>
    </div>
    <?php
    return ob_get_clean();
}
// --- END: render_dashboard_logic() ---