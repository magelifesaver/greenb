<?php

namespace WPAICG\Addons\Ollama;

if (!defined('ABSPATH')) {
    exit;
}

class AIPKit_Ollama_API
{
    public function get_models($api_params)
    {
        $base_url = !empty($api_params['base_url']) ? $api_params['base_url'] : get_option('aipkit_ollama_base_url', 'http://localhost:11434');
        $response = wp_remote_get($base_url . '/api/tags');

        if (is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new \WP_Error('json_decode_error', 'Failed to decode JSON response from Ollama API.');
        }

        return $data;
    }

    public function chat($payload)
    {
        $base_url = !empty($payload['base_url']) ? $payload['base_url'] : get_option('aipkit_ollama_base_url', 'http://localhost:11434');
        
        $response = wp_remote_post(
            $base_url . '/api/chat',
            [
                'body'    => json_encode($payload),
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'timeout' => 120,
            ]
        );

        if (is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new \WP_Error('json_decode_error', 'Failed to decode JSON response from Ollama API.');
        }

        return $data;
    }
}
