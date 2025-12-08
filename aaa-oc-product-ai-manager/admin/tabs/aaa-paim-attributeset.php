<?php
/**
 * File: /wp-content/plugins/aaa-product-ai-manager/admin/tabs/aaa-paim-attributeset.php
 * Purpose: Create & Edit Attribute Sets (choose category + attributes/taxonomies/meta; no values here).
 * Version: 0.3.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! defined( 'AAA_PAIM_DEBUG_TABSET' ) ) { define( 'AAA_PAIM_DEBUG_TABSET', true ); }

class AAA_Paim_Tab_Attribute_Set {

	public static function render() {
		$action = isset( $_GET['action'] ) ? sanitize_key( $_GET['action'] ) : '';
		if ( 'create' === $action ) {
			self::render_create_form();
			return;
		}
		if ( 'edit' === $action && ! empty( $_GET['set_id'] ) ) {
			self::render_edit_form( absint( $_GET['set_id'] ) );
			return;
		}
		self::maybe_handle_delete();
		self::render_list();
	}

	private static function render_list() {
		$sets = AAA_Paim_Sets::list_sets();
		$create_url = admin_url( 'admin.php?page=aaa-paim&tab=attributeset&action=create' );

		foreach ( [ 'created' => 'Attribute Set created.', 'updated' => 'Attribute Set updated.', 'deleted' => 'Attribute Set deleted.' ] as $q => $msg ) {
			if ( isset( $_GET[ $q ] ) ) echo '<div class="notice notice-success"><p>' . esc_html__( $msg, 'aaa-paim' ) . '</p></div>';
		}

		echo '<div class="aaa-paim-header"><a class="button button-primary" href="' . esc_url( $create_url ) . '">' . esc_html__( 'Create New Attribute Set', 'aaa-paim' ) . '</a></div>';

		echo '<table class="widefat striped">';
		echo '<thead><tr><th>' . esc_html__( 'ID', 'aaa-paim' ) . '</th><th>' . esc_html__( 'Name', 'aaa-paim' ) . '</th><th>' . esc_html__( 'Category', 'aaa-paim' ) . '</th><th>' . esc_html__( 'Attributes', 'aaa-paim' ) . '</th><th>' . esc_html__( 'Updated', 'aaa-paim' ) . '</th><th>' . esc_html__( 'Actions', 'aaa-paim' ) . '</th></tr></thead><tbody>';

		if ( empty( $sets ) ) {
			echo '<tr><td colspan="6">' . esc_html__( 'No attribute sets yet.', 'aaa-paim' ) . '</td></tr>';
		} else {
			foreach ( $sets as $s ) {
				$cat = get_term( (int) $s['category_term_id'], 'product_cat' );
				$edit_url = add_query_arg( [ 'page'=>'aaa-paim','tab'=>'attributeset','action'=>'edit','set_id'=>(int)$s['id'] ], admin_url( 'admin.php' ) );
				$del_url  = wp_nonce_url(
					add_query_arg( [ 'page' => 'aaa-paim', 'tab' => 'attributeset', 'delete' => (int) $s['id'] ], admin_url( 'admin.php' ) ),
					'aaa_paim_delete_set_' . (int) $s['id']
				);
				echo '<tr>';
				echo '<td>' . esc_html( $s['id'] ) . '</td>';
				echo '<td>' . esc_html( $s['set_name'] ) . '</td>';
				echo '<td>' . esc_html( $cat ? $cat->name : '—' ) . '</td>';
				echo '<td>' . esc_html( $s['attr_count'] ) . '</td>';
				echo '<td>' . esc_html( $s['updated_at'] ) . '</td>';
				echo '<td><a class="button" href="' . esc_url( $edit_url ) . '">' . esc_html__( 'Edit', 'aaa-paim' ) . '</a> &nbsp; <a class="button-link-delete" href="' . esc_url( $del_url ) . '">' . esc_html__( 'Delete', 'aaa-paim' ) . '</a></td>';
				echo '</tr>';
			}
		}
		echo '</tbody></table>';
	}

	/* ---------- Create ---------- */
	private static function render_create_form() {
		if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['aaa_paim_create_set'] ) ) {
			self::handle_create_submit();
			return;
		}
		self::render_form_common( 'create' );
	}

	private static function handle_create_submit() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) { wp_die( esc_html__( 'Permission denied', 'aaa-paim' ) ); }
		check_admin_referer( 'aaa_paim_create_set', 'aaa_paim_nonce' );

		$name   = isset( $_POST['set_name'] ) ? sanitize_text_field( wp_unslash( $_POST['set_name'] ) ) : '';
		$cat_id = isset( $_POST['category_term_id'] ) ? absint( $_POST['category_term_id'] ) : 0;

		$taxes  = array_map( 'sanitize_key', (array) ( $_POST['attrs_taxonomy'] ?? [] ) );
		$pubm   = array_map( 'sanitize_text_field', (array) ( $_POST['attrs_meta_public'] ?? [] ) );
		$privm  = array_map( 'sanitize_text_field', (array) ( $_POST['attrs_meta_private'] ?? [] ) );

		if ( empty( $name ) || $cat_id <= 0 ) { wp_die( esc_html__( 'Name and Category are required.', 'aaa-paim' ) ); }

		$items = [];
		foreach ( $taxes as $t ) { $items[] = [ 'type' => 'taxonomy', 'key' => $t, 'label' => $t ]; }
		foreach ( $pubm as $m )  { $items[] = [ 'type' => 'meta',     'key' => $m, 'label' => $m ]; }
		foreach ( $privm as $m ) { $items[] = [ 'type' => 'meta',     'key' => $m, 'label' => $m ]; }

		AAA_Paim_Sets::create_set( $name, $cat_id, $items );
		wp_safe_redirect( admin_url( 'admin.php?page=aaa-paim&tab=attributeset&created=1' ) );
		exit;
	}

	/* ---------- Edit ---------- */
	private static function render_edit_form( int $set_id ) {
		if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['aaa_paim_update_set'] ) ) {
			self::handle_edit_submit( $set_id );
			return;
		}
		$set = AAA_Paim_Sets::get_set( $set_id );
		if ( ! $set ) {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'Set not found.', 'aaa-paim' ) . '</p></div>';
			self::render_list();
			return;
		}
		$existing = AAA_Paim_Sets::get_set_items( $set_id );
		$checked  = [ 'taxonomy' => [], 'meta' => [] ];
		foreach ( $existing as $it ) { $checked[ $it['object_type'] ][] = $it['object_key']; }

		self::render_form_common( 'edit', $set, $checked );
	}

	private static function handle_edit_submit( int $set_id ) {
		if ( ! current_user_can( 'manage_woocommerce' ) ) { wp_die( esc_html__( 'Permission denied', 'aaa-paim' ) ); }
		check_admin_referer( 'aaa_paim_update_set_' . $set_id, 'aaa_paim_nonce' );

		$name   = isset( $_POST['set_name'] ) ? sanitize_text_field( wp_unslash( $_POST['set_name'] ) ) : '';
		$cat_id = isset( $_POST['category_term_id'] ) ? absint( $_POST['category_term_id'] ) : 0;

		$taxes  = array_map( 'sanitize_key', (array) ( $_POST['attrs_taxonomy'] ?? [] ) );
		$pubm   = array_map( 'sanitize_text_field', (array) ( $_POST['attrs_meta_public'] ?? [] ) );
		$privm  = array_map( 'sanitize_text_field', (array) ( $_POST['attrs_meta_private'] ?? [] ) );

		if ( empty( $name ) || $cat_id <= 0 ) { wp_die( esc_html__( 'Name and Category are required.', 'aaa-paim' ) ); }

		$items = [];
		foreach ( $taxes as $t ) { $items[] = [ 'type' => 'taxonomy', 'key' => $t, 'label' => $t ]; }
		foreach ( $pubm as $m )  { $items[] = [ 'type' => 'meta',     'key' => $m, 'label' => $m ]; }
		foreach ( $privm as $m ) { $items[] = [ 'type' => 'meta',     'key' => $m, 'label' => $m ]; }

		AAA_Paim_Sets::update_set( $set_id, $name, $cat_id, $items );
		wp_safe_redirect( admin_url( 'admin.php?page=aaa-paim&tab=attributeset&updated=1' ) );
		exit;
	}

	/* ---------- Shared form renderer ---------- */
	private static function render_form_common( string $mode, array $set = null, array $checked = null ) {
		$is_edit = ( 'edit' === $mode );
		$title   = $is_edit ? __( 'Edit Attribute Set', 'aaa-paim' ) : __( 'Create Attribute Set', 'aaa-paim' );
		$nonce_a = $is_edit ? 'aaa_paim_update_set_' . (int) $set['id'] : 'aaa_paim_create_set';

		$woo_attrs  = AAA_Paim_Attribute_Registry::woo_attribute_taxonomies();
		$other_tax  = AAA_Paim_Attribute_Registry::other_product_taxonomies();
		$meta_keys  = AAA_Paim_Attribute_Registry::discover_product_meta_keys();

		echo '<form method="post" action="">';
		wp_nonce_field( $nonce_a, 'aaa_paim_nonce' );

		echo '<h2>' . esc_html( $title ) . '</h2>';
		echo '<table class="form-table"><tbody>';

		echo '<tr><th><label for="set_name">' . esc_html__( 'Set Name', 'aaa-paim' ) . '</label></th><td>';
		echo '<input type="text" id="set_name" name="set_name" class="regular-text" required value="' . esc_attr( $is_edit ? $set['set_name'] : '' ) . '"/>';
		echo '</td></tr>';

		echo '<tr><th><label for="aaa-paim-category">' . esc_html__( 'Product Category', 'aaa-paim' ) . '</label></th><td>';
		$args = AAA_Paim_Attribute_Registry::product_category_dropdown_args();
		if ( $is_edit ) { $args['selected'] = (int) $set['category_term_id']; }
		wp_dropdown_categories( $args );
		echo '</td></tr>';

		echo '</tbody></table>';

		echo '<h3>' . esc_html__( 'Select Attributes / Taxonomies / Custom Fields', 'aaa-paim' ) . '</h3>';
		echo '<p>' . esc_html__( 'Tick everything you want included in this set. No default values are assigned here.', 'aaa-paim' ) . '</p>';

		self::render_checkbox_group( __( 'Woo Attributes', 'aaa-paim' ), 'attrs_taxonomy[]', $woo_attrs, $checked['taxonomy'] ?? [] );
		self::render_checkbox_group( __( 'Other Product Taxonomies', 'aaa-paim' ), 'attrs_taxonomy[]', $other_tax, $checked['taxonomy'] ?? [] );

		echo '<div class="aaa-paim-grid">';
		self::render_checkbox_group( __( 'Custom Fields (public)', 'aaa-paim' ), 'attrs_meta_public[]', $meta_keys['public'] ?? [], $checked['meta'] ?? [] );
		self::render_checkbox_group( __( 'Custom Fields (private)', 'aaa-paim' ), 'attrs_meta_private[]', $meta_keys['private'] ?? [], $checked['meta'] ?? [] );
		echo '</div>';

		echo '<p class="submit">';
		if ( $is_edit ) {
			echo '<button type="submit" class="button button-primary" name="aaa_paim_update_set" value="1">' . esc_html__( 'Save Changes', 'aaa-paim' ) . '</button> ';
			echo '<a class="button" href="' . esc_url( admin_url( 'admin.php?page=aaa-paim&tab=attributeset' ) ) . '">' . esc_html__( 'Back', 'aaa-paim' ) . '</a>';
		} else {
			echo '<button type="submit" class="button button-primary" name="aaa_paim_create_set" value="1">' . esc_html__( 'Save Attribute Set', 'aaa-paim' ) . '</button> ';
			echo '<a class="button" href="' . esc_url( admin_url( 'admin.php?page=aaa-paim&tab=attributeset' ) ) . '">' . esc_html__( 'Cancel', 'aaa-paim' ) . '</a>';
		}
		echo '</p>';

		echo '</form>';
	}

	private static function render_checkbox_group( string $title, string $name, array $options, array $checked_values = [] ) {
		echo '<div class="aaa-paim-box"><div class="aaa-paim-box-head">';
		echo '<strong>' . esc_html( $title ) . '</strong>';
		echo '<input type="text" class="aaa-paim-filter" placeholder="' . esc_attr__( 'Search…', 'aaa-paim' ) . '" /></div>';
		echo '<div class="aaa-paim-box-body">';
		if ( empty( $options ) ) {
			echo '<em>' . esc_html__( 'None found.', 'aaa-paim' ) . '</em>';
		} else {
			foreach ( $options as $key => $label ) {
				$id = esc_attr( $name . '_' . $key );
				$checked = in_array( $key, $checked_values, true ) ? ' checked' : '';
				echo '<label class="aaa-paim-item"><input type="checkbox" name="' . esc_attr( $name ) . '" value="' . esc_attr( $key ) . '"' . $checked . '> <span>' . esc_html( $label ) . ' <code>' . esc_html( $key ) . '</code></span></label>';
			}
		}
		echo '</div></div>';
	}

	private static function maybe_handle_delete() {
		if ( empty( $_GET['delete'] ) ) { return; }
		$set_id = absint( $_GET['delete'] );
		check_admin_referer( 'aaa_paim_delete_set_' . $set_id );
		if ( ! current_user_can( 'manage_woocommerce' ) ) { wp_die( esc_html__( 'Permission denied', 'aaa-paim' ) ); }
		AAA_Paim_Sets::delete_set( $set_id );
		wp_safe_redirect( admin_url( 'admin.php?page=aaa-paim&tab=attributeset&deleted=1' ) );
		exit;
	}
}
