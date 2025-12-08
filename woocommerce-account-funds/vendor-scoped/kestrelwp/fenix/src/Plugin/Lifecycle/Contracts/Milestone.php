<?php

declare (strict_types=1);
namespace Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Lifecycle\Contracts;

defined('ABSPATH') or exit;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Admin\Notices\Notice;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Traits\Is_Handler;
/**
 * Contract for milestones in the plugin lifecycle.
 *
 * @since 1.6.0
 */
interface Milestone
{
    /**
     * Initializes the milestone.
     *
     * @see Is_Handler::initialize()
     *
     * @since 1.6.0
     *
     * @param mixed ...$args
     * @return static
     */
    public static function initialize(...$args);
    /**
     * Returns the unique identifier for the milestone.
     *
     * @since 1.6.0
     *
     * @return string
     */
    public static function id(): string;
    /**
     * Returns the milestone notice, if any.
     *
     * @since 1.6.0
     *
     * @return Notice|null
     */
    public function notice(): ?Notice;
    /**
     * Determines if the milestone is achieved.
     *
     * @since 1.6.0
     *
     * @return bool
     */
    public static function is_achieved(): bool;
    /**
     * Triggers the milestone.
     *
     * @since 1.6.0
     *
     * @param array<string, mixed> $args
     * @return void
     */
    public static function trigger(array $args = []): void;
}
