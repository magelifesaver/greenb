<?php
/**
 * View for the PO data meta box
 *
 * @since 0.0.1
 *
 * @var \AtumPO\Models\POExtended $atum_order
 * @var \WP_Post                  $atum_order_post
 * @var array                     $labels
 * @var \Atum\Suppliers\Supplier  $supplier
 */

defined( 'ABSPATH' ) || die;

use AtumPO\Inc\Globals;
use AtumPO\Inc\Helpers;
use Atum\Inc\Helpers as AtumHelpers;
use AtumPO\Deliveries\DeliveryLocations;

global $pagenow;

$po_status     = 'post-new.php' === $pagenow ? 'atum_pending' : $atum_order->get_status();
$statuses      = Globals::get_statuses();
$status_colors = Globals::get_status_colors();
$status_color  = $status_colors[ $po_status ] ?? 'transparent';
$status_label  = $statuses[ $po_status ] ?? __( 'Unknown', ATUM_PO_TEXT_DOMAIN );
$is_editable   = $atum_order->is_editable() && ! $atum_order->is_returning();
$disabled_att  = disabled( $is_editable, FALSE, FALSE );
$unsaved       = 'auto-draft' === $atum_order->get_post()->post_status;
?>
<div id="po-data-meta-box" class="panel-wrap<?php echo esc_attr( ! $is_editable ? ' disabled-po' : '' ) ?><?php echo esc_attr( $unsaved ? ' unsaved' : '' ) ?>">

	<input name="post_title" type="hidden" value="<?php echo ( empty( $atum_order->name ) ? esc_attr__( 'Purchase Order', ATUM_PO_TEXT_DOMAIN ) : esc_attr( $atum_order->name ) ) ?>">
	<input name="post_status" type="hidden" value="<?php echo esc_attr( $po_status ) ?>">
	<input name="status" type="hidden" value="<?php echo esc_attr( $po_status ) ?>">
	<input type="hidden" id="atum_order_is_editable" value="<?php echo ( $atum_order->is_editable() ? 'true' : 'false' ) ?>">

	<?php require 'top-bar.php'; ?>

	<?php do_action( 'atum/purchase_orders/before_po_data_panel', $atum_order_post, $labels ); // Using original ATUM hook. ?>

	<div class="atum-meta-box panel">

		<div class="panel-column-container">

			<div class="panel-column supplier-info-panel">

				<h4>
					<?php esc_html_e( "Supplier's Info", ATUM_PO_TEXT_DOMAIN ) ?>
					<span class="panel-column__toggler"></span>
				</h4>

				<div class="panel-column__wrapper">

					<div class="supplier-image-wrapper">

						<div class="supplier-image">

							<?php if ( $supplier ) : ?>
								<a href="<?php echo esc_url( get_edit_post_link( $supplier->id ) ) ?>" target="_blank">
							<?php endif; ?>

								<?php if ( $supplier && $supplier->thumbnail_id ) : ?>
										<?php echo $supplier->get_thumb(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<?php else : ?>
									<?php echo AtumHelpers::get_atum_image_placeholder(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<?php endif; ?>

							<?php if ( $supplier ) : ?>
								</a>
							<?php endif; ?>

						</div>

						<div>

							<p class="form-field">
								<label for="supplier"><?php esc_html_e( 'Select Supplier', ATUM_PO_TEXT_DOMAIN ) ?></label>

								<select id="supplier" name="supplier" data-allow-clear="true" data-minimum_input_length="1"
									data-placeholder="<?php esc_attr_e( 'Search supplier&hellip;', ATUM_PO_TEXT_DOMAIN ); ?>"
									data-action="atum_po_json_search_suppliers" style="width:100%"
									data-nonce="<?php echo esc_attr( wp_create_nonce( 'search-suppliers' ) ) ?>"
									<?php echo $disabled_att; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								>
									<?php if ( $supplier ) : ?>
										<option value="<?php echo esc_attr( $supplier->id ) ?>"><?php echo esc_html( $supplier->name ) ?></option>
									<?php endif; ?>
								</select>
							</p>

							<p class="form-field">
								<label for="supplier_code"><?php esc_html_e( 'Supplier Code', ATUM_PO_TEXT_DOMAIN ) ?></label>

								<input type="text" name="supplier_code" id="supplier_code" value="<?php echo esc_attr( $atum_order->supplier_code ) ?>"
									<?php echo $disabled_att; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
							</p>

						</div>

					</div>

					<p class="form-field">
						<label for="supplier_reference"><?php esc_html_e( "Supplier's Reference", ATUM_PO_TEXT_DOMAIN ) ?></label>
						<input type="text" id="supplier_reference" name="supplier_reference" value="<?php echo esc_attr( $atum_order->supplier_reference ) ?>"
							<?php echo $disabled_att; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
					</p>

					<p class="form-field">
						<label for="supplier_discount"><?php esc_html_e( "Supplier's Discount", ATUM_PO_TEXT_DOMAIN ) ?></label>

						<span class="input-group number-mask">
							<input type="number" min="0" step="any" id="supplier_discount" name="supplier_discount" value="<?php echo esc_attr( $atum_order->supplier_discount ) ?>"
								<?php echo $disabled_att; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							>
							<span class="input-group-append">
								<span class="input-group-text active">%</span>
							</span>
						</span>
					</p>

					<p class="form-field">
						<label for="date_expected"><?php esc_html_e( 'Expected at Location Date', ATUM_PO_TEXT_DOMAIN ) ?></label>

						<span class="date-field with-icon<?php echo esc_attr( ! $is_editable ? ' disabled' : '' ) ?>">
							<input type="text" class="atum-datepicker" name="date_expected" id="date_expected" maxlength="10"
								value="<?php echo esc_attr( $atum_order->date_expected ? date_i18n( 'Y-m-d H:i', strtotime( $atum_order->date_expected ) ) : '' ) ?>"
								autocomplete="off" data-date-format="YYYY-MM-DD HH:mm"<?php echo $disabled_att; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
						</span>
					</p>

					<?php if ( Helpers::may_use_po_taxes( $atum_order ) ) : ?>
					<p class="form-field">
						<label for="supplier_tax_rate"><?php esc_html_e( 'Tax Rate', ATUM_PO_TEXT_DOMAIN ) ?></label>

						<span class="input-group number-mask">

							<?php
							// When creating a new PO, auto-fill the default tax rate to the PO.
							$tax = $atum_order->supplier_tax_rate;
							if ( 'yes' === AtumHelpers::get_option( 'po_use_system_taxes', 'yes' ) && 'post-new.php' === $pagenow ) :
								$tax_rates = \WC_Tax::get_base_tax_rates();

								if ( ! empty( $tax_rates ) ) :
									$tax = current( $tax_rates )['rate'] ?? 0;
								endif;
							endif; ?>
							<input type="number" min="0" step="any" id="supplier_tax_rate" name="supplier_tax_rate" value="<?php echo esc_attr( $tax ) ?>"
								<?php echo $disabled_att; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							>
							<span class="input-group-append">
								<span class="input-group-text active">%</span>
							</span>
						</span>
					</p>
					<?php endif; ?>

					<p class="form-field">
						<label for="supplier_currency"><?php esc_html_e( 'Currency', ATUM_PO_TEXT_DOMAIN ) ?></label>

						<span class="supplier-currency__wrapper">

							<select id="supplier_currency" name="supplier_currency" style="width:100%;"
								data-placeholder="<?php esc_attr_e( 'Choose currency&hellip;', ATUM_PO_TEXT_DOMAIN ); ?>"
								class="wc-enhanced-select atum-enhanced-select"<?php echo $disabled_att; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							>

								<?php
								$currency    = $atum_order->currency;
								$wc_currency = get_woocommerce_currency();
								$currency    = $currency ?: $wc_currency; // Fallback to the WC currency.
								foreach ( get_woocommerce_currencies() as $code => $name ) : ?>

									<?php
									$currency_symbol = get_woocommerce_currency_symbol( $code );
									/* translators: the first one is the currency name and the second is the currency code */
									$currency_label = sprintf( esc_html__( '%1$s (%2$s)', ATUM_PO_TEXT_DOMAIN ), $name, $currency_symbol );
									?>
									<option value="<?php echo esc_attr( $code ) ?>"<?php selected( $code, $currency ) ?>
										data-symbol="<?php echo esc_attr( $currency_symbol ) ?>"
									><?php echo $currency_label; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></option>
								<?php endforeach; ?>

							</select>

							<?php if ( $is_editable ) : ?>
							<span class="currency-options__wrapper atum-tooltip" data-bs-placement="bottom" title="<?php esc_attr_e( 'Select the currency options', ATUM_PO_TEXT_DOMAIN ); ?>">
								<button type="button" class="currency-options atum-edit-field btn btn-default"
									title="<?php esc_attr_e( 'Currency options', ATUM_PO_TEXT_DOMAIN ); ?>"
									data-content-id="currency-options-tmpl" data-bs-custom-class="currency-options-popover"
								>
									<i class="atum-icon atmi-drag"></i>
								</button>

								<input type="hidden" name="currency_pos" value="<?php echo esc_attr( $atum_order->currency_pos ) ?>">
								<input type="hidden" name="price_thousand_sep" value="<?php echo esc_attr( $atum_order->price_thousand_sep ) ?>">
								<input type="hidden" name="price_decimal_sep" value="<?php echo esc_attr( $atum_order->price_decimal_sep ) ?>">
								<input type="hidden" name="price_num_decimals" value="<?php echo esc_attr( $atum_order->price_num_decimals ) ?>">
								<input type="hidden" name="exchange_rate" value="<?php echo esc_attr( $atum_order->exchange_rate ) ?>">

								<template id="currency-options-tmpl">
									<label for="meta-value-currency-pos"><?php esc_html_e( 'Currency position', ATUM_PO_TEXT_DOMAIN ); ?></label>
									<select name="meta-value-currency-pos" id="meta-value-currency-pos" class="meta-value" data-input="currency_pos">
										<?php
										foreach ( [
											'left'        => __( 'Left', ATUM_PO_TEXT_DOMAIN ),
											'right'       => __( 'Right', ATUM_PO_TEXT_DOMAIN ),
											'left_space'  => __( 'Left with space', ATUM_PO_TEXT_DOMAIN ),
											'right_space' => __( 'Right with space', ATUM_PO_TEXT_DOMAIN ),
										] as $position => $label ) : ?>
											<option value="<?php echo esc_attr( $position ) ?>"<?php selected( $atum_order->currency_pos, $position ) ?>>
												<?php echo esc_html( $label ) ?>
											</option>
										<?php endforeach; ?>
									</select>

									<label for="meta-value-price-thousand-sep"><?php esc_html_e( 'Thousand separator', ATUM_PO_TEXT_DOMAIN ); ?></label>
									<input type="text" name="meta-value-price-thousand-sep" id="meta-value-price-thousand-sep" class="meta-value"
										value="<?php echo esc_attr( $atum_order->price_thousand_sep ) ?>" data-input="price_thousand_sep">

									<label for="meta-value-price-decimal-sep"><?php esc_html_e( 'Decimal separator', ATUM_PO_TEXT_DOMAIN ); ?></label>
									<input type="text" name="meta-value-price-decimal-sep" id="meta-value-price-decimal-sep" class="meta-value"
										value="<?php echo esc_attr( $atum_order->price_decimal_sep ) ?>" data-input="price_decimal_sep">

									<label for="meta-value-price-num-decimals"><?php esc_html_e( 'Number of decimals', ATUM_PO_TEXT_DOMAIN ); ?></label>
									<input type="number" name="meta-value-price-num-decimals" id="meta-value-price-num-decimals" min="0" max="5" class="meta-value"
										step="1" value="<?php echo esc_attr( $atum_order->price_num_decimals ) ?>" data-input="price_num_decimals">

									<div class="currency-conversion"<?php echo esc_attr( $wc_currency === $currency ? ' style="display:none"' : '' ) ?>>
										<span class="currency-conversion__label">
											<?php esc_html_e( 'Set exchange rate', ATUM_PO_TEXT_DOMAIN ) ?>
											<span class="atum-help-tip atum-tooltip" title="<?php esc_html_e( "The exchange rate is always calculated against the shop base's currency and all the PO figures recalculated when changed.", ATUM_PO_TEXT_DOMAIN ); ?>"></span>
										</span>

										<span class="currency-conversion__inputs">
											<span class="currency-conversion__from"><?php echo wc_price( 1, [ 'decimals' => 0 ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> =</span>

											<span class="input-group number-mask">
												<input type="number" name="meta-value-exchange-rate" value="<?php echo esc_attr( $atum_order->exchange_rate ) ?>"
													step="0.1" min="0" data-input="exchange_rate" class="meta-value"
													<?php disabled( $wc_currency, $currency ) ?>
												>
												<span class="input-group-append">
													<span class="input-group-text active"><?php echo esc_html( get_woocommerce_currency_symbol( $currency ) ) ?></span>
												</span>
											</span>
										</span>
									</div>
									<a href="<?php echo esc_url( "https://www.xe.com/currencyconverter/convert/?Amount={$atum_order->exchange_rate}&From={$wc_currency}&To={$currency}" ) ?>"
										target="_blank" class="currency-conversion__link"
										<?php echo esc_attr( $wc_currency === $currency ? ' style="display:none"' : '' ) ?>
									><?php esc_html_e( 'Check the actual exchange rate here.', ATUM_PO_TEXT_DOMAIN ) ?></a>

								</template>
							</span>
							<?php endif; ?>
						</span>
					</p>

				</div>

			</div>

			<div class="panel-column purchaser-info-panel">

				<h4>
					<?php esc_html_e( "Purchaser's Info", ATUM_PO_TEXT_DOMAIN ) ?>
					<span class="panel-column__toggler"></span>
				</h4>

				<div class="panel-column__wrapper">

					<p class="form-field">
						<?php $locations = DeliveryLocations::get_locations(); ?>

						<label for="purchaser_name">
							<?php esc_html_e( 'Delivery Location Name', ATUM_PO_TEXT_DOMAIN ) ?>
							<span class="atum-help-tip atum-tooltip" title="<?php esc_html_e( 'Choose a location from the list or write a name in the search box and hit enter to add a custom name', ATUM_PO_TEXT_DOMAIN ); ?>"></span>
						</label>
						<select id="purchaser_name" name="purchaser_name" data-allow-clear="true" data-minimum_input_length="1"
							class="wc-enhanced-select atum-enhanced-select" <?php echo $disabled_att; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							data-placeholder="<?php esc_attr_e( 'Search ATUM Location&hellip;', ATUM_PO_TEXT_DOMAIN ); ?>" style="width:100%"
							data-minimum-results-for-search="0"
						>

							<option value=""></option>

							<?php if ( $atum_order->purchaser_name && 'store_details' !== $atum_order->purchaser_name && FALSE === array_search( $atum_order->purchaser_name, array_column( $locations, 'name' ) ) ) : ?>
								<option value="<?php echo esc_attr( $atum_order->purchaser_name ) ?>" selected><?php echo esc_attr( $atum_order->purchaser_name ) ?></option>
							<?php endif; ?>

							<option value="store_details"><?php esc_html_e( 'Store Details', ATUM_PO_TEXT_DOMAIN ); ?></option>

							<?php foreach ( $locations as $location ) : ?>
								<option value="<?php echo esc_attr( $location['name'] ) ?>"<?php selected( $atum_order->purchaser_name, $location['name'] ) ?>><?php echo esc_html( $location['name'] ) ?></option>
							<?php endforeach; ?>
						</select>
					</p>

					<p class="form-field">
						<label for="purchaser_address"><?php esc_html_e( 'Street Address', ATUM_PO_TEXT_DOMAIN ) ?></label>
						<input type="text" id="purchaser_address" name="purchaser_address" value="<?php echo esc_attr( $atum_order->purchaser_address ) ?>"
							<?php echo $disabled_att; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
					</p>

					<p class="form-field">
						<label for="purchaser_address_2"><?php esc_html_e( 'Address 2', ATUM_PO_TEXT_DOMAIN ) ?></label>
						<input type="text" id="purchaser_address_2" name="purchaser_address_2" value="<?php echo esc_attr( $atum_order->purchaser_address_2 ) ?>"
							<?php echo $disabled_att; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
					</p>

					<p class="form-field">
						<label for="purchaser_city"><?php esc_html_e( 'City/Town', ATUM_PO_TEXT_DOMAIN ) ?></label>
						<input type="text" id="purchaser_city" name="purchaser_city" value="<?php echo esc_attr( $atum_order->purchaser_city ) ?>"
							<?php echo $disabled_att; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
					</p>

					<p class="form-field form-field__flex">

						<span>
							<label for="purchaser_state"><?php esc_html_e( 'State/Region', ATUM_PO_TEXT_DOMAIN ) ?></label>
							<input type="text" id="purchaser_state" name="purchaser_state" value="<?php echo esc_attr( $atum_order->purchaser_state ) ?>"
								<?php echo $disabled_att; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
						</span>

						<span>
							<label for="purchaser_postal_code"><?php esc_html_e( 'Postal Code', ATUM_PO_TEXT_DOMAIN ) ?></label>
							<input type="text" id="purchaser_postal_code" name="purchaser_postal_code" value="<?php echo esc_attr( $atum_order->purchaser_postal_code ) ?>"
								<?php echo $disabled_att; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
						</span>

					</p>

					<p class="form-field">
						<label for="purchaser_country"><?php esc_html_e( 'Country', ATUM_PO_TEXT_DOMAIN ) ?></label>

						<?php $country_obj = new \WC_Countries(); ?>
						<select id="purchaser_country" name="purchaser_country" style="width:100%;" data-allow-clear="true"
							data-placeholder="<?php esc_attr_e( 'Choose country&hellip;', ATUM_PO_TEXT_DOMAIN ); ?>"
							class="wc-enhanced-select atum-enhanced-select"<?php echo $disabled_att; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						>

							<option value=""></option>

							<?php foreach ( $country_obj->get_countries() as $key => $value ) : ?>
								<option value="<?php echo esc_attr( $key ) ?>"<?php selected( $key, $atum_order->purchaser_country ) ?>><?php echo esc_html( $value ) ?></option>
							<?php endforeach; ?>

						</select>
					</p>

				</div>

				<?php do_action( 'atum/purchase_orders/after_po_supplier_data', $atum_order_post, $labels ); ?>

			</div>


			<div class="panel-column internal-info-panel">

				<h4>
					<?php esc_html_e( 'Internal Info', ATUM_PO_TEXT_DOMAIN ) ?>
					<span class="panel-column__toggler"></span>
				</h4>

				<div class="panel-column__wrapper">

					<p class="form-field">
						<label for="date"><?php esc_html_e( 'PO Date', ATUM_PO_TEXT_DOMAIN ) ?></label>

						<span class="date-field with-icon<?php echo esc_attr( ! $is_editable ? ' disabled' : '' ) ?>">
							<input type="text" class="atum-datepicker" name="date" id="date" maxlength="10"
								value="<?php echo esc_attr( $atum_order->date_created ? AtumHelpers::date_format( $atum_order->date_created, FALSE, FALSE, 'Y-m-d H:i' ) : '' ) ?>"
								autocomplete="off" data-date-format="YYYY-MM-DD HH:mm"<?php echo $disabled_att; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
						</span>
					</p>

					<p class="form-field delivery-date-field">
						<label for="delivery_date"><?php esc_html_e( 'Delivery Date', ATUM_PO_TEXT_DOMAIN ) ?></label>

						<span class="date-field with-icon<?php echo esc_attr( ! $is_editable ? ' disabled' : '' ) ?>">
							<input type="text" class="atum-datepicker" name="delivery_date" id="delivery_date" maxlength="10"
								value="<?php echo esc_attr( $atum_order->delivery_date ? AtumHelpers::date_format( $atum_order->delivery_date, FALSE, FALSE, 'Y-m-d H:i' ) : '' ) ?>"
								autocomplete="off" data-date-format="YYYY-MM-DD HH:mm"<?php echo $disabled_att; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
						</span>
					</p>

					<p class="form-field sales-order-field">
						<label for="sales_order_number"><?php esc_html_e( 'Sales Order Number', ATUM_PO_TEXT_DOMAIN ) ?></label>

						<select class="wc-product-search atum-enhanced-select" id="sales_order_number" name="sales_order_number" data-allow_clear="true" data-action="atum_json_search_orders"
							data-placeholder="<?php esc_attr_e( 'Search by Order ID&hellip;', ATUM_PO_TEXT_DOMAIN ); ?>" data-multiple="false"
							data-selected="" data-minimum_input_length="1" data-nonce="<?php echo esc_attr( wp_create_nonce( 'po-sales-order' ) ) ?>"
							<?php echo $disabled_att; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						>
							<?php if ( $atum_order->sales_order_number ) : ?>
								<option value="<?php echo esc_attr( $atum_order->sales_order_number ); ?>" selected="selected"><?php echo esc_html__( 'Order #', ATUM_PO_TEXT_DOMAIN ) . esc_attr( $atum_order->sales_order_number ) ?></option>
							<?php endif; ?>
						</select>
					</p>

					<p class="form-field">
						<label for="customer_name"><?php esc_html_e( "Customer's Name", ATUM_PO_TEXT_DOMAIN ) ?></label>
						<input type="text" id="customer_name" name="customer_name" value="<?php echo esc_attr( $atum_order->customer_name ) ?>"
							<?php echo $disabled_att; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
					</p>

					<?php $requires_requisition = AtumHelpers::get_option( 'po_required_requisition', 'no' ); ?>
					<p class="form-field requisitioner-field">

						<label for="requisitioner"<?php echo 'no' === $requires_requisition ? ' class="atum-tooltip" title="' . ( $is_editable ? esc_attr__( 'For adding a requisitioner, enable the Required Requisition option in ATUM Settings', ATUM_PO_TEXT_DOMAIN ) : '' ) . '"' : '' ?>>
							<?php esc_html_e( 'Requisitioner', ATUM_PO_TEXT_DOMAIN ) ?>
						</label>

						<select id="requisitioner" name="requisitioner" data-allow-clear="true" data-minimum_input_length="1"
							data-placeholder="<?php esc_attr_e( 'Search user&hellip;', ATUM_PO_TEXT_DOMAIN ); ?>"
							data-action="atum_po_json_search_users" style="width:100%"
							data-nonce="<?php echo esc_attr( wp_create_nonce( 'search-users' ) ) ?>"
							<?php echo ! $is_editable ? $disabled_att : disabled( 'no', $requires_requisition, FALSE ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						>

							<?php if ( 'yes' === $requires_requisition && $atum_order->requisitioner ) :
								$user = get_user_by( 'id', $atum_order->requisitioner ) ?>
								<option value="<?php echo esc_attr( $atum_order->requisitioner ) ?>"><?php echo esc_attr( $user->display_name ) ?></option>
							<?php endif; ?>

						</select>
					</p>

					<p class="form-field form-field__flex">

						<span>
							<label for="ship_via"><?php esc_html_e( 'Ship Via', ATUM_PO_TEXT_DOMAIN ) ?></label>

							<select id="ship_via" name="ship_via" style="width:100%;" class="wc-enhanced-select atum-enhanced-select"
								data-placeholder="<?php esc_html_e( 'Ship via&hellip;', ATUM_PO_TEXT_DOMAIN ); ?>"
								data-allow-clear="true"<?php echo $disabled_att; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							>

								<option value=""></option>

								<?php foreach ( Globals::get_shipping_methods() as $key => $label ) : ?>
									<option value="<?php echo esc_attr( $key ) ?>"<?php selected( $key, $atum_order->ship_via ) ?>><?php echo esc_html( $label ) ?></option>
								<?php endforeach; ?>

							</select>
						</span>

						<span>
							<label for="fob"><?php esc_html_e( 'F.O.B.', ATUM_PO_TEXT_DOMAIN ) ?></label>
							<input type="text" id="fob" name="fob" value="<?php echo esc_attr( $atum_order->fob ) ?>"
								<?php echo $disabled_att; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							>
						</span>

					</p>

					<p class="form-field">
						<label for="ships_from"><?php esc_html_e( 'Ships From', ATUM_PO_TEXT_DOMAIN ) ?></label>

						<select id="ships_from" name="ships_from" style="width:100%;" class="wc-enhanced-select atum-enhanced-select"
							data-placeholder="<?php esc_attr_e( 'Choose country&hellip;', ATUM_PO_TEXT_DOMAIN ); ?>"
							data-allow-clear="true"<?php echo $disabled_att; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						>

							<option value=""></option>

							<?php foreach ( $country_obj->get_countries() as $key => $label ) : ?>
								<option value="<?php echo esc_attr( $key ) ?>"<?php selected( $key, $atum_order->ships_from ) ?>><?php echo esc_html( $label ) ?></option>
							<?php endforeach; ?>

						</select>
					</p>
				</div>

			</div>

		</div>

		<?php if ( ! $atum_order->is_cancelled() && ! $atum_order->is_returning() ) : ?>
			<div class="panel-column-container collapsible advanced-options-panel">

				<?php $advanced_options_panel_state = get_post_meta( $atum_order->get_id(), '_advanced_options_panel', TRUE ) ?>
				<h4>
					<?php esc_html_e( 'Advanced Options', ATUM_PO_TEXT_DOMAIN ); ?>
					<span class="panel-column__toggler<?php echo ( 'collapsed' === $advanced_options_panel_state ? ' is-collapsed' : '' ) ?>" data-screen-option="advanced_options_panel"></span>
				</h4>

				<div class="panel-column-container__wrapper"<?php echo ( 'collapsed' === $advanced_options_panel_state ? ' style="display:none"' : '' ) ?>>

					<div class="panel-column description-field">

						<label for="description"><?php esc_html_e( 'Description', ATUM_PO_TEXT_DOMAIN ) ?></label>

						<?php
						$editor_settings = array(
							'media_buttons' => FALSE,
							'textarea_rows' => 10,
							'tinymce'       => array( 'toolbar1' => 'bold,italic,underline,bullist,numlist,link,unlink,forecolor,undo,redo' ),
						);

						if ( ! $is_editable ) : ?>
							<textarea rows="14"<?php echo $disabled_att; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php echo esc_textarea( stripslashes( $atum_order->get_description() ) ) ?></textarea>
						<?php else : ?>
							<?php wp_editor( stripslashes( $atum_order->get_description() ), 'description', $editor_settings ); ?>
						<?php endif; ?>

					</div>

					<div class="panel-column terms-field">

						<label for="delivery_terms"><?php esc_html_e( 'Payment and Delivery Terms', ATUM_PO_TEXT_DOMAIN ) ?></label>

						<?php if ( ! $is_editable ) : ?>
							<textarea rows="14"<?php echo $disabled_att; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php echo esc_textarea( stripslashes( $atum_order->delivery_terms ) ) ?></textarea>
						<?php else : ?>
							<?php wp_editor( $atum_order->delivery_terms, 'delivery_terms', $editor_settings ); ?>
						<?php endif; ?>

					</div>

					<div class="panel-column pdf-template-field">

						<?php if ( $is_editable ) : ?>
							<label><?php esc_html_e( 'PDF Template', ATUM_PO_TEXT_DOMAIN ) ?></label>

							<?php $templates = Helpers::get_po_pdf_templates() ?>
							<div class="template-selector splide">
								<div class="splide__track">
									<ul class="splide__list">
										<?php foreach ( $templates as $key => $template_data ) : ?>

											<?php
											$is_active   = $atum_order->pdf_template === $key;
											$checked_str = checked( $is_active, TRUE, FALSE );
											?>
											<li class="splide__slide<?php if ( $is_active ) echo ' option-checked' ?>">
												<label>
													<img src="<?php echo esc_url( $template_data['img_url'] ) ?>" alt="">
													<input type="radio" name="pdf_template" autocomplete="off"
														<?php echo wp_kses_post( $checked_str ) ?> value="<?php echo esc_attr( $key ) ?>"
													>
													<div><?php echo esc_attr( $template_data['label'] ) ?></div>
												</label>
											</li>

										<?php endforeach; ?>
									</ul>
								</div>
							</div>
						<?php endif; ?>

					</div>

				</div>
			</div>
		<?php endif; ?>

		<?php do_action( 'atum/purchase_orders/after_po_details', $atum_order ); // Using original ATUM hook. ?>

	</div>

</div>
