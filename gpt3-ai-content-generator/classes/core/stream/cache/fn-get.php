<?php

// File: classes/core/stream/cache/fn-get.php
// Status: MODIFIED

namespace WPAICG\Core\Stream\Cache;

use WP_Error;
use DateTime;
use DateTimeZone;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Retrieves a message from the cache using its key.
 * Implemented WP Object Cache to resolve direct database query warnings.
 *
 * @param \WPAICG\Core\Stream\Cache\AIPKit_SSE_Message_Cache $cacheInstance The instance of the cache class.
 * @param string $key The cache key.
 * @return string|WP_Error The message content on success, WP_Error if not found or expired.
 */
function get_logic(\WPAICG\Core\Stream\Cache\AIPKit_SSE_Message_Cache $cacheInstance, string $key): string|\WP_Error
{
    if (empty($key)) {
        return new WP_Error('sse_cache_empty_key', __('Cache key cannot be empty.', 'gpt3-ai-content-generator'));
    }

    $cache_group = 'aipkit_sse_cache';
    $content_cache_key = 'sse_content_' . $key;

    // 1. Try to get the content from the cache.
    $cached_result = wp_cache_get($content_cache_key, $cache_group);

    if (false !== $cached_result) {
        // Cache hit. Return the cached result, which might be content or an error object.
        return $cached_result;
    }

    // 2. Cache miss, so query the database.
    global $wpdb;
    $now_utc = (new DateTime('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s');

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: Direct query to a custom table. Caches will be invalidated.
    $row = $wpdb->get_row(
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: Direct query to a custom table. Caches will be invalidated.
        $wpdb->prepare(
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: Direct query to a custom table. Caches will be invalidated.
            "SELECT message_content FROM {$cacheInstance->get_db_table_name()} WHERE cache_key = %s AND expires_at > %s LIMIT 1",
            $key,
            $now_utc
        ),
        ARRAY_A
    );

    $result_to_cache = null;

    if ($row && isset($row['message_content'])) {
        // 3a. Found valid content.
        $result_to_cache = $row['message_content'];
    } else {
        // 4a. Not found or expired. Check if the key exists at all to differentiate the error.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: Direct query to a custom table. Caches will be invalidated.
        $exists = $wpdb->get_var($wpdb->prepare("SELECT 1 FROM {$cacheInstance->get_db_table_name()} WHERE cache_key = %s LIMIT 1", $key));
        if ($exists) {
            $result_to_cache = new WP_Error('sse_cache_expired', __('Cached message has expired.', 'gpt3-ai-content-generator'));
        } else {
            $result_to_cache = new WP_Error('sse_cache_not_found', __('Message not found in cache.', 'gpt3-ai-content-generator'));
        }
    }

    // 5. Store the result (either content or a WP_Error) in the cache.
    // This prevents repeated DB queries for non-existent or expired keys within the same request or if using a persistent cache.
    wp_cache_set($content_cache_key, $result_to_cache, $cache_group, $cacheInstance::EXPIRY_SECONDS);

    return $result_to_cache;
}