<?php

declare(strict_types=1);

namespace Tmeister\JWTAuthPro\Database;

class RateLimitRepository
{
    private const TRANSIENT_PREFIX = 'jwt_auth_rate_limit_';

    public function __construct() {}

    /**
     * Find rate limit record by IP address
     */
    public function findByIpAddress(string $ipAddress): ?object
    {
        $hashedIpAddress = hash('sha256', $ipAddress);
        $data = get_transient(self::TRANSIENT_PREFIX . $hashedIpAddress);

        if ($data === false) {
            return null;
        }

        return (object) $data;
    }

    /**
     * Create a new rate limit record
     */
    public function create(array $data): int|false
    {
        $hashedIpAddress = hash('sha256', $data['ip_address']);

        $rateLimit = [
            'ip_address' => $data['ip_address'],
            'counter' => 1,
            'window_start' => $data['window_start'],
            'window_end' => $data['window_end'],
        ];

        // Calculate expiration in seconds from window_end
        $expiration = strtotime($data['window_end']) - time();

        $result = set_transient(
            self::TRANSIENT_PREFIX . $hashedIpAddress,
            $rateLimit,
            $expiration
        );

        return $result ? 1 : false;
    }

    /**
     * Increment the counter for a rate limit record
     */
    public function incrementCounter(string $ipAddress): bool
    {
        $hashedIpAddress = hash('sha256', $ipAddress);

        $data = get_transient(self::TRANSIENT_PREFIX . $hashedIpAddress);

        if ($data === false) {
            return false;
        }

        $data['counter']++;

        // Calculate remaining expiration time
        $expiration = strtotime($data['window_end']) - time();

        $result = set_transient(
            self::TRANSIENT_PREFIX . $hashedIpAddress,
            $data,
            $expiration
        );

        return $result;
    }

    /**
     * Clean up is not needed with transients as they auto-expire
     */
    public function pruneExpired(): bool
    {
        return true;
    }
}
