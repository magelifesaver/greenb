<?php
/**
 * File: /wp-content/plugins/aaa-product-ai-manager/helpers/aaa-attr-instructions-view-extras.php
 * Purpose: Also show attribute settings (value_kind, number_precision, defaults) on Edit screens.
 * Version: 0.1.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! function_exists( 'aaa_attriv_current_attribute_id' ) ) {
	// Reuse the resolver if not loaded yet.
	function aaa_attriv_current_attribute_id() : int {
		if ( isset( $_GET['page'], $_GET['edit'] ) && $_GET['page'] === 'product_attributes' ) {
			return max( 0, (int) $_GET['edit'] );
		}
		if ( isset( $_GET['taxonomy'] ) ) {
			$tax = sanitize_key( $_GET['taxonomy'] );
			if ( 0 === strpos( $tax, 'pa_' ) && function_exists( 'wc_attribute_taxonomy_id_by_name' ) ) {
				$id = (int) wc_attribute_taxonomy_id_by_name( $tax );
				if ( $id <= 0 ) {
					$name = substr( $tax, 3 );
					$id   = (int) wc_attribute_taxonomy_id_by_name( $name );
				}
				return max( 0, $id );
			}
		}
		return 0;
	}
}

function aaa_attriv_get_meta_array( int $attr_id ) : array {
	$opt_key = 'aaa_attr_meta_' . $attr_id;
	$meta    = get_option( $opt_key, [] );
	return is_array( $meta ) ? $meta : [];
}

function aaa_attriv_render_settings_rows( array $meta ) : void {
	$kind   = isset($meta['value_kind']) ? (string) $meta['value_kind'] : 'taxonomy';
	$prec   = isset($meta['number_precision']) ? (string) $meta['number_precision'] : '';
	$vis    = isset($meta['default_visible']) ? (string) $meta['default_visible'] : '';
	$var    = isset($meta['default_variation']) ? (string) $meta['default_variation'] : '';

	echo '<table class="widefat striped" style="max-width:700px;margin-top:8px">';
	echo '<thead><tr><th colspan="2">Attribute Settings (read-only)</th></tr></thead><tbody>';
	echo '<tr><td style="width:220px"><strong>Value kind</strong></td><td>' . esc_html($kind) . '</td></tr>';
	echo '<tr><td><strong>Number precision</strong></td><td>' . esc_html($prec) . '</td></tr>';
	echo '<tr><td><strong>Default: Visible on product page</strong></td><td>' . esc_html($vis) . '</td></tr>';
	echo '<tr><td><strong>Default: Used for variations</strong></td><td>' . esc_html($var) . '</td></tr>';
	echo '</tbody></table>';
}

/** Global attribute editor */
add_action( 'woocommerce_after_edit_attribute_fields', function () {
	if ( ! current_user_can( 'manage_woocommerce' ) ) { return; }
	$attr_id = aaa_attriv_current_attribute_id();
	if ( $attr_id <= 0 ) { return; }
	$meta = aaa_attriv_get_meta_array( $attr_id );

	// Help text block (if you already render it elsewhere, you can omit this textarea)
	$help = isset($meta['help_text']) ? (string)$meta['help_text'] : '';
	echo '<tr class="form-field"><th scope="row"><label>Attribute Instructions</label></th><td>';
	echo '<textarea rows="5" cols="60" readonly="readonly">' . esc_textarea( $help ) . '</textarea>';
	echo '</td></tr>';

	// Settings block
	echo '<tr class="form-field"><th scope="row"><label>Settings</label></th><td>';
	aaa_attriv_render_settings_rows( $meta );
	echo '</td></tr>';
}, 12 );

/** Term editor (pa_* taxonomies) */
add_action( 'admin_init', function () {
	if ( ! function_exists( 'wc_get_attribute_taxonomies' ) ) { return; }
	foreach ( (array) wc_get_attribute_taxonomies() as $t ) {
		$slug = 'pa_' . $t->attribute_name;
		if ( ! taxonomy_exists( $slug ) ) { continue; }
		add_action( "{$slug}_edit_form_fields", function () {
			if ( ! current_user_can( 'manage_woocommerce' ) ) { return; }
			$attr_id = aaa_attriv_current_attribute_id();
			if ( $attr_id <= 0 ) { return; }
			$meta = aaa_attriv_get_meta_array( $attr_id );
			$help = isset($meta['help_text']) ? (string)$meta['help_text'] : '';
			?>
			<tr class="form-field">
				<th scope="row"><label>Attribute Instructions</label></th>
				<td><textarea rows="5" cols="60" readonly="readonly"><?php echo esc_textarea($help); ?></textarea></td>
			</tr>
			<tr class="form-field">
				<th scope="row"><label>Settings</label></th>
				<td><?php aaa_attriv_render_settings_rows( $meta ); ?></td>
			</tr>
			<?php
		}, 12, 2 );
	}
});
