<?php
/**
 * File Path: includes/ddd-cfc-rest-controller.php
 * Purpose: Register and handle all REST API endpoints for live search & indexing.
 */

defined( 'ABSPATH' ) || exit;

class DDD_CFC_REST_Controller {

    public static function init() {
        add_action( 'rest_api_init', [ __CLASS__, 'register_routes' ] );
    }

    public static function register_routes() {
        $ns = 'ls/v1';
        register_rest_route( $ns, '/search', [
            'methods'             => 'GET',
            'callback'            => [ __CLASS__, 'search' ],
            'permission_callback' => [ __CLASS__, 'can_manage' ],
        ] );
        register_rest_route( $ns, '/index/build', [
            'methods'             => 'POST',
            'callback'            => [ __CLASS__, 'build_index' ],
            'permission_callback' => [ __CLASS__, 'can_manage' ],
        ] );
        register_rest_route( $ns, '/index/sync', [
            'methods'             => 'POST',
            'callback'            => [ __CLASS__, 'sync_index' ],
            'permission_callback' => [ __CLASS__, 'can_manage' ],
        ] );
        register_rest_route( $ns, '/index/clear', [
            'methods'             => 'POST',
            'callback'            => [ __CLASS__, 'clear_index' ],
            'permission_callback' => [ __CLASS__, 'can_manage' ],
        ] );
	register_rest_route( $ns, '/exclusions', [
	  'methods' => 'POST',
	  'callback' => function( WP_REST_Request $req ) {
	    $list = $req->get_param('list');
	    DDD_CFC_Exclusions::set_list( (array) $list );
	    return rest_ensure_response( ['saved'=>true] );
	  },
	  'permission_callback' => [ __CLASS__, 'can_manage' ],
	] );
	register_rest_route( $ns, '/tree-source', [
	    'methods'  => 'POST',
	    'callback' => [ __CLASS__, 'save_tree_source' ],
	    'permission_callback' => [ __CLASS__, 'can_manage' ],
	] );

    }

    public static function can_manage() {
        return current_user_can( 'manage_options' );
    }

    public static function search( WP_REST_Request $req ) {
        global $wpdb;
        $term  = trim( $req->get_param( 'term' ) );
        if ( '' === $term ) {
            return rest_ensure_response( [] );
        }
        $like  = '%' . $wpdb->esc_like( $term ) . '%';
        $table = $wpdb->prefix . 'ls_file_index';
        $sql   = $wpdb->prepare( "SELECT path, is_dir FROM {$table} WHERE path LIKE %s", $like );
        return rest_ensure_response( $wpdb->get_results( $sql, ARRAY_A ) );
    }

    public static function build_index() {
        // (Call your existing build logic here, e.g. DDD_CFC_Indexer::build_all)
        return rest_ensure_response( DDD_CFC_Indexer::build_all() );
    }

    public static function sync_index() {
        // (Call your existing sync logic: DDD_CFC_Indexer::sync)
        return rest_ensure_response( DDD_CFC_Indexer::sync() );
    }

    public static function clear_index() {
        global $wpdb;
        $table = $wpdb->prefix . 'ls_file_index';
        $wpdb->query( "TRUNCATE TABLE $table" );
        update_option( 'cfc_ls_last_index_time', time() );
        return rest_ensure_response( [ 'count' => 0 ] );
    }
	public static function save_exclusions( WP_REST_Request $req ) {
	    $list = (array) $req->get_param( 'list' );
	    // Normalize: strip empties, trim whitespace
	    $list = array_values( array_filter( array_map( 'sanitize_text_field', $list ) ) );
	    DDD_CFC_Exclusions::set_list( $list );
	    return rest_ensure_response( [ 'saved' => true ] );
	}
	/**
	 * Save the tree source option.
	 */
	public static function save_tree_source( WP_REST_Request $req ) {
	    $val = $req->get_param( 'source' );
	    $val = in_array( $val, [ 'realtime', 'indexed' ], true ) ? $val : 'realtime';
	    update_option( 'cfc_tree_source', $val );
	    return rest_ensure_response( [ 'source' => $val ] );
	}


}
