<?php

declare (strict_types=1);
namespace Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Contracts;

use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Helpers\Arrays;
defined('ABSPATH') or exit;
/**
 * WordPress plugin contract.
 *
 * @since 1.2.0
 */
interface WordPress_Plugin
{
    /**
     * Returns the plugin identifier with underscores (snake_case).
     *
     * @since 1.0.0
     *
     * @param string|null $case optional case to convert the identifier to
     * @return string
     */
    public function id(?string $case = null): string;
    /**
     * Returns the plugin id with dashes (kebab-case) in place of underscores (snake_case, default).
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function id_dasherized(): string;
    /**
     * Returns the plugin full name.
     *
     * @since 1.0.0
     *
     * @return string plugin name
     */
    public function name(): string;
    /**
     * Returns the current version of the plugin.
     *
     * @since 1.0.0
     *
     * @return string semver
     */
    public function version(): string;
    /**
     * Gets the plugin vendor.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function vendor(): string;
    /**
     * Return the main plugin main file path.
     *
     * @since 1.0.0
     *
     * @return string the full path and filename of the plugin file
     */
    public function absolute_file_path(): string;
    /**
     * Returns the path to the plugin main file relative to the plugins' directory.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function relative_file_path(): string;
    /**
     * Returns the path to the plugin directory without a trailing slash.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function absolute_dir_path(): string;
    /**
     * Returns a path related to the plugin's installation root.
     *
     * @since 1.2.0
     *
     * @param string $path optional path to append to the root directory
     * @param bool $absolute whether to return the absolute path (default true, otherwise false for relative)
     * @return string
     */
    public function path(string $path, bool $absolute = \true): string;
    /**
     * Returns the relative path to the plugin's translations directory.
     *
     * @since 1.0.0
     *
     * @param string $path optional path to append to the translations directory
     * @param bool $absolute optional whether to return the absolute path (default true)
     * @return string
     */
    public function translations_path(string $path = '', bool $absolute = \true): string;
    /**
     * Returns the absolute path to the plugin's assets directory.
     *
     * @since 1.1.0
     *
     * @param string $path optional path to append to the assets directory
     * @param bool $absolute optional whether to return the absolute path (default true)
     * @return string
     */
    public function assets_path(string $path = '', bool $absolute = \true): string;
    /**
     * Returns the absolute path to the plugin's templates directory.
     *
     * @since 1.2.0
     *
     * @param string $path optional path relative to the templates directory
     * @param bool $absolute optional whether to return the absolute path (default true)
     */
    public function templates_path(string $path = '', bool $absolute = \true): string;
    /**
     * Returns the plugin's URL without a trailing slash.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function base_url(): string;
    /**
     * Returns the plugin's textdomain.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function textdomain(): string;
    /**
     * Returns the plugin's locale.
     *
     * @since 1.4.0
     *
     * @return string e.g. 'en_US'
     */
    public function locale(): string;
    /**
     * Checks if the plugin is multilingual.
     *
     * @since 1.4.0
     *
     * @return bool true if the plugin is multilingual, false otherwise
     */
    public function is_multilingual(): bool;
    /**
     * Returns the plugin's documentation URL.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function documentation_url(): string;
    /**
     * Returns the plugin's support URL.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function support_url(): string;
    /**
     * Returns the plugin's sales page URL.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function sales_page_url(): string;
    /**
     * Returns the plugin's reviews URL.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function reviews_url(): string;
    /**
     * Returns the URL to the plugin's assets.
     *
     * @since 1.0.0
     *
     * @param string $path optional path
     * @return string
     */
    public function assets_url(string $path = ''): string;
    /**
     * Returns the plugin settings URL.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function settings_url(): string;
    /**
     * Returns the plugin dashboard URL.
     *
     * @since 1.0.0
     *
     * @return string|null returns null when the dashboard is not available
     */
    public function dashboard_url(): ?string;
    /**
     * Returns the plugin configuration.
     *
     * @since 1.0.0
     *
     * @return Arrays array data object
     */
    public function config(): Arrays;
    /**
     * Returns a formatted hook name for the plugin.
     *
     * @since 1.0.0
     *
     * @param string $hook without prepending underscores
     * @return string e.g. '<vendor>_<plugin_id>_<hook>'
     */
    public function hook(string $hook): string;
    /**
     * Returns a formatted key for the plugin in snake case.
     *
     * @since 1.0.0
     *
     * @param string|null $key
     * @return string e.g. '<vendor>_<plugin_id>_<key>'
     */
    public function key(?string $key = null): string;
    /**
     * Returns a formatted handle for the plugin in kebab case.
     *
     * @since 1.0.0
     *
     * @param string|null $handle
     * @return string e.g. '<vendor>-<plugin_id>-<handle>'
     */
    public function handle(?string $handle = null): string;
    /**
     * Checks if the plugin is a fresh installation.
     *
     * @since 1.1.0
     *
     * @return bool
     */
    public function is_new_installation(): bool;
    /**
     * Returns plugin information as an array.
     *
     * @since 1.0.0
     *
     * @return array<string, mixed>
     */
    public function to_array(): array;
}
