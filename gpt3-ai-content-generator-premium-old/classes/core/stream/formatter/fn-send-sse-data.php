<?php
// File: classes/core/stream/formatter/fn-send-sse-data.php

namespace WPAICG\Core\Stream\Formatter;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Sends a standard SSE data event.
 *
 * @param \WPAICG\Core\Stream\Formatter\SSEResponseFormatter $formatterInstance The instance of the SSEResponseFormatter.
 * @param array|string $data Data to send (JSON-encoded if array).
 * @param string|null $id Optional event ID.
 * @return void
 */
function send_sse_data_logic(\WPAICG\Core\Stream\Formatter\SSEResponseFormatter $formatterInstance, $data, ?string $id = null): void {
    $json_data = is_string($data) ? $data : json_encode($data, JSON_UNESCAPED_UNICODE);
    $output = '';
    if ($id !== null) {
        $output .= "id: {$id}\n";
    }
    $output .= "data: {$json_data}\n\n";
    // Call the private send_raw method via a public wrapper in the main class or make send_raw public temporarily
    $formatterInstance->send_raw_public_wrapper($output);
}