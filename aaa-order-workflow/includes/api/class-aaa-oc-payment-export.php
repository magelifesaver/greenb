<?php
/**
 * File: /aaa-order-workflow/includes/api/class-aaa-oc-payment-export.php
 * Purpose: REST endpoint to export joined Payment + Order index data by date,
 *          with mapped fields for Google Sheets including Discounts and Fees
 *          split from fees_json (ignoring Tips).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAA_OC_Payment_Export_API {

    public static function init() {
        add_action( 'rest_api_init', [ __CLASS__, 'register_routes' ] );
    }

    public static function register_routes() {
        register_rest_route( 'aaa-oc/v1', '/payments', [
            'methods'  => 'GET',
            'callback' => [ __CLASS__, 'get_payments' ],
            'permission_callback' => '__return_true',
            'args'     => [
                'date' => [
                    'required' => true,
                    'type'     => 'string',
                    'validate_callback' => function( $param ) {
                        return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $param );
                    },
                ],
            ],
        ] );
    }

    public static function get_payments( WP_REST_Request $request ) {
        global $wpdb;

        $date = sanitize_text_field( $request->get_param( 'date' ) );

        $order_tbl   = $wpdb->prefix . 'aaa_oc_order_index';
        $payment_tbl = $wpdb->prefix . 'aaa_oc_payment_index';
        $users_tbl   = $wpdb->users;

        $sql = $wpdb->prepare("
            SELECT 
                p.id                          AS `PayID`,
                p.order_id                    AS `Order ID`,
                o.daily_order_number          AS `DON`,
                o.time_published              AS `Time Published`,

                CASE o._created_via
                    WHEN 'checkout'             THEN 'WEB'
                    WHEN 'admin'                THEN 'ADMIN'
                    WHEN 'phone'                THEN 'PH'
                    WHEN 'weedmaps'             THEN 'WM'
                    WHEN 'aaa-order-creator-v4' THEN 'OC'
                    ELSE o._created_via
                END                            AS `Source`,

                o.status                      AS `Status`,
                p.aaa_oc_payment_status       AS `Payment Status`,

                o.customer_name               AS `Customer Name`,
                p.epayment_detail             AS `Details`,

                p.subtotal                    AS `Subtotal`,
                p.aaa_oc_payrec_total         AS `Total Payment`,
                p.aaa_oc_order_total          AS `Order Total`,
                (p.aaa_oc_order_total - p.aaa_oc_payrec_total) AS `Remaining`,

                o.shipping_total              AS `Shipping Total`,
                o._funds_used                 AS `Store Credit Used`,

                /* placeholders for clean Discounts / Fees */
                0                             AS `Discounts`,
                0                             AS `Fees`,

                o._cart_discount              AS `Cart Discount`,
                p.epayment_tip                AS `eTip`,
                p.aaa_oc_tip_total            AS `wTip`,
                p.total_order_tip             AS `Total Tip`,

                p.driver_id                   AS `Driver ID`,
                u.display_name                AS `Driver Name`,

                CASE p.real_payment_method
                    WHEN 'cash'       THEN 'Cash'
                    WHEN 'zelle'      THEN 'Zelle'
                    WHEN 'venmo'      THEN 'Venmo'
                    WHEN 'applepay'   THEN 'ApplePay'
                    WHEN 'cashapp'    THEN 'CashApp'
                    WHEN 'creditcard' THEN 'Credit Card'
                    ELSE p.real_payment_method
                END                            AS `Real Method`,

                p.aaa_oc_cash_amount          AS `Cash`,
                p.aaa_oc_zelle_amount         AS `Zelle`,
                p.aaa_oc_venmo_amount         AS `Venmo`,
                p.aaa_oc_applepay_amount      AS `ApplePay`,
                p.aaa_oc_cashapp_amount       AS `CashApp`,
                p.aaa_oc_creditcard_amount    AS `Credit Card`,

                p.aaa_oc_epayment_total       AS `Total ePayment`,
                p.aaa_oc_order_balance        AS `Balance`,

                REPLACE(REPLACE(REPLACE(o.coupons, '[', ''), ']', ''), '\"', '') AS `Coupons`,

                p.processing_fee              AS `Processing Fee`,
                p.envelope_id                 AS `Envelope ID`,
                p.route_id                    AS `Route ID`,
                p.cleared                     AS `Cleared`,
                p.last_updated                AS `Last Updated`,
                p.last_updated_by             AS `Last Updated By`,
                p.notes_summary               AS `Notes Summary`,

                o.fees_json                   AS `DiscAndFeesRaw`

            FROM {$payment_tbl} p
            INNER JOIN {$order_tbl} o ON p.order_id = o.order_id
            LEFT JOIN {$users_tbl} u ON p.driver_id = u.ID
            WHERE DATE(o.time_published) = %s
            ORDER BY o.time_published ASC
        ", $date );

        $rows = $wpdb->get_results( $sql, ARRAY_A );

        // Post-process: split DiscAndFeesRaw into Discounts / Fees
        foreach ( $rows as &$row ) {
            $discount_total = 0;
            $fee_total      = 0;

            if ( ! empty( $row['DiscAndFeesRaw'] ) ) {
                $fees = json_decode( $row['DiscAndFeesRaw'], true );
                if ( is_array( $fees ) ) {
                    foreach ( $fees as $fee ) {
                        if ( isset($fee['name'], $fee['amount']) ) {
                            if ( strtolower($fee['name']) === 'tip' ) {
                                continue; // skip tips
                            }
                            $amount = (float) $fee['amount'];
                            if ( $amount < 0 ) {
                                $discount_total += $amount;
                            } elseif ( $amount > 0 ) {
                                $fee_total += $amount;
                            }
                        }
                    }
                }
            }

            // Overwrite placeholders
            $row['Discounts'] = $discount_total;
            $row['Fees']      = $fee_total;

            // Remove raw JSON column
            unset($row['DiscAndFeesRaw']);
        }
        unset($row);

        return rest_ensure_response( $rows );
    }
}

AAA_OC_Payment_Export_API::init();
