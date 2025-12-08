<?php

namespace WPAICG\Lib\Chat\Triggers\Actions; // UPDATED Namespace

use WPAICG\Lib\Chat\Triggers\Actions\AIPKit_Placeholder_Processor; // Ensure this is the correct new namespace if Placeholder_Processor is also in Lib.
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * AIPKit_Action_Bot_Reply
 *
 * Handles the 'bot_reply' trigger action.
 */
class AIPKit_Action_Bot_Reply {

    /**
     * Executes the 'bot_reply' action.
     *
     * @param array $payload Payload containing 'message' and 'stop_processing_ai'.
     * @param array $context_data Contextual data.
     * @param int   $bot_id Bot ID.
     * @return array|WP_Error Action result.
     */
    public function execute(array $payload, array $context_data, int $bot_id): array|WP_Error {
        $message_template = $payload['message'] ?? '';
        $stop_ai = isset($payload['stop_processing_ai']) && $payload['stop_processing_ai'] === true;

        if (empty($message_template)) {
            return new WP_Error('missing_reply_message', __('Bot reply action is missing the message template.', 'gpt3-ai-content-generator'));
        }

        // Ensure PlaceholderProcessor class exists before calling its static method
        if (!class_exists(\WPAICG\Lib\Chat\Triggers\Actions\AIPKit_Placeholder_Processor::class)) {
             $processed_message = $message_template; // Fallback
        } else {
            $processed_message = AIPKit_Placeholder_Processor::process($message_template, $context_data);
        }

        return [
            'type'    => 'bot_reply',
            'message' => $processed_message,
            'stop_ai' => $stop_ai,
        ];
    }
}