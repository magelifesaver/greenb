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
 * My Account > Top-Up form.
 *
 * @since 1.0.0
 *
 * @version 4.0.0
 *
 * @var float|null $min_topup
 * @var float|null $max_topup
 */
?>
<form method="post">
	<h3>
		<label for="topup_amount">
			<?php
			/* translators: Placeholder: %s: Label used to describe account funds */
			echo esc_html( sprintf( __( 'Top-up %s', 'woocommerce-account-funds' ), wc_account_funds_store_credit_label( 'plural' ) ) );
			?>
		</label>
	</h3>
	<p class="form-row form-row-first">
		<input type="number" class="input-text" name="topup_amount" id="topup_amount" step="0.01" value="<?php echo esc_attr( $min_topup ); ?>" min="<?php echo esc_attr( $min_topup ); ?>" max="<?php echo esc_attr( $max_topup ); ?>" />
		<?php if ( $min_topup || $max_topup ) : ?>
			<span class="description">
				<?php
				// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
				printf(
					'%s%s%s',
					/* translators: Placeholder: %s - Minimum account fund top-up amount */
					$min_topup ? sprintf( __( 'Minimum: <strong>%s</strong>.', 'woocommerce-account-funds' ), wc_price( $min_topup ) ) : '',
					$min_topup && $max_topup ? ' ' : '',
					/* translators: Placeholder: %s - Maximum account fund top-up amount */
					$max_topup ? sprintf( __( 'Maximum: <strong>%s</strong>.', 'woocommerce-account-funds' ), wc_price( $max_topup ) ) : ''
				);
				// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
				?>
			</span>
		<?php endif; ?>
	</p>
	<p class="form-row form-row-last">
		<input type="hidden" name="wc_account_funds_topup" value="true" />
		<input type="submit" class="woocommerce-button wp-element-button button" value="<?php esc_html_e( 'Top-up', 'woocommerce-account-funds' ); ?>" />
	</p>
	<?php wp_nonce_field( 'account-funds-topup' ); ?>
	<div class="clear"></div>
</form>
<?php
