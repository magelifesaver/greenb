<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/chat/admin/ajax/log_ajax_handler.php
// Status: MODIFIED

namespace WPAICG\Chat\Admin\Ajax;

use WPAICG\Chat\Storage\LogStorage;
use WPAICG\Chat\Utils\Utils; // Needed for time diff
use WPAICG\Chat\Storage\LogCronManager; // NEW: For scheduling
use WPAICG\Chat\Storage\LogManager; // NEW: For pruning
use WPAICG\Chat\Utils\LogStatusRenderer; // For cron status display
use WPAICG\Chat\Utils\LogConfig; // For centralized configuration
use WPAICG\aipkit_dashboard;
use WPAICG\AIPKIT_AI_Settings; // ADDED for security settings access
use WP_Error; // Added use statement

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Handles AJAX requests related to Chat Log management (fetching, exporting, deleting).
 * REMOVED: 'module' filter from extract_log_filters_from_post.
 */
class LogAjaxHandler extends BaseAjaxHandler
{
    private $log_storage;

    public function __construct()
    {
        if (!class_exists(\WPAICG\Chat\Storage\LogStorage::class)) {
            return;
        }
        $this->log_storage = new LogStorage();
    }

    /**
     * AJAX: Adds or removes an IP from the banned list.
     * @since 2.3.37
     */
    public function ajax_toggle_ip_block_status()
    {
        $permission_check = $this->check_module_access_permissions('logs', 'aipkit_toggle_ip_block_nonce');
        if (is_wp_error($permission_check)) {
            $this->send_wp_error($permission_check);
            return;
        }

        $post_data = wp_unslash($_POST);
        $ip_to_toggle = isset($post_data['ip']) ? sanitize_text_field($post_data['ip']) : '';
        $is_currently_banned = isset($post_data['is_banned']) ? ($post_data['is_banned'] === 'true') : false;

        if (empty($ip_to_toggle) || !filter_var($ip_to_toggle, FILTER_VALIDATE_IP)) {
            $this->send_wp_error(new WP_Error('invalid_ip', __('Invalid IP address provided.', 'gpt3-ai-content-generator')), 400);
            return;
        }

        $security_options = AIPKIT_AI_Settings::get_security_settings();
        $banned_ips_settings = $security_options['bannedips'] ?? ['ips' => '', 'message' => ''];
        $banned_ips_raw = $banned_ips_settings['ips'] ?? '';

        $banned_ips_list = array_map('trim', explode(',', $banned_ips_raw));
        $banned_ips_list = array_filter($banned_ips_list, 'strlen');

        $ip_index = array_search($ip_to_toggle, $banned_ips_list, true);

        if ($is_currently_banned) {
            // Action is to UNBLOCK
            if ($ip_index !== false) {
                unset($banned_ips_list[$ip_index]);
                $message = __('IP address unblocked.', 'gpt3-ai-content-generator');
            } else {
                $message = __('IP address was already unblocked.', 'gpt3-ai-content-generator');
            }
        } else {
            // Action is to BLOCK
            if ($ip_index === false) {
                $banned_ips_list[] = $ip_to_toggle;
                $message = __('IP address blocked.', 'gpt3-ai-content-generator');
            } else {
                $message = __('IP address was already blocked.', 'gpt3-ai-content-generator');
            }
        }

        $new_banned_ips_string = implode(', ', array_unique($banned_ips_list));
        $security_options['bannedips']['ips'] = $new_banned_ips_string;

        update_option(AIPKIT_AI_Settings::SECURITY_OPTION_NAME, $security_options, 'no');

        wp_send_json_success([
            'message' => $message,
            'new_banned_ips_list' => $new_banned_ips_string
        ]);
    }


    /**
     * AJAX: Saves log pruning settings.
     */
    public function ajax_save_log_settings()
    {
        $permission_check = $this->check_module_access_permissions('logs', 'aipkit_save_log_settings_nonce');
        if (is_wp_error($permission_check)) {
            $this->send_wp_error($permission_check);
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked above
        $post_data = wp_unslash($_POST);

        $enable_pruning = isset($post_data['enable_pruning']) && $post_data['enable_pruning'] === '1';
        $retention_period = isset($post_data['retention_period_days']) ? floatval($post_data['retention_period_days']) : 90;


        // Validate retention period using centralized config
        if (!LogConfig::is_valid_period($retention_period)) {
            $retention_period = 90; // Default if invalid value is submitted
        }

        // Pro plan check: Only allow enabling pruning if user has pro plan
        $is_pro = aipkit_dashboard::is_pro_plan();
        if ($enable_pruning && !$is_pro) {
            // If user tries to enable pruning without pro plan, keep it disabled
            $enable_pruning = false;
        }

        $settings = [
            'enable_pruning' => $enable_pruning,
            'retention_period_days' => $retention_period,
        ];

        update_option('aipkit_log_settings', $settings, 'no');

        // Schedule or unschedule the cron job based on the new setting and pro status
        if (class_exists(LogCronManager::class)) {
            if ($enable_pruning && $is_pro) {
                LogCronManager::schedule_event();
            } else {
                LogCronManager::unschedule_event(); // Unschedule if disabled OR not pro
            }
        }

        $message = __('Log settings saved successfully.', 'gpt3-ai-content-generator');
        if (!$is_pro && isset($post_data['enable_pruning']) && $post_data['enable_pruning'] === '1') {
            $message .= ' ' . __('Note: Auto-delete feature requires Pro plan.', 'gpt3-ai-content-generator');
        }

        wp_send_json_success(['message' => $message]);
    }

    /**
     * AJAX: Manually triggers the log pruning process.
     */
    public function ajax_prune_logs_now()
    {
        $permission_check = $this->check_module_access_permissions('logs', 'aipkit_save_log_settings_nonce');
        if (is_wp_error($permission_check)) {
            $this->send_wp_error($permission_check);
            return;
        }

        // --- NEW: Pro Check ---
        if (!aipkit_dashboard::is_pro_plan()) {
            $this->send_wp_error(new WP_Error('pro_feature_required', __('Manual log pruning is a Pro feature. Please upgrade.', 'gpt3-ai-content-generator')), 403);
            return;
        }
        // --- END NEW ---

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked above
        $post_data = wp_unslash($_POST);
        $retention_period = isset($post_data['retention_period_days']) ? floatval($post_data['retention_period_days']) : 90;

        if ($retention_period <= 0) {
            $this->send_wp_error(new WP_Error('invalid_period', __('Invalid retention period provided.', 'gpt3-ai-content-generator')), 400);
            return;
        }

        if (!class_exists(LogManager::class)) {
            $this->send_wp_error(new WP_Error('dependency_missing', __('Log manager component is unavailable.', 'gpt3-ai-content-generator')), 500);
            return;
        }

        $log_manager = new LogManager();
        $deleted_rows = $log_manager->prune_logs($retention_period);

        if ($deleted_rows === false) {
            $this->send_wp_error(new WP_Error('pruning_failed', __('An error occurred while pruning the logs.', 'gpt3-ai-content-generator')), 500);
        } else {
            // Update last run time for manual pruning
            update_option('aipkit_log_pruning_last_run', current_time('mysql', true), 'no');
            
            /* translators: %d: The number of log entries that were deleted. */
            $message = sprintf(_n('%d log entry pruned.', '%d log entries pruned.', $deleted_rows, 'gpt3-ai-content-generator'), number_format_i18n($deleted_rows));
            wp_send_json_success(['message' => $message, 'deleted_count' => $deleted_rows]);
        }
    }


    /** Extracts and sanitizes log filters from POST data. */
    private function extract_log_filters_from_post(array $post_data): array
    {
        $filters = [];
        // Check only if filter keys are explicitly sent and non-empty
        if (isset($post_data['filter_user_name']) && $post_data['filter_user_name'] !== '') {
            $filters['user_name'] = sanitize_text_field(wp_unslash($post_data['filter_user_name']));
        }
        if (isset($post_data['filter_chatbot_id']) && $post_data['filter_chatbot_id'] !== '') {
            $bot_filter = trim($post_data['filter_chatbot_id']);
            if ($bot_filter === '0') {
                $filters['bot_id'] = 0; // Explicitly filter for "No Bot"
            } else {
                $filters['bot_id'] = absint($bot_filter);
            }
        }
        if (isset($post_data['filter_message_like']) && $post_data['filter_message_like'] !== '') {
            $filters['message_like'] = sanitize_text_field(wp_unslash($post_data['filter_message_like']));
        }

        return $filters;
    }

    /**
     * Converts an array into a CSV-formatted string line.
     * Uses a memory stream which is the standard and efficient way to use fputcsv
     * without creating a physical file. WP_Filesystem is not applicable for php://memory.
     */
    private function array_to_csv_line(array $fields): string
    {
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen, WordPress.WP.AlternativeFunctions.file_system_operations_fclose
        $handle = fopen('php://memory', 'w');
        if (false === $handle) {
            return '';
        }

        if (fputcsv($handle, $fields) === false) {
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Reason: fclose is safe here
            fclose($handle);
            return '';
        }
        
        rewind($handle);
        $csv_line = stream_get_contents($handle);
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Reason: fclose is safe here
        fclose($handle);

        if (false === $csv_line) {
            return '';
        }

        // fputcsv appends a system-specific newline. rtrim and adding a consistent \n handles this.
        return rtrim($csv_line) . "\n";
    }

    /** AJAX: Retrieves conversation summaries for the admin log view. */
    public function ajax_get_chat_logs_html()
    {
        $permission_check = $this->check_module_access_permissions('logs');
        if (is_wp_error($permission_check)) {
            $this->send_wp_error($permission_check);
            return;
        }

        // phpcs:disable WordPress.Security.NonceVerification.Missing -- Reason: Nonce is checked correctly within the check_module_access_permissions() method.
        $current_page = isset($_POST['log_page']) ? absint($_POST['log_page']) : 1;
        $logs_per_page = 20;
        $offset = ($current_page - 1) * $logs_per_page;

        // FIX: Only extract filters if they are actually sent beyond just pagination
        $filters = [];
        $post_keys = array_keys($_POST);
        $filter_keys_present = array_filter($post_keys, function ($key) {
            return strpos($key, 'filter_') === 0;
        });
        if (!empty($filter_keys_present)) {
            $filters = $this->extract_log_filters_from_post($_POST);
        }

        // Fetch logs with potentially empty filters (for initial load)
        // Default sort order changed in LogManager::get_logs
        $logs = $this->log_storage->get_logs($filters, $logs_per_page, $offset);
        $total_logs = $this->log_storage->count_logs($filters);
        $total_pages = ($total_logs > 0) ? ceil($total_logs / $logs_per_page) : 0;
        $base_url = admin_url('admin-ajax.php?action=aipkit_get_chat_logs_html');

        ob_start();
        $partial_path = WPAICG_PLUGIN_DIR . 'admin/views/modules/logs/partials/logs-table.php';
        if (file_exists($partial_path)) {
            include $partial_path;
        } else {
            echo '<p style="color:red;">Error: Log table template file not found.</p>';
        }
        $html = ob_get_clean();

        wp_send_json_success(['html' => $html]);
    }

    /**
     * AJAX: Handles exporting chat messages based on filters.
     * Iterates through messages within each conversation.
     */
    public function ajax_export_chat_logs()
    {
        $permission_check = $this->check_module_access_permissions('logs');
        if (is_wp_error($permission_check)) {
            $this->send_wp_error($permission_check);
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Reason: Nonce verification is handled in check_module_access_permissions method.
        $page = isset($_POST['page']) ? absint($_POST['page']) : 0;
        $batch_size = 50;
        $total_conversations_known = isset($_POST['total_count']) ? absint($_POST['total_count']) : 0;
        $filters = $this->extract_log_filters_from_post($_POST);

        // *** FIX: Define offset based on page and batch size ***
        $offset = $page * $batch_size;

        try {
            $total_conversations = $total_conversations_known;
            if ($page === 0) {
                $total_conversations = $this->log_storage->count_logs($filters);
                if ($total_conversations === 0) {
                    wp_send_json_success(['csv_chunk' => '', 'is_last_batch' => true, 'total_count' => 0, 'exported_count' => 0]);
                    return;
                }
            }
            if ($total_conversations === 0 && $page > 0) {
                wp_send_json_success(['csv_chunk' => '', 'is_last_batch' => true, 'total_count' => 0, 'exported_count' => $total_conversations_known]);
                return;
            }

            // *** Pass the calculated offset ***
            $conversations = $this->log_storage->get_raw_conversations_for_export($filters, $batch_size, $offset);

            if (empty($conversations) && $page === 0) {
                wp_send_json_success(['csv_chunk' => '', 'is_last_batch' => true, 'total_count' => 0, 'exported_count' => 0]);
                return;
            }

            $csv_chunk = '';
            if ($page === 0) {
                $headers = [
                    'Conversation Parent ID', 'Message ID', 'Conversation UUID', 'Bot Name', // Removed Module
                    'User ID', 'User Name', 'Guest Session ID', 'Message Timestamp', 'Message Role',
                    'Message Content', 'AI Provider', 'AI Model', 'IP Address',
                    'Feedback', 'Input Tokens', 'Output Tokens', 'Total Tokens',
                    'Usage Details JSON'
                ];
                $csv_chunk .= $this->array_to_csv_line($headers);
            }

            $conversations_processed_in_batch = 0;
            foreach ($conversations as $conv) {
                $bot_name = $conv['bot_name'] ?? __('(Unknown Bot)', 'gpt3-ai-content-generator');
                // $module_name = $conv['module'] ?? ''; // Module name no longer needed in export
                $user_display_name = $conv['user_display_name'] ?? __('(Unknown User)', 'gpt3-ai-content-generator');
                $conversation_uuid = $conv['conversation_uuid'] ?? '';
                $user_id = $conv['user_id'] ?? '';
                $session_id = $conv['session_id'] ?? '';
                $ip_address = $conv['ip_address'] ?? '';

                $conversation_data = json_decode($conv['messages'] ?? '[]', true);
                $parent_id = '';
                $messages_array = [];

                if (is_array($conversation_data) && isset($conversation_data['parent_id']) && isset($conversation_data['messages']) && is_array($conversation_data['messages'])) {
                    $parent_id = $conversation_data['parent_id'];
                    $messages_array = $conversation_data['messages'];
                } elseif (is_array($conversation_data)) {
                    $messages_array = $conversation_data;
                }

                if (is_array($messages_array)) {
                    foreach ($messages_array as $msg) {
                        $usage = $msg['usage'] ?? null;
                        $input_tokens = $usage['input_tokens'] ?? ($usage['promptTokenCount'] ?? '');
                        $output_tokens = $usage['output_tokens'] ?? ($usage['candidatesTokenCount'] ?? '');
                        $total_tokens = $usage['total_tokens'] ?? ($usage['totalTokenCount'] ?? '');
                        $usage_details_json = $usage ? wp_json_encode($usage, JSON_UNESCAPED_UNICODE) : '';

                        $csv_row = [
                           $parent_id, $msg['message_id'] ?? '', $conversation_uuid, $bot_name, // $module_name removed
                           $user_id, $user_display_name, $session_id, isset($msg['timestamp']) ? wp_date('Y-m-d H:i:s', $msg['timestamp']) : '',
                           $msg['role'] ?? '', $msg['content'] ?? '', $msg['provider'] ?? '', $msg['model'] ?? '',
                           $ip_address, $msg['feedback'] ?? '', $input_tokens, $output_tokens, $total_tokens,
                           $usage_details_json,
                        ];
                        $csv_chunk .= $this->array_to_csv_line($csv_row);
                    }
                }
                $conversations_processed_in_batch++;
            }

            $conversations_processed_so_far = ($page * $batch_size) + $conversations_processed_in_batch;
            $is_last_batch = ($conversations_processed_so_far >= $total_conversations);

            wp_send_json_success([
                'csv_chunk' => $csv_chunk,
                'is_last_batch' => $is_last_batch,
                'total_count' => $total_conversations,
                'exported_count' => $conversations_processed_so_far
            ]);
        } catch (\Exception $e) {
            wp_send_json_error(['message' => 'Export error: ' . $e->getMessage()], 500);
        }
    }


    /** AJAX: Handles deleting chat messages based on filters. */
    public function ajax_delete_chat_logs()
    {
        $permission_check = $this->check_module_access_permissions('logs');
        if (is_wp_error($permission_check)) {
            $this->send_wp_error($permission_check);
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Reason: Nonce is checked correctly within the check_module_access_permissions() method.
        $page = isset($_POST['page']) ? absint($_POST['page']) : 0;
        $batch_size = 100;
        $total_count_known = isset($_POST['total_count']) ? absint($_POST['total_count']) : 0;
        $deleted_so_far = isset($_POST['deleted_count']) ? absint($_POST['deleted_count']) : 0;
        $filters = $this->extract_log_filters_from_post($_POST);

        try {
            $total_conversations_to_delete = $total_count_known;
            if ($page === 0) {
                $total_conversations_to_delete = $this->log_storage->count_logs($filters);
                if ($total_conversations_to_delete === 0) {
                    wp_send_json_success(['deleted_total' => 0, 'is_last_batch' => true, 'total_count' => 0]);
                    return;
                }
            }
            if ($total_conversations_to_delete === 0 && $page > 0) {
                wp_send_json_success(['deleted_total' => $deleted_so_far, 'is_last_batch' => true, 'total_count' => 0]);
                return;
            }

            $deleted_in_this_batch = $this->log_storage->delete_logs($filters, $batch_size);

            if ($deleted_in_this_batch === false) {
                throw new \Exception(__('Database error during log deletion.', 'gpt3-ai-content-generator'));
            }

            $new_deleted_total = $deleted_so_far + $deleted_in_this_batch;
            $is_last_batch = ($deleted_in_this_batch < $batch_size) || ($total_conversations_to_delete > 0 && $new_deleted_total >= $total_conversations_to_delete);

            wp_send_json_success(['deleted_total' => $new_deleted_total, 'is_last_batch' => $is_last_batch, 'total_count'   => $total_conversations_to_delete]);
        } catch (\Exception $e) {
            wp_send_json_error(['message' => __('Deletion error:', 'gpt3-ai-content-generator') . ' ' . $e->getMessage()], 500);
        }
    }

    /**
     * AJAX: Gets the current cron status for log pruning.
     */
    public function ajax_get_log_cron_status()
    {
        $permission_check = $this->check_module_access_permissions('logs');
        if (is_wp_error($permission_check)) {
            $this->send_wp_error($permission_check);
            return;
        }

        $html = LogStatusRenderer::render_cron_status_panel();
        wp_send_json_success(['html' => $html]);
    }
}