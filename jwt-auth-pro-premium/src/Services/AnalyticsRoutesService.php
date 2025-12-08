<?php

declare(strict_types=1);

namespace Tmeister\JWTAuthPro\Services;

use Tmeister\JWTAuthPro\Database\AnalyticsRepository;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

class AnalyticsRoutesService
{
    private const NAMESPACE = 'jwt-auth/v1';
    private const REST_BASE = 'analytics';
    private const CACHE_EXPIRATION = 8 * HOUR_IN_SECONDS;

    public function __construct(
        private readonly AnalyticsRepository $analyticsRepository,
    ) {}

    public function registerHooks(): void
    {
        add_action('rest_api_init', [$this, 'registerRoutes']);
    }

    public function registerRoutes(): void
    {
        register_rest_route(
            self::NAMESPACE,
            self::REST_BASE . '/summary',
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'getDashboardSummary'],
                'permission_callback' => [$this, 'checkAdminPermissions'],
            ]
        );

        register_rest_route(
            self::NAMESPACE,
            self::REST_BASE . '/all-time',
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'getAllTimeStats'],
                'permission_callback' => [$this, 'checkAdminPermissions'],
            ]
        );

        register_rest_route(
            self::NAMESPACE,
            self::REST_BASE . '/historical',
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'getHistoricalData'],
                'permission_callback' => [$this, 'checkAdminPermissions'],
            ]
        );
    }

    public function checkAdminPermissions(): bool
    {
        // Use SecurityService to check if the current user is an admin
        $securityService = new SecurityService();
        return $securityService->validateAdminAccess();
    }

    public function getDashboardSummary(WP_REST_Request $request): WP_REST_Response
    {
        $cache_key = $this->getCacheKey('dashboard_summary');
        $result = get_transient($cache_key);

        if ($result === false) {
            $result = $this->analyticsRepository->getDashboardSummary();

            if (!is_wp_error($result)) {
                set_transient($cache_key, $result, self::CACHE_EXPIRATION);
            }
        }

        if (is_wp_error($result)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => $result->get_error_message(),
            ], 500);
        }

        return new WP_REST_Response([
            'success' => true,
            'data'    => $result,
        ]);
    }

    public function getHistoricalData(WP_REST_Request $request): WP_REST_Response
    {
        $period = $request->get_param('period') ?? '7d';
        $cache_key = $this->getCacheKey('historical_data', ['period' => $period]);
        $result = get_transient($cache_key);

        if ($result === false) {
            $result = $this->analyticsRepository->getHistoricalData($period);

            if (!is_wp_error($result)) {
                set_transient($cache_key, $result, self::CACHE_EXPIRATION);
            }
        }

        if (is_wp_error($result)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => $result->get_error_message(),
            ], 500);
        }

        return new WP_REST_Response([
            'success' => true,
            'data'    => $result,
        ]);
    }

    public function getAllTimeStats(WP_REST_Request $request): WP_REST_Response
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $result = $this->analyticsRepository->getAllTimeStats();
        } else {
            $cache_key = $this->getCacheKey('all_time_stats');
            $result = get_transient($cache_key);
        }

        if ($result === false) {
            $result = $this->analyticsRepository->getAllTimeStats();

            if (!is_wp_error($result)) {
                set_transient($cache_key, $result, self::CACHE_EXPIRATION);
            }
        }

        if (is_wp_error($result)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => $result->get_error_message(),
            ], 500);
        }

        return new WP_REST_Response([
            'success' => true,
            'data'    => $result,
        ]);
    }

    public function deleteAnalyticsTransients(): void
    {
        $blog_id = get_current_blog_id();
        $transient_keys = [
            "jwt_auth_pro_dashboard_summary_{$blog_id}",
            "jwt_auth_pro_all_time_stats_{$blog_id}",
        ];

        // Delete historical data transients for different periods
        $periods = ['7d', '30d', '90d', '1y'];
        foreach ($periods as $period) {
            $transient_keys[] = $this->getCacheKey('historical_data', ['period' => $period]);
        }

        foreach ($transient_keys as $key) {
            delete_transient($key);
        }

        do_action('jwt_auth_analytics_cache_cleared');
    }

    private function getCacheKey(string $endpoint, array $params = []): string
    {
        $blog_id = get_current_blog_id();
        $key = "jwt_auth_pro_{$endpoint}_{$blog_id}";

        if (!empty($params)) {
            $key .= '_' . md5(serialize($params));
        }

        return $key;
    }
}
