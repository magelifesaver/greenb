<?php
// File: classes/core/stream/cache/fn-unschedule-cleanup-event.php

namespace WPAICG\Core\Stream\Cache;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Unschedules the cache cleanup event.
 *
 * @param string $cron_hook_const The cron hook constant.
 * @return void
 */
function unschedule_cleanup_event_logic(string $cron_hook_const): void {
    $timestamp = wp_next_scheduled($cron_hook_const);
    if ($timestamp) {
        wp_unschedule_event($timestamp, $cron_hook_const);
    }
    remove_action($cron_hook_const, ['WPAICG\Core\Stream\Cache\AIPKit_SSE_Message_Cache', 'run_db_cleanup_static_wrapper']);
}