<?php
// File: classes/core/providers/azure/build-sse-payload.php

namespace WPAICG\Core\Providers\Azure\Methods;

use WPAICG\Core\Providers\AzureProviderStrategy;
use WPAICG\Core\Providers\Azure\AzurePayloadFormatter; // For direct call

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the build_sse_payload method of AzureProviderStrategy.
 *
 * @param AzureProviderStrategy $strategyInstance The instance of the strategy class.
 * @param array $messages Formatted messages/input/contents array.
 * @param string|array|null $system_instruction Formatted system instruction.
 * @param array $ai_params AI parameters.
 * @param string $model Target model/deployment.
 * @return array The formatted request body data for SSE.
 */
function build_sse_payload_logic(
    AzureProviderStrategy $strategyInstance,
    array $messages,
    $system_instruction,
    array $ai_params,
    string $model
): array {
    // This method in AzureProviderStrategy directly calls AzurePayloadFormatter::format_sse.
    if (!class_exists(\WPAICG\Core\Providers\Azure\AzurePayloadFormatter::class)) {
        $formatter_bootstrap = __DIR__ . '/bootstrap-payload-formatter.php';
        if (file_exists($formatter_bootstrap)) {
            require_once $formatter_bootstrap;
        } else {
            // This should not happen if ProviderDependenciesLoader is correct.
            return []; // Or throw an error
        }
    }
    // The $system_instruction is now part of the messages array for the formatter
    return \WPAICG\Core\Providers\Azure\AzurePayloadFormatter::format_sse($messages, $system_instruction, $ai_params, $model, true);
}