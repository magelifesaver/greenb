<?php

declare (strict_types=1);
namespace Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin;

defined('ABSPATH') or exit;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Contracts\WordPress_Plugin;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Traits\Is_Handler;
/**
 * Handles the assets of the plugin.
 *
 * @since 1.1.0
 */
final class Assets
{
    use Is_Handler;
    /** @var array<string, mixed> memoized list of assets data */
    private static array $script_data = [];
    /**
     * Constructor.
     *
     * @since 1.1.0
     *
     * @param WordPress_Plugin $plugin
     */
    protected function __construct(WordPress_Plugin $plugin)
    {
        self::$plugin = $plugin;
    }
    /**
     * Gets the URL pointing to an asset file.
     *
     * @since 1.1.0
     *
     * @param string $relative_path
     * @param string $filename
     * @return string
     */
    public static function get_asset_url(string $relative_path, string $filename): string
    {
        return self::plugin()->assets_url(trailingslashit($relative_path) . $filename);
    }
    /**
     * Returns the dependencies of an asset handled by WP Scripts.
     *
     * @since 1.1.0
     *
     * @param string|null $asset_file relative file path to the asset file
     * @return string[]
     */
    public static function get_asset_dependencies(?string $asset_file = null): array
    {
        $asset_data = $asset_file ? self::get_script_data($asset_file) : null;
        $dependencies = $asset_data['dependencies'] ?? [];
        return is_array($dependencies) ? $dependencies : [];
    }
    /**
     * Returns the version of an asset handled by WP Scripts.
     *
     * @since 1.1.0
     *
     * @param string|null $asset_file relative file path to the asset file
     * @return string
     */
    public static function get_asset_version(?string $asset_file = null): string
    {
        // @phpstan-ignore-next-line
        if (defined('Kestrel\Store_Credit\Scoped\WP_SCRIPT_DEBUG') && \Kestrel\Store_Credit\Scoped\WP_SCRIPT_DEBUG) {
            return (string) time();
        }
        $asset_data = $asset_file ? self::get_script_data($asset_file) : [];
        $version = null;
        if (isset($asset_data['version'])) {
            $version = $asset_data['version'];
        } elseif ($asset_file && is_readable(self::get_asset_file_path($asset_file))) {
            $version = filemtime(self::get_asset_file_path($asset_file));
        }
        if (!$version || !is_string($version)) {
            $version = self::plugin()->version();
        }
        return $version;
    }
    /**
     * Returns the full path to an asset file.
     *
     * @since 1.1.0
     *
     * @param string $asset_file
     * @return string
     */
    private static function get_asset_file_path(string $asset_file): string
    {
        return self::plugin()->absolute_dir_path() . '/assets/' . $asset_file;
    }
    /**
     * Returns the data of an asset handled by WP Scripts.
     *
     * @since 1.1.0
     *
     * @param string $asset_file relative file path to the asset file
     * @return array<string, mixed>
     */
    private static function get_script_data(string $asset_file): array
    {
        if (array_key_exists($asset_file, self::$script_data)) {
            return self::$script_data[$asset_file];
        }
        $asset_file = self::get_asset_file_path($asset_file);
        // phpcs:disable
        if (!is_readable($asset_file)) {
            trigger_error('Asset file not found or not readable: ' . $asset_file, \E_USER_WARNING);
            self::$script_data[$asset_file] = [];
        } else {
            // nosemgrep
            self::$script_data[$asset_file] = require $asset_file;
            // @phpstan-ignore-line
            if (!is_array(self::$script_data[$asset_file])) {
                trigger_error('Asset file does not return an array: ' . $asset_file, \E_USER_WARNING);
                self::$script_data[$asset_file] = [];
            }
        }
        // phpcs:enable
        return self::$script_data[$asset_file];
    }
}
