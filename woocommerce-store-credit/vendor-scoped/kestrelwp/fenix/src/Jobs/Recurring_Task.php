<?php

declare (strict_types=1);
namespace Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Jobs;

defined('ABSPATH') or exit;
use DateInterval;
/**
 * Object representation of a task repeating at a specific interval.
 *
 * @since 1.6.0
 *
 * @method DateInterval|null get_interval()
 * @method $this set_interval( DateInterval $interval )
 */
class Recurring_Task extends Scheduled_Task
{
    /** @var DateInterval|null */
    protected ?DateInterval $interval = null;
    /**
     * Sets the interval for the recurring task.
     *
     * @since 1.6.0
     *
     * @param DateInterval $interval
     * @return $this
     */
    public function every(DateInterval $interval): Recurring_Task
    {
        return $this->set_interval($interval);
    }
    /**
     * Gets the schedule interval in seconds.
     *
     * @since 1.6.0
     *
     * @return int|null
     */
    protected function get_interval_timestamp(): ?int
    {
        $interval = $this->get_interval();
        $start_time = $this->get_schedule_at();
        if (!$interval instanceof DateInterval) {
            return null;
        }
        $next_time = (clone $this->get_schedule_at())->add($interval);
        return $next_time->getTimestamp() - $start_time->getTimestamp();
    }
    /**
     * Schedules the recurring action.
     *
     * @since 1.6.0
     *
     * @return void
     */
    public function schedule(): void
    {
        if (!$this->should_schedule()) {
            return;
        }
        $name = $this->get_name();
        $interval = $this->get_interval_timestamp();
        // @phpstan-ignore-next-line Action Scheduler must be available in the environment
        if (null === $interval || empty($name) || !is_callable('as_schedule_recurring_action')) {
            return;
        }
        as_schedule_recurring_action($this->get_schedule_at()->getTimestamp(), $interval, $name, $this->get_arguments(), $this->get_group(), $this->is_unique(), $this->get_priority());
    }
}
