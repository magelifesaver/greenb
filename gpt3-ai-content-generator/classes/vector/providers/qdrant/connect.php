<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/vector/providers/qdrant/connect.php

namespace WPAICG\Vector\Providers\Qdrant\Methods;

use WPAICG\Vector\Providers\AIPKit_Vector_Qdrant_Strategy;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the connect method of AIPKit_Vector_Qdrant_Strategy.
 *
 * @param AIPKit_Vector_Qdrant_Strategy $strategyInstance The instance of the strategy class.
 * @param array $config Configuration array. Must include 'url' and 'api_key'.
 * @return bool|WP_Error True on success, WP_Error on failure.
 */
function connect_logic(AIPKit_Vector_Qdrant_Strategy $strategyInstance, array $config): bool|WP_Error {
    if (empty($config['url'])) {
        return new WP_Error('missing_qdrant_url', __('Qdrant URL is required for connection.', 'gpt3-ai-content-generator'));
    }
    if (empty($config['api_key'])) {
        return new WP_Error('missing_qdrant_api_key', __('Qdrant API Key is required for connection.', 'gpt3-ai-content-generator'));
    }
    $strategyInstance->set_qdrant_url($config['url']);
    $strategyInstance->set_api_key($config['api_key']);
    $strategyInstance->set_is_connected_status(false); // Set to false initially, _request will test

    // The _request method called by list_indexes will determine actual connectivity
    // List collections is used as a lightweight connection test
    $test_connection = _request_logic($strategyInstance, 'GET', '/collections');

    if (is_wp_error($test_connection)) {
        /* translators: %1$s: The URL of the Qdrant instance, %2$s: The specific connection error message. */
        $error_message = sprintf(__('Failed to connect to Qdrant at %1$s. Error: %2$s', 'gpt3-ai-content-generator'), esc_html($strategyInstance->get_qdrant_url()), $test_connection->get_error_message());
        return new WP_Error('qdrant_connection_failed', $error_message, $test_connection->get_error_data());
    }
    if (isset($test_connection['collections']) && is_array($test_connection['collections'])) {
        $strategyInstance->set_is_connected_status(true);
        return true;
    }

    /* translators: %s: The URL of the Qdrant instance. */
    $error_message = sprintf(__('Unexpected response while connecting to Qdrant at %s. Please check URL and API key.', 'gpt3-ai-content-generator'), esc_html($strategyInstance->get_qdrant_url()));
    return new WP_Error('qdrant_connection_unexpected_response', $error_message);
}