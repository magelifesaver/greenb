<?php
// File: classes/images/providers/class-aipkit-image-openai-provider-strategy.php

namespace WPAICG\Images\Providers;

use WPAICG\Images\AIPKit_Image_Base_Provider_Strategy;
// NEW: Use the component classes from the openai image subdirectory
use WPAICG\Images\Providers\OpenAI\OpenAIImageUrlBuilder;
use WPAICG\Images\Providers\OpenAI\OpenAIPayloadFormatter;
use WPAICG\Images\Providers\OpenAI\OpenAIImageResponseParser;
// --- END NEW ---
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * OpenAI Image Generation Provider Strategy.
 * Implements generation using OpenAI DALL-E API (v1/images/generations).
 * Delegates logic to specialized component classes.
 */
class AIPKit_Image_OpenAI_Provider_Strategy extends AIPKit_Image_Base_Provider_Strategy {

    /**
     * Constructor. Ensures necessary component classes are loaded.
     */
    public function __construct() {
        // NEW: Load image-specific components
        $openai_image_dir = __DIR__ . '/openai/';
        if (!class_exists(OpenAIImageUrlBuilder::class) && file_exists($openai_image_dir . 'OpenAIImageUrlBuilder.php')) {
            require_once $openai_image_dir . 'OpenAIImageUrlBuilder.php';
        }
        if (!class_exists(OpenAIPayloadFormatter::class) && file_exists($openai_image_dir . 'OpenAIPayloadFormatter.php')) {
            require_once $openai_image_dir . 'OpenAIPayloadFormatter.php';
        }
        if (!class_exists(OpenAIImageResponseParser::class) && file_exists($openai_image_dir . 'OpenAIImageResponseParser.php')) {
            require_once $openai_image_dir . 'OpenAIImageResponseParser.php';
        }
        // --- END NEW ---
    }

    /**
     * Generate an image based on a text prompt using OpenAI DALL-E or GPT Image.
     *
     * @param string $prompt The text prompt describing the image.
     * @param array $api_params API connection parameters ('api_key', 'base_url', 'api_version').
     * @param array $options Generation options merged with defaults ('model', 'n', 'size', 'quality', 'response_format', 'style', 'user', etc.).
     * @return array|WP_Error Array containing 'images' => [['url'=>..., 'b64_json'=>..., 'revised_prompt'=>...], ...], 'usage' => array|null or WP_Error on failure.
     */
    public function generate_image(string $prompt, array $api_params, array $options = []): array|WP_Error {
        $api_key = $api_params['api_key'] ?? null;
        if (empty($api_key)) return new WP_Error('openai_image_missing_key', __('OpenAI API Key is required for image generation.', 'gpt3-ai-content-generator'));
        if (empty($prompt)) return new WP_Error('openai_image_missing_prompt', __('Prompt cannot be empty for image generation.', 'gpt3-ai-content-generator'));

        // Ensure component classes are loaded (they should be by constructor, but defensive check)
        if (!class_exists(OpenAIImageUrlBuilder::class) || !class_exists(OpenAIPayloadFormatter::class) || !class_exists(OpenAIImageResponseParser::class)) {
            return new WP_Error('openai_image_dependency_missing', __('OpenAI image generation components are missing.', 'gpt3-ai-content-generator'), ['status' => 500]);
        }

        // --- Build URL using image-specific builder ---
        $url_builder_params = [
            'base_url' => $api_params['base_url'] ?? 'https://api.openai.com',
            'api_version' => $api_params['api_version'] ?? 'v1',
        ];
        $url = OpenAIImageUrlBuilder::build('images/generations', $url_builder_params);
        if (is_wp_error($url)) return $url;

        // --- Build Payload using image-specific formatter ---
        $payload = OpenAIPayloadFormatter::format($prompt, $options);
        // --- End Build Payload ---

        $headers_array = $this->get_api_headers($api_key, 'generate');
        $request_options = $this->get_request_options('generate');
        $request_body_json = wp_json_encode($payload);
        $request_args = array_merge($request_options, [
            'headers' => $headers_array,
            'body' => $request_body_json,
            'data_format' => 'body',
        ]);

        $response = wp_remote_post($url, $request_args);

        if (is_wp_error($response)) {
            return new WP_Error('openai_image_http_error', __('HTTP error during image generation.', 'gpt3-ai-content-generator'));
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        $decoded_response = $this->decode_json($body, 'OpenAI Image Generation'); // Uses base class helper

        if ($status_code !== 200 || is_wp_error($decoded_response)) {
            $error_msg = is_wp_error($decoded_response)
                        ? $decoded_response->get_error_message()
                        : OpenAIImageResponseParser::parse_error($body, $status_code); // Use image-specific error parser

            /* translators: %1$d: HTTP status code, %2$s: Error message from the API. */
            return new WP_Error('openai_image_api_error', sprintf(__('OpenAI Image API Error (%1$d): %2$s', 'gpt3-ai-content-generator'), $status_code, $error_msg));
        }

        // --- Parse response using image-specific parser ---
        $parsed_data = OpenAIImageResponseParser::parse($decoded_response, $options['model'] ?? 'dall-e-3', $prompt);
        if (!isset($parsed_data['images']) || !is_array($parsed_data['images'])) { // Check if 'images' key exists and is an array

            return new WP_Error('openai_image_no_data_parsed', __('OpenAI API returned success but image data structure is invalid.', 'gpt3-ai-content-generator'));
        }
        // --- End Parse ---

        return $parsed_data; // Returns ['images' => [...], 'usage' => ...]
    }

    /**
     * Get the supported image sizes for OpenAI DALL-E (all possible).
     */
    public function get_supported_sizes(): array {
        return ['1024x1024', '1792x1024', '1024x1792', '1536x1024', '1024x1536', '512x512', '256x256'];
    }

    /**
     * Get API headers required for OpenAI Image requests.
     */
    public function get_api_headers(string $api_key, string $operation): array {
         $headers = parent::get_api_headers($api_key, $operation); // Gets Content-Type
         $headers['Authorization'] = 'Bearer ' . $api_key;
         return $headers;
    }
}