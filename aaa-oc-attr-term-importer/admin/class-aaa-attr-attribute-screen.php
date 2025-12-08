<?php
/**
 * File: /wp-content/plugins/aaa-attr-term-importer/admin/class-aaa-attr-attribute-screen.php
 * Purpose: Add a compact panel to the WooCommerce Attribute edit screen to VIEW/EDIT:
 *          - Treat as numeric (value kind)
 *          - Precision (decimals)
 *          - Default flags (visible / used for variations)
 *          - Instructions/help (for admins & AI)
 * Also shows a status notice on the edit screen so you can verify saved state immediately.
 * Version: 0.1.1
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class AAA_Attr_Attribute_Screen {

	public static function init() {
		// Render extra fields under native WooCommerce attribute forms
		add_action( 'woocommerce_after_edit_attribute_fields', [ __CLASS__, 'render_edit_panel' ] );
		add_action( 'woocommerce_after_add_attribute_fields',  [ __CLASS__, 'render_add_hint' ] );

		// Persist attribute meta when an attribute is added/updated
		add_action( 'woocommerce_attribute_updated', [ __CLASS__, 'save_attr_meta_on_update' ], 10, 2 );
		add_action( 'woocommerce_attribute_added',   [ __CLASS__, 'save_attr_meta_on_add' ],     10, 2 );

		// Show a status notice on the attribute edit screen (Kind / Precision)
		add_action( 'current_screen', [ __CLASS__, 'maybe_hook_status_notice' ] );
	}

	/** Small hint on the "Add attribute" form */
	public static function render_add_hint() {
		echo '<div class="form-field"><p class="description">';
		echo esc_html__( 'After creating the attribute, you can edit its Numeric/Precision/Visibility defaults below.', 'aaa-attr' );
		echo '</p></div>';
	}

	/**
	 * Render the AAA panel on the Edit attribute screen.
	 * $attribute is an array with keys: id, name, label, slug, etc.
	 */
	public static function render_edit_panel( $attribute ) {
		if ( empty( $attribute['id'] ) ) { return; }
		$attr_id = (int) $attribute['id'];
		$opt_key = 'aaa_attr_meta_' . $attr_id;
		$meta    = get_option( $opt_key, [] );
		if ( ! is_array( $meta ) ) { $meta = []; }

		$kind       = $meta['value_kind']        ?? 'taxonomy';
		$precision  = $meta['number_precision']  ?? '';
		$visible    = (int) ( $meta['default_visible']   ?? 1 );
		$variation  = (int) ( $meta['default_variation'] ?? 0 );
		$help_text  = $meta['help_text']         ?? '';

		wp_nonce_field( 'aaa_attr_screen_save_' . $attr_id, 'aaa_attr_screen_nonce' );

		echo '<h3 style="margin-top:24px;">' . esc_html__( 'Attribute Defaults (AAA)', 'aaa-attr' ) . '</h3>';
		echo '<table class="form-table"><tbody>';

		// Numeric flag
		echo '<tr>';
		echo '<th scope="row">' . esc_html__( 'Treat as numeric', 'aaa-attr' ) . '</th>';
		echo '<td><label><input type="checkbox" name="aaa_attr_is_number" value="1" ' . checked( $kind === 'number', true, false ) . ' /> ';
		echo esc_html__( 'Store values as numbers (meta), not terms', 'aaa-attr' ) . '</label></td>';
		echo '</tr>';

		// Precision
		echo '<tr>';
		echo '<th scope="row">' . esc_html__( 'Precision (decimals)', 'aaa-attr' ) . '</th>';
		echo '<td><input type="number" class="small-text" name="aaa_attr_number_precision" min="0" max="6" value="' . esc_attr( $precision ) . '" /></td>';
		echo '</tr>';

		// Default flags
		echo '<tr>';
		echo '<th scope="row">' . esc_html__( 'Default flags', 'aaa-attr' ) . '</th>';
		echo '<td>';
		echo '<label style="margin-right:16px;"><input type="checkbox" name="aaa_attr_default_visible" value="1" ' . checked( $visible, 1, false ) . ' /> ' . esc_html__( 'Visible on product page', 'aaa-attr' ) . '</label>';
		echo '<label><input type="checkbox" name="aaa_attr_default_variation" value="1" ' . checked( $variation, 1, false ) . ' /> ' . esc_html__( 'Used for variations', 'aaa-attr' ) . '</label>';
		echo '</td>';
		echo '</tr>';

		// Help / instructions
		echo '<tr>';
		echo '<th scope="row">' . esc_html__( 'Instructions (help)', 'aaa-attr' ) . '</th>';
		echo '<td><textarea name="aaa_attr_help_text" rows="3" class="large-text" placeholder="' . esc_attr__( 'Guidance for admins / AI hints', 'aaa-attr' ) . '">' . esc_textarea( $help_text ) . '</textarea></td>';
		echo '</tr>';

		echo '</tbody></table>';

		// Current status
		$badge = ( $kind === 'number' ) ? 'Number' : 'Taxonomy';
		echo '<p><em>' . esc_html__( 'Current kind:', 'aaa-attr' ) . ' ' . esc_html( $badge ) . '</em></p>';
	}

	/** Hook from Woo: attribute updated */
	public static function save_attr_meta_on_update( $attribute_id, $data ) {
		self::save_attr_meta( (int) $attribute_id );
	}

	/** Hook from Woo: attribute added */
	public static function save_attr_meta_on_add( $attribute_id, $data ) {
		self::save_attr_meta( (int) $attribute_id );
	}

	/** Persist our meta in wp_options keyed by attribute id */
	private static function save_attr_meta( int $attr_id ) {
		if ( $attr_id <= 0 ) { return; }
		if ( ! isset( $_POST['aaa_attr_screen_nonce'] ) ||
		     ! wp_verify_nonce( $_POST['aaa_attr_screen_nonce'], 'aaa_attr_screen_save_' . $attr_id ) ) {
			return;
		}

		$opt_key = 'aaa_attr_meta_' . $attr_id;
		$meta    = get_option( $opt_key, [] );
		if ( ! is_array( $meta ) ) { $meta = []; }

		$is_number = ! empty( $_POST['aaa_attr_is_number'] );
		$prec      = ( isset( $_POST['aaa_attr_number_precision'] ) && is_numeric( $_POST['aaa_attr_number_precision'] ) )
			? (int) $_POST['aaa_attr_number_precision'] : null;
		$visible   = ! empty( $_POST['aaa_attr_default_visible'] ) ? 1 : 0;
		$variation = ! empty( $_POST['aaa_attr_default_variation'] ) ? 1 : 0;
		$help      = isset( $_POST['aaa_attr_help_text'] ) ? sanitize_text_field( wp_unslash( $_POST['aaa_attr_help_text'] ) ) : '';

		$meta['value_kind']        = $is_number ? 'number' : ( $meta['value_kind'] ?? 'taxonomy' );
		$meta['number_precision']  = $is_number ? $prec : null;
		$meta['default_visible']   = $visible;
		$meta['default_variation'] = $variation;
		$meta['help_text']         = $help;

		update_option( $opt_key, $meta, false );
	}

	/**
	 * Attach a one-time admin notice on the attribute edit screen to confirm saved state.
	 * Runs after screen is set; hooks admin_notices only when on ?page=product_attributes&action=edit&edit={id}
	 */
	public static function maybe_hook_status_notice( $screen ) {
		if ( empty( $screen ) || 'product_page_product_attributes' !== $screen->id ) { return; }
		$attr_id = isset( $_GET['edit'] ) ? (int) $_GET['edit'] : 0; // Woo uses ?edit=
		if ( $attr_id <= 0 ) { return; }

		$meta = get_option( 'aaa_attr_meta_' . $attr_id, [] );
		if ( ! is_array( $meta ) ) { $meta = []; }

		$kind = $meta['value_kind'] ?? 'taxonomy';
		$prec = ( array_key_exists( 'number_precision', $meta ) && $meta['number_precision'] !== null )
			? (string) $meta['number_precision'] : '—';

		add_action( 'admin_notices', function() use ( $kind, $prec ) {
			$cls = ( $kind === 'number' ) ? 'notice-success' : 'notice-info';
			echo '<div class="notice ' . esc_attr( $cls ) . '"><p><strong>'
			   . esc_html__( 'AAA Attribute Status:', 'aaa-attr' ) . '</strong> '
			   . esc_html__( 'Kind', 'aaa-attr' ) . ': ' . esc_html( $kind )
			   . ', ' . esc_html__( 'Precision', 'aaa-attr' ) . ': ' . esc_html( $prec ) . '.</p></div>';
		} );
	}
}
AAA_Attr_Attribute_Screen::init();
