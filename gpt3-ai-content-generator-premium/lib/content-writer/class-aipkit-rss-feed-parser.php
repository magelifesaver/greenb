<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/lib/content-writer/class-aipkit-rss-feed-parser.php
// Status: MODIFIED

namespace WPAICG\Lib\ContentWriter;

use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Parses RSS feeds to extract new items since the last check.
 */
class AIPKit_Rss_Feed_Parser
{
    /**
     * Gets the latest items from a list of RSS feeds that have been published
     * since the last checked timestamp.
     *
     * @param string $feeds_raw A string of RSS feed URLs, one per line.
     * @param string|null $last_checked_timestamp_gmt The GMT timestamp of the last check (in 'Y-m-d H:i:s' format).
     * @return array An array of new items, each being an array with 'title', 'link', 'timestamp', and 'description'. Returns empty array on error or if no new items.
     */
    public function get_latest_items(string $feeds_raw, ?string $last_checked_timestamp_gmt): array
    {
        if (empty($feeds_raw)) {
            return [];
        }

        if (!function_exists('fetch_feed')) {
            include_once(ABSPATH . WPINC . '/feed.php');
        }

        $feed_urls = array_filter(array_map('trim', explode("\n", $feeds_raw)));
        $new_items = [];
        $last_check_ts = $last_checked_timestamp_gmt ? strtotime($last_checked_timestamp_gmt . ' GMT') : 0;

        foreach ($feed_urls as $feed_url) {
            if (empty($feed_url) || !filter_var($feed_url, FILTER_VALIDATE_URL)) {
                continue;
            }

            $feed = fetch_feed($feed_url);

            if (is_wp_error($feed)) {
                continue;
            }

            $max_items = $feed->get_item_quantity(15);
            $rss_items = $feed->get_items(0, $max_items);

            if (empty($rss_items)) {
                continue;
            }

            foreach ($rss_items as $item) {
                $item_ts = $item->get_date('U');
                if ($item_ts > $last_check_ts) {
                    $description = $item->get_description();
                    $item_guid = $item->get_id(true);
                    if (empty($item_guid)) {
                        $item_guid = $item->get_permalink();
                    }
                    $new_items[] = [
                        'title'       => esc_html($item->get_title()),
                        'link'        => esc_url($item->get_permalink()),
                        'timestamp'   => $item_ts,
                        'description' => $description ? wp_strip_all_tags($description) : '',
                        'guid'        => $item_guid,
                    ];
                }
            }
        }

        usort($new_items, function ($a, $b) {
            return $a['timestamp'] <=> $b['timestamp'];
        });

        return $new_items;
    }
}
