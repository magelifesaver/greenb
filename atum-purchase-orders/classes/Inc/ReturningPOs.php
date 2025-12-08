<?php
/**
 * Class ReturningPOs
 *
 * @package     AtumPO\Inc
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2025 Stock Management Labs™
 *
 * @since       1.1.2
 */

namespace AtumPO\Inc;

defined( 'ABSPATH' ) || die;

use Atum\Inc\Helpers as AtumHelpers;
use Atum\Components\AtumCache;
use Atum\PurchaseOrders\PurchaseOrders;
use AtumPO\Models\POExtended;

class ReturningPOs {

	/**
	 * The singleton instance holder
	 *
	 * @var ReturningPOs
	 */
	private static $instance;

	/**
	 * ReturningPOs singleton constructor
	 *
	 * @since 1.1.2
	 */
	private function __construct() {

		if ( is_admin() ) {

			// Add the returning POs to the original POs (if any).
			add_action( 'edit_form_top', array( $this, 'add_returning_pos_list' ), 100 );

		}

	}

	/**
	 * Add the returning POs to the original POs (if any).
	 *
	 * @since 1.1.3
	 *
	 * @param \WP_Post $post
	 */
	public function add_returning_pos_list( $post ) {

		if ( $post instanceof \WP_Post && PurchaseOrders::POST_TYPE === $post->post_type ) {

			/**
			 * Variable definition
			 *
			 * @var POExtended $po
			 */
			$po = AtumHelpers::get_atum_order_model( $post->ID, FALSE, PurchaseOrders::POST_TYPE );

			if ( ! $po->is_returning() ) {

				global $wpdb;

				$returning_pos = $wpdb->get_results( $wpdb->prepare( "
					SELECT DISTINCT ID, pm1.meta_value as number 
					FROM $wpdb->posts p
		            INNER JOIN $wpdb->postmeta pm1 ON (p.ID = pm1.post_id AND pm1.meta_key = '_number')
		            INNER JOIN $wpdb->postmeta pm2 ON (p.ID = pm2.post_id AND pm2.meta_key = '_related_po')
					WHERE p.post_type = %s AND p.post_status IN('atum_returning', 'atum_returned') AND pm2.meta_value = %d
				", PurchaseOrders::POST_TYPE, $po->get_id() ) );

				if ( ! empty( $returning_pos ) ) {

					echo '<div class="returning-pos">';
					echo esc_html( _n( 'Returning PO: ', 'Returning POs: ', count( $returning_pos ), ATUM_PO_TEXT_DOMAIN ) );

					$po_links = [];

					foreach ( $returning_pos as $returning_po ) {
						$po_links[] = '<a href="' . esc_url( get_edit_post_link( $returning_po->ID ) ) . '" target="_blank">' . esc_html( $returning_po->number ) . '</a>';
					}

					echo implode( ', ', $po_links ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo '</div>';

				}

			}

		}

	}

	/**
	 * Create a Returning PO from any given PO
	 *
	 * @since 1.1.2
	 *
	 * @param POExtended $original_po
	 *
	 * @return POExtended|\WP_Error
	 */
	public static function create_returning_po( $original_po ) {

		// Only the delivered items should be added to the returning PO, so remove the non-delivered items and qtys.
		$delivered_items = Helpers::get_delivered_po_items( $original_po->get_id() );

		if ( is_wp_error( $delivered_items ) ) {
			return $delivered_items;
		}

		$returned_items = [];

		// Check if there is at least 1 delivery items that has not been returned yet.
		$returned_po_items = self::get_returned_po_items( $original_po->get_id() );

		if ( ! empty( $returned_po_items ) ) {

			foreach ( $returned_po_items as $returned_po_product => $returned_po_item_qty ) {

				if ( array_key_exists( $returned_po_product, $delivered_items ) ) {
					$returned_items[ $returned_po_product ] = $returned_po_item_qty;
				}

			}

		}

		if ( empty( array_diff_assoc( $delivered_items, $returned_items ) ) ) {
			return new \WP_Error( 'returning_po_already_returned', __( 'All the delivery items on this PO have been already returned', ATUM_PO_TEXT_DOMAIN ) );
		}

		$duplicate_po = DuplicatePO::get_instance();
		$returning_po = $duplicate_po->duplicate( $original_po, [
			'comments'      => 'no',
			'deliveries'    => 'no',
			'files'         => 'no',
			'info'          => 'yes',
			'status'        => 'atum_returning',
			'invoices'      => 'no',
			'items'         => 'yes',
			'replace_items' => 'yes',
		] );

		$r_number = "R-{$original_po->number}";

		// Check if any returning order with this same number exists.
		global $wpdb;

		$other_pos = (int) $wpdb->get_var( $wpdb->prepare( "
			SELECT COUNT(DISTINCT p.ID) FROM $wpdb->posts p
            INNER JOIN $wpdb->postmeta pm ON (p.ID = pm.post_id AND pm.meta_key = '_number')
			WHERE p.post_type = %s AND p.post_status != 'auto-draft' AND pm.meta_value LIKE %s
		", PurchaseOrders::POST_TYPE, "$r_number%%" ) );

		if ( $other_pos ) {
			++$other_pos;
			$r_number .= "/{$other_pos}";
		}

		$returning_po->set_number( $r_number );
		$returning_po->set_related_po( $original_po->get_id() );
		$returning_po->save();

		// Remove cache before read PO to getting the added items.
		$cache_key  = AtumCache::get_cache_key( 'get_atum_order_model', [ $returning_po->get_id(), TRUE, PurchaseOrders::POST_TYPE ] );
		AtumCache::delete_cache( $cache_key );

		// Re-read the returnig PO from the db to make sure we have the correct data.
		$returning_po = AtumHelpers::get_atum_order_model( $returning_po->get_id(), TRUE, PurchaseOrders::POST_TYPE );

		// Make sure the items added match with the delivered items.
		$returning_items = $returning_po->get_items();

		foreach ( $returning_items as $returning_item ) {

			$product_id         = $returning_item->get_product_id();
			$returning_item_id  = $returning_item->get_id();
			$returning_item_qty = $returning_item->get_quantity();

			if ( ! isset( $delivered_items[ $product_id ] ) ) {
				$returning_po->remove_item( $returning_item_id );
			}
			elseif ( array_key_exists( $product_id, $returned_items ) ) {

				// Already fully returned?
				if ( $returning_item_qty === $returned_items[ $product_id ] ) {
					$returning_po->remove_item( $returning_item_id );
				}
				else {
					$returning_item->set_quantity( $returning_item_qty - $returned_items[ $product_id ] );
				}

			}
			elseif ( $returning_item_qty !== $delivered_items[ $product_id ] ) {
				$returning_item->set_quantity( $delivered_items[ $product_id ] );
			}

		}

		$returning_po->calculate_totals( TRUE );
		$returning_po->save();

		return $returning_po;

	}

	/**
	 * Return the items and quantities of any specific PO that were already returned
	 *
	 * @since 1.1.2
	 *
	 * @param int $po_id The original PO ID.
	 *
	 * @return array
	 */
	public static function get_returned_po_items( $po_id ) {

		$cache_key      = AtumCache::get_cache_key( 'returned_po_items', [ $po_id ] );
		$returned_items = AtumCache::get_cache( $cache_key, ATUM_PO_TEXT_DOMAIN, FALSE, $has_cache );

		if ( $has_cache ) {
			return $returned_items;
		}

		global $wpdb;

		$sql = $wpdb->prepare( "
			SELECT DISTINCT p.ID FROM $wpdb->posts p
            INNER JOIN $wpdb->postmeta pm ON (p.ID = pm.post_id AND pm.meta_key = '_related_po')
			WHERE p.post_type = %s AND p.post_status IN ('atum_returning', 'atum_returned') AND pm.meta_value = %s
		", PurchaseOrders::POST_TYPE, $po_id );

		$returning_pos = $wpdb->get_col( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$returned_items = [];

		if ( ! empty( $returning_pos ) ) {

			foreach ( $returning_pos as $returning_po_id ) {

				$returning_po = AtumHelpers::get_atum_order_model( $returning_po_id, TRUE, PurchaseOrders::POST_TYPE );
				$items        = $returning_po->get_items();

				foreach ( $items as $item ) {

					$product_id = $item->get_product_id();

					if ( ! $product_id ) {
						continue;
					}

					if ( array_key_exists( $product_id, $returned_items ) ) {
						$returned_items[ $product_id ] += $item->get_quantity();
					}
					else {
						$returned_items[ $product_id ] = $item->get_quantity();
					}

				}

				$returned_items = apply_filters( 'atum/purchase_orders_pro/returning_pos/returned_po_items', $returned_items, $items, $returning_po );

			}

		}

		AtumCache::set_cache( $cache_key, $returned_items, ATUM_PO_TEXT_DOMAIN );

		return $returned_items;

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
	 * @return ReturningPOs instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}
