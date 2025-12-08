<?php

declare (strict_types=1);
namespace Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Admin\Notices;

use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Admin\Notices;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Traits\Has_Plugin_Instance;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Traits\Creates_New_Instances;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Traits\Has_Accessors;
defined('ABSPATH') or exit;
/**
 * Admin notice object.
 *
 * @since 1.0.0
 *
 * @method string get_id()
 * @method string get_type()
 * @method string|null get_title()
 * @method string get_content()
 * @method array<string, Call_To_Action> get_call_to_actions()
 * @method bool get_dismissible()
 * @method bool get_deferred()
 * @method string get_capability()
 * @method callable|null get_display_condition()
 * @method array<string, string> get_attributes()
 * @method $this set_id( string $id )
 * @method $this set_type( string $type )
 * @method $this set_title( ?string $title )
 * @method $this set_content( string $content )
 * @method $this set_call_to_actions( array $call_to_actions )
 * @method $this set_dismissible( ?bool $dismissible )
 * @method $this set_deferred( bool $deferred )
 * @method $this set_capability( string $capability )
 * @method $this set_display_condition( ?callable $display_condition )
 * @method $this set_attributes( array $attributes )
 */
class Notice
{
    use Creates_New_Instances;
    use Has_Accessors;
    use Has_Plugin_Instance;
    /** @var string */
    protected string $id = '';
    /** @var string */
    protected string $type = '';
    /** @var string|null defaults to plugin name */
    protected ?string $title = null;
    /** @var string */
    protected string $content = '';
    /** @var array<string, Call_To_Action> optional call to action(s) to append to the notice */
    protected array $call_to_actions = [];
    /** @var array<string, string> optional attributes */
    protected array $attributes = [];
    /** @var bool whether the notice can be dismissed by the user who sees it (when false it should be dismissed internally or via display condition) */
    protected bool $dismissible = \false;
    /** @var bool whether the notice should be deferred instead of displaying it directly */
    protected bool $deferred = \false;
    /** @var string user capability required to view the notice */
    protected string $capability = '';
    /** @var callable|callable-string|null optional condition callback to display the notice */
    protected $display_condition = null;
    /**
     * Notice constructor.
     *
     * @since 1.0.0
     *
     * @param array<string, mixed> $args
     */
    public function __construct(array $args = [])
    {
        if (empty($args['id']) && !empty($args['content'])) {
            $args['id'] = md5($args['content']);
        }
        $this->set_properties($args);
    }
    /**
     * Adds a call to action to the notice.
     *
     * @since 1.0.0
     *
     * @param Call_To_Action $cta
     * @return $this
     */
    public function add_call_to_action(Call_To_Action $cta): Notice
    {
        $this->call_to_actions[$cta->get_id()] = $cta;
        return $this;
    }
    /**
     * Removes a call to action from the notice.
     *
     * @since 1.0.0
     *
     * @param string $cta_id
     * @return $this
     */
    public function remove_call_to_action(string $cta_id): Notice
    {
        if (isset($this->call_to_actions[$cta_id])) {
            unset($this->call_to_actions[$cta_id]);
        }
        return $this;
    }
    /**
     * Determines if the notice has call to actions.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    public function has_call_to_actions(): bool
    {
        return !empty($this->call_to_actions);
    }
    /**
     * Determines if the notice should be displayed to the current user.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    public function should_display(): bool
    {
        $should_display = \true;
        $capability = $this->get_capability();
        if (!empty($capability)) {
            $should_display = current_user_can($capability);
        }
        $display_condition = $should_display ? $this->get_display_condition() : null;
        if (is_callable($display_condition)) {
            $should_display = call_user_func($display_condition);
        }
        if ($should_display && $this->is_dismissible()) {
            $should_display = !$this->is_dismissed();
        }
        return $should_display;
    }
    /**
     * Determines if the notice should be deferred.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    public function is_deferred(): bool
    {
        return $this->deferred;
    }
    /**
     * Determines if the notice should be displayed.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    public function is_dismissible(): bool
    {
        return $this->dismissible;
    }
    /**
     * Determines if the notice is already dismissed.
     *
     * @since 1.0.0
     *
     * @param int|null $user_id optional, defaults to the current user ID
     * @return bool
     */
    protected function is_dismissed(?int $user_id = null): bool
    {
        $user_id = null === $user_id ? get_current_user_id() : $user_id;
        if (empty($user_id)) {
            return \false;
        }
        return in_array($this->get_id(), Notices::get_dismissed_notices($user_id), \true);
    }
    /**
     * Dismisses the notice.
     *
     * @since 1.0.0
     *
     * @param int|null $user_id optional, defaults to the current user ID
     * @return void
     */
    public function dismiss(?int $user_id = null): void
    {
        $user_id = null === $user_id ? get_current_user_id() : $user_id;
        if (empty($user_id)) {
            return;
        }
        $dismissed_notices = Notices::get_dismissed_notices($user_id);
        $dismissed_notices[] = $this->get_id();
        update_user_meta($user_id, '_' . static::plugin()->key('dismissed_notices'), $dismissed_notices);
    }
    /**
     * Queues the notice for display.
     *
     * If you need to output a notice immediately, use the `print` method instead.
     *
     * @since 1.0.0
     *
     * @param Notice|null $notice
     * @return void
     */
    public function dispatch(?self $notice = null): void
    {
        if (null === $notice) {
            $notice = $this;
        }
        Notices::add($notice);
    }
    /**
     * Outputs the notice immediately.
     *
     * This method can be used to display a notice inline instead of dispatching it.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function print(): void
    {
        Notices::render($this);
    }
    /**
     * Creates a success notice.
     *
     * @since 1.0.0
     *
     * @param string $message
     * @param array<string, mixed> $args
     * @return $this
     */
    public static function success(string $message, array $args = []): Notice
    {
        return static::create(array_merge($args, ['content' => $message, 'type' => Type::SUCCESS]));
    }
    /**
     * Creates an info notice.
     *
     * @since 1.0.0
     *
     * @param string $message
     * @param array<string, mixed> $args
     * @return $this
     */
    public static function info(string $message, array $args = []): Notice
    {
        return static::create(array_merge($args, ['content' => $message, 'type' => Type::INFO]));
    }
    /**
     * Creates a warning notice.
     *
     * @since 1.0.0
     *
     * @param string $message
     * @param array<string, mixed> $args
     * @return $this
     */
    public static function warning(string $message, array $args = []): Notice
    {
        return static::create(array_merge($args, ['content' => $message, 'type' => Type::WARNING]));
    }
    /**
     * Creates an error notice.
     *
     * @since 1.0.0
     *
     * @param string $message
     * @param array<string, mixed> $args
     * @return $this
     */
    public static function error(string $message, array $args = []): Notice
    {
        return static::create(array_merge($args, ['content' => $message, 'type' => Type::ERROR]));
    }
}
