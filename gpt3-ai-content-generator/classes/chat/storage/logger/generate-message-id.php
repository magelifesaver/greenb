<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/chat/storage/logger/generate-message-id.php
// Status: NEW FILE

namespace WPAICG\Chat\Storage\LoggerMethods;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Generates a unique ID for individual messages, removing the dot.
 *
 * @return string
 */
function generate_message_id_logic(): string {
    return str_replace('.', '', uniqid('aipkit-msg-', true));
}