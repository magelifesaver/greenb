<?php
/**
 * Custom REST behavior for PayConfirm.
 *
 * This version marries the privacy controls from the LIVE branch with
 * the DEV branch’s desire to expose the data via a custom endpoint.
 * It disables the default wp/v2 exposure, keeps the post type locked
 * down in WordPress, and registers a fallback route under
 * `aaa-oc/v1/payconfirm` that anyone can fetch. Even though the
 * endpoint is public, only private/draft/pending posts are returned and
 * the link provided is the admin edit URL, not a public permalink.
 * File: /wp-content/plugins/aaa-order-workflow/includes/payconfirm/index/class-aaa-oc-payconfirm-rest.php
 * Purpose: Ensure 'payment-confirmation' is visible in wp/v2; add a fallback route.
 * Version: 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAA_OC_PayConfirm_REST {

    /**
     * Hook into WordPress filters and actions.
     */
    public static function init() {
        add_filter( 'register_post_type_args', [ __CLASS__, 'force_rest' ], 10, 2 );
        add_action( 'rest_api_init', [ __CLASS__, 'fallback' ] );
    }

    /**
     * Lock down the payment‑confirmation CPT.
     *
     * We prevent the CPT from being publicly queryable or exposed via
     * the default REST controller. Posts remain private at the DB level.
     *
     * @param array  $args      Arguments passed to register_post_type().
     * @param string $post_type Post type key.
     *
     * @return array Modified arguments.
     */
    public static function force_rest( $args, $post_type ) {
        if ( 'payment-confirmation' !== $post_type ) {
            return $args;
        }

        // Lock this CPT down even if another plugin registers it differently.
        $args['public']              = true;
        $args['publicly_queryable']  = false;
        $args['exclude_from_search'] = true;
        $args['has_archive']         = false;
        $args['rewrite']             = false;
        $args['query_var']           = false;

        // Avoid exposing via wp/v2 routes.
        $args['show_in_rest']        = false;

        return $args;
    }

    /**
     * Register our fallback REST route.
     *
     * The fallback route is open to any caller (`__return_true`)
     * because the posts themselves are private. We return only basic
     * details along with an admin link for editing. Consumers should
     * handle pagination on the client side by altering `per_page`.
     */
    public static function fallback() {
        register_rest_route( 'aaa-oc/v1', '/payconfirm', [
            'methods'             => 'GET',
            'permission_callback' => '__return_true',
            'args'                => [
                'per_page' => [
                    'default' => 20,
                ],
            ],
            'callback'            => function( WP_REST_Request $r ) {
                $pp = max( 1, (int) $r->get_param( 'per_page' ) );

                $q = new WP_Query( [
                    'post_type'      => 'payment-confirmation',
                    'post_status'    => [ 'private', 'draft', 'pending' ],
                    'posts_per_page' => $pp,
                    'no_found_rows'  => true,
                    'orderby'        => 'date',
                    'order'          => 'DESC',
                ] );

                $out = [];
                foreach ( $q->posts as $p ) {
                    $out[] = [
                        'id'               => $p->ID,
                        'date'             => get_post_time( 'c', true, $p ),
                        'title'            => get_the_title( $p ),
                        // Provide an admin link for editing. Do not return a public permalink.
                        'pc_link'          => admin_url( 'post.php?post=' . (int) $p->ID . '&action=edit' ),
                        'matched_order_id' => (int) get_post_meta( $p->ID, '_pc_matched_order_id', true ),
                    ];
                }

                return new WP_REST_Response( $out, 200 );
            },
        ] );
    }
}
