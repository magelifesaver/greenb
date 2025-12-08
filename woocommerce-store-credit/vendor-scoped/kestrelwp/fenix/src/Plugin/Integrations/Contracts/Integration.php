<?php

declare (strict_types=1);
namespace Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Integrations\Contracts;

use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Traits\Is_Handler;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugins;
defined('ABSPATH') or exit;
/**
 * Contract for plugin integrations.
 *
 * @since 1.0.0
 */
interface Integration
{
    /**
     * Initializes the integration.
     *
     * @see Is_Handler::initialize()
     *
     * @since 1.0.0
     *
     * @param mixed ...$args
     * @return static|void
     */
    public static function initialize(...$args);
    /**
     * Determines whether the integration should be initialized.
     *
     * Classes implementing this method should check if the plugin they are integrating with is available.
     * For example, they can use {@see Plugins::is_plugin_active()} to check if a plugin is active.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    public static function should_initialize(): bool;
}
