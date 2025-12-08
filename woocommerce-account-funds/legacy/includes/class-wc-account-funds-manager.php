<?php

defined( 'ABSPATH' ) or exit;

/**
 * Account funds handler.
 *
 * @since 2.7.0
 * @deprecated 2.7.0
 */
class WC_Account_Funds_Manager {

	/**
	 * Gets the user's funds.
	 *
	 * @since 2.7.0
	 * @deprecated 2.7.0
	 *
	 * @param int|mixed $user_id user ID
	 * @return float
	 */
	public static function get_user_funds( $user_id ) : float {

		if ( ! is_numeric( $user_id ) || ! intval( $user_id ) ) {
			return 0.0;
		}

		wc_deprecated_function( __METHOD__, '4.0.0' );

		return \Kestrel\Account_Funds\Users::get_user_available_funds( (int) $user_id, false );
	}

	/**
	 * Sets the user's funds to the specified amount.
	 *
	 * @since 2.7.0
	 * @deprecated 2.7.0
	 *
	 * @return void
	 */
	public static function set_user_funds() : void {

		wc_deprecated_function( __METHOD__, '2.7.0' );
	}

	/**
	 * Increases the user's funds.
	 *
	 * @since 2.7.0
	 * @deprecated 2.7.0
	 *
	 * @param mixed|int $user_id user ID
	 * @param float|mixed $amount funds amount
	 * @param mixed|string $event event name
	 * @return void
	 */
	public static function increase_user_funds( $user_id, $amount, string $event ) : void {

		if ( ! is_numeric( $amount ) || ! is_numeric( $user_id ) ) {
			return;
		}

		wc_deprecated_function( __METHOD__, '4.0.0' );

		\Kestrel\Account_Funds\Users::increase_user_funds( (int) $user_id, (float) $amount, $event );
	}

	/**
	 * Decreases the user's funds.
	 *
	 * @since 2.7.0
	 * @deprecated 2.7.0
	 *
	 * @param mixed|int $user_id user ID
	 * @param float|mixed $amount funds amount
	 * @param mixed|string $event event name
	 * @return void
	 */
	public static function decrease_user_funds( $user_id, $amount, string $event ) : void {

		if ( ! is_numeric( $amount ) || ! is_numeric( $user_id ) ) {
			return;
		}

		wc_deprecated_function( __METHOD__, '4.0.0' );

		\Kestrel\Account_Funds\Users::decrease_user_funds( (int) $user_id, (float) $amount, $event );
	}

}
