<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/payconfirm/index/class-aaa-oc-payconfirm-register.php
 * Purpose: Register/normalize CPT 'payment-confirmation' and its post/user meta.
 * Version: 1.0.5
 */
if ( ! defined('ABSPATH') ) { exit; }

class AAA_OC_PayConfirm_Register {
	public static function init() {
		// Normalize args even if CPT was registered earlier by someone else
		add_filter('register_post_type_args', [__CLASS__, 'force_admin_ui_under_workflow'], 9, 2);

		// Register CPT if missing
		add_action('init', [__CLASS__, 'maybe_register_cpt'], 5);

		// Register meta
		add_action('init', [__CLASS__, 'register_post_meta'], 6);
		add_action('init', [__CLASS__, 'register_user_meta'], 6);

		// Safety net: ensure a submenu under Workflow
		add_action('admin_menu', [__CLASS__, 'submenu_fallback'], 99);
	}

	/**
	 * If the CPT exists, force it to appear as a SUBMENU under the Workflow parent.
	 * Parent slug: aaa-oc-workflow-board
	 */
	public static function force_admin_ui_under_workflow( $args, $post_type ) {
		if ( $post_type !== 'payment-confirmation' ) return $args;

		$args['public']        = $args['public']        ?? false;
		$args['show_ui']       = true;
		// Put CPT under Workflow top menu
		$args['show_in_menu']  = 'aaa-oc-workflow-board';
		// Not a top-level icon when nested
		unset($args['menu_icon']);

		$args['supports']      = $args['supports']      ?? ['title','editor','custom-fields','author'];
		$args['show_in_rest']  = true;
		$args['rest_base']     = 'payment-confirmation';
		$args['rest_controller_class'] = 'WP_REST_Posts_Controller';
		return $args;
	}

	public static function maybe_register_cpt() {
		if ( post_type_exists('payment-confirmation') ) return;

		register_post_type('payment-confirmation', [
			'labels'      => ['name'=>'Payment Confirmations','singular_name'=>'Payment Confirmation'],
			'public'      => false,
			'show_ui'     => true,
			// Nest under Workflow
			'show_in_menu'=> 'aaa-oc-workflow-board',
			'show_in_rest'=> true,
			'rest_base'   => 'payment-confirmation',
			'rest_controller_class' => 'WP_REST_Posts_Controller',
			'supports'    => ['title','editor','custom-fields','author'],
			// no menu_icon needed when nested
		]);
	}

	public static function register_post_meta() {
		$keys = [
			'_pc_payment_method'     => 'string',
			'_pc_account_name'       => 'string',
			'_pc_amount'             => 'number',
			'_pc_sent_on'            => 'string',
			'_pc_txn'                => 'string',
			'_pc_memo'               => 'string',
			'_pc_matched_order_id'   => 'integer',
			'_pc_match_confidence'   => 'number',
			'_pc_match_method'       => 'string',
			'_pc_match_status'       => 'string',
			'_pc_match_reason'       => 'string',
			'_pc_last_match_result'  => 'object',
		];
		foreach ( $keys as $key => $type ) {
			register_post_meta('payment-confirmation', $key, [
				'single' => true,
				'type'   => $type,
				'show_in_rest'  => false,
				'auth_callback' => function(){ return current_user_can('edit_posts'); },
				'sanitize_callback'=> function($v){ return is_scalar($v) ? wp_kses_post($v) : $v; },
			]);
		}
	}

	public static function register_user_meta() {
		register_meta('user', 'aaa_oc_pay_accounts', [
			'type'             => 'array',
			'single'           => true,
			'show_in_rest'     => false,
			'auth_callback'    => function(){ return current_user_can('edit_users'); },
			'sanitize_callback'=> function($v){
				if ( ! is_array($v) ) return [];
				$out = [];
				foreach ($v as $method=>$aliases) {
					$k = strtolower( preg_replace('/[^a-z0-9]+/i','',(string)$method) );
					if ( $k === '' ) continue;
					$list = is_array($aliases) ? $aliases : [ $aliases ];
					$list = array_map('strval', $list);
					$list = array_map('sanitize_text_field', $list);
					$list = array_values(array_unique(array_filter($list)));
					if ( $list ) $out[$k] = $list;
				}
				return $out;
			},
			'default'          => [],
		]);
	}

	/**
	 * Fallback in case some other registrar blocks the CPT menu:
	 * explicitly add a submenu item pointing to the CPT list under Workflow.
	 */
	public static function submenu_fallback() {
		$parent = 'aaa-oc-workflow-board';
		$slug   = 'edit.php?post_type=payment-confirmation';

		// If WordPress already added a submenu for this CPT under our parent, skip
		global $submenu;
		if ( isset($submenu[$parent]) ) {
			foreach ( (array) $submenu[$parent] as $item ) {
				if ( isset($item[2]) && $item[2] === $slug ) {
					return;
				}
			}
		}

		add_submenu_page(
			$parent,
			'Payment Confirmations',
			'Payment Confirmations',
			'edit_posts',
			$slug
		);
	}
}
