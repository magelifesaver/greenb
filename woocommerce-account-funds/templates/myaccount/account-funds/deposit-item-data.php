<?php
/**
 * Account Funds for WooCommerce
 *
 * This source file is subject to the GNU General Public License v3.0 that is bundled with this plugin in the file license.txt.
 *
 * Please do not modify this file if you want to upgrade this plugin to newer versions in the future.
 * If you want to customize this file for your needs, please review our developer documentation.
 * Join our developer program at https://kestrelwp.com/developers
 *
 * @author    Kestrel
 * @copyright Copyright (c) 2015-2025 Kestrel Commerce LLC [hey@kestrelwp.com]
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined( 'ABSPATH' ) or exit;

/**
 * My Account > Deposit item data.
 *
 * @since 1.0.0
 *
 * @version 2.8.0
 *
 * @var array<string, mixed> $deposit
 */
?>
<tr class="order">
	<td class="order-number" data-title="<?php esc_attr_e( 'Order number', 'woocommerce' ); ?>">
		<a href="<?php echo esc_url( $deposit['order_url'] ); ?>">
			#<?php echo esc_html( $deposit['order_number'] ); ?>
		</a>
	</td>
	<td class="order-date" data-title="<?php esc_attr_e( 'Date', 'woocommerce' ); ?>">
		<time datetime="<?php echo esc_attr( gmdate( 'c', strtotime( $deposit['order_date'] ) ) ); ?>"><?php echo esc_html( date_i18n( wc_date_format(), strtotime( $deposit['order_date'] ) ) ); ?></time>
	</td>
	<td class="order-status" data-title="<?php esc_attr_e( 'Status', 'woocommerce' ); ?>">
		<?php echo esc_html( $deposit['order_status_name'] ); ?>
	</td>
	<td class="order-total" data-title="<?php echo esc_attr( wc_account_funds_store_credit_label( 'plural' ) ); ?>">
		<?php echo wp_kses_post( wc_price( $deposit['funded'] ) ); ?>
	</td>
</tr>
