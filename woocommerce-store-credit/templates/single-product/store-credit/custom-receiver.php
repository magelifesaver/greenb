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
 * Store Credit Product: Custom receiver template.
 *
 * @since 3.0.0
 * @version 4.0.3
 *
 * @var array<string, mixed> $data store credit product data
 * @var array<string, mixed> $fields fields to display
 */
?>
<h3 class="send-to-different-customer">
	<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
		<input id="send-to-different-customer" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" type="checkbox" name="send-to-different-customer" value="1" <?php checked( 'expanded', $data['display_receiver_fields'] ); ?> />
		<span><?php echo wp_kses_post( $data['receiver_fields_title'] ); ?></span>
	</label>
</h3>
<div class="store-credit-receiver-fields">
	<?php
	foreach ( $fields as $key => $field ) :
		woocommerce_form_field( $key, $field, WC_Store_Credit_Product_Addons::get_value( $key ) );
	endforeach;
	?>
</div>
<?php
