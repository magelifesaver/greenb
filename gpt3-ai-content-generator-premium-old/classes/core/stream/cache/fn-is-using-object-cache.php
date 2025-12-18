<?php
// File: classes/core/stream/cache/fn-is-using-object-cache.php

namespace WPAICG\Core\Stream\Cache;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Check if the external object cache is being used.
 *
 * @param \WPAICG\Core\Stream\Cache\AIPKit_SSE_Message_Cache $cacheInstance The instance of the cache class.
 * @return bool True if using object cache, false otherwise.
 */
function is_using_object_cache_logic(\WPAICG\Core\Stream\Cache\AIPKit_SSE_Message_Cache $cacheInstance): bool {
    // The property use_object_cache is private, need a getter or make it public
    return $cacheInstance->get_use_object_cache_status();
}