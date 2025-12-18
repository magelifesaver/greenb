<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/vector/manager/get-connected-strategy.php
// Status: NEW FILE

namespace WPAICG\Vector\ManagerMethods;

use WPAICG\Vector\AIPKit_Vector_Provider_Strategy_Interface;
use WPAICG\Vector\AIPKit_Vector_Provider_Strategy_Factory;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Helper to get and connect a strategy.
 * This logic was previously a private method in AIPKit_Vector_Store_Manager.
 *
 * @param string $provider The vector store provider name.
 * @param array $provider_config Provider-specific connection/API configuration.
 * @return AIPKit_Vector_Provider_Strategy_Interface|WP_Error The connected strategy instance or WP_Error.
 */
function get_connected_strategy_logic(string $provider, array $provider_config): AIPKit_Vector_Provider_Strategy_Interface|WP_Error {
    if (!class_exists(AIPKit_Vector_Provider_Strategy_Factory::class)) {
        // This should ideally be caught by the main class constructor or dependency loader
        return new WP_Error('factory_missing', __('Vector Provider Strategy Factory is not available.', 'gpt3-ai-content-generator'));
    }

    $strategy = AIPKit_Vector_Provider_Strategy_Factory::get_strategy($provider);
    if (is_wp_error($strategy)) {
        return $strategy;
    }

    $connect_result = $strategy->connect($provider_config);
    if (is_wp_error($connect_result) || $connect_result === false) {
        /* translators: %s is the vector store provider name */
        return is_wp_error($connect_result) ? $connect_result : new WP_Error('connection_failed', sprintf(__('Failed to connect to %s vector store.', 'gpt3-ai-content-generator'), $provider));
    }
    return $strategy;
}