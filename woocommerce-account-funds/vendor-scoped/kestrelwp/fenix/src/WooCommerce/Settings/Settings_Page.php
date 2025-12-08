<?php

declare (strict_types=1);
namespace Kestrel\Account_Funds\Scoped\Kestrel\Fenix\WooCommerce\Settings;

defined('ABSPATH') or exit;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Settings\Exceptions\Setting_Exception;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Settings\Field;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Settings\Setting;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Settings\Types\Type;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Traits\Has_Hidden_Callbacks;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Traits\Has_Plugin_Instance;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Traits\Creates_New_Instances;
use WC_Admin_Settings;
use WC_Settings_Page;
use WP_Screen;
/**
 * Abstract class for adding a setting page as a setting tab inside the WooCommerce settings page.
 *
 * @NOTE Since this object extends {@see WC_Settings_Page}, constants and static methods better be internal access only as the parent class might not be always available.
 *
 * @since 1.1.0
 */
abstract class Settings_Page extends WC_Settings_Page
{
    use Creates_New_Instances;
    use Has_Plugin_Instance;
    use Has_Hidden_Callbacks;
    /** @var string setting page tab ID */
    protected const TAB_ID = '';
    /** @var string setting page tab position among the WooCommerce settings */
    protected const TAB_POSITION = '';
    /** @var string the capability required to view the page */
    protected const CAPABILITY = 'manage_woocommerce';
    /** @var array<string, string> section IDs and titles */
    protected array $sections = [];
    /** @var array<string, Settings_Section|Settings_Section[]> */
    protected array $settings = [];
    /**
     * Settings page constructor.
     *
     * @since 1.1.0
     *
     * @param string $label the settings tab label
     * @param array<string, Settings_Section|Settings_Section[]> $settings
     */
    public function __construct(string $label, array $settings = [])
    {
        $this->id = static::TAB_ID;
        $this->label = $label;
        $this->settings = $settings;
        static::add_filter('woocommerce_get_settings_' . $this->id, [$this, 'get_adapted_settings_for_section'], 9, 2);
        static::add_action('admin_enqueue_scripts', [$this, 'register_scripts']);
        parent::__construct();
    }
    /**
     * Registers the scripts and styles for the current settings page.
     *
     * @since 1.1.0
     *
     * @return void
     */
    protected function register_scripts(): void
    {
        // concrete implementations may override this method if they need to add scripts and styles for the current setting page only
    }
    /**
     * Checks if the current screen is for the current settings page.
     *
     * @NOTE This method is not public since this class extends {@see WC_Settings_Page} and the parent class might not be always loaded, resulting in errors if this would be invoked in a different context.
     *
     * @since 1.1.0
     *
     * @param string|null $section_id
     * @return bool
     */
    protected static function is_current_screen(?string $section_id = null): bool
    {
        global $current_screen, $current_tab, $current_section;
        if (!$current_screen instanceof WP_Screen) {
            return \false;
        }
        if ('woocommerce_page_wc-settings' !== $current_screen->id) {
            return \false;
        }
        $tab = $current_tab;
        $section = $current_section;
        if (!$tab) {
            $tab = $_GET['tab'] ?? '';
            // phpcs:ignore
        }
        if (null === $section_id) {
            return $tab === static::TAB_ID;
        }
        if (!$section) {
            $section = $_GET['section'] ?? '';
            // phpcs:ignore
        }
        return $tab === static::TAB_ID && $section === $section_id;
    }
    /**
     * Adds the setting page.
     *
     * Overrides {@see \WC_Settings_Page::add_settings_page()} to insert our page in the correct position.
     *
     * @since 1.1.0
     *
     * @param array<string, string>|mixed $pages
     * @return array<string, string>|mixed
     */
    public function add_settings_page($pages)
    {
        if (!is_array($pages) || !current_user_can(static::CAPABILITY)) {
            return $pages;
        }
        $id = $this->get_id();
        $label = $this->get_label();
        if ('' === $id || '' === $label) {
            return $pages;
        }
        if ('' !== static::TAB_POSITION && isset($pages[static::TAB_POSITION])) {
            $position = array_search(static::TAB_POSITION, array_keys($pages), \true);
            $pages = array_slice($pages, 0, $position + 1, \true) + [$id => $label] + array_slice($pages, $position + 1, null, \true);
        } else {
            $pages[$id] = $label;
        }
        return $pages;
    }
    /**
     * Gets the page own sections.
     *
     * Implements {@see WC_Settings_Page::get_own_sections()}.
     *
     * @since 1.1.0
     *
     * @return array<string, string>
     */
    protected function get_own_sections()
    {
        return $this->sections;
    }
    /**
     * Returns the adapted settings for the given section.
     *
     * Callback for {@see WC_Settings_Page::get_settings_for_section()}.
     *
     * @since 1.1.0
     *
     * @param array<int, array<string, mixed>>|mixed $settings
     * @param mixed|string $section_id
     * @return array<int, array<string, mixed>>|mixed
     */
    protected function get_adapted_settings_for_section($settings, $section_id)
    {
        if (!is_string($section_id)) {
            return $settings;
        }
        return $this->get_adapted_settings($section_id);
    }
    /**
     * Returns the settings for the current section.
     *
     * @since 1.1.0
     *
     * @param string $section_id default section is an empty string
     * @return array<int, array<string, mixed>>
     */
    protected function get_adapted_settings(string $section_id): array
    {
        $settings = (array) ($this->settings[$section_id] ?? []);
        if (empty($settings)) {
            return [];
        }
        $adapted_settings = [];
        foreach ($settings as $section) {
            if ($section instanceof Settings_Section) {
                $adapted_settings[] = $section->get_settings_definitions();
            }
        }
        return array_merge(...$adapted_settings);
    }
    /**
     * Saves the settings.
     *
     * Overrides {@see WC_Settings_Page::save()} to save the settings.
     *
     * @since 1.1.0
     *
     * @return void
     */
    public function save()
    {
        global $current_section;
        if (!is_string($current_section)) {
            return;
        }
        $sections = (array) ($this->settings[$current_section] ?? []);
        $posted_data = $_POST;
        // phpcs:ignore
        foreach ($sections as $section) {
            if (!$section instanceof Settings_Section) {
                continue;
            }
            foreach ($section->get_settings() as $setting) {
                // @phpstan-ignore-next-line type sanity check
                if (!$setting instanceof Setting) {
                    continue;
                }
                $value = $posted_data[$setting->get_name()] ?? null;
                $type = $setting->get_type();
                // @phpstan-ignore-next-line
                if ($type && $type->get_field() === Field::CHECKBOX) {
                    $value = wc_bool_to_string($value);
                }
                $setting->set_value($value);
                // phpcs:ignore
                try {
                    $setting->save();
                } catch (Setting_Exception $exception) {
                    // validation exceptions are caught and displayed as errors
                    WC_Admin_Settings::add_error(esc_html($exception->getMessage()));
                }
            }
        }
    }
}
