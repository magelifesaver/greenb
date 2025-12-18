<?php

namespace WPAICG\ContentWriter\Ajax\Actions\InitStream;

use WPAICG\Core\Stream\Cache\AIPKit_SSE_Message_Cache;
use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

/**
* Writes the payload to the SSE cache and returns the cache key.
*
* @param array $data_to_cache The structured payload to cache.
* @return string|WP_Error The cache key on success, or WP_Error on failure.
*/
function write_to_sse_cache_logic(array $data_to_cache): string|WP_Error
{
    $sse_message_cache = new AIPKit_SSE_Message_Cache();
    $cache_key_result = $sse_message_cache->set(wp_json_encode($data_to_cache));

    if (is_wp_error($cache_key_result)) {
        return $cache_key_result;
    }
    return $cache_key_result;
}
