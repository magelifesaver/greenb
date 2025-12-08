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

namespace Kestrel\Account_Funds\Store_Credit\Data_Stores\Traits;

defined( 'ABSPATH' ) or exit;

/**
 * Internal trait for query helper methods.
 *
 * @since 4.0.0
 */
trait Has_Query_Helpers {

	/**
	 * Returns a processed string value for a query argument.
	 *
	 * @since 4.0.0
	 *
	 * @param string $column
	 * @param string|string[] $value
	 * @return string
	 */
	protected function process_string_query( string $column, $value ) : string {

		if ( is_array( $value ) ) {
			$query = $column . ' IN (%s)';
		} else {
			$query = $column . ' = \'%s\'';
		}

		return $query;
	}

	/**
	 * Returns a processed string value for a query argument.
	 *
	 * @since 4.0.0
	 *
	 * @param mixed|string|string[] $arg
	 * @return string
	 */
	protected function process_string_value( $arg ) : string {

		if ( is_array( $arg ) ) {
			$value = esc_sql( implode( ',', $arg ) );
		} else {
			$value = is_string( $arg ) ? esc_sql( $arg ) : '';
		}

		return $value;
	}

	/**
	 * Returns a prepared clause for an integer argument.
	 *
	 * @since 4.0.0
	 *
	 * @param string $column
	 * @param int|int[]|mixed $arg
	 * @return string
	 */
	protected function process_integer_query( string $column, $arg ) : string {

		if ( is_array( $arg ) ) {
			$query = $column . ' IN (%s)';
		} else {
			$query = $column . ' = %d';
		}

		return $query;
	}

	/**
	 * Returns a processed integer value for a query argument.
	 *
	 * @since 4.0.0
	 *
	 * @param int|int[]|mixed $arg
	 * @return int|string
	 */
	protected function process_integer_value( $arg ) {

		if ( is_array( $arg ) ) {
			$value = esc_sql( implode( ',', array_unique( array_map( function( $item ) {
				return is_numeric( $item ) ? intval( $item ) : 0;
			}, $arg ) ) ) );
		} else {
			$value = is_numeric( $arg ) ? intval( $arg ) : 0;
		}

		return $value;
	}

}
