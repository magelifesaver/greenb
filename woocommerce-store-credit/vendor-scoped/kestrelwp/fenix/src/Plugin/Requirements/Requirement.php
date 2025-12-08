<?php

declare (strict_types=1);
namespace Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Requirements;

defined('ABSPATH') or exit;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Contracts\WordPress_Plugin;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Traits\Has_Plugin_Instance;
/**
 * Base class for plugin requirements.
 *
 * @since 1.1.0
 */
abstract class Requirement
{
    use Has_Plugin_Instance;
    /** @var array<string, mixed> */
    protected array $args;
    /**
     * Requirement constructor.
     *
     * The arguments can be used to pass additional data to the requirement which the {@see Requirement::is_satisfied()} method or the other methods of this class can use.
     *
     * @since 1.1.0
     *
     * @param WordPress_Plugin $plugin
     * @param array<string, mixed> $args
     */
    public function __construct(WordPress_Plugin $plugin, array $args = [])
    {
        self::$plugin = $plugin;
        $this->args = $args;
    }
    /**
     * Checks if the requirement is satisfied.
     *
     * @since 1.1.0
     *
     * @return bool
     */
    abstract public function is_satisfied(): bool;
    /**
     * Executes when the requirement is satisfied.
     *
     * @since 1.1.0
     *
     * @return void
     */
    public function success(): void
    {
        // by default, do nothing if the requirement is met - concrete requirements can override this method if needed
    }
    /**
     * Executes when the requirement is not satisfied.
     *
     * @since 1.1.0
     *
     * @return void
     */
    abstract public function fail(): void;
    /**
     * Returns whether the plugin should initialize when the requirement is not satisfied.
     *
     * @since 1.1.0
     *
     * @return bool
     */
    public function should_plugin_initialize_on_failure(): bool
    {
        // by default, do not initialize the plugin if a requirement is not satisfied - concrete requirements can override this method if needed
        return (bool) ($this->args['initialize_on_failure'] ?? \false);
    }
}
