<?php
if ( ! defined('ABSPATH') ) exit;

class AAA_WFUIM_Builder_Product {
    protected static function p($id){ return function_exists('wc_get_product') ? wc_get_product($id) : null; }
    protected static function core($p,$key){
        switch ($key){
            case 'ID': return (int)($p ? $p->get_id() : 0);
            case 'sku': return (string) ($p ? $p->get_sku() : get_post_meta($p? $p->get_id():0, '_sku', true));
            case 'post_title': $post = get_post($p? $p->get_id():0); return $post? (string)$post->post_title : '';
            case 'price': return (float) ($p ? $p->get_price('edit') : 0);
            case 'stock_status': return (string) ($p ? $p->get_stock_status('edit') : '');
            case 'stock_quantity': return (int) ($p ? (int)$p->get_stock_quantity('edit') : 0);
            default: return '';
        }
    }
    protected static function meta($id,$key){
        $v = get_post_meta($id, $key, true);
        return is_scalar($v) ? $v : json_encode($v);
    }
    protected static function computed($token,$p){
        if ($token==='updated_at') return current_time('mysql', false);
        if ($token==='is_in_stock' && $p) return $p->is_in_stock() ? 1 : 0;
        return '';
    }
    public static function build($t,$id){
        $p = self::p($id);
        $row=[];
        foreach ((array)$t['columns'] as $c){
            $col=$c['col']; $src=$c['source']; $key=$c['key'];
            if ($src==='core')      $row[$col] = self::core($p,$key);
            elseif ($src==='meta')  $row[$col] = (string) self::meta($id,$key);
            elseif ($src==='computed') $row[$col] = self::computed($key,$p);
        }
        if ( empty($row[$t['columns'][0]['col']]) ) $row[$t['columns'][0]['col']] = (int)$id;
        return $row;
    }
}
