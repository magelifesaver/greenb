<?php
/**
 * Plugin Name: GPT Sales Summary & Overview Endpoint (v1.5)
 * Description: GPT-safe proxy for LokeyReports summary + combined overview under /gpt/v1/sales/
 * Version: 1.5
 * Author: Lokey Delivery DevOps
 */

if (!defined('ABSPATH')) exit;

add_action('rest_api_init', function () {

    // Shared helper: fetch LokeyReports endpoint
    $lokey_fetch = function($endpoint, $request) {
        $consumer_key    = 'ck_dd31fdb6262a021f2d74bdc487b22b7c81776bbf';
        $consumer_secret = 'cs_e5422dca649d60c50872d9aed1424315a1691622';
        $lokey_url       = home_url('/wp-json/lokeyreports/v1/sales/' . $endpoint);

        $query = [];
        foreach (['preset','from','to','group_by','statuses'] as $param) {
            if ($val = $request->get_param($param)) {
                $query[$param] = sanitize_text_field($val);
            }
        }

        // 🧠 N-day range support (Timezone-aware + full 00–23:59:59 local day)
        if ($days = absint($request->get_param('days'))) {

            // Get site timezone or fallback to LA
            $tz   = new DateTimeZone(get_option('timezone_string') ?: 'America/Los_Angeles');
            $utc  = new DateTimeZone('UTC');

            // Current local date in site timezone
            $now = new DateTime('now', $tz);

            // Start of local day (00:00:00)
            $start_local = (clone $now)->setTime(0, 0, 0);
            // End of local day (23:59:59)
            $end_local   = (clone $now)->setTime(23, 59, 59);

            // Convert to UTC for LokeyReports API
            $from_utc = (clone $start_local)->setTimezone($utc);
            $to_utc   = (clone $end_local)->setTimezone($utc);

            // Format with full timestamps
            $query['from'] = $from_utc->format('Y-m-d H:i:s');
            $query['to']   = $to_utc->format('Y-m-d H:i:s');

            // Clean up and bust cache
            unset($query['preset']);
            $query['_nocache'] = time();
        }

        // Inject credentials
        $query['consumer_key']    = $consumer_key;
        $query['consumer_secret'] = $consumer_secret;

        // Build and fetch
        $url      = add_query_arg($query, $lokey_url);
        $response = wp_remote_get($url, ['timeout' => 15]);

        if (is_wp_error($response)) {
            return new WP_REST_Response([
                'ok'    => false,
                'error' => $response->get_error_message(),
                'url'   => $url,
            ], 500);
        }

        $body   = json_decode(wp_remote_retrieve_body($response), true);
        $status = wp_remote_retrieve_response_code($response);

        return [
            'ok'     => $status < 400,
            'data'   => $body,
            'status' => $status,
            'url'    => $url,
        ];
    };

    // 🔹 Summary endpoint
    register_rest_route('gpt/v1', '/sales/summary', [
        'methods'  => 'GET',
        'callback' => function($request) use ($lokey_fetch) {
            return new WP_REST_Response($lokey_fetch('summary', $request));
        },
        'permission_callback' => '__return_true',
    ]);

    // 🔹 Overview endpoint: merges summary + top (with from/to support)
    register_rest_route('gpt/v1', '/sales/overview', [
        'methods'  => 'GET',
        'callback' => function($request) {

            $days  = absint($request->get_param('days')) ?: 30;
            $limit = absint($request->get_param('limit')) ?: 10;
            $debug = (bool)$request->get_param('debug');

            $base = home_url('/wp-json/gpt/v1');

            // from/to override support
            $from = sanitize_text_field($request->get_param('from'));
            $to   = sanitize_text_field($request->get_param('to'));

            if ($from && $to) {
                $summary_url = add_query_arg(['from' => $from, 'to' => $to], "{$base}/sales/summary");
                $top_url     = add_query_arg(['from' => $from, 'to' => $to, 'limit' => $limit], "{$base}/sales/top");
            } else {
                $summary_url = add_query_arg(['days' => $days], "{$base}/sales/summary");
                $top_url     = add_query_arg(['days' => $days, 'limit' => $limit], "{$base}/sales/top");
            }

            $summary_resp = wp_remote_get($summary_url, ['timeout' => 20]);
            $top_resp     = wp_remote_get($top_url, ['timeout' => 20]);

            $summary_data = json_decode(wp_remote_retrieve_body($summary_resp), true);
            $top_data     = json_decode(wp_remote_retrieve_body($top_resp), true);

            $summary_status = wp_remote_retrieve_response_code($summary_resp);
            $top_status     = wp_remote_retrieve_response_code($top_resp);

            if (is_wp_error($summary_resp) || is_wp_error($top_resp) || $summary_status >= 400) {
                return new WP_REST_Response([
                    'error'           => 'Failed to retrieve sales data',
                    'summary_status'  => $summary_status,
                    'top_status'      => $top_status,
                    '_debug_urls'     => compact('summary_url', 'top_url'),
                ], 500);
            }

            $result = [
                'overview_generated' => gmdate('Y-m-d H:i:s'),
                'summary'            => $summary_data['data'] ?? $summary_data,
                'top'                => $top_data['top'] ?? [],
                'summary_status'     => $summary_status,
                'top_status'         => $top_status,
            ];

            if ($debug) {
                $result['_debug'] = [
                    'summary_url' => $summary_url,
                    'top_url'     => $top_url,
                ];
            }

            return new WP_REST_Response($result, 200);
        },
        'permission_callback' => '__return_true',
    ]);
});
