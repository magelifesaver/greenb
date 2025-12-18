<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/ajax/migration/analysis/get-old-integration-data.php
// Status: MODIFIED
// I have updated the logic to correctly handle both raw JSON strings and serialized PHP arrays from the database for Google Sheets and RSS data.

namespace WPAICG\Admin\Ajax\Migration\Analysis;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Gets details about old integration data (Google Sheets, RSS) from the options table.
 *
 * @return array ['count' => int, 'integrations' => array, 'summary' => string, 'details' => array]
 */
function get_old_integration_data_logic(): array
{
    $integrations = [];
    $count = 0;
    $details = [];

    // Google Sheets URL
    $g_sheets_url = get_option('wpaicg_google_sheets_url', '');
    if (!empty($g_sheets_url)) {
        $integrations['google_sheets_url'] = [
            'label' => __('Google Sheets URL', 'gpt3-ai-content-generator'),
            'value' => $g_sheets_url,
            'is_json' => false
        ];
        $count++;
        $details[] = 'Google Sheets URL found.';
    }

    // Google Sheets Credentials
    $g_sheets_creds = get_option('wpaicg_google_credentials_json', []);
    if (!empty($g_sheets_creds)) {
        $creds_value = '';
        if (is_array($g_sheets_creds)) {
            // If it's already an array, encode it for display.
            $creds_value = wp_json_encode($g_sheets_creds, JSON_PRETTY_PRINT);
        } elseif (is_string($g_sheets_creds)) {
            // If it's a string, try to decode it to re-encode it prettily.
            // This handles cases where a raw JSON string was saved.
            $decoded = json_decode($g_sheets_creds, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $creds_value = wp_json_encode($decoded, JSON_PRETTY_PRINT);
            } else {
                $creds_value = $g_sheets_creds; // Fallback to the raw string if it's not valid JSON
            }
        }

        if (!empty($creds_value)) {
            $integrations['google_sheets_credentials'] = [
               'label' => __('Google Sheets Credentials', 'gpt3-ai-content-generator'),
               'value' => $creds_value,
               'is_json' => true
            ];
            $count++;
            $details[] = 'Google Sheets Credentials found.';
        }
    }

    // RSS Feeds
    $rss_feeds = get_option('wpaicg_rss_feeds', []);
    if (is_string($rss_feeds)) {
        // Attempt to unserialize if it's a string (handles cases where DB clients might not auto-unserialize)
        $rss_feeds = maybe_unserialize($rss_feeds);
    }
    if (!empty($rss_feeds) && is_array($rss_feeds)) {
        $rss_urls = array_column($rss_feeds, 'url');
        $filtered_urls = array_filter($rss_urls);

        if (!empty($filtered_urls)) {
            $integrations['rss_feeds'] = [
                'label' => __('RSS Feed URLs', 'gpt3-ai-content-generator'),
                'value' => implode("\n", $filtered_urls),
                'is_json' => false
            ];
            $count++;
            $details[] = count($filtered_urls) . ' RSS Feed(s) found.';
        }
    }

    return [
        'count' => $count,
        'integrations' => $integrations,
        /* translators: %d is the number of old integration settings found */
        'summary' => sprintf(_n('%d old integration setting found.', '%d old integration settings found.', $count, 'gpt3-ai-content-generator'), $count),
        'details' => $details
    ];
}
