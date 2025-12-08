<?php
/**
 * Kestrel Store Credit for WooCommerce
 *
 * This source file is subject to the GNU General Public License v3.0 that is bundled with this plugin in the file license.txt.
 *
 * Please do not modify this file if you want to upgrade this plugin to newer versions in the future.
 * If you want to customize this file for your needs, please review our developer documentation.
 * Join our developer program at https://kestrelwp.com/developers
 *
 * @author    Kestrel
 * @copyright Copyright (c) 2012-2025 Kestrel Commerce LLC [hey@kestrelwp.com]
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined( 'ABSPATH' ) or exit;

/**
 * My account: store credit.
 *
 * @since 3.0.0
 * @version 5.0.0
 */

$coupons = wc_store_credit_get_customer_coupons( get_current_user_id() );
$columns = [
	/* translators: Context: Table column for store credit coupon codes */
	'code'    => __( 'Code', 'woocommerce-store-credit' ),
	/* translators: Context: Table column for store credit amounts */
	'credit'  => __( 'Credit', 'woocommerce-store-credit' ),
	/* translators: Context: Table column for store credit expiration */
	'expiry'  => __( 'Expiry date', 'woocommerce-store-credit' ),
	/* translators: Context: Table column for actions concerning user store credit */
	'actions' => __( 'Actions', 'woocommerce-store-credit' ),
];

if ( empty( $coupons ) ) :

	?>
	<p class="woocommerce-Message woocommerce-Message--info woocommerce-info"><?php esc_html_e( 'No store credit coupons found.', 'woocommerce-store-credit' ); ?></p>
	<?php

else :

	?>
	<table class="woocommerce-MyAccount-store-credit shop_table shop_table_responsive">
		<thead>
			<tr>
				<th class="woocommerce-store-credit-code"><span class="nobr"><?php echo esc_html( $columns['code'] ); ?></span></th>
				<th class="woocommerce-store-credit-credit"><span class="nobr"><?php echo esc_html( $columns['credit'] ); ?></span></th>
				<th class="woocommerce-store-credit-expiry"><span class="nobr"><?php echo esc_html( $columns['expiry'] ); ?></span></th>
				<th class="woocommerce-store-credit-actions"><span class="nobr"><?php echo esc_html( $columns['actions'] ); ?></span></th>
			</tr>
		</thead>
		<tbody>
		<?php foreach ( $coupons as $coupon ) : ?>
			<tr class="woocommerce-store-credit-row">
				<td class="woocommerce-store-credit-code" data-title="<?php echo esc_attr( $columns['code'] ); ?>">
					<?php echo esc_html( $coupon->get_code() ); ?>
				</td>

				<td class="woocommerce-store-credit-credit" data-title="<?php echo esc_attr( $columns['credit'] ); ?>">
					<?php echo wp_kses_post( wc_price( $coupon->get_amount() ) ); ?>
				</td>

				<td class="woocommerce-store-credit-expiry" data-title="<?php echo esc_attr( $columns['expiry'] ); ?>">
					<?php
						$expiration_date = $coupon->get_date_expires();

						echo esc_html( $expiration_date ? wc_format_datetime( $expiration_date ) : '&ndash;' );
					?>
				</td>

				<td class="woocommerce-store-credit-actions" data-title="<?php echo esc_attr( $columns['actions'] ); ?>">
					<a href="<?php echo esc_url( wc_store_credit_get_redeem_url( $coupon ) ); ?>" class="woocommerce-Button button wp-element-button woocommerce-button button apply">
						<?php esc_html_e( 'Use now', 'woocommerce-store-credit' ); ?>
					</a>
				</td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<?php

endif;
