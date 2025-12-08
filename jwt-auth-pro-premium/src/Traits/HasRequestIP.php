<?php

declare(strict_types=1);

namespace Tmeister\JWTAuthPro\Traits;

trait HasRequestIP
{
    private function getRequestIP(): string
    {
        $ip = '';

        // Check if we're behind a proxy
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // HTTP_X_FORWARDED_FOR can contain a chain of comma-separated addresses
            $ip = sanitize_text_field($_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim(explode(',', $ip)[0]);
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = sanitize_text_field($_SERVER['REMOTE_ADDR']);
        }

        // Additional headers for common proxy setups
        $additionalHeaders = apply_filters('jwt_auth_ip_headers', [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_REAL_IP',       // Nginx proxy
            'HTTP_CLIENT_IP',       // Client IP
        ]);

        // Only check additional headers if we haven't found an IP yet
        if (empty($ip)) {
            foreach ($additionalHeaders as $header) {
                if (!empty($_SERVER[$header])) {
                    $ip = sanitize_text_field($_SERVER[$header]);
                    break;
                }
            }
        }

        // Validate the IP (both IPv4 and IPv6)
        if (!empty($ip) && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6)) {
            return $ip;
        }

        return apply_filters('jwt_auth_default_ip', '');
    }
}
