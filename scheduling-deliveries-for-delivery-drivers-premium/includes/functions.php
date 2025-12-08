<?php
use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * Determines whether HPOS is enabled.
 *
 * @return bool
 */
function sdfdd_is_hpos_enabled() : bool {
	if ( version_compare( get_option( 'woocommerce_version' ), '7.1.0' ) < 0 ) {
		return false;
	}

	if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
		return true;
	}

	return false;
}

/**
 * Get the formatted delivery date and time for an order.
 *
 * Retrieves the delivery date from the order metadata and formats it according to the site's date format.
 *
 * @param WC_Order $order The order object.
 * @return string|void The formatted delivery date or nothing if the date is not set.
 */
function sdfdd_get_formatted_order_delivery_date_time( $order ) {
	$date_format = lddfw_date_format( 'date' );
	$time_format = lddfw_date_format( 'time' );

	$delivery_date = $order->get_meta( '_lddfw_delivery_date' );
	$delivery_time = $order->get_meta( '_lddfw_delivery_time' );

	$time_parts = explode( ' - ', $delivery_time );

	// Assign the parts to the respective variables.
	$from_time = isset( $time_parts[0] ) ? trim( $time_parts[0] ) : '';
	$to_time   = isset( $time_parts[1] ) ? trim( $time_parts[1] ) : '';

	if ( empty( $delivery_date ) ) {
		return;
	}

	// Format the delivery date
	$formatted_date = date( $date_format, strtotime( $delivery_date ) );

	// Format the delivery time range
	$formatted_time = '';

	if ( ! empty( $from_time ) && ! empty( $to_time ) ) {
		// Both from and to time are available
		$formatted_time = ' ' . esc_html__( 'at', 'scheduling-deliveries-for-delivery-drivers' ) . ' ' . date( $time_format, strtotime( $from_time ) ) . ' - ' . date( $time_format, strtotime( $to_time ) );
	} elseif ( ! empty( $from_time ) && empty( $to_time ) ) {
		// Only from time is available
		$formatted_time = ' ' . esc_html__( 'starting at', 'scheduling-deliveries-for-delivery-drivers' ) . ' ' . date( $time_format, strtotime( $from_time ) );
	} elseif ( empty( $from_time ) && ! empty( $to_time ) ) {
		// Only to time is available
		$formatted_time = ' ' . esc_html__( 'until', 'scheduling-deliveries-for-delivery-drivers' ) . ' ' . date( $time_format, strtotime( $to_time ) );
	}

	// Return the formatted delivery date and time
	return $formatted_date . $formatted_time;
}


/**
 * Get delivery date with label.
 *
 * Combines the delivery date with its label for display.
 *
 * @param string $delivery_date The formatted delivery date.
 * @return string The delivery date with the label.
 */
function sdfdd_get_labeled_delivery_date( $delivery_date ) {
	return esc_html( __( 'Delivery date', 'scheduling-deliveries-for-delivery-drivers' ) ) . ': ' . $delivery_date;
}

function sdfdd_get_delivery_date_input( $id, $value, $placeholder ) {
	echo '<input type="text" placeholder="' . esc_attr( $placeholder ) . '" id="' . $id . '" name="' . $id . '" value="' . esc_attr( $value ) . '" />';
}
function sdfdd_get_delivery_time_select( $id, $value, $placeholder ) {
	$time_format = get_option( 'time_format', 'H:i' );
	echo '<select id="' . $id . '" name="' . $id . '">
	<option value="">' . esc_html( $placeholder ) . '</option>';
	for ( $i = 0; $i < 24; $i++ ) {

		// Create a DateTime object for each hour
		$date_time = new DateTime();
		$date_time->setTime( $i, 0 ); // Set the time to the current hour in the loop

		// Format the time according to the WordPress time format setting
		$formatted_time = $date_time->format( $time_format );

		$time = sprintf( '%02d:00', $i );
		echo '<option value="' . esc_attr( $time ) . '" ' . selected( $value, $time, false ) . '>' . esc_html( $formatted_time ) . '</option>';
	}
	echo '</select>';
}
