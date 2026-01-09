<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/payconfirm/index/class-aaa-oc-payconfirm-register.php
 * Purpose: Reuse CPT slug 'payment-confirmation' if present; register all post & user metas up-front.
 * Version: 1.0.3
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAA_OC_PayConfirm_Register {
    /**
     * Initialize hooks.
     */
    public static function init() {
        add_action( 'init', [ __CLASS__, 'maybe_register_cpt' ], 5 );
        add_action( 'init', [ __CLASS__, 'register_post_meta' ], 6 );
        add_action( 'init', [ __CLASS__, 'register_user_meta' ], 6 );
    }

    /**
     * Register the payment-confirmation custom post type if it does not already exist.
     */
    public static function maybe_register_cpt() {
        if ( post_type_exists( 'payment-confirmation' ) ) {
            return;
        }
        register_post_type( 'payment-confirmation', [
            'labels'              => [
                'name'          => 'Payment Confirmations',
                'singular_name' => 'Payment Confirmation',
            ],
            'public'              => true,
            'publicly_queryable'  => false,
            'exclude_from_search' => true,
            'has_archive'         => false,
            'rewrite'             => false,
            'query_var'           => false,
            'show_in_rest'        => false,
            'show_in_nav_menus'   => false,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'supports'            => [ 'title', 'editor', 'custom-fields', 'author' ],
            'menu_icon'           => 'dashicons-money-alt',
        ] );
    }

    /**
     * Register post meta fields for the payment-confirmation post type.
     */
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
                        '_pc_match_method'       => 'string', // kept for backward compatibility
                        '_pc_match_status'       => 'string', // NEW: matched | partial | unmatched
                        '_pc_match_reason'       => 'string', // NEW: human-friendly reason alias
                        '_pc_last_match_result'  => 'object',
                        // Added meta for storing user ID when a match is made
                        '_pc_user_id'            => 'integer',
                ];
                foreach ( $keys as $key => $type ) {
                        register_post_meta('payment-confirmation', $key, [
                                'single'           => true,
                                'type'             => $type,
                                'show_in_rest'     => false,
                'auth_callback'    => function() {
                    return current_user_can( 'edit_posts' );
                },
                'sanitize_callback' => function( $v ) {
                    return is_scalar( $v ) ? wp_kses_post( $v ) : $v;
                },
            ] );
        }
    }

    /**
     * Register user meta for pay accounts.
     */
    public static function register_user_meta() {
        register_meta( 'user', 'aaa_oc_pay_accounts', [
            'type'             => 'array',
            'single'           => true,
            'show_in_rest'     => false,
            'auth_callback'    => function() {
                return current_user_can( 'edit_users' );
            },
            'sanitize_callback' => function( $v ) {
                return is_array( $v ) ? array_map( 'sanitize_text_field', $v ) : [];
            },
            'default'          => [],
        ] );
    }
}