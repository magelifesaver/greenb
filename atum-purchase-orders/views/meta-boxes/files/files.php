<?php
/**
 * View for the Purchase Order's Files meta box
 *
 * @since 0.9.0
 *
 * @var \AtumPO\Models\POExtended $po
 */

use Atum\Inc\Helpers as AtumHelpers;

$files          = $po->files && is_array( $po->files ) ? $po->files : [];
$supplier_files = wp_list_filter( $files, [ 'supplier' => 'yes' ] );
$our_files      = wp_list_filter( $files, [ 'supplier' => 'yes' ], 'NOT' );
?>
<div class="atum-meta-box po-files">

	<div class="po-files__wrapper">
		<div class="our-files<?php echo empty( $our_files ) ? ' no-items' : '' ?>">

			<h4><?php esc_html_e( 'Our Files', ATUM_PO_TEXT_DOMAIN ); ?></h4>

			<?php
			AtumHelpers::load_view( ATUM_PO_PATH . 'views/meta-boxes/files/files-table', [
				'files' => $our_files,
				'po'    => $po,
			] ); ?>

		</div>

		<div class="supplier-files<?php echo empty( $supplier_files ) ? ' no-items' : '' ?>">

			<h4><?php esc_html_e( 'Supplier Files', ATUM_PO_TEXT_DOMAIN ); ?></h4>

			<?php
			AtumHelpers::load_view( ATUM_PO_PATH . 'views/meta-boxes/files/files-table', [
				'files' => $supplier_files,
				'po'    => $po,
			] ); ?>

		</div>

	</div>

	<?php if ( $po->is_editable() ) : ?>
		<?php AtumHelpers::load_view( ATUM_PO_PATH . 'views/js-templates/add-file-modal' ); ?>
	<?php else : ?>
		<div class="atum-meta-box__footer">
			<span class="description">
				<span class="atum-help-tip atum-tooltip" title="<?php esc_attr_e( 'To edit the PO files change the PO status to any other that allows editing', ATUM_PO_TEXT_DOMAIN ) ?>"></span> <?php esc_html_e( 'These files are no longer editable.', ATUM_PO_TEXT_DOMAIN ); ?>
			</span>
		</div>
	<?php endif; ?>
</div>
