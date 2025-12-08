<?php

declare (strict_types=1);
namespace Kestrel\Account_Funds\Scoped\Kestrel\Fenix\WooCommerce;

defined('ABSPATH') or exit;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Traits\Has_Plugin_Instance;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Traits\Is_Singleton;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\WooCommerce\Contracts\WooCommerce_Extension;
use WC_Payment_Gateway;
/**
 * Base class for WooCommerce payment gateways.
 *
 * @since 1.7.0
 */
abstract class Gateway extends WC_Payment_Gateway
{
    use Has_Plugin_Instance;
    use Is_Singleton;
    /**
     * Payment gateway constructor.
     *
     * @since 1.7.0
     *
     * @param WooCommerce_Extension|null $plugin
     */
    public function __construct(?WooCommerce_Extension $plugin = null)
    {
        if (!static::$plugin && $plugin) {
            static::$plugin = $plugin;
        }
    }
}
