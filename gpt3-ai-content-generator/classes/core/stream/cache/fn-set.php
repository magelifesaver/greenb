<?php
// File: classes/core/stream/cache/fn-set.php
// Status: MODIFIED

namespace WPAICG\Core\Stream\Cache;

use WP_Error;
use DateTime;
use DateTimeZone;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Stores a message in the cache.
 * MODIFIED: Always write to the database as a reliable fallback, then attempt to write to object cache for performance.
 *
 * @param \WPAICG\Core\Stream\Cache\AIPKit_SSE_Message_Cache $cacheInstance The instance of the cache class.
 * @param string $message The user message content.
 * @return string|WP_Error The cache key on success, WP_Error on failure.
 */
function set_logic(\WPAICG\Core\Stream\Cache\AIPKit_SSE_Message_Cache $cacheInstance, string $message): string|\WP_Error {
    if (empty($message)) {
        return new WP_Error('sse_cache_empty_message', __('Cannot cache an empty message.', 'gpt3-ai-content-generator'));
    }

    $key = $cacheInstance->generate_key_public_wrapper();

    // Always write to the database as a reliable fallback.
    global $wpdb;
    $expires_at = new DateTime('now', new DateTimeZone('UTC'));
    $expires_at->modify('+' . $cacheInstance::EXPIRY_SECONDS . ' seconds');
    $expires_at_str = $expires_at->format('Y-m-d H:i:s');

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Reason: Direct insertion into a custom table is necessary. Cache is set below.
    $inserted = $wpdb->insert(
        $cacheInstance->get_db_table_name(),
        [
            'cache_key' => $key,
            'message_content' => $message,
            'expires_at' => $expires_at_str,
        ],
        ['%s', '%s', '%s']
    );

    if ($inserted === false) {
        return new WP_Error('sse_cache_db_insert_failed', __('Failed to store message in database cache.', 'gpt3-ai-content-generator'));
    }

    // Also try to set in the object cache for performance, but don't fail if it doesn't work.
    if ($cacheInstance->is_using_object_cache()) {
        $set_obj_cache = wp_cache_set($key, $message, $cacheInstance::CACHE_GROUP, $cacheInstance::EXPIRY_SECONDS);
    }

    return $key; // Return the key since the DB write succeeded.
}