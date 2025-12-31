<?php
if ( ! defined('ABSPATH') ) exit;

class AAA_OC_IndexManager_Hooks_Products {
    public static function boot(){
        $cfg = AAA_OC_IndexManager_Helpers::get_opt('products');
        if ( empty($cfg['enabled']) ) return;
        $t = (array)($cfg['triggers'] ?? []);

        $maybe = function($pid, $src) use ($cfg){
            $pid = (int)$pid; if ( ! $pid ) return;
            if ( AAA_OC_IndexManager_Helpers::products_should_index($pid, $cfg) ) {
                AAA_OC_IndexManager_Table_Indexer::reindex('products', $pid, $src);
            } else {
                if ( ! empty($cfg['purge_excluded']) ) {
                    AAA_OC_IndexManager_Table_Indexer::purge('products', $pid);
                }
            }
        };

        if ( in_array('save_post_product',$t,true) ) {
            add_action('save_post_product', function($pid) use ($maybe){ $maybe($pid,'save_post_product'); }, 10, 1);
        }
        if ( function_exists('WC') && in_array('woocommerce_product_set_stock',$t,true) ) {
            add_action('woocommerce_product_set_stock', function($product) use ($maybe){
                $maybe( is_object($product)? $product->get_id() : 0, 'wc_set_stock' );
            }, 10, 1);
        }
        if ( in_array('updated_post_meta',$t,true) ) {
            add_action('updated_post_meta', function($mid,$post_id,$meta_key,$meta_val) use ($maybe){
                if ( get_post_type($post_id) === 'product' ) $maybe($post_id,'updated_post_meta:'.$meta_key);
            }, 10, 4);
        }
        if ( function_exists('WC') && in_array('woocommerce_update_product',$t,true) ) {
            add_action('woocommerce_update_product', function($pid) use ($maybe){ $maybe($pid,'wc_update_product'); }, 10, 1);
        }
        if ( function_exists('WC') && in_array('woocommerce_admin_process_product_object',$t,true) ) {
            add_action('woocommerce_admin_process_product_object', function($product) use ($maybe){
                $maybe( is_object($product)? $product->get_id() : 0, 'wc_admin_process_product_object' );
            }, 10, 1);
        }
        if ( function_exists('WC') && in_array('woocommerce_product_quick_edit_save',$t,true) ) {
            add_action('woocommerce_product_quick_edit_save', function($product) use ($maybe){
                $maybe( is_object($product)? $product->get_id() : 0, 'wc_quick_edit' );
            }, 10, 1);
        }
        if ( function_exists('WC') && in_array('woocommerce_after_product_object_save',$t,true) ) {
            add_action('woocommerce_after_product_object_save', function($product) use ($maybe){
                $maybe( is_object($product)? $product->get_id() : 0, 'wc_after_save' );
            }, 10, 1);
        }
        if ( in_array('set_object_terms',$t,true) ) {
            add_action('set_object_terms', function($object_id,$terms,$tt_ids,$taxonomy,$append,$old_tt_ids) use ($maybe){
                if ( get_post_type($object_id) === 'product' ) $maybe($object_id,'set_object_terms:'.$taxonomy);
            }, 10, 6);
        }
        if ( in_array('trashed_post',$t,true) ) {
            add_action('trashed_post', function($post_id){
                if ( get_post_type($post_id) === 'product' ) {
                    AAA_OC_IndexManager_Table_Indexer::purge('products', (int)$post_id);
                }
            }, 10, 1);
        }
        if ( in_array('untrashed_post',$t,true) ) {
            add_action('untrashed_post', function($post_id) use ($maybe){
                if ( get_post_type($post_id) === 'product' ) $maybe($post_id,'untrashed_post');
            }, 10, 1);
        }
    }
}
