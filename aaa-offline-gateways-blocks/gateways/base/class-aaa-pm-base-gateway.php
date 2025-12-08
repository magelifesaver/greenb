<?php
/**
 * Base Offline Gateway (shared settings/logic)
 * File: /wp-content/plugins/aaa-offline-gateways-blocks/gateways/base/class-aaa-pm-base-gateway.php
 * File Version: 1.4.3
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

abstract class AAA_PM_Gateway_Base extends WC_Payment_Gateway {
	protected $instructions, $backend_only, $enable_default_order_status, $default_order_status, $force_status_override;
	protected $enable_tipping, $tipping_default_amount, $tipping_presets;
	protected $require_reference, $reference_label, $reference_placeholder;

	public function __construct( $id, $title, $method_title, $method_description ) {
		$this->id = $id;
		$this->method_title = $method_title;
		$this->method_description = $method_description;
		$this->has_fields = true;
		$this->supports = array( 'products' );

		$this->init_form_fields();
		$this->init_settings();

		$this->title   = $this->get_option( 'title', $title );
		$this->enabled = $this->get_option( 'enabled', 'no' );

		$this->backend_only                = $this->get_option( 'backend_only', 'no' );
		$this->description                 = $this->get_option( 'description', '' );
		$this->instructions                = $this->get_option( 'instructions', '' );
		$this->enable_default_order_status = $this->get_option( 'enable_default_order_status', 'no' );
		$this->default_order_status        = $this->get_option( 'default_order_status', 'wc-on-hold' );
		$this->force_status_override       = $this->get_option( 'force_status_override', 'no' );

		$this->enable_tipping         = $this->get_option( 'enable_tipping', 'no' );
		$this->tipping_default_amount = $this->get_option( 'tipping_default_amount', '' );
		$this->tipping_presets        = $this->get_option( 'tipping_presets', '' );

		$this->require_reference     = $this->get_option( 'require_reference', 'no' );
		$this->reference_label       = $this->get_option( 'reference_label', __( 'Reference number', 'aaa-offline-gateways-blocks' ) );
		$this->reference_placeholder = $this->get_option( 'reference_placeholder', '' );

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, [ $this, 'process_admin_options' ] );
		add_action( 'woocommerce_checkout_update_order_meta', [ $this, 'save_payment_fields' ] );
		add_action( 'woocommerce_admin_order_data_after_billing_address', [ $this, 'display_payment_fields_in_admin' ] );
		add_action( 'woocommerce_thankyou_' . $this->id, [ $this, 'thankyou_page' ] );
	}

	public function is_available() {
		if ( 'yes' === $this->backend_only && ! is_admin() ) {
			return false;
		}
		return parent::is_available();
	}

	public function init_form_fields() {
		$statuses = array();
		foreach ( wc_get_order_statuses() as $k => $v ) { $statuses[ $k ] = $v; }
		$this->form_fields = [
			'enabled' => [ 'title'=>__('Enable/Disable','aaa-offline-gateways-blocks'), 'type'=>'checkbox', 'label'=>__('Enable Payment Method','aaa-offline-gateways-blocks'), 'default'=>'no' ],
			'backend_only' => [ 'title'=>__('Backend Only','aaa-offline-gateways-blocks'), 'type'=>'checkbox', 'label'=>__('Only show in Woo admin','aaa-offline-gateways-blocks'), 'default'=>'no' ],
			'title' => [ 'title'=>__('Title','aaa-offline-gateways-blocks'), 'type'=>'text', 'default'=>'' ],
			'description' => [ 'title'=>__('Description','aaa-offline-gateways-blocks'), 'type'=>'textarea', 'default'=>'' ],
			'instructions' => [ 'title'=>__('Instructions (TY, emails, My Account)','aaa-offline-gateways-blocks'), 'type'=>'textarea', 'description'=>__('Vars: {order_number}, {order_total}, {payment_method}','aaa-offline-gateways-blocks'), 'default'=>'' ],

			'enable_default_order_status' => [ 'title'=>__('Enable Custom Default Order Status','aaa-offline-gateways-blocks'), 'type'=>'checkbox', 'label'=>__('Set status for new orders','aaa-offline-gateways-blocks'), 'default'=>'no' ],
			'default_order_status' => [ 'title'=>__('Default Order Status','aaa-offline-gateways-blocks'), 'type'=>'select', 'default'=>'wc-on-hold', 'options'=>$statuses ],
			'force_status_override' => [ 'title'=>__('Force Status Override','aaa-offline-gateways-blocks'), 'type'=>'checkbox', 'label'=>__('Override even if already set','aaa-offline-gateways-blocks'), 'default'=>'no' ],

			// ✅ NEW FIELDS
			'mark_as_paid' => [
				'title'   => __( 'Mark Order as Paid', 'aaa-offline-gateways-blocks' ),
				'type'    => 'checkbox',
				'label'   => __( 'Automatically set the order as paid when this gateway is used', 'aaa-offline-gateways-blocks' ),
				'default' => 'no',
			],
			'mark_paid_amount' => [
				'title'   => __( 'Mark Paid Amount', 'aaa-offline-gateways-blocks' ),
				'type'    => 'checkbox',
				'label'   => __( 'Record the full order amount as paid when this gateway is used', 'aaa-offline-gateways-blocks' ),
				'default' => 'no',
			],

			'enable_tipping' => [ 'title'=>__('Enable Tipping','aaa-offline-gateways-blocks'), 'type'=>'checkbox', 'label'=>__('Show tipping UI','aaa-offline-gateways-blocks'), 'default'=>'no' ],
			'tipping_default_amount' => [ 'title'=>__('Default Tip Amount','aaa-offline-gateways-blocks'), 'type'=>'text', 'default'=>'' ],
			'tipping_presets' => [ 'title'=>__('Preset Tip Amounts','aaa-offline-gateways-blocks'), 'type'=>'text', 'description'=>__('Comma-separated (e.g., 2,3,5)','aaa-offline-gateways-blocks'), 'default'=>'' ],

			'require_reference' => [ 'title'=>__( 'Require Reference Entry', 'aaa-offline-gateways-blocks' ), 'type'=>'checkbox', 'label'=>__( 'Customer must provide reference/transaction number after placing order', 'aaa-offline-gateways-blocks' ), 'default'=>'no' ],
			'reference_label'  => [ 'title'=>__( 'Reference Field Label', 'aaa-offline-gateways-blocks' ), 'type'=>'text', 'default'=>__( 'Reference number', 'aaa-offline-gateways-blocks' ) ],
			'reference_placeholder' => [ 'title'=>__( 'Reference Placeholder', 'aaa-offline-gateways-blocks' ), 'type'=>'text', 'default'=>'' ],
		];
	}

	public function payment_fields() {
		// Tipping UI
		if ( 'yes' === $this->enable_tipping ) {
			$presets = array_filter( array_map( 'trim', explode( ',', (string) $this->tipping_presets ) ) );
			echo '<div class="aaa-tip">';
			echo '<label><strong>' . esc_html__( 'Tip (optional)', 'aaa-offline-gateways-blocks' ) . '</strong></label>';
			if ( $presets ) {
				echo '<div class="aaa-tip-presets">';
				foreach ( $presets as $amt_raw ) {
					$amt = floatval( $amt_raw );
					echo '<button type="button" class="button aaa-tip-btn" data-tip="' . esc_attr( $amt ) . '">' . wp_kses_post( wc_price( $amt ) ) . '</button> ';
				}
				echo '</div>';
			}
			$def = $this->tipping_default_amount !== '' ? floatval( $this->tipping_default_amount ) : '';
			echo '<div class="aaa-tip-input">';
			echo '<input type="number" step="0.5" min="0" name="aaa_tip_amount" id="aaa_tip_amount" value="' . esc_attr( $def ) . '" placeholder="' . esc_attr__( 'Enter tip', 'aaa-offline-gateways-blocks' ) . '" />';
			echo '<button type="button" class="button aaa-tip-apply">' . esc_html__( 'Apply tip', 'aaa-offline-gateways-blocks' ) . '</button>';
			echo '</div></div>';
			echo "<script>
			document.querySelectorAll('.aaa-tip-btn').forEach(function(b){
				b.addEventListener('click', function(){
					var i=document.getElementById('aaa_tip_amount');
					if(i){ i.value=b.dataset.tip; }
				});
			});
			</script>";
		}
		if ( $this->description ) {
			echo wpautop( wptexturize( $this->description ) );
		}
	}

	public function save_payment_fields( $order_id ) {
		if ( isset( $_POST['aaa_tip_amount'] ) ) {
			$raw = floatval( wp_unslash( $_POST['aaa_tip_amount'] ) );
			$tip = max( 0, round( $raw * 2 ) / 2 ); // enforce $0.50 increments
			update_post_meta( $order_id, '_wpslash_tip', $tip );
		}
	}

	public function display_payment_fields_in_admin( $order ) {
		// ✅ Print only for the real gateway used on this order (prevents duplicates)
		if ( ! $order instanceof WC_Order || $order->get_payment_method() !== $this->id ) {
			return;
		}
		$tip = get_post_meta( $order->get_id(), '_wpslash_tip', true );
		if ( $tip !== '' && $tip !== null ) {
			echo '<p><strong>' . __( 'Tip', 'aaa-offline-gateways-blocks' ) . ':</strong> ' . esc_html( $tip ) . '</p>';
		}
		$ref = get_post_meta( $order->get_id(), '_aaa_pm_reference', true );
		if ( $ref ) {
			$label = $this->reference_label ?: __( 'Reference number', 'aaa-offline-gateways-blocks' );
			echo '<p><strong>' . esc_html( $label ) . ':</strong> ' . esc_html( $ref ) . '</p>';
		}
	}

	public function thankyou_page( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) return;
		if ( $this->instructions ) {
			$repl = [
				'{order_number}'   => $order->get_order_number(),
				'{order_total}'    => wc_price( $order->get_total() ),
				'{payment_method}' => $order->get_payment_method_title(),
			];
			$msg = str_replace( array_keys( $repl ), array_values( $repl ), $this->instructions );
			echo '<div class="aaa-payment-instructions">' . wpautop( wp_kses_post( $msg ) ) . '</div>';
		}
	}

	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( 'yes' === $this->enable_default_order_status ) {
			$should = ( 'yes' === $this->force_status_override ) || $order->has_status( array( 'pending', 'failed' ) );
			if ( $should ) {
				$order->update_status( $this->default_order_status, sprintf( __( 'Payment via %s', 'aaa-offline-gateways-blocks' ), $this->title ) );
			}
		}

		// ✅ NEW: Mark as paid logic
		if ( 'yes' === $this->get_option( 'mark_as_paid', 'no' ) ) {
			$order->set_date_paid( current_time( 'mysql', true ) );

			if ( 'yes' === $this->get_option( 'mark_paid_amount', 'no' ) ) {
				$order->payment_complete(); // sets status + paid total
			} else {
				$order->save();
			}
		}

		wc_reduce_stock_levels( $order_id );
		WC()->cart->empty_cart();
		return [ 'result'=>'success', 'redirect'=>$this->get_return_url( $order ) ];
	}
}
