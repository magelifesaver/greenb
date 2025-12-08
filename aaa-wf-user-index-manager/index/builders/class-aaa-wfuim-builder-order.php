<?php
if ( ! defined('ABSPATH') ) exit;

class AAA_WFUIM_Builder_Order {
    protected static function order($id){ return function_exists('wc_get_order') ? wc_get_order($id) : null; }
    protected static function core($o,$key){
        if (! $o) return ($key==='ID') ? (int)$o : '';
        switch ($key){
            case 'ID': return (int)$o->get_id();
            case 'order_number': return (string)$o->get_order_number();
            case 'customer_id': return (int)$o->get_customer_id();
            case 'status': return (string)$o->get_status();
            case 'total': return (float)$o->get_total();
            case 'currency': return (string)$o->get_currency();
            default: return '';
        }
    }
    protected static function meta($id,$key){
        $v = get_post_meta($id, $key, true);
        return is_scalar($v) ? $v : json_encode($v);
    }
    protected static function computed($token,$o){
        if ($token==='updated_at') return current_time('mysql', false);
        if ($token==='items_count' && $o) return (int) count( $o->get_items() );
        if ($token==='is_paid' && $o) return $o->is_paid() ? 1 : 0;
        return '';
    }
    public static function build($t,$id){
        $o = self::order($id);
        $row=[];
        foreach ((array)$t['columns'] as $c){
            $col=$c['col']; $src=$c['source']; $key=$c['key'];
            if ($src==='core')      $row[$col] = self::core($o,$key);
            elseif ($src==='meta')  $row[$col] = (string) self::meta($id,$key);
            elseif ($src==='computed') $row[$col] = self::computed($key,$o);
        }
        if ( empty($row[$t['columns'][0]['col']]) ) $row[$t['columns'][0]['col']] = (int)$id;
        return $row;
    }
}
