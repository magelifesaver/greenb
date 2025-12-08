<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/core/stream/contexts/chat/process/construct-sse-processor-input.php
// Status: NEW FILE

namespace WPAICG\Core\Stream\Contexts\Chat\Process;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Assembles the final array expected by SSEStreamProcessor::start_stream.
 *
 * @param array $request_data_for_ai Data prepared by build_ai_request_data_for_stream_logic.
 * @param string $conversation_uuid The UUID of the conversation.
 * @param array $base_log_data Base data for logging.
 * @param string $bot_message_id The generated ID for the bot's message.
 * @param array|null $initial_trigger_reply_data Optional data for an initial reply from triggers.
 * @return array The final input array for SSEStreamProcessor.
 */
function construct_sse_processor_input_logic(
    array $request_data_for_ai,
    string $conversation_uuid,
    array $base_log_data,
    string $bot_message_id,
    ?array $initial_trigger_reply_data
): array {
    $return_data = [
        'provider'                      => $request_data_for_ai['provider'],
        'model'                         => $request_data_for_ai['model'],
        'user_message'                  => $request_data_for_ai['user_message'],
        'history'                       => $request_data_for_ai['history'],
        'system_instruction_filtered'   => $request_data_for_ai['system_instruction_filtered'],
        'api_params'                    => $request_data_for_ai['api_params'],
        'ai_params'                     => $request_data_for_ai['ai_params'],
        'vector_search_scores'          => $request_data_for_ai['vector_search_scores'] ?? [], // Include vector search scores
        'conversation_uuid'             => $conversation_uuid,
        'base_log_data'                 => $base_log_data, // This already includes bot_message_id for the upcoming bot reply
        'bot_message_id'                => $bot_message_id, // Explicitly pass it as well
    ];
    
    if ($initial_trigger_reply_data) {
        $return_data['initial_trigger_reply_data'] = $initial_trigger_reply_data;
    }
    return $return_data;
}
