<?php

declare (strict_types=1);
namespace Kestrel\Store_Credit\Scoped\Kestrel\Fenix\WooCommerce;

defined('ABSPATH') or exit;
use Kestrel\Store_Credit\Scoped\CommerceGuys\Addressing\Address;
use Exception;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Collection;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Model;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Model\User;
use WC_Customer;
use WP_User;
/**
 * Object representation of a customer in WooCommerce.
 *
 * @since 1.1.0
 */
class Customer extends User
{
    /** @var string the internal model name */
    public const MODEL_NAME = 'customer';
    /** @var string */
    protected const COUNTRY_CODE_META_KEY = 'country';
    /** @var string */
    protected const ADMINISTRATIVE_AREA_META_KEY = 'state';
    /** @var string */
    protected const LOCALITY_META_KEY = 'city';
    /** @var string */
    protected const POSTAL_CODE_META_KEY = 'postcode';
    /** @var string */
    protected const ADDRESS_LINE_1_META_KEY = 'address_1';
    /** @var string */
    protected const ADDRESS_LINE_2_META_KEY = 'address_2';
    /** @var string */
    protected const ORGANIZATION_META_KEY = 'company';
    /** @var string */
    protected const GIVEN_NAME_META_KEY = 'first_name';
    /** @var string */
    protected const PHONE_META_KEY = 'phone';
    /** @var string */
    protected const EMAIL_META_KEY = 'email';
    /** @var string */
    protected const FAMILY_NAME_META_KEY = 'last_name';
    /** @var string */
    protected string $billing_email = '';
    /** @var string */
    protected string $billing_phone = '';
    /** @var string */
    protected string $shipping_phone = '';
    /**
     * Finds a customer with the given identifier.
     *
     * @since 1.1.0
     *
     * @param int|mixed|string|User|WC_Customer|WP_User $identifier
     * @return Customer|null
     */
    public static function find($identifier): ?Model
    {
        if ($identifier instanceof Customer) {
            return $identifier;
        }
        if ($identifier instanceof WC_Customer) {
            $identifier = $identifier->get_id();
        }
        $user = parent::find($identifier);
        return $user ? new self($user->to_array()) : null;
    }
    /**
     * Finds many customers by given arguments.
     *
     * @since 1.1.0
     *
     * @phpstan-param array{
     *     billing_address?: Address,
     *     billing_email?: string|list<string>,
     *     billing_phone?: string|list<string>,
     *     email?: string|list<string>,
     *     handle?: string|list<string>,
     *     id?: int|list<int>,
     *     limit?: int,
     *     meta_query?: array<int|string, array{
     *          key: string,
     *          value?: mixed,
     *          compare?: string,
     *     }>,
     *     offset?: int,
     *     page?: positive-int,
     *     search?: string,
     *     search_where?: list<string>,
     *     shipping_address?: Address,
     *     shipping_phone?: string|list<string>,
     *  } $args
     *
     * @param array<string,mixed> $args
     * @return Collection<int, Customer>
     */
    public static function find_many(array $args = []): Collection
    {
        $users = parent::find_many(static::parse_meta_query($args));
        // remap to Customer instances but retain any pagination data
        return $users->map(function (User $user) {
            return new self($user->to_array());
        })->set_pageable($users->get_pageable())->set_total_items($users->get_total_items())->set_total_pages($users->get_total_pages())->set_current_page($users->get_current_page())->set_items_per_page($users->get_items_per_page());
    }
    /**
     * Prepares the meta query for customer metadata.
     *
     * @since 1.1.0
     *
     * @param array<string, mixed> $args
     * @return array<string, mixed>
     */
    protected static function parse_meta_query(array $args = []): array
    {
        $addresses = ['billing' => $args['billing_address'] ?? null, 'shipping' => $args['shipping_address'] ?? null];
        foreach ($addresses as $address_type => $address) {
            if (!$address instanceof Address) {
                continue;
            }
            $adapter = Address_Adapter::from_object($address);
            if ('shipping' === $address_type) {
                $address = $adapter->to_shipping_address();
            } else {
                $address = $adapter->to_billing_address();
            }
            foreach ($address as $meta_key => $meta_value) {
                if (empty($meta_value)) {
                    continue;
                }
                if (!isset($args['meta_query'])) {
                    $args['meta_query'] = [];
                }
                $args['meta_query'][] = ['key' => $meta_key, 'value' => $meta_value, 'compare' => 'LIKE'];
            }
        }
        unset($args['billing_address'], $args['shipping_address']);
        $other_address_meta = ['billing_email' => $args['billing_email'] ?? null, 'billing_phone' => $args['billing_phone'] ?? null, 'shipping_phone' => $args['shipping_phone'] ?? null];
        foreach ($other_address_meta as $meta_key => $meta_value) {
            unset($args[$meta_key]);
            if (empty($meta_value)) {
                continue;
            }
            if (!isset($args['meta_query'])) {
                $args['meta_query'] = [];
            }
            $args['meta_query'][] = ['key' => $meta_key, 'value' => is_array($meta_value) ? $meta_value : (string) $meta_value, 'compare' => is_array($meta_value) ? 'IN' : 'LIKE'];
        }
        if (isset($args['meta_query']) && count((array) $args['meta_query']) > 1 && !isset($args['meta_query']['relation'])) {
            $args['meta_query']['relation'] = 'AND';
        }
        return $args;
    }
    /**
     * Gets the customer billing phone.
     *
     * @since 1.1.0
     *
     * @return string
     */
    public function get_billing_phone(): string
    {
        return $this->get_meta('billing_' . static::PHONE_META_KEY, '');
    }
    /**
     * Sets the customer billing phone.
     *
     * @since 1.1.0
     *
     * @param string $phone
     * @return $this
     */
    public function set_billing_phone(string $phone): Customer
    {
        $this->billing_phone = $phone;
        return $this->set_meta('billing_' . static::PHONE_META_KEY, $this->billing_phone);
    }
    /**
     * Gets the customer shipping phone.
     *
     * @since 1.1.0
     *
     * @return string
     */
    public function get_shipping_phone(): string
    {
        return $this->get_meta('shipping_' . static::PHONE_META_KEY, '');
    }
    /**
     * Sets the customer shipping phone.
     *
     * @since 1.1.0
     *
     * @param string $phone
     * @return $this
     */
    public function set_shipping_phone(string $phone): Customer
    {
        $this->shipping_phone = $phone;
        return $this->set_meta('shipping_' . static::PHONE_META_KEY, $this->shipping_phone);
    }
    /**
     * Gets the customer billing email address.
     *
     * @since 1.1.0
     *
     * @return non-empty-string defaults to user email
     */
    public function get_billing_email(): string
    {
        return $this->get_meta('billing_' . static::EMAIL_META_KEY, $this->get_email());
    }
    /**
     * Sets the customer billing email address.
     *
     * @since 1.1.0
     *
     * @param string $email
     * @return $this
     */
    public function set_billing_email(string $email): Customer
    {
        $this->billing_email = $email;
        return $this->set_meta('billing_' . static::EMAIL_META_KEY, $this->billing_email);
    }
    /**
     * Gets a customer billing or shipping address.
     *
     * @since 1.1.0
     *
     * @param string $which
     * @return Address
     */
    protected function get_address(string $which): Address
    {
        $prefix = 'shipping' === $which ? 'shipping_' : 'billing_';
        $country_code = $this->get_meta($prefix . static::COUNTRY_CODE_META_KEY, '');
        $administrative_area = $this->get_meta($prefix . static::ADMINISTRATIVE_AREA_META_KEY, '');
        $locality = $this->get_meta($prefix . static::LOCALITY_META_KEY, '');
        $postal_code = $this->get_meta($prefix . static::POSTAL_CODE_META_KEY, '');
        $address_line_1 = $this->get_meta($prefix . static::ADDRESS_LINE_1_META_KEY, '');
        $address_line_2 = $this->get_meta($prefix . static::ADDRESS_LINE_2_META_KEY, '');
        $organization = $this->get_meta($prefix . static::ORGANIZATION_META_KEY, '');
        $given_name = $this->get_meta($prefix . static::GIVEN_NAME_META_KEY, '');
        $family_name = $this->get_meta($prefix . static::FAMILY_NAME_META_KEY, '');
        return new Address($country_code, $administrative_area, $locality, '', $postal_code, '', $address_line_1, $address_line_2, $organization, $given_name, '', $family_name);
    }
    /**
     * Gets the customer billing address.
     *
     * @since 1.1.0
     *
     * @return Address
     */
    public function get_billing_address(): Address
    {
        return $this->get_address('billing');
    }
    /**
     * Sets the customer billing address.
     *
     * @since 1.1.0
     *
     * @param Address $address
     * @return $this
     */
    public function set_billing_address(Address $address): Customer
    {
        $this->set_meta('billing_' . static::COUNTRY_CODE_META_KEY, (string) $address->getCountryCode());
        $this->set_meta('billing_' . static::ADMINISTRATIVE_AREA_META_KEY, (string) $address->getAdministrativeArea());
        $this->set_meta('billing_' . static::LOCALITY_META_KEY, (string) $address->getLocality());
        $this->set_meta('billing_' . static::POSTAL_CODE_META_KEY, (string) $address->getPostalCode());
        $this->set_meta('billing_' . static::ADDRESS_LINE_1_META_KEY, (string) $address->getAddressLine1());
        $this->set_meta('billing_' . static::ADDRESS_LINE_2_META_KEY, (string) $address->getAddressLine2());
        $this->set_meta('billing_' . static::ORGANIZATION_META_KEY, (string) $address->getOrganization());
        $this->set_meta('billing_' . static::GIVEN_NAME_META_KEY, (string) $address->getGivenName());
        $this->set_meta('billing_' . static::FAMILY_NAME_META_KEY, (string) $address->getFamilyName());
        return $this;
    }
    /**
     * Gets the customer shipping address.
     *
     * @since 1.1.0
     *
     * @return Address
     */
    public function get_shipping_address(): Address
    {
        return $this->get_address('shipping');
    }
    /**
     * Sets the customer shipping address.
     *
     * @since 1.1.0
     *
     * @param Address $address
     * @return $this
     */
    public function set_shipping_address(Address $address): Customer
    {
        $this->set_meta('shipping_' . static::COUNTRY_CODE_META_KEY, (string) $address->getCountryCode());
        $this->set_meta('shipping_' . static::ADMINISTRATIVE_AREA_META_KEY, (string) $address->getAdministrativeArea());
        $this->set_meta('shipping_' . static::LOCALITY_META_KEY, (string) $address->getLocality());
        $this->set_meta('shipping_' . static::POSTAL_CODE_META_KEY, (string) $address->getPostalCode());
        $this->set_meta('shipping_' . static::ADDRESS_LINE_1_META_KEY, (string) $address->getAddressLine1());
        $this->set_meta('shipping_' . static::ADDRESS_LINE_2_META_KEY, (string) $address->getAddressLine2());
        $this->set_meta('shipping_' . static::ORGANIZATION_META_KEY, (string) $address->getOrganization());
        $this->set_meta('shipping_' . static::GIVEN_NAME_META_KEY, (string) $address->getGivenName());
        $this->set_meta('shipping_' . static::FAMILY_NAME_META_KEY, (string) $address->getFamilyName());
        return $this;
    }
    /**
     * Returns the current member instance as a WooCommerce customer object.
     *
     * @since 1.1.0
     *
     * @return WC_Customer
     */
    public function as_woocommerce_customer(): WC_Customer
    {
        try {
            $customer = new WC_Customer($this->get_id());
        } catch (Exception $exception) {
            $customer = new WC_Customer();
            // @phpstan-ignore-line does not throw again at this point
            $customer->set_id($this->get_id());
        }
        $customer->set_email($this->get_email());
        $customer->set_first_name($this->get_given_name());
        $customer->set_last_name($this->get_family_name());
        $customer->set_display_name($this->get_display_name());
        $addresses = ['billing' => $this->get_billing_address(), 'shipping' => $this->get_shipping_address()];
        foreach ($addresses as $address_type => $address) {
            $customer->{"set_{$address_type}_country"}($address->getCountryCode());
            $customer->{"set_{$address_type}_state"}($address->getAdministrativeArea());
            $customer->{"set_{$address_type}_city"}($address->getLocality());
            $customer->{"set_{$address_type}_postcode"}($address->getPostalCode());
            $customer->{"set_{$address_type}_address_1"}($address->getAddressLine1());
            $customer->{"set_{$address_type}_address_2"}($address->getAddressLine2());
            $customer->{"set_{$address_type}_company"}($address->getOrganization());
            $customer->{"set_{$address_type}_first_name"}($address->getGivenName());
            $customer->{"set_{$address_type}_last_name"}($address->getFamilyName());
        }
        $customer->set_billing_email($this->get_billing_email());
        $customer->set_billing_phone($this->get_billing_phone());
        $customer->set_shipping_phone($this->get_shipping_phone());
        return $customer;
    }
    /**
     * Returns the customer as an array.
     *
     * @since 1.1.0
     *
     * @return array<string, mixed>
     */
    public function to_array(): array
    {
        $array = parent::to_array();
        $addresses = ['billing' => $this->get_billing_address(), 'shipping' => $this->get_shipping_address()];
        $array['addresses'] = ['billing' => [], 'shipping' => []];
        /** @var Address $address */
        foreach ($addresses as $address_type => $address) {
            $given_name = $address->getGivenName();
            $family_name = $address->getFamilyName();
            $array['addresses'][$address_type] = ['country_code' => $address->getCountryCode(), 'administrative_area' => $address->getAdministrativeArea(), 'locality' => $address->getLocality(), 'postal_code' => $address->getPostalCode(), 'address_line_1' => $address->getAddressLine1(), 'address_line_2' => $address->getAddressLine2(), 'organization' => $address->getOrganization(), 'given_name' => $given_name, 'family_name' => $family_name, 'full_name' => $this->format_full_name($given_name, $family_name), 'formatted' => Address_Adapter::from_object($address)->to_string()];
        }
        return $array;
    }
}
