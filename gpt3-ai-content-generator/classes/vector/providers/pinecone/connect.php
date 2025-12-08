<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/vector/providers/pinecone/connect.php
// Status: NEW

namespace WPAICG\Vector\Providers\Pinecone\Methods;

use WPAICG\Vector\Providers\AIPKit_Vector_Pinecone_Strategy;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the connect method of AIPKit_Vector_Pinecone_Strategy.
 *
 * @param AIPKit_Vector_Pinecone_Strategy $strategyInstance The instance of the strategy class.
 * @param array $config Configuration array. Must include 'api_key'.
 * @return bool|WP_Error True on success, WP_Error on failure.
 */
function connect_logic(AIPKit_Vector_Pinecone_Strategy $strategyInstance, array $config): bool|WP_Error {
    if (empty($config['api_key'])) {
        return new WP_Error('missing_api_key_pinecone', __('Pinecone API Key is required.', 'gpt3-ai-content-generator'));
    }
    $strategyInstance->set_api_key($config['api_key']);
    $strategyInstance->set_is_connected_status(true); // Assume connected if key is present, then test

    // Test connection by trying to list a single index
    $test_list_response = $strategyInstance->list_indexes(1);
    if (is_wp_error($test_list_response)) {
        $strategyInstance->set_is_connected_status(false);
        return $test_list_response;
    }
    return true;
}