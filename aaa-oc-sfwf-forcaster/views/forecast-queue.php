<?php
/**
 * Filepath: sfwf/views/forecast-queue.php
 * ---------------------------------------------------------------------------
 * Admin UI: Forecast Queue
 */

if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can('manage_woocommerce') ) wp_die( 'Forbidden' );

$msg = isset($_GET['sfwf_msg']) ? sanitize_text_field( wp_unslash($_GET['sfwf_msg']) ) : '';

$base = admin_url( 'admin.php?page=sfwf-forecast-queue' );
$process_url = add_query_arg(
	array( 'sfwf_queue_action' => 'process', 'sfwf_nonce' => wp_create_nonce('sfwf_queue_process') ),
	$base
);
$sync_url = add_query_arg(
	array( 'sfwf_queue_action' => 'sync', 'sfwf_nonce' => wp_create_nonce('sfwf_queue_sync') ),
	$base
);
$clear_failed_url = add_query_arg(
	array( 'sfwf_queue_action' => 'clear_failed', 'sfwf_nonce' => wp_create_nonce('sfwf_queue_clear_failed') ),
	$base
);

$pending_count = WF_SFWF_Forecast_Queue::count('pending');
$failed_count  = WF_SFWF_Forecast_Queue::count('failed');

$pending = WF_SFWF_Forecast_Queue::list_items('pending', 200);
$failed  = WF_SFWF_Forecast_Queue::list_items('failed', 200);

function sfwf_queue_product_link( $product_id ) {
	$title = get_the_title( $product_id );
	if ( $title === '' ) $title = '(missing product #' . intval($product_id) . ')';
	$url = get_edit_post_link( $product_id, 'raw' );
	return $url ? '<a href="' . esc_url($url) . '">' . esc_html($title) . '</a>' : esc_html($title);
}
?>

<div class="wrap">
	<h1>Forecast Queue</h1>

	<?php if ( $msg !== '' ) : ?>
		<div class="notice notice-success"><p><?php echo esc_html($msg); ?></p></div>
	<?php endif; ?>

	<p>
		<strong>Pending:</strong> <?php echo esc_html($pending_count); ?> |
		<strong>Failed:</strong> <?php echo esc_html($failed_count); ?>
	</p>

	<p>
		<a class="button button-primary" href="<?php echo esc_url($process_url); ?>">Process next 25</a>
		<a class="button" href="<?php echo esc_url($sync_url); ?>">Sync queue from enabled products</a>
		<a class="button" href="<?php echo esc_url($clear_failed_url); ?>">Clear failed</a>
	</p>

	<h2>Pending (max 200 shown)</h2>
	<table class="widefat striped">
		<thead><tr>
			<th>Product</th><th>Status</th><th>Attempts</th><th>Queued</th><th>Updated</th>
		</tr></thead>
		<tbody>
		<?php if ( empty($pending) ) : ?>
			<tr><td colspan="5">No pending items.</td></tr>
		<?php else : foreach ( $pending as $row ) : ?>
			<tr>
				<td><?php echo sfwf_queue_product_link( absint($row['product_id']) ); ?></td>
				<td><?php echo esc_html($row['status']); ?></td>
				<td><?php echo esc_html($row['attempts']); ?></td>
				<td><?php echo esc_html($row['queued_at']); ?></td>
				<td><?php echo esc_html($row['updated_at']); ?></td>
			</tr>
		<?php endforeach; endif; ?>
		</tbody>
	</table>

	<h2>Failed (max 200 shown)</h2>
	<table class="widefat striped">
		<thead><tr>
			<th>Product</th><th>Attempts</th><th>Last error</th><th>Queued</th><th>Updated</th>
		</tr></thead>
		<tbody>
		<?php if ( empty($failed) ) : ?>
			<tr><td colspan="5">No failed items.</td></tr>
		<?php else : foreach ( $failed as $row ) : ?>
			<tr>
				<td><?php echo sfwf_queue_product_link( absint($row['product_id']) ); ?></td>
				<td><?php echo esc_html($row['attempts']); ?></td>
				<td><?php echo esc_html($row['last_error']); ?></td>
				<td><?php echo esc_html($row['queued_at']); ?></td>
				<td><?php echo esc_html($row['updated_at']); ?></td>
			</tr>
		<?php endforeach; endif; ?>
		</tbody>
	</table>
</div>
