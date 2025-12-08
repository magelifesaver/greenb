<?php

namespace WPAICG\Dashboard\Ajax\Pinecone\HandlerIndexes;

use WP_Error;
use WPAICG\Dashboard\Ajax\AIPKit_Vector_Store_Pinecone_Ajax_Handler;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Handles the logic for fetching full details for a single Pinecone index.
 * Called by AIPKit_Vector_Store_Pinecone_Ajax_Handler::ajax_get_pinecone_index_details().
 *
 * @param AIPKit_Vector_Store_Pinecone_Ajax_Handler $handler_instance
 * @return void
 */
function do_ajax_get_index_details_logic(AIPKit_Vector_Store_Pinecone_Ajax_Handler $handler_instance): void
{
    $vector_store_manager = $handler_instance->get_vector_store_manager();

    if (!$vector_store_manager) {
        $handler_instance->send_wp_error(new WP_Error('manager_not_ready_pinecone_details', __('Vector Store Manager not available.', 'gpt3-ai-content-generator'), ['status' => 500]));
        return;
    }

    $pinecone_config = $handler_instance->_get_pinecone_config();
    if (is_wp_error($pinecone_config)) {
        $handler_instance->send_wp_error($pinecone_config);
        return;
    }

    // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked in the calling handler method.
    $index_name = isset($_POST['index_name']) ? sanitize_text_field(wp_unslash($_POST['index_name'])) : '';
    if (empty($index_name)) {
        $handler_instance->send_wp_error(new WP_Error('missing_index_name_details_pinecone', __('Pinecone index name is required to get details.', 'gpt3-ai-content-generator'), ['status' => 400]));
        return;
    }

    // Use the manager to describe the index, which now should be the sole source of this detailed call
    $index_details = $vector_store_manager->describe_single_index('Pinecone', $index_name, $pinecone_config);

    if (is_wp_error($index_details)) {
        $handler_instance->send_wp_error($index_details);
        return;
    }

    wp_send_json_success(['details' => $index_details, 'message' => __('Pinecone index details fetched.', 'gpt3-ai-content-generator')]);
}