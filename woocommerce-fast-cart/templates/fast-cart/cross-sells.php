<?php
/**
 * Cross sells product loop template
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/wfc/fast-cart/cross-sells.php.
 */

defined( 'ABSPATH' ) || exit;

if ( $cross_sells ) : ?>

	<div class="wfc-cross-sells <?php echo count( $cross_sells ) > 1 ? 'more-than-one' : 'less-than-two'; ?> <?php echo count( $cross_sells ) > 2 ? 'more-than-two' : 'less-than-three'; ?>">
		<?php
		$heading = apply_filters( 'woocommerce_product_cross_sells_products_heading', __( 'You may be interested in&hellip;', 'wc-fast-cart' ) );

		if ( $heading ) :
			?>
			<h3><?php echo esc_html( $heading ); ?></h3>
		<?php endif; ?>

		<div class="wfc-cross-sells__inner-container">
			<?php $GLOBALS['woocommerce_loop']['loop'] = 0; ?>
			<ul class="products">

				<?php foreach ( $cross_sells as $cross_sell ) : ?>

					<?php
						$post_object = get_post( $cross_sell->get_id() );

						setup_postdata( $GLOBALS['post'] =& $post_object ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited, Squiz.PHP.DisallowMultipleAssignments.Found

						do_action( 'wfc_cross_sells_loop_product', $post_object );
					?>

				<?php endforeach; ?>

			</ul>
		</div>

	</div>
	<?php
endif;

wp_reset_postdata();
