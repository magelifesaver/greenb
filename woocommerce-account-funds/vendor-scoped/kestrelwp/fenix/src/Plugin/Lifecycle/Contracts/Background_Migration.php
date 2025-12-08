<?php

declare (strict_types=1);
namespace Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Lifecycle\Contracts;

defined('ABSPATH') or exit;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Jobs\Background_Job;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Contracts\WordPress_Plugin;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Lifecycle;
/**
 * Contract for background migration routines.
 *
 * @since 1.6.0
 */
interface Background_Migration extends Migration
{
    /**
     * Initializes the background migration.
     *
     * The {@see Lifecycle::update()} method will call this method to ensure that the processor will autonomously handle the migration tasks in the background until completion.
     *
     * This method should instantiate one or more jobs via {@see Background_Job::initialize()}.
     * The concrete implementations of this class should handle the specifics of the migration process.
     * This method could set a processor instance to an internal property, then have {@see Background_Migration::upgrade()} retrieve it and push items to its queue, save and dispatch the job.
     *
     * @since 1.6.0
     *
     * @param WordPress_Plugin $plugin concrete plugin instance
     * @return void
     */
    public function __construct(WordPress_Plugin $plugin);
    /**
     * Determines whether the background migration process has completed.
     *
     * @see Background_Job::is_processing()
     * @see Background_Job::is_queued()
     *
     * @since 1.6.0
     *
     * @return bool
     */
    public function is_done(): bool;
}
