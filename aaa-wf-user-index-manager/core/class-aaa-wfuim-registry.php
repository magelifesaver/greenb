<?php
if ( ! defined('ABSPATH') ) exit;

class AAA_WFUIM_Registry {
    const OPT_ENABLED = 'aaa_wfuim_enabled';
    const OPT_TABLES  = 'aaa_wfuim_tables';

    public static function enabled(){ return (bool) get_option(self::OPT_ENABLED, 1); }
    public static function tables(){ $all = get_option(self::OPT_TABLES, []); return is_array($all) ? $all : []; }
    public static function table($slug){ $t = self::tables(); return $t[$slug] ?? null; }
    public static function save_tables($arr){ update_option(self::OPT_TABLES, is_array($arr)? $arr : []); }
    public static function sanitize_slug($name){ return substr(sanitize_title($name),0,60); }
    public static function table_name($slug){ global $wpdb; return $wpdb->prefix.'aaa_wfuim_'.$slug; }

    public static function new_table_skeleton($name='New Index', $entity='user'){
        return [
            'name' => sanitize_text_field($name),
            'slug' => self::sanitize_slug($name),
            'entity' => in_array($entity,['user','order','product'],true)? $entity : 'user',
            'enabled' => 1,

            // SESSION-ONLY: for user tables, default ON; others ignored
            'session_only' => ($entity==='user') ? 1 : 0,
            'index_on_login' => ($entity==='user') ? 1 : 0,
            'purge_on_logout' => ($entity==='user') ? 1 : 0,

            'triggers' => \AAA_WFUIM_Entities::default_triggers($entity),
            'custom_hooks' => [],

            // keep in struct (ignored when session_only is on)
            'lat_keys' => '_wc_billing/aaa-delivery-blocks/latitude,_billing_latitude,aaa_coords_lat',
            'lng_keys' => '_wc_billing/aaa-delivery-blocks/longitude,_billing_longitude,aaa_coords_lng',
            'meta_trigger_whitelist' => "billing_*\nshipping_*",

            'columns' => \AAA_WFUIM_Entities::default_columns($entity),
        ];
    }
}
