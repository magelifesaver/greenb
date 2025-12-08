<?php
/**
 * Fired during plugin activation
 *
 * @link  http://www.powerfulwp.com
 * @since 1.0.0
 *
 * @package    LDDFW
 * @subpackage LDDFW/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    LDDFW
 * @subpackage LDDFW/includes
 * @author     powerfulwp <apowerfulwp@gmail.com>
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'LDDFW_Assigned_Order_Email_Driver', false ) ) :

	/**
	 * Customer Processing Order Email.
	 *
	 * An email sent to the customer when a new order is paid for.
	 *
	 * @class       LDDFW_Assigned_Order_Email_Driver
	 * @version     3.5.0
	 * @package     WooCommerce/Classes/Emails
	 * @extends     WC_Email
	 */
	class LDDFW_Assigned_Order_Email_Driver extends WC_Email {


		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id             = 'driver_assigned_order';
			$this->customer_email = false;
			$this->title          = __( 'LDDFW - Assigned order - driver', 'lddfw' );
			$this->description    = __( 'This is an order notification sent to the delivery driver containing that order has been assigned to him.', 'lddfw' );
			$this->template_html  = 'emails/driver-assigned-order.php';
			$this->template_plain = 'emails/plain/driver-assigned-order.php';
			$this->template_base  = LDDFW_DIR . '/woocommerce/';
			$this->placeholders   = array(
				'{order_date}'   => '',
				'{order_number}' => '',
			);

			// Triggers for this email.
			add_action( 'lddfw_assigned_order_email_driver_notification', array( $this, 'trigger' ), 10, 2 );

			// Other settings.
			$this->recipient = 'Driver@of.the.order';

			// Call parent constructor.
			parent::__construct();
		}

		/**
		 * Get email subject.
		 *
		 * @since  3.1.0
		 * @return string
		 */
		public function get_default_subject() {
			return __( 'A new order has been assigned to you!', 'lddfw' );
		}

		/**
		 * Get email heading.
		 *
		 * @since  3.1.0
		 * @return string
		 */
		public function get_default_heading() {
			return __( 'Order Assigned', 'lddfw' );
		}

		/**
		 * Trigger the sending of this email.
		 *
		 * @param int            $order_id The order ID.
		 * @param WC_Order|false $order Order object.
		 */
		public function trigger( $order_id, $order = false ) {
			$this->setup_locale();

			if ( $order_id && ! is_a( $order, 'WC_Order' ) ) {
				$order = wc_get_order( $order_id );
			}

			if ( is_a( $order, 'WC_Order' ) ) {
				$driver_id = $order->get_meta( 'lddfw_driverid' );
				$user_info = get_userdata( $driver_id );

				$this->object                         = $order;
				$this->recipient                      = $user_info->user_email;
				$this->placeholders['{order_date}']   = $this->object->get_date_created()->format( lddfw_date_format( 'date' ) );
				$this->placeholders['{order_number}'] = $this->object->get_id();
			}

			if ( $this->is_enabled() && $this->get_recipient() ) {
				$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
			}

			$this->restore_locale();
		}

		/**
		 * Get content html.
		 *
		 * @return string
		 */
		public function get_content_html() {
			return wc_get_template_html(
				$this->template_html,
				array(
					'order'              => $this->object,
					'email_heading'      => $this->get_heading(),
					'additional_content' => $this->get_additional_content(),
					'sent_to_admin'      => false,
					'plain_text'         => false,
					'email'              => $this,
				)
			);
		}

		/**
		 * Get content plain.
		 *
		 * @return string
		 */
		public function get_content_plain() {
			return wc_get_template_html(
				$this->template_plain,
				array(
					'order'              => $this->object,
					'email_heading'      => $this->get_heading(),
					'additional_content' => $this->get_additional_content(),
					'sent_to_admin'      => false,
					'plain_text'         => true,
					'email'              => $this,
				)
			);
		}

		/**
		 * Default content to show below main email content.
		 *
		 * @since 3.7.0
		 * @return string
		 */
		public function get_default_additional_content() {
			return __( 'Thanks for using {site_address}!', 'lddfw' );
		}
	}

endif;

return new LDDFW_Assigned_Order_Email_Driver();
