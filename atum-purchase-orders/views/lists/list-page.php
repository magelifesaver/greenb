<?php
/**
 * View for the POs List Table page
 *
 * @since 0.9.12
 *
 * @var string    $url
 * @var ListTable $list
 * @var string    $ajax
 * @var int       $late_pos_count
 */

defined( 'ABSPATH' ) || die;

use AtumPO\ListTables\Lists\ListTable;
use Atum\PurchaseOrders\PurchaseOrders;
use Atum\Inc\Helpers as AtumHelpers;

AtumHelpers::load_view( ATUM_PO_PATH . 'views/meta-boxes/comments/actions', array( 'is_editable' => TRUE ) );

?>
<div class="wrap">
	<h1 id="atum-pos-list-table-title" class="wp-heading-inline extend-list-table">
		<?php esc_html_e( 'Purchase Orders', ATUM_PO_TEXT_DOMAIN ) ?>

		<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . PurchaseOrders::POST_TYPE ) ) ?>" class="page-title-action"><?php esc_html_e( 'Add New PO', ATUM_PO_TEXT_DOMAIN ) ?></a>

		<?php if ( $late_pos_count > 0 ) : ?>
			<div class="late-po-banner">
				<i class="atum-icon atmi-warning"></i>
				<?php
				/* translators: the number of late POs */
				printf( esc_html__( 'Late Purchase Orders (%d)', ATUM_PO_TEXT_DOMAIN ), $late_pos_count ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<button type="button" class="btn btn-small btn-danger view-late-pos"><?php esc_html_e( 'View', ATUM_PO_TEXT_DOMAIN ); ?></button>
			</div>
		<?php endif; ?>
	</h1>

	<hr class="wp-header-end">

	<div class="atum-list-wrapper" data-list="<?php echo esc_attr( $list->get_id() ) ?>" data-action="atum_po_fetch_list"
	     data-screen="<?php echo esc_attr( $list->screen->id ) ?>"
	>
		<div class="list-table-header">

			<div id="scroll-po_list_nav" class="nav-container-box nav-mc">

				<nav id="pos_list_nav" class="nav-with-scroll-effect dragscroll">
					<?php $list->views(); ?>
					<div class="overflow-opacity-effect-right"></div>
					<div class="overflow-opacity-effect-left"></div>
				</nav>
			</div>

			<div class="search-box extend-list-table">
				<button type="button" class="reset-filters hidden tips" data-tip="<?php esc_attr_e( 'Reset Filters', ATUM_PO_TEXT_DOMAIN ) ?>"><i class="atum-icon atmi-undo"></i></button>

				<div class="input-group input-group-sm">
					<div class="input-group-append">
						<button class="search-column-btn btn btn-outline-secondary dropdown-toggle dropdown-toggle-mc tips" type="button"
							data-value="" title="<?php esc_html_e( 'Search in Column', ATUM_PO_TEXT_DOMAIN ) ?>"
							aria-haspopup="true" aria-expanded="false" data-toggle="dropdown"
						>
							<?php esc_html_e( 'Search In', ATUM_PO_TEXT_DOMAIN ) ?>
						</button>

						<div class="search-column-dropdown dropdown-menu" id="search_column_dropdown"
							data-no-option="<?php esc_attr_e( 'Search In', ATUM_PO_TEXT_DOMAIN ) ?>">
						</div>
					</div>

					<input type="search"
						class="form-control atum-post-search atum-post-search-mc atum-post-search-with-dropdown"
						data-value=""
						autocomplete="off" placeholder="<?php esc_attr_e( 'Search...', ATUM_PO_TEXT_DOMAIN ) ?>"/>

					<?php if ( 'no' === $ajax ) : ?>
						<input type="submit" class="button search-submit" value="<?php esc_attr_e( 'Search', ATUM_PO_TEXT_DOMAIN ) ?>">
					<?php endif; ?>

				</div>

			</div>
		</div>

		<?php $list->display(); ?>

	</div>
</div>
