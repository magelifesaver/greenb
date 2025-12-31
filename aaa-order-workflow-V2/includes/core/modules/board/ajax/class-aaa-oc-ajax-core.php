<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/core/modules/board/ajax/class-aaa-oc-ajax-core.php
 * Purpose: Full-row board feed grouped by status with column title + count (no sort button).
 * Notes:
 *  - ONLY uses the statuses in aaa_oc_enabled_statuses (scope: workflow).
 *  - Titles come from wc_get_order_statuses() (or prettified slug fallback).
 *  - Keeps SELECT * so ctx.oi contains every column.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'AAA_OC_Ajax_Core' ) ) :

final class AAA_OC_Ajax_Core {
	private const MAX_ROWS = 200;

	public static function boot(): void {
		add_action( 'wp_ajax_aaa_oc_get_latest_orders',        [ __CLASS__, 'get_latest_orders' ], 10 );
		add_action( 'wp_ajax_nopriv_aaa_oc_get_latest_orders', [ __CLASS__, 'get_latest_orders' ], 10 );
	}

	private static function verify_or_cap(): void {
		$nonce = $_REQUEST['_ajax_nonce'] ?? ( $_REQUEST['security'] ?? ( $_REQUEST['nonce'] ?? '' ) );
		if ( $nonce ) {
			if ( wp_verify_nonce( $nonce, 'aaa_oc_ajax_nonce' ) || wp_verify_nonce( $nonce, 'aaa_oc_nonce' ) ) {
				// ok
			} else {
				wp_send_json_error( [ 'message' => 'bad_nonce' ], 403 );
			}
		}
		if ( ! current_user_can('manage_woocommerce') && ! current_user_can('manage_options') ) {
			wp_send_json_error( [ 'message' => 'unauthorized' ], 403 );
		}
	}

	private static function get_enabled_status_slugs(): array {
		// Prefer our options helper (scoped), fall back to raw option if needed.
		if ( function_exists('aaa_oc_get_option') ) {
			$enabled = aaa_oc_get_option( 'aaa_oc_enabled_statuses', 'workflow', [] );
		} else {
			$enabled = get_option( 'aaa_oc_enabled_statuses', [] );
			if ( is_string( $enabled ) && is_serialized( $enabled ) ) $enabled = maybe_unserialize( $enabled );
		}
		if ( ! is_array( $enabled ) ) $enabled = [];

		// Normalize to slugs WITHOUT wc-
		return array_values( array_filter( array_map( static function( $s ) {
			$s = (string) $s;
			return ( strpos( $s, 'wc-' ) === 0 ) ? substr( $s, 3 ) : $s;
		}, $enabled ) ) );
	}

	private static function label_for_status( string $slug_no_wc ): string {
		$all = function_exists('wc_get_order_statuses') ? (array) wc_get_order_statuses() : [];
		$key = 'wc-' . $slug_no_wc;
		if ( isset( $all[ $key ] ) && is_string( $all[ $key ] ) ) {
			return $all[ $key ];
		}
		// Fallback: prettify slug (lkd-packed-ready → “Lkd Packed Ready”)
		return ucwords( str_replace( '-', ' ', $slug_no_wc ) );
	}

	public static function get_latest_orders(): void {
		self::verify_or_cap();

		global $wpdb;
		$tbl = $wpdb->prefix . 'aaa_oc_order_index';

		$sortMode = ( isset($_POST['sortMode']) && $_POST['sortMode'] === 'status' ) ? 'status' : 'published';
		$order_by = ( $sortMode === 'status' ) ? 'time_in_status DESC' : 'time_published DESC';

		$rows = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$tbl} ORDER BY {$order_by} LIMIT %d", self::MAX_ROWS ),
			ARRAY_A
		);
		if ( ! is_array( $rows ) ) $rows = [];

		$enabled_no_wc = self::get_enabled_status_slugs(); // strict — no hardcoded defaults

		// If nothing is enabled, return an empty shell with a hint.
		if ( empty( $enabled_no_wc ) ) {
			ob_start();
			echo '<div class="aaa-oc-columns-wrap" data-feed="fullrow-core-grouped">';
			echo '<div class="aaa-oc-column" data-status="none"><div class="aaa-oc-column-title">';
			echo esc_html__( 'No statuses enabled. Go to Workflow Settings → Board Status Visibility.', 'aaa-order-workflow' );
			echo '</div></div></div>';
			wp_send_json_success( [ 'columns_html' => (string) ob_get_clean() ] );
		}

		// Bucket rows by normalized status (only enabled buckets are created)
		$buckets = array_fill_keys( $enabled_no_wc, [] );
		foreach ( $rows as $r ) {
			$st = isset( $r['status'] ) ? strtolower( (string) $r['status'] ) : '';
			if ( strpos( $st, 'wc-' ) === 0 ) $st = substr( $st, 3 );
			if ( isset( $buckets[ $st ] ) ) {
				$buckets[ $st ][] = $r;
			}
		}

		ob_start();
		echo '<div class="aaa-oc-columns-wrap" data-feed="fullrow-core-grouped">';

		$print_col = function( string $slug, array $rows_in_col ) use ( $enabled_no_wc ) {
			$count = count( $rows_in_col );
			$cc    = ( $count < 10 ) ? 1 : ( ( $count < 30 ) ? 2 : 3 );

			$title = AAA_OC_Ajax_Core::label_for_status( $slug );

			$extra = ( $slug === 'completed' ) ? ' aaa-oc-completed-col' : '';
			echo '<div class="aaa-oc-column col-count-' . (int) $cc . ' status-' . esc_attr( $slug ) . $extra
			   . '" data-status="' . esc_attr( $slug ) . '" data-col="' . esc_attr( $slug ) . '">';

			echo '<div class="aaa-oc-column-title" style="display:flex;justify-content:space-between;text-transform:uppercase;align-items:center;">';
			echo '<span>' . esc_html( $title ) . ' <span class="aaa-oc-order-count">(' . (int) $count . ')</span></span>';
			if ( $slug === 'completed' ) {
				echo '<button class="button aaa-oc-toggle-completed" title="Hide/Show Completed">👁</button>';
			}
			echo '</div>';

			if ( class_exists( 'AAA_OC_Ajax_Cards' ) && $rows_in_col ) {
				foreach ( $rows_in_col as $r ) {
					echo AAA_OC_Ajax_Cards::build_order_card_html( (object) $r, $cc, $enabled_no_wc );
				}
			} else {
				echo '<p>' . esc_html__( 'No orders found in this status.', 'aaa-order-workflow' ) . '</p>';
			}

			echo '</div>';
		};

		// Render columns ONLY for enabled statuses, in the saved order.
		foreach ( $enabled_no_wc as $slug ) {
			$print_col( $slug, $buckets[ $slug ] ?? [] );
		}

		echo '</div>';
		wp_send_json_success( [ 'columns_html' => (string) ob_get_clean() ] );
	}
}
AAA_OC_Ajax_Core::boot();

else :
/* Legacy class already defined — provide a compatible closure using the same strict logic. */
add_action( 'wp_ajax_aaa_oc_get_latest_orders', function () {
	$nonce = $_REQUEST['_ajax_nonce'] ?? ( $_REQUEST['security'] ?? ( $_REQUEST['nonce'] ?? '' ) );
	if ( $nonce && ! ( wp_verify_nonce( $nonce, 'aaa_oc_ajax_nonce' ) || wp_verify_nonce( $nonce, 'aaa_oc_nonce' ) ) ) {
		wp_send_json_error( [ 'message' => 'bad_nonce' ], 403 );
	}
	if ( ! current_user_can('manage_woocommerce') && ! current_user_can('manage_options') ) {
		wp_send_json_error( [ 'message' => 'unauthorized' ], 403 );
	}

	global $wpdb;
	$tbl = $wpdb->prefix . 'aaa_oc_order_index';
	$rows = $wpdb->get_results( $wpdb->prepare("SELECT * FROM {$tbl} ORDER BY time_in_status DESC LIMIT %d", 200), ARRAY_A );
	if ( ! is_array( $rows ) ) $rows = [];

	$enabled_no_wc = [];
	if ( function_exists('aaa_oc_get_option') ) {
		$enabled_no_wc = (array) aaa_oc_get_option( 'aaa_oc_enabled_statuses', 'workflow', [] );
	} else {
		$enabled_no_wc = (array) get_option( 'aaa_oc_enabled_statuses', [] );
		if ( is_string( $enabled_no_wc ) && is_serialized( $enabled_no_wc ) ) $enabled_no_wc = maybe_unserialize( $enabled_no_wc );
	}
	$enabled_no_wc = array_values( array_filter( array_map( function( $s ){ return strpos($s,'wc-')===0 ? substr($s,3) : (string)$s; }, $enabled_no_wc ) ) );

	$buckets = array_fill_keys( $enabled_no_wc, [] );
	foreach ( $rows as $r ) {
		$st = isset($r['status']) ? strtolower((string)$r['status']) : '';
		if ( strpos($st,'wc-')===0 ) $st = substr($st,3);
		if ( isset($buckets[$st]) ) $buckets[$st][] = $r;
	}

	ob_start();
	echo '<div class="aaa-oc-columns-wrap" data-feed="fullrow-core-grouped">';
	$label = function($slug){
		$all = function_exists('wc_get_order_statuses') ? (array) wc_get_order_statuses() : [];
		return isset($all['wc-'.$slug]) ? $all['wc-'.$slug] : ucwords(str_replace('-',' ',$slug));
	};
	$print = function($slug,$list) use($enabled_no_wc,$label){
		$count = count($list); $cc = ($count<10)?1:(($count<30)?2:3);
		$title = $label($slug);
		$extra = ($slug==='completed')?' aaa-oc-completed-col':'';
		echo '<div class="aaa-oc-column col-count-'.(int)$cc.' status-'.esc_attr($slug).$extra.'" data-status="'.esc_attr($slug).'" data-col="'.esc_attr($slug).'">';
		echo '<div class="aaa-oc-column-title" style="display:flex;justify-content:space-between;text-transform:uppercase;align-items:center;">';
		echo '<span>'.esc_html($title).' <span class="aaa-oc-order-count">('.(int)$count.')</span></span>';
		if($slug==='completed'){ echo '<button class="button aaa-oc-toggle-completed" title="Hide/Show Completed">👁</button>'; }
		echo '</div>';
		if ( class_exists('AAA_OC_Ajax_Cards') && $list ){ foreach($list as $r){ echo AAA_OC_Ajax_Cards::build_order_card_html((object)$r,$cc,$enabled_no_wc); } }
		else { echo '<p>'.esc_html__('No orders found in this status.','aaa-order-workflow').'</p>'; }
		echo '</div>';
	};
	foreach($enabled_no_wc as $s){ $print($s, $buckets[$s]??[]); }
	echo '</div>';
	wp_send_json_success(['columns_html'=> (string)ob_get_clean()]);
}, -999);

endif;
