<?php
/**
 * Account Funds for WooCommerce
 *
 * This source file is subject to the GNU General Public License v3.0 that is bundled with this plugin in the file license.txt.
 *
 * Please do not modify this file if you want to upgrade this plugin to newer versions in the future.
 * If you want to customize this file for your needs, please review our developer documentation.
 * Join our developer program at https://kestrelwp.com/developers
 *
 * @author    Kestrel
 * @copyright Copyright (c) 2015-2025 Kestrel Commerce LLC [hey@kestrelwp.com]
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) or exit;

use Kestrel\Account_Funds\Products\Data_Stores\Store_Credit_Top_Up_Post_Type;
use Kestrel\Account_Funds\Products\Store_Credit_Deposit;
use Kestrel\Account_Funds\Products\Store_Credit_Top_Up;

/**
 * This file exists for backwards compatibility with WooCommerce.
 *
 * Custom product types in WooCommerce are hardcoded in a way that PSR autoloading cannot exist.
 * The product type has to match a class name pattern like `WC_Product_<Product_Type>`.
 * The `<Product_Type>` will be mapped to the custom type as a snake-case string.
 *
 * So, we actually use {@see Store_Credit_Deposit} and {@see Store_Credit_Top_Up} however we extend those files here to please WooCommerce internals.
 *
 * @NOTE This file shouldn't be extended beyond the class names declaration and actual logic should go into the parent classes.
 *
 * @since 4.0.0
 */

// phpcs:disable

/**
 * @see Store_Credit_Deposit
 *
 * @phpstan-ignore-next-line
 */
final class WC_Product_Deposit extends Store_Credit_Deposit {

}

/**
 * @see Store_Credit_Top_Up
 *
 * @phpstan-ignore-next-line
 */
final class WC_Product_Topup extends Store_Credit_Top_Up {

}

/**
 * @see Store_Credit_Top_Up_Post_Type
 *
 * @phpstan-ignore-next-line
 */
final class WC_Product_Topup_Data_Store extends Store_Credit_Top_Up_Post_Type {

}

// phpcs:enable
