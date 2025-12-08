<?php

declare (strict_types=1);
namespace Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Jobs;

defined('ABSPATH') or exit;
/**
 * Object representing a scheduled task occurring once.
 *
 * @since 1.6.0
 */
class Task extends Scheduled_Task
{
    /**
     * Schedules the task.
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
        // @phpstan-ignore-next-line
        if (empty($name) || !is_callable('as_schedule_single_action')) {
            return;
        }
        as_schedule_single_action($this->get_schedule_at()->getTimestamp(), $name, $this->get_arguments(), $this->get_group(), $this->is_unique(), $this->get_priority());
    }
}
