<?php
/**
 * File Path: /wp-content/plugins/aaa-geo-business-mapper/includes/ajax/class-aaa-gbm-ajax.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAA_GBM_Ajax {

    const DEBUG_THIS_FILE = true;

    public static function init() {
        add_action( 'wp_ajax_aaa_gbm_search_nearby', array( __CLASS__, 'search_nearby' ) );
        add_action( 'wp_ajax_aaa_gbm_search_text', array( __CLASS__, 'search_text' ) );
    }

    private static function guard() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Forbidden' ), 403 );
        }
        check_ajax_referer( 'aaa_gbm_nonce', 'nonce' );
    }

    public static function search_nearby() {
        self::guard();

        $key = get_option( AAA_GBM_Admin::OPT_SERVER_KEY, '' );
        if ( ! $key ) {
            wp_send_json_error( array( 'message' => 'Missing server API key' ), 400 );
        }

        $lat    = floatval( $_POST['lat'] ?? 0 );
        $lng    = floatval( $_POST['lng'] ?? 0 );
        $radius = floatval( $_POST['radius'] ?? 1800 );
        $types  = (array) ( $_POST['types'] ?? array() );

        $types = array_values( array_filter( array_map( 'sanitize_text_field', $types ) ) );
        if ( ! $lat || ! $lng || empty( $types ) ) {
            wp_send_json_error( array( 'message' => 'Invalid parameters' ), 400 );
        }

        $body = array(
            'includedTypes' => $types,
            'maxResultCount' => 20,
            'rankPreference' => 'POPULARITY',
            'locationRestriction' => array(
                'circle' => array(
                    'center' => array( 'latitude' => $lat, 'longitude' => $lng ),
                    'radius' => $radius,
                ),
            ),
        );

        $resp = wp_remote_post(
            'https://places.googleapis.com/v1/places:searchNearby',
            array(
                'timeout' => 20,
                'headers' => array(
                    'Content-Type'     => 'application/json',
                    'X-Goog-Api-Key'   => $key,
                    'X-Goog-FieldMask' => 'places.id,places.displayName,places.formattedAddress,places.location,places.primaryType,places.types',
                ),
                'body' => wp_json_encode( $body ),
            )
        );

        if ( is_wp_error( $resp ) ) {
            wp_send_json_error( array( 'message' => $resp->get_error_message() ), 500 );
        }

        $code = wp_remote_retrieve_response_code( $resp );
        $json = json_decode( wp_remote_retrieve_body( $resp ), true );

        if ( self::DEBUG_THIS_FILE ) {
            AAA_GBM_Logger::log( 'Nearby search', array( 'code' => $code, 'types' => $types ) );
        }

        if ( 200 !== $code ) {
            wp_send_json_error( array( 'message' => 'Places error', 'details' => $json ), $code );
        }

        wp_send_json_success( array( 'places' => ( $json['places'] ?? array() ) ) );
    }

    public static function search_text() {
        self::guard();

        $key = get_option( AAA_GBM_Admin::OPT_SERVER_KEY, '' );
        $q   = sanitize_text_field( $_POST['q'] ?? '' );
        $lat = floatval( $_POST['lat'] ?? 0 );
        $lng = floatval( $_POST['lng'] ?? 0 );
        $r   = floatval( $_POST['radius'] ?? 1800 );

        if ( ! $key || ! $q || ! $lat || ! $lng ) {
            wp_send_json_error( array( 'message' => 'Invalid parameters' ), 400 );
        }

        $body = array(
            'textQuery' => $q,
            'maxResultCount' => 20,
            'locationBias' => array(
                'circle' => array(
                    'center' => array( 'latitude' => $lat, 'longitude' => $lng ),
                    'radius' => $r,
                ),
            ),
        );

        $resp = wp_remote_post(
            'https://places.googleapis.com/v1/places:searchText',
            array(
                'timeout' => 20,
                'headers' => array(
                    'Content-Type'     => 'application/json',
                    'X-Goog-Api-Key'   => $key,
                    'X-Goog-FieldMask' => 'places.id,places.displayName,places.formattedAddress,places.location,places.primaryType,places.types',
                ),
                'body' => wp_json_encode( $body ),
            )
        );

        if ( is_wp_error( $resp ) ) {
            wp_send_json_error( array( 'message' => $resp->get_error_message() ), 500 );
        }

        $code = wp_remote_retrieve_response_code( $resp );
        $json = json_decode( wp_remote_retrieve_body( $resp ), true );

        if ( self::DEBUG_THIS_FILE ) {
            AAA_GBM_Logger::log( 'Text search', array( 'code' => $code, 'q' => $q ) );
        }

        if ( 200 !== $code ) {
            wp_send_json_error( array( 'message' => 'Places error', 'details' => $json ), $code );
        }

        wp_send_json_success( array( 'places' => ( $json['places'] ?? array() ) ) );
    }
}
