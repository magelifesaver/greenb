<?php
// File: classes/core/providers/azure/format-embeddings.php

namespace WPAICG\Core\Providers\Azure\Methods;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the format_embeddings static method of AzurePayloadFormatter.
 *
 * @param string|array $input The input text or array of texts.
 * @param array  $options Embedding options (dimensions, user). Model is in URL.
 * @return array The formatted request body data.
 */
function format_embeddings_logic_for_payload_formatter($input, array $options): array {
    $payload = [
        'input' => $input,
    ];
    if (isset($options['dimensions']) && is_int($options['dimensions']) && $options['dimensions'] > 0) {
        $payload['dimensions'] = $options['dimensions'];
    }
    if (isset($options['user']) && is_string($options['user'])) {
        $payload['user'] = $options['user'];
    }
    return $payload;
}