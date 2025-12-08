<?php

declare(strict_types=1);

namespace Tmeister\JWTAuthPro\Actions\Admin\System;

use WP_REST_Response;

class GetSystemInfoAction
{
    public function execute(): WP_REST_Response
    {
        global $wp_version, $wpdb;

        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        // Get all plugins with their status
        $plugins = [];
        $all_plugins = get_plugins();
        foreach ($all_plugins as $plugin_path => $plugin_data) {
            $plugins[] = [
                'name' => $plugin_data['Name'],
                'version' => $plugin_data['Version'],
                'status' => is_plugin_active($plugin_path) ? 'active' : 'inactive',
                'author' => $plugin_data['Author'] ?? '',
            ];
        }

        // Sort plugins by status (active first) and then by name
        usort($plugins, function ($a, $b) {
            if ($a['status'] === $b['status']) {
                return strcasecmp($a['name'], $b['name']);
            }
            return $a['status'] === 'active' ? -1 : 1;
        });

        // Get active theme information
        $theme = wp_get_theme();
        $theme_info = [
            'name' => $theme->get('Name'),
            'version' => $theme->get('Version'),
            'author' => $theme->get('Author'),
            'status' => 'active',
        ];

        // Get WordPress constants
        $wp_constants = [
            'WP_DEBUG' => defined('WP_DEBUG') && WP_DEBUG,
            'WP_DEBUG_LOG' => defined('WP_DEBUG_LOG') && WP_DEBUG_LOG,
            'WP_DEBUG_DISPLAY' => defined('WP_DEBUG_DISPLAY') && WP_DEBUG_DISPLAY,
            'SCRIPT_DEBUG' => defined('SCRIPT_DEBUG') && SCRIPT_DEBUG,
            'WP_CACHE' => defined('WP_CACHE') && WP_CACHE,
            'CONCATENATE_SCRIPTS' => defined('CONCATENATE_SCRIPTS') && CONCATENATE_SCRIPTS,
            'COMPRESS_SCRIPTS' => defined('COMPRESS_SCRIPTS') && COMPRESS_SCRIPTS,
            'COMPRESS_CSS' => defined('COMPRESS_CSS') && COMPRESS_CSS,
            'WP_LOCAL_DEV' => defined('WP_LOCAL_DEV') && WP_LOCAL_DEV,
        ];

        // Get PHP configuration
        $php_config = [
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'post_max_size' => ini_get('post_max_size'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'max_input_vars' => ini_get('max_input_vars'),
            'allow_url_fopen' => ini_get('allow_url_fopen'),
            'display_errors' => ini_get('display_errors'),
            'max_input_time' => ini_get('max_input_time'),
            'session' => [
                'save_handler' => ini_get('session.save_handler'),
                'save_path' => ini_get('session.save_path'),
            ],
            'opcache_enabled' => function_exists('opcache_get_status') && @opcache_get_status() !== false,
        ];

        // Get server information
        $server_info = [
            'software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'os' => PHP_OS_FAMILY,
            'architecture' => PHP_INT_SIZE === 8 ? '64-bit' : '32-bit',
            'ip' => $_SERVER['SERVER_ADDR'] ?? 'Unknown',
            'hostname' => gethostname() ?: 'Unknown',
            'ssl_installed' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        ];

        // Get WordPress configuration
        $wp_config = [
            'version' => $wp_version,
            'site_url' => get_site_url(),
            'home_url' => get_home_url(),
            'is_multisite' => is_multisite(),
            'max_upload_size' => size_format(wp_max_upload_size()),
            'memory_limit' => WP_MEMORY_LIMIT,
            'permalink_structure' => get_option('permalink_structure'),
            'language' => get_locale(),
            'timezone' => [
                'string' => get_option('timezone_string'),
                'gmt_offset' => get_option('gmt_offset'),
            ],
            'admin_email' => get_option('admin_email'),
            'environment_type' => wp_get_environment_type(),
        ];

        // Get database information
        $db_info = [
            'version' => $wpdb->get_var('SELECT VERSION()'),
            'charset' => $wpdb->charset,
            'collate' => $wpdb->collate,
            'prefix' => $wpdb->prefix,
            'tables' => [
                'base_prefix' => $wpdb->base_prefix,
                'prefix_length' => strlen($wpdb->prefix),
            ],
        ];

        // Get system information
        $system_info = [
            'php_version' => [
                'version' => PHP_VERSION,
                'status' => version_compare(PHP_VERSION, '8.1.0', '>=') ? 'optimal' : 'warning',
            ],
            'wordpress_version' => [
                'version' => $wp_version,
                'status' => version_compare($wp_version, '6.0.0', '>=') ? 'optimal' : 'warning',
            ],
            'mysql_version' => [
                'version' => $this->getMySQLVersion(),
                'status' => version_compare($this->getMySQLVersion(), '8.0.0', '>=') ? 'optimal' : 'warning',
            ],
            'server' => $server_info,
            'php' => $php_config,
            'wordpress' => $wp_config,
            'database' => $db_info,
            'constants' => $wp_constants,
            'theme' => $theme_info,
            'plugins' => [
                'total' => count($all_plugins),
                'active' => count(array_filter($plugins, fn($p) => $p['status'] === 'active')),
                'items' => $plugins,
            ],
        ];

        return new WP_REST_Response([
            'success' => true,
            'data' => $system_info,
        ], 200);
    }

    private function getMySQLVersion(): string
    {
        global $wpdb;
        $version = $wpdb->get_var('SELECT VERSION()');
        return $version ?: '0.0.0';
    }
}
