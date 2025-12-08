<?php

declare (strict_types=1);
namespace Kestrel\Store_Credit\Scoped\Kestrel\Fenix;

defined('ABSPATH') or exit;
/**
 * WordPress helper.
 *
 * For additional helper classes, see the `WordPress` namespace.
 *
 * @since 1.1.0
 */
final class WordPress
{
    /**
     * Gets the installed WordPress version.
     *
     * @since 1.1.0
     *
     * @return string
     */
    public static function version(): string
    {
        return get_bloginfo('version');
    }
    /**
     * Determines if the installed WordPress version matches the specified version and comparator.
     *
     * @since 1.0.0
     *
     * @param string $comparator
     * @param string $version
     * @return bool
     */
    public static function is_version(string $comparator, string $version): bool
    {
        $installed_version = self::version();
        return $installed_version && version_compare($installed_version, $version, $comparator);
    }
    /**
     * Returns the WordPress content directory path.
     *
     * @since 1.2.0
     *
     * @param string $path optional path to append to the content directory
     * @return string
     */
    public static function content_path(string $path = ''): string
    {
        if ($path) {
            return trailingslashit(\WP_CONTENT_DIR) . ltrim($path, '/');
            // @phpstan-ignore-line WordPress constant
        }
        return untrailingslashit(\WP_CONTENT_DIR);
        // @phpstan-ignore-line WordPress constant
    }
    /**
     * Returns the WordPress uploads directory path.
     *
     * @since 1.2.0
     *
     * @param string $path optional path to append to the uploads directory
     * @return string
     */
    public static function uploads_path(string $path = ''): string
    {
        $content_path = self::content_path();
        if ($path) {
            return trailingslashit($content_path) . 'uploads/' . ltrim($path, '/');
        }
        return self::content_path('uploads');
    }
    /**
     * Determines if the WordPress installation uses a block (FSE) theme.
     *
     * @since 1.3.0
     *
     * @return bool
     */
    public static function uses_block_theme(): bool
    {
        return function_exists('wp_is_block_theme') && wp_is_block_theme() || function_exists('wp_get_theme') && wp_get_theme()->is_block_theme();
    }
}
