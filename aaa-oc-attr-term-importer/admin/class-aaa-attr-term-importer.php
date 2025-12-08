<?php
/**
 * File: /wp-content/plugins/aaa-attr-term-importer/admin/class-aaa-attr-term-importer.php
 * Purpose: Tools → Attribute Term Importer (bulk create terms + aliases) and save attribute-level instructions/defaults
 * Version: 0.1.2
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class AAA_Attr_Term_Importer {
	public static function init() {
		add_action( 'admin_menu', [ __CLASS__, 'menu' ] );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue' ] );
	}

	public static function menu() {
		add_management_page(
			__( 'Attribute Term Importer', 'aaa-attr' ),
			__( 'Attribute Term Importer', 'aaa-attr' ),
			'manage_woocommerce',
			'aaa-attr-term-importer',
			[ __CLASS__, 'render' ]
		);
	}

	public static function enqueue( $hook ) {
		// Load JS only on our screen
		if ( 'tools_page_aaa-attr-term-importer' !== $hook ) { return; }
		$base_dir = dirname( __DIR__ );
		$js_rel   = '/assets/js/aaa-attr-term-importer.js';
		$js_file  = $base_dir . $js_rel;
		$ver      = '0.1.2';
		if ( file_exists( $js_file ) ) {
			$ver .= '.' . filemtime( $js_file );
		}
		wp_enqueue_script(
			'aaa-attr-term-importer',
			plugins_url( $js_rel, __DIR__ ),
			[ 'jquery' ],
			$ver,
			true
		);
	}

	private static function attrs_select_options(): array {
		$out = [];
		if ( function_exists( 'wc_get_attribute_taxonomies' ) ) {
			foreach ( wc_get_attribute_taxonomies() as $t ) {
				$slug = 'pa_' . $t->attribute_name;
				if ( taxonomy_exists( $slug ) ) {
					$out[ $slug ] = $t->attribute_label ?: $slug;
				}
			}
		}
		return $out;
	}

	public static function render() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Permission denied', 'aaa-attr' ) );
		}

		$messages = [];

		// Handle POST
		if ( 'POST' === $_SERVER['REQUEST_METHOD']
		     && ( isset( $_POST['aaa_attr_import'] ) || isset( $_POST['aaa_attr_save'] ) ) ) {

			check_admin_referer( 'aaa_attr_term_import', 'aaa_attr_nonce' );

			$taxonomy   = sanitize_key( $_POST['taxonomy'] ?? '' );
			$lines_raw  = (string) wp_unslash( $_POST['lines'] ?? '' );
			$skip_exist = ! empty( $_POST['skip_exist'] );
			$save_alias = ! empty( $_POST['save_alias'] );
			$title_case = ! empty( $_POST['title_case'] );
			$dry_run    = ! empty( $_POST['dry_run'] );

			// Attribute-level instructions & defaults
			$attr_help          = (string) wp_unslash( $_POST['attr_help'] ?? '' );
			$attr_is_number     = ! empty( $_POST['attr_is_number'] );
			$numeric_mode       = (bool) $attr_is_number;
			$attr_number_prec   = isset( $_POST['attr_number_precision'] ) && is_numeric( $_POST['attr_number_precision'] ) ? (int) $_POST['attr_number_precision'] : null;
			$attr_def_visible   = isset( $_POST['attr_default_visible'] ) ? 1 : 0;
			$attr_def_variation = isset( $_POST['attr_default_variation'] ) ? 1 : 0;

			$early_done = false; // show notices + form, but skip term import if true

			if ( ! taxonomy_exists( $taxonomy ) ) {
				$messages[] = [ 'error', __( 'Invalid taxonomy selected.', 'aaa-attr' ) ];
			} else {
				// Save attribute-level meta in wp_options: aaa_attr_meta_{attribute_id}
				if ( function_exists( 'wc_attribute_taxonomy_id_by_name' ) ) {
					$attr_id = (int) wc_attribute_taxonomy_id_by_name( $taxonomy );
					if ( $attr_id > 0 ) {
						$opt_key = 'aaa_attr_meta_' . $attr_id;
						$meta    = get_option( $opt_key, [] );
						if ( ! is_array( $meta ) ) { $meta = []; }

						if ( $attr_help !== '' ) {
							$meta['help_text'] = sanitize_text_field( $attr_help );
						}
						// Defaults and numeric kind
						$meta['value_kind']        = $attr_is_number ? 'number' : ( $meta['value_kind'] ?? 'taxonomy' );
						$meta['number_precision']  = $attr_is_number ? $attr_number_prec : null;
						$meta['default_visible']   = $attr_def_visible;
						$meta['default_variation'] = $attr_def_variation;

						update_option( $opt_key, $meta, false );
						$messages[] = [ 'updated', sprintf(
							/* translators: 1: value kind, 2: precision, 3: visible, 4: variation */
							__( 'Saved attribute instructions/defaults. Kind: %1$s, Precision: %2$s, Visible: %3$s, Variation: %4$s.', 'aaa-attr' ),
							( $meta['value_kind'] ?? 'taxonomy' ),
							( $meta['number_precision'] === null ? '—' : (string) $meta['number_precision'] ),
							( ! empty( $meta['default_visible'] ) ? 'yes' : 'no' ),
							( ! empty( $meta['default_variation'] ) ? 'yes' : 'no' )
						) ];
					}
				}

				// If user clicked "Save Attribute Settings", we won't run term import.
				if ( isset( $_POST['aaa_attr_save'] ) ) {
					$early_done = true;
				}

				// If numeric attribute, importing terms makes no sense; show info and skip import logic.
				if ( isset( $_POST['aaa_attr_import'] ) && $numeric_mode ) {
					$messages[] = [ 'notice-info', __( 'Attribute is marked numeric; term import skipped.', 'aaa-attr' ) ];
					$early_done = true;
				}

				if ( ! $early_done ) {
					$lines   = preg_split( '/\r\n|\r|\n/', $lines_raw );
					$created = 0;
					$updated = 0; // kept for summary format; we don't update term descriptions anymore
					$skipped = 0;
					$preview = [];

					// Preload existing term names for faster duplicate checks
					$existing_lc = [];
					$existing = get_terms( [ 'taxonomy' => $taxonomy, 'hide_empty' => false, 'fields' => 'all' ] );
					if ( ! is_wp_error( $existing ) ) {
						foreach ( $existing as $t ) {
							$existing_lc[ mb_strtolower( $t->name ) ] = (int) $t->term_id;
						}
					}

					foreach ( $lines as $ln ) {
						$ln = trim( $ln );
						if ( $ln === '' ) { continue; }

						// Parse: Name | Aliases (comma-separated)
						$parts = array_map( 'trim', explode( '|', $ln ) );
						$name  = $parts[0] ?? '';
						if ( $title_case && $name !== '' ) {
							$name = mb_convert_case( $name, MB_CASE_TITLE, 'UTF-8' );
						}
						$alias = $parts[1] ?? '';

						$needle  = mb_strtolower( $name );
						$term_id = $existing_lc[ $needle ] ?? 0;

						// Term exists
						if ( $term_id ) {
							if ( $skip_exist ) {
								$skipped++;
								$memo = 'exists';
							} else {
								$memo = 'exists';
							}
							// Save aliases if requested
							if ( $save_alias && $alias !== '' && ! $dry_run ) {
								$aliases = array_filter( array_map( 'trim', explode( ',', $alias ) ) );
								if ( $aliases ) {
									update_term_meta( $term_id, 'aaa_term_aliases', implode( ', ', $aliases ) );
									$memo = 'exists+aliases';
								}
							}
							$preview[] = [ 'action' => $memo, 'name' => $name ];
							continue;
						}

						// New term
						if ( $dry_run ) {
							$created++;
							$preview[] = [ 'action' => 'create', 'name' => $name, 'alias' => $alias ];
							continue;
						}

						$ins = wp_insert_term( $name, $taxonomy, [] ); // no per-term description; attribute-level help is above
						if ( is_wp_error( $ins ) ) {
							$preview[] = [ 'action' => 'error', 'name' => $name, 'msg' => $ins->get_error_message() ];
							continue;
						}
						$created++;
						$term_id = (int) $ins['term_id'];
						$existing_lc[ $needle ] = $term_id;

						if ( $save_alias && $alias !== '' ) {
							$aliases = array_filter( array_map( 'trim', explode( ',', $alias ) ) );
							if ( $aliases ) {
								update_term_meta( $term_id, 'aaa_term_aliases', implode( ', ', $aliases ) );
							}
						}
						$preview[] = [ 'action' => 'created', 'name' => $name ];
					}

					$messages[] = [ 'updated', sprintf(
						/* translators: 1: created, 2: updated, 3: skipped */
						__( 'Import complete. Created: %1$d, Updated: %2$d, Skipped: %3$d.', 'aaa-attr' ),
						$created, $updated, $skipped
					) ];
					if ( $dry_run ) {
						$messages[] = [ 'notice-info', __( 'Dry-run only. No changes were written.', 'aaa-attr' ) ];
					}
					// Preview list
					if ( ! empty( $preview ) ) {
						echo '<div class="notice notice-info"><p><strong>' . esc_html__( 'Preview', 'aaa-attr' ) . ':</strong></p><ul>';
						foreach ( $preview as $row ) {
							$txt = $row['action'] . ': ' . ( $row['name'] ?? '' );
							if ( ! empty( $row['msg'] ) ) { $txt .= ' — ' . $row['msg']; }
							echo '<li>' . esc_html( $txt ) . '</li>';
						}
						echo '</ul></div>';
					}
				}
			}
		}

		// Notices
		foreach ( $messages as $m ) {
			printf( '<div class="notice %1$s"><p>%2$s</p></div>',
				esc_attr( $m[0] ),
				esc_html( $m[1] )
			);
		}

		RENDER_FORM:
		// Form
		$attrs = self::attrs_select_options();

		echo '<div class="wrap"><h1>' . esc_html__( 'Attribute Term Importer', 'aaa-attr' ) . '</h1>';
		echo '<form method="post">';
		wp_nonce_field( 'aaa_attr_term_import', 'aaa_attr_nonce' );

		echo '<table class="form-table"><tbody>';

		// Attribute select
		echo '<tr><th><label for="taxonomy">' . esc_html__( 'Attribute', 'aaa-attr' ) . '</label></th><td>';
		echo '<select id="taxonomy" name="taxonomy" required>';
		echo '<option value="">' . esc_html__( 'Select…', 'aaa-attr' ) . '</option>';
		foreach ( $attrs as $tax => $label ) {
			echo '<option value="' . esc_attr( $tax ) . '">' . esc_html( $label ) . ' (' . esc_html( $tax ) . ')</option>';
		}
		echo '</select></td></tr>';

		// Attribute defaults
		echo '<tr><th>' . esc_html__( 'Attribute Defaults', 'aaa-attr' ) . '</th><td>';
		echo '<label style="display:block;margin-bottom:6px;"><input type="checkbox" name="attr_is_number" value="1"> ' .
		     esc_html__( 'Treat this attribute as numeric (store values as numbers)', 'aaa-attr' ) . '</label>';
		echo '<label style="display:block;margin-left:18px;margin-bottom:6px;">' .
		     esc_html__( 'Precision (decimals)', 'aaa-attr' ) . ' ' .
		     '<input type="number" name="attr_number_precision" class="small-text" min="0" max="6" value="2"></label>';
		echo '<label style="display:block;margin-bottom:6px;"><input type="checkbox" name="attr_default_visible" value="1" checked> ' .
		     esc_html__( 'Default: Visible on product page', 'aaa-attr' ) . '</label>';
		echo '<label style="display:block;"><input type="checkbox" name="attr_default_variation" value="0"> ' .
		     esc_html__( 'Default: Used for variations', 'aaa-attr' ) . '</label>';
		echo '<p class="description" style="margin-top:8px;">' .
		     esc_html__( 'Saved globally. Other plugins can honor these defaults; PAIM will by design.', 'aaa-attr' ) .
		     '</p>';
		echo '</td></tr>';

		// Attribute-level instructions/help (stored on the attribute, not terms)
		echo '<tr><th><label for="attr_help">' . esc_html__( 'Attribute Instructions (applies to this attribute)', 'aaa-attr' ) . '</label></th><td>';
		echo '<textarea id="attr_help" name="attr_help" rows="3" style="width:100%"></textarea>';
		echo '<p class="description">' . esc_html__( 'Guidance/description for this attribute (used in admin UIs and AI hints).', 'aaa-attr' ) . '</p>';
		echo '</td></tr>';

		// Lines textarea
		echo '<tr><th><label for="lines">' . esc_html__( 'Terms (one per line)', 'aaa-attr' ) . '</label></th><td>';
		echo '<textarea id="lines" name="lines" rows="12" style="width:100%;font-family:monospace" placeholder="Strain A | alias1, alias2&#10;Strain B&#10;…"></textarea>';
		echo '<p class="description">' . esc_html__( 'Format: Name | Aliases (aliases optional, comma-separated). Any middle “Description” is ignored; use the attribute instructions above.', 'aaa-attr' ) . '</p>';
		echo '<p id="aaa-attr-linecount" class="description"></p>';
		echo '</td></tr>';

		// Options
		echo '<tr><th>' . esc_html__( 'Options', 'aaa-attr' ) . '</th><td>';
		echo '<label><input type="checkbox" name="skip_exist" value="1" checked> ' . esc_html__( 'Skip existing terms', 'aaa-attr' ) . '</label><br>';
		echo '<label><input type="checkbox" name="save_alias" value="1"> ' . esc_html__( 'Save aliases to term meta', 'aaa-attr' ) . '</label><br>';
		echo '<label><input type="checkbox" name="title_case" value="1"> ' . esc_html__( 'Normalize to Title Case', 'aaa-attr' ) . '</label><br>';
		echo '<label><input type="checkbox" name="dry_run" value="1"> ' . esc_html__( 'Dry-run (preview only)', 'aaa-attr' ) . '</label>';
		echo '</td></tr>';

		echo '</tbody></table>';
		echo '<p class="submit">';
		echo '<button class="button" name="aaa_attr_save" value="1">' . esc_html__( 'Save Attribute Settings', 'aaa-attr' ) . '</button> ';
		echo '<button class="button button-primary" name="aaa_attr_import" value="1">' . esc_html__( 'Import Terms', 'aaa-attr' ) . '</button>';
		echo '</p>';
		echo '</form></div>';
	}
}
AAA_Attr_Term_Importer::init();
