<?php
// File: classes/core/providers/azure/parse-sse-chunk.php

namespace WPAICG\Core\Providers\Azure\Methods;

use WPAICG\Core\Providers\AzureProviderStrategy;
use WPAICG\Core\Providers\Azure\AzureResponseParser; // For direct call

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the parse_sse_chunk method of AzureProviderStrategy.
 *
 * @param AzureProviderStrategy $strategyInstance The instance of the strategy class.
 * @param string $sse_chunk The raw chunk received from the stream.
 * @param string &$current_buffer The reference to the incomplete buffer for this provider.
 * @return array Result containing delta, usage, flags.
 */
function parse_sse_chunk_logic(
    AzureProviderStrategy $strategyInstance,
    string $sse_chunk,
    string &$current_buffer
): array {
    // This method in AzureProviderStrategy directly calls AzureResponseParser::parse_sse_chunk.
    if (!class_exists(\WPAICG\Core\Providers\Azure\AzureResponseParser::class)) {
        $parser_bootstrap = __DIR__ . '/bootstrap-response-parser.php';
        if (file_exists($parser_bootstrap)) {
            require_once $parser_bootstrap;
        } else {
            // This should not happen if ProviderDependenciesLoader is correct.
            return ['delta' => null, 'usage' => null, 'is_error' => true, 'is_warning' => false, 'is_done' => true];
        }
    }
    return \WPAICG\Core\Providers\Azure\AzureResponseParser::parse_sse_chunk($sse_chunk, $current_buffer);
}