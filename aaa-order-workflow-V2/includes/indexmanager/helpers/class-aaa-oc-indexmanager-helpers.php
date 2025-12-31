<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/indexmanager/helpers/class-aaa-oc-indexmanager-helpers.php
 */
if ( ! defined('ABSPATH') ) exit;

class AAA_OC_IndexManager_Helpers {
    // Option keys + scope in aaa_oc_options
    const SCOPE = 'indexmanager';
    const KEY_USERS    = 'aaa_oc_im_users';
    const KEY_PRODUCTS = 'aaa_oc_im_products';
    const KEY_ORDERS   = 'aaa_oc_im_orders';

    public static function can_manage(){
        $cap = defined('AAA_OC_REQUIRED_CAP') ? AAA_OC_REQUIRED_CAP : 'manage_woocommerce';
        return current_user_can($cap);
    }

    public static function table_name($entity){
        global $wpdb; return $wpdb->prefix . "aaa_oc_im_" . $entity;
    }

    /** Defaults WITH qualifiers/ retention and richer triggers */
    public static function defaults($entity){
        if ($entity==='users') {
            return [
                'enabled'      => 1,
                'session_only' => 1,    // index on login / purge on logout only
                'login_index'  => 1,
                'logout_purge' => 1,
                // full set (only used when session_only = 0)
                'triggers'     => [
                    'wp_login','wp_logout_purge','profile_update',
                    'added_user_meta','updated_user_meta','deleted_user_meta',
                    'set_user_role','woocommerce_customer_save_address'
                ],
                'columns'      => [
                    ['col'=>'user_id','source'=>'core','key'=>'ID','type'=>'BIGINT(20) UNSIGNED','primary'=>true,'index'=>true],
                    ['col'=>'display_name','source'=>'core','key'=>'display_name','type'=>'VARCHAR(200)'],
                    ['col'=>'user_email','source'=>'core','key'=>'user_email','type'=>'VARCHAR(190)','index'=>true],
                    ['col'=>'billing_address','source'=>'computed','key'=>'billing_address','type'=>'TEXT'],
                    ['col'=>'shipping_address','source'=>'computed','key'=>'shipping_address','type'=>'TEXT'],
                    ['col'=>'updated_at','source'=>'computed','key'=>'updated_at','type'=>'DATETIME','index'=>true],
                ],
            ];
        }
        if ($entity==='products') {
            return [
                'enabled'  => 0,
                'triggers' => [
                    'save_post_product','woocommerce_product_set_stock','updated_post_meta',
                    'woocommerce_update_product','woocommerce_admin_process_classic_meta',
                    'woocommerce_product_quick_edit','woocommerce_process_product_meta',
                    'set_object_terms','trashed_post','untrashed_post'
                ],
                'allowed_stock_statuses' => ['instock'], // only index when in these statuses
                'show_unpublished'       => 0,           // by default skip non-published
                'purge_excluded'         => 1,           // purge when product no longer qualifies
                'columns'  => [
                    ['col'=>'product_id','source'=>'core','key'=>'ID','type'=>'BIGINT(20) UNSIGNED','primary'=>true,'index'=>true],
                    ['col'=>'sku','source'=>'core','key'=>'sku','type'=>'VARCHAR(190)','index'=>true],
                    ['col'=>'name','source'=>'core','key'=>'post_title','type'=>'VARCHAR(200)'],
                    ['col'=>'price','source'=>'core','key'=>'price','type'=>'DECIMAL(18,6)'],
                    ['col'=>'stock_status','source'=>'core','key'=>'stock_status','type'=>'VARCHAR(32)','index'=>true],
                    ['col'=>'stock_quantity','source'=>'core','key'=>'stock_quantity','type'=>'INT(11)'],
                    ['col'=>'updated_at','source'=>'computed','key'=>'updated_at','type'=>'DATETIME','index'=>true],
                ],
            ];
        }
        // orders
        return [
            'enabled'  => 0,
            'triggers' => [
                'save_post_shop_order','woocommerce_order_status_changed','updated_post_meta',
                'woocommerce_new_order','woocommerce_checkout_update_order_meta',
                'deleted_post','trashed_post','untrashed_post'
            ],
            'allowed_statuses'  => [], // e.g. ['wc-processing','wc-completed']; empty = all
            'purge_excluded'    => 0,
            'retention_enable'  => 0,  // daily purge by age
            'retention_days'    => 30,
            'columns'  => [
                ['col'=>'order_id','source'=>'core','key'=>'ID','type'=>'BIGINT(20) UNSIGNED','primary'=>true,'index'=>true],
                ['col'=>'order_number','source'=>'core','key'=>'order_number','type'=>'VARCHAR(32)','index'=>true],
                ['col'=>'customer_id','source'=>'core','key'=>'customer_id','type'=>'BIGINT(20) UNSIGNED','index'=>true],
                ['col'=>'status','source'=>'core','key'=>'status','type'=>'VARCHAR(32)','index'=>true],
                ['col'=>'total','source'=>'core','key'=>'total','type'=>'DECIMAL(18,6)'],
                ['col'=>'currency','source'=>'core','key'=>'currency','type'=>'VARCHAR(10)'],
                ['col'=>'updated_at','source'=>'computed','key'=>'updated_at','type'=>'DATETIME','index'=>true],
            ],
        ];
    }

    public static function get_opt($entity){
        $key = ($entity==='users')? self::KEY_USERS : (($entity==='products')? self::KEY_PRODUCTS : self::KEY_ORDERS);
        $val = function_exists('aaa_oc_get_option') ? aaa_oc_get_option($key, self::SCOPE, null) : null;
        $def = self::defaults($entity);
        return is_array($val) ? array_replace_recursive($def, $val) : $def;
    }

    public static function set_opt($entity, $arr){
        $key = ($entity==='users')? self::KEY_USERS : (($entity==='products')? self::KEY_PRODUCTS : self::KEY_ORDERS);
        if ( function_exists('aaa_oc_set_option') ) {
            aaa_oc_set_option($key, $arr, self::SCOPE);
        }
    }

    /** Return the column name flagged as primary, or first column, or 'object_id'. */
    public static function primary_col( string $entity ) : string {
        $cfg = self::get_opt($entity);
        $cols = (array)($cfg['columns'] ?? []);
        foreach ($cols as $c) {
            if ( !empty($c['primary']) ) { return sanitize_key($c['col']); }
        }
        return sanitize_key($cols[0]['col'] ?? 'object_id');
    }

    /** Fast fetch of a single indexed user column (respects configured primary column) */
    public static function get_user_col( int $user_id, string $col ) {
        $col = sanitize_key($col);
        $cfg = self::get_opt('users');
        $allowed = array_map(function($c){ return sanitize_key($c['col']); }, (array)$cfg['columns']);
        if ( ! in_array($col, $allowed, true) ) return null;

        global $wpdb;
        $table = self::table_name('users');
        $pk = self::primary_col('users');
        return $wpdb->get_var( $wpdb->prepare("SELECT `$col` FROM `$table` WHERE `$pk` = %d LIMIT 1", $user_id) );
    }

    /* ========= Qualifiers & retention helpers ========= */

    public static function products_should_index($pid, array $cfg) : bool {
        if ( ! function_exists('wc_get_product') ) return true;
        $p = wc_get_product($pid); if ( ! $p ) return false;

        if ( empty($cfg['show_unpublished']) ) {
            $st = $p->get_status();
            if ( ! in_array($st, ['publish','private'], true) ) return false;
        }

        $allow = (array)($cfg['allowed_stock_statuses'] ?? []);
        if ( empty($allow) ) return true;
        $status = (string) $p->get_stock_status('edit');
        return in_array($status, $allow, true);
    }

    public static function orders_should_index($oid, array $cfg) : bool {
        if ( ! function_exists('wc_get_order') ) return true;
        $allow = (array)($cfg['allowed_statuses'] ?? []);
        if ( empty($allow) ) return true;
        $o = wc_get_order($oid); if ( ! $o ) return false;
        $st = $o.get_status(); // 'processing'
        return in_array($st, $allow, true) || in_array('wc-'.$st, $allow, true);
    }

    /** Ensure/clear daily schedule for order retention */
    public static function sync_orders_vertical_cron() {
        $cfg = self::get_opt('orders');
        $hook = 'aaa_oc_im_purge_orders_cron';
        if ( ! empty($cfg['retention_enable']) && (int)$cfg['retention_days'] > 0 ) {
            if ( ! wp_next_scheduled($hook) ) {
                wp_schedule_event(time()+300, 'daily', $hook);
            }
        } else {
            $ts = wp_next_scheduled($hook);
            if ($ts) wp_unschedule_event($ts, $hook);
        }
    }
}
