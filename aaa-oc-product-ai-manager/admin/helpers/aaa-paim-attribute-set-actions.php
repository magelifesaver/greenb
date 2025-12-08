<?php
/**
 * File: /wp-content/plugins/aaa-product-ai-manager/admin/helpers/aaa-paim-attribute-set-actions.php
 * Purpose: Render "Process" actions for Attribute Set rows (dry-run & live) — PAIM naming.
 * Version: 1.0.1
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Local per-file debug toggle (dev default true).
 * Enable with: define('AAA_PAIM_ATTR_ACTIONS_DEBUG', true);
 */
if ( ! defined( 'AAA_PAIM_ATTR_ACTIONS_DEBUG' ) ) {
	define( 'AAA_PAIM_ATTR_ACTIONS_DEBUG', true );
}

/**
 * Returns HTML for the Process buttons (Dry-run & Live).
 * Call this from your Attribute Set list's "Actions" column.
 *
 * @param int $set_id Attribute Set ID.
 * @param int $cat_id Optional product_cat term_id mapped to the set (0 = auto-resolve).
 * @return string HTML
 */
function aaa_paim_get_process_buttons_html( int $set_id, int $cat_id = 0 ) : string {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return '';
	}

	$nonce = wp_create_nonce( 'aaa_paim_process_set' );
	$base_args = array(
		'action' => 'aaa_paim_start_run',
		'set_id' => $set_id,
		'nonce'  => $nonce,
	);
	if ( $cat_id > 0 ) {
		$base_args['cat'] = $cat_id;
	}

	$dry_url  = add_query_arg( array_merge( $base_args, array( 'dry_run' => 1 ) ), admin_url( 'admin-post.php' ) );
	$live_url = add_query_arg( array_merge( $base_args, array( 'dry_run' => 0 ) ), admin_url( 'admin-post.php' ) );

	$html  = '<div class="aaa-paim-actions" style="display:flex; gap:6px; align-items:center; flex-wrap:wrap;">';
	$html .= sprintf(
		'<a class="button button-primary" href="%s" target="_blank" rel="noopener noreferrer" title="%s">%s</a>',
		esc_url( $dry_url ),
		esc_attr__( 'Create a dry-run and open the Run Report in a new tab', 'aaa-paim' ),
		esc_html__( 'Process (Dry-run)', 'aaa-paim' )
	);
	$html .= sprintf(
		'<a class="button" href="%s" target="_blank" rel="noopener noreferrer" title="%s">%s</a>',
		esc_url( $live_url ),
		esc_attr__( 'Process immediately (writes changes) and open the Run Report', 'aaa-paim' ),
		esc_html__( 'Process (Live)', 'aaa-paim' )
	);
	$html .= '</div>';

	if ( AAA_PAIM_ATTR_ACTIONS_DEBUG ) {
		error_log( sprintf( '[PAIM][AttrActions] Render buttons for set=%d cat=%d', $set_id, $cat_id ) );
	}

	return $html;
}
