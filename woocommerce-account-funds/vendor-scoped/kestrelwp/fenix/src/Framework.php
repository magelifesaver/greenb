<?php

declare (strict_types=1);
namespace Kestrel\Account_Funds\Scoped\Kestrel\Fenix;

use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Traits\Has_Plugin_Instance;
defined('ABSPATH') or exit;
/**
 * Main framework handler.
 *
 * @since 1.0.0
 */
final class Framework
{
    /** @var string the current Fenix framework version */
    public const VERSION = '1.8.2';
    /**
     * Returns the bundled framework absolute file path.
     *
     * E.g.: `/www/path/to/wp-content/plugins/<plugin-directory>/vendor-prefixed/kestrelwp/fenix/src/Framework.php`
     *
     * @since 1.0.0
     *
     * @return string
     */
    public static function absolute_file_path(): string
    {
        return __FILE__;
    }
    /**
     * Returns the bundled framework dir path, without trailing slash.
     *
     * E.g.: `/www/path/to/wp-content/plugins/<plugin-directory>/vendor-prefixed/kestrelwp/fenix`
     *
     * @since 1.0.0
     *
     * @return string
     */
    public static function absolute_dir_path(): string
    {
        return untrailingslashit(plugin_dir_path(dirname(self::absolute_file_path())));
    }
    /**
     * Returns the bundled framework URL without a trailing slash.
     *
     * E.g.: `https://kestrelwp.com/wp-content/plugins/<plugin-directory>/vendor-prefixed/kestrelwp/fenix`
     *
     * @since 1.0.0
     *
     * @return string
     */
    public static function base_url(): string
    {
        return untrailingslashit(plugins_url('/', dirname(self::absolute_dir_path())));
    }
    /**
     * Returns the bundled framework assets URL without a trailing slash.
     *
     * E.g.: `http://kestrelwp.com/wp-content/plugins/<plugin-directory>/vendor-prefixed/kestrelwp/fenix/assets`
     *
     * @since 1.1.0
     *
     * @param string $path optional path to append to the assets URL
     * @return string
     */
    public static function assets_url(string $path = ''): string
    {
        return untrailingslashit(self::base_url() . '/assets/' . ltrim($path, '/'));
    }
    /**
     * Gets the textdomain to be used in the framework for translatable strings.
     *
     * This equals to the current plugin textdomain, therefore the plugin instance should be available before calling this method.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public static function textdomain(): string
    {
        $plugin = self::current_plugin();
        if ($plugin instanceof Plugin) {
            return $plugin->textdomain();
        }
        _doing_it_wrong(__METHOD__, 'The plugin instance is not available to set a textdomain.', '');
        return '';
    }
    /**
     * Returns the current main plugin instance, if available.
     *
     * @see Has_Plugin_Instance for handlers that need to access the plugin instance directly
     *
     * @since 1.0.0
     *
     * @return Plugin|null
     */
    public static function current_plugin(): ?Plugin
    {
        return Container::get(Plugin::class);
    }
}
