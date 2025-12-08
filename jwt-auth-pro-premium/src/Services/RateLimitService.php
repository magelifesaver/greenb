<?php

declare(strict_types=1);

namespace Tmeister\JWTAuthPro\Services;

use Tmeister\JWTAuthPro\Database\RateLimitRepository;
use Tmeister\JWTAuthPro\Traits\HasRequestIP;
use WP_Error;

class RateLimitService
{
    use HasRequestIP;

    public function __construct(
        private readonly RateLimitRepository $repository,
        private readonly SettingsService $settingsService,
    ) {}

    /**
     * Check if the request is rate limited
     */
    public function isRateLimited(string $ipAddress): bool|WP_Error
    {
        // Allow short-circuiting the rate limit check, check the settings first and then honor the filter
        $rateLimitEnabled = apply_filters('jwt_auth_rate_limit_enabled', $this->settingsService->getSetting('rate_limit_settings', 'enabled'));
        if (!$rateLimitEnabled) {
            return true;
        }

        // Get the rate limit config
        $maxRequests = $this->getRateLimitRequests();
        $windowMinutes = $this->getRateLimitWindowMinutes();

        // Check if we have an existing rate limit record
        $rateLimit = $this->repository->findByIpAddress($ipAddress);

        if ($rateLimit === null) {
            // Create new rate limit record with UTC timestamps
            $now = gmdate('Y-m-d H:i:s');
            $windowEnd = gmdate('Y-m-d H:i:s', strtotime($now) + ($windowMinutes * 60));
            $this->repository->create(['ip_address' => $ipAddress, 'window_start' => $now, 'window_end' => $windowEnd]);
            $this->addRateLimitHeaders($maxRequests, $maxRequests - 1, strtotime($windowEnd));

            return true;
        }

        if ($rateLimit->counter >= $maxRequests) {
            $retryAfter = strtotime($rateLimit->window_end) - time();

            // Create error response with headers
            $error = new WP_Error(
                'jwt_auth_rate_limited',
                'Rate limit exceeded',
                [
                    'status' => 429,
                    'retry_after' => $retryAfter,
                ]
            );

            $this->addRateLimitHeaders($maxRequests, 0, strtotime($rateLimit->window_end), $retryAfter);
            return $error;
        }

        // Increment the counter
        $this->repository->incrementCounter($ipAddress);

        // Add rate limit headers for successful requests
        $remaining = max(0, $maxRequests - ($rateLimit->counter + 1));
        $this->addRateLimitHeaders($maxRequests, $remaining, strtotime($rateLimit->window_end));

        return true;
    }

    /**
     * Add rate limit headers to the response
     */
    public function addRateLimitHeaders(?int $limit = null, ?int $remaining = null, ?int $reset = null, ?int $retryAfter = null): void
    {
        // Allow short-circuiting the rate limit check, check the settings first and then honor the filter
        $rateLimitEnabled = apply_filters('jwt_auth_rate_limit_enabled', $this->settingsService->getSetting('rate_limit_settings', 'enabled'));
        $sendHeaders = apply_filters('jwt_auth_rate_limit_headers_enabled', true);

        if (!$rateLimitEnabled || !$sendHeaders) {
            return;
        }

        if (!function_exists('header')) {
            return;
        }

        // If we dont have a limit, remaining or reset value, we need to get the data from the RateLimitRepository
        if ($limit === null && $remaining === null && $reset === null) {
            // Get the data from the RateLimitRepository
            $rateLimit = $this->repository->findByIpAddress($this->getRequestIP());
            // Now calculate the limit, remaining and reset values based on the $rateLimit data
            $limit = $this->getRateLimitRequests();
            $remaining = isset($rateLimit->counter) ? $limit - $rateLimit->counter : $limit;
            $reset = isset($rateLimit->window_end) ? strtotime($rateLimit->window_end) : strtotime('+' . $this->getRateLimitWindowMinutes() . ' minutes');
            $retryAfter = isset($rateLimit->window_end) ? strtotime($rateLimit->window_end) - time() : null;
        }

        header('X-RateLimit-Limit: ' . $limit);
        header('X-RateLimit-Remaining: ' . $remaining);
        header('X-RateLimit-Reset: ' . $reset);

        if ($retryAfter !== null) {
            header('Retry-After: ' . $retryAfter);
        }
    }

    private function getRateLimitRequests(): int
    {
        return (int) apply_filters(
            'jwt_auth_rate_limit_max_requests',
            $this->settingsService->getSetting('rate_limit_settings', 'max_requests')
        );
    }

    private function getRateLimitWindowMinutes(): int
    {
        return (int) apply_filters(
            'jwt_auth_rate_limit_window_minutes',
            $this->settingsService->getSetting('rate_limit_settings', 'window_minutes')
        );
    }
}
