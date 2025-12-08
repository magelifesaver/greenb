<?php
// File: classes/core/stream/cache/fn-schedule-cleanup-event.php

namespace WPAICG\Core\Stream\Cache;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Schedules the hourly cache cleanup event if not already scheduled.
 *
 * @param string $cron_hook_const The cron hook constant.
 * @return void
 */
function schedule_cleanup_event_logic(string $cron_hook_const): void {
    if (!wp_next_scheduled($cron_hook_const)) {
        wp_schedule_event(time(), 'hourly', $cron_hook_const);
    }
    if (!has_action($cron_hook_const, ['WPAICG\Core\Stream\Cache\AIPKit_SSE_Message_Cache', 'run_db_cleanup_static_wrapper'])) {
        add_action($cron_hook_const, ['WPAICG\Core\Stream\Cache\AIPKit_SSE_Message_Cache', 'run_db_cleanup_static_wrapper']);
    }
}