<?php
if ( ! defined('ABSPATH') ) exit;

class AAA_WFUIM_Builder_User {

    protected static function meta($uid,$key){
        $v = get_user_meta($uid, $key, true);
        return is_scalar($v) ? $v : json_encode($v);
    }
    protected static function address($uid,$type){
        $g = function($s) use($uid,$type){
            $a = self::meta($uid,"{$type}_{$s}");
            if ($a==='') $a = self::meta($uid,"_{$type}_{$s}");
            return $a;
        };
        $a1=$g('address_1'); $a2=$g('address_2'); $c=$g('city'); $st=$g('state'); $pc=$g('postcode'); $co=$g('country');
        $l1 = trim(implode(' ', array_filter([$a1,$a2])));
        $l2 = trim(implode(' ', array_filter([$c,$st,$pc])));
        return trim(implode(', ', array_filter([$l1,$l2,$co])));
    }
    protected static function coords($uid,$lat_keys,$lng_keys){
        $lat=''; $lng='';
        foreach ($lat_keys as $k){ $v=self::meta($uid,$k); if($v!==''){ $lat=$v; break; } }
        foreach ($lng_keys as $k){ $v=self::meta($uid,$k); if($v!==''){ $lng=$v; break; } }
        $lat = is_numeric($lat)? round((float)$lat,6) : null;
        $lng = is_numeric($lng)? round((float)$lng,6) : null;
        return [$lat,$lng];
    }
    protected static function computed($token,$uid,$lat_keys,$lng_keys){
        // tokens: updated_at | billing_address | shipping_address | lat | lng | flag_has_latlng
        // flag_meta_present:<meta_key>
        // coalesce_meta:<k1,k2,...>
        // orders_count[:days] | orders_total[:days]
        if ($token==='updated_at') return current_time('mysql', false);
        if ($token==='billing_address')  return self::address($uid,'billing');
        if ($token==='shipping_address') return self::address($uid,'shipping');
        if ($token==='lat' || $token==='lng'){
            list($la,$ln) = self::coords($uid,$lat_keys,$lng_keys);
            return $token==='lat' ? $la : $ln;
        }
        if ($token==='flag_has_latlng'){
            list($la,$ln) = self::coords($uid,$lat_keys,$lng_keys);
            return ($la!==null && $ln!==null) ? 1 : 0;
        }
        if ( str_starts_with($token,'flag_meta_present:') ){
            $k = trim(substr($token, strlen('flag_meta_present:')));
            return self::meta($uid,$k) !== '' ? 1 : 0;
        }
        if ( str_starts_with($token,'coalesce_meta:') ){
            $list = array_filter(array_map('trim', explode(',', substr($token, strlen('coalesce_meta:')))));
            foreach ($list as $k){ $v=self::meta($uid,$k); if($v!=='') return $v; }
            return '';
        }
        if ( function_exists('wc_get_orders') && (str_starts_with($token,'orders_count') || str_starts_with($token,'orders_total')) ){
            $days = 0;
            if ( preg_match('/:(\d+)$/', $token, $m) ) $days = (int)$m[1];
            $args = ['customer'=>$uid, 'status'=>['processing','completed'], 'limit'=>-1, 'return'=>'objects'];
            $orders = wc_get_orders($args);
            $cut = $days ? ( time() - $days*DAY_IN_SECONDS ) : 0;
            $count=0; $sum=0.0;
            foreach ($orders as $o){
                $t = $o->get_date_created(); $ts = $t ? $t->getTimestamp() : 0;
                if ( $days && $ts && $ts < $cut ) continue;
                $count++; $sum += (float)$o->get_total();
            }
            return str_starts_with($token,'orders_count') ? $count : round($sum,2);
        }
        return ''; // fallback
    }

    public static function build($t,$uid){
        $u = get_userdata($uid); if (! $u) return [];
        $lat_keys = array_filter(array_map('trim', explode(',', $t['lat_keys'] ?? '')));
        $lng_keys = array_filter(array_map('trim', explode(',', $t['lng_keys'] ?? '')));

        $row = [];
        foreach ((array)$t['columns'] as $c){
            $col=$c['col']; $src=$c['source']; $key=$c['key'];
            switch ($src) {
                case 'core':     $row[$col] = ($key==='ID') ? (int)$u->ID : (string)($u->$key ?? ''); break;
                case 'meta':     $row[$col] = (string) self::meta($uid, $key); break;
                case 'computed': $row[$col] = self::computed($key, $uid, $lat_keys, $lng_keys); break;
            }
        }
        if ( empty($row[$t['columns'][0]['col']]) ) $row[$t['columns'][0]['col']] = (int)$u->ID;
        return $row;
    }
}
