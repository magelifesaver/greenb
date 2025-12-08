<?php

declare(strict_types=1);

namespace Tmeister\JWTAuthPro\Services;

use WP_Error;

class SecurityService
{
    private const NONCE_ACTION = 'jwt_auth_nonce';

    public function validateAdminAccess(): WP_Error|bool
    {
        if (!current_user_can('manage_options')) {
            return new WP_Error(
                'jwt_auth_insufficient_permissions',
                'You do not have sufficient permissions to access this resource.',
                ['status' => 403]
            );
        }

        return true;
    }

    public function validateNonce(string $nonce): WP_Error|bool
    {
        if (!wp_verify_nonce($nonce, self::NONCE_ACTION)) {
            return new WP_Error(
                'jwt_auth_invalid_nonce',
                'Invalid security token.',
                ['status' => 403]
            );
        }

        return true;
    }

    public function addSecurityHeaders(): void
    {
        // If we're in test mode, don't do anything with headers
        if (defined('DOING_TESTS') || apply_filters('jwt_auth_test_mode', false)) {
            return;
        }

        $headers = [
            // Prevents MIME-type sniffing
            'X-Content-Type-Options'    => 'nosniff',

            // Prevents clickjacking attacks
            'X-Frame-Options'           => 'DENY',

            // Enables XSS filtering
            'X-XSS-Protection'          => '1; mode=block',

            // Controls how much referrer information should be in requests
            'Referrer-Policy'           => 'strict-origin-when-cross-origin',

            // Forces HTTPS connections
            'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
        ];

        $headers = apply_filters('jwt_auth_security_headers', $headers);

        foreach ($headers as $header => $value) {
            header("$header: $value");
        }
    }
}
