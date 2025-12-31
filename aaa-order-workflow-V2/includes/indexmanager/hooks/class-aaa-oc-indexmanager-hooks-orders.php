<?php
if ( ! defined('ABSPATH') ) exit;

class AAA_OC_IndexManager_Hooks_Orders {
    public static function boot(){
        $cfg = AAA_OC_IndexManager_Helpers::get_opt('orders');
        if ( empty($cfg['enabled']) ) return;
        $t = (array)($cfg['triggers'] ?? []);

        $maybe = function($oid, $src) use ($cfg){
            $oid = (int)$oid; if ( ! $oid ) return;
            if ( AAA_OC_IndexManager_Helpers::orders_should_index($oid, $cfg) ) {
                AAA_OC_IndexManager_Table_Indexer::reindex('orders', $oid, $src);
            } else {
                if ( ! empty($cfg['purge_excluded']) ) {
                    AAA_OC_IndexManager_Table_Indexer::purge('orders', $oid);
                }
            }
        };

        if ( in_array('save_post_shop_order',$t,true) ) {
            add_action('save_post_shop_order', function($oid) use ($maybe){ $maybe($oid,'save_post_shop_order'); }, 10, 1);
        }
        if ( function_exists('WC') && in_array('woocommerce_order_status_changed',$t,true) ) {
            add_action('woocommerce_order_status_changed', function($oid,$from,$to) use ($maybe){
                $maybe($oid,"status:$from->$to");
            }, 10, 3);
        }
        if ( in_array('updated_post_meta',$t,true) ) {
            add_action('updated_post_meta', function($mid,$post_id,$meta_key,$meta_val) use ($maybe){
                if ( get_post_type($post_id) === 'shop_order' ) $maybe($post_id,'updated_post_meta:'.$meta_key);
            }, 10, 4);
        }
        if ( function_exists('WC') && in_array('woocommerce_new_order',$t,true) ) {
            add_action('woocommerce_new_order', function($oid) use ($maybe){ $maybe($oid,'wc_new_order'); }, 10, 1);
        }
        if ( function_exists('WC') && in_array('woocommerce_checkout_order_processed',$t,true) ) {
            add_action('woocommerce_checkout_order_processed', function($oid) use ($maybe){ $maybe($oid,'wc_checkout_processed'); }, 10, 1);
        }
        if ( in_array('deleted_post',$t,true) ) {
            add_action('deleted_post', function($post_id){
                if ( get_post_type($post_id) === 'shop_order' ) {
                    AAA_OC_IndexManager_Table_Indexer::purge('orders', (int)$post_id);
                }
            }, 10, 1);
        }
        if ( in_array('trashed_post',$t,true) ) {
            add_action('trashed_post', function($post_id){
                if ( get_post_type($post_id) === 'shop_order' ) {
                    AAA_OC_IndexManager_Table_Indexer::purge('orders', (int)$post_id);
                }
            }, 10, 1);
        }
        if ( in_array('untrashed_post',$t,true) ) {
            add_action('untrashed_post', function($post_id) use ($maybe){
                if ( get_post_type($post_id) === 'shop_order' ) $maybe($post_id,'untrashed_post');
            }, 10, 1);
        }

        // Retention schedule
        AAA_OC_IndexManager_Helpers::sync_orders_retention_schedule();
        add_action('aaa_oc_im_orders_retention', function(){
            $cfg = AAA_OC_IndexManager_Helpers::get_opt('orders');
            $days = (int)($cfg['retention_days'] ?? 0);
            if ( empty($cfg['retention_enable']) || $days <= 0 ) return;

            global $wpdb;
            $table = AAA_OC_IndexManager_Helpers::table_name('orders');
            $ids = $wpdb->get_col("SELECT order_id FROM `$table`");
            $cut = time() - $days * DAY_IN_SECONDS;

            if ( function_exists('wc_get_order') ) {
                foreach ( (array)$ids as $oid ) {
                    $o = wc_get_order($oid);
                    if ( ! $o ) { AAA_OC_IndexManager_Table_Indexer::purge('orders',(int)$oid); continue; }
                    $d = $o->get_date_created(); $ts = $d ? $d->getTimestamp() : 0;
                    if ( $ts && $ts < $cut ) AAA_OC_IndexManager_Table_Indexer::purge('orders', (int)$oid);
                }
            }
        });
    }
}
