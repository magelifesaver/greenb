<?php

declare(strict_types=1);

namespace Tmeister\JWTAuthPro\Services;

use Tmeister\JWTAuthPro\Database\TokenRepository;

class AdminService
{
    private const DEV_SERVER_URL = 'http://localhost:3000';

    public function __construct(
        private readonly TokenRepository $tokenRepository,
        private readonly SettingsService $settingsService,
    ) {}

    public function registerHooks(): void
    {
        // Admin UI hooks
        add_action('admin_menu', [$this, 'registerAdminMenu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);

        // Plugin links
        add_filter('plugin_action_links_' . JWT_AUTH_PRO_BASENAME, [$this, 'addPluginActionLinks']);

        // Scheduling
        add_action('init', [$this, 'maybeScheduleEvents']);
    }

    public function maybeScheduleEvents(): void
    {
        // Schedule daily analytics aggregation
        if (!wp_next_scheduled('jwt_auth_daily_analytics')) {
            wp_schedule_event(strtotime('tomorrow 00:00:00'), 'daily', 'jwt_auth_daily_analytics');
        }

        // Schedule weekly analytics consolidation
        if (!wp_next_scheduled('jwt_auth_weekly_consolidation')) {
            wp_schedule_event(
                strtotime('next monday 1am', time()),
                'weekly',
                'jwt_auth_weekly_consolidation'
            );
        }
    }

    public function registerAdminMenu(): void
    {
        add_submenu_page(
            'options-general.php',
            'JWT Auth Pro',
            'JWT Auth Pro 🛡',
            'manage_options',
            'jwt-auth-pro',
            [$this, 'renderAdminPage']
        );
    }

    public function renderAdminPage(): void
    {
        echo '<div id="jwt-auth-pro-app">Loading...</div>';
    }

    /**
     * Add plugin action links.
     *
     * @param  array<string>  $links  Array of plugin action links.
     * @return array<string>
     */
    public function addPluginActionLinks(array $links): array
    {
        $settingsUrl = admin_url('options-general.php?page=jwt-auth-pro');

        // Store the deactivate link if it exists
        $deactivateLink = $links['deactivate'] ?? '';

        // Reset links array to control order
        $links = [];

        // Add deactivate link first if it exists
        if ($deactivateLink) {
            $links['deactivate'] = $deactivateLink;
        }

        // Add settings link second
        $links['settings'] = sprintf(
            '<a href="%s">%s</a>',
            $settingsUrl,
            'Settings'
        );

        return $links;
    }

    public function enqueueAssets(string $hook): void
    {
        if ($hook !== 'settings_page_jwt-auth-pro') {
            return;
        }

        // Enqueue wp-api-fetch first
        wp_enqueue_script('wp-api-fetch');

        // Set up the REST API settings
        wp_localize_script('wp-api-fetch', 'wpApiSettings', [
            'root' => esc_url_raw(rest_url()),
            'nonce' => wp_create_nonce('wp_rest'),
            'is_whitelabeled' => LicenseService::getInstance()->getFreemius()->is_whitelabeled(),
            'preload' => [
                '/wp/v2/settings' => [
                    'body' => get_option('jwt_auth_pro_settings', []),
                    'headers' => ['X-WP-Nonce' => wp_create_nonce('wp_rest')],
                ],
            ],
        ]);

        if ($this->isDevelopment()) {
            // Development mode - load from Vite dev server
            add_action('admin_head', function () {
                echo '<script type="module">
                    import RefreshRuntime from "' . self::DEV_SERVER_URL . '/@react-refresh"
                    RefreshRuntime.injectIntoGlobalHook(window)
                    window.$RefreshReg$ = () => {}
                    window.$RefreshSig$ = () => (type) => type
                    window.__vite_plugin_react_preamble_installed__ = true
                </script>';
            });

            wp_enqueue_script(
                'vite-client',
                self::DEV_SERVER_URL . '/@vite/client',
                ['wp-api-fetch'], // Add dependency on wp-api-fetch
                null,
                true
            );

            wp_enqueue_script(
                'jwt-auth-pro-admin',
                self::DEV_SERVER_URL . '/admin/App.tsx',
                ['wp-api-fetch', 'vite-client'], // Add dependencies
                null,
                true
            );

            // Add type="module" to the scripts
            add_filter('script_loader_tag', function ($tag, $handle) {
                if (in_array($handle, ['vite-client', 'jwt-auth-pro-admin'])) {
                    return str_replace('<script', '<script type="module"', $tag);
                }

                return $tag;
            }, 10, 2);
        } else {
            wp_enqueue_script(
                'jwt-auth-pro-admin',
                JWT_AUTH_PRO_URL . 'dist/js/App.js',
                ['wp-api-fetch'], // Add dependency on wp-api-fetch
                JWT_AUTH_PRO_VERSION,
                true
            );

            wp_enqueue_style(
                'jwt-auth-pro-admin',
                JWT_AUTH_PRO_URL . 'dist/assets/style.css',
                [],
                JWT_AUTH_PRO_VERSION
            );

            // Add type="module" to the production script as well
            add_filter('script_loader_tag', function ($tag, $handle) {
                if ($handle === 'jwt-auth-pro-admin') {
                    return str_replace('<script', '<script type="module"', $tag);
                }

                return $tag;
            }, 10, 2);
        }
    }

    private function isDevelopment(): bool
    {
        return defined('JWT_AUTH_PRO_DEV') && JWT_AUTH_PRO_DEV === true;
    }
}
