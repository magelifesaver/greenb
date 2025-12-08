<?php

declare (strict_types=1);
namespace Kestrel\Account_Funds\Scoped\Kestrel\Fenix\WooCommerce;

defined('ABSPATH') or exit;
use Exception;
use InvalidArgumentException;
/**
 * WooCommerce currency helper class.
 *
 * @since 1.6.0
 */
final class Currency
{
    /** @var string */
    private string $code;
    /** @var array<string, string>|null */
    private static ?array $currencies = null;
    /** @var array<string, string>|null */
    private static ?array $symbols = null;
    /**
     * Constructor.
     *
     * @since 1.6.0
     *
     * @link https://cldr.unicode.org/translation/currency-names-and-symbols uses CLDR standards
     *
     * @param string $currency_code 3-letter currency code (e.g., 'USD', 'EUR')
     * @throws InvalidArgumentException if the currency code is invalid
     */
    public function __construct(string $currency_code)
    {
        $currencies = self::list();
        $code = isset($currencies[strtoupper($currency_code)]) ? strtoupper($currency_code) : null;
        if ($code === null) {
            throw new InvalidArgumentException(esc_html(sprintf('Invalid currency code: "%s".', $currency_code)));
        }
        $this->code = $code;
    }
    /**
     * Returns the list of available currencies.
     *
     * @since 1.6.0
     *
     * @return array<string, string> associative array of currency codes and their names
     */
    public static function list(): array
    {
        if (self::$currencies === null) {
            self::$currencies = get_woocommerce_currencies();
        }
        return self::$currencies;
    }
    /**
     * Returns the list of available currency symbols.
     *
     * @since 1.6.0
     *
     * @return array<string, string> associative array of currency codes and their symbols
     */
    public static function symbols(): array
    {
        if (self::$symbols === null) {
            self::$symbols = get_woocommerce_currency_symbols();
        }
        return self::$symbols;
    }
    /**
     * Returns the currency code.
     *
     * @since 1.6.0
     *
     * @return string
     */
    public function code(): string
    {
        return $this->code;
    }
    /**
     * Returns the currency name.
     *
     * @since 1.6.0
     *
     * @return string
     */
    public function name(): string
    {
        $currencies = self::list();
        return $currencies[$this->code] ?? '';
    }
    /**
     * Returns the currency symbol.
     *
     * @since 1.6.0
     *
     * @return string
     */
    public function symbol(): string
    {
        $symbols = self::symbols();
        // run through internal WooCommerce filter to allow modification of the currency symbol
        return apply_filters('woocommerce_currency_symbol', $symbols[$this->code] ?? '', $this->code);
        // phpcs:ignore
    }
    /**
     * Implements the magic method to return instances of self mapped to the currency code.
     *
     * @since 1.7.1
     *
     * @param string $name
     * @param array<mixed> $arguments
     * @return Currency|mixed
     * @throws Exception
     */
    public static function __callStatic(string $name, array $arguments)
    {
        $currencies = self::list();
        if (isset($currencies[strtoupper($name)])) {
            return new self(strtoupper($name));
        }
        throw new Exception(esc_html(sprintf('Call to undefined method: %s::%s().', self::class, $name)));
    }
}
