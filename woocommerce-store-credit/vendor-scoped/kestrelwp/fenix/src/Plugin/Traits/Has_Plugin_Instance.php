<?php

declare (strict_types=1);
namespace Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Traits;

defined('ABSPATH') or exit;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Container;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Contracts\WordPress_Plugin;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\WooCommerce\Extension;
/**
 * Trait used by plugin handlers that need to access to the main plugin instance.
 *
 * @since 1.0.0
 *
 * @phpstan-consistent-constructor
 */
trait Has_Plugin_Instance
{
    /** @var Extension|Plugin|null */
    protected static ?WordPress_Plugin $plugin = null;
    // @phpstan-ignore-line
    /**
     * Returns the main instance of the plugin or the WooCommerce extension.
     *
     * @since 1.0.0
     *
     * @return Extension|Plugin
     */
    protected static function plugin(): WordPress_Plugin
    {
        // load from the handler own instance, if set
        if (is_a(static::$plugin, Plugin::class)) {
            return static::$plugin;
        }
        /** @var Extension|Plugin $plugin load from container */
        $plugin = Container::get(Plugin::class);
        return $plugin;
    }
}
