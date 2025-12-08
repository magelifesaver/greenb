<?php
/**
 * File: /wp-content/plugins/aaa-product-ai-manager/admin/tabs/aaa-paim-products.php
 * Purpose: Choose existing or create new product, then apply Attribute Set; add new terms inline.
 * Version: 0.5.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! defined( 'AAA_PAIM_DEBUG_TABPROD' ) ) { define( 'AAA_PAIM_DEBUG_TABPROD', true ); }

class AAA_Paim_Tab_Products {

	public static function render() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Permission denied', 'aaa-paim' ) );
		}

		self::maybe_handle_create();
		self::maybe_handle_save();

		echo '<h2>' . esc_html__( 'Apply Attribute Set to Product', 'aaa-paim' ) . '</h2>';
		if ( isset( $_GET['saved'] ) ) {
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Saved product values and flags.', 'aaa-paim' ) . '</p></div>';
		}
		if ( isset( $_GET['created'] ) && isset( $_GET['product_id'] ) ) {
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Product created.', 'aaa-paim' ) . ' ' .
			     '<a href="' . esc_url( get_edit_post_link( absint( $_GET['product_id'] ) ) ) . '" target="_blank">' .
			     esc_html__( 'Edit in WooCommerce', 'aaa-paim' ) . '</a></p></div>';
		}
		if ( isset( $_GET['error'] ) ) {
			echo '<div class="notice notice-error"><p>' . esc_html( wp_kses_post( $_GET['error'] ) ) . '</p></div>';
		}

		self::render_selector_bar();

		$product_id = isset( $_GET['product_id'] ) ? absint( $_GET['product_id'] ) : 0;
		$set_id     = isset( $_GET['set_id'] ) ? absint( $_GET['set_id'] ) : 0;
		if ( $product_id ) { self::render_product_summary( $product_id ); }

		// If both chosen, render the fields
		if ( $product_id && $set_id ) {
			self::render_set_form( $product_id, $set_id );
		}
	}

	private static function render_selector_bar() {
		// Selector: Existing vs New
		$mode = isset( $_GET['mode'] ) ? sanitize_key( $_GET['mode'] ) : 'existing';

		echo '<div class="postbox" style="padding:12px;">';
		echo '<form method="get" action="">';
		echo '<input type="hidden" name="page" value="aaa-paim"><input type="hidden" name="tab" value="products">';
		echo '<input type="hidden" name="product_id" value="' . esc_attr( $product_id ) . '">';
		echo '<input type="hidden" name="set_id" value="' . esc_attr( $set_id ) . '">';
		echo '<input type="hidden" id="aaa-paim-nonce" value="' . esc_attr( wp_create_nonce( 'aaa_paim_nonce' ) ) . '">'; // NEW

		echo '<p><label><input type="radio" name="mode" value="existing" ' . checked( $mode, 'existing', false ) . '> ' . esc_html__( 'Existing Product', 'aaa-paim' ) . '</label> &nbsp; ';
		echo '<label><input type="radio" name="mode" value="new" ' . checked( $mode, 'new', false ) . '> ' . esc_html__( 'New Product', 'aaa-paim' ) . '</label></p>';

		if ( 'existing' === $mode ) {
			$product_id = isset( $_GET['product_id'] ) ? absint( $_GET['product_id'] ) : 0;
			echo '<table class="form-table"><tbody>';
			echo '<tr><th><label for="product_id">' . esc_html__( 'Product ID', 'aaa-paim' ) . '</label></th><td>';
			echo '<input type="number" min="1" class="small-text" name="product_id" id="product_id" value="' . esc_attr( $product_id ) . '"> ';
			if ( $product_id ) {
				$edit = get_edit_post_link( $product_id, '' );
				echo $edit ? '<a target="_blank" href="' . esc_url( $edit ) . '">' . esc_html__( 'Edit Product', 'aaa-paim' ) . '</a>' : '';
			}
			echo '</td></tr>';
			echo '</tbody></table>';
			self::render_set_dropdown();
			echo '<p><button class="button button-primary">' . esc_html__( 'Load', 'aaa-paim' ) . '</button></p>';
		} else {
			// New product mini-form
			echo '<h3>' . esc_html__( 'New Product', 'aaa-paim' ) . '</h3>';
			echo '<input type="hidden" name="mode" value="new">';
			echo '</form>'; // close GET
			echo '<form method="post" action="">';
			wp_nonce_field( 'aaa_paim_create_product', 'aaa_paim_nonce' );
			echo '<input type="hidden" name="page" value="aaa-paim"><input type="hidden" name="tab" value="products">';
			echo '<table class="form-table"><tbody>';
			echo '<tr><th>' . esc_html__( 'Name', 'aaa-paim' ) . '</th><td><input type="text" name="np[name]" class="regular-text" required></td></tr>';
			echo '<tr><th>' . esc_html__( 'SKU', 'aaa-paim' ) . '</th><td><input type="text" name="np[sku]" class="regular-text"></td></tr>';
			echo '<tr><th>' . esc_html__( 'Price', 'aaa-paim' ) . '</th><td><input type="number" step="0.01" name="np[price]" class="small-text"></td></tr>';
			echo '<tr><th>' . esc_html__( 'Sale Price', 'aaa-paim' ) . '</th><td><input type="number" step="0.01" name="np[sale_price]" class="small-text"></td></tr>';
			echo '<tr><th>' . esc_html__( 'COGS', 'aaa-paim' ) . '</th><td><input type="text" name="np[cogs]" class="small-text" placeholder="_cogs_total_value"></td></tr>';
			echo '<tr><th>' . esc_html__( 'Manage Stock', 'aaa-paim' ) . '</th><td><label><input type="checkbox" name="np[manage_stock]" value="1"> ';
			echo esc_html__( 'Enable stock management', 'aaa-paim' ) . '</label> ';
			echo '&nbsp; ' . esc_html__( 'Qty', 'aaa-paim' ) . ' <input type="number" name="np[stock_qty]" class="small-text" value="0"></td></tr>';
			echo '</tbody></table>';
			echo '<p><button class="button button-primary" name="aaa_paim_create_product" value="1">' . esc_html__( 'Create Product', 'aaa-paim' ) . '</button></p>';
			echo '</form>';
			echo '<form method="get" action=""><input type="hidden" name="page" value="aaa-paim"><input type="hidden" name="tab" value="products"><p><button class="button">' . esc_html__( 'Back', 'aaa-paim' ) . '</button></p></form>';
			echo '</div>';
			return;
		}

		echo '</form>';
		echo '</div>';
	}

	private static function render_set_dropdown() {
		global $wpdb;
		$sets_table = $wpdb->prefix . 'aaa_paim_attribute_sets';
		$sets = $wpdb->get_results( "SELECT id,set_name FROM {$sets_table} WHERE status='active' ORDER BY updated_at DESC LIMIT 200", ARRAY_A ) ?: [];
		$set_id = isset( $_GET['set_id'] ) ? absint( $_GET['set_id'] ) : 0;

		echo '<table class="form-table"><tbody>';
		echo '<tr><th><label for="set_id">' . esc_html__( 'Attribute Set', 'aaa-paim' ) . '</label></th><td>';
		echo '<select id="set_id" name="set_id" required><option value="">' . esc_html__( 'Select…', 'aaa-paim' ) . '</option>';
		foreach ( $sets as $s ) {
			$sel = selected( $set_id, (int) $s['id'], false );
			echo '<option value="' . esc_attr( $s['id'] ) . '"' . $sel . '>' . esc_html( $s['set_name'] ) . '</option>';
		}
		echo '</select></td></tr>';
		echo '</tbody></table>';
	}

		private static function render_product_summary( int $product_id ) {
		$p = get_post( $product_id );
		if ( ! $p || 'product' !== $p->post_type ) { return; }

		$title = get_the_title( $product_id );
		$edit  = get_edit_post_link( $product_id, '' );

		// Brand (berocket_brand) as names
		$brands    = wp_get_object_terms( $product_id, 'berocket_brand', [ 'fields' => 'names' ] );
		$brand_txt = ( ! is_wp_error( $brands ) && ! empty( $brands ) ) ? implode( ', ', $brands ) : '—';

		// Image: featured → Woo placeholder → WP default
		$img = get_the_post_thumbnail_url( $product_id, 'thumbnail' );
		if ( ! $img && function_exists( 'wc_placeholder_img_src' ) ) {
			$img = wc_placeholder_img_src( 'thumbnail' );
		}
		if ( ! $img ) { $img = includes_url( 'images/media/default.png' ); }

		echo '<div class="postbox" style="padding:12px; margin:0 0 12px;">';
		echo '  <div style="display:flex; gap:16px; align-items:center;">';
		echo '    <div style="flex:0 0 auto;"><img src="' . esc_url( $img ) . '" alt="" style="width:90px; height:90px; object-fit:cover; border:1px solid #ccd0d4; border-radius:4px;"></div>';
		echo '    <div style="flex:1 1 auto;">';
		echo '      <div style="font-size:16px; font-weight:600; margin-bottom:4px;">' . esc_html( $title ) . '</div>';
		if ( $edit ) {
			echo '      <div style="margin-bottom:6px;"><a href="' . esc_url( $edit ) . '" target="_blank">' . esc_html__( 'Edit Product', 'aaa-paim' ) . '</a></div>';
		}
		echo '      <div><strong>' . esc_html__( 'Brand:', 'aaa-paim' ) . '</strong> ' . esc_html( $brand_txt ) . '</div>';
		echo '    </div>';
		echo '  </div>';
		echo '</div>';
	}

private static function render_set_form( int $product_id, int $set_id ) {
		$items  = AAA_Paim_Product::get_set_items( $set_id );
		if ( empty( $items ) ) {
			echo '<div class="notice notice-warning"><p>' . esc_html__( 'This set has no attributes.', 'aaa-paim' ) . '</p></div>';
			return;
		}
		$values = AAA_Paim_Product::get_product_values( $product_id, $items );
		$flags  = AAA_Paim_Product::get_ai_flags( $product_id, $set_id );

		echo '<form method="post" action="">';
		wp_nonce_field( 'aaa_paim_save_prod', 'aaa_paim_nonce' );
		echo '<input type="hidden" name="page" value="aaa-paim"><input type="hidden" name="tab" value="products">';
		echo '<input type="hidden" name="product_id" value="' . esc_attr( $product_id ) . '">';
		echo '<input type="hidden" name="set_id" value="' . esc_attr( $set_id ) . '">';

		echo '<h3>' . esc_html__( 'Attributes in this Set', 'aaa-paim' ) . '</h3>';
		// Source URLs (brand site, Weedmaps, etc.)
//		$src_val = get_post_meta( $product_id, '_paim_source_urls', true );
//		echo '<div class="postbox" style="padding:12px;margin:12px 0;">';
//		echo '<h4>' . esc_html__( 'Source URLs (one per line)', 'aaa-paim' ) . '</h4>';
//		echo '<textarea name="source_urls" rows="4" style="width:100%;font-family:monospace;">' . esc_textarea( (string) $src_val ) . '</textarea>';
//		echo '<p class="description">' . esc_html__( 'Add brand page, Weedmaps product page, lab report, etc. The AI will only use these sources.', 'aaa-paim' ) . '</p>';
//		echo '</div>';

		echo '<p>' . esc_html__( 'Enter values for what you know. Check “AI request” for fields you want AI to fill later. To add new terms, use the input under a taxonomy.', 'aaa-paim' ) . '</p>';

		echo '<table class="widefat striped"><thead><tr>';
		echo '<th>' . esc_html__( 'Type', 'aaa-paim' ) . '</th>';
		echo '<th>' . esc_html__( 'Key', 'aaa-paim' ) . '</th>';
		echo '<th>' . esc_html__( 'Value', 'aaa-paim' ) . '</th>';
		echo '<th>' . esc_html__( 'AI request', 'aaa-paim' ) . '</th>';
		echo '</tr></thead><tbody>';

		foreach ( $items as $it ) {
			$type = $it['object_type'];
			$key  = $it['object_key'];
			$label = $it['label'] ?: $key;
			$ai_key = $type . ':' . $key;
			$ai_on  = ! empty( $flags[ $ai_key ] );

			echo '<tr>';
			echo '<td><code>' . esc_html( $type ) . '</code></td>';
			// --- Pull core defaults when the set doesn't specify kind/help ---
			$kind = $it['value_kind'] ?? null;
			$help = $it['help_text']  ?? null;

			if ( ! $kind && 'taxonomy' === $type ) {
				// Fallback to global attribute meta (saved under aaa_attr_meta_{attribute_id})
				$kind = class_exists('AAA_Paim_AttrMeta') ? AAA_Paim_AttrMeta::kind( $key, 'taxonomy' ) : 'taxonomy';
				if ( $help === null && class_exists('AAA_Paim_AttrMeta') ) {
					$help = AAA_Paim_AttrMeta::help( $key );
				}
			}
			if ( $help === null ) { $help = ''; }

			// --- Label cell (with optional help + numeric badge) ---
			echo '<td><strong>' . esc_html( $label ) . '</strong>';
			if ( $help !== '' ) {
				echo '<div style="opacity:.75;margin-top:2px;">' . esc_html( $help ) . '</div>';
			}
			echo '<div><code>' . esc_html( $key ) . '</code>';
			if ( $kind === 'number' ) {
				// #4: subtle badge to indicate numeric attribute
				echo ' <span class="dashicons dashicons-chart-line" title="Numeric attribute"></span>';
			}
			echo '</div></td>';

			// --- Value cell (render number input if numeric; otherwise taxonomy/select or plain text) ---
			echo '<td>';

			if ( $kind === 'number' ) {
				// NUMBER: use precision from set else core attribute meta
				$val = $values[ $key ] ?? '';
				$dec = null;

				if ( isset( $it['number_precision'] ) && is_numeric( $it['number_precision'] ) ) {
					$dec = (int) $it['number_precision'];
				} elseif ( class_exists('AAA_Paim_AttrMeta') ) {
					$pc = AAA_Paim_AttrMeta::precision( $key );
					$dec = ( is_numeric( $pc ) ? (int) $pc : null );
				}

				$step = 'any';
				if ( $dec !== null ) {
					$step = $dec > 0 ? '0.' . str_repeat('0', $dec - 1) . '1' : '1';
				}
				echo '<input type="number" step="' . esc_attr( $step ) . '" class="small-text" name="meta[' . esc_attr( $key ) . ']" value="' . esc_attr( is_scalar( $val ) ? $val : '' ) . '">';

			} elseif ( 'taxonomy' === $type ) {
				// TAXONOMY: multi-select + "New terms" input (unchanged)
				$all_terms = get_terms( [ 'taxonomy' => $key, 'hide_empty' => false ] );
				$current  = $values[ $key ] ?? [];
				echo '<select name="tax[' . esc_attr( $key ) . '][]" multiple size="6" style="min-width:280px">';
				if ( ! is_wp_error( $all_terms ) ) {
					foreach ( $all_terms as $t ) {
						$sel = in_array( (int) $t->term_id, (array) $current, true ) ? ' selected' : '';
						echo '<option value="' . esc_attr( $t->term_id ) . '"' . $sel . '>' . esc_html( $t->name ) . '</option>';
					}
				}
				echo '</select>';
				echo '<div style="margin-top:6px">';
				echo '<label>' . esc_html__( 'New terms (comma-separated):', 'aaa-paim' ) . ' ';
				echo '<input type="text" name="new_terms[' . esc_attr( $key ) . ']" class="regular-text" placeholder="e.g. Strawberry, Grape"></label>';
				echo '</div>';

			} else {
				// META (free text)
				$val = $values[ $key ] ?? '';
				echo '<input type="text" class="regular-text" name="meta[' . esc_attr( $key ) . ']" value="' . esc_attr( is_scalar( $val ) ? $val : '' ) . '">';
			}

			echo '</td>';
			echo '<td style="text-align:center">';
			echo '<label><input type="checkbox" name="ai[' . esc_attr( $ai_key ) . ']" value="1" ' . checked( $ai_on, true, false ) . '> ' . esc_html__( 'Request AI', 'aaa-paim' ) . '</label>';
			echo '</td>';
			echo '</tr>';
		}

		echo '</tbody></table>';
		echo '<p class="submit"><button class="button button-primary" name="aaa_paim_save_prod" value="1">' . esc_html__( 'Save', 'aaa-paim' ) . '</button></p>';
		// Run AI now (AJAX)
		echo '<p><button type="button" class="button" id="aaa-paim-run-ai" data-product="' . esc_attr( $product_id ) . '" data-set="' . esc_attr( $set_id ) . '">' . esc_html__( 'Run AI Now', 'aaa-paim' ) . '</button> <span id="aaa-paim-ai-result" style="margin-left:8px;"></span></p>';
		echo '</form>';
	}

	private static function maybe_handle_save() {
		if ( empty( $_POST['aaa_paim_save_prod'] ) ) { return; }
		check_admin_referer( 'aaa_paim_save_prod', 'aaa_paim_nonce' );
		if ( ! current_user_can( 'manage_woocommerce' ) ) { wp_die( esc_html__( 'Permission denied', 'aaa-paim' ) ); }

		$product_id = absint( $_POST['product_id'] ?? 0 );
		$set_id     = absint( $_POST['set_id'] ?? 0 );
		if ( ! $product_id || ! $set_id ) { wp_die( esc_html__( 'Missing product or set.', 'aaa-paim' ) ); }

		$tax       = (array) ( $_POST['tax']        ?? [] );
		$meta      = (array) ( $_POST['meta']       ?? [] );
		$ai        = (array) ( $_POST['ai']         ?? [] );
		$new_terms = (array) ( $_POST['new_terms']  ?? [] );
		$source_urls = isset( $_POST['source_urls'] ) ? (string) wp_unslash( $_POST['source_urls'] ) : '';
		update_post_meta( $product_id, '_paim_source_urls', $source_urls );

		$payload = [ 'tax' => $tax, 'meta' => self::clean_meta($meta), 'ai' => $ai, 'new_terms' => $new_terms ];
		$res = AAA_Paim_Product::save_submission( $product_id, $set_id, $payload );

		$args = [
		 'page'=>'aaa-paim','tab'=>'products','product_id'=>$product_id,'set_id'=>$set_id
		];
		if ( is_wp_error( $res ) ) {
			$args['error'] = rawurlencode( $res->get_error_message() );
		} else {
			$args['saved'] = 1;
		}
		wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
		exit;
	}

	private static function maybe_handle_create() {
		if ( empty( $_POST['aaa_paim_create_product'] ) ) { return; }
		check_admin_referer( 'aaa_paim_create_product', 'aaa_paim_nonce' );
		if ( ! current_user_can( 'manage_woocommerce' ) ) { wp_die( esc_html__( 'Permission denied', 'aaa-paim' ) ); }

		$np = (array) ( $_POST['np'] ?? [] );
		$res = AAA_Paim_Product::create_simple_product( $np );
		$args = [ 'page'=>'aaa-paim','tab'=>'products' ];
		if ( is_wp_error( $res ) ) {
			$args['mode']  = 'new';
			$args['error'] = rawurlencode( $res->get_error_message() );
		} else {
			$args['mode']       = 'existing';
			$args['product_id'] = (int) $res;
			$args['created']    = 1;
		}
		wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
		exit;
	}

	private static function clean_meta( array $meta ) : array {
		$out = [];
		foreach ( $meta as $k => $v ) {
			$out[ sanitize_key( $k ) ] = is_array( $v ) ? array_map( 'sanitize_text_field', $v ) : sanitize_text_field( $v );
		}
		return $out;
	}
}
