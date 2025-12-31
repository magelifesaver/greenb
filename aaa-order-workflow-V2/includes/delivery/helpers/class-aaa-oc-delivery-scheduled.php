<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/delivery/helpers/class-aaa-oc-delivery-scheduled.php
 * Purpose:
 *   • Provide [scheduled_deliveries_list] shortcode listing all orders at the configured "scheduled" status.
 *   • Append two quick status buttons (Schedule / Set Packed & Ready) into the board’s action area (Section 6.3)
 *     using existing hook 'aaa_oc_board_action_buttons'.
 * Options (scope=delivery):
 *   - delivery_scheduled_status (default: 'scheduled')
 *   - delivery_packed_ready_status (default: 'lkd-packed-ready')
 * Version: 1.0.1
 */
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'AAA_OC_Delivery_Scheduled' ) ) :

final class AAA_OC_Delivery_Scheduled {

	const OPT_SCOPE         = 'delivery';
	const OPT_KEY_SCHEDULED = 'delivery_scheduled_status';
	const OPT_KEY_PACKREADY = 'delivery_packed_ready_status';

	public static function init() : void {
		add_action( 'init', [ __CLASS__, 'maybe_seed_defaults' ], 1 );
		add_shortcode( 'scheduled_deliveries_list', [ __CLASS__, 'shortcode_list' ] );
		add_action( 'aaa_oc_board_action_buttons', [ __CLASS__, 'render_quick_status_buttons' ], 20, 1 );
	}

	public static function maybe_seed_defaults() : void {
		if ( ! function_exists( 'aaa_oc_get_option' ) || ! function_exists( 'aaa_oc_set_option' ) ) return;

		if ( null === aaa_oc_get_option( self::OPT_KEY_SCHEDULED, self::OPT_SCOPE, null ) ) {
			aaa_oc_set_option( self::OPT_KEY_SCHEDULED, 'scheduled', self::OPT_SCOPE );
		}
		if ( null === aaa_oc_get_option( self::OPT_KEY_PACKREADY, self::OPT_SCOPE, null ) ) {
			aaa_oc_set_option( self::OPT_KEY_PACKREADY, 'lkd-packed-ready', self::OPT_SCOPE );
		}
	}

	public static function shortcode_list( $atts ) : string {
		return '<div class="aaa-oc-scheduled-shortcode">' . self::render_scheduled_table() . '</div>';
	}

	private static function render_scheduled_table() : string {
		if ( ! function_exists( 'wc_get_orders' ) ) return '<em>WooCommerce not available.</em>';

		$scheduled = self::opt_scheduled();
		$statuses  = array_unique( [ $scheduled, 'wc-' . $scheduled ] );
		$today     = date_i18n( 'Y-m-d' );

		$orders = wc_get_orders( [
			'status' => $statuses,
			'type'   => 'shop_order',
			'limit'  => -1,
		] );

		ob_start();
		?>
		<style>.aaa-oc-scheduled-table tr.today-delivery{background:#e6ffe6}</style>
		<?php if ( empty( $orders ) ) : ?>
			<p><?php esc_html_e( 'No scheduled deliveries found.', 'aaa-oc' ); ?></p>
		<?php else : ?>
			<table class="widefat fixed striped aaa-oc-scheduled-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Delivery Date', 'aaa-oc' ); ?></th>
						<th><?php esc_html_e( 'Delivery Time', 'aaa-oc' ); ?></th>
						<th><?php esc_html_e( 'Order', 'aaa-oc' ); ?></th>
						<th><?php esc_html_e( 'Customer', 'aaa-oc' ); ?></th>
						<th><?php esc_html_e( 'Payment', 'aaa-oc' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ( $orders as $order ) :
					/** @var \WC_Order $order */
					$oid  = $order->get_id();
					$date = (string) get_post_meta( $oid, 'delivery_date_formatted', true );
					$time = (string) get_post_meta( $oid, 'delivery_time',        true );
					if ( $time === '' ) {
						$range = (string) get_post_meta( $oid, 'delivery_time_range', true );
						if ( preg_match( '/^\s*from\s+(.+?)\s*(?:-|–|to)\s*(?:to\s+)?(.+)/i', strtolower( $range ), $m ) ) {
							$time = trim( $m[1] ) . ' - ' . trim( $m[2] );
						}
					}
					$payStatus = (string) get_post_meta( $oid, 'aaa_oc_payment_status', true );
					$isToday   = $date && ( date_i18n( 'Y-m-d', strtotime( $date ) ) === $today );
					?>
					<tr class="<?php echo $isToday ? 'today-delivery' : ''; ?>">
						<td><?php echo $date ? esc_html( date_i18n( 'M j, Y', strtotime( $date ) ) ) : '—'; ?></td>
						<td><?php echo $time ? esc_html( $time ) : '—'; ?></td>
						<td><a href="<?php echo esc_url( admin_url( 'post.php?post=' . $oid . '&action=edit' ) ); ?>">#<?php echo esc_html( $oid ); ?></a></td>
						<td><?php echo esc_html( $order->get_formatted_billing_full_name() ); ?></td>
						<td><?php echo $payStatus ? esc_html( ucfirst( $payStatus ) ) : '—'; ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		<?php
		endif;
		return ob_get_clean();
	}

	public static function render_quick_status_buttons( array $ctx ) : void {
		$order_id = isset($ctx['order_id']) ? (int)$ctx['order_id'] : 0;
		if ( ! $order_id ) return;

		$current = '';
		if ( isset($ctx['oi']) && is_object($ctx['oi']) && ! empty($ctx['oi']->status) ) {
			$current = (string) $ctx['oi']->status; // no 'wc-'
		} else {
			$st = get_post_status( $order_id );
			$current = $st ? ltrim( (string) $st, 'wc-' ) : '';
		}

		$scheduled = self::opt_scheduled();     // e.g. 'scheduled'
		$packed    = self::opt_packed_ready();  // e.g. 'lkd-packed-ready'

		if ( $current === $packed ) {
			printf(
				'<button type="button" class="button button-modern" onclick="try{if(window.aaaOcCloseModal) aaaOcCloseModal();}catch(e){}; if(window.aaaOcChangeOrderStatus){ aaaOcChangeOrderStatus(%1$d, \'%2$s\'); }">%3$s</button>',
				$order_id,
				esc_js( $scheduled ),
				esc_html__( 'Schedule Delivery', 'aaa-oc' )
			);
		}

		if ( $current === $scheduled ) {
			printf(
				'<button type="button" class="button button-modern" onclick="try{if(window.aaaOcCloseModal) aaaOcCloseModal();}catch(e){}; if(window.aaaOcChangeOrderStatus){ aaaOcChangeOrderStatus(%1$d, \'%2$s\'); }">%3$s</button>',
				$order_id,
				esc_js( $packed ),
				esc_html__( 'Set Packed & Ready', 'aaa-oc' )
			);
		}
	}

	private static function opt_scheduled() : string {
		return function_exists('aaa_oc_get_option')
			? (string) aaa_oc_get_option( self::OPT_KEY_SCHEDULED, self::OPT_SCOPE, 'scheduled' )
			: 'scheduled';
	}
	private static function opt_packed_ready() : string {
		return function_exists('aaa_oc_get_option')
			? (string) aaa_oc_get_option( self::OPT_KEY_PACKREADY, self::OPT_SCOPE, 'lkd-packed-ready' )
			: 'lkd-packed-ready';
	}
}
AAA_OC_Delivery_Scheduled::init();
endif;
