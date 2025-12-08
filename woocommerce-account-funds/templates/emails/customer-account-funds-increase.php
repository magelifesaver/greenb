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
 * Customer increase account funds email.
 *
 * @since 1.0.0
 *
 * @version 3.2.0
 *
 * @var WC_Email $email
 * @var string $email_heading
 * @var string $message
 * @var float $funds_amount
 * @var string $additional_content
 */

/*
 * @hooked WC_Emails::email_header() Output the email header.
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); // phpcs:ignore WooCommerce.Commenting.CommentHooks

/**
 * Show email message - this is set in the email's settings.
 */
echo wp_kses_post( wpautop( wptexturize( $message ) ) );

?>
<div class="wc-account-funds-wrapper text-centephpcr">
	<span class="wc-account-funds-amount"><?php echo wp_kses_post( wc_price( $funds_amount ) ); ?></span>
</div>
<?php

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) :

	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );

endif;

/*
 * @hooked WC_Emails::email_footer() Output the email footer.
 */
do_action( 'woocommerce_email_footer', $email ); // phpcs:ignore WooCommerce.Commenting.CommentHooks
