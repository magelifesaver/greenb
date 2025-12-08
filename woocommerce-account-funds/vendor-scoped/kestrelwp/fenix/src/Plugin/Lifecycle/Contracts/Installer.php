<?php

declare (strict_types=1);
namespace Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Lifecycle\Contracts;

defined('ABSPATH') or exit;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Lifecycle;
/**
 * Contract for plugin lifecycle routines.
 *
 * @see Lifecycle::install()
 * @see Lifecycle::activate()
 * @see Lifecycle::deactivate()
 *
 * @since 1.0.0
 */
interface Installer
{
    /**
     * Performs the installation lifecycle routine.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function install(): void;
    /**
     * Performs the activation lifecycle routine.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function activate(): void;
    /**
     * Performs the deactivation lifecycle routine.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function deactivate(): void;
}
