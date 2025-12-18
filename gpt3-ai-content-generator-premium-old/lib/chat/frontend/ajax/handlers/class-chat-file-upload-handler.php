<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/lib/chat/frontend/ajax/handlers/class-chat-file-upload-handler.php
// Status: MODIFIED

namespace WPAICG\Lib\Chat\Frontend\Ajax\Handlers;

use WPAICG\Lib\Chat\Frontend\Ajax\Utils\ChatUploadValidator;
use WPAICG\Lib\Chat\Frontend\Ajax\Utils\ChatUploadLogger;
use WPAICG\Lib\Chat\Frontend\Ajax\Utils\ChatUploadConfigLoader;
use WPAICG\Lib\Chat\Frontend\Ajax\Handlers\ChatFileUploadOpenAIHandler;
use WPAICG\Lib\Chat\Frontend\Ajax\Handlers\ChatFileUploadPineconeHandler;
use WPAICG\Lib\Chat\Frontend\Ajax\Handlers\ChatFileUploadQdrantHandler;
use WPAICG\aipkit_dashboard;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * ChatFileUploadHandler
 *
 * Orchestrates the file upload process: validates, routes to provider-specific handlers,
 * and manages logging.
 */
class ChatFileUploadHandler
{
    private $validator;
    private $logger;
    private $config_loader;

    public function __construct(ChatUploadConfigLoader $config_loader)
    {
        $this->config_loader = $config_loader;
        $this->validator = new ChatUploadValidator();
        $this->logger = new ChatUploadLogger($config_loader->get_wpdb(), $config_loader->get_data_source_table_name());
    }

    /**
     * Handles the validated file upload request and routes to the correct provider.
     *
     * @param array $post_data The sanitized $_POST data.
     * @param array $files_data The $_FILES superglobal.
     * @return array|WP_Error Success data or WP_Error.
     */
    public function handle_upload(array $post_data, array $files_data): array|WP_Error
    {
        if (!class_exists(aipkit_dashboard::class) || !aipkit_dashboard::is_pro_plan() || !aipkit_dashboard::is_addon_active('file_upload')) {
            return new WP_Error('pro_feature_required', __('File upload for chat requires a Pro plan and the File Upload addon to be active.', 'gpt3-ai-content-generator'), ['status' => 403]);
        }

        if (!isset($files_data['file_to_upload']) || empty($files_data['file_to_upload']['tmp_name'])) {
            return new WP_Error('no_file', __('No file provided.', 'gpt3-ai-content-generator'), ['status' => 400]);
        }
        $file_data = $files_data['file_to_upload'];

        $validation_result = $this->validator->validate_file($file_data);
        if (is_wp_error($validation_result)) {
            return $validation_result;
        }

        $provider_slug = isset($post_data['upload_provider']) ? sanitize_key(strtolower($post_data['upload_provider'])) : '';
        $user_id = get_current_user_id();
        $session_id = isset($post_data['session_id']) ? sanitize_text_field(wp_unslash($post_data['session_id'])) : null;
        $bot_id = isset($post_data['bot_id']) ? absint($post_data['bot_id']) : 0;


        $provider_handler = null;
        $provider_config = null;

        switch ($provider_slug) {
            case 'openai':
                $provider_handler = new ChatFileUploadOpenAIHandler($this->config_loader, $this->logger);
                $provider_config = $provider_handler->get_provider_api_config();
                break;
            case 'pinecone':
                $provider_handler = new ChatFileUploadPineconeHandler($this->config_loader, $this->logger);
                $provider_config = $provider_handler->get_provider_api_config();
                break;
            case 'qdrant':
                // --- MODIFIED: Instantiate Qdrant handler ---
                if (!class_exists(ChatFileUploadQdrantHandler::class)) {
                    // This should not happen if files are loaded correctly by wpaicg__premium_only.php
                    return new WP_Error('handler_class_missing', __('Qdrant file processing component is missing.', 'gpt3-ai-content-generator'), ['status' => 500]);
                }
                $provider_handler = new ChatFileUploadQdrantHandler($this->config_loader, $this->logger);
                $provider_config = $provider_handler->get_provider_api_config();
                // --- END MODIFICATION ---
                break;
            default:
                return new WP_Error('unsupported_provider', __('Unsupported provider for file upload.', 'gpt3-ai-content-generator'), ['status' => 400]);
        }

        if (is_wp_error($provider_config)) {
            return $provider_config; // API key or URL might be missing
        }

        if (!$provider_handler || !method_exists($provider_handler, 'process_upload')) {
            return new WP_Error('handler_error', __('File processing handler for the selected provider is not available.', 'gpt3-ai-content-generator'), ['status' => 500]);
        }

        return $provider_handler->process_upload($file_data, $user_id, $session_id, $provider_config, $bot_id);
    }
}
