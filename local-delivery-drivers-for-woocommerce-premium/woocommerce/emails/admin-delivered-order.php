<?php
/**
 * Customer processing order email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/customer-processing-order.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates/Emails
 * @version 3.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<?php /* translators: %s: Order number */ ?>
<p><?php printf( esc_html__( 'Order #%s has been delivered:', 'lddfw' ), esc_html( $order->get_order_number() ) ); ?></p>

<?php
$lddfw_driver_id = $order->get_meta( 'lddfw_driverid' );
if ( '' !== $lddfw_driver_id ) {
	$driver = new LDDFW_Driver();
	if ( '' !== $lddfw_driver_id ) {
		echo $driver->get_driver_info__premium_only( $lddfw_driver_id, 'html' );
		echo $driver->get_vehicle_info__premium_only( $lddfw_driver_id, 'html' );
	}
}

/* driver note */
$lddfw_driver_note = $order->get_meta( 'lddfw_driver_note' );
if ( '' !== $lddfw_driver_note ) {
	echo '<p><b>' . esc_html( __( 'Driver note', 'lddfw' ) ) . ':</b><br> ' . esc_html( $lddfw_driver_note ) . '</p>';
}

// Signature.
$lddfw_order_signature = $order->get_meta( 'lddfw_order_last_signature' );
if ( '' !== $lddfw_order_signature ) {
	echo '<p><b>';
	echo esc_html( __( 'Signature', 'lddfw' ) ) . '</b><br>';
	echo '<a href="' . esc_attr( $lddfw_order_signature ) . '" target="_blank"><img style="max-width:100%" src="' . esc_attr( $lddfw_order_signature ) . '"></a>';
	echo '</p>';
}

	// Display Delivery Photo(s).
	$lddfw_order_delivery_image_meta = $order->get_meta( 'lddfw_order_last_delivery_image', false );
	if ( ! empty( $lddfw_order_delivery_image_meta ) ) {
	echo '<p>';
	// Check if this is a JSON array of images.
	if ( is_array( $lddfw_order_delivery_image_meta ) ) {
		echo  '<b>' . esc_html( __( 'Photos', 'lddfw' ) ) . '</b><br>';

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
					echo '<a href="' . esc_attr( $data['value'] ) . '" target="_blank"><img style="max-width:100%; margin-bottom: 10px;" src="' . esc_attr( $data['value'] ) . '"></a><br>';
				}
			}
		}
	} elseif ( is_string( $lddfw_order_delivery_image_meta ) && filter_var( $lddfw_order_delivery_image_meta, FILTER_VALIDATE_URL ) ) {
		echo  '<b>' . esc_html( __( 'Photo', 'lddfw' ) ) . '</b><br>';
		// Display single image (original functionality).
		echo '<a href="' . esc_attr( $lddfw_order_delivery_image_meta ) . '" target="_blank"><img style="max-width:100%" src="' . esc_attr( $lddfw_order_delivery_image_meta ) . '"></a>';
	}
	echo '</p>';
}

/*
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @hooked WC_Structured_Data::generate_order_data() Generates structured data.
 * @hooked WC_Structured_Data::output_structured_data() Outputs structured data.
 * @since 2.5.0
 */
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

/*
 * @hooked WC_Emails::order_meta() Shows order meta data.
 */
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

/*
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 */
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

/*
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );
