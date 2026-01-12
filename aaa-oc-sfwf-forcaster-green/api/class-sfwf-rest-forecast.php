<?php
/**
 * Filepath: sfwf/api/class-sfwf-rest-forecast.php
 * ---------------------------------------------------------------------------
 * REST API endpoint to expose forecast metrics for reporting and testing.
 * This version is for internal testing via ChatGPT (not production integration yet).
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class SFWF_REST_Forecast_Endpoint {

    public static function init() {
        add_action( 'rest_api_init', [ __CLASS__, 'register_routes' ] );
    }

    public static function register_routes() {
        register_rest_route( 'sfwf/v1', '/forecast', [
            'methods'  => 'GET',
            'callback' => [ __CLASS__, 'get_forecast_data' ],
            'permission_callback' => function() {
                return current_user_can('manage_woocommerce') || current_user_can('read');
            },
            'args' => [
                'limit' => [
                    'type' => 'integer',
                    'default' => 100,
                ],
                'status' => [
                    'type' => 'string',
                ],
            ]
        ]);
    }

    public static function get_forecast_data( $request ) {
        $limit  = intval( $request->get_param('limit') ?? 100 );
        $status = sanitize_text_field( $request->get_param('status') ?? '' );

        $args = [
            'status' => ['publish', 'private'],
            'limit'  => $limit,
            'return' => 'ids',
            'meta_query' => [
                ['key' => 'forecast_enable_reorder', 'value' => 'yes']
            ]
        ];

        if ( $status ) {
            $args['meta_query'][] = [
                'key' => 'forecast_sales_status',
                'value' => $status,
            ];
        }

        $products = wc_get_products( $args );
        $columns  = class_exists('SFWF_Column_Definitions') ? SFWF_Column_Definitions::get_columns() : [];
        $data = [];

        foreach ( $products as $product_id ) {
            $product = wc_get_product( $product_id );
            if ( ! $product ) continue;

            $row = [
                'product_id'   => $product_id,
                'product_name' => $product->get_name(),
                'sku'          => $product->get_sku(),
                'price'        => $product->get_price(),
            ];

            foreach ( $columns as $key => $col ) {
                $row[$key] = get_post_meta( $product_id, $key, true );
            }

            $data[] = $row;
        }

        // Log request for debugging
        error_log('[SFWF REST] Forecast endpoint hit with ' . count($data) . ' results.');

        return rest_ensure_response([
            'count' => count($data),
            'timestamp' => current_time('mysql'),
            'data'  => $data,
        ]);
    }
}

SFWF_REST_Forecast_Endpoint::init();
