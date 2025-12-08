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

namespace Kestrel\Account_Funds;

defined( 'ABSPATH' ) or exit;

use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Traits\Is_Handler;
use Kestrel\Account_Funds\Settings\Store_Credit_Account_Top_Up;

/**
 * Store credit top-up handler.
 *
 * @since 3.2.0
 * @deprecated 4.0.0
 */
final class Top_Up {
	use Is_Handler;

	/**
	 * Returns the top-up image data.
	 *
	 * @since 3.2.0
	 * @deprecated 4.0.0
	 *
	 * @return array<int, object{
	 *     id: int,
	 *     src: string,
	 *     thumbnail: string,
	 *     srcset: string,
	 *     sizes: string,
	 *     name: string,
	 *     alt: string
	 * }>
	 */
	public static function image_data() : array {

		wc_deprecated_function( __METHOD__, '4.0.0', Store_Credit_Account_Top_Up::class . '::image_data()' );

		return Store_Credit_Account_Top_Up::image_data();
	}

	/**
	 * Returns the top-up image HTML.
	 *
	 * @since 3.2.0
	 * @deprecated 4.0.0
	 *
	 * @return string
	 */
	public static function image_html() : string {

		wc_deprecated_function( __METHOD__, '4.0.0', Store_Credit_Account_Top_Up::class . '::image_html()' );

		return Store_Credit_Account_Top_Up::image_html();
	}

	/**
	 * Returns the top-up image URL.
	 *
	 * @since 3.2.0
	 * @deprecated 4.0.0
	 *
	 * @return string
	 */
	public static function image_url() : string {

		wc_deprecated_function( __METHOD__, '4.0.0', Store_Credit_Account_Top_Up::class . '::image_url()' );

		return Store_Credit_Account_Top_Up::image_url();
	}

}
