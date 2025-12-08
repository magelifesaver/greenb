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
 * Store Credit Product: Preset amounts.
 *
 * @since 3.0.0
 * @version 4.5.0
 *
 * @var float[] $preset_amounts an array with the predefined credit amounts
 * @var bool $allow_custom whether the Store Credit product allows setting a custom amount
 */
?>
<div class="store-credit-preset-amounts-container">
	<h3 class="store-credit-preset-amounts-title"><?php esc_html_e( 'Choose a different amount:', 'woocommerce-store-credit' ); ?></h3>
	<div class="store-credit-preset-amounts">
		<?php foreach ( $preset_amounts as $amount ) : ?>
			<a class="button store-credit-preset-amount" data-value="<?php echo esc_attr( $amount ); ?>">
				<?php echo wp_kses_post( wc_price( $amount, [ 'decimals' => 0 ] ) ); ?>
			</a>
		<?php endforeach; ?>

		<?php if ( $allow_custom ) : ?>
			<a class="button store-credit-preset-amount custom-amount" data-value="custom">
				<?php esc_html_e( 'Other', 'woocommerce-store-credit' ); ?>
			</a>
		<?php endif; ?>
	</div>
</div>
<?php
