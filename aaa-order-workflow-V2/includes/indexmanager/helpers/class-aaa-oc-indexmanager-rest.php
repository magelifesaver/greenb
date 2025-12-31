<?php
if ( ! defined('ABSPATH') ) exit;

class AAA_OC_IndexManager_REST {
    const NS = 'aaa-oc-im/v1';

    public static function init(){
        add_action('rest_api_init', [__CLASS__, 'register']);
    }

    public static function register(){
        register_rest_route(self::NS, '/me', [
            'methods'  => 'GET',
            'callback' => [__CLASS__, 'get_me'],
            'permission_callback' => function(){ return is_user_logged_in(); }
        ]);

        register_rest_route(self::NS, '/me/field', [
            'methods'  => 'GET',
            'callback' => [__CLASS__, 'get_me_field'],
            'permission_callback' => function(){ return is_user_logged_in(); },
            'args' => [
                'col' => [
                    'type' => 'string',
                    'required' => true,
                    'sanitize_callback' => 'sanitize_key'
                ],
            ],
        ]);
    }

    public static function get_me( WP_REST_Request $req ){
        $uid = get_current_user_id();
        if ( ! $uid ) return new WP_Error('not_logged_in','Login required', ['status'=>401]);

        global $wpdb;
        $table = AAA_OC_IndexManager_Helpers::table_name('users');

        // Get configured columns to prevent SELECT *
        $cfg = AAA_OC_IndexManager_Helpers::get_opt('users');
        $cols = array_map(function($c){ return sanitize_key($c['col']); }, (array)$cfg['columns']);
        $cols = array_filter($cols);
        if ( empty($cols) ) return [];

        $pk = $cfg['columns'][0]['col'] ?? 'object_id';
        $sql = "SELECT `".implode("`,`",$cols)."` FROM `$table` WHERE `$pk` = %d LIMIT 1";
        $row = $wpdb->get_row( $wpdb->prepare($sql, $uid), ARRAY_A );
        return $row ? $row : [];
    }

    public static function get_me_field( WP_REST_Request $req ){
        $uid = get_current_user_id();
        if ( ! $uid ) return new WP_Error('not_logged_in','Login required', ['status'=>401]);

        $col = sanitize_key( $req->get_param('col') );
        $cfg = AAA_OC_IndexManager_Helpers::get_opt('users');
        $allowed = array_map(function($c){ return sanitize_key($c['col']); }, (array)$cfg['columns']);
        if ( ! in_array($col, $allowed, true) ) {
            return new WP_Error('invalid_column','Column not indexed or not allowed', ['status'=>400]);
        }

        global $wpdb;
        $table = AAA_OC_IndexManager_Helpers::table_name('users');
        $pk = $cfg['columns'][0]['col'] ?? 'object_id';
        $val = $wpdb->get_var( $wpdb->prepare("SELECT `$col` FROM `$table` WHERE `$pk` = %d LIMIT 1", $uid) );
        return is_null($val) ? '' : $val;
    }
}
AAA_OC_IndexManager_REST::init();
