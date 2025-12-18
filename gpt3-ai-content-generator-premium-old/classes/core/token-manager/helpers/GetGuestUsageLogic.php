<?php
// File: classes/core/token-manager/helpers/GetGuestUsageLogic.php

namespace WPAICG\Core\TokenManager\Helpers;

use WPAICG\Core\TokenManager\AIPKit_Token_Manager;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic to get current token usage and last reset time for a guest.
 *
 * @param AIPKit_Token_Manager $managerInstance The instance of AIPKit_Token_Manager.
 * @param string $session_id The guest's session ID.
 * @param int $guest_context_table_id The context ID for the guest table (bot_id or module identifier).
 * @return array ['current_usage' => int, 'last_reset_time' => int]
 */
function GetGuestUsageLogic(AIPKit_Token_Manager $managerInstance, string $session_id, int $guest_context_table_id): array {
    global $wpdb;
    $guest_table_name = $managerInstance->get_guest_table_name();
    $current_usage = 0;
    $last_reset_time = 0;

    // Define a unique cache key for this specific guest and context.
    $cache_key = "aipkit_guest_usage_{$session_id}_{$guest_context_table_id}";
    $cache_group = 'aipkit_token_usage';

    // Try to get the cached result first.
    $guest_row = wp_cache_get($cache_key, $cache_group);

    if (false === $guest_row) {
        // Cache miss: Query the database.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: Custom table query; unavoidable for this functionality.
        $guest_row = $wpdb->get_row($wpdb->prepare("SELECT tokens_used, last_reset_timestamp FROM {$guest_table_name} WHERE session_id = %s AND bot_id = %d",
            $session_id, $guest_context_table_id
        ), ARRAY_A);

        // Cache the result to avoid future database hits.
        // Cache even if it's null/false to prevent repeated queries for non-existent rows.
        wp_cache_set($cache_key, $guest_row, $cache_group, 300); // Cache for 5 minutes.
    }


    if ($guest_row) {
        $current_usage = (int) $guest_row['tokens_used'];
        $last_reset_time = (int) $guest_row['last_reset_timestamp'];
    }
    return ['current_usage' => $current_usage, 'last_reset_time' => $last_reset_time];
}