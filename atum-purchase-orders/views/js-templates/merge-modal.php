<?php
/**
 * View for the PO Email Popup modal's JS template
 *
 * @since 0.9.11
 *
 * @var AtumPO\Models\POExtended $atum_order
 */

defined( 'ABSPATH' ) || die;

use AtumPO\Inc\Helpers;
use Atum\Components\AtumOrders\Models\AtumOrderModel;
use Atum\Components\AtumHelpGuide;

$templates = Helpers::get_po_email_templates();
$email_to  = '';

if ( $atum_order instanceof AtumOrderModel && $atum_order->supplier ) :
	$supplier = $atum_order->get_supplier();
endif;

$settings = array(
	'info'       => __( 'PO Info', ATUM_PO_TEXT_DOMAIN ),
	'items'      => __( 'PO Items', ATUM_PO_TEXT_DOMAIN ),
	'deliveries' => __( 'PO Deliveries', ATUM_PO_TEXT_DOMAIN ),
	'invoices'   => __( 'PO Invoices', ATUM_PO_TEXT_DOMAIN ),
	'files'      => __( 'PO Files', ATUM_PO_TEXT_DOMAIN ),
	'comments'   => __( 'PO Comments', ATUM_PO_TEXT_DOMAIN ),
);

?>
<template id="po-merge-popup">

	<div class="atum-modal-content">

		<div class="note">
			<?php esc_html_e( 'Merge the purchase order you wish.', ATUM_PO_TEXT_DOMAIN ) ?>
		</div>

		<hr>

		<div class="po-modal__details po-merge-select-po">

			<h4><?php esc_html_e( 'Select PO', ATUM_PO_TEXT_DOMAIN ); ?></h4>

			<div class="po-modal__details-field full-width">
				<label for=""><?php esc_html_e( 'Select the purchase order you want to merge', ATUM_PO_TEXT_DOMAIN ); ?></label>

				<select id="search-po" name="search-po" data-allow-clear="true" data-minimum_input_length="1"
					data-placeholder="<?php esc_attr_e( 'Select purchase order', ATUM_PO_TEXT_DOMAIN ); ?>"
					data-action="atum_po_json_search_po" style="width:100%"
					data-nonce="<?php echo esc_attr( wp_create_nonce( 'search-po' ) ) ?>">
				</select>
			</div>
		</div>

		<hr>

		<div class="po-modal__details po-merge-settings-list">

			<h4><?php esc_html_e( 'Select Sections', ATUM_PO_TEXT_DOMAIN ); ?></h4>

			<span class="settings_note"><?php esc_html_e( 'Select the sections you want to merge.', ATUM_PO_TEXT_DOMAIN ); ?></span>

			<?php foreach ( $settings as $setting => $setting_name ) : ?>

			<div class="merge_setting form-switch">
				<label for="<?php echo esc_html( $setting ); ?>">
					<span><?php echo esc_html( $setting_name ); ?></span>
					<input type="checkbox" id="<?php echo esc_html( $setting ); ?>" name="<?php echo esc_html( $setting ); ?>" value="yes" checked="checked" class="form-check-input atum-settings-input">
				</label>
			</div>
			<?php endforeach; ?>

		</div>

		<hr>

		<div class="po-modal__details po-merge-replace-items">
			<div class="merge_setting form-switch">

				<label for="<?php echo esc_html( 'replace_items' ); ?>">
					<span>
						<?php esc_attr_e( 'Replace PO Items', ATUM_PO_TEXT_DOMAIN ); ?>
						<i class="atum-icon atmi-question-circle tips" data-tip="<?php esc_attr_e( 'Check this option to replace items instead of merge them.', ATUM_PO_TEXT_DOMAIN ); ?>" title=""></i>
					</span>
					<input type="checkbox" id="<?php echo esc_html( 'replace_items' ); ?>" name="<?php echo esc_html( 'replace_items' ); ?>" value="yes" class="form-check-input atum-settings-input">
				</label>
			</div>
		</div>


	</div>

</template>

