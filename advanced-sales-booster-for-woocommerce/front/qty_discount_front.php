<?php
add_action( 'woocommerce_before_calculate_totals', 'absb_qty_disct_alter_price_cart', 9999 );

function absb_qty_alter_price_on_quantity_change() {
	absb_qty_disct_alter_price_cart( WC()->cart );
}
add_action( 'woocommerce_after_cart_item_quantity_update', 'absb_qty_alter_price_on_quantity_change' );

function absb_qty_disct_alter_price_cart( $cart ) {
	if ( did_action( 'woocommerce_before_calculate_totals' ) >= 2 ) {
		return;
	}
	if ( is_admin() && ! defined( ‘DOING_AJAX’ ) ) {
		return;
	}

	$absb_rules_data= get_option('absb_rule_settings');

	if ( '' == $absb_rules_data ) {
		$absb_rules_data=array();
	}

	$pro=[];
	$cat=[];
	$who=[];
	foreach ($absb_rules_data as $key => $value) {
		if ('categories' == $value['appliedon']) {
			$cat[]=$value;
		} else if ('products' == $value['appliedon']) {
			$pro[]=$value;
		} else {
			$who[]=$value;
		}
	}

	foreach ($cat as $key => $value) {
		$who[]=$value;
	}
	foreach ($pro as $key => $value) {
		$who[]=$value;
	}
	$idsarray=[];
	foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
		$product = $cart_item['data'];
		$old_price = $product->get_price();
		$main_product_id = $cart_item['product_id'];
		
		$idsarrayx=absb_qty_disct_set_applicable_products_categories($main_product_id, $who);
		foreach ($idsarrayx as $key => $value) {
			$idsarray[$key]=$value;
		}
	}
	foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
		$product = $cart_item['data'];
		// $old_price = $product->get_price();
		$old_price = wc_get_product($product->get_ID())->get_price();
		$main_product_id = $cart_item['product_id'];
		
		if (isset($idsarray[$product->get_id()])) {
			$value=$who[$idsarray[$product->get_id()]];

			$cat_ids = wp_get_post_terms($cart_item['product_id'], 'product_cat', array('fields'=>'ids'));
			$quantityxyz=$cart_item['quantity'];

			$new_price=absb_qty_disct_new_price($old_price, $value, $product->get_id(), $quantityxyz);

			if ('old' != $new_price || '0' == $new_price) {

				WC()->cart->cart_contents[$cart_item_key]['old_price_with_tax'] =wc_get_price_including_tax( $product );
				WC()->cart->cart_contents[$cart_item_key]['old_price'] =$cart_item['data']->get_price();
				WC()->cart->cart_contents[$cart_item_key]['absb_discount'] ='true';
				
				$cart_item['data']->set_price($new_price );
				WC()->cart->cart_contents[$cart_item_key]['new_price'] =$cart_item['data']->get_price();
				WC()->cart->set_session();

			} else {
				$cart_item['data']->set_price($old_price );
				WC()->cart->cart_contents[$cart_item_key]['absb_discount'] ='false';
				WC()->cart->set_session();
			}
		} else {
			$cart_item['data']->set_price($old_price );
			WC()->cart->cart_contents[$cart_item_key]['absb_discount'] ='false';
			WC()->cart->set_session();
		}

	}
}



add_filter( 'woocommerce_cart_item_price', 'absb_qty_disct_woocommerce_cart_item_price_filter_function', 9999, 3 );
function absb_qty_disct_woocommerce_cart_item_price_filter_function( $price, $cart_item, $cart_item_key ) {

	
	$product = $cart_item['data'];
	if (is_cart()) {
		if ('true' == $cart_item['absb_discount']) {

			if ( 'incl' == get_option('woocommerce_tax_display_cart') && 'yes' == get_option('woocommerce_calc_taxes')) {
				if (wc_get_price_including_tax($product) != $cart_item['old_price_with_tax']) {

					return '<strike>' . wc_price($cart_item['old_price_with_tax']) . '</strike> ' . wc_price(  wc_get_price_including_tax( $product ) );
				}
				
			} else {
				if ($cart_item['old_price']!=$cart_item['new_price']) {
					return '<strike>' . wc_price($cart_item['old_price']) . '</strike> ' . wc_price( $cart_item['new_price'] );
				}
				
			}
		}

	}
	if ('true' == $cart_item['absb_discount']) {
		if ( 'incl' == get_option('woocommerce_tax_display_cart') && 'yes' == get_option('woocommerce_calc_taxes')) {


			return wc_price(  wc_get_price_including_tax( $product ) );
			

		} else {

			return wc_price( $cart_item['new_price'] );
			
			
		}
	}

	return $price;
}


function absb_qty_disct_new_price( $old_price, $value, $current_product_id, $quantity ) {

	$new_price='old';
	foreach ($value['startrange'] as $key__1 => $value__1) {

		$absb_qty_disct_end_range=$value['endrange'][$key__1];
		

		if ($quantity>=$value__1 && $quantity<=$absb_qty_disct_end_range) {
			if ('fix' == $value['discounttype'][$key__1]) {
				$new_price=$old_price-$value['discountamount'][$key__1];
				if (0>$new_price) {
					$new_price=0;
				}
				return $new_price;
			} else if ('per' == $value['discounttype'][$key__1]) {

				$new_price=$old_price/100;
				$new_price=$new_price*$value['discountamount'][$key__1];
				$new_price=$old_price-$new_price;
				if (0>$new_price) {
					$new_price=0;
				}

				return $new_price;

			} else if ('revised' == $value['discounttype'][$key__1]) {
				$new_price=$value['discountamount'][$key__1];
				if (0>$new_price) {
					$new_price=0;
				}
				return $new_price;
			}



		}

	}
	return 'old';
}


function absb_set_allowed_roles( $value ) {
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




function absb_qty_disct_set_applicable_products_categories( $main_product_id, $who ) {

	$product=wc_get_product($main_product_id);
	$typeee=$product->get_type();
	$idsarray=[];
	
	
	
	$source='';
	foreach ($who as $rulenumber => $ruledata) {
		$found_user_role=absb_set_allowed_roles($ruledata);
		
		if ('true' == $ruledata['activaterule'] && $found_user_role) {

			if ('products' == $ruledata['appliedon']) {
				if ('variable' == $typeee) {
					$variations = $product->get_available_variations();
					$variations_id = wp_list_pluck( $variations, 'variation_id' );

					if (isset($ruledata['procat_ids']) && in_array($main_product_id, $ruledata['procat_ids'])) {
						foreach ($variations_id as $keys => $var_id) {
							if (!isset($idsarray[$var_id])) {

								$idsarray[$var_id]=$rulenumber;

							} else {
								if ('categories' == $source ) {
									$idsarray[$var_id]=$rulenumber;

								}
							}
						}
						$source='variable';
					} else {
						foreach ($variations_id as $keys => $var_id) {
							if (isset($ruledata['procat_ids']) && in_array($var_id, $ruledata['procat_ids'])) {

								$idsarray[$var_id]=$rulenumber;
								$source='variation';


							}
						}

					}



				} else {
					if (isset($ruledata['procat_ids']) && in_array($main_product_id, $ruledata['procat_ids'])) {
						if (!isset($idsarray[$main_product_id])) {
							$idsarray[$main_product_id]=$rulenumber;
							$source='simple';
						} else {
							if ('categories' == $source ) {
								$idsarray[$main_product_id]=$rulenumber;
								$source='simple';
							}

						}


					} 
				}




			} else if ('categories' == $ruledata['appliedon']) {
				if (isset($ruledata['procat_ids'])) {
					$cat_ids = wp_get_post_terms($main_product_id, 'product_cat');
					foreach ($cat_ids as $key1 => $value1) {

						if (is_array($ruledata['procat_ids']) && ( in_array($value1->term_id, $ruledata['procat_ids']) || in_array($value1->parent, $ruledata['procat_ids']) ) ) {
							if ('variable' == $typeee) {
								$variations = $product->get_available_variations();
								$variations_id = wp_list_pluck( $variations, 'variation_id' );


								foreach ($variations_id as $keys => $var_id) {
									if (!isset($idsarray[$var_id])) {
										$idsarray[$var_id]=$rulenumber;

									} else {
										if ('whole' == $source) {
											$idsarray[$var_id]=$rulenumber;

										}
									}

								}

								$source='categories';


							} else {
								if (!isset($idsarray[$main_product_id])) {
									$idsarray[$main_product_id]=$rulenumber;
									$source='categories';
								} else {
									if ('whole' == $source) {
										$idsarray[$main_product_id]=$rulenumber;
										$source='categories';
									}
								}



							}
							break;
						}
					}
				}

			} else {
				if ('variable' == $typeee) {
					$variations = $product->get_available_variations();
					$variations_id = wp_list_pluck( $variations, 'variation_id' );



					foreach ($variations_id as $keys => $var_id) {
						$idsarray[$var_id]=$rulenumber;

					}
					$source='whole';




				} else {

					$idsarray[$main_product_id]=$rulenumber;
					$source='whole';


				}
			}
		}


		
		
	}

	return $idsarray;
}


function absb_qty_disct_discounted_price ( $value, $id ) {
	
	$product=wc_get_product($id);
	$old_price=$product->get_price();
	$lowest_price=array();
	foreach ($value['startrange'] as $key__1 => $value__1) {		
		$new_price='';
		if ('fix' == $value['discounttype'][$key__1]) {
			$new_price=$old_price-$value['discountamount'][$key__1];
		} else if ('per' == $value['discounttype'][$key__1]) {
			$new_price=$old_price/100;
			$new_price=$new_price*$value['discountamount'][$key__1];
			$new_price=$old_price-$new_price;

		} else if ('revised' == $value['discounttype'][$key__1]) {
			$new_price=$value['discountamount'][$key__1];
		}
		if (0>$new_price) {
			$new_price=0;
		}
		array_push($lowest_price, $new_price);
	}
	return min($lowest_price); 
}

add_action( 'woocommerce_checkout_create_order_line_item', 'absb_qty_disct_sending_data_with_order', 9999, 4 );

function absb_qty_disct_sending_data_with_order( $item, $cart_item_key, $values, $order ) {

	$product = $values['data'];
	if ('true' == $values['absb_discount']) {
		if ( 'incl' == get_option('woocommerce_tax_display_cart') && 'yes' == get_option('woocommerce_calc_taxes')) {
			if (wc_get_price_including_tax($product) != $values['old_price_with_tax']) {
				
				$item->add_meta_data( __( 'Quantity Discount'), 'Applied', true );
				$item->add_meta_data( __( 'Original Price (Per Item)'), wc_price($values['old_price_with_tax']), true );
			}
			
		} else {
			if ($values['old_price']!=$values['new_price']) {

				$item->add_meta_data( __( 'Quantity Discount'), 'Applied', true );
				$item->add_meta_data( __( 'Original Price (Per Item)'), wc_price($values['old_price']), true );
			}
			
		}
	}
}


add_action('wp_head', 'absb_select_hook_to_use');

function absb_select_hook_to_use() {

	$rules_settings_quantity = get_option('absb_rule_settings');
	$general_settings_quantity = get_option('absb_gen_settings_for_quantity_discount');

	$visual_hook = 'woocommerce_before_add_to_cart_button';
	if (is_product() ) {
		$product = wc_get_product(get_the_ID());
		$product_price = $product->get_price();
		$product_sale_price = $product->get_sale_price();


		if ('beforeadd' == $general_settings_quantity['location']) {
			$visual_hook = 'woocommerce_before_add_to_cart_form';
		} else if ('afteradd' == $general_settings_quantity['location']) {
			$visual_hook = 'woocommerce_after_add_to_cart_form';
		} else if ('aftersummary' == $general_settings_quantity['location']) {
			$visual_hook = 'woocommerce_after_single_product_summary';
		} 

		

		
		add_action($visual_hook, 'absb_create_table');
	}
}


function absb_create_table() {

	$general_settings_quantity = get_option('absb_gen_settings_for_quantity_discount');
	$rules_settings_quantity = get_option('absb_rule_settings');


	$main_product_id=get_the_ID();
	$idsarray=[];

	if ( '' == $rules_settings_quantity ) {
		$rules_settings_quantity=array();
	}
	$pro=[];
	$cat=[];
	$who=[];
	foreach ($rules_settings_quantity as $key => $value) {
		if ('categories' == $value['appliedon']) {
			$cat[]=$value;
		} else if ('products' == $value['appliedon']) {
			$pro[]=$value;
		} else {
			$who[]=$value;
		}
	}


	foreach ($cat as $key => $value) {
		$who[]=$value;
	}
	foreach ($pro as $key => $value) {
		$who[]=$value;
	}
	$idsarray=absb_qty_disct_set_applicable_products_categories($main_product_id, $who);


	?>

	<?php
	foreach ($idsarray as $prodkey => $rule_id) {

		$value = $who[$rule_id];
		$product = wc_get_product($prodkey);
		$product_price = $product->get_price();
		$product_sale_price = $product->get_sale_price();


		$new_price = ' ';

		$styleee= '';
		if ($product->get_type() == 'variable') {
			$styleee = 'display:none;';
		}
		if ($product->get_type() == 'variation') {
			$styleee = 'display:none;';
		}


		?>
		<div class="qty_dis_table" id="hidden_table_for<?php echo filter_var($prodkey); ?>" style=" <?php echo filter_var($styleee); ?>">

			<strong><?php echo esc_html($general_settings_quantity['tabletitle']); ?></strong>

			<table style="text-align: center !important;">



				<tr>
					<th class="absb_th"><?php echo esc_html($general_settings_quantity['heading_1']); ?></th>
					<th class="absb_th"><?php echo esc_html($general_settings_quantity['heading_2']); ?></th>
					<th class="absb_th"><?php echo esc_html($general_settings_quantity['heading_3']); ?></th>
				</tr>
				<tbody>
					<?php
					foreach ($value['startrange'] as $key1 => $value1) {

						?>
						<tr>
							<td>
								<?php echo filter_var($value['startrange'][$key1] . '-' . $value['endrange'][$key1]); ?>
							</td>
							<td>
								<?php 
								if ('per' == $value['discounttype'][$key1]) {
									echo filter_var($value['discountamount'][$key1]) . '  %'; 	
								} else if ('fix' == $value['discounttype'][$key1] ) {
									echo filter_var(wc_price($value['discountamount'][$key1])) ;
								} else {
									echo  filter_var($product_price - $value['discountamount'][$key1]);
								}

								?>
							</td>
							<td>
								<?php	
								if ('fix' == $value['discounttype'][$key1]) {

									$new_price = $product_price - $value['discountamount'][$key1];
									if ($new_price<0) {
										echo filter_var(wc_price('0'));
									} else {
										echo filter_var(wc_price($new_price));
									}

								} else if ('per' == $value['discounttype'][$key1]) {

									$new_price = $product_price - ( $value['discountamount'][$key1] / 100 ) * $product_price;

									if ($new_price<0) {
										echo filter_var(wc_price('0'));
									} else {

										echo filter_var(wc_price($new_price));
									}

								} else if ('revised' == $value['discounttype'][$key1]) {

									$new_price = $value['discountamount'][$key1];
									if ($new_price<0) {
										echo filter_var(wc_price('0'));
									} else {
										echo filter_var(wc_price($new_price));
									}
								}  
								?>
							</td>
						</tr>
						<?php
					}

					?>
				</tbody>
			</table>
		</div>
		<?php
	}

	?>


	<style type="text/css">

		.qty_dis_table td {
			background-color: <?php echo esc_attr($general_settings_quantity['table_bg_color']); ?>!important; 
			color: <?php echo esc_attr($general_settings_quantity['table_text_color']); ?>!important;

		}
		.absb_th {
			background-color: <?php echo esc_attr($general_settings_quantity['head_bg_color']); ?>!important;
			color: <?php echo esc_attr($general_settings_quantity['head_text_color']); ?>!important;

		}


	</style>

	<script type="text/javascript">
		jQuery(document).ready(function(){

			jQuery( ".variations_form" ).on( "woocommerce_variation_select_change", function () {
				jQuery('.qty_dis_table').hide();
			} );


			jQuery( ".single_variation_wrap" ).on( "show_variation", function ( event, variation ) {
				

				jQuery('#hidden_table_for'+variation.variation_id).show();
				
			});
		});
	</script>
	<?php
}




function plgfy_absb_qty_disc_alter_price_on_quantity_change() {
	absb_qty_disct_alter_price_cart( WC()->cart );
}
add_action( 'woocommerce_after_cart_item_quantity_update', 'plgfy_absb_qty_disc_alter_price_on_quantity_change' );
