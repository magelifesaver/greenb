<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/dashboard/ajax/qdrant/handler-collections/ajax-get-collection-stats.php
// Status: MODIFIED

namespace WPAICG\Dashboard\Ajax\Qdrant\HandlerCollections;

use WP_Error;
use WPAICG\Dashboard\Ajax\AIPKit_Vector_Store_Qdrant_Ajax_Handler;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Handles the logic for fetching Qdrant collection statistics/details.
 * Called by AIPKit_Vector_Store_Qdrant_Ajax_Handler::ajax_get_qdrant_collection_stats().
 *
 * @param AIPKit_Vector_Store_Qdrant_Ajax_Handler $handler_instance
 * @return void
 */
function _aipkit_qdrant_ajax_get_collection_stats_logic(AIPKit_Vector_Store_Qdrant_Ajax_Handler $handler_instance): void
{
    $vector_store_manager = $handler_instance->get_vector_store_manager();

    if (!$vector_store_manager) {
        $handler_instance->send_wp_error(new WP_Error('manager_not_ready_qdrant_stats', __('Vector Store Manager not available for Qdrant stats.', 'gpt3-ai-content-generator'), ['status' => 500]));
        return;
    }

    $qdrant_config = $handler_instance->_get_qdrant_config();
    if (is_wp_error($qdrant_config)) {
        $handler_instance->send_wp_error($qdrant_config);
        return;
    }

    // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked in the calling handler method.
    $collection_name = isset($_POST['collection_name']) ? sanitize_text_field(wp_unslash($_POST['collection_name'])) : '';
    if (empty($collection_name)) {
        $handler_instance->send_wp_error(new WP_Error('missing_name_stats_qdrant', __('Collection name is required to get stats.', 'gpt3-ai-content-generator'), ['status' => 400]));
        return;
    }

    $collection_details = $vector_store_manager->describe_single_index('Qdrant', $collection_name, $qdrant_config);
    if (is_wp_error($collection_details)) {
        $handler_instance->send_wp_error($collection_details);
        return;
    }

    wp_send_json_success(['stats' => $collection_details, 'message' => __('Qdrant collection details fetched.', 'gpt3-ai-content-generator')]);
}