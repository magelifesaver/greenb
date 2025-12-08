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
 * Styles for the Store Credit emails template.
 *
 * @since 3.0.0
 * @version 5.0.0
 */

$base      = get_option( 'woocommerce_email_base_color' );
$base_text = wc_light_or_dark( $base, '#202020', '#ffffff' );

?>
.text-center {
	text-align: center;
}

.store-credit-wrapper {
	margin: 40px 0;
}

.store-credit-restrictions {
	font-size: 14px;
}

.store-credit-code {
	display: inline-block;
	font-size: 28px;
	font-weight: bold;
	line-height: 1.2;
}

.store-credit-cta-button {
	display: inline-block;
	padding: 15px 20px;
	font-size: 18px;
	text-decoration: none;
	background-color: <?php echo esc_attr( $base ); ?>;
	color: <?php echo esc_attr( $base_text ); ?>;
}
<?php
