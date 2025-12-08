<?php

declare (strict_types=1);
namespace Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Lifecycle;

defined('ABSPATH') or exit;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Contracts\WordPress_Plugin;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Lifecycle\Contracts\Installer as Lifecycle_Routine;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Traits\Is_Handler;
/**
 * Base class for plugin lifecycle routines.
 *
 * Plugin implementations that need to define installation, activation, and deactivation routines may extend this class.
 * Then, pass their own class name to the `installer` key in the `lifecycle` array of the plugin configuration.
 *
 * @see Lifecycle::install()
 * @see Lifecycle::activate()
 * @see Lifecycle::deactivate()
 *
 * @since 1.0.0
 */
abstract class Installer implements Lifecycle_Routine
{
    use Is_Handler;
    /**
     * Constructor.
     *
     * @since 1.1.0
     *
     * @param WordPress_Plugin $plugin
     */
    protected function __construct(WordPress_Plugin $plugin)
    {
        static::$plugin = $plugin;
    }
    /**
     * Performs the installation lifecycle routine.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function install(): void
    {
        // stub method
    }
    /**
     * Performs the activation lifecycle routine.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function activate(): void
    {
        // stub method
    }
    /**
     * Performs the deactivation lifecycle routine.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function deactivate(): void
    {
        // stub method
    }
}
