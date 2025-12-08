<?php

namespace Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Lifecycle\Contracts;

use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Loader;
defined('ABSPATH') or exit;
/**
 * Contract for plugin uninstall routines.
 *
 * Classes implementing this contract should use PHP code that is compatible with PHP 5.6+ as it may be run on older WordPress installations.
 *
 * @see Loader::plugin_uninstall()
 *
 * @since 1.0.0
 */
interface Uninstaller
{
    /**
     * Performs uninstallation routines.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function uninstall();
}
