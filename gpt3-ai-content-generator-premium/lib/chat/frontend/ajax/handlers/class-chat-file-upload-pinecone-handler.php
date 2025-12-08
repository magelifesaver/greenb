<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/lib/chat/frontend/ajax/handlers/class-chat-file-upload-pinecone-handler.php
// Status: NEW FILE

namespace WPAICG\Lib\Chat\Frontend\Ajax\Handlers;

use WPAICG\Lib\Chat\Frontend\Ajax\Utils\ChatUploadLogger;
use WPAICG\Lib\Chat\Frontend\Ajax\Utils\ChatUploadConfigLoader;
use WPAICG\AIPKit_Providers;
use WPAICG\Chat\Storage\BotStorage;
use WPAICG\Core\AIPKit_AI_Caller;
use WPAICG\Lib\Utils\AIPKit_Pdf_Parser;
use WPAICG\Utils\AIPKit_Identifier_Utils;
use WPAICG\Vector\AIPKit_Vector_Store_Manager;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * ChatFileUploadPineconeHandler
 *
 * Handles Pinecone-specific file upload logic for chat context.
 */
class ChatFileUploadPineconeHandler
{
    private $config_loader;
    private $logger;
    private $bot_storage;
    private $ai_caller;
    private $vector_store_manager;

    public function __construct(ChatUploadConfigLoader $config_loader, ChatUploadLogger $logger)
    {
        $this->config_loader = $config_loader;
        $this->logger = $logger;

        // Instantiate dependencies not directly provided by ChatUploadConfigLoader
        if (class_exists(BotStorage::class)) {
            $this->bot_storage = new BotStorage();
        } else {
            $this->bot_storage = null;
        }

        if (class_exists(AIPKit_AI_Caller::class)) {
            $this->ai_caller = new AIPKit_AI_Caller();
        } else {
            $this->ai_caller = null;
        }

        $this->vector_store_manager = $this->config_loader->get_vector_store_manager();
    }

    /**
     * Gets Pinecone API configuration.
     *
     * @return array|WP_Error
     */
    public function get_provider_api_config(): array|WP_Error
    {
        if (!class_exists(AIPKit_Providers::class)) {
            return new WP_Error('dependency_missing_pinecone_providers', __('Provider configuration component is missing for Pinecone.', 'gpt3-ai-content-generator'), ['status' => 500]);
        }
        $pinecone_data = AIPKit_Providers::get_provider_data('Pinecone');
        if (empty($pinecone_data['api_key'])) {
            return new WP_Error('missing_pinecone_api_key', __('Pinecone API Key is not configured.', 'gpt3-ai-content-generator'), ['status' => 500]);
        }
        return ['api_key' => $pinecone_data['api_key']];
    }

    /**
     * Processes the file upload for Pinecone.
     *
     * @param array $file_data The $_FILES entry for the uploaded file.
     * @param int|null $user_id Current user ID (null for guests).
     * @param string|null $session_id Guest session ID.
     * @param array $pinecone_config Pinecone API connection config.
     * @param int $bot_id The ID of the chatbot.
     * @return array|WP_Error Success data or WP_Error.
     */
    public function process_upload(array $file_data, ?int $user_id, ?string $session_id, array $pinecone_config, int $bot_id): array|WP_Error
    {
        if (!$this->bot_storage || !$this->ai_caller || !$this->vector_store_manager) {
            return new WP_Error('internal_error_pinecone_deps', __('Core components for Pinecone file upload are not available.', 'gpt3-ai-content-generator'), ['status' => 500]);
        }

        // Get Bot Settings
        $bot_settings = $this->bot_storage->get_chatbot_settings($bot_id);
        $pinecone_index_name = $bot_settings['pinecone_index_name'] ?? null;
        $embedding_provider_key = $bot_settings['vector_embedding_provider'] ?? null;
        $embedding_model = $bot_settings['vector_embedding_model'] ?? null;

        if (empty($pinecone_index_name)) {
            return new WP_Error('pinecone_index_not_configured', __('This chatbot is not configured for Pinecone file uploads (index name missing).', 'gpt3-ai-content-generator'), ['status' => 400]);
        }
        if (empty($embedding_provider_key) || empty($embedding_model)) {
            return new WP_Error('embedding_config_missing', __('Embedding provider or model is not configured for this chatbot.', 'gpt3-ai-content-generator'), ['status' => 400]);
        }

        // File Content Extraction
        $file_content_for_embedding = '';
        $uploaded_file_mime_type = '';
        if (function_exists('mime_content_type') && is_readable($file_data['tmp_name'])) {
            $uploaded_file_mime_type = mime_content_type($file_data['tmp_name']);
        } elseif (isset($file_data['type'])) {
            $uploaded_file_mime_type = $file_data['type'];
        }

        if ($uploaded_file_mime_type === 'application/pdf') {
            if (!class_exists(AIPKit_Pdf_Parser::class)) {
                return new WP_Error('pdf_parser_missing_lib', __('PDF parsing utility is missing (Pinecone).', 'gpt3-ai-content-generator'), ['status' => 500]);
            }
            $pdf_parser = new AIPKit_Pdf_Parser();
            $extracted_text_or_error = $pdf_parser->extract_text($file_data['tmp_name']);
            if (is_wp_error($extracted_text_or_error)) {
                $this->logger->log_event([
                    'provider' => 'Pinecone', 'vector_store_id' => $pinecone_index_name, 'vector_store_name' => $pinecone_index_name,
                    'status' => 'content_error', 'message' => 'PDF parsing failed: ' . $extracted_text_or_error->get_error_message(),
                    'embedding_provider' => $embedding_provider_key, 'embedding_model' => $embedding_model,
                    'indexed_content' => sanitize_file_name($file_data['name']),
                    'post_title' => sanitize_file_name($file_data['name']), // Using filename as title for log
                    'source_type_for_log' => 'chat_file_upload_pinecone'
                ]);
                return new WP_Error('pdf_parsing_failed_pinecone_chat', 'PDF Parsing Failed: ' . $extracted_text_or_error->get_error_message(), ['status' => 500]);
            }
            $file_content_for_embedding = $extracted_text_or_error;
        } elseif ($uploaded_file_mime_type === 'text/plain') {
            $file_content_for_embedding = file_get_contents($file_data['tmp_name']);
        } else {
            return new WP_Error('unsupported_file_type_pinecone_chat', __('Unsupported file type for Pinecone chat upload. Given: ', 'gpt3-ai-content-generator') . $uploaded_file_mime_type, ['status' => 400]);
        }

        if ($file_content_for_embedding === false || empty(trim($file_content_for_embedding))) {
            $this->logger->log_event([
                'provider' => 'Pinecone', 'vector_store_id' => $pinecone_index_name, 'vector_store_name' => $pinecone_index_name,
                'status' => 'content_error', 'message' => 'Could not read file content or file is empty for Pinecone chat upload.',
                'embedding_provider' => $embedding_provider_key, 'embedding_model' => $embedding_model,
                'indexed_content' => sanitize_file_name($file_data['name']),
                'post_title' => sanitize_file_name($file_data['name']),
                'source_type_for_log' => 'chat_file_upload_pinecone'
            ]);
            return new WP_Error('file_read_error_pinecone_chat', __('Could not read file content or file is empty.', 'gpt3-ai-content-generator'), ['status' => 500]);
        }

        // Embedding Generation
        $provider_map = ['openai' => 'OpenAI', 'google' => 'Google', 'azure' => 'Azure'];
        $embedding_provider_norm = $provider_map[strtolower($embedding_provider_key)] ?? ucfirst($embedding_provider_key);
        $embedding_options = ['model' => $embedding_model];
        $embedding_result = $this->ai_caller->generate_embeddings($embedding_provider_norm, $file_content_for_embedding, $embedding_options);

        if (is_wp_error($embedding_result) || empty($embedding_result['embeddings'][0])) {
            $error_msg = is_wp_error($embedding_result) ? $embedding_result->get_error_message() : 'No embeddings returned.';
            $this->logger->log_event([
                'provider' => 'Pinecone', 'vector_store_id' => $pinecone_index_name, 'vector_store_name' => $pinecone_index_name,
                'status' => 'failed', 'message' => 'Embedding failed: ' . $error_msg,
                'embedding_provider' => $embedding_provider_key, 'embedding_model' => $embedding_model,
                'indexed_content' => $file_content_for_embedding, // Log full content here for diagnosis
                'post_title' => sanitize_file_name($file_data['name']),
                'source_type_for_log' => 'chat_file_upload_pinecone'
            ]);
            return new WP_Error('embedding_failed_pinecone_chat', __('Failed to generate vector for file content.', 'gpt3-ai-content-generator'), ['status' => 500]);
        }
        $vector_values = $embedding_result['embeddings'][0];

        // Pinecone Namespace and Vector ID Generation
        $namespace_for_file_context = AIPKit_Identifier_Utils::generate_chat_context_identifier($user_id, $session_id, 'pinecone_chat_file_');
        $vector_id_for_pinecone = 'chatfile_' . hash('md5', sanitize_file_name($file_data['name']) . $namespace_for_file_context) . '_' . time();

        // Vector Upsert
        $metadata = [
            'original_filename' => sanitize_file_name($file_data['name']),
            'file_upload_context_id' => $namespace_for_file_context, // Using namespace as the context ID
            'user_id' => $user_id ? (string)$user_id : null,
            'session_id' => $session_id,
            'timestamp' => time(),
            'source' => 'chat_file_upload' // More specific source
        ];
        $vectors_to_upsert = [[
            'id' => $vector_id_for_pinecone,
            'values' => $vector_values,
            'metadata' => $metadata
        ]];
        $upsert_data_for_pinecone = ['vectors' => $vectors_to_upsert, 'namespace' => $namespace_for_file_context];

        $upsert_result = $this->vector_store_manager->upsert_vectors('Pinecone', $pinecone_index_name, $upsert_data_for_pinecone, $pinecone_config);

        if (is_wp_error($upsert_result)) {
            $this->logger->log_event([
                'provider' => 'Pinecone', 'vector_store_id' => $pinecone_index_name, 'vector_store_name' => $pinecone_index_name,
                'status' => 'failed', 'message' => 'Upsert to Pinecone failed: ' . $upsert_result->get_error_message(),
                'embedding_provider' => $embedding_provider_key, 'embedding_model' => $embedding_model,
                'indexed_content' => $file_content_for_embedding,
                'file_id' => $vector_id_for_pinecone, // Log Pinecone vector ID
                'post_title' => sanitize_file_name($file_data['name']),
                'source_type_for_log' => 'chat_file_upload_pinecone',
                'batch_id' => $namespace_for_file_context // Use namespace as a batch/context identifier for logging
            ]);
            return new WP_Error('upsert_failed_pinecone_chat', 'Upsert to Pinecone failed: ' . $upsert_result->get_error_message(), ['status' => 500]);
        }

        $this->logger->log_event([
            'provider' => 'Pinecone', 'vector_store_id' => $pinecone_index_name, 'vector_store_name' => $pinecone_index_name,
            'status' => 'success',
            'message' => 'File content embedded and upserted to Pinecone. Vector ID: ' . $vector_id_for_pinecone . ', Namespace: ' . $namespace_for_file_context,
            'embedding_provider' => $embedding_provider_key, 'embedding_model' => $embedding_model,
            'indexed_content' => $file_content_for_embedding, // Log full content here
            'file_id' => $vector_id_for_pinecone,
            'post_title' => sanitize_file_name($file_data['name']),
            'source_type_for_log' => 'chat_file_upload_pinecone',
            'batch_id' => $namespace_for_file_context
        ]);

        return [
            'message' => __('File processed for Pinecone chat.', 'gpt3-ai-content-generator'),
            'file_context' => [
                'provider' => 'Pinecone',
                'index_name' => $pinecone_index_name,
                'namespace' => $namespace_for_file_context,
                'original_filename' => sanitize_file_name($file_data['name'])
            ]
        ];
    }
}
