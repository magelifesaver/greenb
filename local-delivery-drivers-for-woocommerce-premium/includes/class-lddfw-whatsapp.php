<?php
/**
 * Plugin WHATSAPP.
 *
 * All the WHATSAPP functions.
 *
 * @package    LDDFW
 * @subpackage LDDFW/includes
 * @author     powerfulwp <apowerfulwp@gmail.com>
 */

/**
 * Plugin WHATSAPP.
 *
 * All the WHATSAPP functions.
 *
 * @package    LDDFW
 * @subpackage LDDFW/includes
 * @author     powerfulwp <apowerfulwp@gmail.com>
 */
class LDDFW_WHATSAPP {


	 /**
     * Check WhatsApp settings and parameters.
     *
     * @param string $to_number WhatsApp number.
     * @param array  $message_data Message data including Content SID and variables.
     * @return array
     */
	public function lddfw_check_whatsapp__premium_only( $to_number, $message_data  ) {
		$whatsapp_provider = get_option( 'lddfw_whatsapp_provider', '' );
		if ( '' === $whatsapp_provider ) {
			return array( 0, __( 'Failed to send WhatsApp, the WhatsApp provider is missing.', 'lddfw' ) );
		}
		if ( 'twilio' !== $whatsapp_provider ) {
			return array( 0, __( 'Failed to send WhatsApp, the WhatsApp provider is not supported.', 'lddfw' ) );
		}

		$sid = get_option( 'lddfw_whatsapp_api_sid', '' );
		if ( '' === $sid ) {
			return array( 0, __( 'Failed to send WhatsApp, the SID is missing.', 'lddfw' ) );
		}

		$auth_token = get_option( 'lddfw_whatsapp_api_auth_token', '' );
		if ( '' === $auth_token ) {
			return array( 0, __( 'Failed to send WhatsApp, the auth token is missing.', 'lddfw' ) );
		}

		$from_number = get_option( 'lddfw_whatsapp_api_phone', '' );
		if ( '' === $from_number ) {
			return array( 0, __( 'Failed to send WhatsApp, the WhatsApp phone number is missing.', 'lddfw' ) );
		}

		if ( '' === $to_number ) {
			return array( 0, __( 'Failed to send WhatsApp, the phone number is missing.', 'lddfw' ) );
		}
		if ( empty( $message_data ) || empty( $message_data['content_sid'] ) ) {
            return array( 0, __( 'Failed to send WhatsApp: the message data is incomplete.', 'lddfw' ) );
        }

		return array( 1, 'ok', 'lddfw' );
	}

	 /**
     * Send WhatsApp to customer.
     *
     * @param int    $order_id Order number.
     * @param object $order Order object.
     * @param string $order_status Order status.
     * @return array
     */
	public function lddfw_send_whatsapp_to_customer__premium_only( $order_id, $order, $order_status ) {
		$driver_id             = $order->get_meta( 'lddfw_driverid' );
		$country_code          = $order->get_billing_country();
		$customer_phone_number = $order->get_billing_phone();

		// Determine the key based on order status
        $key = '';
        if ( get_option( 'lddfw_out_for_delivery_status', '' ) === 'wc-' . $order_status ) {
            $key = 'whatsapp_out_for_delivery';
        } elseif ( get_option( 'lddfw_delivered_status', '' ) === 'wc-' . $order_status ) {
            $key = 'whatsapp_delivered';
        } elseif ( get_option( 'lddfw_failed_attempt_status', '' ) === 'wc-' . $order_status ) {
            $key = 'whatsapp_not_delivered';
        } elseif ( 'start_delivery' === $order_status ) {
            $key = 'whatsapp_start_delivery';
        }

		if ( '' === $key ) {
			return array( 0, __( 'Failed to send WhatsApp: unrecognized message type.', 'lddfw' ) );
        }

		// Check if WhatsApp sending is enabled for this message type
		$enabled = get_option( 'lddfw_' . $key, '' );
		if ( '1' !== $enabled ) {
			return array( 0, __( 'WhatsApp sending is disabled for this message type.', 'lddfw' ) );
		}

		// Get Content SID and Variables
		$content_sid = get_option( 'lddfw_' . $key . '_content_sid', '' );
		if ( '' === $content_sid ) {
			return array( 0, __( 'Failed to send WhatsApp: the Content SID is missing.', 'lddfw' ) );
		}

		$variables = get_option( 'lddfw_' . $key . '_variables', '{}' );
        if ( ! is_array( $variables ) ) {
            $variables = json_decode( $variables, true ) ?: [];
        }

		// Replace tags in variables
		foreach ( $variables as &$variable ) {
            $variable = lddfw_replace_tags__premium_only( $variable, $order_id, $order, $driver_id );
        }

		$message_data = array(
            'content_sid' => $content_sid,
            'variables'   => $variables,
        );

		$result = $this->lddfw_check_whatsapp__premium_only( $customer_phone_number, $message_data );
        if ( 0 === $result[0] ) {
            return $result;
        }
   
		$customer_phone_number = lddfw_get_international_phone_number( $country_code, $customer_phone_number );
  
		return $this->lddfw_send_whatsapp__premium_only( $message_data, $customer_phone_number );
	}

	/**
     * Send WhatsApp to driver.
     *
     * @param int    $order_id Order number.
     * @param object $order Order object.
     * @param int    $driver_id Driver user ID.
     * @return array
     */
	public function lddfw_send_whatsapp_to_driver__premium_only( $order_id, $order, $driver_id ) {
		$country_code        = get_user_meta( $driver_id, 'billing_country', true );
		$driver_phone_number = get_user_meta( $driver_id, 'billing_phone', true );
		 
		$key = 'whatsapp_assign_to_driver';

		// Check if WhatsApp sending is enabled for this message type
		$enabled = get_option( 'lddfw_' . $key, '' );
		if ( '1' !== $enabled ) {
			return array( 0, __( 'WhatsApp sending is disabled for this message type.', 'lddfw' ) );
		}

		// Get Content SID and Variables
		$content_sid = get_option( 'lddfw_' . $key . '_content_sid', '' );
		if ( '' === $content_sid ) {
			return array( 0, __( 'Failed to send WhatsApp: the Content SID is missing.', 'lddfw' ) );
		}

		$variables = get_option( 'lddfw_' . $key . '_variables', '{}' );
		if ( ! is_array( $variables ) ) {
			$variables = json_decode( $variables, true ) ?: [];
		}

		// Replace tags in variables
		foreach ( $variables as &$variable ) {
			$variable = lddfw_replace_tags__premium_only( $variable, $order_id, $order, $driver_id );
		}

		$message_data = array(
			'content_sid' => $content_sid,
			'variables'   => $variables,
		);

		$result = $this->lddfw_check_whatsapp__premium_only( $driver_phone_number, $message_data );
        if ( 0 === $result[0] ) {
            return $result;
        }

		$driver_phone_number = lddfw_get_international_phone_number( $country_code, $driver_phone_number );

		return $this->lddfw_send_whatsapp__premium_only( $message_data, $driver_phone_number );
	}

	 /**
     * Send WhatsApp message.
     *
     * @param array  $message_data Message data including Content SID and variables.
     * @param string $to_number WhatsApp phone number.
     * @return array
     */
	public function lddfw_send_whatsapp__premium_only( $message_data, $to_number ) {
		$from_number       = get_option( 'lddfw_whatsapp_api_phone', '' );
		$whatsapp_provider = get_option( 'lddfw_whatsapp_provider', '' );
		$sid               = get_option( 'lddfw_whatsapp_api_sid', '' );
		$auth_token        = get_option( 'lddfw_whatsapp_api_auth_token', '' );
		if ( 'twilio' === $whatsapp_provider ) {
			return $this->lddfw_send_whatsapp_twilio__premium_only( $message_data, $from_number, $to_number, $sid, $auth_token );
		}
	}



		/**
		 * Send WhatsApp via Twilio using Content Template.
		 *
		 * @param array  $message_data Message data including Content SID and variables.
		 * @param string $from_number WhatsApp from phone number.
		 * @param string $to_number WhatsApp to phone number.
		 * @param string $sid Twilio SID.
		 * @param string $auth_token Twilio Auth Token.
		 * @return array
		 */
		public function lddfw_send_whatsapp_twilio__premium_only( $message_data, $from_number, $to_number, $sid, $auth_token ) {

		$url  = "https://api.twilio.com/2010-04-01/Accounts/$sid/Messages.json";

		// Map variables with string indices starting from '1'
		$variables_assoc = array();
		$index = 1;
		foreach ( $message_data['variables'] as $variable ) {
			$variables_assoc[ (string) $index ] = $variable;
			$index++;
		}
 
		$data = array(
            'From'             => 'whatsapp:' . $from_number,
            'To'               => 'whatsapp:' . $to_number,
            'ContentSid'       => $message_data['content_sid'],
            'ContentVariables' => json_encode( $variables_assoc ),
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
		$json = json_decode( $return, true );

		if ( isset( $json['status'] ) && in_array( $json['status'], array( 'queued', 'accepted', 'sent', 'delivered' ), true ) ) {
            return array( 1, sprintf( __( 'WhatsApp has been sent successfully to %s', 'lddfw' ), $to_number ) );
        } else {
            $error_message = isset( $json['message'] ) ? $json['message'] : 'Unknown error';
            return array( 0, sprintf( __( 'Failed to send WhatsApp to %1$s, error: %2$s', 'lddfw' ), $to_number, $error_message ) );
        }
 
	}
}
