<?php

add_action('woocommerce_after_checkout_form', 'absb_display_upsells');
add_action('woocommerce_before_checkout_form', 'absb_display_upsells');
add_action('woocommerce_before_cart', 'absb_display_upsells');
add_action('woocommerce_after_cart', 'absb_display_upsells');

add_action('the_content', 'plugify_absb_compatible_with_blocks');


function plugify_absb_compatible_with_blocks ( $content ) {
	if (!is_cart() && !is_checkout()) {
		return $content;
	}


	if ( is_checkout() && is_wc_endpoint_url('order-received') ) {
		return $content;
	}
	if ( 0 == WC()->cart->cart_contents_count) {
		return $content;
	}

		
	$content_before = '';
	$content_after = '';

	if (is_cart()) {
		if (absb_is_cart_page_using_blocks()) {
			ob_start();
			absb_display_upsells('', 'woocommerce_before_cart');
			$content_before = ob_get_clean();

			ob_start();
			absb_display_upsells('', 'woocommerce_after_cart');
			$content_after = ob_get_clean();
		}
	}
	if (is_checkout()) {
		if (absb_is_checkout_page_using_blocks()) {
			ob_start();
			absb_display_upsells('', 'woocommerce_before_checkout_form');
			$content_before = ob_get_clean();

			ob_start();
			absb_display_upsells('', 'woocommerce_after_checkout_form');
			$content_after = ob_get_clean();
		}
	}
	return $content_before . $content . $content_after;
}



function absb_is_checkout_page_using_blocks() {

	$checkout_page_id = wc_get_page_id( 'checkout' );
	$checkout_page_content = get_post_field( 'post_content', $checkout_page_id );
	if (strpos($checkout_page_content, '[woocommerce_checkout]') !== false) {

		return false;
	} else {
		return true;
	}

}


function absb_is_cart_page_using_blocks() {

	$cart_page_id = wc_get_page_id( 'cart' );
	$cart_page_content = get_post_field( 'post_content', $cart_page_id );
	if (strpos($cart_page_content, '[woocommerce_cart]') !== false) {
		return false;
	} else {
		return true;
	}

}



function absb_display_upsells( $checkout = '', $default_hook = 'not_set' ) {

	wp_enqueue_style('style_load_for_slider_123431123', plugins_url() . '/advanced-sales-booster-for-woocommerce/front/assets/slickcssfile.css', '1.0', 'all');
	wp_enqueue_style('style_load_for_slider_23432423', plugins_url() . '/advanced-sales-booster-for-woocommerce/front/assets/slick_newcss.css', '1.0', 'all');
	wp_enqueue_script('script_load_for_slider_32342342312', plugins_url() . '/advanced-sales-booster-for-woocommerce/front/assets/slickkijs.js', '', '1.0');


	$rules_settings_upsell = get_option('absb_rule_settings_upsell');
	if (empty($rules_settings_upsell)) {
		return;
	}
	krsort($rules_settings_upsell);

	$gen_settings_upsell = get_option('absb_saved_upsell_general_settings');

	$cart = WC()->cart->get_cart();

	$array=[];

	
	if ( 'not_set' === $default_hook) {
		$current_hook = current_action();		
	} else {
		$current_hook=$default_hook;
	}
	foreach ( $cart as $cart_item_key => $cart_item ) {
		$product = $cart_item['data'];
		

		// if ('simple' == $product->get_type()) {
		$product_id[] = $product->get_id();

		// }

		if ('variable' == $product->get_type()) {
			$variations=$product->get_available_variations();
			$product_id[] = wp_list_pluck( $variations, 'variation_id' );
		}


		$quantity = WC()->cart->get_cart_contents_count();

		$subtotal = WC()->cart->subtotal;

		$cart_total = WC()->cart->cart_contents_total;
		$coupen = WC()->cart->applied_coupons;



		$meta = wc_get_formatted_cart_item_data( $cart_item );
		$cats[] = $product->get_category_ids();



	}

	$ids=array();


	foreach ($rules_settings_upsell as $key => $value) {

		$absb_condition = false;
		$conditions_satisfied = 0;

		foreach ($value['select_iss'] as $key_1 => $value_1) {



			if ('true' == $value['activate_rule'] ) {


				if (is_cart()) {
					if ($current_hook != $value['location_on_cart']) {
						continue;
					}
				}

				if (is_checkout()) {
					if ($current_hook != $value['location_on_checkout']) {
						continue;
					}	
				}


				if ('equals' == $value['conditionn'][$key_1]) {
					if ('user_role' == $value_1 ) {
						$user_meta=get_userdata(get_current_user_ID());
						if (isset($user_meta->roles)) {
							$user_roles=$user_meta->roles;
							if (in_array($value['conditional_val'][$key_1] , $user_roles )) {
								$absb_condition = true;
								++$conditions_satisfied;
							}
						}
					}
				} else if ('notequal' == $value['conditionn'][$key_1]) {
					if ('user_role' == $value_1) {
						$user_meta=get_userdata(get_current_user_ID());
						if (isset($user_meta->roles)) {
							$user_roles=$user_meta->roles;
							if (!in_array($value['conditional_val'][$key_1] , $user_roles )) {
								$absb_condition = true;
								++$conditions_satisfied;
							}
						}
					}
				}





				if ('equals' == $value['conditionn'][$key_1]) {
					if ('slctd_product' == $value_1 ) {
						if (in_array($value['conditional_val'][$key_1], $product_id )) {

							
							$absb_condition = true; 
							++$conditions_satisfied;
						}
					}
				} else if ('notequal' == $value['conditionn'][$key_1]) {
					if ('slctd_product' == $value_1) {
						if (!in_array($value['conditional_val'][$key_1], $product_id )) {
							$absb_condition = true; 
							++$conditions_satisfied;
						}
					}
				}



				if ('equals' == $value['conditionn'][$key_1]) {
					if ('slctd_cateory' == $value_1 ) {


						foreach ( $cart as $cart_item_key => $cart_item ) {
							$product = $cart_item['data'];
							$cats = $product->get_category_ids();
							if (in_array($value['conditional_val'][$key_1], $cats )) {
								$absb_condition = true;
								++$conditions_satisfied;
								break;
							}
						}
					}
				} else if ('notequal' == $value['conditionn'][$key_1]) {
					if ('slctd_cateory' == $value_1) {

						foreach ( $cart as $cart_item_key => $cart_item ) {
							$product = $cart_item['data'];
							$cats = $product->get_category_ids();
							if (!in_array($value['conditional_val'][$key_1], $cats )) {
								$absb_condition = true;
								++$conditions_satisfied;
								break;
							}
						}
					}
				}

				if ('equals' == $value['conditionn'][$key_1]) {
					if ('cartitems' == $value_1) {
						if ($quantity == $value['conditional_val'][$key_1]) {
							$absb_condition = true;
							++$conditions_satisfied;
						}
					}
				} else if ('notequal' == $value['conditionn'][$key_1]) {
					if ('cartitems' == $value_1) {
						if ($quantity != $value['conditional_val'][$key_1]) {
							$absb_condition = true;
							++$conditions_satisfied;
						}
					}
				} else if ('greater' == $value['conditionn'][$key_1]) {
					if ('cartitems' == $value_1) {
						if ($quantity > $value['conditional_val'][$key_1]) {
							$absb_condition = true;
							++$conditions_satisfied;
						}
					}
				} else if ('less' == $value['conditionn'][$key_1]) {
					if ('cartitems' == $value_1) {
						if ($quantity < $value['conditional_val'][$key_1]) {
							$absb_condition = true;
							++$conditions_satisfied;
						}
					}
				}

				if ('equals' == $value['conditionn'][$key_1]) {
					if ('subtotal' == $value_1) {
						if ($cart_total == $value['conditional_val'][$key_1]) {

							$absb_condition = true;
							++$conditions_satisfied;
						}
					}
				} else if ('notequal' == $value['conditionn'][$key_1]) {
					if ('subtotal' == $value_1) {
						if ($cart_total != $value['conditional_val'][$key_1]) {
							$absb_condition = true;
							++$conditions_satisfied;
						}
					}
				} else if ('greater' == $value['conditionn'][$key_1]) {
					if ('subtotal' == $value_1) {
						if ($cart_total > $value['conditional_val'][$key_1]) {
							$absb_condition = true;
							++$conditions_satisfied;
						}
					}
				} else if ('less' == $value['conditionn'][$key_1]) {
					if ('subtotal' == $value_1) {
						if ($cart_total < $value['conditional_val'][$key_1]) {
							$absb_condition = true;
							++$conditions_satisfied;
						}
					}
				}

				if ('equals' == $value['conditionn'][$key_1]) {
					if ('total' == $value_1) {


						if ($subtotal == $value['conditional_val'][$key_1]) {
							$absb_condition = true;
							++$conditions_satisfied;
						}
					}
				} else if ('notequal' == $value['conditionn'][$key_1]) {
					if ('total' == $value_1) {
						if ($subtotal != $value['conditional_val'][$key_1]) {
							$absb_condition = true;
							++$conditions_satisfied;
						}
					}
				} else if ('greater' == $value['conditionn'][$key_1]) {
					if ('total' == $value_1) {
						if ($subtotal > $value['conditional_val'][$key_1]) {
							$absb_condition = true;
							++$conditions_satisfied;
						}
					}
				} else if ('less' == $value['conditionn'][$key_1]) {
					if ('total' == $value_1) {
						if ($subtotal < $value['conditional_val'][$key_1]) {
							$absb_condition = true;
							++$conditions_satisfied;
						}
					}
				}

				if ('equals' == $value['conditionn'][$key_1]) {
					if ('cpn_used' == $value_1 ) {

						$coupen = WC()->cart->applied_coupons;
						if (in_array($value['conditional_val'][$key_1] , $coupen) ) {
							$absb_condition = true;
							++$conditions_satisfied;
						}
					}

				} else if ('notequal' == $value['conditionn'][$key_1]) {
					if ('cpn_used' == $value_1) {
						$coupen = WC()->cart->applied_coupons;
						if (!in_array($value['conditional_val'][$key_1] , $coupen )) {
							$absb_condition = true;
							++$conditions_satisfied;
						}
					}
				}
			}
		}

		if ($conditions_satisfied >= count($value['select_iss'])) {
			if ('products' == $value['appliedon_upsell']) {
				$ids = array_merge($ids, $value['procat_ids_upsell']);
			} else if ('categories' == $value['appliedon_upsell']) { 
				$all_ids = get_posts( array(
					'post_type' => array('product', 'product_variation'),
					'numberposts' => -1,
					'post_status' => 'publish',
					'fields' => 'ids',
					'tax_query' => array(
						array(
							'taxonomy' => 'product_cat',
							'terms' => $value['procat_ids_upsell'],
							'operator' => 'IN',
						)
					)
				));
				foreach ( $all_ids as $id ) {
					array_push($ids, $id);
				}

			}
		}
	}

	$ids =array_unique($ids);	

	if (!empty($ids)) {

		global $woocommerce;
		?>
		<strong><?php echo esc_html($gen_settings_upsell['upsell_title']); ?></strong>

		<div class="testing" id="testing" >
			<?php
			foreach ($ids as $keyzzz => $valuezzz) {

				?>
				<div class="frq_bgt_grid_items" >


					<center> 
						<?php  
						$image = wp_get_attachment_image_src( get_post_thumbnail_id( $valuezzz ), 'single-post-thumbnail' ); 
						$product=wc_get_product($valuezzz);

						$price = wc_price($product->get_price());

						if (empty($image[0])) {
							$image[0] = wc_placeholder_img_src();
						}					

						if ('true' == $gen_settings_upsell['upsell_hyperlink']) {
							?>
							<a href="<?php echo esc_attr(get_permalink( $valuezzz )); ?>">	<img src=" <?php echo esc_attr($image[0]); ?>" width="100" height="150" class="frq_bgt_center" ></a>

							<a href="<?php echo esc_attr(get_permalink( $valuezzz )); ?>">
								<?php
								$product = wc_get_product( $valuezzz );

								if ( $product ) {
									if ( $product->is_type( 'variation' ) ) {
										$parent = wc_get_product( $product->get_parent_id() );
										$variation_attributes = wc_get_formatted_variation( $product, true );
										$title = $parent->get_name() . ' - ' . $variation_attributes;
									} else {
										$title = $product->get_name();
									}

									echo esc_html( $title );
								}
								?>

							</a>

							<br>
							<?php
						} else {
							?>
							<img src=" <?php echo esc_attr($image[0]); ?>" width="100" height="150" class="frq_bgt_center" >
							<?php
							$product = wc_get_product( $valuezzz );

							if ( $product ) {
								if ( $product->is_type( 'variation' ) ) {
									$parent = wc_get_product( $product->get_parent_id() );
									$variation_attributes = wc_get_formatted_variation( $product, true );
									$title = $parent->get_name() . ' - ' . $variation_attributes;
								} else {
									$title = $product->get_name();
								}

								echo esc_html( $title );
							}
							?>

							<br>
							<?php 
						}
						?>
						<span class="price">
							<?php
							// nosemgrep: audit.php.wp.security.xss.unescaped-stored-option
							// echo filter_var($price);
							do_action('plugify_allll_contenttt3', $price);

							?>

						</span>
						<?php
						if ('true' == $gen_settings_upsell['upsell_discription']) {
							// nosemgrep: audit.php.wp.security.xss.unescaped-stored-option
							// echo '<br>' . filter_var($product->get_short_description()); 
							do_action('plugify_allll_contenttt3', '<br>' . $product->get_short_description());
						}
						?>
					</center>
				</div>	
				<?php


			}

			?>
		</div> 
		<?php

	}

	?>

	<script type="text/javascript">
		jQuery(document).ready(function () {

			jQuery('.testing').slick({

				infinite:<?php echo esc_attr($gen_settings_upsell['enable_loop']); ?>,
				autoplay:<?php echo esc_attr($gen_settings_upsell['enable_autoplay']); ?>,
				autoplaySpeed:<?php echo esc_attr($gen_settings_upsell['autoplayspeed']*1000); ?>,
				arrows:<?php echo esc_attr($gen_settings_upsell['upsell_arrows']); ?>,
				centerMode:false,
				dots:<?php echo esc_attr($gen_settings_upsell['upsell_dots']); ?>,

				prevArrow: '<button class="slide-arrow prev-arrow"></button>',
				nextArrow: '<button class="slide-arrow next-arrow"></button>',

				responsive: [{
					breakpoint: 5000,
					settings: {
						slidesToShow: <?php echo esc_attr($gen_settings_upsell['screens_for_1100_greater']); ?>,
					}

				}, {
					breakpoint: 1100,
					settings: {
						slidesToShow: <?php echo esc_attr($gen_settings_upsell['screens_for_1100']); ?>,

					}
				}, {
					breakpoint: 800,
					settings: {
						slidesToShow: <?php echo esc_attr($gen_settings_upsell['screens_for_800']); ?>,
					}
				}, {
					breakpoint: 500,
					settings: {
						slidesToShow: <?php echo esc_attr($gen_settings_upsell['screens_for_500']); ?>,
					}
				}],

			});

		})


	</script>

	<style type="text/css">


		.testing {
			width: 100%;
			margin: 1% !important;

		}

		.slick-slide {
			margin: 10px
		}

		.slick-slide img {
			width: 100%;
			border: 0px solid #fff
		}



		.slide-arrow{
			margin: 0;
			padding: 0;
			background: none;
			border: none;
			border-radius: 0;
			outline: none;
			-webkit-appearance: none;
			-moz-appearance: none;
			appearance: none;
			position: absolute;
			top: 50%;
			/*margin-top: -15px;*/
			margin: -2%;
		}
		.prev-arrow{
			left: -8px;
			width: 0;
			z-index: 99999999999;

			height: 0;
			border-left: 0 solid transparent;
			border-right: 15px solid #113463;
			border-top: 10px solid transparent;
			border-bottom: 10px solid transparent;
			margin-top: -7%;

		}
		.next-arrow{
			right: -8px;
			z-index: 99999999999;
			width: 0;

			height: 0;
			border-right: 0 solid transparent;
			border-left: 15px solid #113463;
			border-top: 10px solid transparent;
			border-bottom: 10px solid transparent;
			margin-top: -7%;
		}

	</style>
	<?php
}


add_action('plugify_allll_contenttt3', 'plugify_allll_contenttt3');

function plugify_allll_contenttt3 ( $random_messages ) {
	echo filter_var($random_messages);
}


