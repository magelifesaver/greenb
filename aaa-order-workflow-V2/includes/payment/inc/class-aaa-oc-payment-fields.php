<?php
/**
 * File: /aaa-order-workflow/includes/payment/class-aaa-oc-payment-fields.php
 * Purpose: Handles fetching, defaulting, and ensuring payment rows,
 *          now including driver, tip, and envelope_outstanding columns.
 */
if (!defined('ABSPATH')) {
    exit;
}

class AAA_OC_Payment_Fields {

    /**
     * Fetch payment data for modal, joining in authoritative index totals.
     */
	public static function get_payment_fields( $order_id ) {
	    global $wpdb;

	    $pay_t = $wpdb->prefix . 'aaa_oc_payment_index';
	    $idx_t = $wpdb->prefix . 'aaa_oc_order_index';

	    $sql = $wpdb->prepare( "
	        SELECT
	            p.*,
	            /* authoritative order total from order index if available */
	            COALESCE( i._order_total,          p.aaa_oc_order_total    ) AS aaa_oc_order_total,
	            /* authoritative e-payment total from order index if available */
	            COALESCE( i.aaa_oc_epayment_total, p.aaa_oc_epayment_total ) AS aaa_oc_epayment_total,
	            /* fall back to order-index driver_id when p.driver_id is 0 or NULL */
	            COALESCE( NULLIF( p.driver_id, 0 ), i.driver_id )          AS driver_id
	        FROM   {$pay_t} p
	        LEFT JOIN {$idx_t} i ON i.order_id = p.order_id
	        WHERE  p.order_id = %d
	        LIMIT  1
	    ", $order_id );

	    $result = $wpdb->get_row( $sql, ARRAY_A );

	    return $result ?: self::get_default_payment_fields( $order_id );
	}

/**
 * Ensure a payment‐index row exists for the given order ID,
 * initializing the new tip fields correctly.
 */
	public static function ensure_payment_row_exists( $order_id ) {
	    global $wpdb;

	    $table  = $wpdb->prefix . 'aaa_oc_payment_index';
	    $exists = (int) $wpdb->get_var( 
	        $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE order_id = %d", $order_id ) 
	    );
	    if ( $exists ) {
	        return;
	    }

	    $order           = wc_get_order( $order_id );
	    $order_total     = $order ? (float) $order->get_total() : 0.00;
	    $epayment_total  = 0.00;                             // no e-payments on creation
	    $original_tip    = (float) $order->get_meta( '_wpslash_tip', true );
	    $driver_id       = (int)   $order->get_meta( '_driver_id',  true );
	    $epayment_tip    = 0.00;                             // start at zero, not order_total
	    $total_order_tip = $original_tip;                    // just the front-end tip initially

	    $wpdb->insert( 
	        $table, 
	        [
	            'order_id'              => $order_id,
	            'aaa_oc_cash_amount'    => 0.00,
	            'aaa_oc_zelle_amount'   => 0.00,
	            'aaa_oc_venmo_amount'   => 0.00,
	            'aaa_oc_applepay_amount'=> 0.00,
	            'aaa_oc_cashapp_amount' => 0.00,
	            'aaa_oc_epayment_total' => $epayment_total,
	            'driver_id'             => $driver_id,
	            'epayment_tip'          => $epayment_tip,
	            'total_order_tip'       => $total_order_tip,
	            'aaa_oc_tip_total'      => $original_tip,
	            'aaa_oc_payrec_total'   => 0.00,
	            'aaa_oc_order_balance'  => $order_total,
	            'aaa_oc_order_total'    => $order_total,
	            'aaa_oc_payment_status' => 'unpaid',
	            'cleared'               => 0,
	            'envelope_outstanding'  => 0,
	            'last_updated_by'       => 'system',
	        ]
	    );
	}

    /**
     * Default fields if no row found – include new columns.
     */
    private static function get_default_payment_fields($order_id = 0) {
        $order       = wc_get_order($order_id);
        $order_total = $order ? (float) $order->get_total() : 0.00;

        return [
            'aaa_oc_cash_amount'    => '0.00',
            'aaa_oc_zelle_amount'   => '0.00',
            'aaa_oc_venmo_amount'   => '0.00',
            'aaa_oc_applepay_amount'=> '0.00',
            'aaa_oc_cashapp_amount' => '0.00',
            'aaa_oc_epayment_total' => '0.00',
            'driver_id'             => '0',
            'epayment_tip'          => '0.00',
            'total_order_tip'       => '0.00',
            'aaa_oc_tip_total'      => '0.00',
            'aaa_oc_payrec_total'   => '0.00',
            'aaa_oc_order_balance'  => number_format($order_total, 2, '.', ''),
            'aaa_oc_order_total'    => number_format($order_total, 2, '.', ''),
            'aaa_oc_payment_status' => 'unpaid',
            'cleared'               => 0,
            'envelope_outstanding'  => 0,
            'last_updated'          => '',
            'last_updated_by'       => '',
            'notes_summary'         => '',
            'change_log_id'         => null
        ];
    }

    /**
     * Define modal fields.
     */
    public static function register_payment_fields() {
        return [
            'aaa_oc_cash_amount' => [
                'label' => 'Cash Amount',
                'type'  => 'number'
            ],
            'aaa_oc_zelle_amount' => [
                'label' => 'Zelle Amount',
                'type'  => 'number'
            ],
            'aaa_oc_venmo_amount' => [
                'label' => 'Venmo Amount',
                'type'  => 'number'
            ],
            'aaa_oc_applepay_amount' => [
                'label' => 'ApplePay Amount',
                'type'  => 'number'
            ],
            'aaa_oc_cashapp_amount' => [
                'label' => 'CashApp Amount',
                'type'  => 'number'
            ],
            'aaa_oc_epayment_total' => [
                'label'    => 'ePayment Total',
                'type'     => 'number',
                'readonly' => true
            ],
            'driver_id' => [
                'label'   => 'Driver',
                'type'    => 'select',
                'options' => self::get_driver_options(),
                'readonly'=> false
            ],
            'aaa_oc_tip_total' => [
                'label'    => 'Tip Total',
                'type'     => 'number',
                'readonly' => true
            ],
            'epayment_tip' => [
                'label'    => 'ePayment Tip',
                'type'     => 'number',
                'readonly' => true
            ],
            'total_order_tip' => [
                'label'    => 'Total Order Tip',
                'type'     => 'number',
                'readonly' => true
            ],
            'aaa_oc_payrec_total' => [
                'label'    => 'Total Payment Recorded',
                'type'     => 'number',
                'readonly' => true
            ],
            'aaa_oc_order_balance' => [
                'label'    => 'Order Balance',
                'type'     => 'number',
                'readonly' => true
            ],
            'aaa_oc_order_total' => [
                'label'    => 'Order Total',
                'type'     => 'number',
                'readonly' => true
            ],
            'aaa_oc_payment_status' => [
                'label'   => 'Payment Status',
                'type'    => 'select',
                'options' => [
                    'unpaid'  => 'Unpaid',
                    'partial' => 'Partial',
                    'paid'    => 'Paid'
                ]
            ],
            'envelope_outstanding'=> [ 'label'=> 'Envelope Outstanding','type'=>'checkbox' ],
            'cleared' => [
                'label' => 'Payment Cleared',
                'type'  => 'checkbox'
            ]
        ];
    }

    /**
     * Helper to load driver options for the Driver select.
     */
    public static function get_driver_options() {
        $drivers = get_users([
            'role'    => 'driver',
            'orderby' => 'display_name',
            'fields'  => ['ID','display_name']
        ]);
        $opts = [0 => '— Select driver —'];
        foreach ($drivers as $u) {
            $opts[$u->ID] = $u->display_name;
        }
        return $opts;
    }
}