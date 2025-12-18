<?php

namespace WPAICG\Lib\WhatsApp\Core;

use WPAICG\Chat\Storage\BotStorage;
use WPAICG\Chat\Storage\LogStorage;
use WPAICG\Chat\Core\AIService;
use WPAICG\Core\TokenManager\AIPKit_Token_Manager;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Orchestrates processing of inbound WhatsApp messages into AI responses.
 */
class WhatsApp_Processor
{
    private $bot_storage;
    private $log_storage;
    private $ai_service;
    private $token_manager;
    private $wa_client;

    public function __construct()
    {
        $this->bot_storage   = new BotStorage();
        $this->log_storage   = new LogStorage();
        $this->ai_service    = new AIService();
        $this->wa_client     = new WhatsApp_Client();
        $this->token_manager = class_exists('\\WPAICG\\Core\\TokenManager\\AIPKit_Token_Manager') ? new AIPKit_Token_Manager() : null;
    }

    /**
     * Process an inbound WhatsApp message.
     *
     * @param array $connector Connector config.
     * @param string $phone_number_id Incoming metadata phone_number_id.
     * @param string $from_wa_id Sender wa_id.
     * @param array $message Full message object (with id, type, text, etc.)
     * @return void
     */
    public function process_incoming(array $connector, string $phone_number_id, string $from_wa_id, array $message): void
    {
        $message_id = $message['id'] ?? '';
        if (empty($message_id)) {
            return; // Nothing to do
        }
        // Idempotency guard (5 minutes)
        $dup_key = 'aipkit_wa_msg_' . md5($message_id);
        if (get_transient($dup_key)) {
            return;
        }
        set_transient($dup_key, 1, 5 * MINUTE_IN_SECONDS);

        $graph_version = $connector['api_version'] ?? WhatsApp_Client::DEFAULT_GRAPH_VERSION;
        $access_token  = $connector['access_token'] ?? '';

        // Mark as read early (best effort)
        if ($access_token) {
            $this->wa_client->mark_read($graph_version, $phone_number_id, $access_token, $message_id);
        }

        // Only handle text messages for MVP
        $type = $message['type'] ?? '';
        $text_body = '';
        if ($type === 'text') {
            $text_body = trim((string)($message['text']['body'] ?? ''));
        }
        if ($text_body === '') {
            // Optional: send a polite unsupported message
            if ($access_token) {
                $this->wa_client->send_text($graph_version, $phone_number_id, $access_token, $from_wa_id, __('Sorry, this message type is not supported yet.', 'gpt3-ai-content-generator'));
            }
            return;
        }

        // Resolve bot id: check bots explicitly mapped to this connector id, else site-wide
        $bot_id = $this->find_mapped_bot_id_for_connector($connector['id'] ?? '');
        if (!$bot_id) {
            $bot_id = $this->bot_storage->get_site_wide_bot_id() ?: 0;
        }
        if (!$bot_id) {
            // No bot to handle; bail
            return;
        }

        // Build conversation identity
        $session_id = 'wa_' . sanitize_key($from_wa_id);
        $conversation_uuid = $session_id; // 1:1 mapping for WA sender

        // Log user message first
        $base_log = [
            'bot_id'            => $bot_id,
            'user_id'           => null,
            'session_id'        => $session_id,
            'conversation_uuid' => $conversation_uuid,
            'module'            => 'chat',
            'message_role'      => 'user',
            'message_content'   => $text_body,
            'timestamp'         => time(),
        ];
        $this->log_storage->log_message($base_log);

        // Gather bot settings and limited history
        $bot_settings = $this->bot_storage->get_chatbot_settings($bot_id);
        $history = $this->log_storage->get_conversation_thread_history(null, $session_id, $bot_id, $conversation_uuid);
        $max_msgs = isset($bot_settings['max_messages']) ? absint($bot_settings['max_messages']) : 15;
        if (count($history) > $max_msgs) {
            $history = array_slice($history, -$max_msgs);
        }

        // Call AI
        $ai_result = $this->ai_service->generate_response(
            $text_body,
            array_merge($bot_settings, ['bot_id' => $bot_id]),
            $history,
            0,
            null,
            false,
            false,
            null,
            null,
            null,
            null,
            null
        );

        if (is_wp_error($ai_result)) {
            // Log error as bot message
            $this->log_storage->log_message([
                'bot_id'            => $bot_id,
                'user_id'           => null,
                'session_id'        => $session_id,
                'conversation_uuid' => $conversation_uuid,
                'module'            => 'chat',
                'message_role'      => 'bot',
                'message_content'   => 'Error: ' . $ai_result->get_error_message(),
                'timestamp'         => time(),
            ]);
            return;
        }

        $reply = (string)($ai_result['content'] ?? '');
        $usage = $ai_result['usage'] ?? null;

        // Log bot reply
        $this->log_storage->log_message([
            'bot_id'            => $bot_id,
            'user_id'           => null,
            'session_id'        => $session_id,
            'conversation_uuid' => $conversation_uuid,
            'module'            => 'chat',
            'message_role'      => 'bot',
            'message_content'   => $reply,
            'timestamp'         => time(),
            'usage'             => $usage,
        ]);

        // Record tokens
        if ($this->token_manager && is_array($usage) && isset($usage['total_tokens'])) {
            $this->token_manager->record_token_usage(null, $session_id, $bot_id, (int)$usage['total_tokens']);
        }

        // Send reply
        if ($access_token && $reply !== '') {
            $this->wa_client->send_text($graph_version, $phone_number_id, $access_token, $from_wa_id, $reply);
        }
    }

    /**
     * Find a bot that has explicitly attached the given connector id.
     */
    private function find_mapped_bot_id_for_connector(string $connector_id): int
    {
        if ($connector_id === '') { return 0; }
        $bots = $this->bot_storage->get_chatbots();
        foreach ($bots as $bot_post) {
            $arr = get_post_meta($bot_post->ID, '_aipkit_whatsapp_connector_ids', true);
            if (is_array($arr) && in_array($connector_id, $arr, true)) {
                return (int)$bot_post->ID;
            }
        }
        return 0;
    }
}
