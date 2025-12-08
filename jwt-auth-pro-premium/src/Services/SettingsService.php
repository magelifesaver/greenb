<?php

declare(strict_types=1);

namespace Tmeister\JWTAuthPro\Services;

use Tmeister\JWTAuthPro\Helpers\JWT;

class SettingsService
{
    public const OPTION_KEY = 'jwt_auth_pro_settings';
    public const SETTINGS_VERSION_KEY = 'jwt_auth_pro_settings_version';
    private const CURRENT_SETTINGS_VERSION = '0.1.0';

    private array $defaultSettings = [
        'data_management' => [
            'delete_on_deactivation' => false,
            'analytics_retention_days' => 90,
            'anonymize_ip' => false,
        ],
        'token_settings' => [
            'jwt_expiration' => DAY_IN_SECONDS * 7,
            'refresh_token_expiration' => DAY_IN_SECONDS * 30,
            'signing_algorithm' => 'HS256',
            'enable_cors' => false,
        ],
        'user_settings' => [
            'revoke_on_password_change' => true,
            'revoke_on_role_change' => true,
            'revoke_on_email_change' => true,
            'delete_on_user_delete' => true,
        ],
        'rate_limit_settings' => [
            'max_requests' => 60,
            'window_minutes' => 1,
            'enabled' => true,
        ],
    ];

    /**
     * Track new settings added in each version
     * Format: version => [group => [keys]]
     */
    private array $settingsVersions = [
        '0.1.0' => [
            'data_management' => [
                'delete_on_deactivation',
                'analytics_retention_days',
                'anonymize_ip',
            ],
            'token_settings' => [
                'jwt_expiration',
                'refresh_token_expiration',
                'signing_algorithm',
                'enable_cors',
            ],
            'user_settings' => [
                'revoke_on_password_change',
                'revoke_on_role_change',
                'revoke_on_email_change',
                'delete_on_user_delete',
            ],
            'rate_limit_settings' => [
                'max_requests',
                'window_minutes',
                'enabled',
            ],
        ],
    ];

    public function registerHooks(): void
    {
        add_action('rest_api_init', [$this, 'registerSettings']);
        add_action('init', [$this, 'initializeSettings']);
    }

    private function maybeUpdateSettings(): void
    {
        $currentVersion = get_option(self::SETTINGS_VERSION_KEY, '1.0');
        if (version_compare($currentVersion, self::CURRENT_SETTINGS_VERSION, '>=')) {
            return;
        }

        $settings = get_option(self::OPTION_KEY, []);

        // Sort versions to ensure we apply updates in order
        $versions = array_keys($this->settingsVersions);
        usort($versions, 'version_compare');

        foreach ($versions as $version) {
            if (version_compare($currentVersion, $version, '<')) {
                foreach ($this->settingsVersions[$version] as $group => $keys) {
                    // Skip if the group doesn't exist in default settings
                    if (!isset($this->defaultSettings[$group])) {
                        continue;
                    }

                    if (!isset($settings[$group])) {
                        $settings[$group] = [];
                    }

                    foreach ($keys as $key) {
                        // Skip if the key doesn't exist in default settings
                        if (!isset($this->defaultSettings[$group][$key])) {
                            continue;
                        }

                        if (!isset($settings[$group][$key])) {
                            $settings[$group][$key] = $this->defaultSettings[$group][$key];
                        }
                    }
                }
                // Update version after each successful version update
                update_option(self::SETTINGS_VERSION_KEY, $version);
            }
        }

        // Save the updated settings
        update_option(self::OPTION_KEY, $settings);

        // Ensure we're at the latest version
        if (version_compare(get_option(self::SETTINGS_VERSION_KEY), self::CURRENT_SETTINGS_VERSION, '<')) {
            update_option(self::SETTINGS_VERSION_KEY, self::CURRENT_SETTINGS_VERSION);
        }
    }

    public function initializeSettings(): void
    {
        $existingSettings = get_option(self::OPTION_KEY, []);
        if (empty($existingSettings)) {
            update_option(self::OPTION_KEY, $this->defaultSettings);
            update_option(self::SETTINGS_VERSION_KEY, self::CURRENT_SETTINGS_VERSION);
            return;
        }

        $this->maybeUpdateSettings();
    }

    public function registerSettings(): void
    {
        register_setting(
            'jwt_auth_pro',
            self::OPTION_KEY,
            [
                'type' => 'object',
                'default' => $this->defaultSettings,
                'show_in_rest' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'data_management' => [
                                'type' => 'object',
                                'properties' => [
                                    'delete_on_deactivation' => [
                                        'type' => 'boolean',
                                        'default' => true,
                                    ],
                                    'analytics_retention_days' => [
                                        'type' => 'integer',
                                        'default' => 90,
                                    ],
                                    'anonymize_ip' => [
                                        'type' => 'boolean',
                                        'default' => false,
                                    ],
                                ],
                            ],
                            'token_settings' => [
                                'type' => 'object',
                                'properties' => [
                                    'jwt_expiration' => [
                                        'type' => 'integer',
                                        'default' => DAY_IN_SECONDS * 7,
                                    ],
                                    'refresh_token_expiration' => [
                                        'type' => 'integer',
                                        'default' => DAY_IN_SECONDS * 30,
                                    ],
                                    'signing_algorithm' => [
                                        'type' => 'string',
                                        'enum' => JWT::getSupportedAlgorithms(),
                                        'default' => 'HS256',
                                    ],
                                    'enable_cors' => [
                                        'type' => 'boolean',
                                        'default' => false,
                                    ],
                                ],
                            ],
                            'user_settings' => [
                                'type' => 'object',
                                'properties' => [
                                    'revoke_on_password_change' => [
                                        'type' => 'boolean',
                                        'default' => true,
                                    ],
                                    'revoke_on_role_change' => [
                                        'type' => 'boolean',
                                        'default' => true,
                                    ],
                                    'revoke_on_email_change' => [
                                        'type' => 'boolean',
                                        'default' => true,
                                    ],
                                    'delete_on_user_delete' => [
                                        'type' => 'boolean',
                                        'default' => true,
                                    ],
                                ],
                            ],
                            'rate_limit_settings' => [
                                'type' => 'object',
                                'properties' => [
                                    'max_requests' => [
                                        'type' => 'integer',
                                        'minimum' => 1,
                                        'maximum' => 10000,
                                        'default' => 60,
                                    ],
                                    'window_minutes' => [
                                        'type' => 'integer',
                                        'minimum' => 1,
                                        'maximum' => 1440, // 24 hours
                                        'default' => 1,
                                    ],
                                    'enabled' => [
                                        'type' => 'boolean',
                                        'default' => true,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'sanitize_callback' => [$this, 'sanitizeSettings'],
            ]
        );
    }

    public function getSettings(): array
    {
        return get_option(self::OPTION_KEY, $this->defaultSettings);
    }

    public function getSetting(string $group, string $key)
    {
        $settings = $this->getSettings();
        $value = $settings[$group][$key] ?? $this->defaultSettings[$group][$key];

        return apply_filters("jwt_auth_{$group}_{$key}", $value);
    }

    public function sanitizeSettings($settings): array
    {
        if (!is_array($settings)) {
            return $this->defaultSettings;
        }

        $sanitized = [];

        // Data Management settings
        $sanitized['data_management'] = [
            'delete_on_deactivation' => (bool) ($settings['data_management']['delete_on_deactivation'] ?? true),
            'analytics_retention_days' => (int) ($settings['data_management']['analytics_retention_days'] ?? 90),
            'anonymize_ip' => (bool) ($settings['data_management']['anonymize_ip'] ?? false),
        ];

        // Token settings
        $sanitized['token_settings'] = [
            'jwt_expiration' => (int) ($settings['token_settings']['jwt_expiration'] ?? DAY_IN_SECONDS * 7),
            'refresh_token_expiration' => (int) ($settings['token_settings']['refresh_token_expiration'] ?? DAY_IN_SECONDS * 30),
            'signing_algorithm' => JWT::isSupportedAlgorithm($settings['token_settings']['signing_algorithm'] ?? 'HS256')
                ? $settings['token_settings']['signing_algorithm']
                : 'HS256',
            'enable_cors' => (bool) ($settings['token_settings']['enable_cors'] ?? false),
        ];

        // User settings
        $sanitized['user_settings'] = [
            'revoke_on_password_change' => (bool) ($settings['user_settings']['revoke_on_password_change'] ?? true),
            'revoke_on_role_change' => (bool) ($settings['user_settings']['revoke_on_role_change'] ?? true),
            'revoke_on_email_change' => (bool) ($settings['user_settings']['revoke_on_email_change'] ?? true),
            'delete_on_user_delete' => (bool) ($settings['user_settings']['delete_on_user_delete'] ?? true),
        ];

        // Rate limit settings
        $sanitized['rate_limit_settings'] = [
            'max_requests' => min(10000, max(1, (int) ($settings['rate_limit_settings']['max_requests'] ?? 60))),
            'window_minutes' => min(1440, max(1, (int) ($settings['rate_limit_settings']['window_minutes'] ?? 1))),
            'enabled' => (bool) ($settings['rate_limit_settings']['enabled'] ?? true),
        ];

        return $sanitized;
    }
}
