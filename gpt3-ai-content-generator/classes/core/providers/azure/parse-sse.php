<?php
// File: classes/core/providers/azure/parse-sse.php

namespace WPAICG\Core\Providers\Azure\Methods;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the parse_sse_chunk static method of AzureResponseParser.
 *
 * @param string $sse_chunk The raw chunk received.
 * @param string &$current_buffer Reference to the incomplete buffer.
 * @return array Result containing delta, usage, flags.
 */
function parse_sse_chunk_logic_for_response_parser(string $sse_chunk, string &$current_buffer): array {
    $current_buffer .= $sse_chunk;
    $result = ['delta' => null, 'usage' => null, 'is_error' => false, 'is_warning' => false, 'is_done' => false];

    while (($line_end_pos = strpos($current_buffer, "\n\n")) !== false) {
        $event_block = substr($current_buffer, 0, $line_end_pos);
        $current_buffer = substr($current_buffer, $line_end_pos + 2);

        $event_data_json = null;
        // Azure SSE typically only uses 'data:' lines, no explicit 'event:' type for content.
        foreach (explode("\n", $event_block) as $line) {
            $line = rtrim($line, "\r");
            if (empty($line) || strpos($line, 'data:') !== 0) continue;
            $event_data_json = trim(substr($line, 5));
            break;
        }

        if ($event_data_json === '[DONE]') {
            $result['is_done'] = true;
            continue;
        }

        if ($event_data_json) {
            $decoded = json_decode($event_data_json, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                if (isset($decoded['error'])) {
                    $result['delta'] = parse_error_logic_for_response_parser($decoded, 500);
                    $result['is_error'] = true;
                    return $result;
                }
                if (isset($decoded['usage']) && is_array($decoded['usage'])) {
                    $result['usage'] = [
                        'input_tokens'  => $decoded['usage']['prompt_tokens'] ?? 0,
                        'output_tokens' => $decoded['usage']['completion_tokens'] ?? 0,
                        'total_tokens'  => $decoded['usage']['total_tokens'] ?? 0,
                        'provider_raw' => $decoded['usage'],
                    ];
                }
                if (isset($decoded['choices'][0]['delta']['content'])) {
                    $delta_text = $decoded['choices'][0]['delta']['content'];
                    if ($result['delta'] === null) $result['delta'] = '';
                    $result['delta'] .= $delta_text;
                }
                if (isset($decoded['choices'][0]['finish_reason'])) {
                    $finish_reason = $decoded['choices'][0]['finish_reason'];
                    if ($finish_reason === 'content_filter') {
                        if ($result['delta'] === null) $result['delta'] = '';
                        $result['delta'] .= sprintf(' (%s)', __('Warning: Content Filtered', 'gpt3-ai-content-generator'));
                        $result['is_warning'] = true;
                    }
                }
            }
        }
    }
    return $result;
}