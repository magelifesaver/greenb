<?php

declare (strict_types=1);
namespace Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Jobs;

defined('ABSPATH') or exit;
/**
 * Object representation of a cron job using the Action Scheduler cron system.
 *
 * @link https://en.wikipedia.org/wiki/Cron for valid cron expressions
 *
 * @since 1.7.2
 *
 * @method string get_expression()
 * @method $this set_expression( string $expression )
 */
class Cron_Job extends Scheduled_Task
{
    /** @var string */
    protected string $expression = '';
    /**
     * Schedules the cron job.
     *
     * @NOTE If the cron schedule has an invalid pattern it will trigger a PHP error.
     *
     * @since 1.7.2
     *
     * @return void
     */
    public function schedule(): void
    {
        if (!$this->should_schedule()) {
            return;
        }
        $schedule = $this->get_expression();
        $name = $this->get_name();
        // @phpstan-ignore-next-line Action Scheduler must be available in the environment
        if (empty($schedule) || empty($name) || !is_callable('as_schedule_cron_action')) {
            return;
        }
        as_schedule_cron_action($this->get_schedule_at()->getTimestamp(), $schedule, $name, $this->get_arguments(), $this->get_group(), $this->get_unique(), $this->get_priority());
    }
}
