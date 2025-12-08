<?php

declare (strict_types=1);
namespace Kestrel\Store_Credit\Scoped\Kestrel\Fenix\WordPress;

defined('ABSPATH') or exit;
use WP_Screen;
/**
 * WordPress admin utilities.
 *
 * @since 1.0.0
 */
final class Admin
{
    /**
     * Gets the current WordPress admin screen.
     *
     * @since 1.0.0
     *
     * @return WP_Screen|null
     */
    public static function get_current_screen(): ?WP_Screen
    {
        global $current_screen;
        if (function_exists('get_current_screen')) {
            return get_current_screen();
        } elseif ($current_screen) {
            return $current_screen;
        }
        return null;
    }
}
