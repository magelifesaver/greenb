<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/vector/post-processor/pinecone/class-pinecone-embedding-handler.php
// Status: NEW FILE

namespace WPAICG\Vector\PostProcessor\Pinecone;

use WPAICG\Core\AIPKit_AI_Caller;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Handles embedding generation for Pinecone post processing.
 */
class PineconeEmbeddingHandler {
    private $ai_caller;

    public function __construct() {
        if (!class_exists(\WPAICG\Core\AIPKit_AI_Caller::class)) {
            $ai_caller_path = WPAICG_PLUGIN_DIR . 'classes/core/class-aipkit_ai_caller.php';
            if (file_exists($ai_caller_path)) require_once $ai_caller_path;
        }
        if (class_exists(\WPAICG\Core\AIPKit_AI_Caller::class)) {
            $this->ai_caller = new \WPAICG\Core\AIPKit_AI_Caller();
        }
    }

    public function generate_embedding(string $content_string, string $embedding_provider, string $embedding_model): array|WP_Error {
        if (!$this->ai_caller) {
            return new WP_Error('ai_caller_missing_pinecone_embed', 'AI Caller component is not available for Pinecone embeddings.');
        }
        $embedding_options = ['model' => $embedding_model];
        $embedding_result = $this->ai_caller->generate_embeddings($embedding_provider, $content_string, $embedding_options);

        if (is_wp_error($embedding_result) || empty($embedding_result['embeddings'][0])) {
            return is_wp_error($embedding_result) ? $embedding_result : new WP_Error('embedding_failed_pinecone_embed', 'No embeddings returned for Pinecone.');
        }
        return $embedding_result;
    }
}