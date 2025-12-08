<?php
/**
 * File: /wp-content/plugins/aaa-wf-user-index-manager/index/class-aaa-wfuim-indexer.php
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( AAA_WFUIM_DEBUG ) error_log('[WFUIM:DEBUG] indexer loaded');

class AAA_WFUIM_Indexer {

    public static function init() {
        add_action('wp_login',  [__CLASS__, 'handle_login'], 10, 2);
        add_action('wp_logout', [__CLASS__, 'handle_logout'], 10);

        // Live update when profile/meta changes
        add_action('profile_update', function($uid){ self::maybe_update($uid,'profile_update'); }, 10, 1);

        // Reindex when relevant meta keys change
        add_action('updated_user_meta', function($meta_id,$uid,$key,$val){
            $s = aaa_wfuim_get_settings();
            if ( empty($s['auto_update']) ) return;

            $should = self::key_is_included($key, $s) || self::is_coord_key($key, $s) || self::looks_like_address($key);
            if ( $should ) { self::maybe_update($uid,'updated_user_meta:'.$key); }
        }, 10, 4);
    }

    public static function handle_login( $user_login, $user ) {
        $s = aaa_wfuim_get_settings();
        if ( empty( $s['enabled'] ) ) { if ( AAA_WFUIM_DEBUG ) error_log('[WFUIM] login skipped (disabled)'); return; }
        self::index_user( $user->ID );
    }

    public static function handle_logout() {
        $s = aaa_wfuim_get_settings();
        if ( empty( $s['purge_on_logout'] ) ) return;

        $uid = get_current_user_id();
        if ( ! $uid && ! empty($GLOBALS['aaa_wfuim_last_uid']) ) {
            $uid = (int) $GLOBALS['aaa_wfuim_last_uid']; // fallback if current user already cleared
        }
        if ( $uid ) self::purge_user( $uid );
    }

    public static function maybe_update( $uid, $src='unknown' ) {
        $s = aaa_wfuim_get_settings();
        if ( empty( $s['enabled'] ) || empty( $s['auto_update'] ) ) return;
        if ( AAA_WFUIM_DEBUG ) error_log("[WFUIM] reindex via {$src} for user {$uid}");
        self::index_user( $uid );
    }

    /** Core: build row and upsert */
    public static function index_user( $user_id ) {
        if ( ! $user_id ) return;
        $u = get_userdata( $user_id );
        if ( ! $u ) return;

        $s        = aaa_wfuim_get_settings();
        $meta     = get_user_meta( $user_id );
        $filtered = self::filter_meta( $meta, $s );

        // Curated columns (addresses)
        $billing  = self::compose_address($filtered, 'billing');
        $shipping = self::compose_address($filtered, 'shipping');

        // Coordinates
        list($lat,$lng) = self::extract_coords($filtered, $s);

        // JSON blob of included meta
        $json = wp_json_encode( self::normalize_meta_for_json($filtered) );

        // Base data + formats
        global $wpdb; $table = aaa_wfuim_table();
        $data = [
            'user_id'          => $user_id,
            'display_name'     => $u->display_name,
            'user_email'       => $u->user_email,
            'billing_address'  => $billing,
            'shipping_address' => $shipping,
            'lat'              => ($lat !== '' ? $lat : null),
            'lng'              => ($lng !== '' ? $lng : null),
            'meta_json'        => $json,
            'updated_at'       => current_time('mysql', false),
        ];
        $fmt  = ['%d','%s','%s','%s','%s','%f','%f','%s','%s'];

        // Extra columns from settings
        $extras = self::parse_extras( $s );
        foreach ( $extras as $ex ) {
            $val = self::get_meta_value($filtered, $ex['meta']);
            if ( $ex['type'] === 'DECIMAL(12,6)' ) {
                $data[$ex['col']] = ( $val === '' || $val === null ) ? null : round( (float)$val, 6 );
                $fmt[] = '%f';
            } elseif ( $ex['type'] === 'INT(11)' ) {
                $data[$ex['col']] = ( $val === '' || $val === null ) ? null : (int)$val;
                $fmt[] = '%d';
            } else {
                $data[$ex['col']] = ( $val === '' || $val === null ) ? null : sanitize_text_field( (string)$val );
                $fmt[] = '%s';
            }
        }

        // Upsert
        $ok = $wpdb->replace( $table, $data, $fmt );
        if ( AAA_WFUIM_DEBUG ) error_log('[WFUIM] upsert user '.$user_id.' => '.( $ok ? 'OK':'FAIL' ));
    }

    public static function purge_user( $user_id ) {
        global $wpdb; $table = aaa_wfuim_table();
        $wpdb->delete( $table, ['user_id'=>$user_id], ['%d'] );
        if ( AAA_WFUIM_DEBUG ) error_log('[WFUIM] purged user '.$user_id);
    }

    /** ------- helpers ------- */

    protected static function pattern_list( $txt ) {
        $lines = array_filter( array_map( 'trim', preg_split('/\r\n|\r|\n/',$txt ?? '') ) );
        return array_values( $lines );
    }

    protected static function key_is_included( $key, $settings ) {
        $w = self::pattern_list( $settings['whitelist'] ?? '' );
        $x = self::pattern_list( $settings['exclude'] ?? '' );
        $in = empty($w); // if whitelist empty => include all (before excludes)

        foreach ($w as $p) { if ( self::match($key, $p) ) { $in = true; break; } }
        foreach ($x as $p) { if ( self::match($key, $p) ) { $in = false; break; } }
        return $in;
    }

    protected static function is_coord_key( $key, $settings ) {
        $latKeys = array_filter(array_map('trim', explode(',', (string)($settings['lat_keys'] ?? ''))));
        $lngKeys = array_filter(array_map('trim', explode(',', (string)($settings['lng_keys'] ?? ''))));
        return in_array($key, $latKeys, true) || in_array($key, $lngKeys, true);
    }

    protected static function looks_like_address( $key ) {
        return str_starts_with($key, 'billing_') || str_starts_with($key, 'shipping_') || str_starts_with($key, '_billing_') || str_starts_with($key, '_shipping_');
    }

    protected static function match( $key, $pattern ) {
        // supports '*' suffix wildcard, e.g. 'billing_*' or '_billing_*'
        if ( substr($pattern,-1) === '*' ) {
            $pref = substr($pattern,0,-1);
            return str_starts_with( $key, $pref );
        }
        return $key === $pattern;
    }

    protected static function filter_meta( $meta, $settings ) {
        $out = [];
        foreach ( $meta as $k => $vals ) {
            if ( self::key_is_included($k, $settings) ) {
                $v = is_array($vals) ? ( count($vals) ? $vals[0] : '' ) : $vals;
                $out[$k] = $v;
            }
        }
        return $out;
    }

    protected static function compose_address( $meta, $type='billing' ) {
        $grab = function($suffix) use($meta,$type){
            $keys = ["_{$type}_{$suffix}","{$type}_{$suffix}"];
            foreach ($keys as $k) { if (!empty($meta[$k])) return sanitize_text_field( (string)$meta[$k] ); }
            return '';
        };
        $a1=$grab('address_1'); $a2=$grab('address_2'); $city=$grab('city');
        $st=$grab('state'); $zip=$grab('postcode'); $cty=$grab('country');

        $parts = array_filter([$a1, $a2]);
        $line1 = implode(' ', $parts);
        $line2 = trim(implode(' ', array_filter([$city, $st, $zip])));
        $line3 = $cty;
        return trim( implode(', ', array_filter([$line1,$line2,$line3])) );
    }

    protected static function extract_coords( $meta, $settings ) {
        $lat = $lng = '';
        $latKeys = array_filter(array_map('trim', explode(',', (string)($settings['lat_keys'] ?? ''))));
        $lngKeys = array_filter(array_map('trim', explode(',', (string)($settings['lng_keys'] ?? ''))));
        foreach ($latKeys as $k){ if(isset($meta[$k]) && is_scalar($meta[$k])) { $lat = (string)$meta[$k]; break; } }
        foreach ($lngKeys as $k){ if(isset($meta[$k]) && is_scalar($meta[$k])) { $lng = (string)$meta[$k]; break; } }
        $lat = is_numeric($lat) ? (string)round((float)$lat,6) : '';
        $lng = is_numeric($lng) ? (string)round((float)$lng,6) : '';
        return [$lat,$lng];
    }

    protected static function normalize_meta_for_json( $arr ) {
        $out=[];
        foreach ($arr as $k=>$v) {
            if ( is_array($v) || is_object($v) ) { $out[$k] = json_decode( wp_json_encode($v), true ); }
            else { $out[$k] = is_scalar($v) ? $v : (string)$v; }
        }
        return $out;
    }

    /** ----- extras (shared with installer) ----- */
    protected static function parse_extras( $settings ) {
        $raw = (string)($settings['extra_columns'] ?? '');
        $lines = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $raw)));
        $out = [];
        foreach ( $lines as $line ) {
            $parts = array_map('trim', explode('|', $line));
            if ( count($parts) < 2 ) continue;
            $col  = preg_replace('/[^a-zA-Z0-9_]/', '_', strtolower($parts[0]));
            $meta = $parts[1];
            $type = strtoupper($parts[2] ?? 'VARCHAR(190)');
            if ( ! in_array($type, ['VARCHAR(190)','TEXT','DECIMAL(12,6)','INT(11)'], true) ) {
                $type = 'VARCHAR(190)';
            }
            $out[] = ['col'=>$col,'meta'=>$meta,'type'=>$type];
        }
        return $out;
    }

    protected static function get_meta_value( $filtered_meta, $meta_key ) {
        if ( isset($filtered_meta[$meta_key]) ) return $filtered_meta[$meta_key];
        // if not whitelisted, attempt raw user_meta first value
        $raw = get_user_meta( get_current_user_id(), $meta_key, true );
        return is_array($raw) ? (count($raw)?$raw[0]:'') : $raw;
    }
}
