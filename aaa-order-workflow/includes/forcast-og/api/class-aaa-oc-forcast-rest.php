<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/forcast/api/class-aaa-oc-forcast-rest.php
 * Purpose: Registers REST API endpoints for the Forcast module.
 * Version: 0.1.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Class AAA_OC_Forcast_REST
 *
 * Handles REST API route registration for forecast-related data.
 */
class AAA_OC_Forcast_REST {

    /**
     * Initialize REST routes.
     */
    public static function init() {
        add_action( 'rest_api_init', [ __CLASS__, 'register_routes' ] );
    }

    /**
     * Register custom REST endpoints.
     */
    public static function register_routes() {
        // TODO: Define endpoints using register_rest_route().
    }
}