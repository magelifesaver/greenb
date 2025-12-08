<?php

declare(strict_types=1);

namespace Tmeister\JWTAuthPro\Services;

class LicenseService
{
    private static $instance;

    private $jwt_fs;

    private function __construct()
    {
        // Include Freemius SDK

        if (!function_exists('fs_dynamic_init')) {
            return;
        }

        $this->jwt_fs = fs_dynamic_init([
            'id' => '17706',
            'slug' => 'jwt-auth-pro',
            'type' => 'plugin',
            'public_key' => 'pk_1c644825d6f2adf01066d1bf56c58',
            'is_premium' => true,
            'is_premium_only' => true,
            'has_addons' => false,
            'has_paid_plans' => true,
            'is_org_compliant' => false,
            'menu' => [
                'slug' => 'jwt-auth-pro',
                'contact' => false,
                'support' => false,
                'parent' => [
                    'slug' => 'options-general.php',
                ],
            ],
        ]);

        // Signal that SDK was initiated
        do_action('jwt_fs_loaded');
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function getFreemius(): mixed
    {
        return $this->jwt_fs;
    }

    /**
     * Check if running in testing mode
     *
     * This allows CI/CD and test environments to bypass license checks.
     * Two methods are supported:
     * 1. Test environment detection (DOING_TESTS constant)
     * 2. Environment variable validation for advanced testing
     *
     * @return bool True if valid testing mode, false otherwise
     */
    public static function isTestingMode(): bool
    {
        // Method 1: Check if we're in a test environment
        if (defined('DOING_TESTS') && DOING_TESTS) {
            return true;
        }

        // Method 2: Environment variable validation (for advanced testing)
        if (defined('WP_FS__jwt-auth-pro_SECRET_KEY') &&
            defined('WP_FS__jwt-auth-pro_EXPECTED_HASH')) {

            $expected_value = constant('WP_FS__jwt-auth-pro_EXPECTED_HASH');
            $generated_value = 'pk_' . hash('sha256', constant('WP_FS__jwt-auth-pro_SECRET_KEY'));

            return $generated_value === $expected_value;
        }

        return false;
    }

    // Prevent cloning and unserialization
    private function __clone(): void {}

    public function __wakeup(): void {}
}
