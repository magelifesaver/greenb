<?php
// File: classes/core/stream/formatter/fn-send-sse-done.php

namespace WPAICG\Core\Stream\Formatter;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Sends a final SSE 'done' event.
 *
 * @param \WPAICG\Core\Stream\Formatter\SSEResponseFormatter $formatterInstance The instance of the SSEResponseFormatter.
 * @return void
 */
function send_sse_done_logic(\WPAICG\Core\Stream\Formatter\SSEResponseFormatter $formatterInstance): void {
    $formatterInstance->send_sse_event('done', ['finished' => true]);
}