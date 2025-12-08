<?php

declare (strict_types=1);
namespace Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Admin;

defined('ABSPATH') or exit;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Admin\Onboarding\Status;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Traits\Has_Plugin_Instance;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Telemetry\Persona;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Traits\Has_Accessors;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Traits\Is_Singleton;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\WooCommerce\Extension;
/**
 * Onboarding handler.
 *
 * @since 1.0.0
 *
 * @method string get_status()
 * @method string get_step()
 * @method array<string, mixed> get_progress()
 * @method Persona get_persona()
 * @method string get_version()
 * @method string|null get_last_updated()
 * @method self set_status( string $status )
 * @method self set_step( string $step )
 * @method self set_persona( Persona $persona )
 * @method self set_version( string $version )
 * @method self set_last_updated( ?string $last_updated )
 */
final class Onboarding
{
    use Has_Accessors;
    use Has_Plugin_Instance;
    use Is_Singleton;
    /** @var string current onboarding status */
    protected string $status = '';
    /** @var string current onboarding step */
    protected string $step = '';
    /** @var array<string, mixed>|null onboarding progress */
    protected ?array $progress = null;
    /** @var Persona|null onboarded persona */
    protected ?Persona $persona = null;
    /** @var string onboarded version, typically matches the plugin version during the last onboarding */
    protected string $version = '';
    /** @var string|null datetime when the onboarding last updated */
    protected ?string $last_updated = null;
    /**
     * Constructor.
     *
     * @since 1.0.0
     *
     * @param array<string, mixed>|null $initial_data
     */
    protected function __construct(?array $initial_data = null)
    {
        if (!$initial_data) {
            $initial_data = (array) get_option(self::get_option_name(), []);
        }
        if (empty($initial_data['version'])) {
            $initial_data['version'] = self::plugin()->version();
        }
        $this->read($initial_data);
    }
    /**
     * Returns the capability required to manage the onboarding.
     *
     * @return string
     */
    public static function get_capability(): string
    {
        return self::plugin() instanceof Extension ? 'manage_woocommerce' : 'manage_options';
    }
    /**
     * Determines if the current user can handle the onboarding.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    public static function current_user_can_handle_onboarding(): bool
    {
        return current_user_can(self::get_capability());
    }
    /**
     * Sets the onboarding progress.
     *
     * @since 1.0.0
     *
     * @param array<string, mixed>|null $progress
     * @return $this
     */
    public function set_progress(?array $progress = null): Onboarding
    {
        $this->progress = $progress;
        return $this;
    }
    /**
     * Determines if the onboarding is available.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    public static function is_available(): bool
    {
        $has_dashboard = self::plugin()->config()->get('admin.dashboard');
        return is_string($has_dashboard) && is_a($has_dashboard, Dashboard::class, \true);
    }
    /**
     * Determines if the onboarding is in progress.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    public function is_in_progress(): bool
    {
        return Status::IN_PROGRESS === $this->get_status();
    }
    /**
     * Determines if the onboarding is complete.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    public function is_completed(): bool
    {
        return Status::COMPLETED === $this->get_status();
    }
    /**
     * Determines if the onboarding has been skipped or dismissed.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    public function is_dismissed(): bool
    {
        return Status::DISMISSED === $this->get_status();
    }
    /**
     * Reads the onboarding data.
     *
     * @since 1.0.0
     *
     * @param array<string, mixed> $data
     * @return void
     */
    public function read(array $data)
    {
        $data = wp_parse_args($data, ['status' => self::is_available() ? Status::NOT_STARTED : Status::UNAVAILABLE, 'step' => '', 'persona' => Persona::get(), 'progress' => null, 'version' => self::plugin()->version(), 'last_updated' => null]);
        foreach ($data as $key => $value) {
            if (!property_exists($this, $key)) {
                continue;
            }
            if ('persona' === $key) {
                $this->{$key} = $value instanceof Persona ? $value : Persona::seed((array) $value);
            } else {
                $this->{$key} = $value;
            }
        }
    }
    /**
     * Updates the onboarding status:
     *
     * @since 1.0.0
     *
     * @param string $status
     * @return void
     */
    public function update(string $status): void
    {
        switch ($status) {
            case Status::COMPLETED:
                $this->complete();
                return;
            case Status::DISMISSED:
                $this->dismiss();
                return;
            default:
                $this->save();
                break;
        }
        /**
         * Fires after the onboarding is updated.
         *
         * @since 1.0.0
         *
         * @param Onboarding $onboarding
         */
        do_action(self::plugin()->hook('onboarding_updated'), $this);
    }
    /**
     * Completes the onboarding.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function complete(): void
    {
        $this->set_status(Status::COMPLETED);
        $this->save();
        /**
         * Fires after the onboarding is completed.
         *
         * @since 1.0.0
         *
         * @param Onboarding $onboarding
         */
        do_action(self::plugin()->hook('onboarding_complete'), $this);
    }
    /**
     * Dismisses the onboarding.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function dismiss(): void
    {
        $this->set_status(Status::DISMISSED);
        $this->save();
        /**
         * Fires after the onboarding is dismissed.
         *
         * @since 1.0.0
         *
         * @param Onboarding $onboarding
         */
        do_action(self::plugin()->hook('onboarding_dismissed'), $this);
    }
    /**
     * Resets the onboarding data.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function reset(): void
    {
        delete_option(self::get_option_name());
        $this->read([]);
    }
    /**
     * Saves the onboarding data.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function save(): void
    {
        $this->set_last_updated(current_time('c', \true));
        $this->set_version(self::plugin()->version());
        $this->get_persona()->save();
        $data = $this->to_array();
        unset($data['persona']);
        update_option(self::get_option_name(), $data);
    }
    /**
     * Gets the URL to the onboarding page.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public static function get_url(): string
    {
        return add_query_arg('onboarding', self::plugin()->id_dasherized(), self::plugin()->dashboard_url());
    }
    /**
     * Returns the name of the option used to store the onboarding data.
     *
     * @since 1.0.0
     *
     * @return string
     */
    private static function get_option_name(): string
    {
        return self::plugin()->key('onboarding');
    }
}
