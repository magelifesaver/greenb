<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/core/class-aipkit-payload-sanitizer.php
// Status: NEW FILE

namespace WPAICG\Core;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * AIPKit_Payload_Sanitizer
 *
 * Utility class for sanitizing API request payloads before logging or storage.
 * Specifically redacts base64 image data.
 */
class AIPKit_Payload_Sanitizer {

    /**
     * Sanitizes image_inputs within a payload array for logging purposes.
     * Replaces base64 data with a redaction notice and size.
     *
     * @param array $payload The payload to sanitize.
     * @return array The sanitized payload.
     */
    public static function sanitize_for_logging(array $payload): array {
        $sanitized_payload = $payload;

        // Sanitize direct image_inputs key (often in $final_ai_params or similar)
        if (isset($sanitized_payload['image_inputs']) && is_array($sanitized_payload['image_inputs'])) {
            foreach ($sanitized_payload['image_inputs'] as &$image_input_ref) { // Use reference
                if (isset($image_input_ref['base64'])) {
                    $len = strlen($image_input_ref['base64']);
                    $mime = $image_input_ref['type'] ?? 'unknown/unknown';
                    $image_input_ref['base64'] = "[REDACTED base64_size={$len} mime={$mime}]";
                }
            }
            unset($image_input_ref); // Unset reference
        }
        
        // Sanitize OpenAI/Azure formatted message content (input[...]['content'][...]['image_url'])
        // Note: 'input' is typically the key in OpenAI 'Responses' API payload.
        // Chat Completions API used by Azure/OpenRouter uses 'messages'.
        $message_list_key = isset($sanitized_payload['input']) ? 'input' : (isset($sanitized_payload['messages']) ? 'messages' : null);

        if ($message_list_key && isset($sanitized_payload[$message_list_key]) && is_array($sanitized_payload[$message_list_key])) {
            foreach ($sanitized_payload[$message_list_key] as &$message_ref) { // Use reference
                if (isset($message_ref['content']) && is_array($message_ref['content'])) {
                    foreach ($message_ref['content'] as &$part_ref) { // Use reference
                        if (isset($part_ref['type']) && ($part_ref['type'] === 'input_image' || $part_ref['type'] === 'image_url') && isset($part_ref['image_url'])) { // OpenAI 'Responses' API or Chat Completions image_url part
                            $image_url_to_check = is_array($part_ref['image_url']) && isset($part_ref['image_url']['url'])
                                                  ? $part_ref['image_url']['url']
                                                  : (is_string($part_ref['image_url']) ? $part_ref['image_url'] : null);

                            if ($image_url_to_check && strpos($image_url_to_check, 'data:') === 0 && strpos($image_url_to_check, ';base64,') !== false) {
                                $base64_part_actual = substr($image_url_to_check, strpos($image_url_to_check, ';base64,') + 8);
                                $len = strlen($base64_part_actual);
                                $mime_part = substr($image_url_to_check, 5, strpos($image_url_to_check, ';base64,') - 5);
                                
                                if (is_array($part_ref['image_url'])) {
                                    $part_ref['image_url']['url'] = "data:{$mime_part};base64,[REDACTED base64_size={$len}]";
                                } else {
                                    $part_ref['image_url'] = "data:{$mime_part};base64,[REDACTED base64_size={$len}]";
                                }
                            }
                        }
                    }
                    unset($part_ref); 
                }
            }
            unset($message_ref); 
        }
        
        // Sanitize Google Gemini formatted message content (contents[...]['parts'][...]['inlineData'])
        if (isset($sanitized_payload['contents']) && is_array($sanitized_payload['contents'])) {
            foreach ($sanitized_payload['contents'] as &$message_ref) { // Use reference
                if (isset($message_ref['parts']) && is_array($message_ref['parts'])) {
                    foreach ($message_ref['parts'] as &$part_ref) { // Use reference
                        if (isset($part_ref['inlineData']['data'])) {
                            $len = strlen($part_ref['inlineData']['data']);
                            $mime = $part_ref['inlineData']['mimeType'] ?? 'unknown/unknown';
                            $part_ref['inlineData']['data'] = "[REDACTED base64_size={$len} mime={$mime}]";
                        }
                    }
                    unset($part_ref); 
                }
            }
            unset($message_ref); 
        }

        return $sanitized_payload;
    }
}