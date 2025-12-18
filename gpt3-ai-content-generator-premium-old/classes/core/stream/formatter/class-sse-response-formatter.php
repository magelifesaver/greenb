<?php
// File: classes/core/stream/formatter/class-sse-response-formatter.php

namespace WPAICG\Core\Stream\Formatter;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Load method logic files
require_once __DIR__ . '/fn-set-headers.php';
require_once __DIR__ . '/fn-send-sse-data.php';
require_once __DIR__ . '/fn-send-sse-event.php';
require_once __DIR__ . '/fn-send-sse-error.php';
require_once __DIR__ . '/fn-send-sse-done.php';
require_once __DIR__ . '/fn-send-raw.php';

/**
 * Formats and sends Server-Sent Events (SSE) to the client.
 */
class SSEResponseFormatter {

    private $headers_sent = false;

    public function set_sse_headers() {
        set_sse_headers_logic($this);
    }

    public function send_sse_data($data, ?string $id = null) {
        send_sse_data_logic($this, $data, $id);
    }

    public function send_sse_event(string $event_type, $data, ?string $id = null) {
        send_sse_event_logic($this, $event_type, $data, $id);
    }

    public function send_sse_error(string $message, bool $non_fatal = false) {
        send_sse_error_logic($this, $message, $non_fatal);
    }

    public function send_sse_done() {
        send_sse_done_logic($this);
    }

    // Public wrapper for the private send_raw logic
    public function send_raw_public_wrapper(string $output): void {
        send_raw_logic($output);
    }

    // Getter and Setter for private property (needed by externalized logic)
    public function get_headers_sent_status(): bool {
        return $this->headers_sent;
    }

    public function set_headers_sent_status(bool $status): void {
        $this->headers_sent = $status;
    }
}