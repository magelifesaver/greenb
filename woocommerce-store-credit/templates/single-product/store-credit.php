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
 * Single product store credit template.
 *
 * @since 3.0.0
 * @version 4.0.0
 */

?>
<div class="wc-store-credit-product-container">
	<?php

	/**
	 * Fires before the store credit single product content.
	 *
	 * @since 4.0.0
	 */
	do_action( 'wc_store_credit_single_product_content' );

	?>
</div>
