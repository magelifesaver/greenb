<?php
/**
 * Upgrade tasks
 *
 * @package        AtumPO
 * @subpackage     Inc
 * @author         BE REBEL - https://berebel.studio
 * @copyright      ©2025 Stock Management Labs™
 *
 * @since          0.9.19
 */

namespace AtumPO\Inc;

defined( 'ABSPATH' ) || die;

use Atum\Addons\Addons;
use Atum\PurchaseOrders\PurchaseOrders;


class Upgrade {

	/**
	 * Whether PO PRO is being installed for the first time
	 *
	 * @var bool
	 */
	private $is_fresh_install = FALSE;

	/**
	 * Upgrade constructor
	 *
	 * @since 0.9.19
	 *
	 * @param string $db_version    The ATUM PO PRO version saved in db as an option.
	 */
	public function __construct( $db_version ) {

		// Update the db version to the current ATUM POM PRO version before upgrade to prevent various executions.
		update_option( 'atum_purchase_orders_pro_version', ATUM_PO_VERSION );

		// Make sure any old status transient is cleared for the add-on.
		Addons::delete_status_transient( 'Purchase Orders PRO' );

		if ( ! $db_version || version_compare( $db_version, '0.0.1', '<=' ) ) {
			$this->is_fresh_install = TRUE;
		}

		/************************
		 * UPGRADE ACTIONS START
		 **********************!*/

		if ( $this->is_fresh_install ) {
			$this->keep_po_free_status();
		}

		/* version 1.1.4: The linked BOM products are now stored on its own db table. */
		if ( version_compare( $db_version, '0.9.26', '<' ) ) {
			$this->clean_saved_po_meta_boxes_layouts();
		}

		/* version 1.2.0.1: Fixed atum_partially_receiving post status. */
		if ( version_compare( $db_version, '1.2.0.1', '<' ) && ! $this->is_fresh_install ) {
			$this->fix_partially_receiving_post_status();
		}

		/**********************
		 * UPGRADE ACTIONS END
		 ********************!*/

		do_action( 'atum/purchase_orders_pro/after_upgrade', $db_version );

	}

	/**
	 * Keep the last status of the PO free orders the first time PO Pro is activated.
	 * This function uses bulk insert and maximum allowed packet to minimize the execution time.
	 *
	 * @since 0.9.27
	 */
	private function keep_po_free_status() {

		global $wpdb;

		// Get the maximum length of the query.
		$max_packet = $wpdb->get_row( "SHOW VARIABLES LIKE 'max_allowed_packet'" );
		$max_packet = $max_packet ? (int) $max_packet->Value : 1024; // phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase, 1024 is the min value.

		// Get all POs and their statuses.
		$pos_free = $wpdb->get_results( $wpdb->prepare( "SELECT ID,post_status FROM $wpdb->posts WHERE post_type = %s", 'atum_purchase_order' ) );

		if ( $pos_free ) {

			$default_insert = $insert_sql = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) VALUES ";

			foreach ( $pos_free as $po_free ) {

				$new_meta = "($po_free->ID, '_old_po_free_status', '$po_free->post_status'),";

				// If the string reached its maximum length, execute it and star again.
				if ( strlen( $insert_sql ) + strlen( $new_meta ) > $max_packet ) {

					// Remove the last comma.
					$insert_sql = substr( $insert_sql, 0, strlen( $insert_sql ) - 1 );

					$wpdb->query( $insert_sql ); // phpcs:ignore WordPress.DB.PreparedSQL

					$insert_sql = $default_insert;

				}
				else {
					$insert_sql .= $new_meta;
				}

			}

			// Execute the last insert if needed.
			if ( $default_insert !== $insert_sql ) {
				$wpdb->query( substr( $insert_sql, 0, strlen( $insert_sql ) - 1 ) ); // phpcs:ignore WordPress.DB.NotPreparedSQL,WordPress.DB.PreparedSQL
			}

		}

	}

	/**
	 * Clean any saved PO meta boxes order, so the default UI is shown after the first install
	 *
	 * @since 0.9.25
	 */
	public function clean_saved_po_meta_boxes_layouts() {

		global $wpdb;

		$wpdb->query( "
			DELETE FROM $wpdb->usermeta WHERE meta_key = 'meta-box-order_atum_purchase_order';		
		" );

	}

	/**
	 * Replace atum_partially_receiving status with atum_partially_rcv
	 *
	 * @since 1.2.0.1
	 */
	public function fix_partially_receiving_post_status() {
		global $wpdb;

		$post_type = PurchaseOrders::POST_TYPE;

		$wpdb->query( "
			UPDATE $wpdb->posts SET post_status = 'atum_part_receiving' WHERE post_type = '$post_type' AND post_status = 'atum_partially_receiving';		
		" );

	}

}
