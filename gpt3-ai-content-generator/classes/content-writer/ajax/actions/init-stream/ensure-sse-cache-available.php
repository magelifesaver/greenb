<?php

namespace WPAICG\ContentWriter\Ajax\Actions\InitStream;

use WPAICG\Core\Stream\Cache\AIPKit_SSE_Message_Cache;
use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

/**
* Ensures the SSE Cache class is available, loading it if necessary.
*
* @return true|WP_Error True on success, WP_Error on failure.
*/
function ensure_sse_cache_available_logic(): bool|WP_Error
{
    if (!class_exists(AIPKit_SSE_Message_Cache::class)) {
        $sse_cache_path = WPAICG_PLUGIN_DIR . 'classes/core/stream/cache/class-sse-message-cache.php';
        if (file_exists($sse_cache_path)) {
            require_once $sse_cache_path;
        } else {
            return new WP_Error('dependency_missing', __('SSE Caching component is missing.', 'gpt3-ai-content-generator'), ['status' => 500]);
        }
    }
    return true;
}
