<?php
/**
 * File: /includes/core/options/admin/tabs/aaa-oc-workflow-settings.php
 * Purpose: "Workflow Settings" core tab for board visibility, polling, and utilities.
 * Version: 1.6.0
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// Enqueue sortable for status ordering UI
wp_enqueue_script( 'jquery-ui-sortable' );

// Load extender (for driver_id) so we can run it immediately on save if requested.
$extender_file = dirname( __DIR__ ) . '/index/class-aaa-oc-options-table-extender.php';
if ( file_exists( $extender_file ) ) {
	require_once $extender_file;
}

// --- Save handler ---
if ( isset( $_POST['aaa_oc_settings_submit'] ) && check_admin_referer( 'aaa_oc_settings_nonce' ) ) {

	// Enabled statuses (visibility)
	$enabled_statuses = isset( $_POST['aaa_oc_enabled_statuses'] ) ? (array) $_POST['aaa_oc_enabled_statuses'] : [];
	$enabled_statuses = array_map( 'sanitize_text_field', $enabled_statuses );

	// Polling options
	$show_countdown_bar = ! empty( $_POST['aaa_oc_show_countdown_bar'] ) ? 1 : 0;
	$disable_polling    = ! empty( $_POST['aaa_oc_disable_polling'] ) ? 1 : 0;

	// NEW: Add Driver ID to Index Table (order_index + payment_index)
	$add_driver_to_index = ! empty( $_POST['aaa_oc_add_driver_to_index'] ) ? 1 : 0;

	// NEW: Status order (sortable) — stored as ordered list of slugs
	$status_order = [];
	if ( ! empty( $_POST['aaa_oc_status_order'] ) ) {
		// Hidden input contains CSV in UI; accept array or CSV safely.
		if ( is_array( $_POST['aaa_oc_status_order'] ) ) {
			$status_order = array_map( 'sanitize_text_field', $_POST['aaa_oc_status_order'] );
		} else {
			$status_order = array_map( 'sanitize_text_field', array_filter( array_map( 'trim', explode( ',', (string) $_POST['aaa_oc_status_order'] ) ) ) );
		}
	}

	// Persist
	aaa_oc_set_option( 'aaa_oc_enabled_statuses',    $enabled_statuses,   'workflow' );
	aaa_oc_set_option( 'aaa_oc_show_countdown_bar',  $show_countdown_bar, 'workflow' );
	aaa_oc_set_option( 'aaa_oc_disable_polling',     $disable_polling,    'workflow' );
	aaa_oc_set_option( 'aaa_oc_add_driver_to_index', $add_driver_to_index,'workflow' );
	aaa_oc_set_option( 'aaa_oc_status_order',        $status_order,       'workflow' );

	// If admin opted-in, run the extender now (no watchdogs)
	if ( $add_driver_to_index && class_exists( 'AAA_OC_Options_Table_Extender' ) ) {
		AAA_OC_Options_Table_Extender::ensure_driver_columns();
	}

	echo '<div class="notice notice-success"><p>' . esc_html__( 'Workflow settings saved.', 'aaa-oc' ) . '</p></div>';
}

// --- Load current values ---
$all_wc_statuses   = wc_get_order_statuses(); // slug => label
$enabled_statuses  = aaa_oc_get_option( 'aaa_oc_enabled_statuses',    'workflow', [] );
$show_bar          = aaa_oc_get_option( 'aaa_oc_show_countdown_bar',  'workflow', 0 );
$disable_polling   = aaa_oc_get_option( 'aaa_oc_disable_polling',     'workflow', 0 );
$add_driver_opt    = aaa_oc_get_option( 'aaa_oc_add_driver_to_index', 'workflow', 0 );
$stored_order      = aaa_oc_get_option( 'aaa_oc_status_order',        'workflow', [] );

// Normalize status order list we will render:
// 1) put stored_order slugs first (in saved order) if they still exist,
// 2) append any other statuses not yet in stored_order (new WC versions or gateways).
$ordered_list = [];
foreach ( (array) $stored_order as $slug ) {
	if ( isset( $all_wc_statuses[ $slug ] ) ) $ordered_list[] = $slug;
}
foreach ( $all_wc_statuses as $slug => $_ ) {
	if ( ! in_array( $slug, $ordered_list, true ) ) $ordered_list[] = $slug;
}

// CSV for hidden field (sortable writes back here)
$ordered_csv = implode( ',', $ordered_list );
?>

<div class="wrap">
	<h2><?php esc_html_e( 'Workflow Settings', 'aaa-oc' ); ?></h2>
	<form method="post">
		<?php wp_nonce_field( 'aaa_oc_settings_nonce' ); ?>

		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Board Status Visibility', 'aaa-oc' ); ?></th>
				<td>
					<?php foreach ( $all_wc_statuses as $slug => $label ) : ?>
						<div style="margin-bottom:6px;">
							<label>
								<input type="checkbox"
								       name="aaa_oc_enabled_statuses[]"
								       value="<?php echo esc_attr( $slug ); ?>"
								       <?php checked( in_array( $slug, $enabled_statuses, true ) ); ?> />
								<?php echo esc_html( $label ) . ' (' . esc_html( $slug ) . ')'; ?>
							</label>
						</div>
					<?php endforeach; ?>
					<p class="description"><?php esc_html_e( 'Choose which statuses appear as columns/cards on the Workflow Board.', 'aaa-oc' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Order of Status Columns', 'aaa-oc' ); ?></th>
				<td>
					<input type="hidden" id="aaa_oc_status_order" name="aaa_oc_status_order" value="<?php echo esc_attr( $ordered_csv ); ?>" />
					<ul id="aaa-oc-status-sortable" style="margin:0;padding:0;list-style:none;max-width:480px;">
						<?php foreach ( $ordered_list as $slug ) : ?>
							<li class="aaa-oc-status-item" data-slug="<?php echo esc_attr( $slug ); ?>" style="margin:4px 0;padding:6px 10px;background:#fff;border:1px solid #ccd0d4;border-radius:6px;cursor:move;">
								<strong><?php echo esc_html( $all_wc_statuses[ $slug ] ?? $slug ); ?></strong>
								<code style="opacity:.7;margin-left:8px;"><?php echo esc_html( $slug ); ?></code>
							</li>
						<?php endforeach; ?>
					</ul>
					<p class="description"><?php esc_html_e( 'Drag to set the visual order of columns on the Workflow Board.', 'aaa-oc' ); ?></p>

					<script>
						jQuery(function($){
							var $list = $('#aaa-oc-status-sortable');
							var $hidden = $('#aaa_oc_status_order');
							if ($list.sortable) {
								$list.sortable({
									update: function(){
										var slugs = [];
										$list.find('.aaa-oc-status-item').each(function(){
											slugs.push($(this).data('slug'));
										});
										$hidden.val(slugs.join(','));
									}
								});
							}
						});
					</script>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Show Polling Countdown Bar', 'aaa-oc' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="aaa_oc_show_countdown_bar" value="1" <?php checked( $show_bar, 1 ); ?> />
						<?php esc_html_e( 'Display top countdown on the board page', 'aaa-oc' ); ?>
					</label>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Disable Polling (Development)', 'aaa-oc' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="aaa_oc_disable_polling" value="1" <?php checked( $disable_polling, 1 ); ?> />
						<?php esc_html_e( 'Stop board auto-refresh / polling', 'aaa-oc' ); ?>
					</label>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Add Driver ID to Index Table', 'aaa-oc' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="aaa_oc_add_driver_to_index" value="1" <?php checked( $add_driver_opt, 1 ); ?> />
						<?php esc_html_e( 'If enabled, adds a driver_id column to both order_index and payment_index (with indexes).', 'aaa-oc' ); ?>
					</label>
					<p class="description">
						<?php esc_html_e( 'Driver assignment is core to dispatch and payments; enabling this makes it first-class in your indices. Runs immediately on Save.', 'aaa-oc' ); ?>
					</p>
				</td>
			</tr>
		</table>

		<p class="submit">
			<button type="submit" name="aaa_oc_settings_submit" class="button button-primary">
				<?php esc_html_e( 'Save Settings', 'aaa-oc' ); ?>
			</button>
		</p>
	</form>
</div>
