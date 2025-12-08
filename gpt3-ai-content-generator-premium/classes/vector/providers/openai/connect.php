<?php

namespace WPAICG\Vector\Providers\OpenAI\Methods;

use WPAICG\Vector\Providers\AIPKit_Vector_OpenAI_Strategy;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the connect method of AIPKit_Vector_OpenAI_Strategy.
 *
 * @param AIPKit_Vector_OpenAI_Strategy $strategyInstance The instance of the strategy class.
 * @param array $config Configuration array. Must include 'api_key'.
 * @return bool|WP_Error True on success, WP_Error on failure.
 */
function connect_logic(AIPKit_Vector_OpenAI_Strategy $strategyInstance, array $config): bool|WP_Error {
    if (empty($config['api_key'])) {
        return new WP_Error('missing_api_key', __('OpenAI API Key is required for connection.', 'gpt3-ai-content-generator'));
    }

    if (!$strategyInstance->get_is_connected_status()) { // This should be true if bootstrap's connect set it
        return new WP_Error('internal_error_connect_openai', 'Strategy instance not marked as connected before test call.');
    }
    
    // Test connection by trying to list a single vector store (less intensive)
    // This uses the _request_logic which is also externalized.
    $test_list_response = \WPAICG\Vector\Providers\OpenAI\Methods\_request_logic(
        $strategyInstance,
        'GET',
        \WPAICG\Core\Providers\OpenAI\OpenAIUrlBuilder::build('vector_stores', [
            'base_url' => $strategyInstance->get_base_url(),
            'api_version' => $strategyInstance->get_api_version(),
            'limit' => 1
        ])
    );

    if (is_wp_error($test_list_response)) {
        // $strategyInstance->is_connected = false; // This needs a setter method.
        return $test_list_response;
    }
    return true;
}