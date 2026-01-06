<?php
$gen_settings = get_option('absb_saved_general_settings_for_price_negotiate');

if (is_array($gen_settings) && isset($gen_settings['offer_show_onshop'])) {
	if ('true' == $gen_settings['offer_show_onshop']) {
		add_action('woocommerce_after_shop_loop_item', 'absb_price_nego_shop_button_create');
	}
}

function absb_price_nego_shop_button_create() {

	$rule_settings = get_option('absb_offer_rule_settings');
	$gen_settings = get_option('absb_saved_general_settings_for_price_negotiate');
	$condition = false;
	if (is_array($rule_settings)) {
		foreach ($rule_settings as $key => $value) {

			if ('true' == $value['activate_offer_rule']) {

				if ('products' ==  $value['offer_appliedon']) {
					$products = $value['offer_procat_ids'];

				} else if ('categories' == $value['offer_appliedon']) {
					$terms = get_the_terms( get_the_ID(), 'product_cat' );
					if ( ! empty( $terms ) ) {
						foreach ( $terms as $term ) {
							$term_id = $term->term_id;
							$parent_id = $term->parent;

							if (is_array($value['offer_procat_ids']) && ( in_array( $term_id, $value['offer_procat_ids'] ) || in_array( $parent_id, $value['offer_procat_ids']) ) ) {
								$products[] = get_the_ID();
							}

						}
					}
				}
			}

			if ( is_user_logged_in() ) {
				$user_meta=get_userdata(get_current_user_ID());
				$user_roles=$user_meta->roles;

				foreach ($user_roles as $keyccc => $valueccc) {
					if (is_array($value['offer_roles']) ) {
						if (in_array($valueccc, $value['offer_roles'] )) {
							$condition = true;
						}
					} else if ('' == $value['offer_roles'] ) {

						$condition = true;
					}
				}

			} else {
				if ('true' == $value['absb_prc_neg_is_guest']) {
					$condition=true; 	
				}
			}


		}
	}

	if ($condition) {

		if (is_array($products) && in_array(get_the_ID(), $products )) {

			$redirect_uri = add_query_arg ('is_for_offer', 'true', get_permalink (get_the_ID()))

			?>

		<a href="<?php echo esc_attr($redirect_uri); ?>">	<button type="button" id="makeoffer_onshop" style="margin-bottom: 4px; background-color: <?php echo esc_attr($gen_settings['create_offer_btn_bg_color']); ?>;  color:<?php echo esc_attr($gen_settings['create_offer_btn_text_clr']); ?>;"><?php echo esc_html($gen_settings['create_offer_btn_text']); ?></button> </a>
			<?php

		}
	}
}

add_action('woocommerce_after_add_to_cart_button', 'absb_crate_offer');


function absb_crate_offer() {

	$gen_settings = get_option('absb_saved_general_settings_for_price_negotiate');
	$rule_settings = get_option('absb_offer_rule_settings');

	global $wpdb;
	$offer_to_show_ids=[];
	if (is_user_logged_in()) {

		$current_userr_id_is = get_current_user_ID();

		$results = $wpdb->get_results( $wpdb->prepare('SELECT ID FROM  ' . $wpdb->prefix . 'posts u , ' . $wpdb->prefix . 'postmeta m WHERE post_status = "publish" AND post_type = "absb_custom_offers" AND u.ID = m.post_id AND  m.meta_key = "user_id" AND m.meta_value  = %s ORDER BY ID DESC', $current_userr_id_is));

		$offer_to_show_ids=[];

		foreach ($results as $key => $value) {
			$product=wc_get_product(get_the_ID());
			if ('variable' == $product->get_type()) {
				$variations=$product->get_available_variations();
				$variations_id = wp_list_pluck( $variations, 'variation_id' );
				foreach ($variations_id as $keyforprogress => $valueforprogress) {
					if (get_post_meta($value->ID, 'product_id', true) == $valueforprogress) {
						$offer_to_show_ids[]=$value->ID;
					}
				}
			} else {
				if (get_the_ID() == get_post_meta($value->ID, 'product_id', true)) {
					$offer_to_show_ids[]=$value->ID;
				}
			}


		}

		$offer_to_show_id=$offer_to_show_ids[0];

	}

	$rule_settings = get_option('absb_offer_rule_settings');
	$condition = false;
	if (is_array($rule_settings)) {
		foreach ($rule_settings as $key => $value) {

			if ('products' ==  $value['offer_appliedon']) {
				$products = $value['offer_procat_ids'];

			} else if ('categories' == $value['offer_appliedon']) {

				$terms = get_the_terms( get_the_ID(), 'product_cat' );

				if ( ! empty( $terms ) ) {
					foreach ( $terms as $term ) {
						$term_id = $term->term_id;
						$parent_id = $term->parent;

						if ( is_array( $value['offer_procat_ids'] ) && ( in_array( $term_id, $value['offer_procat_ids'] ) || in_array($parent_id, $value['offer_procat_ids'] ) ) ) {
							$products[] = get_the_ID();
						}

					}
				}

			}







			if ('true' == $value['activate_offer_rule']) {


				if ('0' == get_current_user_ID() || '' == get_current_user_ID()) {
					if ('true' == $value['absb_prc_neg_is_guest']) {
						$condition=true; 	
					}


				} else if ( is_user_logged_in() ) {
					$user_meta=get_userdata(get_current_user_ID());
					$user_roles=$user_meta->roles;

					foreach ($user_roles as $keyccc => $valueccc) {
						if (is_array($value['offer_roles']) ) {
							if (in_array($valueccc, $value['offer_roles'] )) {
								$condition = true;
							}
						} else if ('' == $value['offer_roles'] ) {

							$condition = true;
						}
					}

				} 
			}
		}
	}

	global $current_user;
	wp_get_current_user();
	$user_name = $current_user->user_login;
	$user_email = $current_user->user_email;


	$product=wc_get_product(get_the_ID());
	if ('simple' == $product->get_type()) {
		$is_out_of_stock='false';
		$_backorders=get_post_meta(get_the_ID(), '_backorders', true);
		$stock_status=get_post_meta(get_the_ID(), '_stock_status', true);
		if ('instock' == $stock_status) {
			$stock_count=get_post_meta(get_the_ID(), '_stock', true);
			$_manage_stock=get_post_meta(get_the_ID(), '_manage_stock', true);
			$_backorders=get_post_meta(get_the_ID(), '_backorders', true);
			if ('no' != $_manage_stock && 0 >= $stock_count && 'no' == $_backorders) {
				$is_out_of_stock='true';
			} 
		} else if ('outofstock' == $stock_status && 'no' == $_backorders) {

			$is_out_of_stock='true';
		}



	}
	
	if ($condition) {




		if (is_array($products) && in_array(get_the_ID(), $products )) {



			if ('variable' != $product->get_type() && 'grouped' != $product->get_type()) {
				?>

				<button type="button" id="makeoffer" style="background-color: <?php echo esc_attr($gen_settings['create_offer_btn_bg_color']); ?>;  color:<?php echo esc_attr($gen_settings['create_offer_btn_text_clr']); ?>;"><?php echo esc_html($gen_settings['create_offer_btn_text']); ?></button>

				<?php 
			} else {

				?>
				<button disabled type="button" id="makeoffer" style="background-color: <?php echo esc_attr($gen_settings['create_offer_btn_bg_color']); ?>;  color:<?php echo esc_attr($gen_settings['create_offer_btn_text_clr']); ?>;"><?php echo esc_html($gen_settings['create_offer_btn_text']); ?></button>
				<?php

			}
			?>
			<div id="button_show_offer_divvvvv">
				<?php
				if (!empty($offer_to_show_ids)) {
					?>
					<button type="button" id="viewoffer_absb" value="<?php echo esc_attr($offer_to_show_ids[0]); ?>" style="background-color: <?php echo esc_attr($gen_settings['view_offer_btn_bg_color']); ?>;  color:<?php echo esc_attr($gen_settings['view_offer_btn_text_clr']); ?>; margin-top: 2%; width: 100%;"><?php echo esc_html($gen_settings['view_ofr__btn_txt']); ?></button>
					<?php
				}
				?>
			</div>
			<?php
		}
	}
	?>



	<div class="modelpopup1" id="show_offer_popup" role="dialog" style="display: none;">
		<div class="modal-dialog">
			<div class="modal-content1">
				<div class="modal-header" style="">
					<button type="button" class="close1" data-dismiss="modal" style="font-size: 28px; color: red;">&times;</button>

					<h2 class="modal-title" style="color: #000 !important; line-height: 0.618; "><?php echo esc_html($gen_settings['view_popup_headtxt']); ?></h2><hr>
				</div>
				<div class="modal-body1 " >
				</div>

			</div>
		</div>
	</div>

	<?php


	if (!is_user_logged_in()) {
		?>

		<div class="modelpopup1" id="absb_offer_pop_up" role="dialog" style="display: none;">
			<div class="modal-dialog">
				<div class="modal-content1">
					<div class="modal-header" style="">
						<button type="button" class="close1" id="closet4321" data-dismiss="modal" style="margin-top: -1%; color: red !important;">&times;</button>

						<h2 class="modal-title" style="color: #000 !important; ">Login Required</h2><hr>


						
						
					</div>
					<div class="modal-body1 animate__animated animate__flash" >

						You can avail negotiation request feature and get the discount by logging into the site. Please <a href="<?php echo filter_var(get_permalink( wc_get_page_id( 'myaccount' )) ); ?>"><i><u>Log in</u></i></a> here


					</div>

				</div>
			</div>
		</div>

		<?php

	} else {
		?>


	<div class="modal fade" id="absb_offer_pop_up" role="dialog" style="display: none;">
		<div class="modal-dialog">
			<div class="modal-content" style="background-color: #fff !important;border-radius: 3px !important;">
				<div class="modal-header" style="">
					<div style="display:inline-flex;width: 100%;">
						<h2 class="modal-title" style="padding: 10px 20px 5px 20px;width: 97.5%;color: #000;"><?php echo esc_html($gen_settings['popoup_head_txt']); ?></h2><hr>
						<label class="close" id="ffffffff" style="color: red;" data-dismiss="modal">&times;</label>
						<hr>

					</div>
					<hr>
				</div>
				<div class="modal-body" style="margin-top:2%;">
					<table class="ruletblrowss" style="width:100%;border: unset !important;">
						<?php
						$product=wc_get_product(get_the_ID());
						if ( 'variable' == $product->get_type() ) {
							$variations   = $product->get_available_variations();
							$variations_id = wp_list_pluck( $variations, 'variation_id' );

							foreach ( $variations_id as $keyforprogress => $valueforprogress ) {
								$old_price = get_post_meta( $valueforprogress, '_sale_price', true );
								if ( '' == $old_price || '0' == $old_price ) {
									$old_price = get_post_meta( $valueforprogress, '_regular_price', true );
								}
								$producttt = wc_get_product( $valueforprogress );
								?>

								<tr style="display:none;border: unset !important;" class="all_trss" id="thistr<?php echo esc_attr( $valueforprogress ); ?>">
									<td colspan="2" style="border: unset !important;">
										<div style="display:inline-flex;width:100%;margin-bottom: 10px;">
											<?php
											$image = wp_get_attachment_image_src( get_post_thumbnail_id( $producttt->get_ID() ), 'single-post-thumbnail' );
											if ( empty( $image ) || '' == $image[0] ) {
												$placeholder_image = get_option( 'woocommerce_placeholder_image' );
												$image             = wp_get_attachment_image_src( $placeholder_image, 'woocommerce_thumbnail' );
												$image             = $image[0];
											} else {
												$image = $image[0];
											}


											if ( $producttt->is_type( 'variation' ) ) {
												$parent              = wc_get_product( $producttt->get_parent_id() );
												$variation_attributes = wc_get_formatted_variation( $producttt, true );
												$title               = $parent->get_name() . ' - ' . $variation_attributes;
											} else {
												$title = $producttt->get_name();
											}
											?>

											<img src="<?php echo esc_url( $image ); ?>" style="margin-right: 10px;width: 100px !important;height: 80px !important;" >
											<strong style="color: black;"><?php echo esc_html( $title ); ?></strong>
											<?php echo ' (' . wp_kses_post( wc_price( $producttt->get_price() ) ) . ')'; ?>
										</div>
									</td>
								</tr>
								<?php
							}
						} else {
							?>

							<tr style="border: unset !important;">
								<td colspan="2" style="border: unset !important;">
									<div style="display:inline-flex;width:100%;margin-bottom: 10px;">
										<?php
										$image = wp_get_attachment_image_src( get_post_thumbnail_id( get_the_ID() ), 'single-post-thumbnail' );
										if (empty($image) || ''==$image[0]) {
											$placeholder_image = get_option( 'woocommerce_placeholder_image' );
											$image = wp_get_attachment_image_src( $placeholder_image, 'woocommerce_thumbnail' );
											$image=$image[0];
										} else {
											$image=$image[0];
										}

										$product=wc_get_product(get_the_ID());
										?>
										<img src="<?php echo filter_var($image); ?> " style="margin-right: 10px;width: 100px !important;height: 80px !important;" >
										<strong style="color: black;"><?php echo filter_var(get_the_title(get_the_ID()) ); ?></strong>

										<?php echo filter_var(' (' . wc_price($product->get_price()) . ')'); ?>

									</div>

								</td>

							</tr>	
							<?php

						}
						?>
						<tr >
							<td>
								<strong style="color: black;">Name<span style="color:red;">*</span></strong> <br><input type="text" id="name" style="width:90%; color: black; text-align: left;" value="<?php echo filter_var($user_name); ?>">
							</td>
							<td>
								<strong style="color: black;">Email<span style="color:red;">*</span></strong><br><input type="email" id="email" style="width:100%; color: black; text-align: left;" value="<?php echo filter_var($user_email); ?>">
							</td>
						</tr>	
						<tr >
							<td>
								<strong style="color: black;">Desired Quantity<span style="color:red;">*</span></strong><br><input type="number" id="qtty" style="width:90%; color: black; text-align: left;">
							</td>
							<td>
								<strong style="color: black;">Desired Price<span style="color:red;">*</span></strong><br><input type="number" id="uprice" style="width:100%; color: black; text-align: left;">
							</td>
						</tr>
						<tr>
							<td colspan="2" >
								<strong style="color: black;">Additional Note</strong><br><textarea id="txtarea" style="width:100%; color: black; text-align: left;" ></textarea>
							</td>

						</tr>
					</table>






				</div>

				<div class="modal-footer" style="text-align: right; margin-right: 3%;">
					<button type="button" id="requestprice" style="background-color: #007cba; cursor: pointer; color: white; padding: 9px 30px; border:none; border-radius: 5px;"><i class="icon-save"></i>Send Offer</button>

				</div>

				<br>
			</div>
		</div>
	</div>	

<style type="text/css">
	#absb_datatable_offer_wrapper {
		margin: 20px !important;
	}
	</style>
		<?php 
	}

	$product=wc_get_product(get_the_ID());
	$type=$product->get_type();

	if (is_product() && isset($_GET['is_for_offer'])) {

		if ( 'true' == $_GET['is_for_offer']) {
			if ('variable' != $product->get_type()) {
				?>
				<script type="text/javascript">
					jQuery(document).ready(function() {
						jQuery('#absb_offer_pop_up').show();
					});
				</script>
				<?php
			}
		}

	}
	?>
	<script type="text/javascript">
		jQuery(document).ready(function(){

			jQuery('body').on('click', '.reset_variations' , function(){
				jQuery('.all_trss').each(function(){
					jQuery(this).hide();
				});
				jQuery('#makeoffer').attr('disabled','disabled');
			});
			jQuery( ".variations_form" ).on( "woocommerce_variation_select_change", function () {

				jQuery('.all_trss').each(function(){
					jQuery(this).hide();
				});
				jQuery('#makeoffer').attr('disabled','disabled');
				
			} );
			jQuery( ".single_variation_wrap" ).on( "show_variation", function ( event, variation ) {
				jQuery('.all_trss').each(function(){
					jQuery(this).hide();
				});

				jQuery('#makeoffer').removeAttr('disabled');
				jQuery('#viewoffer_absb').val(variation.variation_id);
				jQuery('#thistr'+variation.variation_id).show();
				setTimeout(function(){
					if (jQuery('.single_add_to_cart_button ').hasClass("wc-variation-is-unavailable")) {


						jQuery('#makeoffer').attr('disabled','disabled');
					}else {
						jQuery('#makeoffer').removeAttr('disabled');
					}
				},100)

			});

		});



		jQuery('body').on('click', '#makeoffer', function(){

			jQuery('#absb_offer_pop_up').show();

		})
		jQuery('body').on('click', '.close', function(){

			jQuery('#absb_offer_pop_up').hide();

		})

		jQuery('body').on('click', '#requestprice', function(){

			var req_name = jQuery('#name').val();
			var req_email = jQuery('#email').val();
			var req_quantity = jQuery('#qtty').val();
			var req_price = jQuery('#uprice').val();
			var req_note = jQuery('#txtarea').val();

			function validateEmail(req_email) {
				var emailReg = /^([\w-\.]+@([\w-]+\.)+[\w-]{2,4})?$/;
				return emailReg.test( req_email );
			}


			if( !validateEmail(req_email)) {
			 alert('Please enter valid email');
			 return; 
			}


			if (req_name=='' || req_email=='' || req_quantity=='' || req_price=='') {
				alert('Please fill all fields');
				return;
			}


			var prod_id='';
			if ('simple' == '<?php echo filter_var($type); ?>') {
				prod_id='<?php echo filter_var(get_the_ID()); ?>';
			} else{
				prod_id= jQuery('input.variation_id').val();

				if(prod_id==0){
					alert('Please select variation and try again!');

					return;
				}
			}

			jQuery('#requestprice').html('<i class="fa fa-spinner fa-spin" id="spinbutton"></i> Please Wait');
			jQuery('#requestprice').prop('disabled', true);
			jQuery('body').css('cursor' , 'wait');

			jQuery.ajax({
				url : '<?php echo filter_var(admin_url('admin-ajax.php')); ?>',

				type : 'post',
				data : {
					action : 'absb_sending_price_request_to_db',
					req_name:req_name,
					product_id : prod_id,    

					req_email:req_email,
					req_quantity:req_quantity,

					req_price:req_price,
					req_note:req_note



				},
				success : function( response ) {
					window.onbeforeunload = null;
					


					jQuery('.close').click();



					jQuery('#name').val('');
					jQuery('#email').val('');
					jQuery('#qtty').val('');
					jQuery('#uprice').val('');
					jQuery('#txtarea').val('');


					jQuery('#popupfor_login_guest').show();



					jQuery('#requestprice').html('Request Desired Price');
					jQuery('#requestprice').prop('disabled', false);
					jQuery('body').css('cursor' , 'unset');
// console.log(response);
					jQuery('#button_show_offer_divvvvv').html('<button type="button" id="viewoffer_absb" value="'+response+'" style="background-color: <?php echo esc_attr($gen_settings['view_offer_btn_bg_color']); ?>;  color:<?php echo esc_attr($gen_settings['view_offer_btn_text_clr']); ?>; margin-top: 2%; width: 100%;"><?php echo esc_html($gen_settings['view_ofr__btn_txt']); ?></button>');



				}


			})


		})



		jQuery('body').on('click', '.close1' , function(){

			jQuery('#show_offer_popup').hide();
			jQuery('#absb_offer_pop_up').hide();


		})
		jQuery('body').on('click', '#viewoffer_absb' , function(){
			jQuery('#show_offer_popup').show();

			
			jQuery.ajax({
				url : '<?php echo filter_var(admin_url('admin-ajax.php')); ?>',

				type : 'post',
				data : {
					action : 'absb_view_offer_popup',    
					

					id_to_ammend : jQuery(this).val(),    


				},
				success : function( response ) {

					jQuery('#show_offer_popup').find('.modal-body1').html(response);




				}

			});

		});



	</script>

	<style>

		.first_row_tablee td, .first_row_tablee th {

			padding: 5px !important;
			border: 1px solid #ddd !important;
		}
		.active_modal{
			display: block !important;
			opacity: 1 !important;
		}
		.modal {
			overflow-y:scroll;
			overflow: auto !important;
			display: none; 
			position: fixed;
			z-index: 9999;		
			left: 0;
			top: 0;
			width: 100%; 
			height: 100%; 
			overflow: auto; 
			background-color: rgb(0,0,0); 
			background-color: rgba(0,0,0,0.4); 
			padding: 3%; 

		}


		.modal-content {

			/*background-color: #fefefe;*/
			margin: auto;
			padding: 20px;
			/*border: 2px solid #ae7b3b;*/
			width: 60%;
			border-radius: 4px;
		}
		.modal-body {
			padding: 1px 20px 10px 20px;
		}
		.modal-footer {
			padding: 1px 20px;
			background-color: #fdfdfd;		
		}
		.close {
			margin-right: 15px !important;
			float: right;
			font-size: 28px;
			font-weight: bold;
			opacity: 1 !important;
		}

		.close:hover,
		.close:focus {

			text-decoration: none;
			cursor: pointer;
		}


		#closet4321:hover,
		#closet4321:focus {
			outline: none !important;
			background-color: white !important;
			border: none !important;
		}
		.modelpopup1 {
			display: none;
			position: fixed; 
			z-index: 9999;		
			left: 0;
			overflow-y:scroll;
			overflow: auto !important;
			top: 0;
			width: 100%; 
			height: 100%; 
			overflow: auto; 
			background-color: rgb(0,0,0); 
			background-color: rgba(0,0,0,0.4);
			padding: 3%;
			border: 1px solid #007cba;
			border-radius: 8px;
		}
		.modal-content1 {
			background-color: #fefefe;
			margin: auto;
			padding: 20px;
			/*border: 2px solid #ae7b3b;*/
			width: 60%;
			border-radius: 4px;
		}
		.close1 {
			color: rgba(0,0,0,0.3);
			float: right;
			font-size: 24px;
			font-weight: bold;
			background-color: white;
			border-style: none;
			padding: 0px !important;
		}
		.close1:hover,
		.close1:focus {
			
			text-decoration: none;
			cursor: pointer;
		}






			@media screen and (max-width: 1130px) {

			#makeoffer {
				width: 100% !important;
				margin-top: 2% !important;
			}



		}



		@media screen and (max-width: 700px) {

			.modal-content, .modal-content1 {
				width: 100% !important;
				border:none !important
				padding:unset !important;
				/*margin-top: 2% !important;*/
			}

			.popup_offer_statusss {
				display: unset!important;
				width: unset !important;
			}



		}


	</style>
	<?php
}


add_filter( 'woocommerce_cart_item_price', 'absb_cart_items_price_flter_function', 9999, 3 );
function absb_cart_items_price_flter_function( $price, $cart_item, $cart_item_key ) {

	$product = $cart_item['data'];

	$old_price = $product->get_price();
	// $approval_time = get_post_meta($value['post_id'], 'time', true);
	// echo filter_var($approval_time);

	$current_product_id = $product->get_id();
	$user_data=get_user_meta(get_current_user_ID(), 'is_approved', true);
	if ('' == $user_data) {
		$user_data=array();
	}
	krsort($user_data);

	foreach ($user_data as $key => $value) {
		if ('accepted' == $value['status'] && 'accepted' == get_post_meta($value['post_id'], 'status', true)) {
			if ($value['product_id'] == $current_product_id  && 'publish' == get_post_status($value['post_id']) ) {

				$approval_time = $user_data[0]['time'];

				if ($cart_item['quantity'] >= $value['qty']) {

					$end_time=get_post_meta($value['post_id'], 'changeprice_till', true);
					$newprice= get_post_meta($value['post_id'], 'absb_approved_price', true );

					if (time()<$end_time) {

						if ($current_product_id == $value['product_id'] ) {

							return '<strike>' . wc_price($cart_item['offer_prev_price']) . '</strike><br>' . wc_price(get_post_meta($value['post_id'], 'absb_approved_price', true )) ;

						}
					}
				}
			}
		}
	}

	return $price;
}


add_filter( 'woocommerce_get_price_html', 'absb_change_html_price_for_flat_discount', 100, 2 );
function absb_change_html_price_for_flat_discount( $price, $product) {

	$user_data=get_user_meta(get_current_user_ID(), 'is_approved', true);
	if ('' == $user_data) {
		$user_data=array();
	}
	krsort($user_data);
	

	foreach ($user_data as $key => $value) {
		if ('accepted' == $value['status'] && 'accepted' == get_post_meta($value['post_id'], 'status', true)) {


			$approval_time = $user_data[0]['time'];
			if (get_the_ID() == $value['product_id'] ) {
				$end_time=get_post_meta($value['post_id'], 'changeprice_till', true) ;
				

				if (time()<$end_time) {

					

					return '<strike>' . $price . '</strike><br>' . wc_price(get_post_meta($value['post_id'], 'absb_approved_price', true )) . ' Minimum Quantity: ' . $value['qty'] ;
				}

			}


		}
	}

	return $price;
	$current_product_id=get_the_ID();
}


function absb_price_nigo_alter_price_on_quantity_change() {
	absb_price_nego_alter_price_cart( WC()->cart );
}
add_action( 'woocommerce_after_cart_item_quantity_update', 'absb_price_nigo_alter_price_on_quantity_change' );

add_action( 'woocommerce_before_calculate_totals', 'absb_price_nego_alter_price_cart', 9999 );
function absb_price_nego_alter_price_cart( $cart  ) {
	if ( did_action( 'woocommerce_before_calculate_totals' ) >= 2 ) {
		return;
	} 
	if ( is_admin() && ! defined( ‘DOING_AJAX’ ) ) {
		return;
	}

	foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
		$product = $cart_item['data'];
		$old_price = $product->get_price();
		$current_product_id = $product->get_id();

		if (!is_user_logged_in()) {
			return;
		}

		$user_data=get_user_meta(get_current_user_ID(), 'is_approved', true);
		if ('' == $user_data) {
			$user_data=array();
		}
		krsort($user_data);

		

		$approval_time = $user_data[0]['time'];

		foreach ($user_data as $key => $value) {
			if ('accepted' == $value['status'] && 'accepted' == get_post_meta($value['post_id'], 'status', true)) {
				if ($value['product_id'] == $current_product_id  && 'publish' == get_post_status($value['post_id'])) {

					if ($cart_item['quantity'] >= $value['qty']) {

						$start_time=1627977442;
						$end_time=get_post_meta($value['post_id'], 'changeprice_till', true) ;
						$newprice= get_post_meta($value['post_id'], 'absb_approved_price', true );
						if (time()<$end_time) {

							WC()->cart->cart_contents[$cart_item_key]['offer_prev_price'] = $cart_item['data']->get_price();
							WC()->cart->set_session();

							$cart_item['data']->set_price($newprice);
							break;
						} else {
							$old_price = wc_get_product($product->get_ID())->get_price();
							$cart_item['data']->set_price($old_price);
						}

					} else {
						$old_price = wc_get_product($product->get_ID())->get_price();
						$cart_item['data']->set_price($old_price);
					}


				}

			}
		}
		

	}

}

// function absb_dis_offer_add_premium_support_endpoint() {
// 	add_rewrite_endpoint( 'premium-support', EP_ROOT | EP_PAGES );
// 	// flush_rewrite_rules();
// }

// add_action( 'init', 'absb_dis_offer_add_premium_support_endpoint' );


function absb_dis_offer_premium_support_query_vars( $vars ) {
	$vars[] = 'premium-support';
	return $vars;
}

add_filter( 'query_vars', 'absb_dis_offer_premium_support_query_vars', 0 );


function absb_dis_offer_add_premium_support_link_my_account( $items ) {
	$items['premium-support'] = 'Price Offers';
	return $items;
}

add_filter( 'woocommerce_account_menu_items', 'absb_dis_offer_add_premium_support_link_my_account' );
add_action( 'woocommerce_account_premium-support_endpoint', 'absb_dis_offer__premium_support_content' );


function absb_dis_offer__premium_support_content() {

	wp_enqueue_script('datatables12231', '//cdn.datatables.net/1.10.25/js/jquery.dataTables.min.js', array('jquery'), '1.0', 'all' );		

	wp_enqueue_style('datatables213123134', '//cdn.datatables.net/1.10.25/css/jquery.dataTables.min.css', '1.0', 'all');
	global $wpdb;
	$current_userr_id_is=get_current_user_ID();
	$results = $wpdb->get_results( $wpdb->prepare('SELECT ID FROM  ' . $wpdb->prefix . 'posts u , ' . $wpdb->prefix . 'postmeta m WHERE post_status = "publish" AND post_type = "absb_custom_offers" AND u.ID = m.post_id AND  m.meta_key = "user_id" AND m.meta_value  = %s ORDER BY ID DESC', $current_userr_id_is));


	$offer_to_show_ids=[];
	?>
	<div style="overflow:auto;">
		<table id="absb_show_requests_table" style="width:100%;">
			<thead>
				<tr>
					<th>Request ID</th>
					<th>Product</th>
					<th>Status</th>
					<th>Action</th>
				</tr>
			</thead>
			<tbody>
				<?php
				$cunt=0;
				$structure = get_option( 'permalink_structure' );
				foreach ($results as $key => $value) {
					$cunt++;
					?>
					<tr>
						<td>
							<?php echo filter_var($value->ID); ?>
						</td>
						<td><a href="<?php echo filter_var(get_permalink(get_post_meta($value->ID, 'product_id', true))); ?>"> <?php echo filter_var(get_the_title(get_post_meta($value->ID, 'product_id', true))); ?></a></td>
						<td><?php echo filter_var(ucfirst(get_post_meta($value->ID, 'status', true))); ?></td>
						<td>

							
							<button type="button" class="button show_request_absb" value="<?php echo filter_var($value->ID); ?>">View</button>

						</td>
					</tr>

					<?php

				}
				if (0==$cunt) {
					?>
					<tr>
						<td style="text-align: center;" colspan="4">No Request's Found !</td>
					</tr>
					<?php
				}
				?>
			</tbody>
		</table>
	</div>






	<div class="modelpopup1" id="show_request_popup" role="dialog" style="display: none;">
		<div class="modal-dialog">
			<div class="modal-content1">
				<div class="modal-header" style="">
					<div style="display: inline-flex;width: 100%;">


						<h2 class="modal-title" style="width: 100%; ">Your Request Details</h2>
						<button type="button" class="close1" data-dismiss="modal" style="text-align:right;font-size: 28px; color: red !important;">&times;</button>
					</div>
					<hr>
					
				</div>
				<div class="modal-body1  " >
				</div>

			</div>
		</div>
	</div>



	<script type="text/javascript">
		jQuery(document).ready(function(){
			setTimeout(function(){
				jQuery('#absb_show_requests_table').DataTable();
			},400);

		});




		jQuery('body').on('click', '.close1' , function(){

			jQuery('#show_request_popup').hide();
			jQuery('#popupfor_login_guest').hide();


		})
		jQuery('body').on('click', '.show_request_absb' , function(){
			jQuery('#show_request_popup').show();

			var curr_val = jQuery(this).val();
			console.log(curr_val);

			jQuery.ajax({
				url : '<?php echo filter_var(admin_url('admin-ajax.php')); ?>',

				type : 'post',
				data : {
					action : 'absb_view_request_record_on_my_account_page',    
					

					id_to_ammend : jQuery(this).val(),    


				},
				success : function( response ) {

					jQuery('#show_request_popup').find('.modal-body1').html(response);




				}

			});

		});

	</script>



	<style type="text/css">
		
		.modelpopup1 {
			display: none;
			position: fixed; 
			z-index: 9999;		
			left: 0;
			overflow-y:scroll;
			overflow: auto !important;
			top: 0;
			width: 100%; 
			height: 100%; 
			overflow: auto; 
			background-color: rgb(0,0,0); 
			background-color: rgba(0,0,0,0.4);
			padding: 3%;
			/*border: 1px solid #ae7b3b;*/
			border-radius: 8px;
		}
		.modal-content1 {
			background-color: #fefefe;
			margin: auto;
			padding: 20px;
			/*border: 2px solid #ae7b3b;*/
			width: 60%;
			border-radius: 4px;
		}
		.close1 {
			color: rgba(0,0,0,0.3);
			float: right;
			font-size: 24px;
			font-weight: bold;
			background-color: white;
			border-style: none;
			padding: 0px !important;
		}
		.close1:hover,
		.close1:focus {
			color: #000;
			text-decoration: none;
			cursor: pointer;
			background-color: white;
		}


		@media screen and (max-width: 550px) {

			#makeoffer {
				width: 100% !important;
				margin-top: 2% !important;
			}



		}



		
		@media screen and (max-width: 700px) {

			.modal-content, .modal-content1 {
				width: 100% !important;
				border:none !important
				padding:unset !important;
				/*margin-top: 2% !important;*/
			}

			.popup_offer_statusss {
				display: unset!important;
				width: unset !important;
			}



		}

	</style>
	
	<?php
}
