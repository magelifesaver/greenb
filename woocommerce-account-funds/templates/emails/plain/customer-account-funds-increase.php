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
 * Customer increase account funds email (plain text).
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
echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

/**
 * Show email message - this is set in the email's settings.
 */
echo esc_html( wp_strip_all_tags( wptexturize( $message ) ) );

echo "\n\n----------------------------------------\n\n";

echo esc_html( wp_strip_all_tags( wc_price( $funds_amount ) ) );

echo "\n\n----------------------------------------\n\n";

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) :

	echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );

	echo "\n\n----------------------------------------\n\n";

endif;

echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
