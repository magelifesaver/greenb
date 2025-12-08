<?php
/**
 * View for the PO's item returned ratio column
 *
 * @since 1.1.2
 *
 * @var \AtumPO\Models\POExtended                              $atum_order
 * @var int                                                    $item_id
 * @var \Atum\Components\AtumOrders\Items\AtumOrderItemProduct $item
 * @var WC_Product                                             $product
 * @var int|float                                              $step
 * @var string                                                 $field_name_prefix
 * @var bool                                                   $disable_edit      Optional.
 */

defined( 'ABSPATH' ) || die;

use AtumPO\Inc\Helpers;
use AtumPO\Inc\ReturningPOs;

$returned_qty = $delivered_qty = 0;

// For Returning POs, restrict the maximum units.
if ( $atum_order->is_returning() && $atum_order->related_po ) :
	$returned_po_items  = ReturningPOs::get_returned_po_items( $atum_order->related_po );
	$delivered_po_items = Helpers::get_delivered_po_items( $atum_order->related_po );
	$returned_qty       = $returned_po_items[ $product->get_id() ] ?? 0;
	$delivered_qty      = $delivered_po_items[ $product->get_id() ] ?? 0;
endif;
?>
<td class="returned-ratio center<?php echo $returned_qty > $delivered_qty ? ' color-danger' : '' ?>" style="width: 1%" data-returned="<?php echo esc_attr( $returned_qty ); ?>" data-delivered="<?php echo esc_attr( $delivered_qty ); ?>">
	<?php echo esc_html( $returned_qty ); ?>/<?php echo esc_html( $delivered_qty ); ?>
</td>
