<?php

namespace WPAICG\Lib\Chat\Triggers\Actions; // UPDATED Namespace

use WPAICG\Lib\Chat\Triggers\Actions\AIPKit_Placeholder_Processor; // Ensure this is the correct new namespace if Placeholder_Processor is also in Lib.
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * AIPKit_Action_Inject_Context
 *
 * Handles the 'inject_context' trigger action.
 */
class AIPKit_Action_Inject_Context {

    /**
     * Executes the 'inject_context' action.
     *
     * @param array $payload Payload containing 'placement' (was 'type') and 'content'.
     * @param array $context_data Contextual data.
     * @param int   $bot_id Bot ID.
     * @return array|WP_Error Action result.
     */
    public function execute(array $payload, array $context_data, int $bot_id): array|WP_Error {
        $placement = $payload['placement'] ?? null; 
        $content_template = $payload['content'] ?? '';

        $valid_placements = ['system_instruction_prepend', 'system_instruction_append', 'history_prepend'];
        if (empty($placement) || !in_array($placement, $valid_placements, true)) {
            return new WP_Error('invalid_context_placement', __('Invalid or missing placement type for inject_context action.', 'gpt3-ai-content-generator'));
        }
        if (empty($content_template)) {
            return new WP_Error('missing_context_content', __('Content template is missing for inject_context action.', 'gpt3-ai-content-generator'));
        }

        // Ensure PlaceholderProcessor class exists
        if (!class_exists(\WPAICG\Lib\Chat\Triggers\Actions\AIPKit_Placeholder_Processor::class)) {
             $processed_content = $content_template; // Fallback
        } else {
            $processed_content = AIPKit_Placeholder_Processor::process($content_template, $context_data);
        }


        return [
            'type'      => 'inject_context',
            'placement' => $placement,
            'content'   => $processed_content,
        ];
    }
}