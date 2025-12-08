<?php
if ( ! defined('ABSPATH') ) exit;

class AAA_WFUIM_Schema {

    public static function ensure_table($t){
        if ( ! is_array($t) ) return;
        global $wpdb;
        $table   = \AAA_WFUIM_Registry::table_name($t['slug']);
        $charset = $wpdb->get_charset_collate();

        $cols = []; $keys=[]; $primary=null;
        foreach ((array)$t['columns'] as $c){
            $col = self::safe_col($c['col'] ?? '');
            $type= self::safe_type($c['type'] ?? 'VARCHAR(190)');
            if ( ! $col ) continue;
            $null = !empty($c['primary']) ? 'NOT NULL' : 'NULL';
            $cols[] = "`$col` $type $null";
            if ( ! $primary && ! empty($c['primary']) ) $primary = $col;
            if ( ! empty($c['unique']) ) $keys[] = "UNIQUE KEY `{$col}` (`{$col}`)";
            if ( ! empty($c['index']) )  $keys[] = "KEY `{$col}` (`{$col}`)";
        }
        if ( ! $primary ) {
            $cols[] = "`object_id` BIGINT(20) UNSIGNED NOT NULL";
            $primary = 'object_id';
        }
        $cols[] = "PRIMARY KEY (`{$primary}`)";

        require_once ABSPATH.'wp-admin/includes/upgrade.php';
        $sql = "CREATE TABLE {$table} (" . implode(",\n", array_merge($cols,$keys)) . ") $charset;";
        dbDelta($sql);
        if ( AAA_WFUIM_DEBUG ) error_log('[WFUIM] ensure_table: '.$table);
    }

    public static function safe_col($name){
        $n = strtolower(preg_replace('/[^a-zA-Z0-9_]/','_',$name));
        return $n ? substr($n,0,64) : '';
    }
    public static function safe_type($type){
        $map = ['BOOLEAN'=>'TINYINT(1)'];
        $t = strtoupper($type);
        if ( isset($map[$t]) ) $t = $map[$t];
        $ok = ['VARCHAR(190)','VARCHAR(200)','TEXT','INT(11)','BIGINT(20) UNSIGNED','DECIMAL(12,6)','DECIMAL(18,6)','DATETIME','TINYINT(1)'];
        return in_array($t,$ok,true) ? $t : 'VARCHAR(190)';
    }
}
