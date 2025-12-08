<?php
/**
 * Duplicate PO class
 *
 * @since       0.8.7
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2025 Stock Management Labs™
 *
 * @package     AtumPO\Inc
 */

namespace AtumPO\Inc;

defined( 'ABSPATH' ) || exit;

use Atum\PurchaseOrders\PurchaseOrders;
use Atum\Inc\Helpers as AtumHelpers;
use AtumPO\Models\POExtended;


class DuplicatePO {

	/**
	 * The singleton instance holder
	 *
	 * @var DuplicatePO
	 */
	private static $instance;

	/**
	 * DuplicatePO singleton constructor.
	 *
	 * @since 0.8.7
	 */
	private function __construct() {

		add_action( 'admin_action_duplicate_po', array( $this, 'duplicate_po_action' ) );

	}

	/**
	 * Duplicate a PO action.
	 *
	 * @since 0.8.7
	 */
	public function duplicate_po_action() {

		if ( empty( $_REQUEST['post'] ) ) {
			wp_die( esc_html__( 'No PO to duplicate has been supplied!', ATUM_PO_TEXT_DOMAIN ) );
		}

		$po_id = isset( $_REQUEST['post'] ) ? absint( $_REQUEST['post'] ) : '';

		check_admin_referer( "atum-duplicate-po_$po_id" );

		$po = AtumHelpers::get_atum_order_model( $po_id, TRUE, PurchaseOrders::POST_TYPE );

		if ( is_wp_error( $po ) || ! $po->exists() ) {
			/* translators: %s: PO id */
			wp_die( sprintf( esc_html__( 'PO creation failed, could not find original PO: %s', ATUM_PO_TEXT_DOMAIN ), esc_html( $po_id ) ) );
		}

		$settings = [];

		if ( ! isset( $_REQUEST['clone_deliveries'] ) || 0 === absint( $_REQUEST['clone_deliveries'] ) ) {
			$settings = array(
				'deliveries' => 'no',
				'invoices'   => 'no',
			);
		}

		$duplicated_po = $this->duplicate( $po, $settings );

		do_action( 'atum/purchase_orders_pro/duplicate', $duplicated_po, $po );

		// Redirect to the edit screen for the new draft page.
		wp_safe_redirect( admin_url( 'post.php?action=edit&post=' . $duplicated_po->get_id() ) );
		exit;

	}

	/**
	 * Function to create the duplicate of the PO.
	 *
	 * @since 0.8.7
	 *
	 * @param POExtended $po       The PO to duplicate.
	 * @param array      $settings Optional. The settings to duplicate from the original PO.
	 *
	 * @return POExtended The duplicate.
	 */
	public function duplicate( $po, $settings = [] ) {

		$duplicated_po = new POExtended();

		do_action( 'atum/purchase_orders_pro/po_duplicate_before_save', $duplicated_po, $po );

		// Save to assign it an ID.
		$duplicated_po->save();

		// Just use the MergePO class to bring all the data.
		$merge         = new MergePO( $po, $duplicated_po );
		$settings      = array_merge( [
			'comments'      => 'yes',
			'deliveries'    => 'yes',
			'files'         => 'yes',
			'info'          => 'yes',
			'status'        => 'atum_pending',
			'invoices'      => 'yes',
			'items'         => 'yes',
			'replace_items' => 'yes',
		], $settings );
		$duplicated_po = $merge->merge_data( $settings );

		// Calculate totals and save the PO.
		$duplicated_po->calculate_totals();
		$duplicated_po->save_meta();

		do_action( 'atum/purchase_orders_pro/po_duplicate_after_save', $duplicated_po, $po );

		return $duplicated_po;

	}

	/**
	 * Get a link for duplicating a specific PO
	 *
	 * @since 0.8.7
	 *
	 * @param int $po_id
	 *
	 * @return string
	 */
	public static function get_duplicate_link( $po_id ) {

		// Is a new PO?
		if ( ! $po_id ) {
			return '';
		}

		return wp_nonce_url( admin_url( "post.php?post=$po_id&action=duplicate_po" ), "atum-duplicate-po_$po_id" );

	}


	/*******************
	 * Instance methods
	 *******************/

	/**
	 * Cannot be cloned
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_attr__( 'Cheatin&#8217; huh?', ATUM_PO_TEXT_DOMAIN ), '1.0.0' );
	}

	/**
	 * Cannot be serialized
	 */
	public function __sleep() {
		_doing_it_wrong( __FUNCTION__, esc_attr__( 'Cheatin&#8217; huh?', ATUM_PO_TEXT_DOMAIN ), '1.0.0' );
	}

	/**
	 * Get Singleton instance
	 *
	 * @return DuplicatePO instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}
