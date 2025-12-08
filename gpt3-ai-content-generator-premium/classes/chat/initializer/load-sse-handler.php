<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/chat/initializer/load-sse-handler.php
// Status: NEW FILE

namespace WPAICG\Chat\Initializer;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for loading the Core SSE Handler dependency.
 * Called by WPAICG\Chat\Initializer::load_dependencies().
 */
function load_sse_handler_logic(): void {
    $sse_handler_path = WPAICG_PLUGIN_DIR . 'classes/core/stream/handler/class-sse-handler.php';
    if (file_exists($sse_handler_path) && !class_exists(\WPAICG\Core\Stream\Handler\SSEHandler::class)) {
        require_once $sse_handler_path;
    }
}