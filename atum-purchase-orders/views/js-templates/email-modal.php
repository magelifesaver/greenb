<?php
/**
 * View for the PO Email Popup modal's JS template
 *
 * @since 0.9.0
 *
 * @var AtumPO\Models\POExtended $atum_order
 */

defined( 'ABSPATH' ) || die;

use AtumPO\Inc\Helpers;
use Atum\Components\AtumOrders\Models\AtumOrderModel;
use Atum\Inc\Helpers as AtumHelpers;

$templates = Helpers::get_po_email_templates();
$email_to  = '';
$email_cc  = apply_filters( 'atum/purchase_orders_pro/template_email_cc', '', $atum_order );
$email_bcc = apply_filters( 'atum/purchase_orders_pro/template_email_bcc', '', $atum_order );

if ( $atum_order instanceof AtumOrderModel && $atum_order->supplier ) :
	$supplier = $atum_order->get_supplier();
	$email_to = $supplier->ordering_email;
endif;

if ( ! $atum_order->email_template ) :
	$atum_order->set_email_template( 'default' );
endif;

?>
<template id="po-email-popup">

	<div class="atum-modal-content">

		<div class="po-modal__details">

			<div class="po-modal__details-field full-width">
				<label for="po-email-from"><?php esc_html_e( 'From', ATUM_PO_TEXT_DOMAIN ); ?></label>
				<input id="po-email-from" type="email" name="from" value="<?php echo esc_attr( apply_filters( 'atum/purchase_orders_pro/display_template_email_from', AtumHelpers::get_option( 'po_default_emails_sender', get_option( 'admin_email' ) ) ) ); ?>"/>
			</div>

			<div class="po-modal__details-field full-width">
				<label for="po-email-name-from"><?php esc_html_e( 'Name', ATUM_PO_TEXT_DOMAIN ); ?></label>
				<input id="po-email-name-from" type="email" name="name_from" value="<?php echo esc_attr( AtumHelpers::get_option( 'company_name', '' ) ); ?>"/>
			</div>

			<div class="po-modal__details-field full-width">
				<label for="po-email-to"><?php esc_html_e( 'To', ATUM_PO_TEXT_DOMAIN ); ?></label>
				<input id="po-email-to" type="email" name="to" value="<?php echo esc_attr( $email_to ); ?>"/>
			</div>

			<div class="po-modal__details-field full-width">
				<label for="po-email-cc"><?php esc_html_e( 'CC', ATUM_PO_TEXT_DOMAIN ); ?></label>
				<input id="po-email-cc" type="email" name="cc" multiple value="<?php echo esc_attr( $email_cc ); ?>"/>
			</div>

			<div class="po-modal__details-field full-width">
				<label for="po-email-bcc"><?php esc_html_e( 'BCC', ATUM_PO_TEXT_DOMAIN ); ?></label>
				<input id="po-email-bcc" type="email" name="bcc" multiple value="<?php echo esc_attr( $email_bcc ); ?>"/>
			</div>

			<div class="po-modal__details-field full-width">
				<label for="po-email-subject"><?php esc_html_e( 'Subject', ATUM_PO_TEXT_DOMAIN ); ?></label>
				<?php /* translators: the PO number */ ?>
				<input id="po-email-subject" type="text" name="subject" value="<?php echo esc_attr( sprintf( __( 'Purchase Order Request - #%s', ATUM_PO_TEXT_DOMAIN ), $atum_order->number ) ) ?>"/>
			</div>

			<div class="po-modal__details-field full-width po-email-attachments__field">
				<label for="po-email-attachments"><?php esc_html_e( 'Attachments', ATUM_PO_TEXT_DOMAIN ); ?></label>
				<div class="po-files-list po-field-disabled"><span class="po-attachment po-pdf-attachment">PO-<?php echo $atum_order->number; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>.pdf<span></span></span></div>
				<input id="po-email-attachments" class="po-files po-field-disabled" multiple type="file" name="attachments"/>
				<span id="po-email-attachments-button" class="btn btn-sm btn-primary po-email-target-selector"><span class="atum-icon atmi-plus-circle"></span></span>
			</div>

			<div class="po-modal__attachments-limit-warning">
				<small><span class="atum-icon atmi-warning"></span>
				<?php esc_html_e( 'The maximum size of attachments is limited to 10 MB.', ATUM_PO_TEXT_DOMAIN ); ?></small>
			</div>

			<?php $use_email_template = AtumHelpers::get_option( 'po_use_email_template', 'yes' ); ?>

			<?php if ( 'yes' === $use_email_template ) : ?>

				<div class="po-modal__details-field full-width po-email-template__field">
					<label for="po-email-template"><?php esc_html_e( 'Template', ATUM_PO_TEXT_DOMAIN ); ?></label>
					<input id="po-email-template" class="po-field-disabled" type="text" disabled="disabled" name="template"
						value="<?php echo esc_attr( array_key_exists( $atum_order->email_template, $templates ) ? $templates[ $atum_order->email_template ]['label'] : $atum_order->email_template ) ?>"/>
					<span id="po-email-template-button" class="btn btn-sm btn-primary po-email-target-selector"><span class="atum-icon atmi-plus-circle"></span></span>
				</div>

				<div class="popup-template-selector-wrapper" style="display: none;">
					<div class="atum-image-selector">
						<?php foreach ( $templates as $template_key => $template_data ) : ?>

							<?php $is_active = $atum_order->email_template === $template_key; ?>
							<label class="atum-image-radio<?php if ( $is_active ) echo ' active' ?>">
								<img src="<?php echo esc_url( $template_data['img_url'] ) ?>" alt="">
								<input type="radio" name="email_template" autocomplete="off"
									<?php checked( $is_active ) ?> value="<?php echo esc_attr( $template_key ) ?>">

								<span class="atum-image-radio__label">
									<?php echo esc_attr( $template_data['label'] ) ?>
								</span>
							</label>

						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>

			<div class="po-modal__preview-email">
				<form id="preview-email-form" action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ) ?>" method="post" target="_blank">
					<input type="hidden" name="action" value="atum_po_email_preview"/>
					<input type="hidden" name="template" value=""/>
					<input type="hidden" name="subject" value=""/>
					<input type="hidden" name="body" value="atum_po_email_preview"/>
					<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'po-email-preview' ) ) ?>"/>
					<input type="hidden" name="po_id" value="<?php echo esc_attr( $atum_order->get_id() ) ?>"/>

					<span class="btn btn-link" id="po_email_preview"><?php esc_html_e( 'Preview', ATUM_PO_TEXT_DOMAIN ); ?></span>
				</form>
			</div>
		</div>
	</div>

	<div id="wp-po-email-body-wrap">
		<?php
		$default_body = '
			<p>' . __( 'Dear Supplier,', ATUM_PO_TEXT_DOMAIN ) . '</p>
			<p>' . __( 'Please, find a new Purchase Order attached.', ATUM_PO_TEXT_DOMAIN ) . '<br>
			' . __( 'You can reply to this email when the order gets delivered.', ATUM_PO_TEXT_DOMAIN ) . '</p>
			<p>' . __( 'Thank you!', ATUM_PO_TEXT_DOMAIN ) . '</p>
		';

		wp_enqueue_editor(); // If the PO is not editable, the editor is not being enqueued for other fields.
		?>
		<textarea class="wp-editor" style="height: 224px;" autocomplete="off" cols="40" name="po-email-body"><?php echo wp_kses_post( AtumHelpers::get_option( 'po_default_emails_body', $default_body ) ) ?></textarea>
	</div>

</template>

