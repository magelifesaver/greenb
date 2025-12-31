<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/core/options/admin/tabs/aaa-oc-wfcp.php
 * Purpose: WFCP (Workflow Feature Control Panel)
 *  - Show Main/Assets loader detection
 *  - Enable/disable real modules
 *  - Toggle per-module logging (<slug>_debug in aaa_oc_options, scope 'modules')
 *  - Report DB tables and columns declared by modules (via filters)
 * Version: 1.4.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$has_wrapper_get = function_exists('aaa_oc_get_option');
$has_wrapper_set = function_exists('aaa_oc_set_option');

$opt_get = function( $key, $default = 0 ) use ( $has_wrapper_get ) {
	if ( $has_wrapper_get ) return (int) aaa_oc_get_option( $key, 'modules', $default );
	$val = get_option( $key, $default );
	return is_scalar( $val ) ? (int) $val : 0;
};
$opt_set = function( $key, $val ) use ( $has_wrapper_set ) {
	if ( $has_wrapper_set ) { aaa_oc_set_option( $key, (int) $val, 'modules' ); return; }
	update_option( $key, (int) $val );
};

if ( ! class_exists( 'AAA_OC_Modules_Registry' ) ) {
	echo '<div class="notice notice-error"><p><strong>WFCP:</strong> Modules Registry not loaded.</p></div>';
	return;
}

global $wpdb;

/**
 * DEFAULT expected tables by module slug (minimal, safe fallback).
 * Modules should extend/override via the filter below.
 */
$default_expected_tables = [
	'core'          => [ $wpdb->prefix . 'aaa_oc_options', $wpdb->prefix . 'aaa_oc_order_index' ],
	'board'         => [],
];

/**
 * DEFAULT expected columns (table => [cols]) declared by modules (fallback).
 * Modules should register their column requirements via the filter below.
 */
$default_expected_columns = [
	// example: $wpdb->prefix.'aaa_oc_order_index' => ['driver_id']
];

/** Allow modules to declare their tables/columns dynamically from their loaders */
$expected_tables  = apply_filters( 'aaa_oc_expected_tables',  $default_expected_tables );
$expected_columns = apply_filters( 'aaa_oc_expected_columns', $default_expected_columns );

/** Helpers */
$table_exists = function( string $name ) use ( $wpdb ) : bool {
	$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $name ) );
	return ( $found === $name );
};
$column_exists = function( string $table, string $column ) use ( $wpdb ) : bool {
	return (bool) $wpdb->get_var( $wpdb->prepare(
		"SELECT COLUMN_NAME
		   FROM INFORMATION_SCHEMA.COLUMNS
		  WHERE TABLE_SCHEMA = DATABASE()
		    AND TABLE_NAME   = %s
		    AND COLUMN_NAME  = %s
		  LIMIT 1",
		$table, $column
	) );
};

/** Handle POST (Enable + Logging) */
if ( isset( $_POST['aaa_oc_wfcp_nonce'] ) && wp_verify_nonce( sanitize_text_field( $_POST['aaa_oc_wfcp_nonce'] ), 'aaa_oc_wfcp_save' ) ) {

	// Enabled modules map
	$new_enabled = [];
	if ( ! empty( $_POST['aaa_oc_mod_enabled'] ) && is_array( $_POST['aaa_oc_mod_enabled'] ) ) {
		foreach ( $_POST['aaa_oc_mod_enabled'] as $slug => $val ) {
			$new_enabled[ sanitize_key( $slug ) ] = 1;
		}
	}
	if ( method_exists( 'AAA_OC_Modules_Registry', 'set_enabled_map' ) ) {
		AAA_OC_Modules_Registry::set_enabled_map( $new_enabled );
	}

	// Per-module logging toggles (<slug>_debug)
	$posted_debug = isset( $_POST['aaa_oc_mod_debug'] ) && is_array( $_POST['aaa_oc_mod_debug'] )
		? array_map( 'sanitize_key', array_keys( $_POST['aaa_oc_mod_debug'] ) )
		: [];
	foreach ( AAA_OC_Modules_Registry::all() as $key => $m ) {
		$slug = isset( $m['slug'] ) ? sanitize_key( $m['slug'] ) : sanitize_key( $key );
		$on   = in_array( $slug, $posted_debug, true ) ? 1 : 0;
		$opt_set( "{$slug}_debug", $on );
	}

	echo '<div class="updated notice"><p>WFCP settings saved.</p></div>';
}

$all     = AAA_OC_Modules_Registry::all();          // recognition (core + discovered)
$enabled = AAA_OC_Modules_Registry::enabled_map();  // enabled map
?>
<div class="wrap">
	<h2 style="margin-top:1rem;">Workflow Feature Control Panel (WFCP)</h2>
	<p>Toggle modules, enable logging, and verify declared DB resources. Modules can declare their own expected tables/columns via filters.</p>

	<form method="post">
		<?php wp_nonce_field( 'aaa_oc_wfcp_save', 'aaa_oc_wfcp_nonce' ); ?>

		<style>
			.aaa-oc-wfcp table.wp-list-table th,
			.aaa-oc-wfcp table.wp-list-table td { vertical-align: top; }
			.badge { display:inline-block; padding:2px 8px; border-radius:10px; font-size:11px; line-height:1.7; margin:1px 2px 1px 0; }
			.badge-ok { background:#e6f6ea; color:#117a37; border:1px solid #bfe6c9; }
			.badge-miss { background:#fdecea; color:#b02a37; border:1px solid #f5c2c7; }
			.badge-core { background:#eef2ff; color:#273ea5; border:1px solid #c7d2fe; }
			.badge-assetonly { background:#fff7ed; color:#9a3412; border:1px solid #fed7aa; }
			.col-center { text-align:center; }
			.col-xsmall { width:90px; }
			.col-small { width:120px; }
			.col-medium { width:220px; }
			code.smallpath { font-size:11px; opacity:.85; }
			.small-muted { font-size:11px; opacity:.8; display:block; margin-top:4px; }
		</style>

		<div class="aaa-oc-wfcp">
			<table class="wp-list-table widefat striped">
				<thead>
					<tr>
						<th>Module</th>
						<th class="col-medium">Main Loader</th>
						<th class="col-medium">Assets Loader</th>
						<th>DB Resources (Declared by Modules)</th>
						<th class="col-xsmall col-center">Enabled</th>
						<th class="col-xsmall col-center">Logging</th>
						<th class="col-small">Version</th>
					</tr>
				</thead>
				<tbody>
				<?php if ( empty( $all ) ) : ?>
					<tr><td colspan="7">No modules discovered.</td></tr>
				<?php else : ?>
					<?php foreach ( $all as $key => $m ) :
						$slug     = isset( $m['slug'] ) ? sanitize_key( $m['slug'] ) : sanitize_key( $key );
						$label    = ! empty( $m['label'] ) ? esc_html( $m['label'] ) : ucwords( str_replace( ['-','_'], ' ', $slug ) );
						$ver      = ! empty( $m['version'] ) ? esc_html( $m['version'] ) : '';
						$always   = ! empty( $m['always_on'] );

						$loader   = isset( $m['loader'] ) ? $m['loader'] : '';
						$assets   = isset( $m['assets'] ) ? $m['assets'] : '';
						$has_main = $loader && file_exists( $loader );
						$has_asst = $assets && file_exists( $assets );

						$is_asset_only = ( ! $always && ! $has_main && $has_asst );
						$can_enable    = ( ! $always && $has_main );
						$is_enabled    = ! empty( $enabled[ $slug ] );

						// Logging flag
						$is_debug = ( $opt_get( "{$slug}_debug", 0 ) ? 1 : 0 );

						// Resources this module declares (tables + columns)
						$tables_for_module = $expected_tables[ $slug ] ?? [];
					?>
					<tr>
						<td>
							<strong><?php echo $label; ?></strong>
							<?php if ( $always ): ?>
								<span class="badge badge-core" title="Always On">Core</span>
							<?php elseif ( $is_asset_only ): ?>
								<span class="badge badge-assetonly" title="Assets-only package">Assets Only</span>
							<?php endif; ?>
							<br><code><?php echo esc_html( $slug ); ?></code>
						</td>

						<td>
							<?php
							if ( $loader ) {
								echo $has_main
									? '<span class="badge badge-ok">Exists</span><br><small class="smallpath"><code>' . esc_html( str_replace( ABSPATH, '/', $loader ) ) . '</code></small>'
									: '<span class="badge badge-miss">Missing</span><br><small class="smallpath"><code>' . esc_html( str_replace( ABSPATH, '/', $loader ) ) . '</code></small>';
							} else {
								echo '<span class="badge badge-miss">None</span>';
							}
							?>
						</td>

						<td>
							<?php
							if ( $assets ) {
								echo $has_asst
									? '<span class="badge badge-ok">Exists</span><br><small class="smallpath"><code>' . esc_html( str_replace( ABSPATH, '/', $assets ) ) . '</code></small>'
									: '<span class="badge badge-miss">Missing</span><br><small class="smallpath"><code>' . esc_html( str_replace( ABSPATH, '/', $assets ) ) . '</code></small>';
							} else {
								echo '<span class="badge badge-miss">None</span>';
							}
							?>
						</td>

						<td>
							<?php
							if ( empty( $tables_for_module ) ) {
								echo '<em>—</em>';
							} else {
								foreach ( $tables_for_module as $t ) {
									$has_table = $table_exists( $t );
									echo $has_table
										? '<div><span class="badge badge-ok">Table</span> <code>'. esc_html( $t ) .'</code></div>'
										: '<div><span class="badge badge-miss">Table</span> <code>'. esc_html( $t ) .'</code></div>';

									// If the module also declared columns for this table, show them
									$cols = $expected_columns[ $t ] ?? [];
									if ( $cols ) {
										echo '<div class="small-muted">Columns:</div>';
										foreach ( $cols as $c ) {
											$has_col = $has_table ? $column_exists( $t, $c ) : false;
											echo $has_col
												? '<span class="badge badge-ok">'. esc_html( $c ) .'</span> '
												: '<span class="badge badge-miss">'. esc_html( $c ) .'</span> ';
										}
									}
								}
							}
							?>
						</td>

						<td class="col-center">
							<?php if ( $can_enable ): ?>
								<label><input type="checkbox" name="aaa_oc_mod_enabled[<?php echo esc_attr( $slug ); ?>]" value="1" <?php checked( $is_enabled ); ?> /></label>
							<?php else: ?>
								—
							<?php endif; ?>
						</td>

						<td class="col-center">
							<label><input type="checkbox" name="aaa_oc_mod_debug[<?php echo esc_attr( $slug ); ?>]" value="1" <?php checked( $is_debug ); ?> /></label>
						</td>

						<td><?php echo $ver ? esc_html( $ver ) : '—'; ?></td>
					</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>

			<p class="submit">
				<button type="submit" class="button button-primary">Save Changes</button>
			</p>

			<p><em>Legend:</em>
				<span class="badge badge-ok">Exists</span> = file/table/column found &nbsp;
				<span class="badge badge-miss">Missing</span> = not found &nbsp;
				<span class="badge badge-core">Core</span> = always on &nbsp;
				<span class="badge badge-assetonly">Assets Only</span> = only an assets loader exists in the folder
			</p>
		</div>
	</form>
</div>
