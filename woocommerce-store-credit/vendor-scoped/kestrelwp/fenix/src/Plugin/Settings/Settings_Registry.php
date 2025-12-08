<?php

declare (strict_types=1);
namespace Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Settings;

defined('ABSPATH') or exit;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Contracts\WordPress_Plugin;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Traits\Is_Handler;
/**
 * Settings registry.
 *
 * When plugins create {@see Setting} instances, they should be registered with this class via the {@see Setting::register()} method.
 *
 * @since 1.1.0
 */
final class Settings_Registry
{
    use Is_Handler;
    /** @var array<string, Setting> */
    private static array $settings;
    /**
     * Settings handler constructor.
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
     * Registers a setting.
     *
     * @since 1.1.0
     *
     * @param Setting $setting
     * @return void
     */
    public static function register(Setting $setting): void
    {
        self::$settings[$setting->get_name()] = $setting;
    }
    /**
     * De-registers a setting.
     *
     * @since 1.1.0
     *
     * @param mixed|Setting|string $setting
     * @return void
     */
    public static function deregister($setting): void
    {
        if ($setting instanceof Setting) {
            $name = $setting->get_name();
        } else {
            $name = $setting;
        }
        if (is_string($name)) {
            unset(self::$settings[$name]);
        }
    }
    /**
     * Returns a specific registered setting by its name.
     *
     * @since 1.1.0
     *
     * @param string $name setting name
     * @return Setting|null
     */
    public static function get_setting(string $name): ?Setting
    {
        return self::$settings[$name] ?? null;
    }
    /**
     * Returns multiple registered settings by their names.
     *
     * @since 1.1.0
     *
     * @param string[] $names setting names (leave empty to return all settings)
     * @return array<string, Setting>
     */
    public static function get_settings(array $names = []): array
    {
        $settings = [];
        if (empty($names)) {
            $settings = self::$settings;
        } else {
            foreach ($names as $name) {
                if ($setting = self::get_setting($name)) {
                    $settings[$name] = $setting;
                }
            }
        }
        return $settings;
    }
    /**
     * Returns all settings as an associative array.
     *
     * @since 1.1.0
     *
     * @return array<string, array<string, mixed>>
     */
    public static function to_array(): array
    {
        $to_array = [];
        foreach (self::get_settings() as $setting) {
            $to_array[$setting->get_name()] = $setting->to_array();
        }
        return $to_array;
    }
}
