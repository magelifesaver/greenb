<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/core/stream/processor/fn-log-bot-error.php
// Status: MODIFIED

namespace WPAICG\Core\Stream\Processor;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logs an error message for the bot response.
 *
 * @param \WPAICG\Core\Stream\Processor\SSEStreamProcessor $processorInstance The instance of the processor class.
 * @param string $error_message The error message to log.
 * @return void
 */
function log_bot_error_logic(\WPAICG\Core\Stream\Processor\SSEStreamProcessor $processorInstance, string $error_message): void {
    $log_storage = $processorInstance->get_log_storage();
    $base_log_data = $processorInstance->get_log_base_data();
    $current_bot_message_id = $processorInstance->get_current_bot_message_id();
    $current_provider = $processorInstance->get_current_provider();
    $current_model = $processorInstance->get_current_model();
    $request_payload_log = $processorInstance->get_request_payload_log();
    $current_stream_context = $processorInstance->get_current_stream_context();
    $current_conversation_uuid = $processorInstance->get_current_conversation_uuid();
    $current_openai_response_id = $processorInstance->get_current_openai_response_id();
    $used_previous_openai_response_id = $processorInstance->get_used_previous_openai_response_id_status();
    $grounding_metadata = $processorInstance->get_grounding_metadata();

    if (!$log_storage) { 
        return;
    }

    if (!empty($log_base_data) && !empty($current_bot_message_id)) {
         $log_error_data = array_merge($log_base_data, [
            'message_role'    => 'bot',
            'message_content' => "Error: " . $error_message,
            'timestamp'       => time(),
            'ai_provider'     => $current_provider,
            'ai_model'        => $current_model,
            'usage'           => null,
            'message_id'      => $current_bot_message_id, 
            'request_payload' => $request_payload_log,
         ]);
         
         if ($current_provider === 'OpenAI') {
            if ($current_openai_response_id) $log_error_data['openai_response_id'] = $current_openai_response_id;
            if ($used_previous_openai_response_id) $log_error_data['used_previous_response_id'] = true;
        }
        if ($current_provider === 'Google' && $grounding_metadata !== null) {
            $log_error_data['grounding_metadata'] = $grounding_metadata;
        }
         $log_storage->log_message($log_error_data);
    }
}