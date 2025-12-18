<?php
/**
 * Plugin Name: GPT Sales Diagnostics
 * Description: Health check for GPT-safe Woo + ATUM + Lokey reports stack.
 * Version: 1.0
 */

if (!defined('ABSPATH')) exit;

add_action('rest_api_init', function () {
    register_rest_route('gpt/v1', '/sales/diagnostics', [
        'methods'  => 'GET',
        'callback' => function() {
            $checks = [];

            // WooCommerce status
            $checks['woocommerce_active'] = class_exists('WooCommerce');

            // ATUM plugin
            $checks['atum_active'] = class_exists('Atum\Components\AtumCapabilities');

            // BeRocket brands taxonomy
            $checks['berocket_brand_taxonomy'] = taxonomy_exists('berocket_brand');

            // Lokey reports routes
            $checks['lokeyreports_summary'] = rest_get_server()->get_routes()['/lokeyreports/v1/sales/summary'] ?? false;

            // GPT core routes presence
            $needed_routes = [
                '/gpt/v1/products',
                '/gpt/v1/purchase-orders',
                '/gpt/v1/suppliers',
                '/gpt/v1/brands',
                '/gpt/v1/sales/summary',
                '/gpt/v1/sales/top',
                '/gpt/v1/sales/overview',
            ];

            $routes = rest_get_server()->get_routes();
            $available = array_keys($routes);

            $missing = array_filter($needed_routes, fn($r) => !in_array($r, $available));

            return new WP_REST_Response([
                'status'    => empty($missing) ? 'healthy' : 'incomplete',
                'timestamp' => gmdate('Y-m-d H:i:s'),
                'checks'    => $checks,
                'missing_routes' => array_values($missing),
                'count_active_plugins' => count(get_option('active_plugins', [])),
            ], 200);
        },
        'permission_callback' => '__return_true',
    ]);
});
