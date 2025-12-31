<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/fulfillment/hooks/class-aaa-oc-fulfillment-products-table-hook.php
 * Purpose: Render Fulfillment table only for pick/pack; let Core handle others.
 * Notes:
 *  - Uses "widefat striped" to match Core fonts/weights.
 *  - If indexed items are empty, do not render (Core will render instead).
 * Version: 1.0.7
 */
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'AAA_OC_Fulfillment_Products_Table_Hook' ) ) :

final class AAA_OC_Fulfillment_Products_Table_Hook {

	public static function init() : void {
		// Run before core so we can intercept for pick/pack statuses only.
		add_action( 'aaa_oc_board_products_table', [ __CLASS__, 'render' ], 9, 1 );
	}

	public static function render( array $ctx ) : void {
		$oi       = isset($ctx['oi']) ? (object)$ctx['oi'] : (object)[];
		$status   = self::norm( (string)($oi->status ?? '') ); // ← from order_index
		$currency = (string)($oi->currency ?? '' );
		$items    = json_decode( (string)($oi->items ?? '[]'), true ) ?: [];
		$key      = self::card_key( $ctx, $oi );

		// Options
		$pick_enable_status    = self::norm( (string)( function_exists('aaa_oc_get_option') ? aaa_oc_get_option('pick_enable_status','fulfillment','processing') : 'processing' ) );
		$allow_partial_picking = (int)  ( function_exists('aaa_oc_get_option') ? aaa_oc_get_option('allow_partial_picking','fulfillment',1) : 1 );
		$pick_complete_status  = self::norm( (string)( function_exists('aaa_oc_get_option') ? aaa_oc_get_option('pick_complete_status','fulfillment','lkd-packed-ready') : 'lkd-packed-ready' ) );

		$pack_enable           = (int)  ( function_exists('aaa_oc_get_option') ? aaa_oc_get_option('pack_enable','fulfillment',0) : 0 );
		$pack_show_status      = self::norm( (string)( function_exists('aaa_oc_get_option') ? aaa_oc_get_option('pack_show_status','fulfillment','lkd-packed-ready') : 'lkd-packed-ready' ) );
		$pack_complete_status  = self::norm( (string)( function_exists('aaa_oc_get_option') ? aaa_oc_get_option('pack_complete_status','fulfillment','completed') : 'completed' ) );
		$pack_require_picked   = (int)  ( function_exists('aaa_oc_get_option') ? aaa_oc_get_option('pack_require_picked','fulfillment',1) : 1 );

		$picked_json        = (string)($oi->picked_items ?? '');
		$fulfillment_status = strtolower( (string)($oi->fulfillment_status ?? 'not_picked') ); // ← from order_index

		// Intercept only for pick/pack.
		$mode = '';
		if ( $pack_enable && $status === $pack_show_status ) {
			$mode = 'pack';
			if ( $pack_require_picked && $fulfillment_status !== 'fully_picked' ) {
				echo '<div class="expanded-only" style="padding:8px;background:#fff3cd;border-left:4px solid #ffecb5;">'
				   . esc_html__( 'Packing is blocked until the order is fully picked.', 'aaa-oc' ) . '</div>';
				self::mark_handled($key);
				return;
			}
		} elseif ( $status === $pick_enable_status ) {
			$mode = 'pick';
		} else {
			return; // Not a fulfillment status → let Core render.
		}

		// If items are missing/empty, do not render here (Core will render a neutral table).
		if ( empty( $items ) ) return;

		// Render table, then mark handled so Core skips.
		echo '<div class="expanded-only" style="display:block;border-left:1px solid #ccc;padding:8px;background:#e9e9e9;">';
		echo self::build_table(
			$items, $currency, $picked_json, $status, $fulfillment_status, $mode,
			$allow_partial_picking, $pick_complete_status, $pack_complete_status
		);
		echo '</div>';
		self::mark_handled($key);
	}

	/* ========== helpers ========== */

	private static function norm( string $s ) : string { $s = trim(strtolower($s)); return (strpos($s,'wc-')===0) ? substr($s,3) : $s; }

	private static function card_key( array $ctx, \stdClass $oi ) : string {
		$oid = 0;
		if ( isset($ctx['order_id']) && (int)$ctx['order_id'] > 0 ) $oid = (int)$ctx['order_id'];
		elseif ( isset($oi->order_id) && (int)$oi->order_id > 0 )   $oid = (int)$oi->order_id;
		elseif ( isset($oi->ID) && (int)$oi->ID > 0 )               $oid = (int)$oi->ID;
		if ( $oid > 0 ) return 'o_'.$oid;
		return 'h_'.md5( (string)($oi->items ?? '').'|'.(string)($oi->status ?? '').'|'.(string)($oi->currency ?? '') );
	}

	private static function mark_handled( string $key ) : void {
		if ( $key === '' ) return;
		if ( ! isset($GLOBALS['AAA_OC__CARD_PRODUCTS_HANDLED']) ) $GLOBALS['AAA_OC__CARD_PRODUCTS_HANDLED'] = [];
		$GLOBALS['AAA_OC__CARD_PRODUCTS_HANDLED'][$key] = true;
	}

	private static function build_table(
		array $items, string $currency, string $picked_json,
		string $order_status, string $fulfillment_status, string $mode,
		int $allow_partial_picking, string $pick_complete_status, string $pack_complete_status
	) : string {
		// Normalize picked snapshot → sku => qty
		$skuToPicked = []; $pickedRows = [];
		if ( $picked_json !== '' ) {
			$decoded = json_decode($picked_json, true);
			if ( is_array($decoded) ) {
				$isRows = isset($decoded[0]) && is_array($decoded[0]) && array_key_exists('sku',$decoded[0]);
				if ( $isRows ) { foreach($decoded as $r){ $s=(string)($r['sku']??''); $p=(int)($r['picked']??0); if($s!=='') $skuToPicked[$s]=$p; } $pickedRows=$decoded; }
				else { foreach($decoded as $s=>$q) if(is_string($s)) $skuToPicked[$s]=(int)$q; foreach($skuToPicked as $s=>$q) $pickedRows[]=['sku'=>$s,'picked'=>$q,'max'=>0]; }
			}
		}

		ob_start();
		if ( $fulfillment_status === 'fully_picked' && $pickedRows ) {
			echo '<div class="pick-status" style="padding:.5rem;font-weight:bold;">' . esc_html__('Pick status: Fully Picked','aaa-oc') . '</div>';
		}
		?>
		<table class="widefat striped" style="width:100%;">
			<thead><tr>
				<th style="text-align:left;">Qty</th>
				<th style="text-align:left;">Product</th>
				<th style="text-align:right;">Unit Price</th>
				<th style="text-align:right;">Total</th>
				<th style="text-align:center;"><?php echo ($mode==='pack') ? 'Packed' : 'Picked'; ?></th>
			</tr></thead>
			<tbody>
			<?php foreach ($items as $itm) :
				$qty    = (int)($itm['quantity'] ?? 0);
				$name   = (string)($itm['name'] ?? '(no name)');
				$brand  = (string)($itm['brand'] ?? '');
				$sku    = (string)($itm['sku'] ?? '');
				$picked = isset($skuToPicked[$sku]) ? (int)$skuToPicked[$sku] : 0;

				// Load extra meta keys from settings (comma-separated)
				$extra_keys = [];
				if ( function_exists('aaa_oc_get_option') ) {
					$raw = (string) aaa_oc_get_option('additional_sku_meta_keys', 'fulfillment', '');
					if ( $raw !== '' ) {
						$extra_keys = array_filter( array_map('trim', explode(',', $raw) ) );
					}
				}

				// Resolve new_sku from product meta: prefer first non-empty lkd_wm_new_sku, then any configured extra keys
				$product_id = (int)($itm['product_id'] ?? 0);
				$new_sku    = '';
				if ( $product_id ) {
					$all_meta = get_post_meta( $product_id, 'lkd_wm_new_sku', false );
					if ( is_array($all_meta) ) {
						foreach ( $all_meta as $meta_value ) {
							if ( ! empty($meta_value) ) { $new_sku = (string)$meta_value; break; }
						}
					}
					if ( $new_sku === '' ) {
						$new_sku = (string) get_post_meta( $product_id, 'lkd_wm_new_sku', true );
					}
					if ( $new_sku === '' && ! empty($extra_keys) ) {
						foreach ( $extra_keys as $meta_key ) {
							$alt = (string) get_post_meta( $product_id, $meta_key, true );
							if ( $alt !== '' ) { $new_sku = $alt; break; }
						}
					}
				}

				// If not found by original SKU, try picked map by new_sku
				if ( $picked === 0 && $new_sku !== '' && isset($skuToPicked[$new_sku]) ) {
					$picked = (int) $skuToPicked[$new_sku];
				}

				$unit = ($qty > 0) ? (float)($itm['subtotal'] ?? 0) / $qty : 0.0;
				$fmtU = function_exists('wc_price') ? wc_price($unit, ['currency'=>$currency]) : number_format($unit, 2);
				$tot  = (float)($itm['total'] ?? 0);
				$fmtT = function_exists('wc_price') ? wc_price($tot,  ['currency'=>$currency]) : number_format($tot, 2);

				$rowStyle = ($picked >= $qty && $qty > 0) ? 'background-color:#a9fca9;' : (($picked > 0) ? 'background-color:#ffffc7;' : '');
				?>
				<tr class="picked-status"
				    style="border-top:1px solid #ddd;<?php echo esc_attr($rowStyle); ?>"
				    data-sku="<?php echo esc_attr($sku); ?>"
				    data-original-sku="<?php echo esc_attr($sku); ?>"
				    data-new-sku="<?php echo esc_attr($new_sku); ?>"
				    data-max="<?php echo (int)$qty; ?>">
					<td style="padding:4px;"><?php echo (int)$qty; ?></td>
					<td style="padding:4px;"><?php
						echo esc_html($name);
						if ($brand)   echo '<br><em style="font-size:.85em;">'.esc_html($brand).'</em>';
						if ($sku)     echo '<br><small style="font-size:.8em;">SKU: '.esc_html($sku).'</small>';
						echo '<br><small style="font-size:.8em;">New SKU: '.( $new_sku !== '' ? esc_html($new_sku) : esc_html__('No new SKU','aaa-oc') ).'</small>';
					?></td>
					<td style="padding:4px;text-align:right;"><?php echo wp_kses_post($fmtU); ?></td>
					<td style="padding:4px;text-align:right;"><strong><?php echo wp_kses_post($fmtT); ?></strong></td>
					<td style="text-align:center;">
						<?php if ($mode === 'pick') : ?>
							<button class="decrement-picked" data-sku="<?php echo esc_attr($sku); ?>" data-new-sku="<?php echo esc_attr($new_sku); ?>">-</button>
							<span class="picked-count"><?php echo (int)$picked; ?></span>
							<span class="picked-text"><?php echo ' ' . esc_html__('of','aaa-oc') . ' ' . (int)$qty . ' ' . esc_html__('Picked','aaa-oc'); ?></span>
							<button class="increment-picked" data-sku="<?php echo esc_attr($sku); ?>" data-new-sku="<?php echo esc_attr($new_sku); ?>">+</button>
						<?php else : ?>
							<span class="picked-count"><?php echo (int)min($picked, (int)$qty); ?></span>
							<span class="picked-text"><?php echo ' ' . esc_html__('of','aaa-oc') . ' ' . (int)$qty . ' ' . esc_html__('Packed','aaa-oc'); ?></span>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>

			</tbody>
		</table>
		<?php
		return (string)ob_get_clean();
	}
}
AAA_OC_Fulfillment_Products_Table_Hook::init();

endif;
