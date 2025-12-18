<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/core/providers/openai/parse-sse.php
// Status: NEW FILE

namespace WPAICG\Core\Providers\OpenAI\Methods;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the parse_sse_chunk static method of OpenAIResponseParser.
 */
function parse_sse_chunk_logic_for_response_parser(string $sse_chunk, string &$current_buffer): array {
    $current_buffer .= $sse_chunk;
    $result = ['delta' => null, 'usage' => null, 'is_error' => false, 'is_warning' => false, 'is_done' => false, 'openai_response_id' => null];

    while (($line_end_pos = strpos($current_buffer, "\n\n")) !== false) {
        $event_block = substr($current_buffer, 0, $line_end_pos);
        $current_buffer = substr($current_buffer, $line_end_pos + 2);

        $event_data_json = null;
        $event_type = 'message';

        foreach (explode("\n", $event_block) as $line) {
             $line = rtrim($line, "\r");
             if (empty($line) || strpos($line, ':') === false) continue;
             [$field, $value] = explode(':', $line, 2);
             $field = trim($field);
             $value = trim($value);
            if ($field === 'event') $event_type = $value;
            elseif ($field === 'data') $event_data_json = $value;
        }

        if ($event_type === 'error' && $event_data_json) {
            $decoded_error = json_decode($event_data_json, true);
            $error_message = parse_error_logic_for_response_parser($decoded_error, 500); // Use the local error parser
            $result['delta'] = $error_message;
            $result['is_error'] = true;
            return $result;
        }

        if ($event_data_json) {
            $decoded_data = json_decode($event_data_json, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded_data)) {
                switch ($event_type) {
                    case 'response.output_text.delta':
                        $delta_text = $decoded_data['delta'] ?? '';
                        if ($delta_text !== '') {
                            if ($result['delta'] === null) $result['delta'] = '';
                            $result['delta'] .= $delta_text;
                        }
                        break;
                    case 'response.refusal.delta':
                        $refusal_text = $decoded_data['delta'] ?? '';
                        if ($refusal_text !== '') {
                            if ($result['delta'] === null) $result['delta'] = '';
                            $result['delta'] .= sprintf(' (%s: %s)', __('Refusal', 'gpt3-ai-content-generator'), $refusal_text);
                            $result['is_warning'] = true;
                        }
                        break;
                    case 'response.completed':
                    case 'response.incomplete':
                        $result['is_done'] = true;
                        if (isset($decoded_data['response']['usage']) && is_array($decoded_data['response']['usage'])) {
                            $result['usage'] = [
                                'input_tokens'  => $decoded_data['response']['usage']['input_tokens'] ?? 0,
                                'output_tokens' => $decoded_data['response']['usage']['output_tokens'] ?? 0,
                                'total_tokens'  => $decoded_data['response']['usage']['total_tokens'] ?? 0,
                                'provider_raw' => $decoded_data['response']['usage'],
                            ];
                        }
                        if ($event_type === 'response.incomplete') {
                            $reason = $decoded_data['response']['incomplete_details']['reason'] ?? 'unknown';
                            if ($result['delta'] === null) $result['delta'] = '';
                            $result['delta'] .= sprintf(' (%s: %s)', __('Incomplete', 'gpt3-ai-content-generator'), $reason);
                            $result['is_warning'] = true;
                        }
                        if (isset($decoded_data['response']['id'])) {
                            $result['openai_response_id'] = $decoded_data['response']['id'];
                        }
                        break;
                    case 'response.failed':
                        $error_message = $decoded_data['response']['error']['message'] ?? __('Response failed', 'gpt3-ai-content-generator');
                        if ($result['delta'] === null) $result['delta'] = '';
                        $result['delta'] .= sprintf(' (%s: %s)', __('Error', 'gpt3-ai-content-generator'), $error_message);
                        $result['is_error'] = true;
                        break;
                    // Ignore other event types like response.created, response.in_progress, etc.
                }
            }
        }
    }

    return $result;
}