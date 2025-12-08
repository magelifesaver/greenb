<?php

declare(strict_types=1);

/**
 * Plugin Name: JWT Auth Pro
 * Plugin URI: https://github.com/tmeister/jwt-auth-pro
 * Description: Professional JWT Authentication for WordPress REST API
 * Version: 0.2.1
 * Update URI: https://api.freemius.com
 * Author: Tmeister
 * Author URI: https://enriquechavez.co
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: jwt-auth-pro
 * Domain Path: /languages
 * Requires PHP: 8.1
 */

use Tmeister\JWTAuthPro\JWTAuthPro;

use function Tmeister\JWTAuthPro\activate;
use function Tmeister\JWTAuthPro\deactivate;

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('JWT_AUTH_PRO_FILE', __FILE__);
define('JWT_AUTH_PRO_PATH', plugin_dir_path(__FILE__));
define('JWT_AUTH_PRO_URL', plugin_dir_url(__FILE__));
define('JWT_AUTH_PRO_VERSION', '0.2.1');
define('JWT_AUTH_PRO_BASENAME', plugin_basename(__FILE__));

// Define database table constants
define('JWT_AUTH_PRO_TOKENS_TABLE', 'jwt_auth_tokens');
define('JWT_AUTH_PRO_ANALYTICS_TABLE', 'jwt_auth_analytics');
define('JWT_AUTH_PRO_ANALYTICS_SUMMARY_TABLE', 'jwt_auth_analytics_summary');

// Composer autoloader
if (file_exists($composer = __DIR__ . '/vendor/autoload.php')) {
    require_once $composer;
}

// Verify PHP version
if (version_compare(PHP_VERSION, '8.1.0', '<')) {
    add_action('admin_notices', function () {
        echo '<div class="notice notice-error"><p>' . __(
            'JWT Auth Pro requires PHP 8.1 or higher.',
            'jwt-auth-pro'
        ) . '</p></div>';
    });

    return;
}

// Register activation hook
register_activation_hook(
    JWT_AUTH_PRO_FILE,
    static function (): void {
        require_once JWT_AUTH_PRO_PATH . 'src/activation.php';
        activate();
    }
);

// Register deactivation hook
register_deactivation_hook(
    JWT_AUTH_PRO_FILE,
    static function (): void {
        require_once JWT_AUTH_PRO_PATH . 'src/deactivation.php';
        deactivate();
    }
);

// Initialize plugin
add_action(
    'plugins_loaded',
    static function (): void {
        require_once JWT_AUTH_PRO_PATH . 'src/JWTAuthPro.php';
        JWTAuthPro::getInstance();
    }
);

function run_jwt_auth_pro(): void
{
    $plugin = JWTAuthPro::getInstance();
    $plugin->boot();
}

run_jwt_auth_pro();
