<?php
/**
 * View for the ATUM Orders log
 *
 * @since 1.1.9
 *
 * @var array|object|null $order_logs
 */

defined( 'ABSPATH' ) || die;

use AtumLogs\Inc\Helpers;
use AtumLogs\Models\LogEntry;

if ( is_null( $order_logs ) ) :
	$order_logs = [];
elseif ( ! is_array( $order_logs ) ) :
	$order_logs = [ $order_logs ];
endif;
?>

<div class="atum-meta-box">

	<?php if ( 0 < count( $order_logs ) ) : ?>

		<div class="po-logs">
			<?php foreach ( $order_logs as $log ) :

				$user = get_user_by( 'id', $log->user_id );
				$name = FALSE === $user ? __( 'System', ATUM_LOGS_TEXT_DOMAIN ) : $user->display_name;

				?>
				<div class="atum-log-row">
					<div class="log-entry"><?php echo LogEntry::parse_text( stripslashes( $log->entry ), $log->data ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
					<div class="log-date"><?php echo esc_html( $name ) . ' - ' . Helpers::format_log_time( $log->time ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
				</div>
			<?php endforeach; ?>
		</div>

	<?php else : ?>
		<div class="no-items">
			<?php esc_html_e( 'No Logs Registered', ATUM_LOGS_TEXT_DOMAIN ); ?>
		</div>
	<?php endif; ?>

</div>
