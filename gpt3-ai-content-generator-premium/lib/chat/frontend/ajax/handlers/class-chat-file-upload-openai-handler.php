<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/lib/chat/frontend/ajax/handlers/class-chat-file-upload-openai-handler.php
// Status: NEW FILE

namespace WPAICG\Lib\Chat\Frontend\Ajax\Handlers;

use WPAICG\Lib\Chat\Frontend\Ajax\Utils\ChatUploadLogger;
use WPAICG\Lib\Chat\Frontend\Ajax\Utils\ChatUploadConfigLoader;
use WPAICG\Vector\AIPKit_Vector_Provider_Strategy_Factory;
use WPAICG\Utils\AIPKit_Identifier_Utils;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * ChatFileUploadOpenAIHandler
 *
 * Handles the OpenAI-specific logic for file uploads in chat.
 */
class ChatFileUploadOpenAIHandler
{
    private $config_loader;
    private $logger;
    private $vector_store_manager;
    private $providers_helper;

    public function __construct(ChatUploadConfigLoader $config_loader, ChatUploadLogger $logger)
    {
        $this->config_loader = $config_loader;
        $this->logger = $logger;
        $this->vector_store_manager = $config_loader->get_vector_store_manager();
        $this->providers_helper = $config_loader->get_providers_helper();
    }

    /**
     * Gets OpenAI API configuration.
     *
     * @return array|WP_Error
     */
    public function get_provider_api_config(): array|WP_Error
    {
        $openai_data = $this->providers_helper::get_provider_data('OpenAI');
        if (empty($openai_data['api_key'])) {
            return new WP_Error('missing_openai_key', __('OpenAI API Key is not configured.', 'gpt3-ai-content-generator'), ['status' => 500]);
        }
        return [
            'api_key'     => $openai_data['api_key'],
            'base_url'    => $openai_data['base_url'] ?? 'https://api.openai.com',
            'api_version' => $openai_data['api_version'] ?? 'v1',
        ];
    }

    /**
     * Processes the file upload for OpenAI.
     *
     * @param array $file_data The $_FILES entry for the uploaded file.
     * @param int|null $user_id Current user ID (null for guests).
     * @param string|null $session_id Guest session ID.
     * @param array $openai_config OpenAI API connection config.
     * @return array|WP_Error Success data or WP_Error.
     */
    public function process_upload(array $file_data, ?int $user_id, ?string $session_id, array $openai_config): array|WP_Error
    {
        if (!$this->vector_store_manager) {
            return new WP_Error('internal_error_openai_vs_manager', __('Vector store processing service not available for OpenAI.', 'gpt3-ai-content-generator'), ['status' => 500]);
        }

        $strategy = AIPKit_Vector_Provider_Strategy_Factory::get_strategy('OpenAI');
        if (is_wp_error($strategy) || !method_exists($strategy, 'upload_file_for_vector_store')) {
            return new WP_Error('strategy_error_openai', __('OpenAI file processing strategy not available.', 'gpt3-ai-content-generator'), ['status' => 500]);
        }
        $connect_result = $strategy->connect($openai_config);
        if (is_wp_error($connect_result) || $connect_result === false) {
            return new WP_Error('strategy_connect_error_openai', __('Could not connect to OpenAI for file upload.', 'gpt3-ai-content-generator'), ['status' => 500]);
        }

        // Upload the file to OpenAI first
        $upload_result = $strategy->upload_file_for_vector_store($file_data['tmp_name'], $file_data['name'], 'assistants');
        if (is_wp_error($upload_result) || !isset($upload_result['id'])) {
            $err_msg = is_wp_error($upload_result) ? $upload_result->get_error_message() : 'Missing file ID in OpenAI upload response.';
            return new WP_Error('openai_file_upload_failed', 'Failed to upload file to OpenAI: ' . $err_msg, ['status' => 500]);
        }
        $openai_file_id = $upload_result['id'];

        // Create a new, temporary vector store for this file and user/session
        $vector_store_name = AIPKit_Identifier_Utils::generate_chat_context_identifier($user_id, $session_id, 'chat_file_vs');
        $openai_provider_settings = $this->providers_helper::get_provider_data('OpenAI');
        $expiration_days = $openai_provider_settings['expiration_policy'] ?? 7;
        $index_config = [
            'file_ids' => [$openai_file_id],
            'expires_after' => ['anchor' => 'last_active_at', 'days' => absint($expiration_days)]
        ];

        $create_store_result = $this->vector_store_manager->create_index_if_not_exists('OpenAI', $vector_store_name, $index_config, $openai_config);
        if (is_wp_error($create_store_result) || !isset($create_store_result['id'])) {
            $err_msg = is_wp_error($create_store_result) ? $create_store_result->get_error_message() : 'Failed to create vector store with file.';
            // Attempt to delete the orphaned OpenAI file if store creation fails
            if (method_exists($strategy, 'delete_openai_file_object')) {
                $strategy->delete_openai_file_object($openai_file_id);
            }
            return new WP_Error('openai_vs_creation_failed', 'OpenAI Vector Store creation error: ' . $err_msg, ['status' => 500]);
        }
        $new_vector_store_id = $create_store_result['id'];

        $this->logger->log_event([
            'provider' => 'OpenAI',
            'vector_store_id' => $new_vector_store_id,
            'vector_store_name' => $vector_store_name,
            'status' => 'success',
            'message' => 'File uploaded and new Vector Store created for chat context. File ID: ' . $openai_file_id,
            'file_id' => $openai_file_id,
            'source_type_for_log' => 'chat_file_upload' // Specific type for this action
        ]);

        return [
            'message' => __('File processed for OpenAI chat.', 'gpt3-ai-content-generator'),
            'file_context' => [
                'provider' => 'OpenAI',
                'vector_store_id' => $new_vector_store_id,
                'original_filename' => sanitize_file_name($file_data['name'])
            ]
        ];
    }
}
