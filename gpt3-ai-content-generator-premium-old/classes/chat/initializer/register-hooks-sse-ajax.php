<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/chat/initializer/register-hooks-sse-ajax.php
// Status: NEW FILE

namespace WPAICG\Chat\Initializer;

use WPAICG\Core\Stream\Handler\SSEHandler;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for registering SSE-specific AJAX hooks for the Chat module.
 * Called by WPAICG\Chat\Initializer::register_hooks().
 *
 * @param SSEHandler|null $sse_handler
 * @return void
 */
function register_hooks_sse_ajax_logic(?SSEHandler $sse_handler): void {
    if ($sse_handler) {
        add_action('wp_ajax_aipkit_cache_sse_message', [$sse_handler, 'ajax_cache_sse_message']);
        add_action('wp_ajax_nopriv_aipkit_cache_sse_message', [$sse_handler, 'ajax_cache_sse_message']);
        add_action('wp_ajax_aipkit_frontend_chat_stream', [$sse_handler, 'ajax_frontend_chat_stream']);
        add_action('wp_ajax_nopriv_aipkit_frontend_chat_stream', [$sse_handler, 'ajax_frontend_chat_stream']);
    }
}