<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/images/providers/openai/OpenAIPayloadFormatter.php
// Status: MODIFIED

namespace WPAICG\Images\Providers\OpenAI;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Handles formatting request payloads for the OpenAI Image Generation API.
 */
class OpenAIPayloadFormatter
{
    /**
     * Formats the payload for OpenAI image generation.
     *
     * @param string $prompt The text prompt.
     * @param array  $options Generation options including 'model', 'n', 'size', 'quality', 'style', 'response_format', 'user', etc.
     * @return array The formatted request body data.
     */
    public static function format(string $prompt, array $options): array
    {
        $model = $options['model'] ?? 'dall-e-2'; // Fallback default if not provided in options

        $payload = [
            'model' => $model,
            'prompt' => wp_strip_all_tags($prompt), // Ensure prompt is plain text
        ];

        // Apply options based on the selected model
        if ($model === 'gpt-image-1') {
            $payload['n'] = 1; // gpt-image-1 only supports n=1
            if (isset($options['size'])) {
                $payload['size'] = $options['size'];
            }

            // gpt-image-1 uses output_format (png, jpeg, webp), not response_format
            if (isset($options['output_format'])) {
                $payload['output_format'] = $options['output_format'];
            } else {
                $payload['output_format'] = 'png';
            } // Default to png if not specified for gpt-image-1

            // Additional gpt-image-1 parameters if present in options
            if (isset($options['background'])) {
                $payload['background'] = $options['background'];
            }
            if (isset($options['moderation'])) {
                $payload['moderation'] = $options['moderation'];
            }
            if (isset($options['output_compression'])) {
                $payload['output_compression'] = $options['output_compression'];
            }

        } elseif ($model === 'dall-e-3') {
            $payload['n'] = 1; // DALL-E 3 only supports n=1
            if (isset($options['quality'])) {
                $payload['quality'] = $options['quality'];
            }
            if (isset($options['size'])) {
                $payload['size'] = $options['size'];
            }
            if (isset($options['style'])) {
                $payload['style'] = $options['style'];
            }
            if (isset($options['response_format'])) {
                $payload['response_format'] = $options['response_format'];
            }

        } else { // Defaults for dall-e-2 (or other future models that might use these params)
            $n = isset($options['n']) ? absint($options['n']) : 1;
            $payload['n'] = max(1, min($n, 10)); // DALL-E 2 supports n=1 to 10
            if (isset($options['size'])) {
                $payload['size'] = $options['size'];
            }
            if (isset($options['response_format'])) {
                $payload['response_format'] = $options['response_format'];
            }
        }

        // Common parameter for all models
        if (!empty($options['user'])) {
            $payload['user'] = sanitize_text_field($options['user']);
        }

        return $payload;
    }
}
