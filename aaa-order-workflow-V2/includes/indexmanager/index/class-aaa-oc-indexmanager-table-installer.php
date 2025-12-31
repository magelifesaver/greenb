<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/indexmanager/index/class-aaa-oc-indexmanager-table-installer.php
 * Purpose: Create/upgrade dynamic IndexManager tables (users, products, orders).
 * Style:   dbDelta for base schema; guarded ALTER for secondary indexes; WFCP-driven debug; one-per-request guard.
 * Version: 1.2.1
 */

if ( ! defined('ABSPATH') ) exit;

class AAA_OC_IndexManager_Table_Installer {

	/** One-per-request guard for ensure_all */
	private static $did_all = false;

	/** WFCP-driven debug (modules scope → key: indexmanager_debug) */
	private static function debug_on() : bool {
		if ( function_exists('aaa_oc_get_option') ) {
			return (bool) aaa_oc_get_option( 'indexmanager_debug', 'modules', 0 );
		}
		return false;
	}

	public static function ensure_all() {
		if ( self::$did_all ) return;
		self::$did_all = true;
		foreach ( [ 'users', 'products', 'orders' ] as $e ) self::ensure( $e );
	}

	public static function ensure( $entity ) {
		$cfg   = AAA_OC_IndexManager_Helpers::get_opt( $entity );
		$table = AAA_OC_IndexManager_Helpers::table_name( $entity );

		$cols = [];
		$unique_keys = [];
		$plain_keys  = [];
		$pk   = null;

		foreach ( (array) ( $cfg['columns'] ?? [] ) as $c ) {
			$col_raw = $c['col'] ?? '';
			$col = strtolower( preg_replace( '/[^a-zA-Z0-9_]/', '_', $col_raw ) );
			if ( $col === '' ) { continue; }

			$type = strtoupper( $c['type'] ?? 'VARCHAR(190)' );
			if ( $type === 'BOOLEAN' ) $type = 'TINYINT(1)';

			$notnull = ! empty( $c['primary'] ) ? 'NOT NULL' : 'NULL';
			$cols[]  = "`{$col}` {$type} {$notnull}";

			if ( ! $pk && ! empty( $c['primary'] ) ) $pk = $col;

			if ( ! empty( $c['unique'] ) ) {
				$unique_keys[] = "UNIQUE KEY `{$col}` (`{$col}`)";
			}
			if ( ! empty( $c['index'] ) )  {
				$plain_keys[]  = [ 'name' => $col, 'expr' => "(`{$col}`)" ];
			}
		}

		if ( ! $pk ) { $cols[] = "`object_id` BIGINT(20) UNSIGNED NOT NULL"; $pk = 'object_id'; }
		$cols[] = "PRIMARY KEY (`{$pk}`)";

		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$sql = "CREATE TABLE {$table} (
" . implode( ",
", array_merge( $cols, $unique_keys ) ) . "
) " . $wpdb->get_charset_collate() . ";";

		dbDelta( $sql );

		foreach ( $plain_keys as $k ) self::ensure_index( $table, $k['name'], $k['expr'] );

		if ( self::debug_on() ) {
			$msg = "[IM][ensure] ensured table {$table} (entity={$entity})";
			if ( function_exists('aaa_oc_log') ) { aaa_oc_log($msg); } else { error_log($msg); }
		}
	}

	private static function ensure_index( string $table, string $index, string $cols_expr ) : void {
		global $wpdb;

		$index     = trim( $index );
		$cols_expr = trim( $cols_expr );

		if ( $index === '' || $cols_expr === '' ) {
			if ( self::debug_on() ) {
				$msg = "[IM][ensure_index] skipped invalid index for {$table}";
				if ( function_exists('aaa_oc_log') ) { aaa_oc_log($msg); } else { error_log($msg); }
			}
			return;
		}

		$exists = $wpdb->get_var( $wpdb->prepare(
			"SELECT INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS
			 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s AND INDEX_NAME = %s",
			$table, $index
		) );

		if ( $exists ) return;

		$wpdb->query( "ALTER TABLE {$table} ADD INDEX `{$index}` {$cols_expr}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( self::debug_on() ) {
			$msg = "[IM][ensure_index] added index {$index} on {$table}";
			if ( function_exists('aaa_oc_log') ) { aaa_oc_log($msg); } else { error_log($msg); }
		}
	}

	public static function rebuild( string $entity, bool $strict = true ) : void {
		global $wpdb;
		$dest   = AAA_OC_IndexManager_Helpers::table_name( $entity );
		$tmp    = $dest . '_new_' . wp_generate_password( 6, false, false );

		$cfg  = AAA_OC_IndexManager_Helpers::get_opt( $entity );
		$orig = $cfg['columns'] ?? [];
		if ( empty($orig) ) return;

		$cols = []; $unique = []; $pk = null; $plain = [];
		foreach ( $orig as $c ) {
			$col_raw = $c['col'] ?? '';
			$col = strtolower( preg_replace( '/[^a-zA-Z0-9_]/', '_', $col_raw ) );
			if ( $col === '' ) continue;

			$type = strtoupper( $c['type'] ?? 'VARCHAR(190)' );
			if ( $type === 'BOOLEAN' ) $type = 'TINYINT(1)';

			$notnull = ! empty( $c['primary'] ) ? 'NOT NULL' : 'NULL';
			$cols[]  = "`{$col}` {$type} {$notnull}";
			if ( ! $pk && ! empty($c['primary']) ) $pk = $col;
			if ( ! empty($c['unique']) ) $unique[] = "UNIQUE KEY `{$col}` (`{$col}`)";
			if ( ! empty($c['index']) )  $plain[]  = [ 'name'=>$col, 'expr'=>"(`{$col}`)" ];
		}
		if ( ! $pk ) { $cols[]="`object_id` BIGINT(20) UNSIGNED NOT NULL"; $pk='object_id'; }
		$cols[] = "PRIMARY KEY (`{$pk}`)";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$sql = "CREATE TABLE {$tmp} (
" . implode(",\n", array_merge($cols,$unique)) . "
) ".$wpdb->get_charset_collate().";";
		dbDelta($sql);
		foreach ( $plain as $k ) self::ensure_index( $tmp, $k['name'], $k['expr'] );

		$old_cols = self::list_columns($dest);
		$new_cols = self::list_columns($tmp);
		$common   = array_values(array_intersect($old_cols, $new_cols));
		if ( ! empty($common) ) {
			$wpdb->query( "INSERT INTO `$tmp` (`".implode('`,`',$common)."`) SELECT `".implode('`,`',$common)."` FROM `$dest`" );
		}

		$bak = $dest . '_old_' . wp_generate_password( 6, false, false );
		$wpdb->query("RENAME TABLE `$dest` TO `$bak`, `$tmp` TO `$dest`");
		$wpdb->query("DROP TABLE `$bak`");

		if ( self::debug_on() ) {
			$msg = "[IM][rebuild] rebuilt {$dest} (entity={$entity})";
			if ( function_exists('aaa_oc_log') ) { aaa_oc_log($msg); } else { error_log($msg); }
		}
	}

	private static function list_columns( string $table ) : array {
		global $wpdb;
		$cols = $wpdb->get_col( "SHOW COLUMNS FROM `$table` LIKE '%'" );
		return array_map( 'strval', (array)$cols );
	}
}
