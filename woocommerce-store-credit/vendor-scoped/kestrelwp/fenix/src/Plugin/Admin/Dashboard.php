<?php

declare (strict_types=1);
namespace Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Admin;

defined('ABSPATH') or exit;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Model\User;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Admin;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Admin\Notices\Notice;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Assets;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Contracts\WordPress_Plugin;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Traits\Is_Handler;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\WooCommerce\Extension;
/**
 * Dashboard component.
 *
 * This component is responsible for handling the plugin's vendor-specific dashboard, when provided.
 * Plugins interested in offering a JavaScript/REACT-based dashboard should extend this class and implement any stub methods.
 *
 * @TODO consider having part of this class interact/use the {@see Screens} component or have it also include a {@see Screen} item for consistency.
 *
 * @since 1.0.0
 */
class Dashboard
{
    use Is_Handler;
    /** @var bool whether a welcome screen should be output */
    protected static bool $should_welcome = \false;
    /**
     * Constructor.
     *
     * @since 1.0.0
     *
     * @param WordPress_Plugin $plugin
     */
    protected function __construct(WordPress_Plugin $plugin)
    {
        static::$plugin = $plugin;
        $this->load_dashboard();
    }
    /**
     * Loads the dashboard components.
     *
     * @since 1.0.0
     *
     * @return void
     */
    protected function load_dashboard(): void
    {
        if (!static::current_user_can_manage_dashboard()) {
            return;
        }
        // adds a "Setup" action link to the plugin row on the plugins page
        static::add_filter(static::plugin()->hook('plugins_page_row_links'), [$this, 'update_plugins_page_row_links']);
        // page and onboarding output
        static::add_action('admin_menu', [$this, 'add_admin_page'], \PHP_INT_MAX);
        static::add_action('admin_footer', [$this, 'output_container']);
        static::add_action(static::plugin()->hook('installed'), [$this, 'output_container']);
        static::add_action('admin_notices', [$this, 'output_notices']);
        static::add_action('admin_enqueue_scripts', [$this, 'load_assets']);
        /** @see Onboarding::complete() */
        static::add_action(static::plugin()->hook('onboarding_complete'), [$this, 'onboarding_complete']);
        /** @see Onboarding::dismiss() */
        static::add_action(static::plugin()->hook('onboarding_dismissed'), [$this, 'onboarding_dismissed']);
        /** @see Onboarding::update() */
        static::add_action(static::plugin()->hook('onboarding_updated'), [$this, 'onboarding_updated']);
    }
    /**
     * Determines whether the user can manage the dashboard.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    protected static function current_user_can_manage_dashboard(): bool
    {
        return current_user_can(static::get_capability());
    }
    /**
     * Gets the user capability required to manage the dashboard.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public static function get_capability(): string
    {
        // plugins extending this class can override this method to provide an alternative user capability required to manage the dashboard
        return static::plugin() instanceof Extension ? 'manage_woocommerce' : 'manage_options';
    }
    /**
     * Maybe overrides the "Configure" action link with a "Setup" action link to redirect to the onboarding wizard.
     *
     * @see Admin::add_plugins_page_row_links()
     *
     * @since 1.0.0
     *
     * @param array<string, mixed>|mixed $action_links
     * @return array<string, mixed>|mixed
     */
    private function update_plugins_page_row_links($action_links)
    {
        if (!is_array($action_links) || !static::current_user_can_manage_dashboard()) {
            return $action_links;
        }
        $dashboard_link = ['dashboard' => sprintf('<a href="%s">%s</a>', esc_url(static::plugin()->dashboard_url()), esc_html__('Dashboard', static::plugin()->textdomain()))];
        return array_merge($dashboard_link, $action_links);
    }
    /**
     * Adds the admin page for the dashboard.
     *
     * @since 1.0.0
     *
     * @return void
     */
    protected function add_admin_page(): void
    {
        // stub method: plugins extending this class should override this method to add their admin page
    }
    /**
     * Determines whether the current screen is for the dashboard page.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    protected function is_dashboard_screen(): bool
    {
        // stub method: plugins extending this class should override this method to determine if the current page is the dashboard page
        return \false;
    }
    /**
     * Determines whether the current screen is for the welcome screen.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    protected function is_welcome_screen(): bool
    {
        return static::$should_welcome;
    }
    /**
     * Outputs the appropriate container for the dashboard page or the welcome screen.
     *
     * @since 1.0.0
     *
     * @return void
     */
    protected function output_container(): void
    {
        $container_id = $container_class = null;
        $current_action = current_action();
        if ($current_action === static::plugin()->hook('installed')) {
            $container_id = static::plugin()->handle('welcome');
            $container_class = 'plugin-welcome';
            static::$should_welcome = \true;
            // set the welcome screen flag
        } elseif ($current_action === 'admin_footer' && $this->is_dashboard_screen()) {
            $container_id = static::plugin()->handle('dashboard');
            $container_class = 'plugin-dashboard';
        }
        if ($container_id && $container_class) {
            echo '<div id="' . esc_attr($container_id) . '" class="' . esc_attr($container_class) . '" data-plugin-id="' . esc_attr(static::plugin()->id()) . '"></div>';
            // phpcs:ignore
        }
    }
    /**
     * Outputs notices concerning the dashboard components, such as onboarding in progress.
     *
     * @since 1.0.0
     *
     * @return void
     */
    protected function output_notices(): void
    {
        if ($this->is_dashboard_screen() || !Onboarding::instance()->is_in_progress() || !Onboarding::current_user_can_handle_onboarding()) {
            return;
        }
        /* translators: Placeholders: %1$s - Opening anchor tag, %2$s - Closing anchor tag */
        Notice::info(sprintf(__('You have not completed setup. %1$sClick here to resume%2$s.', static::plugin()->textdomain()), '<a href="' . esc_url(static::plugin()->dashboard_url()) . '" target="_self">', '</a>'))->set_id('onboarding_incomplete')->set_dismissible(\true)->dispatch();
    }
    /**
     * Enqueues the plugin admin assets.
     *
     * @since 1.0.0
     *
     * @return void
     */
    private function load_assets(): void
    {
        if ($this->is_welcome_screen()) {
            $this->load_welcome_assets();
        }
        if ($this->is_dashboard_screen()) {
            $this->load_dashboard_assets();
        }
    }
    /**
     * Loads assets for welcoming the user.
     *
     * @since 1.0.0
     *
     * @return void
     */
    protected function load_welcome_assets(): void
    {
        $welcome_handle = static::plugin()->handle('welcome');
        $welcome_version = Assets::get_asset_version('js/admin/welcome.asset.php');
        $script_dependencies = Assets::get_asset_dependencies('js/admin/welcome.asset.php');
        /**
         * Filters the welcome style URL.
         *
         * @since 1.0.0
         *
         * @param string $style_url
         */
        $style_url = (string) apply_filters(static::plugin()->hook('welcome_style_url'), static::plugin()->assets_url('css/admin/welcome.css'));
        /**
         * Filters the welcome script URL.
         *
         * @since 1.0.0
         *
         * @param string $script_url
         */
        $script_url = (string) apply_filters(static::plugin()->hook('welcome_script_url'), static::plugin()->assets_url('js/admin/welcome.js'));
        wp_enqueue_style($welcome_handle, $style_url, [], $welcome_version);
        wp_enqueue_script($welcome_handle, $script_url, $script_dependencies, $welcome_version, \true);
        wp_localize_script($welcome_handle, static::plugin()->key('welcome'), $this->get_welcome_script_data());
        wp_set_script_translations($welcome_handle, static::plugin()->textdomain(), static::plugin()->translations_path());
    }
    /**
     * Gets the scripts shared data.
     *
     * @since 1.0.0
     *
     * @return array<string, mixed>
     */
    protected function get_scripts_shared_data(): array
    {
        $current_user = User::current();
        $onboarding = Onboarding::instance();
        // plugin implementations extending this class can provide additional data by overriding this method and merging the parent data with their own
        $data = ['nonces' => ['rest_api' => static::current_user_can_manage_dashboard() ? wp_create_nonce('wp_rest') : ''], 'plugin' => static::plugin()->to_array(), 'user' => $current_user ? $current_user->to_array() : null];
        // unsetting onboarding data if it's completed also allows the frontend to determine whether to display the onboarding screen, as well as restarting it if needed
        if (!$onboarding->is_completed()) {
            $data['onboarding'] = $onboarding->to_array();
        }
        return $data;
    }
    /**
     * Returns the welcome data.
     *
     * @see Dashboard::get_welcome_script_data()
     *
     * @since 1.0.0
     *
     * @return array<string, mixed>
     */
    protected function get_welcome_script_data(): array
    {
        // plugin implementations extending this class can provide additional data by overriding this method and merging the parent data with their own
        $data = $this->get_scripts_shared_data();
        // onboarding is not part of the welcome step -- the frontend will have access to the dashboard page URL and endpoint where to resume the onboarding
        unset($data['onboarding']);
        return $data;
    }
    /**
     * Loads assets for onboarding the user and the dashboard.
     *
     * @since 1.0.0
     *
     * @return void
     */
    protected function load_dashboard_assets(): void
    {
        $dashboard_handle = static::plugin()->handle('dashboard');
        $onboarding_version = Assets::get_asset_version('js/admin/dashboard.asset.php');
        $script_dependencies = Assets::get_asset_dependencies('js/admin/dashboard.asset.php');
        /**
         * Filters the dashboard style URL.
         *
         * @since 1.0.0
         *
         * @param string $style_url
         */
        $style_url = (string) apply_filters(static::plugin()->hook('dashboard_style_url'), static::plugin()->assets_url('css/admin/dashboard.css'));
        /**
         * Filters the dashboard script URL.
         *
         * @since 1.0.0
         *
         * @param string $script_url
         */
        $script_url = (string) apply_filters(static::plugin()->hook('dashboard_script_url'), static::plugin()->assets_url('js/admin/dashboard.js'));
        wp_enqueue_style($dashboard_handle, $style_url, [], $onboarding_version);
        wp_enqueue_script($dashboard_handle, $script_url, $script_dependencies, $onboarding_version, \true);
        wp_localize_script($dashboard_handle, static::plugin()->key('dashboard'), $this->get_dashboard_script_data());
        wp_set_script_translations($dashboard_handle, static::plugin()->textdomain(), static::plugin()->translations_path());
    }
    /**
     * Returns the dashboard data.
     *
     * @see Dashboard::get_dashboard_script_data()
     *
     * @since 1.0.0
     *
     * @return array<string, mixed>
     */
    protected function get_dashboard_script_data(): array
    {
        // plugin implementations extending this class can provide additional data by overriding this method and merging the parent data with their own
        return $this->get_scripts_shared_data();
    }
    /**
     * Performs tasks when the onboarding is complete.
     *
     * @see Onboarding::complete()
     *
     * @since 1.0.0
     *
     * @param Onboarding $onboarding
     * @return void
     */
    protected function onboarding_complete(Onboarding $onboarding): void
    {
        // plugins extending this class which feature an onboarding can override this method to execute routines upon completion
    }
    /**
     * Performs tasks when the onboarding is dismissed.
     *
     * @see Onboarding::dismiss()
     *
     * @since 1.0.0
     *
     * @param Onboarding $onboarding
     * @return void
     */
    protected function onboarding_dismissed(Onboarding $onboarding): void
    {
        // plugins extending this class which feature an onboarding can override this method to execute routines upon dismissal
    }
    /**
     * Performs tasks when the onboarding is updated.
     *
     * @see Onboarding::update()
     *
     * @since 1.0.0
     *
     * @param Onboarding $onboarding
     * @return void
     */
    protected function onboarding_updated(Onboarding $onboarding): void
    {
        // plugins extending this class which feature an onboarding can override this method to execute routines upon update
    }
}
