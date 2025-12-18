<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/chat/core/ajax_processor.php
// Status: MODIFIED

namespace WPAICG\Chat\Core;

use WP_Error;
// Dependencies used by logic files will be required within those files or by the class constructor.
// No need for direct use statements for AIPKit_Providers, AIPKIT_AI_Settings, etc., here
// if the logic files handle their own dependencies or they are passed to them.

// Use statements for the new frontend chat sub-processors
use WPAICG\Chat\Core\AjaxProcessor\FrontendChat\ChatMessageValidator;
use WPAICG\Chat\Core\AjaxProcessor\FrontendChat\ChatImageInputProcessor;
use WPAICG\Chat\Core\AjaxProcessor\FrontendChat\ChatTriggerRunner;
use WPAICG\Chat\Core\AjaxProcessor\FrontendChat\ChatContextBuilder;
use WPAICG\Chat\Core\AjaxProcessor\FrontendChat\ChatHistoryManager;
use WPAICG\Chat\Core\AjaxProcessor\FrontendChat\ChatAIRequestRunner;
use WPAICG\Chat\Core\AjaxProcessor\FrontendChat\ChatResponseLogger;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Load method logic files (Original constructor and the new entry point)
require_once __DIR__ . '/ajax-processor/constructor.php';
require_once __DIR__ . '/ajax-processor/ajax_frontend_chat_message.php';

/**
 * Handles processing NON-STREAMING messages sent from the frontend chatbot interface via AJAX (Modularized).
 * MODIFIED: Updated Token Manager type hint.
 * MODIFIED: Instantiates new sub-processor classes.
 */
class AjaxProcessor
{
    private $bot_storage;
    private $ai_service;
    private $log_storage;
    private $token_manager;

    // New sub-processor instances
    private $message_validator;
    private $image_processor;
    private $trigger_runner;
    private $context_builder;
    private $history_manager;
    private $ai_request_runner;
    private $response_logger;


    public function __construct()
    {
        AjaxProcessor\constructor($this); // Existing constructor logic

        // Instantiate new sub-processors, passing dependencies
        // Ensure dependencies for these constructors (like LogStorage, TokenManager) are initialized by AjaxProcessor\constructor($this)
        if ($this->token_manager) {
            $this->message_validator = new ChatMessageValidator($this->token_manager);
        } else {
            $this->message_validator = null;
        }

        $this->image_processor = new ChatImageInputProcessor();

        if ($this->log_storage) {
            $this->trigger_runner = new ChatTriggerRunner($this->log_storage);
        } else {
            $this->trigger_runner = null;
        }

        if ($this->bot_storage) {
            $this->context_builder = new ChatContextBuilder($this->bot_storage);
        } else {
            $this->context_builder = null;
        }

        if ($this->log_storage) {
            $this->history_manager = new ChatHistoryManager($this->log_storage);
        } else {
            $this->history_manager = null;
        }

        if ($this->ai_service) {
            $this->ai_request_runner = new ChatAIRequestRunner($this->ai_service);
        } else {
            $this->ai_request_runner = null;
        }

        if ($this->log_storage && $this->token_manager) {
            $this->response_logger = new ChatResponseLogger($this->log_storage, $this->token_manager);
        } else {
            $this->response_logger = null;
        }
    }

    // --- Setters for constructor to set properties ---
    public function set_bot_storage(?\WPAICG\Chat\Storage\BotStorage $storage): void
    {
        $this->bot_storage = $storage;
    }
    public function set_ai_service(?AIService $service): void
    {
        $this->ai_service = $service;
    }
    public function set_log_storage(?\WPAICG\Chat\Storage\LogStorage $storage): void
    {
        $this->log_storage = $storage;
    }
    public function set_token_manager(?\WPAICG\Core\TokenManager\AIPKit_Token_Manager $manager): void
    {
        $this->token_manager = $manager;
    }
    // --- End Setters ---

    // --- Getters for sub-processors (used by ajax_frontend_chat_message.php logic) ---
    public function get_message_validator(): ?ChatMessageValidator
    {
        return $this->message_validator;
    }
    public function get_image_processor(): ?ChatImageInputProcessor
    {
        return $this->image_processor;
    }
    public function get_trigger_runner(): ?ChatTriggerRunner
    {
        return $this->trigger_runner;
    }
    public function get_context_builder(): ?ChatContextBuilder
    {
        return $this->context_builder;
    }
    public function get_history_manager(): ?ChatHistoryManager
    {
        return $this->history_manager;
    }
    public function get_ai_request_runner(): ?ChatAIRequestRunner
    {
        return $this->ai_request_runner;
    }
    public function get_response_logger(): ?ChatResponseLogger
    {
        return $this->response_logger;
    }
    // --- End Getters for sub-processors ---

    // --- Getters for logic files to access properties (original dependencies) ---
    public function get_bot_storage(): ?\WPAICG\Chat\Storage\BotStorage
    {
        return $this->bot_storage;
    }
    public function get_ai_service(): ?AIService
    {
        return $this->ai_service;
    }
    public function get_log_storage(): ?\WPAICG\Chat\Storage\LogStorage
    {
        return $this->log_storage;
    }
    public function get_token_manager(): ?\WPAICG\Core\TokenManager\AIPKit_Token_Manager
    {
        return $this->token_manager;
    }
    // --- End Getters ---


    public function ajax_frontend_chat_message()
    {
        // Check if all sub-processors are initialized
        if (!$this->message_validator || !$this->image_processor || !$this->trigger_runner ||
            !$this->context_builder || !$this->history_manager || !$this->ai_request_runner ||
            !$this->response_logger) {
            wp_send_json_error(['message' => __('Chat processing service is currently unavailable.', 'gpt3-ai-content-generator')], 503);
            return;
        }
        AjaxProcessor\ajax_frontend_chat_message($this);
    }
}
