<?php
/**
 * View for the Log Registry page
 *
 * @since 0.0.1
 *
 * @var string    $url
 * @var ListTable $list
 * @var string    $ajax
 */

defined( 'ABSPATH' ) || die;

use AtumLogs\LogRegistry\Lists\ListTable;

?>
<div class="wrap">
	<h1 id="atum-logs-list-table-title" class="wp-heading-inline extend-list-table">
		<?php esc_html_e( 'Action Logs', ATUM_LOGS_TEXT_DOMAIN ) ?>
		<span id="logs-unread-badge"><?php echo esc_attr( $list->get_unread_count() ); ?></span>
	</h1>

	<hr class="wp-header-end">

	<div class="atum-list-wrapper" data-list="<?php echo esc_attr( $list->get_id() ) ?>" data-action="atum_fetch_log_registry_list"
	     data-screen="<?php echo esc_attr( $list->screen->id ) ?>"
	>
		<div class="list-table-header">
			<div id="scroll-log_registry_nav" class="nav-container-box nav-mc">
				<div class="overflow-opacity-effect-right"></div>
				<div class="overflow-opacity-effect-left"></div>

				<nav id="log_registry_nav" class="nav-with-scroll-effect dragscroll">
					<?php $list->views(); ?>
				</nav>
			</div>

			<div class="search-box extend-list-table search-mc">

				<div class="input-group input-group-sm">
					<div class="input-group-append">
						<button class="search-column-btn btn btn-outline-secondary dropdown-toggle dropdown-toggle-mc tips"
							id="search_column_btn" type="button"
							data-value="" title="<?php esc_html_e( 'Search in Column', ATUM_LOGS_TEXT_DOMAIN ) ?>"
							aria-haspopup="true" aria-expanded="false" data-toggle="dropdown">
							<?php esc_html_e( 'Search In', ATUM_LOGS_TEXT_DOMAIN ) ?>
						</button>

						<div class="search-column-dropdown dropdown-menu" id="search_column_dropdown"
							data-no-option="<?php esc_attr_e( 'Search In', ATUM_LOGS_TEXT_DOMAIN ) ?>">
						</div>
					</div>
					<input type="search"
						class="form-control atum-post-search atum-post-search-mc atum-post-search-with-dropdown"
						data-value=""
						autocomplete="off" placeholder="<?php esc_attr_e( 'Search...', ATUM_LOGS_TEXT_DOMAIN ) ?>"/>

					<?php if ( 'no' === $ajax ) : ?>
						<input type="submit" class="button search-submit"
							value="<?php esc_attr_e( 'Search', ATUM_LOGS_TEXT_DOMAIN ) ?>">
					<?php endif; ?>

				</div>

			</div>
		</div>

		<?php $list->display(); ?>

	</div>
</div>
