<?php

declare (strict_types=1);
namespace Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Lifecycle;

defined('ABSPATH') or exit;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Helpers\Strings;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Logger;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Admin\Notices\Call_To_Action;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Admin\Notices\Notice;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Contracts\WordPress_Plugin;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Lifecycle;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Lifecycle\Contracts\Milestone as Milestone_Contract;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Traits\Is_Handler;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\WooCommerce\Contracts\WooCommerce_Extension;
/**
 * Base milestone class for recording significant events in the plugin lifecycle.
 *
 * @since 1.6.0
 */
abstract class Milestone implements Milestone_Contract
{
    use Is_Handler;
    /** @var string */
    protected const ID = '';
    /** @var string */
    private const HOOK_SUFFIX = '_milestone_triggered';
    /** @var string */
    private const NOTICE_SUFFIX = '_milestone_achieved';
    /** @var string|null */
    protected ?string $title = '';
    /** @var string|null */
    protected ?string $message = null;
    /** @var Call_To_Action|Call_To_Action[]|null */
    protected $cta = null;
    /** @var Notice|null */
    protected ?Notice $notice = null;
    /**
     * Constructor.
     *
     * @since 1.6.0
     *
     * @param WordPress_Plugin $plugin
     */
    protected function __construct(WordPress_Plugin $plugin)
    {
        static::$plugin = $plugin;
        if ($milestone_unlocked_hook = self::hook()) {
            static::add_action($milestone_unlocked_hook, [$this, 'record']);
        }
    }
    /**
     * Returns the unique identifier for the milestone.
     *
     * @since 1.6.0
     *
     * @return string
     */
    public static function id(): string
    {
        return static::ID;
    }
    /**
     * Returns the hook name for the milestone trigger.
     *
     * @since 1.6.0
     *
     * @return string
     */
    private static function hook(): string
    {
        // ensures the hook name is in snake_case format
        return Strings::string(static::plugin()->hook(static::id()))->append(self::HOOK_SUFFIX)->snake_case()->to_string();
    }
    /**
     * Returns the milestone title.
     *
     * @since 1.6.1
     *
     * @return string|null return null to set the title to the plugin name, empty string to not display a title
     */
    protected function title(): ?string
    {
        return $this->title;
    }
    /**
     * Returns the milestone message.
     *
     * Milestones without a message will not be displayed as a notice in the admin area.
     *
     * @since 1.6.0
     *
     * @return string|null
     */
    protected function message(): ?string
    {
        return $this->message;
    }
    /**
     * Returns any call-to-action associated with the milestone notice.
     *
     * Milestones can have multiple call-to-action buttons that provide additional actions for the user.
     *
     * @since 1.6.0
     *
     * @return Call_To_Action|Call_To_Action[]|null
     */
    protected function call_to_action()
    {
        return $this->cta;
    }
    /**
     * Returns the milestone notice.
     *
     * If a notice is provided, it will return that notice.
     * Otherwise, it will create a new notice based on the milestone message and any call-to-action(s) returned in the above methods.
     * Concrete implementations can also override this method altogether as a third option.
     *
     * @since 1.6.0
     *
     * @return Notice|null
     */
    public function notice(): ?Notice
    {
        $id = static::id();
        if (!$id) {
            return null;
        }
        $notice_id = Strings::string($id)->append(self::NOTICE_SUFFIX)->snake_case()->to_string();
        if ($this->notice) {
            return $this->notice->set_id($id);
        }
        $message = $this->message();
        if (!$message) {
            return null;
        }
        return Notice::success($message)->set_id($notice_id)->set_title($this->title())->set_capability(self::plugin() instanceof WooCommerce_Extension ? 'manage_woocommerce' : 'manage_options')->set_dismissible(\true)->set_call_to_actions((array) $this->call_to_action());
    }
    /**
     * Returns the data associated with the milestone.
     *
     * This is typically available only after the milestone has been achieved.
     *
     * Concrete implementations could use this method to obtain additional data related to the milestone, which could be used to output the notice or its message.
     *
     * @since 1.7.0
     *
     * @return array<string, mixed>
     */
    protected function data(): array
    {
        $milestones = Lifecycle::get_milestone_history();
        $data = $milestones[static::id()] ?? [];
        return is_array($data) ? $data : [];
        // @phpstan-ignore-line type safety check
    }
    /**
     * Determines if the milestone has been achieved.
     *
     * @since 1.6.0
     *
     * @return bool
     */
    public static function is_achieved(): bool
    {
        $milestones = Lifecycle::get_milestone_history();
        $data = $milestones[static::id()] ?? [];
        // @phpstan-ignore-next-line sanity checks
        if (!is_array($data) || !isset($data['achieved'])) {
            return \false;
        }
        return 'yes' === $data['achieved'];
    }
    /**
     * Determines if the milestone should be triggered.
     *
     * Defaults to checking if the milestone has not been achieved yet.
     * Concrete implementations can override this method to provide additional logic for when the milestone should be triggered.
     *
     * @since 1.6.0
     *
     * @param array<string, mixed> $args optional arguments passed from the trigger method which concrete implementations can use to determine if the milestone should be triggered
     * @return bool
     */
    protected static function should_trigger(array $args = []): bool
    {
        return static::id() && !static::is_achieved();
    }
    /**
     * Triggers the milestone.
     *
     * @since 1.6.0
     *
     * @param array<string, mixed> $args optional arguments to pass to the milestone trigger hook
     * @return void
     */
    public static function trigger(array $args = []): void
    {
        if (!static::should_trigger($args)) {
            return;
        }
        /**
         * Triggers when a milestone is reached in the plugin lifecycle.
         *
         * @since 1.6.0
         *
         * @param array<string, mixed> $args optional additional arguments passed to the milestone callback when recording the milestone
         */
        do_action(self::hook(), $args);
    }
    /**
     * Records the milestone event.
     *
     * @since 1.6.0
     *
     * @param array<string, mixed> $args optional arguments which concrete implementations extending this method can use
     * @return void
     */
    protected function record(array $args = []): void
    {
        if (!static::id()) {
            return;
        }
        Logger::info(sprintf('Milestone "%s" achieved.', static::id()), null, $args);
        Lifecycle::record_milestone($this, $args);
    }
}
