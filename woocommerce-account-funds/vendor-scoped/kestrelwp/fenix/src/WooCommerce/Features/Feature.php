<?php

declare (strict_types=1);
namespace Kestrel\Account_Funds\Scoped\Kestrel\Fenix\WooCommerce\Features;

use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Traits\Is_Enum;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\WooCommerce\Extension;
defined('ABSPATH') or exit;
/**
 * WooCommerce feature.
 *
 * This class can list specific features a WooCommerce extension plugin may support.
 *
 * @see Extension::__construct()
 * @see Extension::declare_features_compatibility()
 *
 * @since 1.0.0
 */
class Feature
{
    use Is_Enum;
    /** @var string cart and checkout blocks */
    public const CART_CHECKOUT_BLOCKS = 'cart_checkout_blocks';
    /** @var string high performance order tables */
    public const HPOS = 'custom_order_tables';
}
