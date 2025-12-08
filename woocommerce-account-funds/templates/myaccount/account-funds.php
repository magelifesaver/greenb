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
 * My Account > Account Funds page.
 *
 * @since 1.0.0
 *
 * @version 4.0.0
 */

wc_print_notices();

?>
<div class="woocommerce-MyAccount-account-funds">
	<p>
		<?php

		$funds = WC_Account_Funds::get_account_funds();

		echo wp_kses_post(
			sprintf(
				/* translators: Placeholders: %1$s - Funds amount, %2$s - Label used to describe account funds */
				__( 'You currently have <strong>%1$s</strong> worth of %2$s in your account.', 'woocommerce-account-funds' ),
				(string) $funds,
				wc_account_funds_store_credit_label( $funds )
			)
		);
		?>
	</p>
	<?php do_action( 'woocommerce_account_funds_content' ); // phpcs:ignore ?>
</div>
<?php
