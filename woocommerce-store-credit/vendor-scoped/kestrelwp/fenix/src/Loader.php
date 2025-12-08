<?php

namespace Kestrel\Store_Credit\Scoped\Kestrel\Fenix;

defined('ABSPATH') or exit;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Contracts\WordPress_Plugin;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Lifecycle\Contracts\Uninstaller;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\WooCommerce\Contracts\WooCommerce_Extension;
/**
 * Plugin loader.
 *
 * A concrete plugin main file should extend this class to load its {@see Plugin} or {@see WooCommerce\Extension} instance.
 * At a minimum leve, the main file only needs to define any requirements in constants (or use the defaults) and the main plugin class.
 * Then, launch the plugin via {@see Loader::bootstrap()}.
 *
 * @NOTE The PHP-level of this class should be kept at 5.6 for the time being, to gracefully fail in case the minimum PHP version is not met.
 * @NOTE Plugins implementing this loader will need to have `require_once __DIR__ . '/vendor-scoped/aviary-autoload.php';` before invoking the loader.
 *
 * @phpstan-consistent-constructor
 *
 * @since 1.0.0
 */
abstract class Loader
{
    /** @var string plugin implementations should override this with the plugin name */
    const PLUGIN_NAME = 'Fenix';
    /** @var string plugin implementations should override this with their own main file path */
    const PLUGIN_FILE_PATH = __FILE__;
    /** @var class-string<WordPress_Plugin|WooCommerce_Extension> plugin implementation should override this to point to the concrete plugin class */
    const PLUGIN_MAIN_CLASS = Plugin::class;
    /** @var int the priority order the plugin implementation will be loaded at via `plugins_loaded` (default 10) */
    const PLUGIN_LOAD_PRIORITY = 10;
    /** @var class-string<Uninstaller>|null plugins can optionally define an uninstallation handler here to be invoked upon uninstallation */
    const PLUGIN_UNINSTALLER_CLASS = null;
    /** @var string the minimum PHP version required by the plugin */
    const MINIMUM_PHP_VERSION = '7.4';
    // framework minimum PHP version
    /** @var string the minimum WordPress version required by the plugin */
    const MINIMUM_WP_VERSION = '6.2';
    // framework minimum WP version
    /** @var string the minimum WooCommerce version required by the plugin */
    const MINIMUM_WC_VERSION = '';
    // leave empty in plugins not requiring WooCommerce
    /** @var array<string, string> plugin requirements */
    protected $requirements = [];
    /** @var array<string, mixed> the admin notices to add upon activation failure */
    protected $notices = [];
    /** @var static|null */
    protected static $instance = null;
    /**
     * Initializes the plugin loader.
     *
     * @since 1.0.0
     *
     * @param array<string, mixed> $args the plugin loader arguments (optional: these will be inferred by constants above otherwise)
     *
     * @phpstan-param array{
     *     requirements?: array{
     *          php?: string,
     *          wp?: string,
     *          wc?: string,
     *     }
     * }|mixed $args
     */
    protected function __construct($args = [])
    {
        $this->set_plugin_requirements(is_array($args) && array_key_exists('requirements', $args) ? $args['requirements'] : []);
        add_action('admin_init', [$this, 'check_environment_on_initialization']);
        add_action('admin_notices', [$this, 'render_admin_notices'], 15);
        add_action('plugins_loaded', [$this, 'load_plugin'], (int) static::PLUGIN_LOAD_PRIORITY);
        register_activation_hook($this->get_plugin_file(), [$this, 'plugin_activation']);
        register_uninstall_hook($this->get_plugin_file(), [__CLASS__, 'plugin_uninstall']);
    }
    /**
     * Returns the plugin name.
     *
     * @since 1.0.0
     *
     * @return string
     */
    protected function get_plugin_name()
    {
        return static::PLUGIN_NAME;
    }
    /**
     * Returns the implementing plugin's file path.
     *
     * @since 1.0.0
     *
     * @return string
     */
    protected function get_plugin_file()
    {
        return static::PLUGIN_FILE_PATH;
    }
    /**
     * Returns the plugin environment requirements.
     *
     * @since 1.0.0
     *
     * @return array<string, string>
     *
     * @phpstan-return array{
     *     php: string,
     *     wp: string,
     *     wc: string,
     * }
     */
    protected function get_plugin_requirements()
    {
        return $this->requirements;
    }
    /**
     * Returns a specific plugin requirement.
     *
     * @since 1.0.0
     *
     * @param string $requirement
     * @return string
     */
    protected function get_plugin_requirement($requirement)
    {
        $requirements = $this->get_plugin_requirements();
        if (!isset($requirements[$requirement])) {
            return '';
        }
        return $requirements[$requirement];
    }
    /**
     * Sets the plugin requirement.
     *
     * @since 1.0.0
     *
     * @param array<string, string> $requirements
     *
     * @phpstan-param array{
     *     php?: string,
     *     wp?: string,
     *     wc?: string,
     *  } $requirements
     *
     * @return void
     */
    protected function set_plugin_requirements($requirements = [])
    {
        if (!isset($requirements['php'])) {
            $requirements['php'] = static::MINIMUM_PHP_VERSION;
        }
        if (!isset($requirements['wp'])) {
            $requirements['wp'] = static::MINIMUM_WP_VERSION;
        }
        if (!isset($requirements['wc'])) {
            $requirements['wc'] = static::MINIMUM_WC_VERSION;
        }
        $this->requirements = $requirements;
    }
    /**
     * Initializes the plugin.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function load_plugin()
    {
        if (!$this->is_environment_compatible()) {
            return;
        }
        // this message is mainly directed at developers who may be implementing the loader incorrectly
        if (!class_exists(static::PLUGIN_MAIN_CLASS) || !is_a(static::PLUGIN_MAIN_CLASS, Plugin::class, \true)) {
            _doing_it_wrong(__METHOD__, esc_html(sprintf('%1$s: The main plugin class must be specified and implement %2$s.', $this->get_plugin_name(), Plugin::class)), '');
            return;
        }
        $class = static::PLUGIN_MAIN_CLASS;
        $instance = $class::instance(['file' => $this->get_plugin_file()]);
        Container::set(Plugin::class, $instance);
    }
    /**
     * Checks the environment compatibility and deactivates plugins if incompatible.
     *
     * @since 1.0.0
     *
     * @return void
     */
    protected function check_environment_on_activation()
    {
        if (!$this->is_environment_compatible()) {
            $this->plugin_deactivation();
            $this->add_plugin_notices();
        }
    }
    /**
     * Checks the environment upon loading WordPress, just in case the environment changes after activation.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function check_environment_on_initialization()
    {
        if (!$this->is_environment_compatible() && is_plugin_active(plugin_basename($this->get_plugin_file()))) {
            $this->plugin_deactivation();
            $this->add_plugin_notices();
        }
    }
    /**
     * Checks if the environment is compatible with the plugin to load.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    protected function is_environment_compatible()
    {
        return $this->is_php_compatible() && $this->is_wp_compatible() && $this->is_wc_compatible();
    }
    /**
     * Determines if the PHP version is compatible with the plugin.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    protected function is_php_compatible()
    {
        return empty($this->get_plugin_requirement('php')) || version_compare(\PHP_VERSION, $this->get_plugin_requirement('php'), '>=');
    }
    /**
     * Determines if the WordPress version is compatible with the plugin.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    protected function is_wp_compatible()
    {
        return empty($this->get_plugin_requirement('wp')) || version_compare(get_bloginfo('version'), $this->get_plugin_requirement('wp'), '>=');
    }
    /**
     * Determines if the WooCommerce version is compatible with the plugin.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    protected function is_wc_compatible()
    {
        return empty($this->get_plugin_requirement('wc')) || defined('\WC_VERSION') && version_compare(\WC_VERSION, $this->get_plugin_requirement('wc'), '>=');
    }
    /**
     * Gets the error messages for the incompatible environment.
     *
     * @since 1.0.0
     *
     * @return array<string, string>
     */
    protected function get_environment_error_messages()
    {
        $messages = [];
        if (!$this->is_php_compatible()) {
            $messages['php-incompatible'] = sprintf('The minimum PHP version required for this this plugin is %1$s. You are running %2$s. %3$sPlease contact your hosting provider to upgrade PHP%4$s.', $this->get_plugin_requirement('php'), \PHP_VERSION, '<a href="https://wordpress.org/support/update-php/" target="_blank">', '</a>');
        }
        if (!$this->is_wp_compatible()) {
            $messages['wp-incompatible'] = sprintf('This plugin requires WordPress version %1$s or higher. Your site is running WordPress version %2$s. Please %3$supdate WordPress%4$s to the latest version.', $this->get_plugin_requirement('wp'), get_bloginfo('version'), '<a href="' . esc_url(admin_url('update-core.php')) . '">', '</a>');
        }
        if (!$this->is_wc_compatible()) {
            $messages['wc-incompatible'] = sprintf('This plugin requires WooCommerce version %1$s or higher. Please %2$supdate WooCommerce%3$s to the latest version, or %4$sdownload the minimum required version%5$s.', $this->get_plugin_requirement('wc'), '<a href="' . esc_url(admin_url('update-core.php')) . '">', '</a>', '<a href="' . esc_url('https://downloads.wordpress.org/plugin/woocommerce.' . $this->get_plugin_requirement('wc') . '.zip') . '">', '</a>');
        }
        return $messages;
    }
    /**
     * Adds notices to be output when the environment is incompatible with the plugin.
     *
     * @since 1.0.0
     *
     * @return void
     */
    protected function add_plugin_notices()
    {
        foreach ($this->get_environment_error_messages() as $error_id => $error_message) {
            $this->add_admin_notice($error_id, 'error', sprintf('<strong>%1$s</strong>: %2$s', $this->get_plugin_name(), $error_message));
        }
    }
    /**
     * Adds an admin notice to be displayed.
     *
     * @since 1.0.0
     *
     * @param non-empty-string $notice_id a unique notices identifier
     * @param non-empty-string $notice_class the CSS class for the notice
     * @param non-empty-string $notice_message the notice message
     * @return void
     */
    protected function add_admin_notice($notice_id, $notice_class, $notice_message)
    {
        $this->notices[$notice_id] = ['class' => $notice_class, 'message' => $notice_message];
    }
    /**
     * Displays any admin notices.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function render_admin_notices()
    {
        foreach ($this->notices as $notice_key => $notice) {
            if (!isset($notice['message']) || !isset($notice['class']) || !is_string($notice['message']) || !is_string($notice['class'])) {
                continue;
            }
            ?>
			<div id="<?php 
            echo esc_attr(sprintf('fenix-plugin-activation-notice-%s', $notice_key));
            ?>"  class="<?php 
            echo esc_attr($notice['class']);
            ?>">
				<p><?php 
            echo wp_kses_post($notice['message']);
            ?></p>
			</div>
			<?php 
        }
    }
    /**
     * Handles plugin activation tasks.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function plugin_activation()
    {
        $this->check_environment_on_activation();
    }
    /**
     * Handles plugin deactivation tasks.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function plugin_deactivation()
    {
        deactivate_plugins(plugin_basename($this->get_plugin_file()));
        // phpcs:ignore
        if (isset($_GET['activate'])) {
            unset($_GET['activate']);
        }
    }
    /**
     * Handles plugin uninstallation tasks.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public static function plugin_uninstall()
    {
        if (static::PLUGIN_UNINSTALLER_CLASS && class_exists(static::PLUGIN_UNINSTALLER_CLASS)) {
            if (!is_a(static::PLUGIN_UNINSTALLER_CLASS, Uninstaller::class, \true)) {
                return;
            }
            $uninstall_class = static::PLUGIN_UNINSTALLER_CLASS;
            $uninstall_handler = new $uninstall_class();
            $uninstall_handler->uninstall();
        }
    }
    /**
     * Bootstraps the plugin loader.
     *
     * @since 1.0.0
     *
     * @param array<string, mixed> $args the plugin loader arguments (optional: these will be inferred by constants above otherwise)
     * @return void
     */
    public static function bootstrap($args = [])
    {
        if (!static::$instance instanceof self) {
            /** @phpstan-ignore-next-line */
            static::$instance = new static($args);
        }
    }
}
