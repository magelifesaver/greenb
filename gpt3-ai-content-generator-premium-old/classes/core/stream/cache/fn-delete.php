<?php

// File: classes/core/stream/cache/fn-delete.php
// Status: MODIFIED

namespace WPAICG\Core\Stream\Cache;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Deletes a message from the cache.
 * MODIFIED: Now deletes from both the database and the object cache to ensure a clean state.
 *
 * @param \WPAICG\Core\Stream\Cache\AIPKit_SSE_Message_Cache $cacheInstance The instance of the cache class.
 * @param string $key The cache key.
 * @return bool True on success, false on failure or if key didn't exist.
 */
function delete_logic(\WPAICG\Core\Stream\Cache\AIPKit_SSE_Message_Cache $cacheInstance, string $key): bool
{
    if (empty($key)) {
        return false;
    }

    $deleted_from_db = false;
    $deleted_from_object_cache = false;

    // Always try to delete from the database.
    global $wpdb;
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Reason: Direct deletion from custom table is necessary. Cache is invalidated below.
    $deleted_db = $wpdb->delete(
        $cacheInstance->get_db_table_name(),
        ['cache_key' => $key],
        ['%s']
    );
    if ($deleted_db !== false) {
        $deleted_from_db = true; // DB deletion was successful or key didn't exist
    }

    // Also try to delete from the object cache if it's enabled.
    if ($cacheInstance->is_using_object_cache()) {
        $deleted_from_object_cache = wp_cache_delete($key, $cacheInstance::CACHE_GROUP);
    }

    // Return true if it was deleted from at least one source (or didn't exist in either).
    return $deleted_from_db || $deleted_from_object_cache;
}
