<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/rest/handlers/class-aipkit-rest-vector-store-handler.php
// Status: NEW FILE

namespace WPAICG\REST\Handlers;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use WPAICG\Core\AIPKit_AI_Caller;
use WPAICG\Vector\AIPKit_Vector_Store_Manager;
use WPAICG\AIPKit_Providers;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles REST API requests for interacting with Vector Stores (upserting data).
 */
class AIPKit_REST_Vector_Store_Handler extends AIPKit_REST_Base_Handler
{
    private $ai_caller;
    private $vector_store_manager;

    public function __construct()
    {
        if (class_exists(AIPKit_AI_Caller::class)) {
            $this->ai_caller = new AIPKit_AI_Caller();
        }
        if (class_exists(AIPKit_Vector_Store_Manager::class)) {
            $this->vector_store_manager = new AIPKit_Vector_Store_Manager();
        }
    }

    public function get_endpoint_args(): array
    {
        return array(
            'provider' => array(
                'description' => __('The vector database provider.', 'gpt3-ai-content-generator'),
                'type'        => 'string',
                'enum'        => ['pinecone', 'qdrant'],
                'required'    => true,
            ),
            'target_id' => array(
                'description' => __('The name of the target index (for Pinecone) or collection (for Qdrant).', 'gpt3-ai-content-generator'),
                'type'        => 'string',
                'required'    => true,
            ),
            'vectors' => array(
                'description' => __('An array of objects to be embedded and upserted.', 'gpt3-ai-content-generator'),
                'type'        => 'array',
                'required'    => true,
                'items'       => array(
                    'type'       => 'object',
                    'properties' => array(
                        'id'       => array('type' => 'string', 'description' => 'A unique ID for the vector. If omitted, one will be generated.', 'required' => false),
                        'content'  => array('type' => 'string', 'description' => 'The text content to be embedded.', 'required' => true),
                        'metadata' => array('type' => 'object', 'description' => 'Key-value metadata to store with the vector.', 'required' => false),
                    ),
                ),
            ),
            'embedding_provider' => array(
                'description' => __('The AI provider to use for generating embeddings.', 'gpt3-ai-content-generator'),
                'type'        => 'string',
                'enum'        => ['openai', 'google', 'azure'],
                'required'    => true,
            ),
            'embedding_model' => array(
                'description' => __('The specific model ID to use for generating embeddings.', 'gpt3-ai-content-generator'),
                'type'        => 'string',
                'required'    => true,
            ),
            'namespace' => array(
                 'description' => __('(Pinecone only) The namespace to upsert vectors into.', 'gpt3-ai-content-generator'),
                 'type'        => 'string',
                 'required'    => false,
            ),
            'aipkit_api_key' => array(
                'description' => __('API Key for accessing this endpoint.', 'gpt3-ai-content-generator'),
                'type'        => 'string',
            ),
        );
    }

    public function get_item_schema(): array
    {
         return array(
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'aipkit_vector_upsert_response',
            'type'       => 'object',
            'properties' => array(
                'upserted_count' => array(
                    'description' => esc_html__('The number of vectors successfully processed and sent for upserting.', 'gpt3-ai-content-generator'),
                    'type'        => 'integer',
                    'readonly'    => true,
                ),
                'status' => array(
                    'description' => esc_html__('The final status from the vector database provider.', 'gpt3-ai-content-generator'),
                    'type'        => 'string',
                    'readonly'    => true,
                ),
            ),
        );
    }

    public function handle_request(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        if (!$this->ai_caller || !$this->vector_store_manager) {
            return $this->send_wp_error_response(new WP_Error('rest_aipkit_internal_error', __('Internal server error: Vector components not loaded.', 'gpt3-ai-content-generator'), ['status' => 500]));
        }

        $params = $request->get_params();
        $provider_key = $params['provider'];
        $provider_normalized = ucfirst($provider_key);
        $target_id = $params['target_id'];
        $vectors_data = $params['vectors'];
        $embedding_provider_key = $params['embedding_provider'];
        $embedding_model = $params['embedding_model'];
        $namespace = $params['namespace'] ?? null;

        if (empty($vectors_data) || !is_array($vectors_data)) {
            return $this->send_wp_error_response(new WP_Error('rest_aipkit_invalid_vectors', __('The "vectors" parameter must be a non-empty array.', 'gpt3-ai-content-generator'), ['status' => 400]));
        }
        
        $content_array_for_embedding = array_column($vectors_data, 'content');
        if (empty($content_array_for_embedding) || count($content_array_for_embedding) !== count($vectors_data)) {
            return $this->send_wp_error_response(new WP_Error('rest_aipkit_no_content', __('Each object in the "vectors" array must have a "content" key.', 'gpt3-ai-content-generator'), ['status' => 400]));
        }
        
        $embedding_provider_normalized = match(strtolower($embedding_provider_key)) { 'openai' => 'OpenAI', 'google' => 'Google', 'azure' => 'Azure', default => null, };
        if(!$embedding_provider_normalized) return $this->send_wp_error_response(new WP_Error('rest_aipkit_invalid_embedding_provider', __('Invalid embedding provider.', 'gpt3-ai-content-generator'), ['status' => 400]));
        
        $embedding_result = $this->ai_caller->generate_embeddings($embedding_provider_normalized, $content_array_for_embedding, ['model' => $embedding_model]);
        
        if (is_wp_error($embedding_result)) {
            return $this->send_wp_error_response($embedding_result);
        }

        $embedding_vectors = $embedding_result['embeddings'] ?? [];

        if (count($embedding_vectors) !== count($vectors_data)) {
            return $this->send_wp_error_response(new WP_Error('rest_aipkit_embedding_mismatch', __('The number of returned embeddings does not match the number of input texts.', 'gpt3-ai-content-generator'), ['status' => 500]));
        }
        
        $vectors_to_upsert = [];
        foreach($vectors_data as $index => $item) {
            $vector_id = $item['id'] ?? wp_generate_uuid4();
            $metadata = $item['metadata'] ?? [];
            $metadata['original_content'] = $item['content'];

            $vector_item = [
                'id' => (string) $vector_id,
                'values' => $embedding_vectors[$index],
                'vector' => $embedding_vectors[$index],
                'metadata' => $metadata,
                'payload' => $metadata 
            ];
            $vectors_to_upsert[] = $vector_item;
        }

        $provider_config = AIPKit_Providers::get_provider_data($provider_normalized);
        $upsert_payload = [];
        if ($provider_key === 'pinecone') {
            $upsert_payload['vectors'] = $vectors_to_upsert;
            if ($namespace) {
                $upsert_payload['namespace'] = $namespace;
            }
        } elseif ($provider_key === 'qdrant') {
            $upsert_payload['points'] = $vectors_to_upsert;
        }

        $upsert_result = $this->vector_store_manager->upsert_vectors($provider_normalized, $target_id, $upsert_payload, $provider_config);
        
        if (is_wp_error($upsert_result)) {
            return $this->send_wp_error_response($upsert_result);
        }

        $response_data = [
            'upserted_count' => is_array($upsert_result) && isset($upsert_result['upserted_count']) ? $upsert_result['upserted_count'] : count($vectors_to_upsert),
            'status' => 'success'
        ];

        return new WP_REST_Response($response_data, 200);
    }
}