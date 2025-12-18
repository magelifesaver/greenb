<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/shortcodes/class-aipkit-token-usage-shortcode.php
// Status: MODIFIED

namespace WPAICG\Shortcodes; // Correct namespace

use WPAICG\Shortcodes\TokenUsage\Render as TokenUsageRenderer;
use WPAICG\Shortcodes\TokenUsage\Data as TokenUsageData;
use WPAICG\Shortcodes\TokenUsage\Helpers as TokenUsageHelpers;

// Load method logic files
$base_path = __DIR__ . '/token-usage/';
require_once $base_path . 'render/render_shortcode.php';
require_once $base_path . 'render/render_dashboard.php';
require_once $base_path . 'render/render_usage_row.php';
require_once $base_path . 'render/render_progress_bar.php';
require_once $base_path . 'helpers/get_user_limit_for_module.php';
require_once $base_path . 'data/get_user_token_usage_data.php';
require_once $base_path . 'data/get_user_purchase_history.php'; // NEW: Purchase history data
require_once $base_path . 'render/render_module_table_header.php'; // NEW: Require the new helper
require_once $base_path . 'render/render_purchase_details.php'; // NEW: Purchase details render

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * AIPKit_Token_Usage_Shortcode (Facade)
 *
 * Handles the rendering of the [aipkit_token_usage] shortcode by delegating
 * logic to namespaced functions.
 */
class AIPKit_Token_Usage_Shortcode
{
    /**
     * Registers the AJAX hook for fetching usage details.
     */
    public function init_hooks()
    {
        add_action('wp_ajax_aipkit_get_token_usage_details', [$this, 'ajax_get_token_usage_details']);
    }

    /**
     * AJAX handler to fetch detailed token usage for a specific module.
     */
    public function ajax_get_token_usage_details()
    {
        check_ajax_referer('aipkit_token_usage_details_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('You must be logged in to view details.', 'gpt3-ai-content-generator')], 403);
            return;
        }

        $user_id = get_current_user_id();
        $module = isset($_POST['module']) ? sanitize_key($_POST['module']) : '';
        $context_id = isset($_POST['context_id']) ? absint($_POST['context_id']) : 0;
        $page = isset($_POST['page']) ? absint($_POST['page']) : 1;
        $per_page = 10;

        if (empty($module)) {
            wp_send_json_error(['message' => __('Module not specified.', 'gpt3-ai-content-generator')], 400);
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'aipkit_chat_logs';
        $where_clauses = ['user_id = %d', 'module = %s'];
        $params = [$user_id, $module];

        if ($module === 'chat') {
            if (empty($context_id)) {
                wp_send_json_error(['message' => __('Chatbot ID is required.', 'gpt3-ai-content-generator')], 400);
                return;
            }
            $where_clauses[] = 'bot_id = %d';
            $params[] = $context_id;
        } else {
            $where_clauses[] = 'bot_id IS NULL';
        }

        $where_sql = implode(' AND ', $where_clauses);

        // --- Caching for token usage details query ---
        $cache_key = 'aipkit_token_usage_details_' . md5(serialize($params));
        $cache_group = 'aipkit_token_usage';
        $conversations = wp_cache_get($cache_key, $cache_group);

        if (false === $conversations) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Reason: Necessary for fetching and processing usage data from the custom logs table. Caching is implemented.
            $conversations = $wpdb->get_results($wpdb->prepare("SELECT messages FROM {$table_name} WHERE {$where_sql}", $params), ARRAY_A);
            wp_cache_set($cache_key, $conversations, $cache_group, MINUTE_IN_SECONDS); // Cache for 1 minute
        }
        // --- End Caching ---

        $usage_details = [];
        if (!empty($conversations)) {
            foreach ($conversations as $conv) {
                $messages_data = json_decode($conv['messages'], true);
                $messages_array = $messages_data['messages'] ?? ($messages_data ?? []);
                if (is_array($messages_array)) {
                    foreach ($messages_array as $msg) {
                        if (isset($msg['role']) && ($msg['role'] === 'bot' || $msg['role'] === 'assistant') && isset($msg['usage']['total_tokens']) && $msg['usage']['total_tokens'] > 0) {
                            $usage_details[] = [
                                'timestamp' => $msg['timestamp'] ?? 0,
                                'tokens'    => (int) $msg['usage']['total_tokens']
                            ];
                        }
                    }
                }
            }
        }

        // Sort by most recent
        usort($usage_details, fn ($a, $b) => $b['timestamp'] <=> $a['timestamp']);

        $total_items = count($usage_details);
        $total_pages = ceil($total_items / $per_page);
        $offset = ($page - 1) * $per_page;
        $paginated_items = array_slice($usage_details, $offset, $per_page);

        wp_send_json_success([
            'items' => $paginated_items,
            'pagination' => [
                'currentPage' => $page,
                'totalPages'  => $total_pages,
                'totalItems'  => $total_items
            ]
        ]);
    }


    /**
     * Render the shortcode output.
     * Delegates logic to \WPAICG\Shortcodes\TokenUsage\Render\render_shortcode_logic().
     *
     * @param array $atts Shortcode attributes. Supported: chatbot, aiforms, imagegenerator (all 'true' or 'false').
     * @return string HTML output.
     */
    public function render_shortcode($atts = [])
    {
        return TokenUsageRenderer\render_shortcode_logic($this, $atts);
    }

    /**
     * Helper to determine the token limit for a logged-in user for a specific module.
     * Delegates logic to \WPAICG\Shortcodes\TokenUsage\Helpers\get_user_limit_for_module_logic().
     * This method is public so it can be called by the modularized data fetching logic.
     *
     * @param int $user_id The user ID.
     * @param array $module_token_settings The token management settings for the module.
     * @return int|null The token limit (int > 0), 0 if disabled, or null if unlimited.
     */
    public function get_user_limit_for_module(int $user_id, array $module_token_settings): ?int
    {
        return TokenUsageHelpers\get_user_limit_for_module_logic($user_id, $module_token_settings);
    }

    /**
     * Fetches and structures token usage data for the current user.
     * Delegates logic to \WPAICG\Shortcodes\TokenUsage\Data\get_user_token_usage_data_logic().
     *
     * @param int $user_id The ID of the current user.
     * @return array Structured usage data.
     */
    private function get_user_token_usage_data($user_id)
    {
        return TokenUsageData\get_user_token_usage_data_logic($this, $user_id);
    }

    /**
     * Renders the HTML for the token usage dashboard.
     * Delegates logic to \WPAICG\Shortcodes\TokenUsage\Render\render_dashboard_logic().
     *
     * @param array $usage_data Structured usage data grouped by module.
     * @param bool $show_chatbot
     * @param bool $show_aiforms
     * @param bool $show_imagegenerator
     * @return string HTML output.
     */
    private function render_dashboard($usage_data, bool $show_chatbot = true, bool $show_aiforms = true, bool $show_imagegenerator = true)
    {
        return TokenUsageRenderer\render_dashboard_logic($this, $usage_data, $show_chatbot, $show_aiforms, $show_imagegenerator);
    }

    /**
     * Helper to render a single row in a usage table.
     * Delegates logic to \WPAICG\Shortcodes\TokenUsage\Render\render_usage_row_logic().
     *
     * @param array $item The usage data item.
     * @param string $first_column_label The label for the first column ('Bot Name' or 'Module').
     * @return string HTML for the table row.
     */
    private function render_usage_row(array $item, string $first_column_label): string
    {
        return TokenUsageRenderer\render_usage_row_logic($this, $item, $first_column_label);
    }

    /**
     * Helper to render the progress bar HTML.
     * Delegates logic to \WPAICG\Shortcodes\TokenUsage\Render\render_progress_bar_logic().
     *
     * @param int $percentage The percentage to display.
     * @return string HTML for the progress bar.
     */
    private function render_progress_bar($percentage)
    {
        return TokenUsageRenderer\render_progress_bar_logic($percentage);
    }
}