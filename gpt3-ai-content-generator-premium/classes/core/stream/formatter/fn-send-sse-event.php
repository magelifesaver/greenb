<?php
// File: classes/core/stream/formatter/fn-send-sse-event.php

namespace WPAICG\Core\Stream\Formatter;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Sends a custom SSE event.
 *
 * @param \WPAICG\Core\Stream\Formatter\SSEResponseFormatter $formatterInstance The instance of the SSEResponseFormatter.
 * @param string $event_type e.g. 'done', 'error', 'warning', 'message_start'
 * @param array|string $data Data for the event.
 * @param string|null $id Optional event ID.
 * @return void
 */
function send_sse_event_logic(\WPAICG\Core\Stream\Formatter\SSEResponseFormatter $formatterInstance, string $event_type, $data, ?string $id = null): void {
    $json_data = is_string($data) ? $data : json_encode($data, JSON_UNESCAPED_UNICODE);
    $output = "event: {$event_type}\n";
    if ($id !== null) {
        $output .= "id: {$id}\n";
    }
    $output .= "data: {$json_data}\n\n";
    // Call the private send_raw method via a public wrapper in the main class or make send_raw public temporarily
    // For this refactor, assuming send_raw will be made callable.
    $formatterInstance->send_raw_public_wrapper($output);
}