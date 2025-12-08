<?php

declare (strict_types=1);
namespace Kestrel\Store_Credit\Scoped\Kestrel\Fenix\WordPress;

defined('ABSPATH') or exit;
use WP_Post;
use WP_Screen;
/**
 * Block Editor utilities.
 *
 * @since 1.1.0
 */
final class Block_Editor
{
    /**
     * Gets the block editor version, if available.
     *
     * @since 1.1.0
     *
     * @return string
     */
    public static function version(): string
    {
        global $wp_scripts;
        if (defined('Kestrel\Store_Credit\Scoped\GUTENBERG_VERSION')) {
            $version = \Kestrel\Store_Credit\Scoped\GUTENBERG_VERSION;
        } elseif (isset($wp_scripts->registered['wp-blocks']->ver)) {
            $version = $wp_scripts->registered['wp-blocks']->ver;
        } else {
            $version = '';
        }
        return $version;
    }
    /**
     * Determines if the Block Editor version matches the specified version and comparator.
     *
     * @since 1.1.0
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
     * Determines if the block editor is enabled for the given screen.
     *
     * This function will evaluate:
     * - the block editor status for the given screen (by object, or current screen when unspecified)
     * - the block editor status for the given post type (by string)
     * - the block editor status for the given post (by ID or object)
     *
     * @since 1.1.0
     *
     * @param int|mixed|string|WP_Post|WP_Screen|null $item optional: defaults to the current admin screen when unspecified
     * @return bool
     */
    public static function is_available($item = null): bool
    {
        if (null === $item || $item instanceof WP_Screen) {
            $screen = $item ?: Admin::get_current_screen();
            // @phpstan-ignore-next-line some older WP versions do not have this method
            return $screen && is_callable([$screen, 'is_block_editor']) && $screen->is_block_editor();
        }
        if (is_string($item) && post_type_exists($item)) {
            return function_exists('use_block_editor_for_post_type') && use_block_editor_for_post_type($item);
        }
        if ($item instanceof WP_Post || is_numeric($item)) {
            return function_exists('use_block_editor_for_post') && use_block_editor_for_post($item);
        }
        return \false;
    }
}
