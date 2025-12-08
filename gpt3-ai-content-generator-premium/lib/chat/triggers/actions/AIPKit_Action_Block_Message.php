<?php

namespace WPAICG\Lib\Chat\Triggers\Actions; // UPDATED Namespace

use WPAICG\Lib\Chat\Triggers\Actions\AIPKit_Placeholder_Processor; // Ensure this is the correct new namespace if Placeholder_Processor is also in Lib. Assuming it is.

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * AIPKit_Action_Block_Message
 *
 * Handles the 'block_message' trigger action.
 */
class AIPKit_Action_Block_Message {

    /**
     * Executes the 'block_message' action.
     *
     * @param array $payload Payload optionally containing 'reason'.
     * @param array $context_data Contextual data.
     * @param int   $bot_id Bot ID.
     * @return array Action result.
     */
    public function execute(array $payload, array $context_data, int $bot_id): array {
        $reason_template = $payload['reason'] ?? __('Your message could not be processed at this time.', 'gpt3-ai-content-generator');
        // Ensure PlaceholderProcessor class exists before calling its static method
        if (!class_exists(\WPAICG\Lib\Chat\Triggers\Actions\AIPKit_Placeholder_Processor::class)) {
             // This should be loaded by Triggers_Dependencies_Loader. Log an error if not found.
             // Fallback to un-processed reason if processor is missing
             $processed_reason = $reason_template;
        } else {
            $processed_reason = AIPKit_Placeholder_Processor::process($reason_template, $context_data);
        }


        return [
            'type'                   => 'block_message',
            'reason'                 => $processed_reason,
            'stop_ai'                => true, 
            'block_further_triggers' => true, 
        ];
    }
}