<?php
// File: classes/images/providers/class-aipkit-image-google-provider-strategy.php
// REVISED FILE

namespace WPAICG\Images\Providers;

use WPAICG\Images\AIPKit_Image_Base_Provider_Strategy;
use WPAICG\Images\Providers\Google\GoogleImageUrlBuilder;
use WPAICG\Images\Providers\Google\GoogleImagePayloadFormatter;
use WPAICG\Images\Providers\Google\GoogleImageResponseParser;
use WPAICG\Images\Providers\Google\GoogleVideoUrlBuilder;
use WPAICG\Images\Providers\Google\GoogleVideoPayloadFormatter;
use WPAICG\Images\Providers\Google\GoogleVideoResponseParser;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Google Image and Video Generation Provider Strategy.
 * Supports Gemini Flash and Imagen 3 models for images, and Veo 3 for videos.
 */
class AIPKit_Image_Google_Provider_Strategy extends AIPKit_Image_Base_Provider_Strategy {

    public function __construct() {
        $google_image_dir = __DIR__ . '/google/';
        
        // Load image components
        if (!class_exists(GoogleImageUrlBuilder::class) && file_exists($google_image_dir . 'GoogleImageUrlBuilder.php')) {
             require_once $google_image_dir . 'GoogleImageUrlBuilder.php';
        }
        if (!class_exists(GoogleImagePayloadFormatter::class) && file_exists($google_image_dir . 'GoogleImagePayloadFormatter.php')) {
             require_once $google_image_dir . 'GoogleImagePayloadFormatter.php';
        }
        if (!class_exists(GoogleImageResponseParser::class) && file_exists($google_image_dir . 'GoogleImageResponseParser.php')) {
            require_once $google_image_dir . 'GoogleImageResponseParser.php';
        }
        
        // Load video components
        if (!class_exists(GoogleVideoUrlBuilder::class) && file_exists($google_image_dir . 'GoogleVideoUrlBuilder.php')) {
             require_once $google_image_dir . 'GoogleVideoUrlBuilder.php';
        }
        if (!class_exists(GoogleVideoPayloadFormatter::class) && file_exists($google_image_dir . 'GoogleVideoPayloadFormatter.php')) {
             require_once $google_image_dir . 'GoogleVideoPayloadFormatter.php';
        }
        if (!class_exists(GoogleVideoResponseParser::class) && file_exists($google_image_dir . 'GoogleVideoResponseParser.php')) {
            require_once $google_image_dir . 'GoogleVideoResponseParser.php';
        }
    }

    /**
     * Generate an image or video based on a text prompt using Google's services.
     *
     * @param string $prompt The text prompt describing the image or video.
     * @param array $api_params API connection parameters. Must include 'api_key'.
     *                          Optional: 'base_url', 'api_version'.
     * @param array $options Generation options. Must include 'model'.
     *                       Optional: 'n', 'size' (interpreted based on model), 'aspect_ratio', 'negative_prompt', etc.
     * @return array|WP_Error Array containing 'images'/'videos' and 'usage' or WP_Error on failure.
     */
    public function generate_image(string $prompt, array $api_params, array $options = []): array|WP_Error {
        $api_key = $api_params['api_key'] ?? null;
        $model_id = $options['model'] ?? null; // Full model ID like 'gemini-2.0-flash-preview-image-generation' or 'veo-3.0-generate-preview'

        if (empty($api_key)) return new WP_Error('google_missing_key', __('Google API Key is required for generation.', 'gpt3-ai-content-generator'));
        if (empty($model_id)) return new WP_Error('google_missing_model', __('Google model ID is required.', 'gpt3-ai-content-generator'));
        if (empty($prompt)) return new WP_Error('google_missing_prompt', __('Prompt cannot be empty for generation.', 'gpt3-ai-content-generator'));

        // Check if this is a video model and route accordingly
        if ($this->is_video_model($model_id)) {
            return $this->generate_video($prompt, $api_params, $options);
        }

        // Ensure component classes are loaded (they should be by constructor, but defensive check)
        if (!class_exists(GoogleImageUrlBuilder::class) || !class_exists(GoogleImagePayloadFormatter::class) || !class_exists(GoogleImageResponseParser::class)) {
            return new WP_Error('google_image_dependency_missing', __('Google image generation components are missing.', 'gpt3-ai-content-generator'), ['status' => 500]);
        }

        // Pass the full model ID to the URL builder
        $url = GoogleImageUrlBuilder::build($model_id, $api_params);
        if (is_wp_error($url)) return $url;

        // Options already contain the model ID which the formatter will use to switch logic
        $payload = GoogleImagePayloadFormatter::format($prompt, $options);
        if (empty($payload)) { // Formatter might return empty for unsupported models
            return new WP_Error('google_image_payload_error', __('Failed to format payload for Google image model: ', 'gpt3-ai-content-generator') . $model_id);
        }

        $headers_array = $this->get_api_headers($api_key, 'generate');
        $request_options_base = $this->get_request_options('generate');
        $request_body_json = wp_json_encode($payload);

        $request_args = array_merge($request_options_base, [
            'headers' => $headers_array,
            'body' => $request_body_json,
            'data_format' => 'body', // wp_remote_request handles JSON encoding if body is array
        ]);

        $response = wp_remote_post($url, $request_args);

        if (is_wp_error($response)) {
            return new WP_Error('google_image_http_error', __('HTTP error during Google image generation.', 'gpt3-ai-content-generator'));
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        $decoded_response = $this->decode_json($body, 'Google Image Generation');

        if ($status_code !== 200 || is_wp_error($decoded_response)) {
            $error_msg = is_wp_error($decoded_response)
                        ? $decoded_response->get_error_message()
                        : GoogleImageResponseParser::parse_error($body, $status_code);
            /* translators: %1$d: HTTP status code, %2$s: Error message from the API. */
            return new WP_Error('google_image_api_error', sprintf(__('Google Image API Error (%1$d): %2$s', 'gpt3-ai-content-generator'), $status_code, $error_msg));
        }

        return GoogleImageResponseParser::parse($decoded_response, $model_id);
    }

    /**
     * Check if the given model ID is a video model.
     *
     * @param string $model_id The model ID to check.
     * @return bool True if it's a video model, false otherwise.
     */
    private function is_video_model(string $model_id): bool {
        // Prefer synced list of Google Video models; fallback to heuristic
        if (class_exists('\\WPAICG\\AIPKit_Providers')) {
            $video_models = \WPAICG\AIPKit_Providers::get_google_video_models();
            if (is_array($video_models) && !empty($video_models)) {
                $ids = array_map(function($m){ return is_array($m) ? ($m['id'] ?? '') : (is_string($m)? $m : ''); }, $video_models);
                if (in_array($model_id, $ids, true)) {
                    return true;
                }
            }
        }
        return strpos($model_id, 'veo') !== false;
    }

    /**
     * Generate a video using Google's Veo 3 service.
     *
     * @param string $prompt The text prompt describing the video.
     * @param array $api_params API connection parameters.
     * @param array $options Generation options.
     * @return array|WP_Error Array containing 'videos' and 'usage' or WP_Error on failure.
     */
    private function generate_video(string $prompt, array $api_params, array $options): array|WP_Error {
        
        $model_id = $options['model'] ?? null;

        // Ensure video component classes are loaded
        if (!class_exists(GoogleVideoUrlBuilder::class) || !class_exists(GoogleVideoPayloadFormatter::class) || !class_exists(GoogleVideoResponseParser::class)) {
            return new WP_Error('google_video_dependency_missing', __('Google video generation components are missing.', 'gpt3-ai-content-generator'), ['status' => 500]);
        }

        // Build URL for video generation
        $url = GoogleVideoUrlBuilder::build($model_id, $api_params, 'generate');
        if (is_wp_error($url)) {
            return $url;
        }

        // Format payload for video generation
        $payload = GoogleVideoPayloadFormatter::format($prompt, $options);
        if (empty($payload)) {
            return new WP_Error('google_video_payload_error', __('Failed to format payload for Google video model: ', 'gpt3-ai-content-generator') . $model_id);
        }

        $headers_array = $this->get_api_headers($api_params['api_key'] ?? '', 'generate');
        $request_options_base = $this->get_request_options('generate');
        $request_body_json = wp_json_encode($payload);

        $request_args = array_merge($request_options_base, [
            'headers' => $headers_array,
            'body' => $request_body_json,
            'data_format' => 'body',
            'timeout' => 120, // Longer timeout for video generation
        ]);

        $response = wp_remote_post($url, $request_args);

        if (is_wp_error($response)) {
            return new WP_Error('google_video_http_error', __('HTTP error during Google video generation.', 'gpt3-ai-content-generator'));
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        $decoded_response = $this->decode_json($body, 'Google Video Generation');

        if ($status_code !== 200 || is_wp_error($decoded_response)) {
            $error_msg = is_wp_error($decoded_response)
                        ? $decoded_response->get_error_message()
                        : GoogleVideoResponseParser::parse_error($body, $status_code);
            /* translators: %1$d: HTTP status code, %2$s: Error message from the API. */
            return new WP_Error('google_video_api_error', sprintf(__('Google Video API Error (%1$d): %2$s', 'gpt3-ai-content-generator'), $status_code, $error_msg));
        }

        // Parse the response - now returns operation info for async polling
        $parse_result = GoogleVideoResponseParser::parse($decoded_response, $model_id, $api_params);
        
        if (is_wp_error($parse_result)) {
            return $parse_result;
        }
        
        // Check if this is an async operation or completed result
        if (isset($parse_result['status']) && $parse_result['status'] === 'processing') {
            return [
                'status' => 'processing',
                'operation_name' => $parse_result['operation_name'],
                'message' => $parse_result['message']
            ];
        } else {
            return $parse_result;
        }
    }

    /**
     * Get the supported image sizes (Placeholder - needs specific model logic).
     */
    public function get_supported_sizes(): array {
        // For shortcode UI, a common list. Strategy should validate/adapt.
        return ['1024x1024', '1536x1024', '1024x1536', '1024x768', '768x1024'];
    }

    /**
     * Get API headers (Google API key is in URL).
     */
    public function get_api_headers(string $api_key, string $operation): array {
         return ['Content-Type' => 'application/json'];
    }
}
