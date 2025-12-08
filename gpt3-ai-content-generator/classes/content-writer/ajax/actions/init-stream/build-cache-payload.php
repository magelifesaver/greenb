<?php

namespace WPAICG\ContentWriter\Ajax\Actions\InitStream;

if (!defined('ABSPATH')) {
    exit;
}

/**
* Builds the structured payload to be stored in the SSE cache.
* UPDATED: Removed guided mode fields.
*
* @param string $system_instruction The built system instruction.
* @param string $user_prompt The built user prompt.
* @param string $provider The normalized provider name.
* @param string $model The normalized model name.
* @param array $ai_params_for_cache The merged AI parameters.
* @param array $settings The sanitized settings from the request.
* @return array The structured cache payload.
*/
function build_cache_payload_logic(
    string $system_instruction,
    string $user_prompt,
    string $provider,
    string $model,
    array $ai_params_for_cache,
    array $settings
): array {
    // Reuse conversation_uuid from the request if present; otherwise generate a new one
    // phpcs:ignore WordPress.Security.NonceVerification.Missing
    $provided_uuid = isset($settings['conversation_uuid']) ? sanitize_text_field(wp_unslash($settings['conversation_uuid'])) : '';
    $conversation_uuid = !empty($provided_uuid) ? $provided_uuid : wp_generate_uuid4();
    return [
    'stream_context' => 'content_writer',
    'system_instruction' => $system_instruction,
    'user_message' => $user_prompt,
    'provider' => $provider,
    'model' => $model,
    'ai_params' => $ai_params_for_cache,
    'conversation_uuid' => $conversation_uuid,
    'user_id' => get_current_user_id(),
    'bot_id' => null,
    'session_id' => null,
    'post_id' => 0,
    'initial_request_details' => [
    'title' => $settings['content_title'] ?? '',
    'keywords' => $settings['content_keywords'] ?? null,
    'inline_keywords' => $settings['inline_keywords'] ?? '',
    'content_max_tokens' => $ai_params_for_cache['max_completion_tokens'] ?? null,
    'generate_meta_description' => $settings['generate_meta_description'] ?? '0',
    'custom_meta_prompt' => $settings['custom_meta_prompt'] ?? '',
    'generate_focus_keyword' => $settings['generate_focus_keyword'] ?? '0',
    'custom_keyword_prompt' => $settings['custom_keyword_prompt'] ?? '',
    'generate_images_enabled' => $settings['generate_images_enabled'] ?? '0',
    'image_provider' => $settings['image_provider'] ?? 'openai',
    'image_model' => $settings['image_model'] ?? 'gpt-image-1',
    'image_prompt' => $settings['image_prompt'] ?? '',
    'image_count' => $settings['image_count'] ?? 1,
    'image_placement' => $settings['image_placement'] ?? 'after_first_h2',
    'image_placement_param_x' => $settings['image_placement_param_x'] ?? 2,
    'generate_featured_image' => $settings['generate_featured_image'] ?? '0',
    'featured_image_prompt' => $settings['featured_image_prompt'] ?? '',
    'pexels_orientation' => $settings['pexels_orientation'] ?? 'none',
    'pexels_size' => $settings['pexels_size'] ?? 'none',
    'pexels_color' => $settings['pexels_color'] ?? '',
    'pixabay_orientation' => $settings['pixabay_orientation'] ?? 'all',
    'pixabay_image_type' => $settings['pixabay_image_type'] ?? 'all',
    'pixabay_category' => $settings['pixabay_category'] ?? '',
    ],
    'enable_vector_store'           => $settings['enable_vector_store'] ?? '0',
    'vector_store_provider'         => $settings['vector_store_provider'] ?? 'openai',
    'openai_vector_store_ids'       => $settings['openai_vector_store_ids'] ?? [],
    'pinecone_index_name'           => $settings['pinecone_index_name'] ?? '',
    'qdrant_collection_name'        => $settings['qdrant_collection_name'] ?? '',
    'vector_embedding_provider'     => $settings['vector_embedding_provider'] ?? 'openai',
    'vector_embedding_model'        => $settings['vector_embedding_model'] ?? '',
    'vector_store_top_k'            => isset($settings['vector_store_top_k']) ? absint($settings['vector_store_top_k']) : 3,
    'vector_store_confidence_threshold' => isset($settings['vector_store_confidence_threshold']) ? max(0, min(absint($settings['vector_store_confidence_threshold']), 100)) : 20,
    ];
}
