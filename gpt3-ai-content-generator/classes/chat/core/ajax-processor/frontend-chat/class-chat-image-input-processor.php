<?php
// File: classes/chat/core/ajax-processor/frontend-chat/class-chat-image-input-processor.php
// Status: NEW FILE

namespace WPAICG\Chat\Core\AjaxProcessor\FrontendChat;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class ChatImageInputProcessor {

    /**
     * Processes the image_inputs JSON string from POST.
     * Currently supports only a single image.
     *
     * @param string|null $image_inputs_json The JSON string of image inputs.
     * @return array|null The processed image data for AIService, or null if no valid image.
     */
    public function process(?string $image_inputs_json): ?array {
        if (empty($image_inputs_json)) {
            return null;
        }

        $decoded_frontend_data = json_decode($image_inputs_json, true);
        if (!is_array($decoded_frontend_data)) {
            return null;
        }

        // Process only the first image if multiple are sent, as per instruction.
        $item = $decoded_frontend_data[0] ?? null;

        if (is_array($item) && isset($item['mime_type']) && isset($item['base64_data'])) {
            if (strpos($item['mime_type'], 'image/') === 0) {
                // AIService expects an array of image objects
                return [['type' => $item['mime_type'], 'base64' => $item['base64_data']]];
            }
        }
        return null;
    }
}