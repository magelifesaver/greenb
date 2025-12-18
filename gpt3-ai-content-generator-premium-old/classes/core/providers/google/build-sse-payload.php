<?php
// File: classes/core/providers/google/build-sse-payload.php
// Status: NEW FILE

namespace WPAICG\Core\Providers\Google\Methods;

use WPAICG\Core\Providers\GoogleProviderStrategy; 
use WPAICG\Core\Providers\Google\GooglePayloadFormatter; 

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the build_sse_payload method of GoogleProviderStrategy.
 *
 * @param GoogleProviderStrategy $strategyInstance The instance of the strategy class.
 * @param array $messages Formatted messages/input/contents array.
 * @param string|array|null $system_instruction Formatted system instruction.
 * @param array $ai_params AI parameters.
 * @param string $model Target model.
 * @return array The formatted request body data for SSE.
 */
function build_sse_payload_logic(
    GoogleProviderStrategy $strategyInstance,
    array $messages,
    $system_instruction,
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
    return \WPAICG\Core\Providers\Google\GooglePayloadFormatter::format_sse($messages, $system_instruction, $ai_params, $model);
}