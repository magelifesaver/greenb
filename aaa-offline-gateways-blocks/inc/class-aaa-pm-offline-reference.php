<?php
/**
 * Offline reference flow
 * - Render ONE "Complete Your Payment" / "Edit reference number" UI
 * - Hide Woo "Pay" action everywhere when a reference is required
 * - On TY: redirect to View Order after saving reference
 *
 * Path: /wp-content/plugins/aaa-offline-gateways-blocks/inc/class-aaa-pm-offline-reference.php
 * File Version: 1.4.3
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class AAA_PM_Offline_Reference {
	const META_KEY    = '_aaa_pm_reference';
	const AJAX_ACTION = 'aaa_pm_save_reference';

	private static $rendered = false; // prevent duplicate UI on Thank-You

	public static function init() : void {
		// Render UI in both contexts, but guard so it shows once per request.
		add_action( 'woocommerce_before_thankyou', [ __CLASS__, 'render_reference_ui' ], 5 );
		add_action( 'woocommerce_order_details_before_order_table', [ __CLASS__, 'render_reference_ui' ], 5 );

		// Hide Pay button universally when a reference is required.
		add_filter( 'woocommerce_my_account_my_orders_actions', [ __CLASS__, 'my_orders_actions' ], 10, 2 );
		add_filter( 'woocommerce_valid_order_statuses_for_payment', [ __CLASS__, 'hide_all_core_pay_buttons' ], 10, 2 );

		// AJAX save
		add_action( 'wp_ajax_' . self::AJAX_ACTION,        [ __CLASS__, 'ajax_save_reference' ] );
		add_action( 'wp_ajax_nopriv_' . self::AJAX_ACTION, [ __CLASS__, 'ajax_save_reference' ] );
	}

	private static function get_pm_settings( string $pm ) : array {
		return get_option( 'woocommerce_' . $pm . '_settings', array() ) ?: array();
	}
	private static function requires_reference( string $pm ) : bool {
		$s = self::get_pm_settings( $pm );
		return ( isset( $s['require_reference'] ) && $s['require_reference'] === 'yes' );
	}
	private static function ref_label( string $pm ) : string {
		$s = self::get_pm_settings( $pm );
		return $s['reference_label'] ?? __( 'Reference number', 'aaa-offline-gateways-blocks' );
	}
	private static function pm_title( string $pm ) : string {
		$gws = WC()->payment_gateways() ? WC()->payment_gateways()->get_available_payment_gateways() : array();
		if ( isset( $gws[ $pm ] ) && method_exists( $gws[ $pm ], 'get_title' ) ) {
			return trim( wp_strip_all_tags( $gws[ $pm ]->get_title() ) );
		}
		$s = self::get_pm_settings( $pm );
		return $s['title'] ?? ucwords( str_replace( [ 'pay_with_', '_' ], [ '', ' ' ], $pm ) );
	}

	/* -------- My Account Orders list: remove "Pay" action -------- */
	public static function my_orders_actions( $actions, $order ) {
		if ( $order instanceof WC_Order ) {
			$pm  = (string) $order->get_payment_method();
			if ( $pm && self::requires_reference( $pm ) ) {
				unset( $actions['pay'] );
			}
		}
		return $actions;
	}

	/* -------- TY/View Order: hide core Pay button entirely -------- */
	public static function hide_all_core_pay_buttons( $statuses, $order ) {
		if ( $order instanceof WC_Order ) {
			$pm = (string) $order->get_payment_method();
			if ( $pm && self::requires_reference( $pm ) ) {
				return array(); // hide Pay globally for this order
			}
		}
		return $statuses;
	}

	public static function render_reference_ui( $order ) {
		if ( self::$rendered ) { return; } // show once per request
		if ( is_numeric( $order ) ) { $order = wc_get_order( $order ); }
		if ( ! $order instanceof WC_Order ) { return; }

		$pm = (string) $order->get_payment_method();
		if ( ! $pm || ! self::requires_reference( $pm ) ) { return; }

		self::$rendered = true;

		$order_id     = $order->get_id();
		$nonce        = wp_create_nonce( 'aaa_pm_ref_' . $order_id );
		$title        = self::pm_title( $pm );
		$field        = self::ref_label( $pm );
		$existing     = (string) $order->get_meta( self::META_KEY );
		$is_ty        = function_exists( 'is_order_received_page' ) ? is_order_received_page() : false;
		$redirect_url = $order->get_view_order_url();

		$status_msg = sprintf( __( '%s submitted', 'aaa-offline-gateways-blocks' ), $title );
		$cta_label  = $existing ? __( 'Edit reference number', 'aaa-offline-gateways-blocks' ) : __( 'Complete Your Payment', 'aaa-offline-gateways-blocks' );
		?>
		<div id="aaa-pm-ref-wrap"
			data-order="<?php echo esc_attr( $order_id ); ?>"
			data-nonce="<?php echo esc_attr( $nonce ); ?>"
			data-redirect="<?php echo esc_attr( $is_ty ? $redirect_url : '' ); ?>">

			<button type="button" class="button button-primary" id="aaa-pm-ref-open">
				<?php echo esc_html( $cta_label ); ?>
			</button>

			<?php if ( $existing ) : ?>
				<div class="woocommerce-message" role="alert" style="display:inline-block;margin-left:8px;">
					<?php echo esc_html( $field . ': ' . $existing ); ?> — <strong><?php echo esc_html( $status_msg ); ?></strong>
				</div>
			<?php endif; ?>
		</div>

		<style>
			.aaa-pm-ref-ov {position:fixed;inset:0;background:rgba(0,0,0,.6);display:none;align-items:center;justify-content:center;z-index:9999;}
			.aaa-pm-ref-box{background:#fff;border-radius:8px;max-width:480px;width:92%;padding:18px;box-shadow:0 4px 20px rgba(0,0,0,.25);}
			.aaa-pm-ref-row{margin:12px 0;}
			.aaa-pm-ref-actions{margin-top:14px;display:flex;gap:8px;justify-content:flex-end;}
		</style>

		<div class="aaa-pm-ref-ov" id="aaa-pm-ref-ov">
			<div class="aaa-pm-ref-box">
				<h3><?php esc_html_e( 'Enter Payment Reference', 'aaa-offline-gateways-blocks' ); ?></h3>
				<p><?php printf( __( 'Order %1$s — Total %2$s', 'aaa-offline-gateways-blocks' ),
					esc_html( $order->get_order_number() ),
					wp_kses_post( $order->get_formatted_order_total() ) ); ?></p>
				<div class="aaa-pm-ref-row">
					<label for="aaa_pm_ref_input"><?php echo esc_html( $field ); ?></label>
					<input type="text" id="aaa_pm_ref_input" value="<?php echo esc_attr( $existing ); ?>">
				</div>
				<div class="aaa-pm-ref-actions">
					<button type="button" class="button" id="aaa-pm-ref-cancel"><?php esc_html_e( 'Close', 'aaa-offline-gateways-blocks' ); ?></button>
					<button type="button" class="button button-primary" id="aaa-pm-ref-save"><?php esc_html_e( 'Submit', 'aaa-offline-gateways-blocks' ); ?></button>
				</div>
				<p id="aaa-pm-ref-msg" style="margin-top:10px;display:none;"></p>
			</div>
		</div>

		<script>
		(function(){
			const wrap=document.getElementById('aaa-pm-ref-wrap'); if(!wrap) return;
			const order=wrap.dataset.order,nonce=wrap.dataset.nonce,redirect=wrap.dataset.redirect||'';
			const ov=document.getElementById('aaa-pm-ref-ov');
			const openBt=document.getElementById('aaa-pm-ref-open');
			const saveBt=document.getElementById('aaa-pm-ref-save');
			const canBt=document.getElementById('aaa-pm-ref-cancel');
			const input=document.getElementById('aaa_pm_ref_input');
			const msg=document.getElementById('aaa-pm-ref-msg');

			function open(){ ov.style.display='flex'; setTimeout(()=>input&&input.focus(),10); }
			function close(){ ov.style.display='none'; }

			function save(){
				const ref=(input.value||'').trim();
				if(!ref){ msg.style.display='block'; msg.style.color='#b32d2e'; msg.textContent='<?php echo esc_js( __( 'Please enter a value.', 'aaa-offline-gateways-blocks' ) ); ?>'; return; }
				msg.style.display='block'; msg.style.color='#333'; msg.textContent='<?php echo esc_js( __( 'Saving…', 'aaa-offline-gateways-blocks' ) ); ?>';
				fetch('<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>',{
					method:'POST',credentials:'same-origin',
					headers:{'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},
					body:new URLSearchParams({action:'<?php echo esc_js( self::AJAX_ACTION ); ?>',order_id:order,nonce:nonce,reference:ref}).toString()
				}).then(r=>r.json()).then(res=>{
					if(res && res.success){
						msg.style.color='#0073aa'; msg.textContent='<?php echo esc_js( __( 'Saved. Thank you!', 'aaa-offline-gateways-blocks' ) ); ?>';
						setTimeout(function(){
							if(redirect){ window.location.href = redirect; } else { window.location.reload(); }
						}, 400);
					}else{
						msg.style.color='#b32d2e'; msg.textContent=(res.data&&res.data.msg)||'<?php echo esc_js( __( 'Error saving.', 'aaa-offline-gateways-blocks' ) ); ?>';
					}
				}).catch(()=>{
					msg.style.color='#b32d2e'; msg.textContent='<?php echo esc_js( __( 'Network error.', 'aaa-offline-gateways-blocks' ) ); ?>';
				});
			}
			openBt.addEventListener('click',open);
			canBt.addEventListener('click',close);
			saveBt.addEventListener('click',save);
		})();
		</script>
		<?php
	}

	public static function ajax_save_reference() : void {
		$order_id = absint( $_POST['order_id'] ?? 0 );
		$nonce    = (string) ($_POST['nonce'] ?? '');
		$ref      = sanitize_text_field( wp_unslash( $_POST['reference'] ?? '' ) );
		if ( ! $order_id || ! wp_verify_nonce( $nonce, 'aaa_pm_ref_' . $order_id ) ) {
			wp_send_json_error( [ 'msg'=>__( 'Invalid request.', 'aaa-offline-gateways-blocks' ) ], 400 );
		}
		$order = wc_get_order( $order_id ); if ( ! $order ) {
			wp_send_json_error( [ 'msg'=>__( 'Order not found.', 'aaa-offline-gateways-blocks' ) ], 404 );
		}
		if ( $ref === '' ) {
			wp_send_json_error( [ 'msg'=>__( 'Reference is required.', 'aaa-offline-gateways-blocks' ) ], 400 );
		}
		$order->update_meta_data( self::META_KEY, $ref );
		$order->save();
		wp_send_json_success( [ 'ref'=>$ref ] );
	}
}
AAA_PM_Offline_Reference::init();
