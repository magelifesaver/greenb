<?php

declare (strict_types=1);
namespace Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Settings\Types;

defined('ABSPATH') or exit;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Settings\Contracts\Setting_Type;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Traits\Has_Plugin_Instance;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Traits\Creates_New_Instances;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Traits\Has_Accessors;
/**
 * Base class for setting types.
 *
 * @since 1.1.0
 *
 * @method string get_field()
 * @method string|null get_pattern()
 * @method float|int|null get_min()
 * @method float|int|null get_max()
 * @method $this set_field( string $field )
 * @method $this set_multiple( bool|string $multiple )
 * @method $this set_pattern( string|null $pattern )
 * @method $this set_min( float|int|null $min )
 * @method $this set_max( float|int|null $max )
 */
abstract class Type implements Setting_Type
{
    use Creates_New_Instances;
    use Has_Accessors;
    use Has_Plugin_Instance;
    /** @var string the field type */
    protected string $field = '';
    /** @var array<int|string, array<int|string, mixed>|mixed> */
    protected array $choices = [];
    /** @var "no"|"yes"|bool whether the value is a list of items */
    protected $multiple = \false;
    /** @var string|null regular expression pattern */
    protected ?string $pattern = null;
    /** @var float|int|null minimum value length */
    protected $min = null;
    /** @var float|int|null maximum value length */
    protected $max = null;
    /**
     * Setting type constructor.
     *
     * @since 1.1.0
     *
     * @param array<string, mixed> $args
     */
    public function __construct(array $args = [])
    {
        $this->set_properties($args);
    }
    /**
     * Determines if the field is of a specific type.
     *
     * @param string $field
     * @return bool
     */
    protected function is_field(string $field): bool
    {
        return $this->get_field() === $field;
    }
    /**
     * Determines if the value is a list of items.
     *
     * @since 1.1.0
     *
     * @return "no"|"yes"
     */
    public function get_multiple(): string
    {
        if (function_exists('wc_bool_to_string')) {
            return wc_bool_to_string($this->multiple);
        }
        return in_array($this->multiple, [\true, 'true', 1, '1', 'yes'], \true) ? 'yes' : 'no';
    }
    /**
     * Determines if the value is a list of items.
     *
     * @since 1.1.0
     *
     * @return bool
     */
    public function is_multiple(): bool
    {
        return 'yes' === $this->get_multiple();
    }
    /**
     * Sets the multiple value.
     *
     * @since 1.1.0
     *
     * @param array<int|string, array<int|string, mixed>|mixed> $choices
     * @return $this
     */
    public function set_choices(array $choices): Type
    {
        $this->choices = $choices;
        return $this;
    }
    /**
     * Determines if the value has to be within choices.
     *
     * @since 1.1.0
     *
     * @return bool
     */
    public function has_choices(): bool
    {
        return !empty($this->get_choices(\false));
    }
    /**
     * Determines if the choices are organized in groups.
     *
     * @since 1.1.0
     *
     * @return bool
     */
    public function has_group_choices(): bool
    {
        foreach ($this->get_choices(\false) as $value) {
            if (is_array($value)) {
                return \true;
            }
        }
        return \false;
    }
    /**
     * Returns the choices.
     *
     * @since 1.1.0
     *
     * @param bool $flatten optional, defaults to true
     * @return array<int|string, array<int|string, mixed>|mixed>
     */
    public function get_choices(bool $flatten = \true): array
    {
        $choices = $this->choices;
        if ($flatten) {
            foreach ($this->choices as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $sub_key => $sub_value) {
                        $choices[$sub_key] = $sub_value;
                    }
                } else {
                    $choices[$key] = $value;
                }
            }
        }
        return $choices;
    }
    /**
     * Adds a choice to the list.
     *
     * @since 1.1.0
     *
     * @param int|string $key
     * @param mixed $value
     * @return $this
     */
    public function add_choice($key, $value): Type
    {
        $this->choices[$key] = $value;
        return $this;
    }
    /**
     * Removes a choice from the list.
     *
     * @since 1.1.0
     *
     * @param int|string $key
     * @return $this
     */
    public function remove_choice($key): Type
    {
        unset($this->choices[$key]);
        return $this;
    }
    /**
     * Provides an overrideable method for subtypes to validate the value.
     *
     * @since 1.1.0
     *
     * @param mixed $value
     * @return bool
     */
    protected function validate_subtype($value): bool
    {
        return \true;
    }
    /**
     * Provides an overrideable method for subtypes to format the value.
     *
     * @since 1.1.0
     *
     * @param mixed $value
     * @return mixed
     */
    protected function format_subtype($value)
    {
        return $value;
    }
    /**
     * Provides an overrideable method for subtypes to format the value.
     *
     * @since 1.1.0
     *
     * @param scalar $value
     * @return scalar
     */
    protected function sanitize_subtype($value)
    {
        return $value;
    }
}
