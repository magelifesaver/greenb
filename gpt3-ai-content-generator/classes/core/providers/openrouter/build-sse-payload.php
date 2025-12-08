<?php
// File: classes/core/providers/openrouter/build-sse-payload.php
// Status: NEW FILE

namespace WPAICG\Core\Providers\OpenRouter\Methods;

use WPAICG\Core\Providers\OpenRouterProviderStrategy;
use WPAICG\Core\Providers\OpenRouter\OpenRouterPayloadFormatter; // For direct call
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the build_sse_payload method of OpenRouterProviderStrategy.
 *
 * @param OpenRouterProviderStrategy $strategyInstance The instance of the strategy class.
 * @param array $messages Formatted messages/input/contents array.
 * @param string|array|null $system_instruction Formatted system instruction.
 * @param array $ai_params AI parameters.
 * @param string $model Target model/deployment.
 * @return array The formatted request body data for SSE.
 */
function build_sse_payload_logic(
    OpenRouterProviderStrategy $strategyInstance,
    array $messages,
    $system_instruction,
    array $ai_params,
    string $model
): array {
    // Ensure OpenRouterPayloadFormatter is available
    if (!class_exists(\WPAICG\Core\Providers\OpenRouter\OpenRouterPayloadFormatter::class)) {
        $formatter_bootstrap = dirname(__FILE__) . '/bootstrap-payload-formatter.php';
        if (file_exists($formatter_bootstrap)) {
            require_once $formatter_bootstrap;
        } else {
            return []; // Or throw an error
        }
    }
    // The $system_instruction is now part of the messages array for the formatter
    return \WPAICG\Core\Providers\OpenRouter\OpenRouterPayloadFormatter::format_sse($messages, $system_instruction, $ai_params, $model);
}