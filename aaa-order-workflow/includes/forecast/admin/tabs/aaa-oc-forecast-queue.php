<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/forecast/admin/tabs/aaa-oc-forecast-queue.php
 * Purpose: View + manage the Forecast queue table.
 * Version: 0.1.1
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! current_user_can( 'manage_woocommerce' ) ) {
	wp_die( esc_html__( 'Insufficient permissions.', 'aaa-oc' ) );
}

global $wpdb;

$queue_table = defined( 'AAA_OC_FORECAST_QUEUE_TABLE' ) ? AAA_OC_FORECAST_QUEUE_TABLE : '';
$exists      = ( $queue_table && $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $queue_table ) ) === $queue_table );

$filter = isset( $_GET['queue_status'] ) ? sanitize_key( wp_unslash( $_GET['queue_status'] ) ) : 'pending';
if ( ! in_array( $filter, [ 'pending', 'processing', 'done', 'all' ], true ) ) { $filter = 'pending'; }

$counts = [ 'pending' => 0, 'processing' => 0, 'done' => 0 ];
$rows   = [];

if ( $exists ) {
	foreach ( array_keys( $counts ) as $st ) {
		$counts[ $st ] = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$queue_table} WHERE status = %s", $st ) );
	}

	if ( $filter === 'all' ) {
		$rows = $wpdb->get_results( "SELECT * FROM {$queue_table} ORDER BY id DESC LIMIT 200", ARRAY_A );
	} else {
		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$queue_table} WHERE status = %s ORDER BY id DESC LIMIT 200",
			$filter
		), ARRAY_A );
	}
}

$self = admin_url( 'admin.php?page=aaa-oc-core-settings&tab=aaa-oc-forecast-queue' );
?>
<div class="wrap">
	<h2><?php esc_html_e( 'Forecast Queue', 'aaa-oc' ); ?></h2>

	<?php if ( ! empty( $_GET['aaa_oc_forecast_repaired'] ) ) : ?>
		<div class="notice notice-success"><p><?php esc_html_e( 'Forecast tables repaired (dbDelta ran).', 'aaa-oc' ); ?></p></div>
	<?php endif; ?>

	<?php if ( ! empty( $_GET['aaa_oc_forecast_queue_scheduled'] ) ) : ?>
		<div class="notice notice-success"><p><?php esc_html_e( 'Queueing all enabled products has been scheduled.', 'aaa-oc' ); ?></p></div>
	<?php endif; ?>

	<?php if ( ! empty( $_GET['aaa_oc_forecast_process_scheduled'] ) ) : ?>
		<div class="notice notice-success"><p><?php esc_html_e( 'Queue processing has been scheduled to run in the background.', 'aaa-oc' ); ?></p></div>
	<?php endif; ?>

	<?php if ( ! $exists ) : ?>
		<div class="notice notice-error"><p><?php esc_html_e( 'Forecast queue table not found. Use "Repair Tables" below.', 'aaa-oc' ); ?></p></div>
	<?php endif; ?>

	<p>
		<strong><?php esc_html_e( 'Counts:', 'aaa-oc' ); ?></strong>
		<?php echo esc_html( 'Pending: ' . $counts['pending'] . ' | Processing: ' . $counts['processing'] . ' | Done: ' . $counts['done'] ); ?>
	</p>

	<p>
		<a class="button" href="<?php echo esc_url( add_query_arg( [ 'queue_status' => 'pending' ], $self ) ); ?>"><?php esc_html_e( 'Pending', 'aaa-oc' ); ?></a>
		<a class="button" href="<?php echo esc_url( add_query_arg( [ 'queue_status' => 'processing' ], $self ) ); ?>"><?php esc_html_e( 'Processing', 'aaa-oc' ); ?></a>
		<a class="button" href="<?php echo esc_url( add_query_arg( [ 'queue_status' => 'done' ], $self ) ); ?>"><?php esc_html_e( 'Done', 'aaa-oc' ); ?></a>
		<a class="button" href="<?php echo esc_url( add_query_arg( [ 'queue_status' => 'all' ], $self ) ); ?>"><?php esc_html_e( 'All', 'aaa-oc' ); ?></a>
	</p>

	<hr />

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;margin-right:12px;">
		<input type="hidden" name="action" value="aaa_oc_forecast_repair_queue_tables" />
		<?php wp_nonce_field( 'aaa_oc_forecast_repair_queue_tables' ); ?>
		<button type="submit" class="button"><?php esc_html_e( 'Repair Tables', 'aaa-oc' ); ?></button>
	</form>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;margin-right:12px;">
		<input type="hidden" name="action" value="aaa_oc_forecast_queue_all_enabled" />
		<?php wp_nonce_field( 'aaa_oc_forecast_queue_all_enabled' ); ?>
		<button type="submit" class="button"><?php esc_html_e( 'Queue All Enabled Products (Scheduled)', 'aaa-oc' ); ?></button>
	</form>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;">
		<input type="hidden" name="action" value="aaa_oc_forecast_process_queue_now" />
		<?php wp_nonce_field( 'aaa_oc_forecast_process_queue_now' ); ?>
		<button type="submit" class="button button-primary"><?php esc_html_e( 'Process Queue (Scheduled)', 'aaa-oc' ); ?></button>
	</form>

	<hr />

	<h3><?php echo esc_html( 'Queue Rows (' . $filter . ')', 'aaa-oc' ); ?></h3>

	<table class="widefat striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'ID', 'aaa-oc' ); ?></th>
				<th><?php esc_html_e( 'Product', 'aaa-oc' ); ?></th>
				<th><?php esc_html_e( 'Status', 'aaa-oc' ); ?></th>
				<th><?php esc_html_e( 'Attempts', 'aaa-oc' ); ?></th>
				<th><?php esc_html_e( 'Created', 'aaa-oc' ); ?></th>
				<th><?php esc_html_e( 'Updated', 'aaa-oc' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $rows ) ) : ?>
				<tr><td colspan="6"><?php esc_html_e( 'No rows found.', 'aaa-oc' ); ?></td></tr>
			<?php else : foreach ( $rows as $r ) : ?>
				<?php
					$pid   = absint( $r['product_id'] ?? 0 );
					$title = $pid ? get_the_title( $pid ) : '';
					$link  = $pid ? get_edit_post_link( $pid ) : '';
				?>
				<tr>
					<td><?php echo esc_html( absint( $r['id'] ?? 0 ) ); ?></td>
					<td>
						<?php if ( $link ) : ?>
							<a href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( $title ?: ( 'Product #' . $pid ) ); ?></a>
						<?php else : ?>
							<?php echo esc_html( $title ?: ( $pid ? ( 'Product #' . $pid ) : '-' ) ); ?>
						<?php endif; ?>
					</td>
					<td><?php echo esc_html( (string) ( $r['status'] ?? '' ) ); ?></td>
					<td><?php echo esc_html( absint( $r['attempts'] ?? 0 ) ); ?></td>
					<td><?php echo esc_html( (string) ( $r['created_at'] ?? '' ) ); ?></td>
					<td><?php echo esc_html( (string) ( $r['updated_at'] ?? '' ) ); ?></td>
				</tr>
			<?php endforeach; endif; ?>
		</tbody>
	</table>
</div>
