<?php
/**
 * View for the Inventories' UI within the WooCommerce's order items
 *
 * @since 0.7.4
 *
 * @var \AtumPO\Models\POExtended                $order
 * @var int                                      $item_id
 * @var \WC_Product                              $product
 * @var \Atum\PurchaseOrders\Items\POItemProduct $item
 * @var array                                    $order_item_inventories
 * @var string                                   $region_restriction_mode
 * @var array                                    $locations
 * @var string                                   $action
 * @var string                                   $data_prefix
 * @var bool                                     $has_multi_price
 * @var string                                   $currency
 * @var string                                   $currency_template
 * @var int|float                                $step
 */

defined( 'ABSPATH' ) || die;

$total_qty         = 0;
$class_line_delete = '';

if ( ! empty( $order_item_inventories ) ) :

	if ( 1 === count( $order_item_inventories ) ) :
		$class_line_delete = ' hidden';
	endif;

	foreach ( $order_item_inventories as $order_item_inventory ) :
		require 'inventory.php';
	endforeach;

endif;
