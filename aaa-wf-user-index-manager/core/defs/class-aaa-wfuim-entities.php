<?php
if ( ! defined('ABSPATH') ) exit;

class AAA_WFUIM_Entities {

    public static function default_triggers($entity){
        $t = [
            'user' => [
                'wp_login','wp_logout_purge','profile_update',
                'added_user_meta','updated_user_meta','deleted_user_meta','set_user_role',
                'woocommerce_customer_save_address'
            ],
            'order' => [
                'save_post_shop_order','woocommerce_order_status_changed','updated_post_meta'
            ],
            'product' => [
                'save_post_product','woocommerce_product_set_stock','updated_post_meta'
            ],
        ];
        return $t[$entity] ?? [];
    }

    public static function default_columns($entity){
        if ($entity==='user') {
            return [
                ['col'=>'user_id','source'=>'core','key'=>'ID','type'=>'BIGINT(20) UNSIGNED','primary'=>true],
                ['col'=>'display_name','source'=>'core','key'=>'display_name','type'=>'VARCHAR(200)'],
                ['col'=>'user_email','source'=>'core','key'=>'user_email','type'=>'VARCHAR(190)','index'=>true],
                ['col'=>'billing_address','source'=>'computed','key'=>'billing_address','type'=>'TEXT'],
                ['col'=>'shipping_address','source'=>'computed','key'=>'shipping_address','type'=>'TEXT'],
                ['col'=>'lat','source'=>'computed','key'=>'lat','type'=>'DECIMAL(12,6)','index'=>true],
                ['col'=>'lng','source'=>'computed','key'=>'lng','type'=>'DECIMAL(12,6)','index'=>true],
                ['col'=>'updated_at','source'=>'computed','key'=>'updated_at','type'=>'DATETIME','index'=>true],
            ];
        }
        if ($entity==='order') {
            return [
                ['col'=>'order_id','source'=>'core','key'=>'ID','type'=>'BIGINT(20) UNSIGNED','primary'=>true],
                ['col'=>'order_number','source'=>'core','key'=>'order_number','type'=>'VARCHAR(32)','index'=>true],
                ['col'=>'customer_id','source'=>'core','key'=>'customer_id','type'=>'BIGINT(20) UNSIGNED','index'=>true],
                ['col'=>'status','source'=>'core','key'=>'status','type'=>'VARCHAR(32)','index'=>true],
                ['col'=>'total','source'=>'core','key'=>'total','type'=>'DECIMAL(18,6)'],
                ['col'=>'currency','source'=>'core','key'=>'currency','type'=>'VARCHAR(10)'],
                ['col'=>'updated_at','source'=>'computed','key'=>'updated_at','type'=>'DATETIME','index'=>true],
            ];
        }
        return [
            ['col'=>'product_id','source'=>'core','key'=>'ID','type'=>'BIGINT(20) UNSIGNED','primary'=>true],
            ['col'=>'sku','source'=>'core','key'=>'sku','type'=>'VARCHAR(190)','index'=>true],
            ['col'=>'name','source'=>'core','key'=>'post_title','type'=>'VARCHAR(200)'],
            ['col'=>'price','source'=>'core','key'=>'price','type'=>'DECIMAL(18,6)'],
            ['col'=>'stock_status','source'=>'core','key'=>'stock_status','type'=>'VARCHAR(32)','index'=>true],
            ['col'=>'stock_qty','source'=>'core','key'=>'stock_quantity','type'=>'INT(11)'],
            ['col'=>'updated_at','source'=>'computed','key'=>'updated_at','type'=>'DATETIME','index'=>true],
        ];
    }
}
