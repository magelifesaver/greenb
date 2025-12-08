<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/images/providers/openai/OpenAIImageUrlBuilder.php

namespace WPAICG\Images\Providers\OpenAI;

use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Handles building API URLs specific to the OpenAI Image Generation provider.
 */
class OpenAIImageUrlBuilder {

    const IMAGES_GENERATIONS_ENDPOINT = '/images/generations';

    /**
     * Build the full API endpoint URL for OpenAI image generation.
     *
     * @param string $operation Expected to be 'images/generations'.
     * @param array  $params Required parameters (base_url, api_version).
     * @return string|WP_Error The full URL or WP_Error.
     */
    public static function build(string $operation = 'images/generations', array $params = []): string|WP_Error {
        $base_url = !empty($params['base_url']) ? rtrim($params['base_url'], '/') : 'https://api.openai.com';
        $api_version = !empty($params['api_version']) ? $params['api_version'] : 'v1';

        if (empty($base_url)) {
            return new WP_Error("missing_base_url_openai_image", __('OpenAI Base URL is required for images.', 'gpt3-ai-content-generator'));
        }
        if (empty($api_version)) {
            return new WP_Error("missing_api_version_openai_image", __('OpenAI API Version is required for images.', 'gpt3-ai-content-generator'));
        }

        if ($operation !== 'images/generations') {
            // translators: %s is the operation name
            return new WP_Error('unsupported_operation_openai_image', sprintf(__('Operation "%s" not supported for OpenAI Image URL Builder.', 'gpt3-ai-content-generator'), esc_html($operation)));
        }

        // Check if base_url already includes the version path segment
        $version_segment = '/' . trim($api_version, '/');
        if (strpos($base_url, $version_segment) !== false) {
            return $base_url . self::IMAGES_GENERATIONS_ENDPOINT;
        } else {
            return $base_url . $version_segment . self::IMAGES_GENERATIONS_ENDPOINT;
        }
    }
}