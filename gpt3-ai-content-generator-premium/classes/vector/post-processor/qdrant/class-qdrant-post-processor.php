<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/vector/post-processor/qdrant/class-qdrant-post-processor.php
// Status: MODIFIED

namespace WPAICG\Vector\PostProcessor\Qdrant;

use WPAICG\Vector\PostProcessor\Base\AIPKit_Vector_Post_Processor_Base;
use WPAICG\Vector\AIPKit_Vector_Store_Manager;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Handles indexing WordPress post content into Qdrant Collections.
 */
class QdrantPostProcessor extends AIPKit_Vector_Post_Processor_Base
{
    private $vector_store_manager;
    private $config_handler;
    private $embedding_handler;

    public function __construct()
    {
        parent::__construct();
        if (!class_exists(\WPAICG\Vector\AIPKit_Vector_Store_Manager::class)) {
            $manager_path = WPAICG_PLUGIN_DIR . 'classes/vector/class-aipkit-vector-store-manager.php';
            if (file_exists($manager_path)) {
                require_once $manager_path;
            }
        }
        if (class_exists(\WPAICG\Vector\AIPKit_Vector_Store_Manager::class)) {
            $this->vector_store_manager = new \WPAICG\Vector\AIPKit_Vector_Store_Manager();
        }

        if (!class_exists(QdrantConfig::class)) {
            $config_path = __DIR__ . '/class-qdrant-config.php';
            if (file_exists($config_path)) {
                require_once $config_path;
            }
        }
        if (class_exists(QdrantConfig::class)) {
            $this->config_handler = new QdrantConfig();
        }

        if (!class_exists(QdrantEmbeddingHandler::class)) {
            $embed_path = __DIR__ . '/class-qdrant-embedding-handler.php';
            if (file_exists($embed_path)) {
                require_once $embed_path;
            }
        }
        if (class_exists(QdrantEmbeddingHandler::class)) {
            $this->embedding_handler = new QdrantEmbeddingHandler();
        }
    }

    /**
     * Indexes a single post's content to a specified Qdrant collection.
     *
     * @param int $post_id The ID of the post to index.
     * @param string $collection_name The name of the target Qdrant collection.
     * @param string $embedding_provider_key Key of the provider for embeddings.
     * @param string $embedding_model The specific embedding model to use.
     * @return array ['status' => 'success'|'error', 'message' => string]
     */
    public function index_single_post_to_collection(int $post_id, string $collection_name, string $embedding_provider_key, string $embedding_model): array
    {        
        $post_obj = get_post($post_id);
        $post_title_for_log = $post_obj ? $post_obj->post_title : 'N/A';
        
        $provider_map = ['openai' => 'OpenAI', 'google' => 'Google', 'azure' => 'Azure'];
        $embedding_provider_normalized = $provider_map[strtolower($embedding_provider_key)] ?? ucfirst($embedding_provider_key);
        // --- MODIFIED: Use wp_generate_uuid4() for Qdrant point ID ---
        $qdrant_point_id = wp_generate_uuid4();
        // --- END MODIFICATION ---


        $log_entry_base = [
            'provider' => 'Qdrant', 'vector_store_id' => $collection_name, 'vector_store_name' => $collection_name,
            'post_id' => $post_id, 'post_title' => $post_title_for_log,
            'embedding_provider' => $embedding_provider_normalized, 'embedding_model' => $embedding_model,
            'file_id' => $qdrant_point_id, // Log the UUID
            'source_type_for_log' => 'wordpress_post'
        ];

        if (!$this->embedding_handler || !$this->vector_store_manager || !$this->config_handler) {
            $error_msg = __('Qdrant processing components not available.', 'gpt3-ai-content-generator');
            return ['status' => 'error', 'message' => $error_msg];
        }

        $qdrant_api_config = $this->config_handler->get_config();
        if (is_wp_error($qdrant_api_config)) {
            $error_msg = $qdrant_api_config->get_error_message();
            return ['status' => 'error', 'message' => $error_msg];
        }

        $content_string_or_error = $this->get_post_content_as_string($post_id);
        if (is_wp_error($content_string_or_error)) {
            $error_msg = 'Content retrieval error: ' . $content_string_or_error->get_error_message();
            return ['status' => 'error', 'message' => $error_msg];
        }
        $log_entry_base['indexed_content'] = $content_string_or_error;

        if (empty(trim($content_string_or_error))) {
            $error_msg = __('Post content is empty for Qdrant.', 'gpt3-ai-content-generator');
            return ['status' => 'error', 'message' => $error_msg];
        }
        
        $embedding_result = $this->embedding_handler->generate_embedding($content_string_or_error, $embedding_provider_normalized, $embedding_model);
        if (is_wp_error($embedding_result)) {
            $error_msg = 'Embedding failed: ' . $embedding_result->get_error_message();
            return ['status' => 'error', 'message' => $error_msg];
        }
        $vector_values = $embedding_result['embeddings'][0];

        // --- MODIFIED: Ensure 'vector_id' in payload is also the UUID ---
        $payload = [
            'source' => 'wordpress_post',
            'post_id' => (string)$post_id,
            'title' => $post_title_for_log,
            'type' => get_post_type($post_id),
            'url' => get_permalink($post_id),
            'vector_id' => $qdrant_point_id // Store the UUID in payload
        ];
        // --- END MODIFICATION ---
        $points_to_upsert = [['id' => $qdrant_point_id, 'vector' => $vector_values, 'payload' => $payload]];

        $upsert_result = $this->vector_store_manager->upsert_vectors('Qdrant', $collection_name, ['points' => $points_to_upsert], $qdrant_api_config);
        if (is_wp_error($upsert_result)) {
            $error_msg = 'Upsert to Qdrant failed: ' . $upsert_result->get_error_message();
            return ['status' => 'error', 'message' => $error_msg];
        }
        
        $this->log_event(array_merge($log_entry_base, ['status' => 'indexed', 'message' => 'WordPress post content submitted for indexing.']));
        update_post_meta($post_id, '_aipkit_indexed_to_vs_' . sanitize_key($collection_name), '1');
        update_post_meta($post_id, '_aipkit_vector_id_for_vs_' . sanitize_key($collection_name), $qdrant_point_id);
        
        return ['status' => 'success', 'message' => 'Post content indexed to Qdrant.'];
    }
}