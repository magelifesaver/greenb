<?php

declare (strict_types=1);
namespace Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Admin\Metaboxes;

defined('ABSPATH') or exit;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Helpers\Strings;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Admin\Metaboxes;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Traits\Has_Plugin_Instance;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Traits\Has_Accessors;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Traits\Is_Singleton;
/**
 * Object representation of a metabox in WordPress.
 *
 * @since 1.2.0
 *
 * @method string get_id()
 * @method string get_title( string|null $screen = null )
 * @method string get_screen_context( string|null $screen = null )
 * @method string get_screen_priority( string|null $screen = null )
 * @method string[] get_screens()
 * @method string[] get_classes( string|null $screen = null )
 * @method mixed[] get_callback_args( string|null $screen = null )
 * @method $this set_id( string $id )
 * @method $this set_title( string $title )
 * @method $this set_screen_context( string $screen_context )
 * @method $this set_screen_priority( string $screen_priority )
 * @method $this set_screens( string[] $screens )
 * @method $this set_classes( string[] $classes )
 * @method $this set_callback( callable|null $callback )
 * @method $this set_callback_args( array $callback_args )
 */
abstract class Metabox
{
    use Has_Accessors;
    use Has_Plugin_Instance;
    use Is_Singleton;
    /** @var string */
    protected const ID = '';
    /** @var string the metabox ID */
    protected string $id;
    /** @var string the metabox title */
    protected string $title;
    /** @var string the context where the metabox appears in a WordPress screen */
    protected string $screen_context = Metabox_Screen_Context::NORMAL;
    /** @var string the priority of the metabox */
    protected string $screen_priority = Metabox_Screen_Priority::DEFAULT;
    /** @var string[] list of supported screen IDs */
    protected array $screens = [];
    /** @var string[] list of CSS classes (optional) */
    protected array $classes = [];
    /** @var callable|callable-string|null callback to render the screen */
    protected $callback = null;
    /** @var array<string, mixed> arguments to pass to the callback (optional) */
    protected array $callback_args = [];
    /** @var bool whether the metabox in context has been registered */
    protected bool $is_registered = \false;
    /**
     * Metabox constructor.
     *
     * @since 1.2.0
     *
     * @param array<string, mixed> $args the metabox arguments
     */
    protected function __construct(array $args = [])
    {
        $this->id = static::ID;
        $this->set_properties($args);
        $this->to_array_excluded_properties += ['callback', 'callback_args', 'plugin'];
        add_action('admin_enqueue_scripts', fn() => $this->load_assets());
        add_action('save_post', fn($post_id = null, $post = null) => $this->save($post_id, $post), 10, 2);
    }
    /**
     * Determines whether the metabox is registered.
     *
     * @since 1.2.0
     *
     * @param bool|null $set_registered
     * @return bool
     */
    public function is_registered(?bool $set_registered = null): bool
    {
        if (null !== $set_registered) {
            $this->is_registered = $set_registered;
        }
        return $this->is_registered;
    }
    /**
     * Returns the callback to render the metabox.
     *
     * @since 1.2.0
     *
     * @return callable|callable-string|null
     */
    public function get_callback()
    {
        if (null === $this->callback) {
            return fn() => $this->output();
            // default
        } elseif (is_callable($this->callback)) {
            return $this->callback;
            // override
        }
        // @phpstan-ignore-next-line
        return '__return_empty_string';
    }
    /**
     * Returns the nonce name for the current meta box.
     *
     * @since 1.2.0
     *
     * @return string
     */
    protected function get_nonce_name(): string
    {
        return Strings::string($this->get_id())->snake_case()->prepend('_')->append('_nonce')->to_string();
    }
    /**
     * Returns the nonce action for the current meta box.
     *
     * @since 1.2.0
     *
     * @return string
     */
    protected function get_nonce_action(): string
    {
        return Strings::string($this->get_id())->kebab_case()->prepend('update-')->to_string();
    }
    /**
     * Determine if the metabox assets should be loaded.
     *
     * @since 1.2.0
     *
     * @return bool
     */
    protected function should_load_assets(): bool
    {
        return \true;
    }
    /**
     * Enqueues assets for the metabox.
     *
     * @since 1.2.0
     *
     * @return void
     */
    protected function load_assets(): void
    {
        // stub method
    }
    /**
     * Outputs the metabox content.
     *
     * @since 1.2.0
     *
     * @param mixed ...$args callback args (default none)
     * @return void
     */
    public function output(...$args): void
    {
        // phpcs:ignore
        $this->output_nonce_field();
        // stub method
    }
    /**
     * Outputs the nonce field for the metabox.
     *
     * @since 1.2.0
     *
     * @return void
     */
    protected function output_nonce_field(): void
    {
        wp_nonce_field($this->get_nonce_action(), $this->get_nonce_name());
    }
    /**
     * Determines whether the metabox should save its content.
     *
     * @since 1.2.0
     *
     * @param mixed ...$args optional args
     * @return bool
     */
    protected function should_save(...$args): bool
    {
        // phpcs:ignore
        if (!$this->verify_nonce()) {
            return \false;
        }
        if (defined('DOING_AUTOSAVE') && \DOING_AUTOSAVE) {
            return \false;
        }
        return \true;
    }
    /**
     * Verifies the nonce for the metabox.
     *
     * @since 1.2.0
     *
     * @return bool
     */
    protected function verify_nonce(): bool
    {
        // phpcs:ignore
        if (!isset($_POST[$this->get_nonce_name()]) || !wp_verify_nonce($_POST[$this->get_nonce_name()], $this->get_nonce_action())) {
            return \false;
        }
        return \true;
    }
    /**
     * Saves the metabox content when the parent post object is saved.
     *
     * @since 1.2.0
     *
     * @param mixed ...$args normally accepting $post_id and $post
     * @return void
     */
    public function save(...$args): void
    {
        // stub method
    }
    /**
     * Determines whether the metabox is to be registered.
     *
     * @since 1.2.0
     *
     * @param mixed ...$args
     * @return bool
     */
    public function should_register(...$args): bool
    {
        // phpcs:ignore
        return \true;
    }
    /**
     * Registers the metabox.
     *
     * @since 1.2.0
     *
     * @param int|null $priority
     * @return $this
     */
    public static function register(?int $priority = null): Metabox
    {
        $instance = static::instance();
        Metaboxes::register($instance, $priority);
        // @phpstan-ignore-next-line
        return $instance;
    }
    /**
     * Unregisters the metabox.
     *
     * @since 1.2.0
     *
     * @return void
     */
    public static function deregister(): void
    {
        if (!static::is_loaded()) {
            return;
        }
        $instance = static::instance();
        Metaboxes::deregister($instance);
    }
}
