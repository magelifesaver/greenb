<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/lib/chat/triggers/manager/event-processor/summarize_payload_for_log.php
// Status: NEW FILE

namespace WPAICG\Lib\Chat\Triggers\Manager\EventProcessorMethods;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Creates a non-sensitive summary of the action payload for logging.
 * This logic was moved from AIPKit_Trigger_Event_Processor::summarize_payload_for_log().
 *
 * @param string $action_type
 * @param array $payload
 * @return array
 */
function summarize_payload_for_log_logic(string $action_type, array $payload): array {
    $summary = [];
    switch ($action_type) {
        case 'bot_reply':
            $summary['message_snippet'] = mb_substr($payload['message'] ?? '', 0, 30) . '...';
            $summary['stop_ai'] = $payload['stop_processing_ai'] ?? false;
            break;
        case 'inject_context':
            $summary['placement'] = $payload['placement'] ?? 'unknown';
            $summary['content_snippet'] = mb_substr($payload['content'] ?? '', 0, 30) . '...';
            break;
        case 'block_message':
            $summary['reason_snippet'] = mb_substr($payload['reason'] ?? '', 0, 30) . '...';
            break;
        case 'call_webhook':
            $summary['endpoint_url'] = filter_var($payload['endpoint_url'] ?? '', FILTER_VALIDATE_URL) ? $payload['endpoint_url'] : 'Invalid URL';
            $summary['http_method'] = $payload['http_method'] ?? 'POST';
            $summary['has_headers'] = !empty($payload['headers']);
            $summary['has_body_template'] = !empty($payload['body_template']);
            break;
        case 'set_variable':
            $summary['scope'] = $payload['scope'] ?? 'unknown';
            $summary['key'] = $payload['key'] ?? 'unknown';
            // Value is intentionally omitted for security/privacy unless explicitly designed otherwise.
             $summary['value_snippet'] = mb_substr($payload['value'] ?? '', 0, 30) . ((mb_strlen($payload['value'] ?? '') > 30) ? '...' : '');
            break;
        case 'display_form':
             $summary['form_id'] = $payload['form_id'] ?? 'unknown';
             $summary['title_snippet'] = mb_substr($payload['title'] ?? '', 0, 30) . '...';
             $summary['element_count'] = count($payload['elements'] ?? []);
             break;
        default:
            $summary['details'] = 'Payload summary not available for this action type.';
            break;
    }
    return $summary;
}