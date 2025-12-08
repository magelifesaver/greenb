<?php

namespace WPAICG\Lib\WhatsApp\Core;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Lightweight WhatsApp Cloud API client (Graph API) for sending messages and marking read.
 */
class WhatsApp_Client
{
    public const DEFAULT_GRAPH_VERSION = 'v20.0';

    private function build_base_url(string $graph_version, string $phone_number_id): string
    {
        $version = $graph_version ?: self::DEFAULT_GRAPH_VERSION;
        return sprintf('https://graph.facebook.com/%s/%s', $version, $phone_number_id);
    }

    private function post_json(string $url, array $body, string $access_token): array
    {
        $args = [
            'method'  => 'POST',
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type'  => 'application/json',
            ],
            'body'    => wp_json_encode($body),
            'timeout' => 15,
        ];
        $response = wp_remote_post($url, $args);
        if (is_wp_error($response)) {
            return ['success' => false, 'error' => $response->get_error_message()];
        }
        $code = wp_remote_retrieve_response_code($response);
        $body_json = json_decode(wp_remote_retrieve_body($response), true);
        if ($code >= 200 && $code < 300) {
            return ['success' => true, 'data' => $body_json];
        }
        return ['success' => false, 'error' => 'HTTP ' . $code, 'data' => $body_json];
    }

    public function mark_read(string $graph_version, string $phone_number_id, string $access_token, string $message_id): array
    {
        $url = $this->build_base_url($graph_version, $phone_number_id) . '/messages';
        $payload = [
            'messaging_product' => 'whatsapp',
            'status'            => 'read',
            'message_id'        => $message_id,
        ];
        return $this->post_json($url, $payload, $access_token);
    }

    public function send_text(string $graph_version, string $phone_number_id, string $access_token, string $to_wa_id, string $text): array
    {
        $url = $this->build_base_url($graph_version, $phone_number_id) . '/messages';
        $payload = [
            'messaging_product' => 'whatsapp',
            'to'                => $to_wa_id,
            'type'              => 'text',
            'text'              => [ 'body' => $text ],
        ];
        return $this->post_json($url, $payload, $access_token);
    }
}

