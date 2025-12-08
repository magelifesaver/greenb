<?php
/**
 * Class LDDFW_Assigned_Order_Email_Vendor file.
 *
 * @package WooCommerce\Emails
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'LDDFW_Assigned_Order_Email_Vendor', false ) ) :

	/**
	 * Driver Assigned Order Email.
	 *
	 * An email sent to the vendor when a new driver has been assigned to order.
	 *
	 * @class       LDDFW_Assigned_Order_Email_Vendor
	 * @version     3.5.0
	 * @package     WooCommerce/Classes/Emails
	 * @extends     WC_Email
	 */
	class LDDFW_Assigned_Order_Email_Vendor extends WC_Email {
		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id             = 'vendor_driver_assign_order';
			$this->customer_email = false;

			$this->title          = __( 'LDDFW - Driver has been Assigned to Order', 'lddfw' );
			$this->description    = __( 'This is an order notification sent to the vendor containing order and driver details when a new driver has been assigned to order.', 'lddfw' );
			$this->template_html  = 'emails/driver-assigned-order-vendor.php';
			$this->template_plain = 'emails/plain/driver-assigned-order-vendor.php';
			$this->template_base  = LDDFW_DIR . '/woocommerce/';
			$this->placeholders   = array(
				'{order_date}'   => '',
				'{order_number}' => '',
			);
			// Other settings
			$this->recipient = 'Vendor@of.the.order';

			// Triggers for this email.
			add_action( 'lddfw_assigned_order_email_vendor_notification', array( $this, 'trigger' ), 10, 3 );

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
			return __( 'Your {site_title} order has been assigned to driver!', 'lddfw' );
		}

		/**
		 * Get email heading.
		 *
		 * @since  3.1.0
		 * @return string
		 */
		public function get_default_heading() {
			return __( 'Order #{order_number}', 'lddfw' );
		}

		/**
		 * Trigger the sending of this email.
		 *
		 * @param int            $order_id The order ID.
		 * @param WC_Order|false $order Order object.
		 */
		public function trigger( $order_id, $order = false, $seller_id = '' ) {
			$this->setup_locale();

			if ( $order_id && ! is_a( $order, 'WC_Order' ) ) {
				$order = wc_get_order( $order_id );
			}

			$store = new LDDFW_Store();
			$seller_email = $store->lddfw_store_email__premium_only( $seller_id );

			if ( is_a( $order, 'WC_Order' ) ) {
				$this->object                         = $order;
				$this->recipient                      = $seller_email;
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

return new LDDFW_Assigned_Order_Email_Vendor();
