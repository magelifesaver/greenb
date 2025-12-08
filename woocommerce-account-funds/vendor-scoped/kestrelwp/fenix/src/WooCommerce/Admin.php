<?php

declare (strict_types=1);
namespace Kestrel\Account_Funds\Scoped\Kestrel\Fenix\WooCommerce;

defined('ABSPATH') or exit;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\WordPress;
/**
 * WooCommerce admin repository.
 *
 * @since 1.7.0
 */
final class Admin
{
    /**
     * Determines if the current screen is a WooCommerce screen (e.g. the settings page by default).
     *
     * @since 1.7.0
     *
     * @param string $slug the slug of the page, e.g. 'wc-settings'
     * @return bool
     */
    public static function is_screen(string $slug = 'wc-settings'): bool
    {
        $screen = WordPress\Admin::current_screen();
        if (!$screen) {
            return \false;
        }
        /**
         * Normalizes a WooCommerce page screen ID.
         *
         * Needed because WordPress uses a menu title (which is translatable), not slug, to generate a screen ID.
         *
         * @link https://core.trac.wordpress.org/ticket/21454
         */
        $prefix = sanitize_title(__('WooCommerce', 'woocommerce'));
        return $screen->id === $prefix . '_page_' . $slug;
    }
    /**
     * Determines if the current screen is the WooCommerce settings screen.
     *
     * @since 1.7.1
     *
     * @return bool
     */
    public static function is_settings_screen(): bool
    {
        return self::is_screen();
    }
    /**
     * Gets the URL for the WooCommerce status page.
     *
     * @since 1.7.0
     *
     * @param string $tab the tab to display, e.g. 'status' (default)
     * @return string
     */
    public static function status_url(string $tab = 'status'): string
    {
        return admin_url('admin.php?page=wc-status&tab=' . $tab);
    }
}
