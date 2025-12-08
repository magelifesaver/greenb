<?php
if ( ! defined('ABSPATH') ) exit;

require_once __DIR__.'/builders/class-aaa-wfuim-builder-user.php';
require_once __DIR__.'/builders/class-aaa-wfuim-builder-order.php';
require_once __DIR__.'/builders/class-aaa-wfuim-builder-product.php';

class AAA_WFUIM_Engine {

    public static function boot(){
        add_action('wfuim_reindex_event', [__CLASS__,'do_reindex'], 10, 2);
        add_action('aaa_wfuim_reindex_all',  [__CLASS__,'reindex_all']);
        add_action('aaa_wfuim_reindex_table',[__CLASS__,'reindex_table'], 10, 1);
        add_action('aaa_wfuim_reindex_object',[__CLASS__,'reindex_object'],10, 2);

        foreach (\AAA_WFUIM_Registry::tables() as $slug=>$t){
            if ( empty($t['enabled']) ) continue;
            self::register_hooks_for_table($slug, $t);
        }

        // Purge on logout (user tables)
        add_action('wp_logout', function(){
            $uid = get_current_user_id() ?: ($GLOBALS['aaa_wfuim_last_uid'] ?? 0);
            foreach (\AAA_WFUIM_Registry::tables() as $slug=>$t){
                if ($t['entity']==='user' && !empty($t['purge_on_logout']) && $uid){
                    self::purge_row($slug,$t,$uid);
                }
            }
        }, 10);
    }

    protected static function register_hooks_for_table($slug, $t){
        $entity = $t['entity'];
        $session_only = !empty($t['session_only']);

        // USER: session-only => only wp_login index (and wp_logout handled globally)
        if ($entity==='user'){
            if ( ! empty($t['index_on_login']) ) {
                add_action('wp_login', function($login,$user) use($slug,$t){
                    if ( !empty($t['session_only']) ) {
                        self::debounced($slug,$user->ID,'wp_login');
                    } else {
                        self::debounced($slug,$user->ID,'wp_login');
                    }
                }, 10, 2);
            }
            if ( $session_only ) {
                // do NOT register any other user triggers
                return;
            }
        }

        // Non-user tables OR user tables not in session-only: register chosen triggers (existing logic)
        foreach ((array)($t['triggers'] ?? []) as $hook){
            switch ($hook) {
                case 'save_post_shop_order':
                    add_action('save_post_shop_order', fn($oid)=>self::debounced($slug,$oid,'save_post_order'),10,1);
                    break;
                case 'woocommerce_order_status_changed':
                    if ( function_exists('WC') ) add_action('woocommerce_order_status_changed', fn($oid,$from,$to)=>self::debounced($slug,$oid,"order_status:$from->$to"),10,3);
                    break;
                case 'updated_post_meta':
                    add_action('updated_post_meta', function($mid,$oid,$key,$val) use($slug,$t){
                        $pt = get_post_type($oid);
                        if ( ($t['entity']==='order' && $pt==='shop_order') || ($t['entity']==='product' && $pt==='product') ) {
                            self::debounced($slug,$oid,'updated_post_meta:'.$key);
                        }
                    },10,4);
                    break;
                case 'save_post_product':
                    add_action('save_post_product', fn($pid)=>self::debounced($slug,$pid,'save_post_product'),10,1);
                    break;
                case 'woocommerce_product_set_stock':
                    if ( function_exists('WC') ) add_action('woocommerce_product_set_stock', function($product){
                        $pid = is_object($product)? $product->get_id() : 0;
                        if ($pid) foreach (\AAA_WFUIM_Registry::tables() as $sl=>$tt){
                            if ($tt['entity']==='product' && !empty($tt['enabled'])) self::debounced($sl,$pid,'wc_set_stock');
                        }
                    },10,1);
                    break;
                // Any other hooks (user hooks will be ignored in session-only mode)
            }
        }
    }

    /** Global reindexers */
    public static function reindex_all(){
        foreach (\AAA_WFUIM_Registry::tables() as $slug=>$t){
            // Skip user tables in session-only mode
            if ($t['entity']==='user' && !empty($t['session_only'])) continue;
            self::reindex_table($slug);
        }
    }

    public static function reindex_table($slug){
        $t = \AAA_WFUIM_Registry::table($slug); if (! $t || empty($t['enabled'])) return;
        if ($t['entity']==='user' && !empty($t['session_only'])) return; // block backend reindex for session-only users

        $entity=$t['entity'];
        if ($entity==='user'){
            $ids = get_users(['fields'=>'ID']);
        } elseif ($entity==='order'){
            $q = new \WP_Query(['post_type'=>'shop_order','posts_per_page'=>-1,'fields'=>'ids']);
            $ids = $q->posts;
        } else {
            $q = new \WP_Query(['post_type'=>'product','posts_per_page'=>-1,'fields'=>'ids']);
            $ids = $q->posts;
        }
        foreach ((array)$ids as $id){ self::debounced($slug,(int)$id,'reindex_table'); }
    }

    public static function reindex_object($entity, $object_id){
        foreach (\AAA_WFUIM_Registry::tables() as $slug=>$t){
            if ($t['entity'] !== $entity || empty($t['enabled'])) continue;
            if ($entity==='user' && !empty($t['session_only'])) continue; // block direct calls
            self::debounced($slug,(int)$object_id,'reindex_object');
        }
    }

    /** Debounce + upsert with session-only guard */
    protected static function debounced($slug,$id,$src='unknown'){
        if (! $id) return;
        $t = \AAA_WFUIM_Registry::table($slug); if (! $t ) return;

        // Guard: user session-only => only allow when current user == id
        if ($t['entity']==='user' && !empty($t['session_only'])) {
            $current = get_current_user_id();
            if ( (int)$current !== (int)$id ) return;
        }

        $key = 'wfuim_pending_'.$slug.'_'.$id;
        if ( get_transient($key) ) return;
        set_transient($key,1,7);
        if ( function_exists('wp_schedule_single_event') ) {
            wp_schedule_single_event(time()+2, 'wfuim_reindex_event', [$slug,$id]);
        } else {
            self::do_reindex($slug,$id);
        }
    }

    public static function do_reindex($slug,$id){
        delete_transient('wfuim_pending_'.$slug.'_'.$id);
        $t = \AAA_WFUIM_Registry::table($slug); if (! $t || empty($t['enabled'])) return;

        // Final guard: user session-only must be the logged-in user
        if ($t['entity']==='user' && !empty($t['session_only'])) {
            $current = get_current_user_id();
            if ( (int)$current !== (int)$id ) return;
        }

        $data = self::build_row($t,$id); if (! $data) return;

        global $wpdb; $table = \AAA_WFUIM_Registry::table_name($slug);
        $fmt = [];
        foreach ($data as $v){ $fmt[] = is_int($v)? '%d' : (is_float($v)? '%f' : '%s'); }
        $wpdb->replace($table, $data, $fmt);
        if ( defined('AAA_WFUIM_DEBUG') && AAA_WFUIM_DEBUG ) error_log("[WFUIM] upsert {$slug} id={$id}");
    }

    public static function purge_row($slug,$t,$id){
        global $wpdb; $table = \AAA_WFUIM_Registry::table_name($slug);
        $pk = $t['columns'][0]['col'] ?? 'object_id';
        $wpdb->delete($table, [ $pk => (int)$id ], ['%d']);
        if ( defined('AAA_WFUIM_DEBUG') && AAA_WFUIM_DEBUG ) error_log("[WFUIM] purge {$slug} id={$id}");
    }

    protected static function build_row($t,$id){
        $e=$t['entity'];
        if ($e==='user')    return \AAA_WFUIM_Builder_User::build($t,$id);
        if ($e==='order')   return \AAA_WFUIM_Builder_Order::build($t,$id);
        if ($e==='product') return \AAA_WFUIM_Builder_Product::build($t,$id);
        return [];
    }
}
