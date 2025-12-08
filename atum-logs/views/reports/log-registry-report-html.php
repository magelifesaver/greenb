<?php
/**
 * View for the Log Registry reports
 *
 * @since 0.4.1
 *
 * @var int    $max_columns
 * @var array  $count_views
 * @var string $report
 */

// mPDF does not support styling content within a <TD> through classes, so we need to add it inline.
$report_header_title_stl = 'font-weight: bold;text-transform: uppercase;font-size: 13px;';
$warning_color           = 'color: #FEC007;';
$title_color             = 'color: #333;';
?>
<style>
	table tr {
		display: table-row !important;
	}

</style>
<div class="atum-report">
	<h1><?php echo esc_html( apply_filters( 'atum/logs/report_title', __( 'ATUM Log Registry Report', ATUM_LOGS_TEXT_DOMAIN ) ) ) ?></h1>
	<h3><?php bloginfo( 'title' ) ?></h3>

	<table class="report-header">
		<tbody>
			<tr>

				<td class="report-data">
					<h5 style="<?php echo $report_header_title_stl . $title_color; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>"><?php esc_html_e( 'Report Data', ATUM_LOGS_TEXT_DOMAIN ) ?></h5><br>

					<p>
						<?php
						/* translators: the site title */
						printf( esc_html__( 'Site: %s', ATUM_LOGS_TEXT_DOMAIN ), esc_html( get_bloginfo( 'title' ) ) ) ?><br>
						<?php
						global $current_user;
						/* translators: the current user's display name */
						printf( esc_html__( 'Creator: %s', ATUM_LOGS_TEXT_DOMAIN ), esc_attr( $current_user->display_name ) ) ?><br>
						<?php
						/* translators: the current date */
						printf( esc_html__( 'Date: %s', ATUM_LOGS_TEXT_DOMAIN ), esc_attr( date_i18n( get_option( 'date_format' ) ) ) ) ?>
					</p>
				</td>

				<td class="report-details"></td>

				<td class="space"></td>

				<td class="inventory-resume">
					<h5 style="<?php echo $report_header_title_stl . $warning_color; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>"><?php esc_html_e( 'Logs Resume', ATUM_LOGS_TEXT_DOMAIN ) ?></h5><br>

					<p>
						<?php
						/* translators: total items */
						printf( esc_html( _n( 'Total: %d item', 'Total: %d items', $count_views['count_all'], ATUM_LOGS_TEXT_DOMAIN ) ), $count_views['count_all'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><br>
						<span style="color: #00B050;">
							<?php
							/* translators: number of featured items */
							printf( esc_html( _n( 'Featured: %d item', 'Featured: %d items', $count_views['count_featured'], ATUM_LOGS_TEXT_DOMAIN ) ), $count_views['count_featured'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</span><br>
						<?php
						/* translators: number of unread items */
						printf( esc_html( _n( 'Unread: %d item', 'Unread: %d items', $count_views['count_unread'], ATUM_LOGS_TEXT_DOMAIN ) ), $count_views['count_unread'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<br>
						<span style="color: #EF4D5A;">
						<?php
						/* translators: number of deleted items */
						printf( esc_html( _n( 'Deleted: %d item', 'Deleted: %d items', $count_views['count_deleted'], ATUM_LOGS_TEXT_DOMAIN ) ), $count_views['count_deleted'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</span><br>
					</p>
				</td>

			</tr>
		</tbody>
	</table>

	<?php echo $report; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</div>
