<?php
// File: classes/core/providers/google/parse-sse.php
// Status: NEW FILE

namespace WPAICG\Core\Providers\Google\Methods;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the parse_sse_chunk static method of GoogleResponseParser.
 * UPDATED: Include groundingMetadata in the result.
 *
 * @param string $sse_chunk The raw chunk received.
 * @param string &$current_buffer Reference to the incomplete buffer.
 * @return array Result containing delta, usage, flags, and grounding_metadata.
 */
function parse_sse_chunk_logic_for_response_parser(string $sse_chunk, string &$current_buffer): array {
    $current_buffer .= $sse_chunk;
    $result = ['delta' => null, 'usage' => null, 'is_error' => false, 'is_warning' => false, 'is_done' => false, 'grounding_metadata' => null];

    while (($line_end_pos = strpos($current_buffer, "\n")) !== false) {
        $line = substr($current_buffer, 0, $line_end_pos + 1);
        $current_buffer = substr($current_buffer, $line_end_pos + 1);
        $line = rtrim($line, "\r\n");

        if (empty($line) || strpos($line, ':') === 0 || strpos($line, 'data:') !== 0) continue;

        $jsonLine = trim(substr($line, 5));
        if ($jsonLine === '') continue;

        $decoded = json_decode($jsonLine, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            if (isset($decoded['error']['message'])) {
                $result['delta'] = parse_error_logic_for_response_parser($decoded, 500); 
                $result['is_error'] = true;
                return $result;
            }
            if (isset($decoded['promptFeedback']['blockReason'])) {
                if ($result['delta'] === null) $result['delta'] = '';
                $result['delta'] .= sprintf(' (%s: %s)', __('Warning', 'gpt3-ai-content-generator'), $decoded['promptFeedback']['blockReason']);
                $result['is_warning'] = true;
            }
            if (isset($decoded['candidates'][0]['finishReason']) && $decoded['candidates'][0]['finishReason'] !== 'STOP') {
                 if ($result['delta'] === null) $result['delta'] = '';
                 $reason = $decoded['candidates'][0]['finishReason'];
                 if ($reason === 'SAFETY') {
                      $result['delta'] .= sprintf(' (%s: %s)', __('Warning', 'gpt3-ai-content-generator'), $decoded['candidates'][0]['safetyRatings'][0]['category'] ?? $reason);
                      $result['is_warning'] = true;
                 } else {
                      $result['delta'] .= sprintf(' (%s: %s)', __('Note', 'gpt3-ai-content-generator'), $reason);
                 }
            }
            if (isset($decoded['candidates'][0]['content']['parts'][0]['text'])) {
                if ($result['delta'] === null) $result['delta'] = '';
                $result['delta'] .= $decoded['candidates'][0]['content']['parts'][0]['text'];
            }
            if (isset($decoded['usageMetadata']) && is_array($decoded['usageMetadata'])) {
                 $result['usage'] = [
                     'input_tokens'  => $decoded['usageMetadata']['promptTokenCount'] ?? 0,
                     'output_tokens' => $decoded['usageMetadata']['candidatesTokenCount'] ?? 0,
                     'total_tokens'  => $decoded['usageMetadata']['totalTokenCount'] ?? 0,
                     'provider_raw' => $decoded['usageMetadata'],
                 ];
            }
            if (isset($decoded['candidates'][0]['groundingMetadata'])) {
                $result['grounding_metadata'] = $decoded['candidates'][0]['groundingMetadata'];
            }
        }
    }

    return $result;
}