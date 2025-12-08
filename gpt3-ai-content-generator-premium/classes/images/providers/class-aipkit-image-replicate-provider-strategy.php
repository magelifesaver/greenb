<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/images/providers/class-aipkit-image-replicate-provider-strategy.php
// Status: MODIFIED

namespace WPAICG\Images\Providers;

use WPAICG\Images\AIPKit_Image_Base_Provider_Strategy;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Replicate Image Generation Provider Strategy.
 * Handles the asynchronous prediction workflow of the Replicate API.
 */
class AIPKit_Image_Replicate_Provider_Strategy extends AIPKit_Image_Base_Provider_Strategy
{
    private const POLLING_INTERVAL = 2; // seconds
    private const POLLING_TIMEOUT_ITERATIONS = 30; // 30 iterations * 2s = 60s timeout

    /**
     * Get API headers required for Replicate requests.
     */
    public function get_api_headers(string $api_key, string $operation): array
    {
        $headers = [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json'
        ];
        if ($operation === 'create_prediction') {
            // Use sync mode and wait up to 50 seconds. This is a tradeoff.
            // A web request can't block forever. Many models will finish in this time.
            $headers['Prefer'] = 'wait=50';
        }
        return $headers;
    }

    /**
     * Get provider-specific request options. Replicate uses GET for polling.
     */
    public function get_request_options(string $operation): array
    {
        $options = parent::get_request_options($operation);
        if ($operation === 'get_prediction' || $operation === 'models') {
            $options['method'] = 'GET';
        }
        // For the creation request, we increase the timeout to match the `Prefer: wait` header value.
        if ($operation === 'create_prediction') {
            $options['timeout'] = 60; // Slightly more than the Prefer:wait value
        }
        return $options;
    }

    /**
     * Get the list of available text-to-image models from Replicate's collection.
     */
    public function get_models(array $api_params): array|WP_Error
    {
        $api_key = $api_params['api_key'] ?? null;
        if (empty($api_key)) {
            return new WP_Error('replicate_missing_key', __('Replicate API Key is required.', 'gpt3-ai-content-generator'));
        }

        $url = 'https://api.replicate.com/v1/collections/text-to-image';
        $headers = $this->get_api_headers($api_key, 'models');
        $request_options = $this->get_request_options('models');

        $response = wp_remote_get($url, array_merge($request_options, ['headers' => $headers]));
        if (is_wp_error($response)) {
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        if ($status_code !== 200) {
            return new WP_Error('replicate_models_api_error', 'Failed to fetch models: ' . $this->parse_error_response($body, $status_code, 'Replicate Models'));
        }

        $decoded = $this->decode_json($body, 'Replicate Models');
        if (is_wp_error($decoded)) {
            return $decoded;
        }

        $raw_models = $decoded['models'] ?? [];

        // Format to standard structure
        $formatted_models = [];
        foreach ($raw_models as $model) {
            if (!empty($model['latest_version']['id'])) {
                $formatted_models[] = [
                    'id' => $model['owner'] . '/' . $model['name'] . ':' . $model['latest_version']['id'],
                    'name' => $model['owner'] . '/' . $model['name']
                ];
            }
        }
        return $formatted_models;
    }

    /**
     * Generate an image by creating and polling a prediction on Replicate.
     */
    public function generate_image(string $prompt, array $api_params, array $options = []): array|WP_Error
    {
        $api_key = $api_params['api_key'] ?? null;
        if (empty($api_key)) {
            return new WP_Error('replicate_missing_key', __('Replicate API Key is required.', 'gpt3-ai-content-generator'));
        }
        if (empty($options['model'])) {
            return new WP_Error('replicate_missing_model', __('Replicate model/version ID is required.', 'gpt3-ai-content-generator'));
        }

        // 1. Create Prediction (using sync mode via headers)
        $input_params = ['prompt' => $prompt];
        
        // Get Replicate settings to check for disable_safety_checker
        if (class_exists('\WPAICG\Images\AIPKit_Image_Settings_Ajax_Handler')) {
            $image_settings = \WPAICG\Images\AIPKit_Image_Settings_Ajax_Handler::get_settings();
            $replicate_settings = $image_settings['replicate'] ?? [];
            $disable_safety_checker = $replicate_settings['disable_safety_checker'] ?? true;
            
            // Add disable_safety_checker to input if enabled
            if ($disable_safety_checker) {
                $input_params['disable_safety_checker'] = true;
            }
        } else {
            // Fallback: disable safety checker by default if settings class not available
            $input_params['disable_safety_checker'] = true;
        }
        
        $create_payload = [
            'version' => explode(':', $options['model'])[1] ?? $options['model'],
            'input' => $input_params
        ];
    
        $create_url = 'https://api.replicate.com/v1/predictions';
        $create_headers = $this->get_api_headers($api_key, 'create_prediction');
        $create_options = $this->get_request_options('create_prediction');

        $create_response = wp_remote_post($create_url, array_merge($create_options, ['headers' => $create_headers, 'body' => json_encode($create_payload)]));

        if (is_wp_error($create_response)) {
            return $create_response;
        }

        $create_status_code = wp_remote_retrieve_response_code($create_response);
        $create_body = wp_remote_retrieve_body($create_response);

        $create_decoded = $this->decode_json($create_body, 'Replicate Create Prediction');
        if (is_wp_error($create_decoded)) {
            return $create_decoded;
        }

        if ($create_status_code >= 300) {
            return new WP_Error('replicate_create_error', 'Failed to create prediction: ' . $this->parse_error_response($create_body, $create_status_code, 'Replicate Create Prediction'));
        }

        $status = $create_decoded['status'] ?? 'unknown';

        if ($status === 'succeeded') {
            // Finished in sync mode, process result directly.
            return $this->format_successful_response($create_decoded);
        } elseif ($status === 'starting' || $status === 'processing') {
            // Timed out, must poll.
            $get_url = $create_decoded['urls']['get'] ?? null;
            if (!$get_url) {
                return new WP_Error('replicate_no_get_url', 'Replicate API did not return a URL to get the prediction status after sync timeout.');
            }
            return $this->poll_for_result($api_key, $get_url);
        } elseif ($status === 'failed' || $status === 'canceled') {
            return new WP_Error('replicate_prediction_failed_initial', 'Prediction failed or was canceled. Error: ' . ($create_decoded['error'] ?? 'Unknown reason.'));
        } else {
            return new WP_Error('replicate_unknown_status', 'Received unknown prediction status: ' . esc_html($status));
        }
    }

    /**
     * Polls the Replicate API for a prediction result.
     * @param string $api_key
     * @param string $get_url
     * @return array|WP_Error
     */
    private function poll_for_result(string $api_key, string $get_url): array|WP_Error
    {
        $poll_headers = $this->get_api_headers($api_key, 'get_prediction');
        $poll_options = $this->get_request_options('get_prediction');
        for ($i = 0; $i < self::POLLING_TIMEOUT_ITERATIONS; $i++) {
            sleep(self::POLLING_INTERVAL);

            $poll_response = wp_remote_get($get_url, array_merge($poll_options, ['headers' => $poll_headers]));
            if (is_wp_error($poll_response)) {
                return $poll_response;
            }

            $poll_status_code = wp_remote_retrieve_response_code($poll_response);
            $poll_body = wp_remote_retrieve_body($poll_response);

            $poll_decoded = $this->decode_json($poll_body, 'Replicate Poll Prediction');
            if ($poll_status_code >= 300) {
                return new WP_Error('replicate_poll_error', 'Error polling prediction: ' . $this->parse_error_response($poll_body, $poll_status_code, 'Replicate Poll Prediction'));
            }

            $status = $poll_decoded['status'] ?? 'unknown';
            if ($status === 'succeeded') {
                return $this->format_successful_response($poll_decoded);
            } elseif ($status === 'failed' || $status === 'canceled') {
                return new WP_Error('replicate_prediction_failed', 'Prediction failed or was canceled. Error: ' . ($poll_decoded['error'] ?? 'Unknown reason.'));
            }
            // Continue polling if status is 'starting' or 'processing'
        }
        return new WP_Error('replicate_timeout', 'Prediction timed out after ' . (self::POLLING_TIMEOUT_ITERATIONS * self::POLLING_INTERVAL) . ' seconds.');
    }

    /**
     * Formats a successful prediction response into the standard structure.
     * @param array $decoded_response
     * @return array|WP_Error
     */
    private function format_successful_response(array $decoded_response): array|WP_Error
    {
        $output = $decoded_response['output'] ?? null;
        if (!$output) {
            return new WP_Error('replicate_no_output', 'Prediction succeeded but no output was found.');
        }

        $image_urls = is_array($output) ? $output : [$output];
        $images = array_map(fn ($url) => ['url' => $url, 'b64_json' => null], $image_urls);

        $predict_time = $decoded_response['metrics']['predict_time'] ?? 0;
        $estimated_tokens = round($predict_time * 500);
        $usage = ['total_tokens' => $estimated_tokens];

        return ['images' => $images, 'usage' => $usage];
    }


    /**
     * Sizes are model-specific on Replicate, so we return an empty array.
     */
    public function get_supported_sizes(): array
    {
        return [];
    }
}
