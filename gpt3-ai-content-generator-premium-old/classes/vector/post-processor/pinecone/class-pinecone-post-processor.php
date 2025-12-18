<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/vector/post-processor/pinecone/class-pinecone-post-processor.php
// Status: NEW FILE

namespace WPAICG\Vector\PostProcessor\Pinecone;

use WPAICG\Vector\PostProcessor\Base\AIPKit_Vector_Post_Processor_Base;
use WPAICG\Vector\AIPKit_Vector_Store_Manager;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Handles indexing WordPress post content into Pinecone Vector Stores.
 */
class PineconePostProcessor extends AIPKit_Vector_Post_Processor_Base {

    private $vector_store_manager;
    private $config_handler;
    private $embedding_handler;

    public function __construct() {
        parent::__construct();
        if (!class_exists(\WPAICG\Vector\AIPKit_Vector_Store_Manager::class)) {
            $manager_path = WPAICG_PLUGIN_DIR . 'classes/vector/class-aipkit-vector-store-manager.php';
            if (file_exists($manager_path)) require_once $manager_path;
        }
        if (class_exists(\WPAICG\Vector\AIPKit_Vector_Store_Manager::class)) {
            $this->vector_store_manager = new \WPAICG\Vector\AIPKit_Vector_Store_Manager();
        }

        if (!class_exists(PineconeConfig::class)) {
            $config_path = __DIR__ . '/class-pinecone-config.php';
            if (file_exists($config_path)) require_once $config_path;
        }
        if (class_exists(PineconeConfig::class)) {
            $this->config_handler = new PineconeConfig();
        }

        if (!class_exists(PineconeEmbeddingHandler::class)) {
            $embed_path = __DIR__ . '/class-pinecone-embedding-handler.php';
            if (file_exists($embed_path)) require_once $embed_path;
        }
        if (class_exists(PineconeEmbeddingHandler::class)) {
            $this->embedding_handler = new PineconeEmbeddingHandler();
        }
    }

    /**
     * Indexes a single post's content to a specified Pinecone index.
     *
     * @param int $post_id The ID of the post to index.
     * @param string $index_name The name of the target Pinecone index.
     * @param string $embedding_provider_key Key of the provider for embeddings.
     * @param string $embedding_model The specific embedding model to use.
     * @return array ['status' => 'success'|'error', 'message' => string]
     */
    public function index_single_post_to_index(int $post_id, string $index_name, string $embedding_provider_key, string $embedding_model): array {
        
        $post_obj = get_post($post_id);
        $post_title_for_log = $post_obj ? $post_obj->post_title : 'N/A';
        
        $provider_map = ['openai' => 'OpenAI', 'google' => 'Google', 'azure' => 'Azure'];
        $embedding_provider_normalized = $provider_map[strtolower($embedding_provider_key)] ?? ucfirst($embedding_provider_key);
        $pinecone_vector_id = 'wp_post_' . $post_id;

        $log_entry_base = [
            'provider' => 'Pinecone', 'vector_store_id' => $index_name, 'vector_store_name' => $index_name,
            'post_id' => $post_id, 'post_title' => $post_title_for_log,
            'embedding_provider' => $embedding_provider_normalized, 'embedding_model' => $embedding_model,
            'file_id' => $pinecone_vector_id,
            'source_type_for_log' => 'wordpress_post'
        ];

        if (!$this->embedding_handler || !$this->vector_store_manager || !$this->config_handler) {
            $error_msg = __('Pinecone processing components not available.', 'gpt3-ai-content-generator');
            return ['status' => 'error', 'message' => $error_msg];
        }

        $pinecone_api_config = $this->config_handler->get_config();
        if (is_wp_error($pinecone_api_config)) {
            $error_msg = $pinecone_api_config->get_error_message();
            return ['status' => 'error', 'message' => $error_msg];
        }
        
        $content_string_or_error = $this->get_post_content_as_string($post_id);
        if (is_wp_error($content_string_or_error)) {
            $error_msg = 'Content retrieval error: ' . $content_string_or_error->get_error_message();
            return ['status' => 'error', 'message' => $error_msg];
        }
        $log_entry_base['indexed_content'] = $content_string_or_error;

        if (empty(trim($content_string_or_error))) {
            $error_msg = __('Post content is empty for Pinecone.', 'gpt3-ai-content-generator');
            return ['status' => 'error', 'message' => $error_msg];
        }
        
        $embedding_result = $this->embedding_handler->generate_embedding($content_string_or_error, $embedding_provider_normalized, $embedding_model);
        if (is_wp_error($embedding_result)) {
            $error_msg = 'Embedding failed: ' . $embedding_result->get_error_message();
            return ['status' => 'error', 'message' => $error_msg];
        }
        $vector_values = $embedding_result['embeddings'][0];
        
        $metadata = ['source' => 'wordpress_post', 'post_id' => (string)$post_id, 'title' => $post_title_for_log, 'type' => get_post_type($post_id), 'url' => get_permalink($post_id), 'vector_id' => $pinecone_vector_id];
        $vectors_to_upsert = [['id' => $pinecone_vector_id, 'values' => $vector_values, 'metadata' => $metadata]];

        $upsert_result = $this->vector_store_manager->upsert_vectors('Pinecone', $index_name, ['vectors' => $vectors_to_upsert], $pinecone_api_config);
        if (is_wp_error($upsert_result)) {
            $error_msg = 'Upsert to Pinecone failed: ' . $upsert_result->get_error_message();
            return ['status' => 'error', 'message' => $error_msg];
        }
        
        $this->log_event(array_merge($log_entry_base, ['status' => 'indexed', 'message' => 'WordPress post content submitted for indexing.']));
        update_post_meta($post_id, '_aipkit_indexed_to_vs_' . sanitize_key($index_name), '1');
        update_post_meta($post_id, '_aipkit_vector_id_for_vs_' . sanitize_key($index_name), $pinecone_vector_id);
        
        return ['status' => 'success', 'message' => 'Post content indexed to Pinecone.'];
    }
}