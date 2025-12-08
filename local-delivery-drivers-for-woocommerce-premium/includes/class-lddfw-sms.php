<?php
/**
 * Plugin SMS.
 *
 * All the SMS functions.
 *
 * @package    LDDFW
 * @subpackage LDDFW/includes
 * @author     powerfulwp <apowerfulwp@gmail.com>
 */

/**
 * Plugin SMS.
 *
 * All the SMS functions.
 *
 * @package    LDDFW
 * @subpackage LDDFW/includes
 * @author     powerfulwp <apowerfulwp@gmail.com>
 */
class LDDFW_SMS {


	/**
	 * Check sms
	 *
	 * @param string $to_number sms number.
	 * @param string $sms_text sms content.
	 * @return array
	 */
	public function lddfw_check_sms__premium_only( $to_number, $sms_text ) {
		$sms_provider = get_option( 'lddfw_sms_provider', '' );
		if ( '' === $sms_provider ) {
			return array( 0, __( 'Failed to send SMS, the SMS provider is missing.', 'lddfw' ) );
		}
		if ( 'twilio' !== $sms_provider ) {
			return array( 0, __( 'Failed to send SMS, the SMS provider is not supported.', 'lddfw' ) );
		}

		$sid = get_option( 'lddfw_sms_api_sid', '' );
		if ( '' === $sid ) {
			return array( 0, __( 'Failed to send SMS, the SID is missing.', 'lddfw' ) );
		}

		$auth_token = get_option( 'lddfw_sms_api_auth_token', '' );
		if ( '' === $auth_token ) {
			return array( 0, __( 'Failed to send SMS, the auth token is missing.', 'lddfw' ) );
		}

		$from_number = get_option( 'lddfw_sms_api_phone', '' );
		if ( '' === $from_number ) {
			return array( 0, __( 'Failed to send SMS, the SMS phone number is missing.', 'lddfw' ) );
		}

		if ( '' === $to_number ) {
			return array( 0, __( 'Failed to send SMS, the phone number is missing.', 'lddfw' ) );
		}
		if ( '' === $sms_text ) {
			return array( 0, __( 'Failed to send SMS, the SMS text is missing.', 'lddfw' ) );
		}

		return array( 1, 'ok', 'lddfw' );
	}

	/**
	 * Sens sms to customer
	 *
	 * @param int    $order_id order number.
	 * @param object $order order object.
	 * @param int    $order_status order status.
	 * @return array
	 */
	public function lddfw_send_sms_to_customer__premium_only( $order_id, $order, $order_status ) {
		$driver_id             = $order->get_meta( 'lddfw_driverid' );
		$country_code          = $order->get_billing_country();
		$customer_phone_number = $order->get_billing_phone();

		$sms_text = '';
		if ( get_option( 'lddfw_out_for_delivery_status', '' ) === 'wc-' . $order_status ) {
			$sms_text = get_option( 'lddfw_sms_out_for_delivery_template', '' );
		}

		if ( get_option( 'lddfw_delivered_status', '' ) === 'wc-' . $order_status ) {
			$sms_text = get_option( 'lddfw_sms_delivered_template', '' );
		}

		if ( get_option( 'lddfw_failed_attempt_status', '' ) === 'wc-' . $order_status ) {
			$sms_text = get_option( 'lddfw_sms_not_delivered_template', '' );
		}

		if ( 'start_delivery' === $order_status ) {
			$sms_text = get_option( 'lddfw_sms_start_delivery_template', '' );
		}

		$result = $this->lddfw_check_sms__premium_only( $customer_phone_number, $sms_text );
		if ( 0 === $result[0] ) {
			return $result;
		}

		$customer_phone_number = lddfw_get_international_phone_number( $country_code, $customer_phone_number );

		$sms_text = lddfw_replace_tags__premium_only( $sms_text, $order_id, $order, $driver_id );

		return $this->lddfw_send_sms__premium_only( $sms_text, $customer_phone_number );
	}

	/**
	 * Send sms to driver
	 *
	 * @param int    $order_id order number.
	 * @param object $order order object.
	 * @param int    $driver_id user id number.
	 * @return array
	 */
	public function lddfw_send_sms_to_driver__premium_only( $order_id, $order, $driver_id ) {
		$country_code        = get_user_meta( $driver_id, 'billing_country', true );
		$driver_phone_number = get_user_meta( $driver_id, 'billing_phone', true );
		$sms_text            = get_option( 'lddfw_sms_assign_to_driver_template', '' );

		$result = $this->lddfw_check_sms__premium_only( $driver_phone_number, $sms_text );
		if ( 0 === $result[0] ) {
			return $result;
		}

		$driver_phone_number = lddfw_get_international_phone_number( $country_code, $driver_phone_number );
		$sms_text            = lddfw_replace_tags__premium_only( $sms_text, $order_id, $order, $driver_id );
		return $this->lddfw_send_sms__premium_only( $sms_text, $driver_phone_number );
	}
	/**
	 * Sens sms
	 *
	 * @param string $sms_text sms text.
	 * @param string $to_number sms phone number.
	 * @return array
	 */
	public function lddfw_send_sms__premium_only( $sms_text, $to_number ) {
		$from_number  = get_option( 'lddfw_sms_api_phone', '' );
		$sms_provider = get_option( 'lddfw_sms_provider', '' );
		$sid          = get_option( 'lddfw_sms_api_sid', '' );
		$auth_token   = get_option( 'lddfw_sms_api_auth_token', '' );
		if ( 'twilio' === $sms_provider ) {
			return $this->lddfw_send_sms_twilio__premium_only( $sms_text, $from_number, $to_number, $sid, $auth_token );
		}
	}

	/**
	 * Sens sms by twilio
	 *
	 * @param string $sms_text sms text.
	 * @param string $from_number sms from phone number.
	 * @param string $to_number sms to phone number.
	 * @param string $sid sid number.
	 * @param string $auth_token token.
	 * @return array
	 */
	public function lddfw_send_sms_twilio__premium_only( $sms_text, $from_number, $to_number, $sid, $auth_token ) {

		$url  = "https://api.twilio.com/2010-04-01/Accounts/$sid/Messages.json";
		$data = array(
			'From' => $from_number,
			'To'   => $to_number,
			'Body' => $sms_text,
		);
		$post = http_build_query( $data );
		$ch   = curl_init( $url );
		curl_setopt( $ch, CURLOPT_POST, true );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC );
		curl_setopt( $ch, CURLOPT_USERPWD, "$sid:$auth_token" );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $post );
		$return = curl_exec( $ch );
		curl_close( $ch );

		$data = json_decode( $return, true );

		if ( ( ! empty( $data['status'] ) ) && 'queued' === strval( $data['status'] ) ) {
			/* translators: %s: phone number */
			return array( 1, sprintf( __( 'SMS has been sent successfully to %s', 'lddfw' ), $to_number ) );
		} else {
			/* translators: %s: phone number */
			return array( 0, sprintf( __( 'Failed to send SMS to %s ' . $data['message'], 'lddfw' ), $to_number ) );
		}
	}
}
