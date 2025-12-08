<?php
/**
 * Customer processing order email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/plain/customer-processing-order.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates/Emails/Plain
 * @version 3.7.0
 */

defined( 'ABSPATH' ) || exit;

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

/* translators: %s: Customer first name */
echo sprintf( esc_html__( 'Hi %s,', 'lddfw' ), esc_html( $order->get_billing_first_name() ) ) . "\n\n";
/* translators: %s: Order number */
echo sprintf( esc_html__( 'Just to let you know &mdash; your order #%s delivery has failed:', 'lddfw' ), esc_html( $order->get_order_number() ) ) . "\n\n";

$lddfw_driver_id = $order->get_meta( 'lddfw_driverid' );
if ( '' !== $lddfw_driver_id ) {
	$driver = new LDDFW_Driver();
	if ( '' !== $lddfw_driver_id ) {
		echo $driver->get_driver_info__premium_only( $lddfw_driver_id, 'text' );
		echo $driver->get_vehicle_info__premium_only( $lddfw_driver_id, 'text' );
	}
}

/* driver note */
$lddfw_driver_note = $order->get_meta( 'lddfw_driver_note' );
if ( '' !== $lddfw_driver_note ) {
	echo esc_html( __( 'Driver note', 'lddfw' ) ) . ': ' . esc_html( $lddfw_driver_note ) . "\n";
}

// Signature.
$lddfw_order_signature = $order->get_meta( 'lddfw_order_last_signature' );
if ( '' !== $lddfw_order_signature ) {
	echo esc_html( __( 'Signature', 'lddfw' ) ) . "\n";
	echo '<a href="' . esc_attr( $lddfw_order_signature ) . '" target="_blank">' . esc_html( $lddfw_order_signature ) . '</a>';
}

// Display Delivery Photo(s).
$lddfw_order_delivery_image_meta = $order->get_meta( 'lddfw_order_last_delivery_image', false );
if ( ! empty( $lddfw_order_delivery_image_meta ) ) {
	// Check if this is a JSON array of images.
	if ( is_array( $lddfw_order_delivery_image_meta ) ) {
		echo esc_html( __( 'Photos', 'lddfw' ) ) . "\n";

		// Sort the meta data by ID in descending order (last added first).
		usort( $lddfw_order_delivery_image_meta, function( $a, $b ) {
			if ( ! is_a( $a, 'WC_Meta_Data' ) || ! is_a( $b, 'WC_Meta_Data' ) ) {
				return 0;
			}
			return $b->id <=> $a->id; // <-- reversed here
		});

		// Multiple images stored as WC_Meta_Data objects.
		foreach ( $lddfw_order_delivery_image_meta as $image ) {
			if ( is_a( $image, 'WC_Meta_Data' ) ) {
				$data = $image->get_data();
				if ( isset( $data['value'] ) && is_string( $data['value'] ) && filter_var( $data['value'], FILTER_VALIDATE_URL ) ) {
					echo '<a href="' . esc_attr( $data['value'] ) . '" target="_blank">' . esc_html( basename( $data['value'] ) ) . '</a>' . "\n";
				}
			}
		}
	} elseif ( is_string( $lddfw_order_delivery_image_meta ) && filter_var( $lddfw_order_delivery_image_meta, FILTER_VALIDATE_URL ) ) {
		echo esc_html( __( 'Photo', 'lddfw' ) ) . "\n";
		// Display single image (original functionality).
		echo '<a href="' . esc_attr( $lddfw_order_delivery_image_meta ) . '" target="_blank">' . esc_html( basename( $lddfw_order_delivery_image_meta ) ) . '</a>' . "\n";
	}
}

/*
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @hooked WC_Structured_Data::generate_order_data() Generates structured data.
 * @hooked WC_Structured_Data::output_structured_data() Outputs structured data.
 * @since 2.5.0
 */
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

echo "\n----------------------------------------\n\n";

/*
 * @hooked WC_Emails::order_meta() Shows order meta data.
 */
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

/*
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 */
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

echo "\n\n----------------------------------------\n\n";

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
	echo "\n\n----------------------------------------\n\n";
}

echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text', '' ) ) );
