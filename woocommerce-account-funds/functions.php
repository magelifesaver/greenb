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

use Kestrel\Account_Funds\Settings\Store_Credit_Label;

/**
 * Account Funds plugin template functions.
 *
 * Any functions in the global namespace meant for templates or third party access should be defined here or in files included here.
 *
 * @since 4.0.0
 */

defined( 'ABSPATH' ) or exit;

/**
 * Returns the store credit label for the given context.
 *
 * @since 4.0.0
 *
 * @param array<int|string, mixed>|int|numeric-string|object|"plural"|"singular"|null $context
 * @return string
 */
function wc_account_funds_store_credit_label( $context = null ) : string {

	return Store_Credit_Label::for_context( $context )->lowercase()->to_string();
}
