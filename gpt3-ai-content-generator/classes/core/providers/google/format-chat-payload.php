<?php
// File: classes/core/providers/google/format-chat-payload.php
// Status: NEW FILE

namespace WPAICG\Core\Providers\Google\Methods;

use WPAICG\Core\Providers\GoogleProviderStrategy; 
use WPAICG\Core\Providers\Google\GooglePayloadFormatter; 

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the format_chat_payload method of GoogleProviderStrategy.
 *
 * @param GoogleProviderStrategy $strategyInstance The instance of the strategy class.
 * @param string $user_message The user's message (already included in history for Google).
 * @param string $instructions System instructions.
 * @param array  $history Conversation history.
 * @param array  $ai_params AI parameters.
 * @param string $model Model ID.
 * @return array The formatted request body data.
 */
function format_chat_payload_logic(
    GoogleProviderStrategy $strategyInstance,
    string $user_message,
    string $instructions,
    array $history,
    array $ai_params,
    string $model
): array {
    if (!class_exists(\WPAICG\Core\Providers\Google\GooglePayloadFormatter::class)) {
        $formatter_bootstrap = dirname(__FILE__) . '/bootstrap-payload-formatter.php';
        if (file_exists($formatter_bootstrap)) {
            require_once $formatter_bootstrap;
        } else {
            return []; 
        }
    }
    $final_history = $history;
    if(!empty($user_message)){
        $last_msg = end($final_history);
        if(!$last_msg || $last_msg['role'] !== 'user' || $last_msg['content'] !== $user_message){
             $final_history[] = ['role' => 'user', 'content' => $user_message];
        }
    }
    return \WPAICG\Core\Providers\Google\GooglePayloadFormatter::format_chat($instructions, $final_history, $ai_params, $model);
}