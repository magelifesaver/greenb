<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/chat/storage/logger/generate-parent-id.php
// Status: NEW FILE

namespace WPAICG\Chat\Storage\LoggerMethods;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Generates a unique ID for message parents.
 *
 * @return string
 */
function generate_parent_id_logic(): string {
    return str_replace('.', '', uniqid('aipkit-parent-', true));
}