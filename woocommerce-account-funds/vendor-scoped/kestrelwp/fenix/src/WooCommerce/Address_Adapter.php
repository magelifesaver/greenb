<?php

declare (strict_types=1);
namespace Kestrel\Account_Funds\Scoped\Kestrel\Fenix\WooCommerce;

defined('ABSPATH') or die;
use Kestrel\Account_Funds\Scoped\CommerceGuys\Addressing\Address;
use WC_Countries;
/**
 * Converts between an {@see Address} object and a WooCommerce address array.
 *
 * @since 1.1.0
 */
class Address_Adapter
{
    /** @var Address|array<string, string> */
    protected $source;
    /**
     * Constructor.
     *
     * @since 1.1.0
     *
     * @param Address|array<string, string> $source
     */
    protected function __construct($source)
    {
        $this->source = $source;
    }
    /**
     * Initializes an adapter from an address array.
     *
     * @since 1.1.0
     *
     * @param array<int|string, string> $source
     * @return Address_Adapter
     */
    public static function from_array(array $source): Address_Adapter
    {
        return new self(static::trim_address_prefix($source));
    }
    /**
     * Initializes an adapter from an address object.
     *
     * @since 1.1.0
     *
     * @param Address $source
     * @return Address_Adapter
     */
    public static function from_object(Address $source): Address_Adapter
    {
        return new self($source);
    }
    /**
     * Trims the prefix from the keys of the address array.
     *
     * @since 1.1.0
     *
     * @param array<int|string, string> $address
     * @return array<string, string>
     */
    protected static function trim_address_prefix(array $address): array
    {
        $array = [];
        foreach ($address as $key => $value) {
            $new_key = is_string($key) ? preg_replace('/^(billing_|shipping_)/', '', $key) : '';
            if ($new_key !== '') {
                $array[$new_key] = is_scalar($value) ? (string) $value : '';
                // @phpstan-ignore-line sanity check
            }
        }
        return $array;
    }
    /**
     * Converts the source to an address object.
     *
     * @since 1.1.0
     *
     * @return Address
     */
    public function to_address_object(): Address
    {
        if ($this->source instanceof Address) {
            return $this->source;
        }
        return new Address($this->source['country'] ?: '', $this->source['state'] ?: '', $this->source['city'] ?: '', '', $this->source['postcode'] ?: '', '', $this->source['address_1'] ?: '', $this->source['address_2'] ?: '', $this->source['company'] ?: '', $this->source['first_name'] ?: '', '', $this->source['last_name'] ?: '');
    }
    /**
     * Converts the source to a WooCommerce billing address array.
     *
     * @since 1.1.0
     *
     * @return array<string, string>
     */
    public function to_billing_address(): array
    {
        return $this->to_array('billing');
    }
    /**
     * Converts the source to a WooCommerce shipping address array.
     *
     * @since 1.1.0
     *
     * @return array<string, string>
     */
    public function to_shipping_address(): array
    {
        return $this->to_array('shipping');
    }
    /**
     * Converts the source to a WooCommerce address array.
     *
     * @since 1.1.0
     *
     * @param ""|"billing"|"shipping" $prefix optional prefix for the keys
     * @return array<string, string>
     */
    public function to_array(string $prefix = ''): array
    {
        if (is_array($this->source)) {
            $array = ['first_name' => $this->source['given_name'] ?? '', 'last_name' => $this->source['family_name'] ?: '', 'company' => $this->source['organization'] ?: '', 'country' => $this->source['country_code'] ?: '', 'state' => $this->source['administrative_area'] ?: '', 'city' => $this->source['locality'] ?: '', 'postcode' => $this->source['postal_code'] ?: '', 'address_1' => $this->source['address_line_1'] ?: '', 'address_2' => $this->source['address_line_2'] ?: ''];
        } else {
            $array = ['first_name' => $this->source->getGivenName(), 'last_name' => $this->source->getFamilyName(), 'company' => $this->source->getOrganization(), 'country' => $this->source->getCountryCode(), 'state' => $this->source->getAdministrativeArea(), 'city' => $this->source->getLocality(), 'postcode' => $this->source->getPostalCode(), 'address_1' => $this->source->getAddressLine1(), 'address_2' => $this->source->getAddressLine2()];
        }
        return trim($prefix) === '' ? $array : array_combine(array_map(fn($key) => $prefix . '_' . $key, array_keys($array)), array_values($array));
    }
    /**
     * Formats the address to a string.
     *
     * @since 1.1.0
     *
     * @param string $separator optional separator
     * @param bool $display_country optional flag to display the country
     * @return string
     */
    public function to_string(string $separator = "\n", bool $display_country = \true): string
    {
        /** @var WC_Countries|null $formatter */
        $formatter = WC()->countries;
        // This may happen only when WooCommerce is not fully loaded yet, and WC()->countries is not initialized.
        // Ideally, this shouldn't happen because other parts of WooCommerce would also not work, however this is here to prevent errors in rare edge cases.
        if (!$formatter) {
            $filepath = \ABSPATH . 'wp-content/plugins/woocommerce/includes/class-wc-countries.php';
            if (is_readable($filepath)) {
                include_once $filepath;
                // phpcs:ignore
                $formatter = new WC_Countries();
            } else {
                return implode($separator, array_filter($this->to_array()));
            }
        }
        $force_display_base_country = fn() => $display_country;
        add_filter('woocommerce_formatted_address_force_country_display', $force_display_base_country);
        $formatted = $formatter->get_formatted_address($this->to_array(), $separator);
        remove_filter('woocommerce_formatted_address_force_country_display', $force_display_base_country);
        return $formatted;
    }
}
