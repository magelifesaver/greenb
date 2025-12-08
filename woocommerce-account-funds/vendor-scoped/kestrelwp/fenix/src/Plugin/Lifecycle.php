<?php

declare (strict_types=1);
namespace Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin;

use DateTime;
use Exception;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Logger;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Admin\Notices\Call_To_Action;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Admin\Notices\Notice;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Contracts\WordPress_Plugin;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Lifecycle\Contracts\Background_Migration;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Lifecycle\Contracts\Installer;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Lifecycle\Contracts\Migration;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Lifecycle\Contracts\Milestone;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Traits\Is_Handler;
defined('ABSPATH') or exit;
/**
 * Plugin lifecycle handler.
 *
 * @since 1.0.0
 */
final class Lifecycle
{
    use Is_Handler;
    /** @var string unprefixed option key to store the plugin active flag */
    private const ACTIVATION_OPTION = 'is_active';
    /** @var string unprefixed option key to store the installed plugin version */
    private const VERSION_OPTION = 'version';
    /** @var string unprefixed option key to store the timestamp when the plugin was installed */
    private const INSTALLED_AT_OPTION = 'installed_at';
    /** @var string unprefixed option key to store the installed plugin version */
    private const UPDATED_AT_OPTION = 'updated_at';
    /** @var string unprefixed option key to store the plugin update history */
    private const UPDATE_HISTORY_OPTION = 'update_history';
    /** @var string unprefixed option key to store the plugin milestones history */
    private const MILESTONES_HISTORY_OPTION = 'milestones';
    /** @var string stores the class names of migrations containing a background process that needs to run */
    private const BACKGROUND_MIGRATIONS_OPTION = 'background_migrations';
    /** @var class-string<Installer>|null optional installer class */
    private static ?string $installer = null;
    /** @var string|null the currently installed plugin version */
    private static ?string $installed_version = null;
    /** @var class-string<Milestone>[] list of plugin milestones */
    private static array $milestones = [];
    /** @var array<string, mixed>|null milestone history */
    private static ?array $milestone_history = null;
    /** @var array<string, class-string<Migration>> list of migration classes sorted by semver */
    private static array $migrations = [];
    /** @var array<string, class-string<Background_Migration>>|null list of migrations that have a background job to run */
    private static ?array $background_migrations = null;
    /** @var array<int, array<string, mixed>>|null update history */
    private static ?array $update_history = null;
    /**
     * Initializes the plugin lifecycle handler instance.
     *
     * @since 1.0.0
     *
     * @param Plugin $plugin the plugin instance
     */
    private function __construct(WordPress_Plugin $plugin)
    {
        self::$plugin = $plugin;
        self::$installer = $plugin->config()->get('lifecycle.installer');
        self::$migrations = $plugin->config()->get('lifecycle.migrations', []);
        self::$milestones = $plugin->config()->get('lifecycle.milestones', []);
        self::add_action('admin_init', [$this, 'handle_activation']);
        self::add_action('deactivate_' . $plugin->relative_file_path(), [$this, 'handle_deactivation']);
        if (is_admin() && !wp_doing_ajax() && !wp_doing_cron()) {
            self::add_action('wp_loaded', [$this, 'install_or_upgrade']);
        }
        self::add_action('wp_loaded', [$this, 'run_background_migrations'], 20);
        // `init` lets achieve milestones also while on the frontend, notices will be displayed in the admin area only
        self::add_action('init', [$this, 'handle_milestones']);
    }
    /**
     * Checks if the plugin is active or not.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    public static function is_plugin_active(): bool
    {
        return 'yes' === get_option(self::plugin()->key(self::ACTIVATION_OPTION));
    }
    /**
     * Sets the plugin as active or inactive.
     *
     * @since 1.0.0
     *
     * @param bool $active
     * @return void
     */
    private function set_plugin_active(bool $active): void
    {
        update_option(self::plugin()->key(self::ACTIVATION_OPTION), $active ? 'yes' : 'no');
    }
    /**
     * Gets the currently installed plugin version.
     *
     * @since 1.0.0
     *
     * @return string|null
     */
    private function get_installed_version(): ?string
    {
        if (null !== self::$installed_version) {
            return self::$installed_version;
        }
        $version = get_option(self::plugin()->key(self::VERSION_OPTION), '');
        /**
         * Filters the installed plugin version.
         *
         * This filter is mainly in place for debugging purposes and ideally shouldn't be applied in production.
         *
         * @since 1.0.0
         *
         * @param string|null $version the installed plugin version
         */
        self::$installed_version = apply_filters(self::plugin()->hook('version'), is_string($version) && '' !== $version ? $version : null);
        return self::$installed_version;
    }
    /**
     * Sets the installed plugin version.
     *
     * @since 1.0.0
     *
     * @param string $version version to set
     */
    private function set_installed_version(string $version): void
    {
        self::$installed_version = $version;
        update_option(self::plugin()->key(self::VERSION_OPTION), $version);
    }
    /**
     * Gets a registered migration instance.
     *
     * @since 1.0.0
     *
     * @param string $version
     * @return Migration|null
     */
    private static function get_migration(string $version): ?Migration
    {
        $migration = self::$migrations[$version] ?? null;
        // @phpstan-ignore-next-line sanity checks
        if (!is_string($migration) || !class_exists($migration) || !is_a($migration, Migration::class, \true)) {
            return null;
        }
        return new $migration(self::plugin());
    }
    /**
     * Returns the list of background migrations that are registered to run.
     *
     * @since 1.6.0
     *
     * @return array<string, class-string<Background_Migration>>
     */
    private static function get_background_migrations(): array
    {
        if (null !== self::$background_migrations) {
            return self::$background_migrations;
        }
        self::$background_migrations = (array) get_option(self::plugin()->key(self::BACKGROUND_MIGRATIONS_OPTION), []);
        return self::$background_migrations;
    }
    /**
     * Performs the plugin installation or upgrade routines.
     *
     * @since 1.0.0
     *
     * @return void
     */
    private function install_or_upgrade(): void
    {
        // run activation tasks if the plugin is not active yet
        $this->handle_activation();
        $installed_version = $this->get_installed_version();
        $current_version = self::plugin()->version();
        if (!$installed_version) {
            $this->install();
            $this->set_installed_version($current_version);
            self::set_installed_at();
            /**
             * Fires after the plugin has been installed.
             *
             * @since 1.0.0
             */
            do_action(self::plugin()->hook('installed'));
        } elseif (version_compare($installed_version, $current_version, '<')) {
            $this->update($installed_version);
            $this->set_installed_version($current_version);
            self::record_update($installed_version, $current_version);
            self::set_updated_at();
            /**
             * Fires after the plugin has been updated.
             *
             * @since 1.0.0
             */
            do_action(self::plugin()->hook('updated'));
        }
    }
    /**
     * Runs any background migrations that are registered.
     *
     * @since 1.6.0
     *
     * @return void
     */
    private function run_background_migrations(): void
    {
        $background_migrations = self::get_background_migrations();
        if (empty($background_migrations)) {
            return;
        }
        foreach ($background_migrations as $version => $migration) {
            $migration = self::get_migration($version);
            if (!$migration instanceof Background_Migration || $migration->is_done()) {
                unset(self::$background_migrations[$version]);
            }
        }
        if (empty(self::$background_migrations)) {
            delete_option(self::plugin()->key(self::BACKGROUND_MIGRATIONS_OPTION));
        } else {
            update_option(self::plugin()->key(self::BACKGROUND_MIGRATIONS_OPTION), self::$background_migrations, \false);
        }
    }
    /**
     * Gets the datetime for a given option key.
     *
     * @since 1.1.0
     *
     * @param string $option_key
     * @return DateTime|null
     */
    private static function get_datetime(string $option_key): ?DateTime
    {
        $option_value = get_option(self::plugin()->key($option_key), '');
        if (!is_string($option_value) || '' === $option_value) {
            return null;
        }
        try {
            $datetime = new DateTime($option_value);
        } catch (Exception $exception) {
            return null;
        }
        return $datetime;
    }
    /**
     * Sets the datetime for a given option key.
     *
     * @since 1.1.0
     *
     * @param string $option_key
     * @param DateTime|null $datetime
     * @return void
     */
    private static function set_datetime(string $option_key, ?DateTime $datetime = null): void
    {
        if (!$datetime) {
            $datetime = new DateTime();
        }
        update_option(self::plugin()->key($option_key), $datetime->format('c'), \false);
    }
    /**
     * Gets the plugin installed date.
     *
     * @since 1.1.0
     *
     * @return DateTime|null
     */
    public static function get_installed_at(): ?DateTime
    {
        return self::get_datetime(self::INSTALLED_AT_OPTION);
    }
    /**
     * Sets the plugin installed date.
     *
     * @since 1.1.0
     *
     * @param DateTime|null $installed_at datetime
     * @return void
     */
    private static function set_installed_at(?DateTime $installed_at = null): void
    {
        self::set_datetime(self::INSTALLED_AT_OPTION, $installed_at);
    }
    /**
     * Gets the plugin updated date.
     *
     * @since 1.1.0
     *
     * @return DateTime|null
     */
    public static function get_updated_at(): ?DateTime
    {
        return self::get_datetime(self::UPDATED_AT_OPTION);
    }
    /**
     * Sets the plugin updated date.
     *
     * @since 1.1.0
     *
     * @param DateTime|null $updated_at datetime
     * @return void
     */
    private static function set_updated_at(?DateTime $updated_at = null): void
    {
        self::set_datetime(self::UPDATED_AT_OPTION, $updated_at);
    }
    /**
     * Gets the plugin update history.
     *
     * @since 1.1.0
     *
     * @return array<int, array<string, string>>
     *
     * @phpstan-return array<int, array{
     *     from: string,
     *     to: string,
     *     date: string
     * }>
     */
    public static function get_update_history(): array
    {
        if (is_array(self::$update_history)) {
            return self::$update_history;
        }
        $history = get_option(self::plugin()->key(self::UPDATE_HISTORY_OPTION), []);
        if (!is_array($history)) {
            $history = [];
        }
        self::$update_history = $history;
        return self::$update_history;
    }
    /**
     * Records a plugin update in the update history.
     *
     * Whenever the plugin is updated to a new version, we can store a record in the update history option.
     *
     * @NOTE In the future we could add additional information to the record or consider to let individual plugins to append additional data through an {@see Installer} method.
     *
     * @see Lifecycle::get_update_history()
     *
     * @since 1.1.0
     *
     * @param string $installed_version
     * @param string $current_version
     * @return void
     */
    public static function record_update(string $installed_version, string $current_version): void
    {
        $history = self::get_update_history();
        $history[time()] = ['from' => $installed_version, 'to' => $current_version, 'date' => gmdate('c')];
        update_option(self::plugin()->key(self::UPDATE_HISTORY_OPTION), $history, \false);
        self::$update_history = $history;
    }
    /**
     * Returns the plugin milestones history.
     *
     * @since 1.6.0
     *
     * @return array<string, array<string, mixed>> array of milestones history records, keyed by milestone ID
     */
    public static function get_milestone_history(): array
    {
        if (is_array(self::$milestone_history)) {
            return self::$milestone_history;
        }
        $milestones = get_option(self::plugin()->key(self::MILESTONES_HISTORY_OPTION), []);
        if (!is_array($milestones)) {
            $milestones = [];
        }
        self::$milestone_history = $milestones;
        return self::$milestone_history;
    }
    /**
     * Records a plugin milestone in the milestones history.
     *
     * @since 1.6.0
     *
     * @NOTE If passing additional data, try to keep this to a minimum and only include data that is relevant to the milestone.
     *
     * @param Milestone $milestone the milestone to record
     * @param array<string, mixed> $additional_data optional additional data to store with the milestone record
     * @return void
     */
    public static function record_milestone(Milestone $milestone, array $additional_data = []): void
    {
        if (!$milestone::id()) {
            _doing_it_wrong(__METHOD__, 'Cannot record a milestone without a valid unique ID.', '');
            return;
        }
        $milestones = self::get_milestone_history();
        $milestones[$milestone::id()] = array_merge(['achieved' => 'yes', 'achieved_at' => gmdate('c')], $additional_data);
        update_option(self::plugin()->key(self::MILESTONES_HISTORY_OPTION), $milestones);
    }
    /**
     * Triggers plugin activation.
     *
     * We don't use {@see register_activation_hook()} as that can't be called inside the 'plugins_loaded' action. Instead, we rely on setting to track the plugin's activation status.
     *
     * @link https://developer.wordpress.org/reference/functions/register_activation_hook/#comment-2100
     *
     * @since 1.0.0
     *
     * @return void
     */
    private function handle_activation(): void
    {
        if (!self::is_plugin_active()) {
            $this->activate();
            $this->set_plugin_active(\true);
            Logger::info('Plugin activated.');
            $this->dispatch_activation_notice();
            /**
             * Fires when the plugin is activated.
             *
             * @since 1.0.0
             */
            do_action(self::plugin()->hook('activated'));
        }
    }
    /**
     * Dispatches an activation notice.
     *
     * @since 1.2.0
     *
     * @return void
     */
    private function dispatch_activation_notice(): void
    {
        $ctas = [];
        $plugin = self::plugin();
        $settings_url = $plugin->settings_url();
        $dashboard_url = $plugin->dashboard_url();
        $documentation_url = $plugin->documentation_url();
        if ($dashboard_url && $dashboard_url !== $settings_url) {
            $ctas[] = Call_To_Action::create(['label' => __('Visit dashboard', $plugin->textdomain()), 'url' => $dashboard_url]);
        }
        if ($settings_url) {
            $ctas[] = Call_To_Action::create(['label' => __('Configure settings', $plugin->textdomain()), 'url' => $settings_url]);
        }
        if ($documentation_url) {
            $ctas[] = Call_To_Action::create(['label' => __('Read documentation', $plugin->textdomain()), 'url' => $documentation_url, 'target' => '_blank']);
        }
        $activation_notice = Notice::success(__('The plugin has been activated.', $plugin->textdomain()))->set_capability('manage_options')->set_dismissible(\true)->set_call_to_actions($ctas)->set_display_condition(fn() => self::is_plugin_active());
        /**
         * Filters the plugin's activation notice.
         *
         * @since 1.2.0
         *
         * @param Plugin $plugin the plugin instance
         * @param Notice|null $activation_notice the activation notice
         */
        $activation_notice = apply_filters($plugin->hook('activation_notice'), $activation_notice, $plugin);
        if (!$activation_notice instanceof Notice || !$activation_notice->has_call_to_actions()) {
            return;
        }
        $activation_notice->dispatch();
    }
    /**
     * Triggers plugin deactivation.
     *
     * @since 1.0.0
     *
     * @return void
     */
    private function handle_deactivation(): void
    {
        $this->deactivate();
        $this->set_plugin_active(\false);
        Logger::warning('The plugin has been deactivated.');
        /**
         * Fires when the plugin is deactivated.
         *
         * @since 1.0.0
         */
        do_action(self::plugin()->hook('deactivated'));
    }
    /**
     * Gets the installer instance, if available.
     *
     * @since 1.0.0
     *
     * @return Installer|null
     */
    private function get_installer(): ?Installer
    {
        $installer = self::$installer;
        if (!$installer) {
            return null;
        }
        // @phpstan-ignore-next-line sanity checks
        if (!is_string($installer) || !class_exists($installer) || !is_a($installer, Installer::class, \true)) {
            _doing_it_wrong(__METHOD__, 'The lifecycle routines must be defined in a valid class that implements ' . Installer::class, '');
            return null;
        }
        if (method_exists($installer, 'instance')) {
            return $installer::instance(self::plugin());
        }
        return new $installer();
    }
    /**
     * Performs any plugin installation tasks.
     *
     * Plugins implementing their own lifecycle handler can override this method to provide their own installation tasks.
     * For uninstall tasks, {@see Loader::plugin_uninstall()} since we are past `plugins_loaded` in the context of this class.
     *
     * @since 1.0.0
     *
     * @return void
     */
    private function install(): void
    {
        $installer = $this->get_installer();
        if ($installer) {
            $installer->install();
        }
        Logger::info('Plugin installed.');
    }
    /**
     * Performs upgrade routines when a new plugin version is installed.
     *
     * Plugins implementing their own lifecycle handler can override this method to provide their own upgrade routines.
     * However, they could also provide an array of {@see Lifecycle::$migrations} which this method will run through.
     *
     * @since 1.0.0
     *
     * @param string $installed_version
     * @return void
     */
    private function update(string $installed_version): void
    {
        if (!empty(self::$migrations)) {
            self::$background_migrations = self::get_background_migrations();
            // ensures migrations are sorted by semver
            uksort(self::$migrations, 'version_compare');
            foreach (self::$migrations as $upgrade_version => $migration) {
                // this will initialize the migration class before comparing versions, so any background process can be completed
                $migration = self::get_migration($upgrade_version);
                if (!$migration || version_compare($installed_version, $upgrade_version, '>=')) {
                    continue;
                }
                $migration->upgrade();
                /**
                 * Fires after a plugin migration upgrade has been completed.
                 *
                 * @since 1.0.0
                 *
                 * @param string $upgrade_version version the plugin upgraded to (migration version)
                 */
                do_action(self::plugin()->hook('upgraded'), $upgrade_version);
                if ($migration instanceof Background_Migration) {
                    self::$background_migrations[$upgrade_version] = get_class($migration);
                }
                Logger::info(sprintf('Plugin migrated to version %s.', $upgrade_version));
            }
        }
        if (!empty(self::$background_migrations)) {
            update_option(self::plugin()->key(self::BACKGROUND_MIGRATIONS_OPTION), self::$background_migrations, \false);
        }
        Logger::info(sprintf('Plugin updated to version %s.', self::plugin()->version()));
    }
    /**
     * Performs any plugin activation tasks.
     *
     * Plugins implementing their own lifecycle handler can override this method to provide their own activation tasks.
     *
     * @NOTE Operations here should never be destructive for existing data. Since we rely on an option to track activation, it's possible for this to run outside genuine activations.
     *
     * @since 1.0.0
     *
     * @return void
     */
    private function activate(): void
    {
        $installer = $this->get_installer();
        if ($installer) {
            $installer->activate();
        }
    }
    /**
     * Performs any plugin deactivation tasks.
     *
     * Plugins implementing their own lifecycle handler can override this method to provide their own deactivation tasks.
     *
     * @since 1.0.0
     *
     * @return void
     */
    private function deactivate(): void
    {
        $installer = $this->get_installer();
        if ($installer) {
            $installer->deactivate();
        }
    }
    /**
     * Registers the plugin milestones.
     *
     * @since 1.6.0
     *
     * @return void
     */
    private function handle_milestones(): void
    {
        foreach (self::$milestones as $index => $milestone) {
            // @phpstan-ignore-next-line sanity checks
            if (!is_string($milestone)) {
                _doing_it_wrong(__METHOD__, sprintf('The milestone #%s must be a valid class name.', $index), '');
                // phpcs:ignore
                continue;
            }
            // @phpstan-ignore-next-line sanity checks
            if (!is_a($milestone, Milestone::class, \true)) {
                _doing_it_wrong(__METHOD__, sprintf('The milestone "%s" must implement the %s interface.', $milestone, Milestone::class), '');
                // phpcs:ignore
                continue;
            }
            $milestone_id = $milestone::id();
            if (empty($milestone_id)) {
                _doing_it_wrong(__METHOD__, sprintf('The milestone "%s" must have a valid ID.', $milestone), '');
                // phpcs:ignore
                continue;
            }
            $milestone = $milestone::initialize(self::plugin());
            if (!$milestone::is_achieved()) {
                continue;
            }
            if ($notice = $milestone->notice()) {
                // hardening: milestone notices should always be dismissible otherwise they will always be displayed
                $notice->set_dismissible(\true);
                $notice->dispatch();
            }
        }
    }
}
