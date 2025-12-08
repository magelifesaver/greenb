<?php

declare (strict_types=1);
namespace Kestrel\Store_Credit\Scoped\Kestrel\Fenix;

defined('ABSPATH') or exit;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Helpers\Arrays;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Helpers\Strings;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Logger\Contracts\Logger as Logger_Interface;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Admin;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Admin\Dashboard;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Admin\Notices;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Assets;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Blocks;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Contracts\WordPress_Plugin;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Integrations;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Integrations\Contracts\Integration;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Lifecycle;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Lifecycle\Contracts\Migration;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Lifecycle\Installer;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Requirements;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Requirements\Requirement;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\REST_API;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Settings\Settings_Registry;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Traits\Has_Hidden_Callbacks;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Traits\Is_Singleton;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\WooCommerce\Extension;
use WC_Logger_Interface;
/**
 * WordPress plugin.
 *
 * WordPress plugins should extend this class as their main file.
 * For WooCommerce extensions {@see Extension}.
 *
 * @sinc 0.1.0
 */
abstract class Plugin implements WordPress_Plugin
{
    use Has_Hidden_Callbacks;
    use Is_Singleton;
    /** @var string the plugin identifier (should be snake-case) */
    protected const ID = '';
    /** @var string the plugin version number */
    protected const VERSION = '';
    /** @var string the plugin vendor */
    protected const VENDOR = 'Kestrel';
    /** @var string the plugin text domain */
    protected const TEXT_DOMAIN = '';
    /** @var string URL pointing to the plugin's documentation */
    protected const DOCUMENTATION_URL = '';
    /** @var string URL pointing to the plugin's support */
    protected const SUPPORT_URL = '';
    /** @var string URL pointing to the plugin's sales page */
    protected const SALES_PAGE_URL = '';
    /** @var string URL pointing to the plugin's reviews */
    protected const REVIEWS_URL = '';
    /** @var string the default language for the plugin */
    protected const DEFAULT_LANGUAGE = 'en_US';
    /** @var bool|null whether the plugin is multilingual */
    protected ?bool $is_multilingual = null;
    /** @var array<string, mixed> additional plugin configuration data in dot notation */
    private array $config = [];
    /**
     * WordPress plugin constructor.
     *
     * A concrete plugin constructor normally would not take any args but instead pass arguments to this parent constructor.
     *
     * @since 1.0.0
     *
     * @param array<string, mixed> $config plugin configuration
     *
     * @phpstan-param array{
     *     admin?: array{
     *          handler?: class-string<Admin>,
     *          dashboard?: class-string<Dashboard>,
     *     },
     *     blocks?: array{
     *         handler?: class-string<Blocks>,
     *     },
     *     file: string,
     *     integrations?: array<class-string<Integration>>,
     *     lifecycle?: array{
     *          installer?: class-string<Installer>,
     *          migrations?: array<string, class-string<Migration>>
     *     },
     *     logger?: class-string<Logger_Interface>|class-string<WC_Logger_Interface>,
     *     requirements?: array<class-string<Requirement>, array<string, mixed>>,
     *     rest_api?: array{
     *          handler?: class-string<REST_API>,
     *     },
     * } $config
     */
    protected function __construct(array $config)
    {
        $this->config = Arrays::array($config)->flatten_to_dot_notation();
        static::add_action('init', [$this, 'load_textdomain']);
        // initialize notices early as they may be used by requirements or other logic before the plugin is fully initialized
        Notices::initialize($this);
        if (empty($config['requirements']) || Requirements::are_satisfied($this, (array) $config['requirements'])) {
            $this->initialize();
        }
    }
    /**
     * Returns the plugin identifier with underscores (snake_case).
     *
     * @since 1.0.0
     *
     * @param string|null $case optional case to convert the identifier to
     * @return string
     */
    public function id(?string $case = null): string
    {
        if ($case) {
            return Strings::string(static::ID)->convert_case($case)->to_string();
        }
        return static::ID;
    }
    /**
     * Returns the plugin id with dashes (kebab-case) in place of underscores (snake_case, default).
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function id_dasherized(): string
    {
        return static::id(Strings::KEBAB_CASE);
    }
    /**
     * Returns the plugin full name.
     *
     * @NOTE Plugins implementing this class should override this method to provide a translatable string.
     *
     * @since 1.0.0
     *
     * @return string plugin name
     */
    abstract public function name(): string;
    /**
     * Returns the current version of the plugin.
     *
     * @since 1.0.0
     *
     * @return string semver
     */
    public function version(): string
    {
        return static::VERSION;
    }
    /**
     * Gets the plugin vendor.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function vendor(): string
    {
        return static::VENDOR;
    }
    /**
     * Return the main plugin main file path.
     *
     * E.g.: `/www/path/to/wp-content/plugins/plugin-folder-name/plugin-file.php`
     *
     * @since 1.0.0
     *
     * @return string the full path and filename of the plugin file
     */
    public function absolute_file_path(): string
    {
        return (string) $this->config()->get('file', __FILE__);
    }
    /**
     * Returns the path to the plugin main file relative to the plugins' directory.
     *
     * E.g.: `<plugin-directory>/<plugin-loader>.php`
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function relative_file_path(): string
    {
        return plugin_basename($this->absolute_file_path());
    }
    /**
     * Returns the path to the plugin directory without a trailing slash.
     *
     * E.g.: `/www/path/to/wp-content/plugins/<plugin-directory>`
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function absolute_dir_path(): string
    {
        return untrailingslashit(plugin_dir_path($this->absolute_file_path()));
    }
    /**
     * Returns an absolute or relative path from the plugin's root.
     *
     * @since 1.0.0
     *
     * @param string $path optional path relative to the plugin directory
     * @param bool $absolute whether to return the absolute path (default true)
     * @return string
     */
    public function path(string $path, bool $absolute = \true): string
    {
        $the_path = $absolute ? $this->absolute_dir_path() : dirname($this->relative_file_path());
        if ($path) {
            $the_path .= '/' . ltrim($path, '/');
        }
        return $the_path;
    }
    /**
     * Returns the relative path to the plugin's translations directory.
     *
     * E.g.: `<plugin-directory>/i18n/languages`
     *
     * @see self::load_textdomain() as the basic use case, but this will be used also when loading script translations, etc.
     *
     * @since 1.0.0
     *
     * @param string $path optional path relative to the translations directory
     * @param bool $absolute optional whether to return the absolute path
     * @return string
     */
    public function translations_path(string $path = '', bool $absolute = \true): string
    {
        $translations_path = $this->path('/i18n/languages', $absolute);
        if ($path) {
            $translations_path .= '/' . ltrim($path, '/');
        }
        return $translations_path;
    }
    /**
     * Returns the absolute path to the plugin's templates directory.
     *
     * @since 1.2.0
     *
     * @param string $path optional path relative to the templates directory
     * @param bool $absolute optional whether to return the absolute path (default true)
     * @return string
     */
    public function templates_path(string $path = '', bool $absolute = \true): string
    {
        $templates_path = $this->path('/templates', $absolute);
        if ($path) {
            $templates_path .= '/' . ltrim($path, '/');
        }
        return $templates_path;
    }
    /**
     * Returns the absolute path to the plugin's assets directory.
     *
     * @since 1.1.0
     *
     * @param string $path optional path relative to the assets directory
     * @param bool $absolute optional whether to return the absolute path (default true)
     * @return string
     */
    public function assets_path(string $path = '', bool $absolute = \true): string
    {
        $assets_path = $this->path('/assets', $absolute);
        if ($path) {
            $assets_path .= '/' . ltrim($path, '/');
        }
        return $assets_path;
    }
    /**
     * Returns the plugin's URL without a trailing slash.
     *
     * E.g.: `http://kestrelwp.com/wp-content/plugins/<plugin-directory>`
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function base_url(): string
    {
        return untrailingslashit(plugins_url('/', $this->absolute_file_path()));
    }
    /**
     * Returns the plugin's textdomain.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function textdomain(): string
    {
        return static::TEXT_DOMAIN;
    }
    /**
     * Returns the plugin's documentation URL.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function documentation_url(): string
    {
        return add_query_arg(['utm_source' => 'plugin'], static::DOCUMENTATION_URL);
    }
    /**
     * Returns the plugin's support URL.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function support_url(): string
    {
        return add_query_arg(['utm_source' => 'plugin'], static::SUPPORT_URL);
    }
    /**
     * Returns the plugin's sales page URL.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function sales_page_url(): string
    {
        return add_query_arg(['utm_source' => 'plugin'], static::SALES_PAGE_URL);
    }
    /**
     * Returns the plugin's reviews URL.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function reviews_url(): string
    {
        return add_query_arg(['utm_source' => 'plugin'], static::REVIEWS_URL);
    }
    /**
     * Returns the URL to the plugin's assets.
     *
     * @since 1.0.0
     *
     * @param string $path optional path
     * @return string
     */
    public function assets_url(string $path = ''): string
    {
        $assets_url = $this->base_url() . '/assets';
        if ($path) {
            $assets_url .= '/' . ltrim($path, '/');
        }
        return $assets_url;
    }
    /**
     * Returns the plugin settings URL.
     *
     * @since 1.0.0
     *
     * @return string
     */
    abstract public function settings_url(): string;
    /**
     * Returns the plugin dashboard URL.
     *
     * By default, this is the settings URL but implementations may override this method.
     *
     * @since 1.0.0
     *
     * @return string|null returns null when the dashboard is not available
     */
    public function dashboard_url(): ?string
    {
        return null;
    }
    /**
     * Returns the plugin configuration.
     *
     * @since 1.0.0
     *
     * @return Arrays array data object
     */
    public function config(): Arrays
    {
        return Arrays::array($this->config);
    }
    /**
     * Returns a formatted hook name for the plugin.
     *
     * This should be used to build hook names implemented by the plugin.
     *
     * @since 1.0.0
     *
     * @param string $hook without prepending underscores
     * @return string e.g. '<vendor>_<plugin_id>_<hook>'
     */
    public function hook(string $hook): string
    {
        return $this->key($hook);
    }
    /**
     * Returns a formatted key for the plugin in snake case.
     *
     * This should be used for internal use, like option keys.
     *
     * @since 1.0.0
     *
     * @param string|null $key
     * @return string e.g. '<vendor>_<plugin_id>_<key>'
     */
    public function key(?string $key = null): string
    {
        return Strings::string($this->vendor() . '_' . $this->id())->append($key ? '_' . $key : '')->snake_case()->to_string();
    }
    /**
     * Returns a formatted handle for the plugin in kebab case.
     *
     * This should be used for building handle names, like script handles or HTML IDs.
     *
     * @since 1.0.0
     *
     * @param string|null $handle
     * @return string e.g. '<vendor>-<plugin_id>-<handle>'
     */
    public function handle(?string $handle = null): string
    {
        return Strings::string($this->vendor() . '-' . $this->id())->append($handle ? '-' . $handle : '')->kebab_case()->to_string();
    }
    /**
     * Initializes the plugin on instantiation.
     *
     * Plugin implementations can override this method to add their own initialization logic.
     *
     * @since 1.0.0
     *
     * @return void
     */
    protected function initialize(): void
    {
        Settings_Registry::initialize($this);
        Assets::initialize($this);
        $this->initialize_admin();
        $this->initialize_rest_api();
        $this->initialize_blocks_handler();
        Lifecycle::initialize($this);
        Integrations::initialize($this);
    }
    /**
     * Initializes the plugin admin handler.
     *
     * This method is provided so that plugin implementations can override it and provide their own admin handler if needed.
     * Plugins may also override the default handler by providing an 'admin.handler' configuration parameter.
     * The admin handler should be the only entry point for all admin functionalities.
     *
     * @since 1.0.0
     *
     * @return void
     */
    protected function initialize_admin(): void
    {
        $handler = $this->config()->get('admin.handler');
        if (!$handler) {
            Admin::initialize($this);
        } elseif (!is_string($handler) || !class_exists($handler) || !is_subclass_of($handler, Admin::class)) {
            _doing_it_wrong(__METHOD__, 'The admin handler must be a valid class name that extends ' . Admin::class, '');
        } else {
            $handler::initialize($this);
        }
    }
    /**
     * Initializes the REST API handler.
     *
     * @since 1.0.0
     *
     * @return void
     */
    protected function initialize_rest_api(): void
    {
        $handler = $this->config()->get('rest_api.handler');
        if (!$handler) {
            REST_API::initialize($this);
        } elseif (!is_string($handler) || !class_exists($handler) || !is_a($handler, REST_API::class, \true)) {
            _doing_it_wrong(__METHOD__, 'The REST API handler must be a valid class that extends ' . REST_API::class, '');
        } else {
            $handler::initialize($this);
        }
    }
    /**
     * Initializes the block editor handler.
     *
     * @since 1.1.0
     *
     * @return void
     */
    protected function initialize_blocks_handler(): void
    {
        $handler = $this->config()->get('blocks.handler');
        if (!$handler) {
            return;
        }
        if (!is_string($handler) || !class_exists($handler) || !is_a($handler, Blocks::class, \true)) {
            _doing_it_wrong(__METHOD__, 'The blocks handler must be a valid class that extends ' . Blocks::class, '');
        } else {
            $handler::initialize($this);
        }
    }
    /**
     * Loads the plugin textdomain.
     *
     * @since 1.0.0
     *
     * @return void
     */
    protected function load_textdomain(): void
    {
        if (!defined('WP_LANG_DIR')) {
            return;
        }
        $textdomain = $this->textdomain();
        // user's locale if in the admin, or the site locale otherwise
        $locale = is_admin() ? get_user_locale() : get_locale();
        $locale = apply_filters('plugin_locale', $locale, $textdomain);
        // phpcs:ignore
        load_textdomain($textdomain, \WP_LANG_DIR . '/' . $textdomain . '/' . $textdomain . '-' . $locale . '.mo');
        load_plugin_textdomain($textdomain, \false, $this->translations_path('', \false));
    }
    /**
     * Returns the default language for the plugin.
     *
     * @since 1.4.0
     *
     * @return string
     */
    public function locale(): string
    {
        return static::DEFAULT_LANGUAGE;
    }
    /**
     * Determines if the plugin supports more than one language.
     *
     * @since 1.1.4
     *
     * @return bool
     */
    public function is_multilingual(): bool
    {
        if (!is_bool($this->is_multilingual)) {
            $language_folder = $this->translations_path();
            $language_files = glob($language_folder . '/*.mo');
            $this->is_multilingual = !empty($language_files);
        }
        return $this->is_multilingual;
    }
    /**
     * Checks if the plugin is a fresh installation.
     *
     * @since 1.1.0
     *
     * @return bool
     */
    public function is_new_installation(): bool
    {
        $update_history = Lifecycle::get_update_history();
        return empty($update_history);
    }
    /**
     * Returns plugin information as an array.
     *
     * @since 1.0.0
     *
     * @return array<string, mixed>
     */
    public function to_array(): array
    {
        return ['id' => $this->id(), 'name' => $this->name(), 'paths' => ['assets' => $this->assets_path(), 'directory' => $this->absolute_dir_path(), 'file' => $this->absolute_file_path(), 'relative' => $this->relative_file_path(), 'translations' => $this->translations_path(), 'templates' => $this->templates_path()], 'textdomain' => $this->textdomain(), 'urls' => ['assets' => $this->assets_url(), 'base' => $this->base_url(), 'dashboard' => $this->dashboard_url(), 'documentation' => $this->documentation_url(), 'reviews' => $this->reviews_url(), 'sales_page' => $this->sales_page_url(), 'settings' => $this->settings_url(), 'support' => $this->support_url()], 'vendor' => $this->vendor(), 'version' => $this->version()];
    }
}
