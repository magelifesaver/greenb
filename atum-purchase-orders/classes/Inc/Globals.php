<?php
/**
 * Purchase Orders Globals
 *
 * @since       0.0.1
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2025 Stock Management Labs™
 *
 * @package     AtumPO\Inc
 */

namespace AtumPO\Inc;

defined( 'ABSPATH' ) || die;

use Atum\Inc\Helpers as AtumHelpers;

final class Globals {

	/**
	 * Get the list of available statuses
	 *
	 * @since 0.0.1
	 *
	 * @return array
	 */
	public static function get_statuses() {

		// NOTE: Some statuses names come from the ATUM free's original statuses to preserve compatibility.
		return (array) apply_filters( 'atum/purchase_orders_pro/statuses', array(
			'atum_pending'             => _x( 'Draft', 'Atum Purchase Order status', ATUM_PO_TEXT_DOMAIN ),
			'atum_new'                 => _x( 'New', 'ATUM Purchase Order status', ATUM_PO_TEXT_DOMAIN ),
			'atum_approval'            => _x( 'Awaiting Approval', 'ATUM Purchase Order status', ATUM_PO_TEXT_DOMAIN ),
			'atum_approved'            => _x( 'New (Approved)', 'ATUM Purchase Order status', ATUM_PO_TEXT_DOMAIN ),
			'atum_ordered'             => _x( 'Sent', 'ATUM Purchase Order status', ATUM_PO_TEXT_DOMAIN ),
			'atum_vendor_received'     => _x( 'Received by Vendor', 'ATUM Purchase Order status', ATUM_PO_TEXT_DOMAIN ),
			'atum_onthewayin'          => _x( 'On the Way In', 'ATUM Purchase Order status', ATUM_PO_TEXT_DOMAIN ),
			'atum_receiving'           => _x( 'Receiving', 'ATUM Purchase Order status', ATUM_PO_TEXT_DOMAIN ),
			'atum_part_receiving'      => _x( 'Partially Receiving', 'ATUM Purchase Order status', ATUM_PO_TEXT_DOMAIN ),
			'atum_quality_check'       => _x( 'Quality Check', 'ATUM Purchase Order status', ATUM_PO_TEXT_DOMAIN ),
			'atum_added'               => _x( 'Added', 'ATUM Purchase Order status', ATUM_PO_TEXT_DOMAIN ),
			'atum_partially_added'     => _x( 'Partially Added', 'ATUM Purchase Order status', ATUM_PO_TEXT_DOMAIN ),
			'atum_received'            => _x( 'Completed', 'ATUM Purchase Order status', ATUM_PO_TEXT_DOMAIN ),
			'atum_cancelled'           => _x( 'Cancelled', 'ATUM Purchase Order status', ATUM_PO_TEXT_DOMAIN ),
			'atum_returning'           => _x( 'Returning', 'ATUM Purchase Order status', ATUM_PO_TEXT_DOMAIN ),
			'atum_returned'            => _x( 'Returned', 'ATUM Purchase Order status', ATUM_PO_TEXT_DOMAIN ),
			'trash'                    => _x( 'Archived', 'ATUM Purchase Order status', ATUM_PO_TEXT_DOMAIN ), // Using the standard WP trash status for these.
		) );

	}

	/**
	 * Get the list of status colors
	 *
	 * @since 0.7.2
	 *
	 * @return array
	 */
	public static function get_status_colors() {

		return (array) apply_filters( 'atum/purchase_orders_pro/status_colors', array(
			'atum_pending'             => '#adb5bd',
			'atum_new'                 => '#27283b',
			'atum_approval'            => '#27283b',
			'atum_approved'            => '#27283b',
			'atum_ordered'             => '#00b8db',
			'atum_vendor_received'     => '#efaf00',
			'atum_onthewayin'          => '#efaf00',
			'atum_receiving'           => '#efaf00',
			'atum_part_receiving'      => '#efaf00',
			'atum_quality_check'       => '#efaf00',
			'atum_added'               => '#ba7df7',
			'atum_partially_added'     => '#ba7df7',
			'atum_received'            => '#69c61d',
			'atum_cancelled'           => '#ff4848',
			'atum_returning'           => '#94660c',
			'atum_returned'            => '#ff4848',
			'trash'                    => '#ced4da',
		) );

	}

	/**
	 * Get the status flow configuration (which statuses are allowed as destinations for every status type)
	 *
	 * @since 0.9.13
	 *
	 * @return array
	 */
	public static function get_status_flow() {

		// Check if the status flow restriction is enabled.

		$status_flow = array(
			'atum_pending'             => array(
				'atum_new',
				'atum_cancelled',
				'trash',
			),
			'atum_new'                 => array(
				'atum_pending',
				'atum_ordered',
				'atum_cancelled',
				'trash',
			),
			'atum_ordered'             => array(
				'atum_new',
				'atum_vendor_received',
				'atum_cancelled',
				'trash',
			),
			'atum_vendor_received'     => array(
				'atum_ordered',
				'atum_onthewayin',
				'atum_cancelled',
				'trash',
			),
			'atum_onthewayin'          => array(
				'atum_vendor_received',
				'atum_receiving',
				'atum_part_receiving',
				'atum_cancelled',
				'trash',
			),
			'atum_receiving'           => array(
				'atum_onthewayin',
				'atum_quality_check',
				'atum_cancelled',
				'trash',
			),
			'atum_part_receiving'      => array(
				'atum_onthewayin',
				'atum_receiving',
				'atum_quality_check',
				'atum_cancelled',
				'trash',
			),
			'atum_quality_check'       => array(
				'atum_part_receiving',
				'atum_receiving',
				'atum_added',
				'atum_partially_added',
				'atum_cancelled',
				'trash',
			),
			'atum_added'               => array(
				'atum_quality_check',
				'atum_received',
				'atum_cancelled',
				'trash',
			),
			'atum_partially_added'     => array(
				'atum_quality_check',
				'atum_added',
				'atum_received',
				'atum_cancelled',
				'trash',
			),
			'atum_received'            => array(
				'atum_partially_added',
				'atum_added',
				'atum_cancelled',
				'trash',
			),
			'atum_cancelled'           => array(
				'atum_pending',
				'atum_new',
				'atum_ordered',
				'atum_vendor_received',
				'atum_onthewayin',
				'atum_receiving',
				'atum_part_receiving',
				'atum_quality_check',
				'atum_added',
				'atum_partially_added',
				'trash',
			),
			'atum_returning'           => array(
				'atum_returned',
				'trash',
			),
			'atum_returned'            => array(
				'atum_returning',
				'trash',
			),
		);

		// Check if the requisitioner requisition is enabled.
		if ( AtumHelpers::get_option( 'po_required_requisition', 'no' ) === 'yes' ) {

			$status_flow['atum_new'][] = 'atum_approval';
			array_splice( $status_flow['atum_new'], array_search( 'atum_ordered', $status_flow['atum_new'] ), 1 );
			$status_flow['atum_ordered'][]   = 'atum_approved';
			$status_flow['atum_cancelled'][] = 'atum_approval';
			$status_flow['atum_cancelled'][] = 'atum_approved';

			array_splice( $status_flow['atum_ordered'], array_search( 'atum_new', $status_flow['atum_ordered'] ), 1 );

			$status_flow['atum_approval'] = array(
				'atum_new',
				'atum_approved',
				'atum_cancelled',
				'trash',
			);
			$status_flow['atum_approved'] = array(
				'atum_approval',
				'atum_ordered',
				'atum_cancelled',
				'trash',
			);

		}

		return (array) apply_filters( 'atum/purchase_orders_pro/status_flow', $status_flow );

	}

	/**
	 * Get the list of statuses that are considered as "Due" (not received yet).
	 *
	 * @since 0.9.13
	 */
	public static function get_due_statuses() {

		$due_statuses = array(
			'atum_pending',
			'atum_new',
			'atum_ordered',
			'atum_vendor_received',
			'atum_onthewayin',
		);

		if ( AtumHelpers::get_option( 'po_required_requisition', 'no' ) === 'yes' ) {
			$due_statuses = array_merge( $due_statuses, self::get_requisitioner_statuses() );
		}

		return apply_filters( 'atum/purchase_orders_pro/list_table/due_statuses', $due_statuses );

	}

	/**
	 * Get the special statuses when the requisitioner is enabled and assigned to the PO
	 *
	 * @since 0.9.13
	 *
	 * @return array
	 */
	public static function get_requisitioner_statuses() {

		return (array) apply_filters( 'atum/purchase_orders_pro/requisitioner_statuses', array(
			'atum_approval',
			'atum_approved',
		) );

	}

	/**
	 * Get the list of statuses that are considered as "Closed". Orders shouldn't be modified if in these statuses
	 *
	 * @since 0.9.13
	 */
	public static function get_closed_statuses() {

		return (array) apply_filters( 'atum/purchase_orders_pro/closed_statuses', array(
			'atum_cancelled',
			'trash',
		) );
	}

	/**
	 * Get the list of available shipping methods
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	public static function get_shipping_methods() {

		return (array) apply_filters( 'atum/purchase_orders_pro/shipping_methods', array(
			'ship'    => _x( 'Ship', 'ATUM Purchase Order shipping method', ATUM_PO_TEXT_DOMAIN ),
			'train'   => _x( 'Train', 'ATUM Purchase Order shipping method', ATUM_PO_TEXT_DOMAIN ),
			'lorry'   => _x( 'Lorry', 'ATUM Purchase Order shipping method', ATUM_PO_TEXT_DOMAIN ),
			'courier' => _x( 'Courier', 'ATUM Purchase Order shipping method', ATUM_PO_TEXT_DOMAIN ),
		) );

	}

	/**
	 * Get the colors for the expected dates according to its stage.
	 *
	 * @since 0.9.13
	 *
	 * @return array
	 */
	public static function get_date_expected_colors() {

		return (array) apply_filters( 'atum/purchase_orders_pro/date_expected_colors', array(
			'in_time'  => '#69c61d',
			'due_soon' => '#efaf00',
			'late'     => '#ff4848',
		) );

	}

}
