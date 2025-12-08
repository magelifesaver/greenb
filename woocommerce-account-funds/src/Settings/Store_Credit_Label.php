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

namespace Kestrel\Account_Funds\Settings;

defined( 'ABSPATH' ) or exit;

use ArrayAccess;
use Countable;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Helpers\Strings;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Settings\Setting;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Settings\Settings_Group;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Settings\Stores\Option;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Settings\Types\Text;

/**
 * Handles the settings to define the plural and singular names of store credit when displayed to customers.
 *
 * @since 4.0.0
 */
final class Store_Credit_Label extends Settings_Group {

	/** @var string */
	protected const SETTING_NAME = 'store_credit_label';

	/** @var string */
	private const SINGULAR_SETTING_NAME = 'singular';

	/** @var string */
	private const PLURAL_SETTING_NAME = 'plural';

	/**
	 * Constructor.
	 *
	 * @since 4.0.0
	 */
	protected function __construct() {

		$default = self::default();

		parent::__construct(
			new Option( self::plugin()->key( self::SETTING_NAME ), false ),
			[
				self::SINGULAR_SETTING_NAME => new Setting( [
					'name'         => self::SINGULAR_SETTING_NAME,
					'title'        => __( 'Singular label', 'woocommerce-account-funds' ),
					'instructions' => __( 'The label shown to customers when referring to store credit in its singular form.', 'woocommerce-account-funds' ),
					'placeholder'  => $default,
					'default'      => $default,
					'type'         => new Text(),
				] ),
				self::PLURAL_SETTING_NAME   => new Setting( [
					'name'         => self::PLURAL_SETTING_NAME,
					'title'        => __( 'Plural label', 'woocommerce-account-funds' ),
					'instructions' => __( 'The label shown to customers when referring to store credit in its plural form.', 'woocommerce-account-funds' ),
					'placeholder'  => $default,
					'default'      => $default,
					'type'         => new Text(),
				] ),
			]
		);
	}

	/**
	 * Returns the default label for store credit.
	 *
	 * @since 4.0.0
	 *
	 * @return string
	 */
	private static function default() : string {

		return __( 'Store credit', 'woocommerce-account-funds' );
	}

	/**
	 * Returns the singular name of store credit.
	 *
	 * @since 4.0.0
	 *
	 * @return Strings object
	 */
	public static function singular() : Strings {

		return Strings::string( self::instance()->get_setting( self::SINGULAR_SETTING_NAME )->get_value() ?: self::default() );
	}

	/**
	 * Returns the plural name of store credit.
	 *
	 * @since 4.0.0
	 *
	 * @return Strings object
	 */
	public static function plural() : Strings {

		return Strings::string( self::instance()->get_setting( self::PLURAL_SETTING_NAME )->get_value() ?: self::default() );
	}

	/**
	 * Returns the store credit label based on the provided countable context.
	 *
	 * @since 4.0.0
	 *
	 * @param mixed $context
	 * @return Strings
	 */
	public static function for_context( $context ) : Strings {

		if ( is_numeric( $context ) ) {
			$count = absint( $context );
		} elseif ( is_array( $context ) || $context instanceof ArrayAccess || $context instanceof Countable ) {
			$count = count( $context );
		} elseif ( 'plural' === $context ) {
			$count = 2;
		} elseif ( 'singular' === $context ) {
			$count = 1;
		} else {
			$count = 0;
		}

		return ( $count <= 1 )
			? self::singular()
			: self::plural();
	}

}
