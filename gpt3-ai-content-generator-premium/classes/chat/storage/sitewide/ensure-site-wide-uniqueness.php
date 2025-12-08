<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/chat/storage/sitewide/ensure-site-wide-uniqueness.php
// Status: NEW FILE

namespace WPAICG\Chat\Storage\SiteWide;

use WPAICG\Chat\Storage\SiteWideBotManager; // To access methods

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the ensure_site_wide_uniqueness method of SiteWideBotManager.
 *
 * @param int $target_bot_id The ID of the bot being potentially enabled.
 * @param bool $is_enabling Whether the target bot is being enabled for site-wide use.
 * @param SiteWideBotManager $site_wide_manager_instance Instance of SiteWideBotManager to call its methods.
 * @return bool True if the cache should be cleared after the operation.
 */
function ensure_site_wide_uniqueness_logic(int $target_bot_id, bool $is_enabling, SiteWideBotManager $site_wide_manager_instance): bool {
    $clear_cache = false;
    // Force cache refresh to get the absolute current site-wide bot
    // Call the public method on the instance, which delegates to the logic function (this one)
    $current_site_wide_id = $site_wide_manager_instance->get_site_wide_bot_id(true);

    if ($is_enabling) {
        if ($current_site_wide_id && $current_site_wide_id !== $target_bot_id) {
            // Another bot is currently site-wide, disable it first.
            update_post_meta($current_site_wide_id, '_aipkit_site_wide_enabled', '0');
            $clear_cache = true; // Mark cache for clearing
        } elseif ($current_site_wide_id !== $target_bot_id) {
             // No other bot was site-wide, but we are enabling this one, so cache needs update.
             $clear_cache = true;
        }
    } else {
         // If we are *disabling* site-wide for the current bot, check if it *was* the site-wide bot
         if ($current_site_wide_id === $target_bot_id) {
             $clear_cache = true; // Mark cache for clearing as this bot is no longer site-wide
         }
    }
    return $clear_cache;
}