<?php

namespace WPAICG\Core\Providers; 

use WP_Error;
use WPAICG\Core\Providers\Traits\ChatCompletionsPayloadTrait; 
use WPAICG\Core\Providers\Traits\ChatCompletionsResponseParserTrait; 
use WPAICG\Core\Providers\Traits\ChatCompletionsSSEParserTrait; 

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * DeepSeek Provider Strategy.
 * Uses standard Chat Completions format.
 * Uses traits for payload formatting, response parsing, and SSE parsing.
 * @since NEXT_VERSION
 */
class DeepSeekProviderStrategy extends BaseProviderStrategy {
    use ChatCompletionsPayloadTrait;
    use ChatCompletionsResponseParserTrait;
    use ChatCompletionsSSEParserTrait;

    public function build_api_url(string $operation, array $params): string|WP_Error {
        $base_url = !empty($params['base_url']) ? rtrim($params['base_url'], '/') : '';
        $api_version = !empty($params['api_version']) ? $params['api_version'] : ''; 

        if (empty($base_url)) return new WP_Error("missing_base_url_DeepSeek", __('DeepSeek Base URL is required.', 'gpt3-ai-content-generator'));

        $paths = [
            'chat'   => '/chat/completions', 
            'models' => '/models',
        ];
        $path_key = ($operation === 'stream') ? 'chat' : $operation;
        $path_segment = $paths[$path_key] ?? null;

        if ($path_segment === null) {
            /* translators: %s: The operation name (e.g., "chat", "models"). */
            return new WP_Error('unsupported_operation_DeepSeek', sprintf(__('Operation "%s" not supported for DeepSeek.', 'gpt3-ai-content-generator'), $operation));
        }

        $full_path = $path_segment;
        if (!empty($api_version) && strpos($base_url, '/' . trim($api_version, '/')) === false) {
            $full_path = '/' . trim($api_version, '/') . $path_segment;
        }

        return $base_url . $full_path;
    }

    public function get_api_headers(string $api_key, string $operation): array {
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $api_key,
        ];
        if ($operation === 'stream') {
            $headers['Accept'] = 'text/event-stream';
            $headers['Cache-Control'] = 'no-cache';
        }
        return $headers;
    }

    public function format_chat_payload(string $user_message, string $instructions, array $history, array $ai_params, string $model): array {
       return $this->format_chat_completions_payload($instructions, $history, $user_message, $ai_params, $model, true);
    }

    public function parse_error_response($response_body, int $status_code): string {
        $message = __('An unknown API error occurred.', 'gpt3-ai-content-generator');
        $decoded = is_string($response_body) ? json_decode($response_body, true) : $response_body;

        if (is_array($decoded)) {
            if (!empty($decoded['error']['message'])) {
                $message = $decoded['error']['message'];
                if (!empty($decoded['error']['code'])) { $message .= ' (Code: ' . $decoded['error']['code'] . ')'; }
                if (!empty($decoded['error']['type'])) { $message .= ' Type: ' . $decoded['error']['type']; }
            } elseif (!empty($decoded['message'])) { 
                $message = $decoded['message'];
            }
        } elseif (is_string($response_body)) {
             $message = substr($response_body, 0, 200); 
        }

        return trim($message);
    }

    public function get_models(array $api_params): array|WP_Error {
        $url = $this->build_api_url('models', $api_params);
        if (is_wp_error($url)) return $url;

        $headers = $this->get_api_headers($api_params['api_key'] ?? '', 'models');
        $options = $this->get_request_options('models');
        $options['method'] = 'GET';

        $response = wp_remote_get($url, array_merge($options, ['headers' => $headers]));
        if (is_wp_error($response)) return $response;

        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        if ($status_code !== 200) {
            $error_msg = $this->parse_error_response($body, $status_code);
            return new WP_Error('api_error_deepseek_models', sprintf('DeepSeek API Error (HTTP %d): %s', $status_code, $error_msg));
        }

        $decoded = $this->decode_json($body, 'DeepSeek Models');
        if (is_wp_error($decoded)) return $decoded;

        $raw_models = $decoded['data'] ?? [];
        $chat_models = array_filter($raw_models, function($model) {
            return isset($model['id']) && strpos($model['id'], 'chat') !== false;
        });

        return $this->format_model_list($chat_models, 'id', 'id');
    }

    public function build_sse_payload(array $messages, $system_instruction, array $ai_params, string $model): array {
        return $this->format_sse_chat_completions_payload($messages, $system_instruction, $ai_params, $model, true, false);
    }

    /**
     * Generate embeddings for the given input text(s).
     * DeepSeek API primarily focuses on chat completions and does not have a standard
     * /embeddings endpoint like OpenAI or Google.
     *
     * @param string|array $input The input text or array of texts.
     * @param array $api_params Provider-specific API connection parameters.
     * @param array $options Embedding options (model, dimensions, encoding_format, etc.).
     * @return array|WP_Error Always returns a WP_Error indicating not supported.
     */
    public function generate_embeddings($input, array $api_params, array $options = []): array|WP_Error {
        return new WP_Error(
            'embeddings_not_supported_deepseek',
            __('Dedicated embedding generation is not supported for DeepSeek via this strategy.', 'gpt3-ai-content-generator')
        );
    }
}