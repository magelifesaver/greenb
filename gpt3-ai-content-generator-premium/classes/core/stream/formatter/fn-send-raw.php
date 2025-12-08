<?php
// File: classes/core/stream/formatter/fn-send-raw.php

namespace WPAICG\Core\Stream\Formatter;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Sends raw SSE output and flushes.
 *
 * @param string $output The raw output to send.
 * @return void
 */
function send_raw_logic(string $output): void {
    if (connection_status() === CONNECTION_NORMAL) {
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SSE output, not HTML
        echo $output;
        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();
    }
}