<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/payconfirm/index/class-aaa-oc-payconfirm-rest.php
 * Purpose: Normalize CPT args for REST + provide /aaa-oc/v1/payconfirm fallback feed.
 * Version: 1.0.1
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class AAA_OC_PayConfirm_REST {
	public static function init() {
		// Make sure wp/v2 works and admin UI is visible even if another plugin registered CPT
		add_filter( 'register_post_type_args', [ __CLASS__, 'force_args' ], 10, 2 );
		add_action( 'rest_api_init', [ __CLASS__, 'fallback' ] );
	}

	public static function force_args( $args, $post_type ) {
		if ( 'payment-confirmation' !== $post_type ) return $args;

		$args['show_in_rest']        = true;
		$args['rest_base']           = 'payment-confirmation';
		$args['rest_controller_class']= 'WP_REST_Posts_Controller';

		// Also ensure UI visibility (some third-party plugins register hidden CPTs)
		$args['show_ui']      = true;
		$args['show_in_menu'] = true;
		$args['menu_icon']    = $args['menu_icon'] ?? 'dashicons-money-alt';
		return $args;
	}

	public static function fallback() {
		register_rest_route( 'aaa-oc', '/v1/payconfirm', [
			'methods'  => 'GET',
			'permission_callback' => '__return_true',
			'args' => [ 'per_page' => [ 'default' => 20 ] ],
			'callback' => function( WP_REST_Request $r ) {
				$pp = max(1, (int) $r->get_param('per_page'));
				$q  = new WP_Query([
					'post_type'      => 'payment-confirmation',
					'post_status'    => 'publish',
					'no_found_rows'  => true,
					'posts_per_page' => $pp,
				]);
				$out = [];
				foreach ( $q->posts as $p ) {
					$out[] = [
						'id'    => $p->ID,
						'date'  => get_post_time('c', true, $p),
						'title' => get_the_title($p),
						'link'  => get_permalink($p),
					];
				}
				return new WP_REST_Response( $out, 200 );
			}
		] );
	}
}
