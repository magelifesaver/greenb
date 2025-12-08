<?php
/**
 * File: /wp-content/plugins/aaa-product-ai-manager/admin/pages/aaa-paim-run-report.php
 * Purpose: Admin page to view a single processing run (header + items table placeholder) — PAIM naming.
 * Version: 1.0.1
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Local per-file debug toggle.
 * Enable with: define('AAA_PAIM_RUN_REPORT_DEBUG', true);
 */
if ( ! defined( 'AAA_PAIM_RUN_REPORT_DEBUG' ) ) {
	define( 'AAA_PAIM_RUN_REPORT_DEBUG', true );
}

/**
 * Register menu page under the PAIM top-level menu (slug 'aaa-paim').
 */
add_action( 'admin_menu', function () {
	$parent_slug = 'aaa-paim'; // matches your loader's top-level menu slug
	add_submenu_page(
		$parent_slug,
		__( 'PAIM Run Report', 'aaa-paim' ),
		__( 'Run Report', 'aaa-paim' ),
		'manage_woocommerce',
		'aaa-paim-run',
		'aaa_paim_render_run_report_page',
		99
	);
}, 50);

/**
 * Render the Run Report page.
 */
function aaa_paim_render_run_report_page() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( esc_html__( 'Permission denied.', 'aaa-paim' ) );
	}

	$run_id = isset( $_GET['run_id'] ) ? intval( $_GET['run_id'] ) : 0;
	if ( $run_id <= 0 ) {
		echo '<div class="notice notice-error"><p>' . esc_html__( 'Missing run_id.', 'aaa-paim' ) . '</p></div>';
		return;
	}

	global $wpdb;
	$tbl_runs  = $wpdb->prefix . 'aaa_paim_runs';
	$tbl_items = $wpdb->prefix . 'aaa_paim_run_items';

	$run = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tbl_runs} WHERE run_id = %d", $run_id ), ARRAY_A );

	if ( ! $run ) {
		echo '<div class="notice notice-error"><p>' . esc_html__( 'Run not found.', 'aaa-paim' ) . '</p></div>';
		return;
	}

	$status_labels = array(
		'queued'   => __( 'Queued', 'aaa-paim' ),
		'running'  => __( 'Running', 'aaa-paim' ),
		'complete' => __( 'Complete', 'aaa-paim' ),
		'failed'   => __( 'Failed', 'aaa-paim' ),
		'canceled' => __( 'Canceled', 'aaa-paim' ),
	);
	$status = isset( $status_labels[ $run['status'] ] ) ? $status_labels[ $run['status'] ] : $run['status'];

	// Items (basic list; to be enhanced with diffs/filters/exports).
	$items = $wpdb->get_results(
		$wpdb->prepare( "SELECT * FROM {$tbl_items} WHERE run_id = %d ORDER BY item_id ASC LIMIT 200", $run_id ),
		ARRAY_A
	);

	$auto_refresh = in_array( $run['status'], array( 'queued', 'running' ), true );

	?>
	<div class="wrap">
		<h1><?php echo esc_html__( 'PAIM Run Report', 'aaa-paim' ); ?></h1>

		<p><a class="button" href="<?php echo esc_url( add_query_arg( array( 'page' => 'aaa-paim-run', 'run_id' => $run_id ), admin_url( 'admin.php' ) ) ); ?>">
			<?php esc_html_e( 'Refresh', 'aaa-paim' ); ?>
		</a></p>

		<table class="widefat striped">
			<tbody>
				<tr>
					<th style="width:220px;"><?php esc_html_e( 'Run ID', 'aaa-paim' ); ?></th>
					<td><?php echo esc_html( $run_id ); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Status', 'aaa-paim' ); ?></th>
					<td><?php echo esc_html( $status ); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Attribute Set ID', 'aaa-paim' ); ?></th>
					<td><?php echo esc_html( $run['attribute_set_id'] ); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Category Term ID', 'aaa-paim' ); ?></th>
					<td><?php echo esc_html( $run['category_term_id'] ); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Dry-run', 'aaa-paim' ); ?></th>
					<td><?php echo $run['dry_run'] ? esc_html__( 'Yes', 'aaa-paim' ) : esc_html__( 'No', 'aaa-paim' ); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Totals', 'aaa-paim' ); ?></th>
					<td>
						<?php
						printf(
							'%s: %d | %s: %d | %s: %d',
							esc_html__( 'Products', 'aaa-paim' ), intval( $run['total_products'] ),
							esc_html__( 'OK', 'aaa-paim' ),       intval( $run['processed_ok'] ),
							esc_html__( 'Errors', 'aaa-paim' ),    intval( $run['processed_err'] )
						);
						?>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'When', 'aaa-paim' ); ?></th>
					<td>
						<?php
						printf(
							'%s: %s | %s: %s | %s: %s',
							esc_html__( 'Requested', 'aaa-paim' ), esc_html( $run['requested_at'] ),
							esc_html__( 'Started', 'aaa-paim' ),   esc_html( $run['started_at'] ?: '—' ),
							esc_html__( 'Finished', 'aaa-paim' ),  esc_html( $run['finished_at'] ?: '—' )
						);
						?>
					</td>
				</tr>
			</tbody>
		</table>

		<h2 style="margin-top:24px;"><?php esc_html_e( 'Items', 'aaa-paim' ); ?></h2>

		<?php if ( empty( $items ) ) : ?>
			<p><?php esc_html_e( 'No items recorded yet. If this is a new run, items will appear once processing is implemented.', 'aaa-paim' ); ?></p>
		<?php else : ?>
			<table class="widefat fixed striped">
				<thead>
					<tr>
						<th style="width:90px;"><?php esc_html_e( 'Item ID', 'aaa-paim' ); ?></th>
						<th style="width:100px;"><?php esc_html_e( 'Product', 'aaa-paim' ); ?></th>
						<th><?php esc_html_e( 'Flags', 'aaa-paim' ); ?></th>
						<th><?php esc_html_e( 'Changes (JSON)', 'aaa-paim' ); ?></th>
						<th style="width:140px;"><?php esc_html_e( 'Processed At', 'aaa-paim' ); ?></th>
						<th style="width:200px;"><?php esc_html_e( 'Error', 'aaa-paim' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $items as $it ) : ?>
						<tr>
							<td><?php echo esc_html( $it['item_id'] ); ?></td>
							<td>
								<?php
								$pid = intval( $it['product_id'] );
								printf( '<a href="%s" target="_blank">#%d</a>', esc_url( get_edit_post_link( $pid, '' ) ), $pid );
								?>
							</td>
							<td><code><?php echo esc_html( $it['action_flags'] ); ?></code></td>
							<td style="max-width:540px; overflow:auto;"><pre style="white-space:pre-wrap;"><?php echo esc_html( (string) $it['changes_json'] ); ?></pre></td>
							<td><?php echo esc_html( $it['processed_at'] ?: '—' ); ?></td>
							<td><?php echo esc_html( $it['error_message'] ?: '' ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

	</div>

	<?php if ( $auto_refresh ) : ?>
	<script>
		// Simple auto-refresh while the run is queued/running (enhance later with AJAX polling).
		setTimeout(function(){ location.reload(); }, 5000);
	</script>
	<?php endif; ?>
	<?php
	if ( AAA_PAIM_RUN_REPORT_DEBUG ) {
		error_log( sprintf( '[PAIM][RunReport] Render run_id=%d status=%s', $run_id, $run['status'] ) );
	}
}
