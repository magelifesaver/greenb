<?php

declare (strict_types=1);
namespace Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Jobs;

defined('ABSPATH') or exit;
use Closure;
use DateInterval;
use DateTime;
use Exception;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Traits\Creates_New_Instances;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Traits\Has_Accessors;
/**
 * Abstract class for scheduled tasks.
 *
 * @NOTE This component presumes the Action Scheduler v3.0+ library to be available in the environment to function.
 *
 * @link https://actionscheduler.org/
 *
 * @see Task
 * @see Recurring_Task
 *
 * @since 1.5.1
 *
 * @method string get_name()
 * @method $this set_name( string $name )
 * @method array<string, mixed> get_arguments()
 * @method $this set_arguments( array $arguments )
 * @method string get_group()
 * @method $this set_group( string $group )
 * @method $this set_schedule_at( DateTime|null $schedule_at )
 * @method callable|null get_condition()
 * @method $this set_condition( callable|string|null $condition )
 * @method bool get_unique()
 * @method $this set_unique( bool $unique )
 * @method int get_priority()
 * @method $this set_priority( int $priority )
 */
abstract class Scheduled_Task
{
    use Creates_New_Instances;
    use Has_Accessors;
    /** @var string this will correspond to a hook name that can be listened to with an action */
    protected string $name = '';
    /** @var array<string, mixed> these arguments will be passed to the hook when the task is executed */
    protected array $arguments = [];
    /** @var string optional group to assign to the task */
    protected string $group = '';
    /** @var DateTime|null when the task should be scheduled to run (when null it will default to now) */
    protected ?DateTime $schedule_at = null;
    /** @var callable|callable-string|null optionally set a condition to determine if the task should run */
    protected $condition = null;
    /** @var bool whether the task should be unique */
    protected bool $unique = \false;
    /** @var int priority order when multiple tasks with the same name/group exist */
    protected int $priority = 10;
    /**
     * Constructor.
     *
     * @since 1.5.1
     *
     * @param string $hook hook name for the task
     * @param array<string, mixed> $args
     */
    protected function __construct(string $hook = '', array $args = [])
    {
        $this->name = $hook;
        $this->set_properties($args);
    }
    /**
     * Creates a new instance of a single task.
     *
     * @since 1.5.1
     *
     * @param string $name hook name for the task
     * @param array<string, mixed> $args optional
     * @return Task
     */
    public static function once(string $name, array $args = []): Task
    {
        return Task::create($name, $args);
    }
    /**
     * Creates a new instance of a recurring task that runs every minute.
     *
     * @since 1.5.1
     *
     * @param string $name hook name for the task
     * @param array<string, mixed> $args optional
     * @return Recurring_Task
     */
    public static function hourly(string $name, array $args = []): Recurring_Task
    {
        return Recurring_Task::create($name, $args)->every(new DateInterval('PT1H'));
    }
    /**
     * Creates a new instance of a recurring task that runs every day.
     *
     * @since 1.5.1
     *
     * @param string $name hook name for the task
     * @param array<string, mixed> $args optional
     * @return Recurring_Task
     */
    public static function daily(string $name, array $args = []): Recurring_Task
    {
        return Recurring_Task::create($name, $args)->every(new DateInterval('P1D'));
    }
    /**
     * Creates a new instance of a recurring task that runs every week.
     *
     * @since 1.5.1
     *
     * @param string $name hook name for the task
     * @param array<string, mixed> $args optional
     * @return Recurring_Task
     */
    public static function weekly(string $name, array $args = []): Recurring_Task
    {
        return Recurring_Task::create($name, $args)->every(new DateInterval('P1W'));
    }
    /**
     * Creates a new instance of a recurring task that runs every month.
     *
     * @since 1.5.1
     *
     * @param string $name hook name for the task
     * @param array<string, mixed> $args optional
     * @return Recurring_Task
     */
    public static function monthly(string $name, array $args = []): Recurring_Task
    {
        return Recurring_Task::create($name, $args)->every(new DateInterval('P1M'));
    }
    /**
     * Creates a new instance of a recurring task.
     *
     * @since 1.5.1
     *
     * @param string $name hook name for the task
     * @param array<string, mixed> $args optional
     * @return Recurring_Task
     */
    public static function recurring(string $name, array $args = []): Recurring_Task
    {
        return Recurring_Task::create($name, $args);
    }
    /**
     * Sets the time when the task should be scheduled to run.
     *
     * @since 1.5.1
     *
     * @param DateTime $date_time when the task should be scheduled to run
     * @return $this
     */
    public function at(DateTime $date_time): self
    {
        return $this->set_schedule_at($date_time);
    }
    /**
     * Returns the time to schedule the task.
     *
     * @since 1.5.1
     *
     * @return DateTime
     */
    public function get_schedule_at(): DateTime
    {
        if (!$this->schedule_at instanceof DateTime) {
            $this->schedule_at = new DateTime('now');
        }
        return $this->schedule_at;
    }
    /**
     * Returns the time when the task is scheduled to run (alias method).
     *
     * @since 1.6.0
     *
     * @return DateTime
     */
    public function scheduled_at(): DateTime
    {
        return $this->get_schedule_at();
    }
    /**
     * Determines if the task should be unique.
     *
     * @since 1.5.1
     *
     * @return bool
     */
    public function is_unique(): bool
    {
        return $this->unique;
    }
    /**
     * Determines if the task should be scheduled.
     *
     * @since 1.5.1
     *
     * @return bool
     */
    protected function should_schedule(): bool
    {
        if ($this->get_condition() instanceof Closure || is_callable($this->get_condition())) {
            return (bool) call_user_func($this->get_condition(), $this);
        }
        return \true;
    }
    /**
     * Determines whether the task should be scheduled.
     *
     * @since 1.5.1
     *
     * @return bool
     */
    public function is_scheduled(): bool
    {
        $name = $this->get_name();
        if (empty($name)) {
            return \false;
        }
        // backward compatibility for Action Scheduler versions before 3.0.0
        if (is_callable('as_has_scheduled_action')) {
            // @phpstan-ignore-line
            return (bool) as_has_scheduled_action($name, $this->get_arguments(), $this->get_group());
        } elseif (is_callable('as_next_scheduled_action')) {
            // @phpstan-ignore-line
            return (bool) as_next_scheduled_action($name, $this->get_arguments(), $this->get_group());
        }
        return \false;
        // @phpstan-ignore-line
    }
    /**
     * Returns the time when the task is scheduled for its next occurrence.
     *
     * @since 1.5.1
     *
     * @return DateTime|null
     */
    public function next_scheduled_time(): ?DateTime
    {
        $name = $this->get_name();
        // @phpstan-ignore-next-line Action Scheduler must be available in the environment
        if (empty($name) || !is_callable('as_next_scheduled_action')) {
            return null;
        }
        $timestamp = as_next_scheduled_action($name, $this->get_arguments(), $this->get_group());
        try {
            return is_int($timestamp) ? new DateTime('@' . $timestamp) : null;
        } catch (Exception $exception) {
            return null;
        }
    }
    /**
     * Schedules the task.
     *
     * @since 1.5.1
     *
     * @return void
     */
    abstract public function schedule(): void;
    /**
     * Re-schedules the task.
     *
     * This might be useful if the task's properties have changed and you want to ensure the currently scheduled task reflects those changes.
     *
     * @since 1.7.2
     *
     * @return void
     */
    public function reschedule(): void
    {
        $this->unschedule();
        $this->schedule();
    }
    /**
     * Un-schedules the task.
     *
     * @since 1.5.1
     *
     * @param bool $all whether to un-schedule all occurrences of the task (default false, only the current task is unscheduled)
     * @return void
     */
    public function unschedule(bool $all = \false): void
    {
        $name = $this->get_name();
        $unschedule = $all ? 'as_unschedule_all_actions' : 'as_unschedule_action';
        // @phpstan-ignore-next-line Action Scheduler must be available in the environment
        if (empty($name) || !is_callable($unschedule)) {
            return;
        }
        $unschedule($name, $this->get_arguments(), $this->get_group());
    }
    /**
     * Un-schedules all occurrences of the task.
     *
     * @since 1.6.0
     *
     * @return void
     */
    public function unschedule_all(): void
    {
        $this->unschedule(\true);
    }
}
