<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/core/stream/processor/sse/start/class-sse-stream-initializer.php
// Status: NEW FILE

namespace WPAICG\Core\Stream\Processor\SSE\Start;

use WPAICG\Core\Stream\Processor\SSEStreamProcessor;
use WPAICG\Core\Providers\ProviderStrategyInterface;
use WPAICG\Core\Providers\Google\GoogleSettingsHandler;
use WPAICG\Core\AIPKit_Payload_Sanitizer;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Initializes the stream state, builds API URL, and formats the payload for SSE.
 */
class SSEStreamInitializer {

    private $processorInstance;

    public function __construct(SSEStreamProcessor $processorInstance) {
        $this->processorInstance = $processorInstance;

        if (!class_exists(GoogleSettingsHandler::class)) {
            $google_bootstrap_path = WPAICG_PLUGIN_DIR . 'classes/core/providers/google/bootstrap-settings-handler.php';
            if (file_exists($google_bootstrap_path)) {
                require_once $google_bootstrap_path;
            }
        }
        if (!class_exists(AIPKit_Payload_Sanitizer::class)) {
            $sanitizer_path = WPAICG_PLUGIN_DIR . 'classes/core/class-aipkit-payload-sanitizer.php';
            if (file_exists($sanitizer_path)) {
                require_once $sanitizer_path;
            }
        }
    }

    /**
     * Initializes stream state and prepares request parameters.
     *
     * @param string $provider
     * @param string $model
     * @param string $conversation_uuid
     * @param string|null $bot_message_id
     * @param array $base_log_data
     * @param string $stream_context
     * @param bool $used_previous_openai_id
     * @param array $api_params
     * @param string $system_instruction_filtered
     * @param array $history
     * @param string $user_message
     * @param array $ai_params
     * @return array ['endpoint_url' => string, 'curl_headers' => array, 'curl_post_json' => string]|WP_Error
     */
    public function initialize(
        string $provider,
        string $model,
        string $conversation_uuid,
        ?string $bot_message_id,
        array $base_log_data,
        string $stream_context,
        bool $used_previous_openai_id,
        array $api_params,
        string $system_instruction_filtered,
        array $history,
        string $user_message,
        array $ai_params
    ): array|WP_Error {
        $strategy = $this->processorInstance->get_strategy();
        if (!$strategy) {
            return new WP_Error('strategy_not_set_initializer', 'AI Provider strategy is not set for initialization.', ['status' => 500]);
        }

        $this->processorInstance->initialize_stream_state(
            $provider, $model, $conversation_uuid, $bot_message_id, $base_log_data,
            $stream_context, $used_previous_openai_id
        );

        if (empty($this->processorInstance->get_current_bot_message_id())) {
            return new WP_Error('missing_bot_message_id_initializer', 'Internal error: Missing bot message ID for stream.', ['status' => 500]);
        }

        $url_operation = 'stream';
        $url_params = array_merge($api_params, ['model' => $model, 'deployment' => $model]);
        $endpoint_url = $strategy->build_api_url($url_operation, $url_params);
        if (is_wp_error($endpoint_url)) {
            return $endpoint_url;
        }

        $headers = $strategy->get_api_headers($api_params['api_key'], 'stream');
        $curl_headers = $strategy->format_headers_for_curl($headers);

        $messages_for_strategy_payload = $history;
        if (($this->processorInstance->get_current_stream_context() === 'content_writer' || $this->processorInstance->get_current_stream_context() === 'ai_forms') && !empty($user_message)) {
            $messages_for_strategy_payload[] = ['role' => 'user', 'content' => $user_message];
        }

        $final_ai_params = array_merge($ai_params, $api_params);
        if ($provider === 'Google' && !isset($final_ai_params['safety_settings']) && class_exists(GoogleSettingsHandler::class)) {
            $final_ai_params['safety_settings'] = GoogleSettingsHandler::get_safety_settings();
        }

        $curl_post_data = $strategy->build_sse_payload(
            $messages_for_strategy_payload,
            $system_instruction_filtered,
            $final_ai_params,
            $model
        );

        $sanitized_curl_post_data_for_log = class_exists(AIPKit_Payload_Sanitizer::class)
            ? AIPKit_Payload_Sanitizer::sanitize_for_logging($curl_post_data)
            : $curl_post_data; // Fallback if sanitizer not loaded
        $this->processorInstance->set_request_payload_log([
            'provider' => $provider, 'model' => $model, 'payload_sent' => $sanitized_curl_post_data_for_log,
        ]);
        $curl_post_json_for_log = json_encode($sanitized_curl_post_data_for_log);

        $curl_post_data_filtered = apply_filters('aipkit_ai_query', $curl_post_data, $provider, $model, $history, $system_instruction_filtered, $api_params, $ai_params);
        $curl_post_json = json_encode($curl_post_data_filtered);
        return [
            'endpoint_url'   => $endpoint_url,
            'curl_headers'   => $curl_headers,
            'curl_post_json' => $curl_post_json,
        ];
    }
}