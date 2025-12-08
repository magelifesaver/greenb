<?php

declare (strict_types=1);
namespace Kestrel\Account_Funds\Scoped\Kestrel\Fenix\WordPress;

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
     * @deprecated 1.7.0
     *
     * @return WP_Screen|null
     */
    public static function get_current_screen(): ?WP_Screen
    {
        _deprecated_function(__METHOD__, '1.7.0', 'Kestrel\Fenix\WordPress\Admin::current_screen()');
        return self::current_screen();
    }
    /**
     * Returns the current WordPress admin screen.
     *
     * @since 1.7.0
     *
     * @return WP_Screen|null
     */
    public static function current_screen(): ?WP_Screen
    {
        global $current_screen;
        if (function_exists('get_current_screen')) {
            return get_current_screen();
        } elseif ($current_screen instanceof WP_Screen) {
            return $current_screen;
        }
        return null;
    }
}
