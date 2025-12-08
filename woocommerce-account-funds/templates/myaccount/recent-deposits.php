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
 * My Account > Recent deposits.
 *
 * @since 1.0.0
 *
 * @version 4.0.0
 */

$deposit_columns = [
	'order-number' => __( 'Order', 'woocommerce' ),
	'order-date'   => __( 'Date', 'woocommerce' ),
	'order-status' => __( 'Status', 'woocommerce' ),
	'order-total'  => wc_account_funds_store_credit_label( 'plural' ),
];

?>
<h2><?php esc_html_e( 'Recent deposits', 'woocommerce-account-funds' ); ?></h2>
<table class="shop_table shop_table_responsive my_account_deposits">
	<thead>
		<tr>
			<?php foreach ( $deposit_columns as $column_id => $column_name ) : ?>
				<th class="<?php echo esc_attr( $column_id ); ?>"><span class="nobr"><?php echo esc_html( $column_name ); ?></span></th>
			<?php endforeach; ?>
		</tr>
	</thead>
	<tbody>
		<?php

		/**
		 * Fires after the recent deposits table header.
		 *
		 * @since 2.8.0
		 */
		do_action( 'woocommerce_account_funds_recent_deposit_items_data' );

		?>
	</tbody>
</table>
