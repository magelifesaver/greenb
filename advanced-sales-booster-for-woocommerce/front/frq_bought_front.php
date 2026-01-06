<?php 

add_action('woocommerce_add_to_cart', 'absb_custome_add_to_cart');


function absb_custome_add_to_cart() {
	if (isset($_REQUEST['xyz'])) {
		$p_id= filter_var($_REQUEST['xyz']);
	}

	$idss_array = explode( ',', $p_id );



	foreach ($idss_array as $key => $value) {



		
		remove_action('woocommerce_add_to_cart', __FUNCTION__);
		WC()->cart->add_to_cart( $value );
	}
}

add_action('woocommerce_after_add_to_cart_button', 'avcdefghijklmno');
function avcdefghijklmno() {
	?>
	<input type="hidden" name="xyz" class="hidden_field_for_frq">


	<?php
}



add_action('wp_head', 'frq_bgt_select_hook_to_use');

function frq_bgt_select_hook_to_use() {

	$frq_bgt_gen_settings = get_option('frq_bgt_general_settings');
	$frq_bgt_rule_data = get_option('absb_frq_bgt_items');


	$visual_hook = 'woocommerce_before_add_to_cart_form';
	if (is_product()) {
		$product = wc_get_product(get_the_ID());
		$product_price = $product->get_price();
		$product_sale_price = $product->get_sale_price();
		if ('beforeadding' == $frq_bgt_gen_settings['frq_bgt_location']) {
			$visual_hook = 'woocommerce_before_add_to_cart_form';
		} else if ('afteradding' == $frq_bgt_gen_settings['frq_bgt_location'] ) {
			$visual_hook = 'woocommerce_after_add_to_cart_form';
		}

		add_action( $visual_hook, 'frq_bgt_display_data');
	}
}


add_action('plugify_allll_contenttt2', 'plugify_allll_contenttt2');

function plugify_allll_contenttt2 ( $random_messages ) {
	echo filter_var($random_messages);
}


function frq_bgt_display_data() {

	$frq_bgt_gen_settings = get_option('frq_bgt_general_settings');
	$frq_bgt_rule_data = get_option('absb_frq_bgt_items');

	if (is_product()) {
		$product = wc_get_product(get_the_ID());
		$product_price = $product->get_price();

		if (!is_array($frq_bgt_rule_data)) {
			return; 
		}
		
		foreach ($frq_bgt_rule_data as $key => $value) {

			$dissss = '';

			$product_id = get_the_ID();

			$is_rule_applied = false;
			if ('variable' == $product->get_type()) {
				$dissss = 'display:none;';
				$variations = $product->get_available_variations();
				$variations_id = wp_list_pluck( $variations, 'variation_id' );

				if (in_array( $value['selected_productzz'] , $variations_id)) {
					$is_rule_applied = true;
					$product_id = $value['selected_productzz'];
				}

			} else {

				if (get_the_ID() == $value['selected_productzz'] ) {
					$is_rule_applied = true;
				}


			}
			if ($is_rule_applied) {

				?>
				<div  class="frq_bgt_show_hide_class" id="frq_bgt_show_hide_class<?php echo esc_attr(trim($value['selected_productzz'])); ?>"  style="<?php echo esc_attr($dissss); ?>" >
					<?php



					if ('true' == $frq_bgt_gen_settings['frq_bgt_tablename'] ) {

						?>
						<strong><?php echo esc_html($frq_bgt_gen_settings['frq_bgt_tabletitle']); ?></strong>
						<?php 
					}
					?>
					<div class="frq_bgt_grid_container" >
						<?php
						$frq_bgt_rule_settings = get_post_meta($product_id, 'selected_products', true);


							
						$absb_rules_dataxc= get_option('absb_rule_settings');

						if ( '' == $absb_rules_dataxc ) {
							$absb_rules_dataxc=array();
						}
						
						$arrayto_be_checked=[];
						foreach ($absb_rules_dataxc as $keyxc => $valuexc) {
							if ('true' == $valuexc['activaterule']) {
								$found_user_rolexc=absb_set_allowed_rolesxc($valuexc);

								if ('true' == $valuexc['activaterule'] && $found_user_rolexc) {
									if ('products' == $valuexc['appliedon']) {
										foreach ($valuexc['procat_ids'] as $keyinnerxc => $valueinnerxc) {
											$arrayto_be_checked[]=$valueinnerxc;
										}
									}
								}
							}
						}

						foreach ($frq_bgt_rule_settings as $key11 => $value11) {

							$product = wc_get_product($value11);
							$price = wc_price($product->get_price());

							$is_allowwweed = true;
							if ('false' == $frq_bgt_gen_settings['frq_bgt_image'] && 'false' == $frq_bgt_gen_settings['frq_bgt_cartbtn'] && 'false' == $frq_bgt_gen_settings['frq_bgt_price'] && 'false' == $frq_bgt_gen_settings['frq_bgt_enable_ad_cart']) {
								$is_allowwweed = false;
							}

							if ($is_allowwweed) {

								?>
								<div>
									<div class="frq_bgt_grid_items" style="height: 85px; ">

										<table style="width: 100%;" class="table_frq_bgt_go">
											<tr>
												<?php  


												if ('true' == $frq_bgt_gen_settings['frq_bgt_image']) {


													$image = wp_get_attachment_image_src( get_post_thumbnail_id( $value11 ), 'single-post-thumbnail' );
													if (empty($image) || ''==$image[0]) { 
														$placeholder_image = get_option( 'woocommerce_placeholder_image' );
														$image = wp_get_attachment_image_src( $placeholder_image, 'woocommerce_thumbnail' );
														$image=$image[0];
													} else {
														$image=$image[0];
													}



													?>
													<td style="width: 20%; vertical-align: middle; padding: 5px;">
														<a href="<?php echo esc_attr(get_permalink( $value11 )); ?>">	<img src=" <?php echo esc_attr($image); ?>" width="50" height="150" class="frq_bgt_center"></a>
													</td>
													<?php 
												}

												if ('true' == $frq_bgt_gen_settings['frq_bgt_cartbtn']) { 

													?>
													<td style="width: 27%; vertical-align: middle; padding: 5px;">
														<a href="<?php echo esc_attr(get_permalink( $value11 )); ?>">
															<?php echo esc_html(get_the_title($value11)); ?>
														</a>
													</td>

													<?php
												}
												if ('true' == $frq_bgt_gen_settings['frq_bgt_price']) {
													?>
													<td style="width: 30%; vertical-align: middle; padding: 5px;">
														<span class="price">
															<?php 
															if (in_array($value11, $arrayto_be_checked)) {

																// echo filter_var($price) . '<br><span> (Discount may apply)</span>'; 

																do_action('plugify_allll_contenttt2', $price . '<br><span> (Discount may apply)</span>');
															} else {

																// echo filter_var($price); 
																do_action('plugify_allll_contenttt2', $price);
															}
															?>
														</span>
													</td>

													<?php 
												}
												if ('true' == $frq_bgt_gen_settings['frq_bgt_enable_ad_cart'] ) {

													if ('simple' == $product->get_type()) {
														if ($product->is_in_stock()) {
															?>
															<td style="width: 25%; vertical-align: middle; padding: 5px;">
																<input type="checkbox" id="<?php echo esc_attr($value11); ?>" name="vehicle1" value="<?php echo esc_attr($value11); ?>" style="height: 18px; float: left; width: 40px;cursor: pointer; " class="absb_check_frq">
															</td>
															<?php
														} else {
															?>
															<td style="width: 25%; vertical-align: middle; padding: 5px;">
																<a href="<?php echo esc_attr(get_permalink( $value11 )); ?>"><button type="submit" id="add_to_cart_frq_bgt<?php echo esc_attr($value11); ?>" class="add_to_cart_frq_bgt" value="<?php echo esc_attr($value11); ?>" style="padding: 1px 9px;  background-color: lightgrey; color: #000;">View</button></a>
															</td>
															<?php
														}
													} else if ('variation' == $product->get_type() ) {
														$flag = false;
														foreach ($product->get_attributes() as $key111 => $value111) {
															if ('' == $value111) {
																$flag = true;
															}
														}

														if ( $flag ) {

															?>
															
															<td style="width: 25%; vertical-align: middle; padding: 5px;">
																<a href="<?php echo esc_attr(get_permalink( $value11 )); ?>"><button type="submit" id="add_to_cart_frq_bgt<?php echo esc_attr($value11); ?>" class="add_to_cart_frq_bgt" value="<?php echo esc_attr($value11); ?>" style="padding: 1px 9px;  background-color: lightgrey; color: #000;">View</button></a>
															</td>

															<?php
														} else {
															?>
															<td style="width: 20%; padding: 5px;">
																<input type="checkbox" id="<?php echo esc_attr($value11); ?>" name="vehicle1" value="<?php echo esc_attr($value11); ?>" style="height: 18px; float: left; width: 40px; cursor: pointer;" class="absb_check_frq" >

															</td>
															<?php
														}
													}
												}
												?>


											</tr>
										</table>

									</div>

								</div>


								<?php

							}


						}
						if ( 'true' == $frq_bgt_gen_settings['frq_bgt_enable_ad_cart']) {
							?>
							<p style="margin-top: 10px;"> Select <i style="color: #007cba; font-size: 15px;" class="fas fa-check-square"></i> <i style="color: grey;">To purchase products with <?php echo filter_var(get_the_title(get_the_ID())); ?></i></p>
							<?php
						}
						?>
						</div>
					</div>




					<?php

					break;

			}
		}

		?>


			<script type="text/javascript">
				jQuery('body').on('change', '.absb_check_frq', function() {


					var product_idsss=[];


					jQuery('body').find('.absb_check_frq').each(function() {
						if (jQuery(this).is(':checked')) {

							product_idsss.push(jQuery(this).val());
						}
					})

					jQuery('.hidden_field_for_frq').val(product_idsss)

				})
			</script>





			<style>

				.table_frq_bgt_go {
					/*border: 1px solid lightgrey;*/
					/*border-collapse: collapse;*/
					/*margin-top: 1%;*/
					border-bottom: none;
					border-left: none;
					border-right: none;
				}

				.price {
					font-size: 14px;
				}


				input[type=checkbox]:hover {
					outline:none;
				}
				input[type=checkbox]:focus {
					outline:none;
				}



				@media screen and (max-width: 500px) {


					.table_frq_bgt_go{

						overflow: scroll;
						display: block;
					}
					

				}






			</style>
			<?php


	}
	?>

		<script type="text/javascript">
			jQuery(document).ready(function(){
				setTimeout(function(){


					jQuery( ".variations_form" ).on( "woocommerce_variation_select_change", function () {
						jQuery('.frq_bgt_show_hide_class').hide();


					} );


					jQuery( ".single_variation_wrap" ).on( "show_variation", function ( event, variation ) {
						jQuery('#frq_bgt_show_hide_class'+variation.variation_id).show();



					});
				}, 100);

			});


		</script>

		<?php
}



function absb_set_allowed_rolesxc( $value ) {
	$found_user_role=false;

	if ('0' == get_current_user_ID() || '' == get_current_user_ID()) {
		if ('true' == $value['absb_qty_dict_is_guest']) {

			$found_user_role=true; 	


		}

	} else {
		$user_meta=get_userdata(get_current_user_ID());
		$user_roles=$user_meta->roles;
		foreach ($user_roles as $key_1 => $value_1) {
			if (isset($value['allowedrole']) && 0 < count($value['allowedrole'])) {
				if (in_array($value_1, $value['allowedrole'])) {
					$found_user_role=true;
					break;
				}
			} else {
				$found_user_role=true;
				break;
			}

		}
		
	}

	return $found_user_role;
}
