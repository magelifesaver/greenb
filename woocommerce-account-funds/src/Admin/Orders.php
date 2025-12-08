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

declare( strict_types = 1 );

namespace Kestrel\Account_Funds\Admin;

defined( 'ABSPATH' ) or exit;

use Kestrel\Account_Funds\Plugin;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Traits\Is_Handler;
use Kestrel\Account_Funds\Settings\Store_Credit_Label;
use WC_Order;

/**
 * Orders handler for the admin area.
 *
 * @since 4.0.0
 */
final class Orders {
	use Is_Handler;

	/**
	 * Constructor.
	 *
	 * @since 4.0.0
	 *
	 * @param Plugin $plugin
	 */
	protected function __construct( Plugin $plugin ) {
		self::$plugin = $plugin;

		self::add_action( 'woocommerce_admin_order_totals_after_tax', [ $this, 'display_used_store_credit'] );
	}

	/**
	 * Outputs the store credit used in the edit-order screen.
	 *
	 * @since 4.0.0
	 *
	 * @param int|mixed $order_id the order ID
	 * @return void
	 */
	protected function display_used_store_credit( $order_id ) : void {

		$order = wc_get_order( $order_id );

		if ( ! $order instanceof WC_Order ) {
			return;
		}

		$funds_used = (float) $order->get_meta( '_funds_used' );

		if ( 0 >= $funds_used || 'accountfunds' === $order->get_payment_method() ) :
			return;

		endif;

		?>
		<tr>
			<td class="label"><?php echo esc_html( Store_Credit_Label::plural()->uppercase_first()->to_string() ); ?>:</td>
			<td width="1%"></td>
			<td class="total"><?php echo wp_kses_post( '-' . wc_price( $funds_used, [ 'currency' => $order->get_currency() ] ) ); ?></td>
		</tr>
		<?php
	}

}
