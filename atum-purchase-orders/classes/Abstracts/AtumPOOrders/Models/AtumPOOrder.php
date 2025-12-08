<?php
/**
 * Abstract class AtumPOOrder model
 *
 * @package         AtumPO\Abstracts\AtumPOOrders
 * @subpackage      Models
 * @author          BE REBEL - https://berebel.studio
 * @copyright       ©2025 Stock Management Labs™
 *
 * @since           0.9.6
 */

namespace AtumPO\Abstracts\AtumPOOrders\Models;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumOrders\Models\AtumOrderModel;
use Atum\Inc\Helpers as AtumHelpers;
use Atum\PurchaseOrders\PurchaseOrders;
use AtumPO\Invoices\Models\Invoice;
use AtumPO\Models\POExtended;


abstract class AtumPOOrder extends AtumOrderModel {

	/**
	 * Whether the item's quantity will affect positively or negatively (or both) the stock
	 *
	 * @var string
	 */
	protected $action = '';

	/**
	 * Read the ATUM PO Order's metadata from db
	 *
	 * @since 0.9.6
	 */
	public function read_meta() {

		parent::read_meta();
		$this->read_extra_data();

	}

	/**
	 * Read the extra data (not being saved as meta).
	 *
	 * @since 0.9.1
	 */
	protected function read_extra_data() {

		if ( $this->post ) {
			$this->set_meta( 'name', $this->post->post_title );
			$this->set_meta( 'date_created', $this->post->post_date );
			$this->set_meta( 'po', $this->post->post_parent );
		}

	}

	/**
	 * Checks if an ATUM PO Order can be edited
	 *
	 * @since 0.9.6
	 *
	 * @return bool
	 */
	public function is_editable() {
		return TRUE;  // TODO: IS IT ALWAYS EDITABLE?
	}

	/**
	 * Process status changes
	 *
	 * @since 0.9.6
	 */
	public function process_status() {
		// No need to do anything here, so just overwrite the parent method.
		$new_status = $this->get_status();

		// if ! $new_status, order is still being created, so there aren't status changes.
		if ( $new_status ) {

			switch ( $new_status ) {
				case 'atum_onthewayin':
					update_post_meta( $this->get_id(), 'atum_po_onthewayin_date', date_i18n( 'U', FALSE, TRUE ) );
					break;

				case 'atum_receiving':
					$old_status = $this->db_status;

					if ( 'atum_onthewayin' === $old_status ) {

						$date = new \DateTime();
						$date->setTimezone( new \DateTimeZone( 'GMT' ) );
						$date->setTimestamp( get_post_meta( $this->get_id(), 'atum_po_onthewayin_date', TRUE ) );
						$now  = new \DateTime();
						$days = $date->diff( $now )->format( '%d' );

						update_post_meta( $this->get_id(), 'onthewayin_days', $days );

					}
					break;

				case 'atum_cancelled':
					$order      = AtumHelpers::get_atum_order_model( $this->get_id(), TRUE, PurchaseOrders::POST_TYPE );
					$old_status = $this->db_status;

					do_action( 'atum/orders/status_changed', $this->get_id(), $old_status, $new_status, $order );

					break;
			}
		}
	}

	/**
	 * Calculate shipping total
	 *
	 * @since 0.9.6
	 *
	 * @return float
	 */
	public function calculate_shipping() {

		if ( $this instanceof Invoice ) {
			return parent::calculate_shipping();
		}

		return 0; // Not supported.
	}

	/**
	 * Get the post data to be saved to the ATUM PO Order post
	 *
	 * @since 1.2.1
	 *
	 * @param string $status
	 *
	 * @return array
	 */
	protected function get_post_data( $status ) {

		$post_data = parent::get_post_data( $status );
		$post_data['post_parent'] = $this->po;

		return $post_data;

	}

	/**
	 * Perform actions after saving an invoice.
	 *
	 * @since 0.9.6
	 *
	 * @param string $action
	 */
	public function after_save( $action ) {

		// TODO: IS THIS REALLY NECESSARY?
		if ( 'create' === $action ) {

			// Load the post and update the invoice data.
			wp_cache_delete( $this->id, 'posts' ); // Make sure the updated version is returned.
			$this->load_post();

		}

	}

	/*********
	 * GETTERS
	 *********/

	/**
	 * Get the ATUM PO Order status
	 *
	 * @since 0.9.6
	 *
	 * @return string
	 */
	public function get_status() {
		return 'publish'; // No need custom nor multiple statuses here.
	}

	/**
	 * Return an array of shipping costs within this ATUM PO Order
	 *
	 * @since 0.9.6
	 *
	 * @return array
	 */
	public function get_shipping_methods() {

		if ( $this instanceof Invoice ) {
			return parent::get_shipping_methods();
		}

		return []; // If shipping is supported, override this method.
	}

	/**
	 * Get the parent PO object
	 *
	 * @since 0.9.6
	 *
	 * @return POExtended|\WP_Error
	 */
	public function get_po_object() {
		return AtumHelpers::get_atum_order_model( $this->po, FALSE, PurchaseOrders::POST_TYPE );
	}

	/*********
	 * SETTERS
	 *********/

	/**
	 * Setter for the parent PO.
	 *
	 * @param int  $po
	 * @param bool $skip_change
	 */
	public function set_po( $po, $skip_change = FALSE ) {

		$po = absint( $po );

		if ( $po !== $this->delivery_date ) {

			if ( ! $skip_change ) {
				$this->register_change( 'po' );
			}

			$this->set_meta( 'po', $po );
		}

	}

	/**
	 * Setter for the ATUM PO Order's document number
	 *
	 * @since 0.9.6
	 *
	 * @param string $document_number
	 * @param bool   $skip_change
	 */
	public function set_document_number( $document_number, $skip_change = FALSE ) {

		$document_number = sanitize_text_field( $document_number );

		if ( $this->document_number !== $document_number ) {

			if ( ! $skip_change ) {
				$this->register_change( 'document_number' );
			}

			$this->set_meta( 'document_number', $document_number );

		}
	}

	/**
	 * Setter for the ATUM PO Order's files
	 *
	 * @since 0.9.3
	 *
	 * @param int[] $files
	 * @param bool  $skip_change
	 * @param bool  $append
	 */
	public function set_files( $files, $skip_change = FALSE, $append = TRUE ) {

		// It's coming serialised from db.
		if ( ! is_array( $files ) ) {
			$files = maybe_unserialize( $files );
		}

		$files = is_array( $files ) ? array_map( 'absint', $files ) : [];

		if ( $append ) {
			$files = array_merge( $this->files, $files );
		}

		// Duplicated values aren't allowed.
		$files = array_unique( $files );

		if ( $this->files !== $files ) {

			if ( ! $skip_change ) {
				$this->register_change( 'files' );
			}

			$this->set_meta( 'files', $files );

		}

	}

	/**
	 * Set shipping total
	 *
	 * @since 0.9.6
	 *
	 * @param float $shipping_total
	 * @param bool  $skip_change
	 */
	public function set_shipping_total( $shipping_total, $skip_change = FALSE ) {

		if ( $this instanceof Invoice ) {
			parent::set_shipping_total( $shipping_total, $skip_change );
		}

		// Not supported.
	}

	/**
	 * Set shipping tax
	 *
	 * @since 0.9.6
	 *
	 * @param float $shipping_tax
	 * @param bool  $skip_change
	 */
	public function set_shipping_tax( $shipping_tax, $skip_change = FALSE ) {

		if ( $this instanceof Invoice ) {
			parent::set_shipping_tax( $shipping_tax, $skip_change );
		}

		// Not supported.
	}

	/**
	 * Set cart tax
	 *
	 * @since 0.9.6
	 *
	 * @param float $cart_tax
	 * @param bool  $skip_change
	 */
	public function set_cart_tax( $cart_tax, $skip_change = FALSE ) {

		if ( $this instanceof Invoice ) {
			parent::set_cart_tax( $cart_tax, $skip_change );
		}
		else {

			$cart_tax = wc_format_decimal( $cart_tax );

			if ( $cart_tax !== $this->cart_tax ) {

				if ( ! $skip_change ) {
					$this->register_change( 'cart_tax' );
				}

				$this->set_meta( 'cart_tax', $cart_tax );
				$this->set_total_tax( (float) $cart_tax, $skip_change );
			}
		}
	}

}
