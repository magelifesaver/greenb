<?php
// File: classes/core/stream/formatter/fn-send-sse-error.php

namespace WPAICG\Core\Stream\Formatter;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Sends an SSE 'error' or 'warning' event.
 *
 * @param \WPAICG\Core\Stream\Formatter\SSEResponseFormatter $formatterInstance The instance of the SSEResponseFormatter.
 * @param string $message The error or warning message.
 * @param bool $non_fatal True if it's a warning, false if it's a fatal error.
 * @return void
 */
function send_sse_error_logic(\WPAICG\Core\Stream\Formatter\SSEResponseFormatter $formatterInstance, string $message, bool $non_fatal = false): void {
    $event_type = $non_fatal ? 'warning' : 'error';
    $error_data = ['error' => $message];
    $error_id   = 'err-' . time();
    $formatterInstance->send_sse_event($event_type, $error_data, $error_id);
}