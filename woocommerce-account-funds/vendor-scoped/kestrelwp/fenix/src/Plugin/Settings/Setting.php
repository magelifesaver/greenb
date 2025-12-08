<?php

declare (strict_types=1);
namespace Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Settings;

defined('ABSPATH') or exit;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Settings\Contracts\Setting_Store;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Settings\Contracts\Setting_Type;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Settings\Exceptions\Setting_Required_Exception;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Settings\Exceptions\Setting_Validation_Exception;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Settings\Types\Text;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Settings\Types\Type;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Traits\Has_Plugin_Instance;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Traits\Has_Accessors;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Traits\Is_Singleton;
/**
 * Object representation of a setting.
 *
 * @since 1.1.0
 *
 * @method array<string, string> get_attributes()
 * @method mixed get_default()
 * @method string get_description()
 * @method callable|null get_display_condition()
 * @method Settings_Group|null get_group()
 * @method string get_instructions()
 * @method string get_name()
 * @method string get_placeholder()
 * @method bool get_required()
 * @method bool get_read_only()
 * @method callable|null get_read_only_condition()
 * @method Setting_Store|null get_store()
 * @method string get_title()
 * @method Setting_Type|Type|null get_type()
 * @method array<int|string, mixed>|mixed|null get_value()
 * @method $this set_attributes( array $attributes )
 * @method $this set_default( mixed $default )
 * @method $this set_description( string $description )
 * @method $this set_display_condition( callable|null $display_condition )
 * @method $this set_group( Settings_Group $group )
 * @method $this set_instructions( string $instructions )
 * @method $this set_name( string $name )
 * @method $this set_placeholder( string $placeholder )
 * @method $this set_required( bool $required )
 * @method $this set_read_only( bool $read_only )
 * @method $this set_read_only_condition( callable|null $read_only_condition )
 * @method $this set_store( Setting_Store $store )
 * @method $this set_title( string $title )
 * @method $this set_type( Setting_Type|Type $type )
 */
class Setting
{
    use Has_Accessors;
    use Has_Plugin_Instance;
    use Is_Singleton;
    /** @var string setting name */
    protected string $name = '';
    /** @var Setting_Type|null setting type */
    protected ?Setting_Type $type = null;
    /** @var Setting_Store|null setting store */
    protected ?Setting_Store $store = null;
    /** @var string setting title: this is normally a field label */
    protected string $title = '';
    /** @var string optional setting description: for example, this may be a text output below an input field */
    protected string $description = '';
    /** @var string optional setting instruction information: for example, this may be shown in a tooltip next to an input field */
    protected string $instructions = '';
    /** @var string optional setting placeholder: some input fields support a placeholder while empty */
    protected string $placeholder = '';
    /** @var bool required setting flag: if true, the value cannot be null when saved */
    protected bool $required = \false;
    /** @var bool read-only setting flag: if true, the value cannot be changed */
    protected bool $read_only = \false;
    /** @var callable|callable-string|null read-only condition */
    protected $read_only_condition = null;
    /** @var array<string, scalar> optional field attributes */
    protected array $attributes = [];
    /** @var array<mixed>|scalar|null current value */
    protected $value = null;
    /** @var array<mixed>|scalar|null formatted value */
    protected $formatted_value = null;
    /** @var array<mixed>|scalar|null default value */
    protected $default = null;
    /** @var Settings_Group|null setting group */
    protected ?Settings_Group $group = null;
    /** @var callable|callable-string|null display condition */
    protected $display_condition = null;
    /**
     * Constructor.
     *
     * @since 1.1.0
     *
     * @param array<string, mixed> $data
     *
     * @phpstan-param array{
     *     attributes?: array<string, scalar>,
     *     default?: mixed,
     *     description?: string,
     *     display_condition?: callable|callable-string|null,
     *     field?: string,
     *     group?: Settings_Group|null,
     *     instructions?: string,
     *     name?: string,
     *     placeholder?: string,
     *     required?: bool,
     *     read_only?: bool,
     *     read_only_condition?: callable|callable-string|null,
     *     store?: Setting_Store,
     *     title?: string,
     *     type?: Setting_Type,
     *     value?: mixed,
     * } $data
     */
    public function __construct(array $data = [])
    {
        $this->to_array_excluded_properties = ['display_condition', 'group', 'read_only_condition', 'store'];
        $data = wp_parse_args($data, ['type' => new Text()]);
        $this->set_properties($data);
        $this->read();
        $this->register();
    }
    /**
     * Returns the setting instance.
     *
     * @since 1.7.1
     *
     * @param array<string, mixed> $args
     * @return self
     */
    public static function setting(array $args = []): self
    {
        return static::instance($args);
    }
    /**
     * Determines whether the setting value should not be null.
     *
     * @since 1.1.0
     *
     * @return bool
     */
    public function is_required(): bool
    {
        return $this->get_required();
    }
    /**
     * Determines whether the setting value should not be changed once set.
     *
     * @since 1.1.0
     *
     * @return bool
     */
    public function is_read_only(): bool
    {
        $read_only = $this->get_read_only();
        if (\true === $read_only) {
            return \true;
        }
        if (is_callable($this->get_read_only_condition())) {
            return call_user_func($this->get_read_only_condition());
        }
        return \false;
    }
    /**
     * Sets the setting's current value.
     *
     * @since 1.1.0
     *
     * @param array<int|string, mixed>|scalar|null $value
     * @return $this
     */
    public function set_value($value): Setting
    {
        $this->value = $this->get_type()->is_multiple() ? (array) $value : $value;
        return $this;
    }
    /**
     * Returns the setting's formatted value.
     *
     * This method is used when converting {@see Setting::$formatted_value} to array in {@see Setting::to_array()}.
     *
     * @since 1.1.0
     *
     * @return array<int|string, mixed>|scalar|null
     */
    protected function get_formatted_value()
    {
        return $this->format();
    }
    /**
     * Returns the formatted value for display or internal logic.
     *
     * @since 1.1.0
     *
     * @return mixed
     */
    final public function format()
    {
        return $this->get_type()->format($this->get_value());
    }
    /**
     * Validates the current value.
     *
     * @since 1.1.0
     *
     * @return bool
     * @throws Setting_Validation_Exception
     */
    final public function validate(): bool
    {
        return $this->get_type()->validate($this->get_value());
    }
    /**
     * Reads the value for this setting from the store.
     *
     * @since 1.1.0
     *
     * @return $this
     */
    final public function read(): Setting
    {
        $value = null;
        if ($group = $this->get_group()) {
            $group->read();
            if ($setting = $group->get_setting($this->get_name())) {
                $value = $setting->get_value();
            }
        } elseif ($store = $this->get_store()) {
            $value = $store->read();
        }
        $this->set_value(null === $value ? $this->get_default() : $value);
        return $this;
    }
    /**
     * Saves the setting's value to the store.
     *
     * @since 1.1.0
     *
     * @return $this
     * @throws Setting_Required_Exception
     * @throws Setting_Validation_Exception
     */
    final public function save(): Setting
    {
        if ($group = $this->get_group()) {
            $group->update_setting($this);
            $group->save();
            return $this;
        }
        try {
            if (!$this->get_type() instanceof Type || !$this->get_store() || !$this->validate()) {
                return $this;
            }
        } catch (Setting_Validation_Exception $exception) {
            throw new Setting_Validation_Exception(esc_html(sprintf('"%s": %s.', $this->get_title(), lcfirst($exception->getMessage()))), $exception);
            // phpcs:ignore
        }
        if (null === $this->get_value()) {
            if (!$this->is_required()) {
                $this->delete();
            } else {
                /* translators: Placeholder: %s - Setting title */
                throw new Setting_Required_Exception(esc_html(sprintf(__('"%s" is required.', static::plugin()->textdomain()), $this->get_title())));
            }
            return $this;
        }
        $this->get_store()->save($this->get_type()->sanitize($this->get_value()));
        return $this;
    }
    /**
     * Deletes the setting from the store.
     *
     * @since 1.1.0
     *
     * @return $this
     * @throws Setting_Required_Exception
     * @throws Setting_Validation_Exception
     */
    final public function delete(): Setting
    {
        if ($group = $this->get_group()) {
            $this->set_value($this->is_required() ? $this->get_default() : null);
            $group->update_setting($this);
            $group->save();
            return $this;
        }
        if ($store = $this->get_store()) {
            $store->delete();
        }
        return $this;
    }
    /**
     * Registers the setting.
     *
     * @since 1.1.0
     *
     * @return $this
     */
    final public function register(): Setting
    {
        Settings_Registry::register($this);
        return $this;
    }
    /**
     * De-registers the setting.
     *
     * @since 1.1.0
     *
     * @return $this
     */
    final public function deregister(): Setting
    {
        Settings_Registry::deregister($this);
        return $this;
    }
}
