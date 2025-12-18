<?php
// File: classes/core/stream/formatter/fn-set-headers.php

namespace WPAICG\Core\Stream\Formatter;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Sets the required HTTP headers for an SSE response.
 *
 * @param \WPAICG\Core\Stream\Formatter\SSEResponseFormatter $formatterInstance The instance of the SSEResponseFormatter.
 * @return void
 */
function set_sse_headers_logic(\WPAICG\Core\Stream\Formatter\SSEResponseFormatter $formatterInstance): void {
    // Access the headers_sent property via a getter/setter or make it public for this refactor.
    // For now, we assume a way to access/modify it.
    if ($formatterInstance->get_headers_sent_status() || headers_sent()) {
        return;
    }
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Content-Type: text/event-stream; charset=utf-8');
    header('X-Accel-Buffering: no');
    header('Connection: keep-alive');
    // phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged -- This is an SSE endpoint which requires a long-running script.
    set_time_limit(0);
    $formatterInstance->set_headers_sent_status(true);
}