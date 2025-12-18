<?php

namespace WPAICG\Core\Providers;

use WPAICG\Addons\Ollama\AIPKit_Ollama_API;
use WPAICG\Core\Providers\Traits\ChatCompletionsPayloadTrait;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Ollama Provider Strategy (Pro Addon)
 * - Lives under lib/addons/ollama
 * - No API key required
 * - Chat payload standardized using ChatCompletionsPayloadTrait
 */
class AIPKit_Ollama_Strategy extends BaseProviderStrategy
{
    use ChatCompletionsPayloadTrait; // Standardize messages/instructions mapping

    /** @var AIPKit_Ollama_API */
    private $api;

    public function __construct()
    {
        // Ensure API helper is available
        if (!class_exists('WPAICG\\Addons\\Ollama\\AIPKit_Ollama_API')) {
            $ollama_api_path = defined('WPAICG_PLUGIN_DIR') ? WPAICG_PLUGIN_DIR . 'lib/addons/ollama/class-aipkit-ollama-api.php' : null;
            if ($ollama_api_path && file_exists($ollama_api_path)) {
                require_once $ollama_api_path;
            }
        }
        $this->api = new AIPKit_Ollama_API();
    }

    public function build_api_url(string $operation, array $params): string|WP_Error
    {
        $base_url = !empty($params['base_url']) ? rtrim($params['base_url'], '/') : rtrim(get_option('aipkit_ollama_base_url', 'http://localhost:11434'), '/');

        switch ($operation) {
            case 'chat':
            case 'stream':
                return $base_url . '/api/chat'; // Same endpoint for streaming
            case 'models':
                return $base_url . '/api/tags';
            case 'embeddings':
                return $base_url . '/api/embeddings';
            default:
                return new WP_Error('unsupported_operation', 'This operation is not supported by the Ollama provider.');
        }
    }

    public function get_api_headers(string $api_key, string $operation): array
    {
        $headers = [
            'Content-Type' => 'application/json',
        ];
        // Ollama streams JSON lines; Accept can stay JSON
        $headers['Accept'] = ($operation === 'stream') ? 'application/json' : 'application/json';
        return $headers;
    }

    public function format_chat_payload(string $user_message, string $instructions, array $history, array $ai_params, string $model): array
    {
        // Start from standardized Chat Completions-style payload
        $payload = $this->format_chat_completions_payload($instructions, $history, $user_message, $ai_params, $model, true);

        // Move OpenAI-style params into Ollama options and rename as needed
        $options = [];
        if (isset($payload['temperature'])) {
            $options['temperature'] = (float) $payload['temperature'];
            unset($payload['temperature']);
        }
        if (isset($payload['top_p'])) {
            $options['top_p'] = (float) $payload['top_p'];
            unset($payload['top_p']);
        }
        if (isset($payload['max_tokens'])) {
            $options['num_predict'] = (int) $payload['max_tokens'];
            unset($payload['max_tokens']);
        }
        // Optional: top_k if provided via ai_params
        if (isset($ai_params['top_k'])) {
            $options['top_k'] = (int) $ai_params['top_k'];
        }
        if (!empty($options)) {
            $payload['options'] = $options;
        }

        // Ollama uses stream=false for standard calls
        $payload['stream'] = false;
        return $payload;
    }

    public function parse_chat_response(array $decoded_response, array $request_data): array|WP_Error
    {
        if (isset($decoded_response['error'])) {
            return new WP_Error('ollama_api_error', $decoded_response['error']);
        }

        if (!isset($decoded_response['message']['content'])) {
            return new WP_Error('ollama_response_format_error', 'Unexpected response format from Ollama API.');
        }

        $usage = null;
        if (isset($decoded_response['total_duration'])) {
            $usage = [
                'total_tokens'      => (int) (($decoded_response['prompt_eval_count'] ?? 0) + ($decoded_response['eval_count'] ?? 0)),
                'prompt_tokens'     => (int) ($decoded_response['prompt_eval_count'] ?? 0),
                'completion_tokens' => (int) ($decoded_response['eval_count'] ?? 0),
            ];
        }

        return [
            'content' => (string) ($decoded_response['message']['content'] ?? ''),
            'usage'   => $usage,
        ];
    }

    public function parse_error_response($response_body, int $status_code): string
    {
        if (is_array($response_body) && isset($response_body['error'])) {
            return (string) $response_body['error'];
        }
        if (is_string($response_body)) {
            $decoded = json_decode($response_body, true);
            if (json_last_error() === JSON_ERROR_NONE && isset($decoded['error'])) {
                return (string) $decoded['error'];
            }
        }
        return 'Ollama API error: Unexpected response format.';
    }

    public function get_models(array $api_params = []): array|WP_Error
    {
        $models = $this->api->get_models($api_params);

        if (is_wp_error($models)) {
            return $models;
        }

        if (isset($models['models']) && is_array($models['models'])) {
            return $this->format_model_list($models['models'], 'name', 'name');
        }

        return new WP_Error('ollama_model_format_error', 'Unexpected model format from Ollama API.');
    }

    public function generate_embeddings($input, array $api_params, array $options = []): array|WP_Error
    {
        // Not implemented currently
        return new WP_Error('not_implemented', 'Method not implemented.');
    }

    public function build_sse_payload(array $messages, $system_instruction, array $ai_params, string $model): array
    {
        // Use standardized formatter to normalize message structure, then enable stream
        $payload = $this->format_sse_chat_completions_payload($messages, (string)$system_instruction, $ai_params, $model, true, false);

        // Map OpenAI-style params into Ollama options
        $options = [];
        if (isset($payload['temperature'])) {
            $options['temperature'] = (float) $payload['temperature'];
            unset($payload['temperature']);
        }
        if (isset($payload['top_p'])) {
            $options['top_p'] = (float) $payload['top_p'];
            unset($payload['top_p']);
        }
        if (isset($payload['max_tokens'])) {
            $options['num_predict'] = (int) $payload['max_tokens'];
            unset($payload['max_tokens']);
        }
        if (isset($ai_params['top_k'])) {
            $options['top_k'] = (int) $ai_params['top_k'];
        }
        if (!empty($options)) {
            $payload['options'] = $options;
        }

        $payload['stream'] = true; // Explicit
        return $payload;
    }

    public function parse_sse_chunk(string $sse_chunk, string &$current_buffer): array
    {
        // Ollama sends JSON lines (NDJSON), not classic SSE events
        $current_buffer .= $sse_chunk;
        $lines = explode("\n", $current_buffer);
        $current_buffer = array_pop($lines); // keep last partial in buffer

        $result = [
            'delta'      => null,
            'usage'      => null,
            'is_error'   => false,
            'is_warning' => false,
            'is_done'    => false,
        ];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') { continue; }

            $data = json_decode($line, true);
            if (json_last_error() !== JSON_ERROR_NONE) { continue; }

            if (isset($data['error'])) {
                $result['is_error'] = true;
                $result['delta'] = (string) $data['error'];
                continue;
            }

            if (isset($data['message']['content'])) {
                $result['delta'] = (string) $data['message']['content'];
            }

            if (!empty($data['done'])) {
                $result['is_done'] = true;
                if (isset($data['total_duration'])) {
                    $result['usage'] = [
                        'total_tokens'      => (int) (($data['prompt_eval_count'] ?? 0) + ($data['eval_count'] ?? 0)),
                        'prompt_tokens'     => (int) ($data['prompt_eval_count'] ?? 0),
                        'completion_tokens' => (int) ($data['eval_count'] ?? 0),
                    ];
                }
            }
        }

        return $result;
    }
}
