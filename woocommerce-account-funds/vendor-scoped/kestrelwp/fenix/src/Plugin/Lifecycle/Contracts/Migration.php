<?php

declare (strict_types=1);
namespace Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Lifecycle\Contracts;

defined('ABSPATH') or exit;
/**
 * Contract for plugin data migration routines.
 *
 * @since 1.0.0
 */
interface Migration
{
    /**
     * Performs the migration.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function upgrade(): void;
}
