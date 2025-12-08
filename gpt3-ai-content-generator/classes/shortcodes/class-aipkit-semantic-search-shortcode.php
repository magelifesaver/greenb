<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/shortcodes/class-aipkit-semantic-search-shortcode.php
// Status: NEW FILE

namespace WPAICG\Shortcodes;

use WPAICG\aipkit_dashboard;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * AIPKit_Semantic_Search_Shortcode
 *
 * Handles the rendering of the [aipkit_semantic_search] shortcode.
 * Asset enqueueing is handled centrally by AIPKit_Shortcodes_Manager.
 */
class AIPKit_Semantic_Search_Shortcode
{
    /**
     * Render the shortcode output.
     *
     * @param array $atts Shortcode attributes (currently none supported).
     * @return string HTML output.
     */
    public function render_shortcode($atts = [])
    {
        // 1. Check if the addon is active
        if (!class_exists('\\WPAICG\\aipkit_dashboard') || !aipkit_dashboard::is_addon_active('semantic_search')) {
            if (current_user_can('manage_options')) {
                return '<p style="color:orange;"><em>[' . esc_html__('AIPKit Semantic Search Shortcode: Addon is disabled.', 'gpt3-ai-content-generator') . ']</em></p>';
            }
            return '';
        }

        // 2. Retrieve saved settings (handled by localization in AIPKit_Shortcodes_Manager)

        // 3. Render the basic HTML structure
        ob_start();
        ?>
        <div class="aipkit_semantic_search_wrapper">
            <form class="aipkit_semantic_search_form" onsubmit="return false;">
                <input type="search" class="aipkit_semantic_search_input" placeholder="<?php esc_attr_e('Search...', 'gpt3-ai-content-generator'); ?>" required>
                <button type="submit" class="aipkit_semantic_search_button">
                    <span class="aipkit_btn-text"><?php esc_html_e('Search', 'gpt3-ai-content-generator'); ?></span>
                    <span class="aipkit_spinner" style="display:none;"></span>
                </button>
            </form>
            <div class="aipkit_semantic_search_results">
                <!-- Results will be loaded here by JavaScript -->
            </div>
        </div>
        <?php
        return ob_get_clean();

        // 4 & 5. Enqueue and Localize Assets
        // This is handled centrally in AIPKit_Shortcodes_Manager to avoid redundant enqueues/localizations
        // when multiple shortcodes are on a page. The manager's check `has_shortcode` will trigger it.
    }
}
