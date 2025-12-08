<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/lib/chat/frontend/ajax/handlers/class-chat-file-upload-qdrant-handler.php
// Status: MODIFIED

namespace WPAICG\Lib\Chat\Frontend\Ajax\Handlers;

use WPAICG\Lib\Chat\Frontend\Ajax\Utils\ChatUploadLogger;
use WPAICG\Lib\Chat\Frontend\Ajax\Utils\ChatUploadConfigLoader;
use WPAICG\AIPKit_Providers; // For fetching global provider settings
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
 * ChatFileUploadQdrantHandler
 *
 * Handles Qdrant-specific file upload logic for chat context.
 */
class ChatFileUploadQdrantHandler
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
        if (!class_exists(BotStorage::class)) {
            $bot_storage_path = WPAICG_PLUGIN_DIR . 'classes/chat/storage/class-aipkit_chat_bot_storage.php';
            if (file_exists($bot_storage_path)) {
                require_once $bot_storage_path;
            }
        }
        if (class_exists(BotStorage::class)) {
            $this->bot_storage = new BotStorage();
        } else {
            $this->bot_storage = null;
        }


        if (!class_exists(AIPKit_AI_Caller::class)) {
            $ai_caller_path = WPAICG_PLUGIN_DIR . 'classes/core/class-aipkit_ai_caller.php';
            if (file_exists($ai_caller_path)) {
                require_once $ai_caller_path;
            }
        }
        if (class_exists(AIPKit_AI_Caller::class)) {
            $this->ai_caller = new AIPKit_AI_Caller();
        } else {
            $this->ai_caller = null;
        }

        // Ensure AIPKit_Providers is loaded
        if (!class_exists(AIPKit_Providers::class)) {
            $providers_path = WPAICG_PLUGIN_DIR . 'classes/dashboard/class-aipkit_providers.php';
            if (file_exists($providers_path)) {
                require_once $providers_path;
            }
        }

        // Ensure AIPKit_Identifier_Utils is loaded for generating context ID
        if (!class_exists(AIPKit_Identifier_Utils::class)) {
            $identifier_utils_path = WPAICG_PLUGIN_DIR . 'includes/utils/class-aipkit-identifier-utils.php';
            if (file_exists($identifier_utils_path)) {
                require_once $identifier_utils_path;
            }
        }

        // Ensure AIPKit_Pdf_Parser is loaded for PDF processing
        if (!class_exists(AIPKit_Pdf_Parser::class)) {
            $pdf_parser_path = WPAICG_LIB_DIR . 'utils/class-aipkit-pdf-parser.php';
            if (file_exists($pdf_parser_path)) {
                require_once $pdf_parser_path;
            }
        }

        $this->vector_store_manager = $this->config_loader->get_vector_store_manager();
    }

    /**
     * Gets Qdrant API configuration from global settings.
     *
     * @return array|WP_Error An array containing 'url' and 'api_key', or WP_Error on failure.
     */
    public function get_provider_api_config(): array|WP_Error
    {
        if (!class_exists(AIPKit_Providers::class)) {
            return new WP_Error(
                'dependency_missing_qdrant_handler_config',
                __('Provider configuration component is missing for Qdrant.', 'gpt3-ai-content-generator'),
                ['status' => 500]
            );
        }

        $qdrant_data = AIPKit_Providers::get_provider_data('Qdrant');

        if (empty($qdrant_data['url'])) {
            return new WP_Error(
                'missing_qdrant_url_config',
                __('Qdrant URL is not configured in global settings.', 'gpt3-ai-content-generator'),
                ['status' => 500]
            );
        }
        if (empty($qdrant_data['api_key'])) {
            return new WP_Error(
                'missing_qdrant_api_key_config',
                __('Qdrant API Key is not configured in global settings.', 'gpt3-ai-content-generator'),
                ['status' => 500]
            );
        }

        return ['url' => $qdrant_data['url'], 'api_key' => $qdrant_data['api_key']];
    }

    /**
     * Processes the file upload for Qdrant.
     *
     * @param array $file_data The $_FILES entry for the uploaded file.
     * @param int|null $user_id Current user ID (null for guests).
     * @param string|null $session_id Guest session ID.
     * @param array $qdrant_config Qdrant API connection config.
     * @param int $bot_id The ID of the chatbot.
     * @return array|WP_Error Success data or WP_Error.
     */
    public function process_upload(array $file_data, ?int $user_id, ?string $session_id, array $qdrant_config, int $bot_id): array|WP_Error
    {
        // Step 1.1: Verify Dependencies
        if (!$this->bot_storage || !$this->ai_caller || !$this->vector_store_manager) {
            return new WP_Error('internal_error_qdrant_deps_process', __('Core components for Qdrant file upload are not available.', 'gpt3-ai-content-generator'), ['status' => 500]);
        }

        // Step 1.2: Fetch Bot Settings
        $bot_settings = $this->bot_storage->get_chatbot_settings($bot_id);
        $qdrant_collection_name = $bot_settings['qdrant_collection_name'] ?? null;
        $embedding_provider_key = $bot_settings['vector_embedding_provider'] ?? null;
        $embedding_model = $bot_settings['vector_embedding_model'] ?? null;

        if (empty($qdrant_collection_name)) {
            return new WP_Error('qdrant_collection_not_configured', __('This chatbot is not configured for Qdrant file uploads (collection name missing).', 'gpt3-ai-content-generator'), ['status' => 400]);
        }
        if (empty($embedding_provider_key) || empty($embedding_model)) {
            return new WP_Error('embedding_config_missing_qdrant', __('Embedding provider or model is not configured for this chatbot.', 'gpt3-ai-content-generator'), ['status' => 400]);
        }

        // Step 1.3: Extract File Content
        $file_content_for_embedding = '';
        $uploaded_file_mime_type = '';
        if (function_exists('mime_content_type') && is_readable($file_data['tmp_name'])) {
            $uploaded_file_mime_type = mime_content_type($file_data['tmp_name']);
        } elseif (isset($file_data['type'])) {
            $uploaded_file_mime_type = $file_data['type'];
        }

        $log_data_base = [
            'provider' => 'Qdrant', 'vector_store_id' => $qdrant_collection_name, 'vector_store_name' => $qdrant_collection_name,
            'embedding_provider' => $embedding_provider_key, 'embedding_model' => $embedding_model,
            'indexed_content' => sanitize_file_name($file_data['name']),
            'post_title' => sanitize_file_name($file_data['name']),
            'source_type_for_log' => 'chat_file_upload_qdrant'
        ];

        if ($uploaded_file_mime_type === 'application/pdf') {
            if (!class_exists(AIPKit_Pdf_Parser::class)) {
                return new WP_Error('pdf_parser_missing_lib_qdrant', __('PDF parsing utility is missing (Qdrant).', 'gpt3-ai-content-generator'), ['status' => 500]);
            }
            $pdf_parser = new AIPKit_Pdf_Parser();
            $extracted_text_or_error = $pdf_parser->extract_text($file_data['tmp_name']);
            if (is_wp_error($extracted_text_or_error)) {
                $this->logger->log_event(array_merge($log_data_base, ['status' => 'content_error', 'message' => 'PDF parsing failed: ' . $extracted_text_or_error->get_error_message()]));
                return new WP_Error('pdf_parsing_failed_qdrant_chat', 'PDF Parsing Failed: ' . $extracted_text_or_error->get_error_message(), ['status' => 500]);
            }
            $file_content_for_embedding = $extracted_text_or_error;
        } elseif ($uploaded_file_mime_type === 'text/plain') {
            $file_content_for_embedding = file_get_contents($file_data['tmp_name']);
        } else {
            return new WP_Error('unsupported_file_type_qdrant_chat_process', __('Unsupported file type for Qdrant chat upload. Given: ', 'gpt3-ai-content-generator') . $uploaded_file_mime_type, ['status' => 400]);
        }

        if ($file_content_for_embedding === false || empty(trim($file_content_for_embedding))) {
            $this->logger->log_event(array_merge($log_data_base, ['status' => 'content_error', 'message' => 'Could not read file content or file is empty for Qdrant chat upload.']));
            return new WP_Error('file_read_error_qdrant_chat_process', __('Could not read file content or file is empty.', 'gpt3-ai-content-generator'), ['status' => 500]);
        }
        // Update indexed_content for logging now that we have it
        $log_data_base['indexed_content'] = $file_content_for_embedding;


        // Step 1.4: Generate Embeddings
        $provider_map = ['openai' => 'OpenAI', 'google' => 'Google', 'azure' => 'Azure'];
        $embedding_provider_norm = $provider_map[strtolower($embedding_provider_key)] ?? ucfirst($embedding_provider_key);
        $embedding_options = ['model' => $embedding_model];
        $embedding_result = $this->ai_caller->generate_embeddings($embedding_provider_norm, $file_content_for_embedding, $embedding_options);

        if (is_wp_error($embedding_result) || empty($embedding_result['embeddings'][0])) {
            $error_msg = is_wp_error($embedding_result) ? $embedding_result->get_error_message() : 'No embeddings returned.';
            $this->logger->log_event(array_merge($log_data_base, ['status' => 'failed', 'message' => 'Embedding failed: ' . $error_msg]));
            return new WP_Error('embedding_failed_qdrant_chat_process', __('Failed to generate vector for file content.', 'gpt3-ai-content-generator'), ['status' => 500]);
        }
        $vector_values = $embedding_result['embeddings'][0];

        // Step 1.5: Generate Unique File Context ID
        if (!class_exists(AIPKit_Identifier_Utils::class)) {
            return new WP_Error('identifier_util_missing_lib_qdrant', __('Identifier utility is missing.', 'gpt3-ai-content-generator'), ['status' => 500]);
        }
        $file_upload_context_id = AIPKit_Identifier_Utils::generate_chat_context_identifier($user_id, $session_id, 'qdrant_chat_file_');

        // Step 1.6: Prepare Vector(s) for Upsert
        $qdrant_point_id = wp_generate_uuid4();
        $payload_for_qdrant = [
            'source' => 'chat_file_upload',
            'original_filename' => sanitize_file_name($file_data['name']),
            'file_upload_context_id' => $file_upload_context_id,
            'user_id' => $user_id ? (string)$user_id : null,
            'session_id' => $session_id,
            'timestamp' => time(),
            'original_content' => $file_content_for_embedding, // Store full content if feasible and desired
        ];
        $points_to_upsert = [['id' => $qdrant_point_id, 'vector' => $vector_values, 'payload' => $payload_for_qdrant]];

        // Step 1.7: Upsert Vectors to Qdrant Collection
        $upsert_result = $this->vector_store_manager->upsert_vectors('Qdrant', $qdrant_collection_name, ['points' => $points_to_upsert], $qdrant_config);

        if (is_wp_error($upsert_result)) {
            $this->logger->log_event(array_merge($log_data_base, [
                'status' => 'failed', 'message' => 'Upsert to Qdrant failed: ' . $upsert_result->get_error_message(),
                'file_id' => $qdrant_point_id,
                'batch_id' => $file_upload_context_id
            ]));
            return new WP_Error('upsert_failed_qdrant_chat_process', 'Upsert to Qdrant failed: ' . $upsert_result->get_error_message(), ['status' => 500]);
        }

        // Step 1.8: Log Success
        $this->logger->log_event(array_merge($log_data_base, [
            'status' => 'success',
            'message' => 'File content embedded and upserted to Qdrant for chat. Point ID: ' . $qdrant_point_id . ', Context ID: ' . $file_upload_context_id,
            'file_id' => $qdrant_point_id,
            'batch_id' => $file_upload_context_id
        ]));

        // Step 1.9: Return Success Data to Frontend
        return [
            'message' => __('File processed and context established for Qdrant.', 'gpt3-ai-content-generator'),
            'file_context' => [
                'provider' => 'Qdrant',
                'collection_name' => $qdrant_collection_name,
                'file_upload_context_id' => $file_upload_context_id,
                'original_filename' => sanitize_file_name($file_data['name'])
            ]
        ];
    }
}
