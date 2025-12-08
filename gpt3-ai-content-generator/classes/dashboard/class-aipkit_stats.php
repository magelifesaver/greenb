<?php

namespace WPAICG\Stats; // Use a dedicated Stats namespace

use wpdb;
use WP_Error;
use DateTime;
use DateTimeZone;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Calculates statistics related to overall AI usage across different modules.
 * Includes filtering for top N modules in daily stats.
 */
class AIPKit_Stats
{ // Renamed from AIPKit_Chat_Stats to reflect broader scope

    private $wpdb;
    private $log_table_name;
    // Hard caps to prevent memory exhaustion on large sites; can be filtered.
    private const DEFAULT_MAX_BYTES_FOR_STATS = 64000000; // ~64 MB
    private const DEFAULT_MAX_ROWS_FOR_STATS  = 5000;       // cap rows to scan
    public const TOP_N_MODULES_FOR_CHART = 4; // Number of top modules to display individually in the chart

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->log_table_name = $wpdb->prefix . 'aipkit_chat_logs'; // Table name remains the same
    }

    /**
     * Estimate number of rows and total bytes for messages in range to avoid OOM.
     *
     * @param int $start_ts
     * @param int $end_ts
     * @return array{rows:int, bytes:int}|WP_Error
     */
    private function estimate_volume(int $start_ts, int $end_ts): array|WP_Error
    {
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Using prepare with timestamps
        $sql = $this->wpdb->prepare(
            "SELECT COUNT(*) AS cnt, COALESCE(SUM(CHAR_LENGTH(messages)), 0) AS total_bytes FROM {$this->log_table_name} WHERE last_message_ts >= %d AND last_message_ts <= %d",
            $start_ts,
            $end_ts
        );
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
        $row = $this->wpdb->get_row($sql, ARRAY_A);
        if ($this->wpdb->last_error) {
            return new WP_Error('db_query_error', __('Database error estimating log volume for stats.', 'gpt3-ai-content-generator'));
        }
        $rows  = isset($row['cnt']) ? (int) $row['cnt'] : 0;
        $bytes = isset($row['total_bytes']) ? (int) $row['total_bytes'] : 0;
        return ['rows' => $rows, 'bytes' => $bytes];
    }

    

    /**
     * Calculates token usage statistics across all modules for the last X days.
     *
     * @param int $days Number of past days to include.
     * @return array|WP_Error An array containing stats ['total_tokens', 'total_interactions', 'avg_tokens_per_interaction', 'module_counts', 'days_period'] or WP_Error.
     */
    public function get_token_stats_last_days(int $days = 30): array|WP_Error
    {
        if ($days <= 0) {
            return new WP_Error('invalid_days', __('Number of days must be positive.', 'gpt3-ai-content-generator'));
        }

        $wp_timezone = wp_timezone();
        $end_datetime = new DateTime('now', $wp_timezone);
        $start_datetime = new DateTime("-$days days", $wp_timezone);
        $start_datetime->setTime(0, 0, 0);

        $timestamp_threshold_start = $start_datetime->getTimestamp();
        $timestamp_threshold_end = $end_datetime->getTimestamp();

        // Guardrail: estimate volume and bail out if too heavy
        $vol = $this->estimate_volume($timestamp_threshold_start, $timestamp_threshold_end);
        if (is_wp_error($vol)) {
            return $vol;
        }
        $max_bytes = (int) apply_filters('aipkit_stats_max_bytes', self::DEFAULT_MAX_BYTES_FOR_STATS);
        $max_rows  = (int) apply_filters('aipkit_stats_max_rows', self::DEFAULT_MAX_ROWS_FOR_STATS);
        
        if ($vol['rows'] > $max_rows || $vol['bytes'] > $max_bytes) {
            return new WP_Error(
                'stats_volume_too_large',
                sprintf(
                    /* translators: 1: number of rows, 2: number of bytes */
                    __('Usage data volume is too large to compute statistics right now (rows: %1$s, bytes: %2$s). Try reducing retention or disabling conversation storage.', 'gpt3-ai-content-generator'),
                    number_format_i18n($vol['rows']),
                    size_format($vol['bytes'])
                ),
                [ 'rows' => (int) $vol['rows'], 'bytes' => (int) $vol['bytes'] ]
            );
        }

        // Query to fetch conversation logs within the date range based on last_message_ts
        // Select 'messages' and 'module' columns
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: Using prepare to safely insert timestamps
        $query = $this->wpdb->prepare("SELECT messages, module FROM {$this->log_table_name} WHERE last_message_ts >= %d AND last_message_ts <= %d", $timestamp_threshold_start, $timestamp_threshold_end);

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Reason: Using prepare to safely insert timestamps
        $results = $this->wpdb->get_results($query, ARRAY_A);

        if ($this->wpdb->last_error) {
            return new WP_Error('db_query_error', __('Database error fetching logs for stats.', 'gpt3-ai-content-generator'));
        }

        $total_tokens = 0;
        $total_interactions = count($results); // Count each log row as an interaction/conversation
        $module_counts = []; // Count how many interactions per module

        if ($total_interactions > 0) {
            foreach ($results as $row) {
                $module_name = !empty($row['module']) ? $row['module'] : 'unknown';
                $messages_json = $row['messages'] ?? '[]';
                $conversation_data = json_decode($messages_json, true);
                $messages_array = null;

                // Handle new/old structure
                if (is_array($conversation_data) && isset($conversation_data['messages']) && is_array($conversation_data['messages'])) {
                    $messages_array = $conversation_data['messages'];
                } elseif (is_array($conversation_data)) {
                    $messages_array = $conversation_data; // Backward compat
                }

                if (is_array($messages_array)) {
                    foreach ($messages_array as $msg) {
                        // Sum tokens from bot messages or wherever usage is logged
                        $tokens_in_message = 0;
                        if (isset($msg['usage']['total_tokens']) && is_numeric($msg['usage']['total_tokens'])) {
                            $tokens_in_message = (int) $msg['usage']['total_tokens'];
                        } elseif (isset($msg['usage']['totalTokenCount']) && is_numeric($msg['usage']['totalTokenCount'])) { // Google compatibility
                            $tokens_in_message = (int) $msg['usage']['totalTokenCount'];
                        }
                        $total_tokens += $tokens_in_message;
                    }
                }
                // Count the interaction for this module
                $module_counts[$module_name] = ($module_counts[$module_name] ?? 0) + 1;

            } // End foreach $results
        } // End if $total_interactions

        $avg_tokens_per_interaction = ($total_interactions > 0) ? round($total_tokens / $total_interactions) : 0;
        arsort($module_counts); // Sort modules by interaction count descending

        return [
            'total_tokens' => $total_tokens,
            'total_interactions' => $total_interactions, // Renamed from total_conversations
            'avg_tokens_per_interaction' => $avg_tokens_per_interaction, // Renamed
            'module_counts' => $module_counts, // Interaction counts per module
            'days_period' => $days,
        ];
    }

    /**
     * Calculates daily token usage grouped by the top N modules and 'Other' for the last X days.
     *
     * @param int $days Number of past days to include.
     * @return array|WP_Error An array ['YYYY-MM-DD' => ['top_module1' => tokens, 'top_moduleN' => tokens, 'Other' => tokens]] or WP_Error.
     */
    public function get_daily_token_stats(int $days = 30): array|WP_Error
    {
        if ($days <= 0) {
            return new WP_Error('invalid_days', __('Number of days must be positive.', 'gpt3-ai-content-generator'));
        }

        $wp_timezone = wp_timezone();
        $end_datetime = new DateTime('now', $wp_timezone);
        $start_datetime = new DateTime("-$days days", $wp_timezone);
        $start_datetime->setTime(0, 0, 0);

        $timestamp_threshold_start = $start_datetime->getTimestamp();
        $timestamp_threshold_end = $end_datetime->getTimestamp();

        // Guardrail: estimate volume and bail out if too heavy
        $vol = $this->estimate_volume($timestamp_threshold_start, $timestamp_threshold_end);
        if (is_wp_error($vol)) {
            return $vol;
        }
        $max_bytes = (int) apply_filters('aipkit_stats_max_bytes', self::DEFAULT_MAX_BYTES_FOR_STATS);
        $max_rows  = (int) apply_filters('aipkit_stats_max_rows', self::DEFAULT_MAX_ROWS_FOR_STATS);
        
        if ($vol['rows'] > $max_rows || $vol['bytes'] > $max_bytes) {
            return new WP_Error(
                'stats_volume_too_large',
                sprintf(
                    /* translators: 1: number of rows, 2: number of bytes */
                    __('Usage data volume is too large to compute daily chart (rows: %1$s, bytes: %2$s). Try reducing retention or disabling conversation storage.', 'gpt3-ai-content-generator'),
                    number_format_i18n($vol['rows']),
                    size_format($vol['bytes'])
                ),
                [ 'rows' => (int) $vol['rows'], 'bytes' => (int) $vol['bytes'] ]
            );
        }

        // Fetch logs within the range, including 'module' and 'messages'

        // Fetch logs within the range, including 'module' and 'messages'
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: Using prepare to safely insert timestamps
        $query = $this->wpdb->prepare("SELECT messages, module FROM {$this->log_table_name} WHERE last_message_ts >= %d AND last_message_ts <= %d", $timestamp_threshold_start, $timestamp_threshold_end);
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Reason: Using prepare to safely insert timestamps
        $results = $this->wpdb->get_results($query, ARRAY_A);

        if ($this->wpdb->last_error) {
            return new WP_Error('db_query_error', __('Database error fetching logs for daily stats.', 'gpt3-ai-content-generator'));
        }

        // --- Phase 1: Aggregate ALL daily tokens per module and total tokens per module ---
        $daily_tokens_all = [];
        $total_module_tokens = []; // Stores total tokens for each module across the period

        // Initialize all days in the period
        $current_date = clone $start_datetime;
        while ($current_date <= $end_datetime) {
            $daily_tokens_all[$current_date->format('Y-m-d')] = [];
            $current_date->modify('+1 day');
        }

        if (!empty($results)) {
            foreach ($results as $row) {
                $module_name = !empty($row['module']) ? $row['module'] : 'unknown';
                $messages_json = $row['messages'] ?? '[]';
                $conversation_data = json_decode($messages_json, true);
                $messages_array = null;
                if (is_array($conversation_data) && isset($conversation_data['messages']) && is_array($conversation_data['messages'])) {
                    $messages_array = $conversation_data['messages'];
                } elseif (is_array($conversation_data)) {
                    $messages_array = $conversation_data;
                }

                if (is_array($messages_array)) {
                    foreach ($messages_array as $msg) {
                        // Use timestamp of the individual message for daily grouping
                        if (isset($msg['timestamp']) && is_numeric($msg['timestamp'])) {
                            $message_timestamp = (int)$msg['timestamp'];
                            // Ensure message is within the overall date range (redundant check, but safe)
                            if ($message_timestamp < $timestamp_threshold_start || $message_timestamp > $timestamp_threshold_end) {
                                continue;
                            }

                            $message_date = new DateTime('@' . $message_timestamp);
                            $message_date->setTimezone($wp_timezone);
                            $date_key = $message_date->format('Y-m-d');

                            // Sum tokens for this message
                            $tokens_in_message = 0;
                            if (isset($msg['usage']['total_tokens']) && is_numeric($msg['usage']['total_tokens'])) {
                                $tokens_in_message = (int) $msg['usage']['total_tokens'];
                            } elseif (isset($msg['usage']['totalTokenCount']) && is_numeric($msg['usage']['totalTokenCount'])) {
                                $tokens_in_message = (int) $msg['usage']['totalTokenCount'];
                            }

                            if ($tokens_in_message > 0) {
                                // Add to daily count FOR THE MODULE
                                if (isset($daily_tokens_all[$date_key])) {
                                    $daily_tokens_all[$date_key][$module_name] = ($daily_tokens_all[$date_key][$module_name] ?? 0) + $tokens_in_message;
                                }
                                // Add to overall module total
                                $total_module_tokens[$module_name] = ($total_module_tokens[$module_name] ?? 0) + $tokens_in_message;
                            }
                        } // End timestamp check
                    } // End foreach message
                }
            } // End foreach result row
        } // End if results

        // --- Phase 2: Identify Top N modules ---
        arsort($total_module_tokens); // Sort modules by total usage descending
        $top_n_modules = array_slice(array_keys($total_module_tokens), 0, self::TOP_N_MODULES_FOR_CHART);
        $top_n_modules_set = array_flip($top_n_modules); // Use as a set for quick lookups

        // --- Phase 3: Create Filtered Daily Data with 'Other' category ---
        $filtered_daily_tokens = [];
        // Re-initialize all days
        $current_date = clone $start_datetime;
        while ($current_date <= $end_datetime) {
            $filtered_daily_tokens[$current_date->format('Y-m-d')] = [];
            $current_date->modify('+1 day');
        }

        foreach ($daily_tokens_all as $date => $modules_on_day) {
            $other_tokens_for_day = 0;
            foreach ($modules_on_day as $module => $tokens) {
                if (isset($top_n_modules_set[$module])) {
                    $filtered_daily_tokens[$date][$module] = $tokens;
                } else {
                    $other_tokens_for_day += $tokens;
                }
            }
            if ($other_tokens_for_day > 0) {
                $filtered_daily_tokens[$date]['Other'] = $other_tokens_for_day;
            }
        }

        ksort($filtered_daily_tokens); // Ensure outer array is sorted by date key

        return $filtered_daily_tokens;
    }


    /**
     * Fast path: Returns only interactions count and per-module counts for last X days.
     * Avoids loading `messages` longtext to keep memory low.
     *
     * @param int $days
     * @return array|WP_Error ['total_interactions' => int, 'module_counts' => array, 'days_period' => int]
     */
    public function get_quick_interaction_stats(int $days = 3): array|WP_Error
    {
        if ($days <= 0) {
            return new WP_Error('invalid_days', __('Number of days must be positive.', 'gpt3-ai-content-generator'));
        }

        $wp_timezone = wp_timezone();
        $end_datetime = new DateTime('now', $wp_timezone);
        $start_datetime = new DateTime("-$days days", $wp_timezone);
        $start_datetime->setTime(0, 0, 0);

        $timestamp_threshold_start = $start_datetime->getTimestamp();
        $timestamp_threshold_end = $end_datetime->getTimestamp();

        // Count total interactions
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Using prepare with timestamps
        $count_sql = $this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->log_table_name} WHERE last_message_ts >= %d AND last_message_ts <= %d",
            $timestamp_threshold_start,
            $timestamp_threshold_end
        );
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
        $total_interactions = (int) $this->wpdb->get_var($count_sql);
        if ($this->wpdb->last_error) {
            return new WP_Error('db_query_error', __('Database error fetching counts for quick stats.', 'gpt3-ai-content-generator'));
        }

        // Per-module counts
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Using prepare with timestamps
        $group_sql = $this->wpdb->prepare(
            "SELECT COALESCE(module,'unknown') as module, COUNT(*) as cnt FROM {$this->log_table_name} WHERE last_message_ts >= %d AND last_message_ts <= %d GROUP BY module",
            $timestamp_threshold_start,
            $timestamp_threshold_end
        );
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
        $rows = $this->wpdb->get_results($group_sql, ARRAY_A) ?: [];
        if ($this->wpdb->last_error) {
            return new WP_Error('db_query_error', __('Database error fetching module counts for quick stats.', 'gpt3-ai-content-generator'));
        }
        $module_counts = [];
        foreach ($rows as $r) {
            $module = $r['module'] !== null && $r['module'] !== '' ? $r['module'] : 'unknown';
            $module_counts[$module] = (int) $r['cnt'];
        }
        arsort($module_counts);

        return [
            'total_interactions' => $total_interactions,
            'module_counts' => $module_counts,
            'days_period' => $days,
        ];
    }

    /**
     * Gets the most used module from the calculated module counts.
     *
     * @param array $module_counts Associative array of module => count.
     * @return string|null The name of the most used module, or null if none.
     */
    public function get_most_used_module(array $module_counts): ?string
    {
        if (empty($module_counts)) {
            return null;
        }
        // arsort($module_counts); // Ensure sorted (should be done in get_token_stats_last_days)
        $first_key = array_key_first($module_counts);
        if (!$first_key) {
            return null;
        }

        // Map known module slugs to user-facing names
        switch ($first_key) {
            case 'ai_post_enhancer':
                return __('Content Assistant', 'gpt3-ai-content-generator');
            default:
                // Format the module name nicely for display
                return ucfirst(str_replace(['-', '_'], ' ', $first_key));
        }
    }
}
