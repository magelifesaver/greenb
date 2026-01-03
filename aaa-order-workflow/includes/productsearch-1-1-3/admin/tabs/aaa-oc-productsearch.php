<?php
/**
 * File: /plugins/aaa-order-workflow/includes/productsearch/admin/tabs/aaa-oc-productsearch.php
 * Purpose: ProductSearch settings (procedural) — manage synonyms (brand/category/global),
 *          and provide "Refresh Index" and "Clear & Rebuild Index" buttons.
 * Version: 1.2.2
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'AAA_OC_ProductSearch_Table_Installer' ) || ! class_exists( 'AAA_OC_ProductSearch_Table_Indexer' ) ) {
	echo '<div class="notice notice-error"><p>ProductSearch module not fully loaded. Ensure module loader is enabled.</p></div>';
	return;
}

/** Table helpers */
function _aaa_oc_ps_syn_table() {
	global $wpdb;
	return $wpdb->prefix . AAA_OC_ProductSearch_Table_Installer::T_SYNONYMS;
}

/**
 * Get ALL product_cat terms (parents + children) with a "Parent › Child" label.
 */
function _aaa_oc_ps_get_all_cats() {
	global $wpdb;

	$rows = $wpdb->get_results(
		"SELECT t.term_id, t.name, tt.parent
		 FROM {$wpdb->terms} t
		 INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
		 WHERE tt.taxonomy = 'product_cat'
		 ORDER BY t.name ASC",
		ARRAY_A
	);

	if ( empty( $rows ) ) {
		return array();
	}

	// Index by term_id for parent lookup.
	$by_id = array();
	foreach ( $rows as $r ) {
		$by_id[ (int) $r['term_id'] ] = $r;
	}

	$cats = array();
	foreach ( $rows as $r ) {
		$term_id = (int) $r['term_id'];
		$name    = $r['name'];
		$parent  = (int) $r['parent'];

		$label = $name;
		$guard = 0;
		// Build "Parent › Child" chain (max depth 5).
		while ( $parent && isset( $by_id[ $parent ] ) && $guard < 5 ) {
			$p      = $by_id[ $parent ];
			$label  = $p['name'] . ' › ' . $label;
			$parent = (int) $p['parent'];
			$guard++;
		}

		$cats[] = (object) array(
			'term_id' => $term_id,
			'name'    => $name,
			'label'   => $label,
		);
	}

	return $cats;
}

/**
 * Read rows from synonyms table and reshape into UI rows.
 *
 * Brand / Category:
 *   - One row per DB row (same as before).
 *
 * Global:
 *   - Grouped by term_id (group id).
 *   - Each UI row shows:
 *       term (first token in group) as "Search word"
 *       synonym (remaining tokens, comma-separated) in Synonyms field.
 */
function _aaa_oc_ps_get_rows() {
	global $wpdb;

	$table = _aaa_oc_ps_syn_table();
	$raw   = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY scope, term_id, synonym", ARRAY_A );
	if ( empty( $raw ) ) {
		return array();
	}

	$rows          = array();
	$global_groups = array();

	foreach ( $raw as $r ) {
		if ( $r['scope'] === 'global' ) {
			$gid = (int) $r['term_id'];
			if ( ! isset( $global_groups[ $gid ] ) ) {
				$global_groups[ $gid ] = array(
					'tokens' => array(),
					'bidi'   => (int) $r['bidi'],
				);
			}
			$global_groups[ $gid ]['tokens'][] = $r['synonym'];
			if ( (int) $r['bidi'] === 1 ) {
				$global_groups[ $gid ]['bidi'] = 1;
			}
		} else {
			$rows[] = $r; // brand / category unchanged.
		}
	}

	// Convert global groups into synthetic rows.
	foreach ( $global_groups as $gid => $group ) {
		$tokens = array_values( array_unique( array_filter( array_map( 'trim', $group['tokens'] ) ) ) );
		if ( empty( $tokens ) ) {
			continue;
		}
		$term      = array_shift( $tokens );       // "Search word"
		$syn_field = implode( ', ', $tokens );     // remaining tokens as CSV

		$rows[] = array(
			'scope'       => 'global',
			'term_id'     => (int) $gid,
			'synonym'     => $syn_field,
			'bidi'        => (int) $group['bidi'],
			'global_term' => $term,
		);
	}

	return $rows;
}

/** Save posted rows (truncate + insert for simplicity) */
function _aaa_oc_ps_save_rows() {
	global $wpdb;
	$table = _aaa_oc_ps_syn_table();

	// Arrays are now keyed by a row id, e.g. ps_scope[3], ps_bidi[3], etc.
	$scopes       = isset( $_POST['ps_scope'] ) && is_array( $_POST['ps_scope'] ) ? $_POST['ps_scope'] : array();
	$terms        = isset( $_POST['ps_term'] ) && is_array( $_POST['ps_term'] ) ? $_POST['ps_term'] : array();
	$global_terms = isset( $_POST['ps_global_term'] ) && is_array( $_POST['ps_global_term'] ) ? $_POST['ps_global_term'] : array();
	$syns         = isset( $_POST['ps_syn'] ) && is_array( $_POST['ps_syn'] ) ? $_POST['ps_syn'] : array();
	$bidi         = isset( $_POST['ps_bidi'] ) && is_array( $_POST['ps_bidi'] ) ? $_POST['ps_bidi'] : array();

	$wpdb->query( "TRUNCATE TABLE {$table}" );

	$global_gid = 1; // simple group id counter for global rows.

	foreach ( $scopes as $row_id => $scope_raw ) {
		$scope_raw = $scope_raw ?? 'global';
		$scope     = in_array( $scope_raw, array( 'brand', 'category', 'global' ), true ) ? $scope_raw : 'global';

		$is_bidi = ! empty( $bidi[ $row_id ] ) ? 1 : 0;

		$term_id      = isset( $terms[ $row_id ] ) ? (int) $terms[ $row_id ] : 0;
		$global_term  = isset( $global_terms[ $row_id ] ) ? sanitize_text_field( $global_terms[ $row_id ] ) : '';
		$syn_str      = isset( $syns[ $row_id ] ) ? (string) $syns[ $row_id ] : '';
		$list_tokens  = array_filter( array_map( 'trim', explode( ',', $syn_str ) ) );

		if ( $scope === 'global' ) {
			// Global: "Search word" + synonyms all belong to a group.
			$tokens = array();
			if ( '' !== $global_term ) {
				$tokens[] = $global_term;
			}
			if ( ! empty( $list_tokens ) ) {
				$tokens = array_merge( $tokens, $list_tokens );
			}

			$tokens = array_values( array_unique( array_filter( $tokens ) ) );
			if ( empty( $tokens ) ) {
				continue;
			}

			$group_id = $global_gid++;
			foreach ( $tokens as $tok ) {
				$wpdb->insert(
					$table,
					array(
						'scope'      => 'global',
						'term_id'    => $group_id,
						'synonym'    => strtolower( remove_accents( $tok ) ),
						'bidi'       => $is_bidi,
						'active'     => 1,
						'updated_at' => current_time( 'mysql' ),
					),
					array( '%s', '%d', '%s', '%d', '%d', '%s' )
				);
			}
			continue;
		}

		// Brand / Category.
		if ( ! $term_id ) {
			continue;
		}
		if ( empty( $list_tokens ) ) {
			continue;
		}

		foreach ( $list_tokens as $syn ) {
			$wpdb->insert(
				$table,
				array(
					'scope'      => $scope,
					'term_id'    => $term_id,
					'synonym'    => strtolower( remove_accents( $syn ) ),
					'bidi'       => $is_bidi,
					'active'     => 1,
					'updated_at' => current_time( 'mysql' ),
				),
				array( '%s', '%d', '%s', '%d', '%d', '%s' )
			);
		}
	}
}

/**
 * Row renderer
 *
 * @param array $row      DB/virtual row data.
 * @param array $brands   Brand terms.
 * @param array $cats     Category terms (with ->label).
 * @param int   $row_id   Unique row id used for field names.
 */
function _aaa_oc_ps_row_html( $row, $brands, $cats, $row_id ) {
	$scope = $row['scope']   ?? 'brand';
	$term  = $row['term_id'] ?? '';
	$syn   = $row['synonym'] ?? '';
	$bidi  = ! empty( $row['bidi'] );

	$global_term = $row['global_term'] ?? '';

	$row_id_attr = (int) $row_id;

	echo '<tr>';

	// Scope select.
	echo '<td><select name="ps_scope[' . $row_id_attr . ']" class="aaa-oc-ps-scope">
			<option value="brand" ' . selected( $scope, 'brand', false ) . '>Brand</option>
			<option value="category" ' . selected( $scope, 'category', false ) . '>Category</option>
			<option value="global" ' . selected( $scope, 'global', false ) . '>Global</option>
		  </select></td>';

	// Term cell: brand/category dropdown + global text input (shown/hidden by JS).
	echo '<td>';

	// Brand/Category select.
	$sel_style = ( $scope === 'global' ) ? 'style="display:none"' : '';
	echo '<select name="ps_term[' . $row_id_attr . ']" class="aaa-oc-ps-term-select" ' . $sel_style . '>';
	echo '<option value="" data-scope="brand" style="display:none">— Select Brand —</option>';
	foreach ( $brands as $b ) {
		$hide = ( $scope !== 'brand' ) ? 'style="display:none"' : '';
		echo '<option data-scope="brand" value="' . $b->term_id . '" ' . selected( $term, $b->term_id, false ) . ' ' . $hide . '>Brand: ' . esc_html( $b->name ) . '</option>';
	}
	echo '<option value="" data-scope="category" style="display:none">— Select Category —</option>';
	foreach ( $cats as $c ) {
		$hide  = ( $scope !== 'category' ) ? 'style="display:none"' : '';
		$label = isset( $c->label ) ? $c->label : $c->name;
		echo '<option data-scope="category" value="' . $c->term_id . '" ' . selected( $term, $c->term_id, false ) . ' ' . $hide . '>Category: ' . esc_html( $label ) . '</option>';
	}
	echo '</select>';

	// Global "Search word" input.
	$global_style = ( $scope === 'global' ) ? '' : 'style="display:none"';
	echo '<input type="text" name="ps_global_term[' . $row_id_attr . ']" class="aaa-oc-ps-term-global regular-text" ' . $global_style . ' value="' . esc_attr( $global_term ) . '" placeholder="Search word (global)" />';

	echo '</td>';

	// Synonyms field.
	echo '<td><input type="text" name="ps_syn[' . $row_id_attr . ']" value="' . esc_attr( $syn ) . '" class="regular-text" placeholder="e.g. cartridge, carts"></td>';

	// Bidi checkbox.
	echo '<td><label><input type="checkbox" name="ps_bidi[' . $row_id_attr . ']" ' . checked( $bidi, true, false ) . '> Bidirectional</label></td>';

	// Delete button.
	echo '<td><button type="button" class="button aaa-oc-ps-del">—</button></td>';

	echo '</tr>';
}

/** ====== Handle POST ====== */
if (
	current_user_can( 'manage_woocommerce' )
	&& 'POST' === $_SERVER['REQUEST_METHOD']
	&& isset( $_POST['aaa_oc_ps_action'] )
	&& check_admin_referer( 'aaa_oc_ps', 'aaa_oc_ps_nonce' )
) {
	$act = sanitize_text_field( $_POST['aaa_oc_ps_action'] );
	if ( 'save' === $act ) {
		_aaa_oc_ps_save_rows();
		echo '<div class="updated"><p>ProductSearch synonyms saved.</p></div>';
	} elseif ( 'refresh' === $act ) {
		AAA_OC_ProductSearch_Table_Indexer::refresh_all();
		echo '<div class="updated"><p>Index refreshed.</p></div>';
	} elseif ( 'clear_rebuild' === $act ) {
		AAA_OC_ProductSearch_Table_Indexer::clear_and_rebuild();
		echo '<div class="updated"><p>Index cleared and rebuilt.</p></div>';
	}
}

/** ====== Load data for UI ====== */
$rows   = _aaa_oc_ps_get_rows();
$brands = get_terms(
	array(
		'taxonomy'   => 'berocket_brand',
		'hide_empty' => false,
	)
);
$cats = _aaa_oc_ps_get_all_cats();

// Row counter for unique row ids.
$aaa_oc_ps_row_counter = 0;
?>
<div class="wrap">
	<h2>ProductSearch — Synonyms &amp; Index</h2>

	<form method="post" style="margin-bottom:18px">
		<?php wp_nonce_field( 'aaa_oc_ps', 'aaa_oc_ps_nonce' ); ?>
		<input type="hidden" name="aaa_oc_ps_action" value="refresh">
		<button class="button">Refresh Index</button>
		<button class="button button-secondary" name="aaa_oc_ps_action" value="clear_rebuild" onclick="return confirm('Clear and fully rebuild the index?')">Clear &amp; Rebuild Index</button>
	</form>

	<form method="post">
		<?php wp_nonce_field( 'aaa_oc_ps', 'aaa_oc_ps_nonce' ); ?>
		<input type="hidden" name="aaa_oc_ps_action" value="save">

		<table class="widefat striped" style="max-width:1100px">
			<thead>
				<tr>
					<th style="width:120px">Scope</th>
					<th style="width:360px">Term / Search word</th>
					<th>Synonyms (comma-separated)</th>
					<th style="width:120px">Bidirectional</th>
					<th style="width:60px"></th>
				</tr>
			</thead>
			<tbody id="aaa-oc-ps-rows">
				<?php
				if ( ! empty( $rows ) ) {
					foreach ( $rows as $r ) {
						_aaa_oc_ps_row_html( $r, $brands, $cats, $aaa_oc_ps_row_counter );
						$aaa_oc_ps_row_counter++;
					}
				}
				// + one blank row.
				_aaa_oc_ps_row_html(
					array(
						'scope'       => 'brand',
						'term_id'     => '',
						'synonym'     => '',
						'bidi'        => 0,
						'global_term' => '',
					),
					$brands,
					$cats,
					$aaa_oc_ps_row_counter
				);
				$aaa_oc_ps_row_counter++;
				?>
			</tbody>
		</table>

		<p style="margin-top:10px">
			<button type="button" class="button" id="aaa-oc-ps-add">+ Add row</button>
			<button type="submit" class="button button-primary">Save Synonyms</button>
		</p>
	</form>
</div>

<script>
(function($){
	// Start counter from the last row id used in PHP.
	var AAAOC_PS_ROW_COUNTER = <?php echo (int) $aaa_oc_ps_row_counter - 1; ?>;

	$('#aaa-oc-ps-add').on('click', function(){
		let $last = $('#aaa-oc-ps-rows tr:last');
		$('#aaa-oc-ps-rows').append($last.prop('outerHTML'));
		let $new = $('#aaa-oc-ps-rows tr:last');

		// Bump row counter and update all [index] parts in name attributes.
		AAAOC_PS_ROW_COUNTER++;
		$new.find('[name]').each(function(){
			let n = $(this).attr('name');
			if (!n) { return; }
			n = n.replace(/\[\d+\]/, '[' + AAAOC_PS_ROW_COUNTER + ']');
			$(this).attr('name', n);
		});

		// Reset values.
		$new.find('input[type=text]').val('');
		$new.find('input[type=checkbox]').prop('checked', false);
		$new.find('select').val('');
		$new.find('.aaa-oc-ps-scope').val('brand').trigger('change');
	});

	$(document).on('click', '.aaa-oc-ps-del', function(e){
		e.preventDefault();
		let $rows = $('#aaa-oc-ps-rows tr');
		if ($rows.length > 1) {
			$(this).closest('tr').remove();
		}
	});

	function aaaOcPsUpdateRow($row, scope) {
		let $sel  = $row.find('.aaa-oc-ps-term-select');
		let $glob = $row.find('.aaa-oc-ps-term-global');

		$sel.find('option').hide();
		$sel.show();
		$glob.hide();

		if (scope === 'brand') {
			$sel.find('option[data-scope=brand]').show();
		} else if (scope === 'category') {
			$sel.find('option[data-scope=category]').show();
		} else if (scope === 'global') {
			$sel.val('');
			$sel.hide();
			$glob.show();
		}
	}

	$(document).on('change','.aaa-oc-ps-scope',function(){
		let scope = $(this).val();
		let $row  = $(this).closest('tr');
		aaaOcPsUpdateRow($row, scope);
	});

	// Initialize existing rows on load.
	$('#aaa-oc-ps-rows tr').each(function(){
		let $row  = $(this);
		let scope = $row.find('.aaa-oc-ps-scope').val();
		aaaOcPsUpdateRow($row, scope);
	});
})(jQuery);
</script>
