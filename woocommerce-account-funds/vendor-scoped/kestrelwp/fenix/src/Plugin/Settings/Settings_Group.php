<?php

declare (strict_types=1);
namespace Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Settings;

defined('ABSPATH') or exit;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Settings\Contracts\Setting_Store;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Settings\Exceptions\Setting_Required_Exception;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Settings\Exceptions\Setting_Validation_Exception;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Settings\Stores\Option;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Traits\Has_Plugin_Instance;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Traits\Is_Singleton;
/**
 * Object representation of a collection of settings.
 *
 * The main difference with individual {@see Setting} objects is that all settings in the collection are stored and retrieved together.
 * For example, when using the {@see Option} setting store, all settings are stored as a serialized array within the option, instead of individual options.
 *
 * @since 1.1.0
 */
class Settings_Group
{
    use Has_Plugin_Instance;
    use Is_Singleton;
    /** @var array<string, Setting> */
    protected array $settings = [];
    /** @var Setting_Store */
    protected Setting_Store $store;
    /**
     * Constructor.
     *
     * @since 1.1.0
     *
     * @param Setting_Store $store
     * @param array<int|string, string>|Setting[] $settings
     */
    public function __construct(Setting_Store $store, array $settings = [])
    {
        $this->store = $store;
        foreach ($settings as $setting) {
            if ($setting instanceof Setting) {
                $this->add_setting($setting);
            }
        }
        $this->read();
        $this->register();
    }
    /**
     * Adds a setting to the group.
     *
     * @since 1.1.0
     *
     * @param Setting $setting
     * @return $this
     */
    final public function add_setting(Setting $setting): Settings_Group
    {
        if (!isset($this->settings[$setting->get_name()])) {
            $setting->set_group($this);
            $this->settings[$setting->get_name()] = $setting;
        }
        return $this;
    }
    /**
     * Updates a setting in the group.
     *
     * @since 1.1.0
     *
     * @param Setting $setting
     * @return $this
     */
    final public function update_setting(Setting $setting): Settings_Group
    {
        $setting->set_group($this);
        $this->settings[$setting->get_name()] = $setting;
        return $this;
    }
    /**
     * Removes a setting from the group.
     *
     * @since 1.1.0
     *
     * @param mixed|Setting|string $setting
     * @return $this
     */
    final public function remove_setting($setting): Settings_Group
    {
        if ($setting instanceof Setting) {
            $setting = $setting->get_name();
        }
        if (is_string($setting) && isset($this->settings[$setting])) {
            unset($this->settings[$setting]);
        }
        return $this;
    }
    /**
     * Gets a setting from the group.
     *
     * @since 1.1.0
     *
     * @param string $name
     * @return Setting|null
     */
    final public static function get_setting(string $name): ?Setting
    {
        return static::instance()->settings[$name] ?? null;
    }
    /**
     * Gets all the settings in the group.
     *
     * @since 1.1.0
     *
     * @return array<string, Setting>
     */
    final public static function get_settings(): array
    {
        return static::instance()->settings;
    }
    /**
     * Reads the value for this setting group from the store.
     *
     * @since 1.1.0
     *
     * @return $this
     */
    final public function read(): Settings_Group
    {
        $settings_values = (array) $this->store->read();
        foreach ($settings_values as $setting_name => $settings_value) {
            foreach ($this->settings as $setting) {
                if ($setting->get_name() === $setting_name) {
                    $setting->set_value($settings_value);
                }
            }
        }
        return $this;
    }
    /**
     * Saves the settings group to the store.
     *
     * @since 1.1.0
     *
     * @return $this
     * @throws Setting_Required_Exception
     * @throws Setting_Validation_Exception
     */
    final public function save(): Settings_Group
    {
        $settings_values = $validation_errors = $required_errors = [];
        foreach ($this->settings as $setting) {
            try {
                $setting->validate();
            } catch (Setting_Validation_Exception $exception) {
                $validation_errors[] = esc_html(sprintf('"%s": %s.', $setting->get_title(), lcfirst($exception->get_message())));
                continue;
            }
            if (null === $setting->get_value()) {
                if ($setting->is_required()) {
                    /* translators: Placeholder: %s - Setting title */
                    $required_errors[] = esc_html(sprintf(__('"%s" is required.', static::plugin()->textdomain()), $setting->get_title()));
                    continue;
                }
            }
            $settings_values[$setting->get_name()] = $setting->get_type()->sanitize($setting->get_value());
        }
        if (!empty($validation_errors)) {
            throw new Setting_Validation_Exception($validation_errors);
            // phpcs:ignore
        }
        if (!empty($required_errors)) {
            throw new Setting_Required_Exception($required_errors);
            // phpcs:ignore
        }
        $this->store->save($settings_values);
        return $this;
    }
    /**
     * Deletes the setting group data from the store.
     *
     * This will delete all the values for all the settings within the group.
     *
     * @since 1.1.0
     *
     * @return $this
     */
    final public function delete(): Settings_Group
    {
        $this->store->delete();
        return $this;
    }
    /**
     * Registers all the settings in the group.
     *
     * @since 1.1.0
     *
     * @return void
     */
    final public function register(): void
    {
        foreach ($this->settings as $setting) {
            Settings_Registry::register($setting);
        }
    }
    /**
     * De-registers all the settings in the group.
     *
     * @since 1.1.0
     *
     * @return void
     */
    final public function deregister(): void
    {
        foreach ($this->settings as $setting) {
            Settings_Registry::deregister($setting);
        }
    }
}
