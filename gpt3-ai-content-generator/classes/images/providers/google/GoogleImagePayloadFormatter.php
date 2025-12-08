<?php

namespace WPAICG\Images\Providers\Google;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Handles formatting request payloads for Google Image Generation models.
 */
class GoogleImagePayloadFormatter {

    /**
     * Formats the payload for Google Image Generation API.
     *
     * @param string $prompt The text prompt.
     * @param array  $options Generation options including 'model' (full ID), 'n', 'size', etc.
     * @return array The formatted request body data.
     */
    public static function format(string $prompt, array $options): array {
        $model_id = $options['model'] ?? '';
        $payload = [];

        $n = isset($options['n']) ? max(1, (int)$options['n']) : 1;

        // Gemini image-generation models use the text+image modality on generateContent
        if (strpos($model_id, 'gemini') !== false && strpos($model_id, 'image-generation') !== false) {
            $payload = [
                'contents' => [[
                    'parts' => [ ['text' => $prompt] ]
                ]],
                'generationConfig' => [
                    'responseModalities' => ['TEXT', 'IMAGE'],
                ],
            ];
        }
        // Imagen models use the :predict endpoint with instances/parameters
        elseif (strpos($model_id, 'imagen') !== false) {
            $parameters = [ 'sampleCount' => $n ];
            $payload = [
                'instances' => [ ['prompt' => $prompt] ],
                'parameters' => $parameters,
            ];
        }

        return $payload;
    }
}
