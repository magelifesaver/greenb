<?php

declare (strict_types=1);
namespace Kestrel\Store_Credit\Scoped\Kestrel\Fenix\WooCommerce\Settings;

defined('ABSPATH') or exit;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Settings\Field;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Settings\Setting;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Settings\Types\Amount;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Settings\Types\Boolean;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Settings\Types\Credential;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Settings\Types\Number;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Settings\Types\Text;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Settings\Types\Type as Setting_Type;
/**
 * Adapts between a native {@see Setting} object and a WooCommerce setting definitions as an array.
 *
 * @since 1.1.0
 */
class Setting_Adapter
{
    /** @var array<string, mixed>|Setting */
    private $source;
    /**
     * Adapter constructor.
     *
     * @since 1.1.0
     *
     * @param array<int|string, mixed>|Setting $source
     */
    protected function __construct($source)
    {
        $this->source = $source;
    }
    /**
     * Initializes a new adapter instance from an associative array of WooCommerce settings.
     *
     * @since 1.1.0
     *
     * @param array<string, mixed> $source
     * @return Setting_Adapter
     */
    public static function from_array(array $source): Setting_Adapter
    {
        return new self($source);
    }
    /**
     * Initializes a new adapter instance from an array of settings objects.
     *
     * @param Setting $source
     * @return Setting_Adapter
     */
    public static function from_object(Setting $source): Setting_Adapter
    {
        return new self($source);
    }
    /**
     * Converts the source into a WooCommerce setting definition as an associative array.
     *
     * @since 1.1.0
     *
     * @return array<string, mixed>
     */
    public function to_array(): array
    {
        if (is_array($this->source)) {
            return $this->source;
        }
        $setting = $this->source;
        // @phpstan-ignore-next-line
        if (!$setting instanceof Setting) {
            return [];
        }
        $setting_type = $setting->get_type();
        if (!$setting_type instanceof Setting_Type) {
            return [];
        }
        $field_type = $setting_type->get_field();
        $setting_array = ['type' => $field_type, 'id' => $setting->get_name(), 'name' => $setting->get_name(), 'title' => $setting->get_title(), 'placeholder' => $setting->get_placeholder(), 'custom_attributes' => $setting->get_attributes()];
        if ($description = $setting->get_description()) {
            $setting_array['desc'] = $description;
            $setting_array['desc_tip'] = \false;
        }
        if ($tooltip = $setting->get_instructions()) {
            $setting_array['desc_tip'] = $tooltip;
        }
        if (null !== $setting->get_default()) {
            $setting_array['default'] = $setting->get_default();
        }
        if (null !== $setting->get_value()) {
            if (Field::CHECKBOX === $field_type && $setting_type instanceof Boolean) {
                $setting_array['value'] = wc_bool_to_string($setting->get_value());
            } else {
                $setting_array['value'] = $setting_type->format($setting->get_value());
            }
        }
        if ($setting_type->has_choices()) {
            $setting_array['options'] = $setting_type->get_choices();
        }
        if ($setting_type->is_multiple()) {
            $setting_array['name'] .= '[]';
            $setting_array['multiple'] = \true;
        }
        if (Field::SELECT === $field_type) {
            $setting_array['class'] = 'wc-enhanced-select';
            if ($setting_type->has_group_choices()) {
                $setting_array['type'] = $setting_type->is_multiple() ? 'group_multiselect' : 'group_select';
                $setting_array['options'] = $setting_type->get_choices(\false);
            } elseif (!isset($setting_array['options']) || !is_array($setting_array['options'])) {
                // @phpstan-ignore-line sanity check
                $setting_array['options'] = [];
            }
        }
        if ($setting_type instanceof Text) {
            if ($setting_type instanceof Credential) {
                $setting_array['custom_attributes']['autocomplete'] = 'off';
            }
        } elseif ($setting_type instanceof Number) {
            if ($setting_type instanceof Amount) {
                $setting_array['type'] = Field::FINANCIAL_AMOUNT;
            }
            if ($increments = $setting_type->get_increments()) {
                $setting_array['custom_attributes']['step'] = $increments;
            }
        }
        if ($min = $setting_type->get_min()) {
            $setting_array['custom_attributes']['min'] = $min;
        }
        if ($max = $setting_type->get_max()) {
            $setting_array['custom_attributes']['max'] = $max;
        }
        if ($pattern = $setting_type->get_pattern()) {
            $setting_array['custom_attributes']['pattern'] = $pattern;
        }
        // @phpstan-ignore-next-line sanity check to prevent errors in WooCommerce
        if (isset($setting_array['options']) && !is_array($setting_array['options'])) {
            $setting_array['options'] = [];
        }
        return $setting_array;
    }
    /**
     * Converts a WooCommerce setting definition from an associative array into a {@see Setting} object.
     *
     * @since 1.1.0
     *
     * @return Setting
     */
    public function to_object(): Setting
    {
        // @TODO convert back to settings objects
        return new Setting();
    }
}
