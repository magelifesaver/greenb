<?php

declare (strict_types=1);
namespace Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin;

defined('ABSPATH') or exit;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Model\User;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Admin\Dashboard;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Admin\Metaboxes;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Admin\Notices\Notice;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Admin\Screens;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Contracts\WordPress_Plugin;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Traits\Is_Handler;
/**
 * Main plugin handler for the WordPress admin area.
 *
 * Sub-handlers that interact with the WordPress admin area should be initialized from implementations of this class.
 *
 * @since 1.0.0
 */
class Admin
{
    use Is_Handler;
    /**
     * Admin handler constructor.
     *
     * @NOTE The context when this constructor is initialized is not 'admin_init' but 'plugins_loaded'. Hooks may be added here to run on 'admin_init' or later.
     *
     * @since 1.0.0
     *
     * @param Plugin|null $plugin
     */
    protected function __construct(?WordPress_Plugin $plugin)
    {
        static::$plugin = $plugin;
        static::add_action('init', function () {
            $this->initialize_screens();
            $this->initialize_metaboxes();
        });
        static::add_action('admin_notices', [$this, 'add_admin_notices']);
        // plugins page actions
        static::add_filter('plugin_action_links_' . $plugin->relative_file_path(), [$this, 'add_plugins_page_row_links']);
        static::add_filter('extra_plugin_headers', [$this, 'parse_additional_plugin_headers']);
        static::add_filter('plugin_row_meta', [$this, 'add_plugins_page_meta'], 10, 3);
    }
    /**
     * Initializes any plugin admin screens.
     *
     * @since 1.2.0
     *
     * @return void
     */
    protected function initialize_screens(): void
    {
        Screens::initialize(self::plugin());
        $this->initialize_dashboard();
    }
    /**
     * Initializes any plugin admin metaboxes.
     *
     * @since 1.2.0
     *
     * @return void
     */
    protected function initialize_metaboxes(): void
    {
        Metaboxes::initialize(self::plugin());
    }
    /**
     * Initializes the plugin dashboard, if configured.
     *
     * @since 1.0.0
     *
     * @return void
     */
    protected function initialize_dashboard(): void
    {
        $handler = static::plugin()->config()->get('admin.dashboard');
        if (!$handler) {
            return;
        }
        if (!is_string($handler) || !class_exists($handler) || !is_a($handler, Dashboard::class, \true)) {
            _doing_it_wrong(__METHOD__, 'The dashboard must be a valid class name compatible with ' . Dashboard::class, '');
        } else {
            $handler::initialize(static::plugin());
        }
    }
    /**
     * Adds notices to be displayed in the admin.
     *
     * Concrete plugins may extend this method to add additional notices when they are broad or depend on the plugin main class;
     * otherwise, it is advisable to output those notices in their own context from a specific handler, as applicable.
     *
     * @since 1.1.4
     *
     * @return void
     */
    protected function add_admin_notices(): void
    {
        // displays a notice if the user is using WordPress in a different locale than the plugin's default language
        if ($this->should_display_multilingual_notice()) {
            $message = sprintf(
                /* translators: Placeholders: %1$s - Opening <a> link tag, %2$s - Closing </a> link tag */
                __('This plugin is localized into multiple languages. While English is our primary language, our goal is to serve merchants and their customers in their own native language. If you would like to provide localization feedback and help us build features that are relevant to you and to customers located in your region, please %1$sget in touch%2$s.', self::plugin()->textdomain()),
                '<a href="' . esc_url(self::plugin()->support_url()) . '" target="_blank">',
                '</a>'
            );
            Notice::info($message)->set_id(self::plugin()->key('multilingual_feedback'))->set_dismissible(\true)->set_capability('manage_options')->set_display_condition(function () {
                $current_user = User::current();
                return $current_user && !$current_user->uses_locale(self::plugin()->locale());
            })->dispatch();
        }
    }
    /**
     * Determines whether to display the multilingual notice.
     *
     * @since 1.1.4
     *
     * @return bool
     */
    private function should_display_multilingual_notice(): bool
    {
        if (!self::plugin()->is_multilingual()) {
            return \false;
        }
        $default_language = self::plugin()->locale();
        $local_language = get_locale();
        // if the default language is the same or within the same language family, do not display the notice
        if ($default_language === $local_language || strpos($default_language, substr($local_language, 0, 2)) === 0) {
            return \false;
        }
        return \true;
    }
    /**
     * Filters the actions links in the plugin rows on the plugins screen.
     *
     * @NOTE A link to the {@see Dashboard} will be added from that class when it is initialized.
     *
     * @since 1.0.0
     *
     * @param array<string, mixed>|mixed $action_links
     * @return array<string, mixed>|mixed
     */
    protected function add_plugins_page_row_links($action_links)
    {
        if (!is_array($action_links)) {
            return $action_links;
        }
        $custom_actions = [];
        if ($settings_url = static::plugin()->settings_url()) {
            /* translators: Context: WordPress admin link to configure the plugin (verb) */
            $custom_actions['configure'] = sprintf('<a href="%s">%s</a>', esc_url($settings_url), esc_html__('Configure', static::plugin()->textdomain()));
        }
        if ($docs_url = static::plugin()->documentation_url()) {
            /* translators: Context: Link to the plugin's documentation ("Docs" = "Documentation") */
            $custom_actions['docs'] = sprintf('<a href="%s" target="_blank">%s</a>', esc_url($this->add_plugins_page_row_link_utm($docs_url)), esc_html__('Docs', static::plugin()->textdomain()));
        }
        if ($support_url = static::plugin()->support_url()) {
            /* translators: Context: Link to reach out to customer support ("Support" = "Customer support") */
            $custom_actions['support'] = sprintf('<a href="%s" target="_blank">%s</a>', esc_url($this->add_plugins_page_row_link_utm($support_url)), esc_html__('Support', static::plugin()->textdomain()));
        }
        if ($reviews_url = static::plugin()->reviews_url()) {
            /* translators: Context: Link to review the plugin ("Review" = "Review the plugin" as in verb action) */
            $custom_actions['review'] = sprintf('<a href="%s" target="_blank">%s</a>', esc_url($this->add_plugins_page_row_link_utm($reviews_url)), esc_html__('Review', static::plugin()->textdomain()));
        }
        /**
         * Filters the plugin links displayed on the plugins' page row.
         *
         * @since 1.0.0
         *
         * @param array<string, mixed> $action_links
         */
        return (array) apply_filters(static::plugin()->hook('plugins_page_row_links'), array_merge($custom_actions, $action_links));
    }
    /**
     * Adds UTM parameters to the plugin action links.
     *
     * @since 1.2.0
     *
     * @param string $url
     * @return string
     */
    protected function add_plugins_page_row_link_utm(string $url): string
    {
        return add_query_arg(['utm_medium' => 'plugin-action-link'], $url);
    }
    /**
     * Adds extra plugin headers for WordPress to parse among the plugin metadata.
     *
     * Plugins overriding this class may use this method to add additional readers to be read from the plugin main file doc block.
     * These will be available in the {@see Admin::add_plugin_meta()} method to be displayed in the plugins page among other metadata.
     *
     * @since 1.0.0
     *
     * @noinspection
     *
     * @param mixed|string[] $headers original headers
     * @return mixed|string[]
     */
    protected function parse_additional_plugin_headers($headers)
    {
        // add any headers in the array, matching any headers found in the main file headers, e.g. 'Documentation URI'
        return $headers;
    }
    /**
     * Displays additional plugin metadata in the plugins page row.
     *
     * Plugin headers made available for parsing by {@see Loader::add_plugin_headers()} will be available here for plugins page metadata customization.
     *
     * @since 1.0.0
     *
     * @param mixed|string[] $plugin_meta
     * @param mixed|string $plugin_file
     * @param array<string, string>|mixed $plugin_header_data
     * @return mixed|string[]
     */
    protected function add_plugins_page_meta($plugin_meta, $plugin_file, $plugin_header_data)
    {
        if (!is_array($plugin_meta) || !is_string($plugin_file) || !is_array($plugin_header_data)) {
            return $plugin_meta;
        }
        if (plugin_basename(static::plugin()->absolute_file_path()) !== $plugin_file) {
            return $plugin_meta;
        }
        // add custom metadata here from $plugin_header_data
        return $plugin_meta;
    }
}
