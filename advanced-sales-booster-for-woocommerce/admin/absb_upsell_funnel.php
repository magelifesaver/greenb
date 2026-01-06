<?php

$upsell_gen_data = get_option('absb_saved_upsell_general_settings');
$upsell_rule_data = get_option('absb_rule_settings_upsell');

$absb_product_category_html='';
$absb_parentid = get_queried_object_id();
$absb_args = array(
	'numberposts' => -1,
	'taxonomy' => 'product_cat',
);
$absb_terms = get_terms($absb_args);
if ( $absb_terms ) {   
	foreach ( $absb_terms as $absb_term1 ) {
		$absb_product_category_html = $absb_product_category_html . '<option class="absb_catopt" value="' . $absb_term1->term_id . '">' . $absb_term1->name . '</option>';

	}
}

?>


<div id="main_qtydis_inner_div">

	<div id="qty_buttons_div" style="margin-top: 1%; margin-bottom: 2%;">
		<button type="button" id="upsell_rule" class="inner_buttons" style=" padding: 9px 50px;
		border-radius: unset !important;   font-weight: 500; border-bottom: none; margin-left: 5px; font-size: 13px;">All Rules</button>
		<button type="button" id="up_sell_gen" class="inner_buttons activeee" style=" padding: 9px 30px; margin: unset !important;
		border-radius: unset !important;   font-weight: 500; border-bottom: none; font-size:13px; ">Upsell Products Settings</button>
		<hr>

	</div>

	<div id="upsell_general_settings_div">
		
		<table style="width: 98% !important; margin-left: 1%;" class="absb_rule_tables">



			<tr>
				<td style="width: 40%;">
					<strong style="color: #007cba;">Activate Upsell Products</strong>
				</td>
				<td style="width: 60%;">

					<label class="switch">
						<input type="checkbox" id="upsell_activate" 
						<?php 
						if ('true' == $upsell_gen_data['activateupsell']) {
							echo 'checked';
						}
						?>
						>


						<span class="slider"></span>
					</label><br>
				</td>

			</tr>


			<tr>
				<td style="width: 40%;">
					<strong>Enable Short Description of Upsell Products</strong>
				</td>
				<td style="width: 60%;">

					<label class="switch">
						<input type="checkbox" id="discription_upsell" 
						<?php 
						if ('true' == $upsell_gen_data['upsell_discription']) {
							echo 'checked';
						}
						?>
						>


						<span class="slider"></span>
					</label><br>
				</td>

			</tr>



			<tr>
				<td style="width: 40%;">
					<strong>Enable Hyperlink for Upsell Products</strong>
				</td>
				<td style="width: 60%;">

					<label class="switch">
						<input type="checkbox" id="upsell_hyperlink"
						<?php 
						if ('true' == $upsell_gen_data['upsell_hyperlink']) {
							echo 'checked';
						}
						?>
						>


						<span class="slider"></span>
					</label><br>
				</td>

			</tr>





			<tr>
				<td style="width: 40%;">
					<strong>Enable Scroll Dots</strong>
				</td>
				<td style="width: 60%;">

					<label class="switch">
						<input type="checkbox" id="upsell_dots"
						<?php 
						if ('true' == $upsell_gen_data['upsell_dots']) {
							echo 'checked';
						}
						?>
						>


						<span class="slider"></span>
					</label><br>
				</td>

			</tr>




			<tr>
				<td style="width: 40%;">
					<strong>Enable Left/Right Arrows</strong>
				</td>
				<td style="width: 60%;">

					<label class="switch">
						<input type="checkbox" id="upsell_arrows"
						<?php 
						if ('true' == $upsell_gen_data['upsell_arrows']) {
							echo 'checked';
						}
						?>
						>


						<span class="slider"></span>
					</label><br>
				</td>

			</tr>


			<tr>
				<td style="width: 40%;">
					<strong>Enable Autoplay</strong>
				</td>
				<td style="width: 60%;">

					<label class="switch">
						<input type="checkbox" id="enable_autoplay"
						<?php 
						if ('true' == $upsell_gen_data['enable_autoplay']) {
							echo 'checked';
						}
						?>
						>


						<span class="slider"></span>
					</label><br>
				</td>

			</tr>




			<tr>
				<td style="width: 40%;">
					<strong>Enable Infinite Loop</strong>
				</td>
				<td style="width: 60%;">

					<label class="switch">
						<input type="checkbox" id="enable_loop"
						<?php 
						if ('true' == $upsell_gen_data['enable_loop']) {
							echo 'checked';
						}
						?>
						>


						<span class="slider"></span>
					</label><br>
				</td>

			</tr>

			<tr>
				<td>
					<strong>Autoplay Speed</strong>
				</td>
				<td>
					<input type="number" id="autoplayspeed" class="input_type" value="<?php echo esc_attr($upsell_gen_data['autoplayspeed']); ?>">
				</td>
			</tr>




			<tr>
				<td>
					<strong>Upsell Products To Show (Screen Size < 500px)</strong>
				</td>
				<td>
					<input type="number" id="prods_500" class="input_type" value="<?php echo esc_attr($upsell_gen_data['screens_for_500']); ?>">
				</td>
			</tr>




			<tr>
				<td>
					<strong>Upsell Products To Show (Screen Size < 800px)</strong>
				</td>
				<td>
					<input type="number" id="prods_800" class="input_type" value="<?php echo esc_attr($upsell_gen_data['screens_for_800']); ?>">
				</td>
			</tr>




			<tr>
				<td>
					<strong>Upsell Products To Show (Screen Size < 1100px)</strong>
				</td>
				<td>
					<input type="number" id="prods_1100" class="input_type" value="<?php echo esc_attr($upsell_gen_data['screens_for_1100']); ?>">
				</td>
			</tr>



			<tr>
				<td>
					<strong>Upsell Products To Show (Screen Size > 1100px)</strong>
				</td>
				<td>
					<input type="number" id="prods_1100_greater" class="input_type" value="<?php echo esc_attr($upsell_gen_data['screens_for_1100_greater']); ?>">
				</td>
			</tr>


			<tr>
				<td>
					<strong>Title above Upsell Products</strong>
				</td>
				<td>
					<input type="text" id="upsell_title" class="input_type" value="<?php echo esc_attr($upsell_gen_data['upsell_title']); ?>">
				</td>
			</tr>


		</table>
		<div style="text-align: right; margin-bottom: 1%;">
			<button type="button" id="save_upsell_gen_settings" style="background-color: #007cba; cursor: pointer; color: white; padding: 9px 30px; border:none;     margin-right:16px; border-radius: 5px;"><i class="icon-save"></i> Save Settings</button>
		</div>
	</div>



	<div id="upsell_rules_div">
		
		<div style="margin-right: 3%; text-align: right;">
			<button type="button" id="absb_upsell_funnel_add_new" style="background-color: green; color: white; padding: 8px 12px; font-size: 14px; font-weight: 500; cursor: pointer; border:1px solid green; border-radius: 3px !important;"><i class="fa-solid fa-plus"></i> Add Rules</button>
		</div>
		<div style="width:60%; margin-left: 1%;" >
			<h1 class="main_heading" > All Rules</h1>
		</div><hr>



		<form method="POST">
			<table id="absb_datatable_upsell" class="table hover" style="width:97%; margin: 20px !important; text-align: center; border:none;">
				<thead>
					<tr id="recordtr" style="width:100%; text-align: center;">


						<th>Serial #</th>
						<th>Rule Name</th>
						<th>Applied on</th>
						<th>Status</th>
						<th>Edit / Delete</th>

					</tr>
				</thead>


				<tbody>

				</tbody>

				<tfoot>
					<tr style="text-align: center;">
						<th>Serial #</th>
						<th>Rule Name</th>
						<th>Applied on</th>
						<th>Status</th>
						<th>Edit / Delete</th>
					</tr>


				</tfoot>

			</table>
		</form>

		<div class="modelpopup1" id="upsell_edit_rules_div" role="dialog" style="display: none;">
			<div class="modal-dialog">
				<div class="modal-content1">
					<div class="modal-header" style="">
						<button type="button" class="close1" data-dismiss="modal" style="margin-top:-1%; font-size: 28px;">&times;</button>

						<h2 class="modal-title" style="color: #000 !important; ">Edit Rule</h2><hr>
					</div>
					<div class="modal-body1 animate__animated animate__flash" >
					</div>
					<div class="modal-footer" style="text-align: right;">
						<button type="button" id="upsell_update_rules" style="background-color: #007cba; cursor: pointer; color: white; padding: 12px 10px; border:none; border-radius: 5px;" ><i class="icon-save" ></i> Update Rule</button>
					</div>
				</div>
			</div>
		</div>


		<div class="modal fade modalpopup" id="create_qty_rule_modal" role="dialog" style="display: none;">
			<div class="modal-dialog">
				<div class="modal-content modal-content112">
					<div class="modal-header" style="">
						<button type="button" class="close" data-dismiss="modal" style="margin-top: -1%;">&times;</button>

						<h2 class="modal-title" style="color: #000 !important; ">Configure Rule</h2><hr>


					</div>
					<div class="modal-body animate__animated animate__flash" style="margin-top: 3%;" >


						<h2 style="text-align: left;">Basic Settings</h2>

						<table style="width: 98% !important; margin-left: 1%;" class="absb_rule_tables">
							<tr>
								<td style="text-align: left;"><strong>Rule Name</strong>

									<input type="text" name="absb_name" id="upsell_rule_name" class="absbmsgbox" style="width:70%; padding:2px;">



								</td>
								<td style="text-align:right;"><strong>Activate Rule</strong>

									<label class="switch">
										<input type="checkbox" id="upsell_activate_rule" checked>
										<span class="slider"></span>
									</label>
								</td>
							</tr>
						</table>

						

						<h2 style="text-align: left;">Set Conditions for Upsell products</h2>
						<button class="button-primary absb_add_conditions" type="button" style="background-color: green; color: white; padding: 2px 5px; font-size: 14px; font-weight: 500; cursor: pointer; border:1px solid green; border-radius: 3px !important; float: right; margin-bottom: 1%; margin-right: 10px;">
							<i class="fa fa-fw fa-plus"></i>
						Add Condition(s)</button>



						<table style="width: 98% !important; margin-left: 1%;" class="absb_rule_tables table_ppp" id="tablemno">
						

							<tr>
								<th style="width: 30%;">
									Select 
								</th>
								<th style="width: 30%;">
									Condition
								</th>
								<th style="width: 30%;">
									Conditional Value <span class="required" style="color: red; border:none; font-weight: 300;">*</span>
								</th>

								<th style="width: 10%;">
									Action
								</th>
							</tr>
							<tr>
								<td style="width: 30%;">
									<select style="width: 99%;" required class="select_one_for_condition" id="select_one_for_condition_main" >								
										<option value="cartitems">Items in Cart</option>
										<option value="subtotal">Subtotal</option>
										<option value="total">Total</option>
										<option value="cpn_used">Coupon Code Used</option>
										<option value="slctd_product">Cart Contains Product</option>
										<option value="slctd_cateory">Cart Contains Category</option>
										<option value="user_role">User Role</option>
									</select>
								</td>
								<td style="width: 30%; " >
									<select style="width: 99%;" required class="conditionn" id="absb_conditions">		
										<option value="equals">Equal To</option>
										<option value="notequal">Not Equal To</option>
										<option value="greater">Greater Than</option>
										<option value="less">Less Than</option>

									</select>						
								</td>

								<td style="width: 30%;">
									<div id="absb_upsell_sngl_txt" class="divs_for_options">
										<input type="text" class="conditional_val" required style="width: 99%;">
									</div>
									<div style="display: none;" id="select_for_product_div" class="divs_for_options" >
										<select name="upcell_select_products[]"  style="max-width: 99%;width: 99%;font-size: 12px;"  id="upcell_select_products"  class="upcell_select_products" >

										</select>
									</div>
									<div style="display: none;" id="select_for_category_div" class="divs_for_options">
										<select  name="upcell_select_category[]" style="max-width:99%;width: 99%;font-size: 12px;" id="upcell_select_category"    >
											<?php echo filter_var($absb_product_category_html); ?>

										</select>
									</div>
									<div style="display: none;" id="select_for_role_div" class="divs_for_options">


										<?php 
										global $wp_roles;
										$absb_all_roles = $wp_roles->get_names();
										?>

										<select id="upsell_select_roles"  style="width: 99%;">
											<?php
											foreach ($absb_all_roles as $key_role => $value_role) {
												?>
												<option value="<?php echo filter_var($key_role); ?>"><?php echo filter_var(ucfirst($value_role)); ?></option>
												<?php
											}
											?>
										</select>
									</div>

								</td>
							</tr>

						</table>

						<h2 style="text-align: left;">UpSell Products</h2>

						<table style="width: 98% !important; margin-left: 1%;" class="absb_rule_tables table_ppp" >

							<tr>
								<td style="width: 30%;">
									<strong>Select One</strong>
								</td>

								<td style="width: 70%;">
									<select id="absb_upsell_appliedon" class="absbselect" style="width: 100% !important; max-width: 100% !important; padding:1px; text-align: center;">
										<option value="products">Products</option>
										<option value="categories">Categories</option>

									</select>
								</td>
							</tr>
							<tr>
								<td id="upsell_label_for_options">
									<strong>Select Product/Category <span class="required" style="color: red; border:none; font-weight: 300;">*</span></strong>
								</td>
								<td id="upsell_1" >
									<select multiple id="upsell_funnel_select_product" class="absbselect" name="multi[]">
									</select>
								</td>
								<td id="upsell_2" style="display: none;">
									<select multiple id="upsell_funnel_select_category" name="multi2[]" class="absbselect upsell_cat_select">
										<?php echo filter_var($absb_product_category_html); ?>
									</select>
								</td>
							</tr>
						</table>

						<h2 style="text-align: left;">UpSell Products Display Location</h2>

						<table style="width: 98% !important; margin-left: 1%;" class="absb_rule_tables table_ppp">

							<tr>
								<td style="width: 30%;">
									<strong>Select Location for Cart Page</strong>
								</td>

								<td style="width: 70%;">
									<select id="upsell_location_cart" class="absbselect" style="width: 100% !important; max-width: 100% !important; padding:1px; text-align: center;">
										<option value="woocommerce_before_cart">Before Add to Cart Form</option>
										<option value="woocommerce_after_cart">After Add to Cart Form</option>

									</select>
								</td>
							</tr>
							<tr>
								<td id="upsell_label_for_options">
									<strong>Select Location for Checkout Page</strong>
								</td>
								<td>
									<select id="upsell_location_checkout" class="absbselect" style="width: 100% !important; max-width: 100% !important; padding:1px; text-align: center;">
										<option value="woocommerce_before_checkout_form">Before Checkout Form</option>
										<option value="woocommerce_after_checkout_form">After Checkout Form</option>

									</select>
								</td>

							</tr>
						</table>


						<div style="text-align: right;">
							<button type="button" id="upsell_save_rule" style="background-color: #007cba; cursor: pointer; color: white; padding: 9px 30px; border:none; border-radius: 5px;"><i class="icon-save"></i> Save Rule</button>
						</div>
					</div>







				</div>



			</div>
		</div>
	</div>
</div>

<style type="text/css">
	#absb_datatable_upsell_wrapper {
		margin: 20px !important;
	}

	.table_ppp{
		border-collapse: collapse;
		width: 100%;
	}

	.table_ppp th, .table_ppp td {
		border: 1px solid #ccc;
		padding: 6px 10px;
		text-align: left;
	}


</style>



<script type="text/javascript">
	setTimeout(function(){



		jQuery('.upcell_select_products1').select2({
			ajax: {
				url : '<?php echo filter_var(admin_url('admin-ajax.php')); ?>',
				dataType: 'json',
				type: 'post',
				delay: 250, 
				data: function (params) {
					return {
						q: params.term, 
						action: 'frq_bgt_search_productss', 

					};
				},
				processResults: function( data ) {

					var options = [];
					if ( data ) {


						jQuery.each( data, function( index, text ) { 
							options.push( { id: text[0], text: text[1]  } );
						});

					}
					return {
						results: options
					};
				},
				cache: true
			},
			// multiple: true,
			placeholder: 'Choose Products',
			minimumInputLength: 3 

		});








		jQuery('.upcell_select_products').select2({

			ajax: {
				url : '<?php echo filter_var(admin_url('admin-ajax.php')); ?>',
				dataType: 'json',
				type: 'post',
				delay: 250, 
				data: function (params) {
					return {
						q: params.term, 
						action: 'frq_bgt_search_productss', 

					};
				},
				processResults: function( data ) {

					var options = [];
					if ( data ) {


						jQuery.each( data, function( index, text ) { 
							options.push( { id: text[0], text: text[1]  } );
						});

					}
					return {
						results: options
					};
				},
				cache: true
			},
							// multiple: true,
							placeholder: 'Choose Products',
							minimumInputLength: 3 

						});


		jQuery('#upsell_funnel_select_product').select2({

			ajax: {
				url : '<?php echo filter_var(admin_url('admin-ajax.php')); ?>',
				dataType: 'json',
				type: 'post',
				delay: 250, 
				data: function (params) {
					return {
						q: params.term, 
						action: 'frq_bgt_search_productss', 

					};
				},
				processResults: function( data ) {

					var options = [];
					if ( data ) {


						jQuery.each( data, function( index, text ) { 
							options.push( { id: text[0], text: text[1]  } );
						});

					}
					return {
						results: options
					};
				},
				cache: true
			},
			multiple: true,
			placeholder: 'Choose Products',
			minimumInputLength: 3 

		});
		jQuery('#upsell_funnel_select_category').select2();



		jQuery(document).ready(function(){
			jQuery('body').on('click', '#absb_upsell_funnel', function(){
				jQuery('#upsell_rules_div').show();
				jQuery('#upsell_general_settings_div').hide();

				jQuery('#upsell_rule').addClass('abcactive');
				jQuery('#up_sell_gen').removeClass('abcactive');

			})


			jQuery('body').on('click', '#upsell_rule', function(){
				jQuery('#upsell_rules_div').show();
				jQuery('#upsell_general_settings_div').hide();
			})


			jQuery('body').on('click', '#up_sell_gen', function(){
				jQuery('#upsell_rules_div').hide();
				jQuery('#upsell_general_settings_div').show();
			})


			if (jQuery('#enable_autoplay').prop('checked')) {
				jQuery('#autoplayspeed').removeAttr('disabled');
			} else {
				jQuery('#autoplayspeed').prop('disabled', 'disbaled');
			}


			jQuery('#enable_autoplay').on('change', function() {
				if (jQuery(this).prop('checked')) {
					jQuery('#autoplayspeed').removeAttr('disabled');
				} else {
					jQuery('#autoplayspeed').prop('disabled', 'disbaled');
				}
			});


			jQuery('#save_upsell_gen_settings').on('click', function(){



				var activateupsell=jQuery('#upsell_activate').prop('checked');
				var upsell_discription=jQuery('#discription_upsell').prop('checked');
				var upsell_hyperlink=jQuery('#upsell_hyperlink').prop('checked');
				var upsell_dots=jQuery('#upsell_dots').prop('checked');
				var upsell_arrows=jQuery('#upsell_arrows').prop('checked');
				var enable_autoplay=jQuery('#enable_autoplay').prop('checked');
				var enable_loop=jQuery('#enable_loop').prop('checked');

				var autoplayspeed = jQuery('#autoplayspeed').val();
				var screens_for_500 = jQuery('#prods_500').val();
				var screens_for_800 = jQuery('#prods_800').val();
				var screens_for_1100 = jQuery('#prods_1100').val();
				var screens_for_1100_greater = jQuery('#prods_1100_greater').val();
				var upsell_title = jQuery('#upsell_title').val();





				jQuery.ajax({
					url : '<?php echo filter_var(admin_url('admin-ajax.php')); ?>',

					type : 'post',
					data : {
						action : 'absb_upsell_funnel_save_general_settings',


						activateupsell:activateupsell,
						upsell_discription:upsell_discription,
						upsell_hyperlink:upsell_hyperlink,
						upsell_dots:upsell_dots,
						upsell_arrows:upsell_arrows,
						enable_autoplay:enable_autoplay,
						enable_loop:enable_loop,
						autoplayspeed:autoplayspeed,
						screens_for_500:screens_for_500,
						screens_for_800:screens_for_800,
						screens_for_1100:screens_for_1100,
						screens_for_1100_greater:screens_for_1100_greater,
						upsell_title:upsell_title
					},
					success : function( response ) {
						window.onbeforeunload = null;
						datatable.ajax.reload();








						jQuery('#absbsavemsg').html('<div class="notice notice-success is-dismissible elt_msg" ><p id="absb_messageonsave">Rule has been saved</p><button type="button" class="notice-dismiss hidedivv"><span class="screen-reader-text">Dismiss this notice.</span></button></div>')
						jQuery('#absbsavemsg').show();
						jQuery('#absb_messageonsave').html('General Settings have been Saved');
						jQuery("html, body").animate({ scrollTop: 0 }, "slow");

						jQuery("#absbsavemsg").delay(7000).fadeOut(3000);








					}

				});
			});





		});



		jQuery('#absb_upsell_funnel_add_new').click(function(){
			jQuery('#create_qty_rule_modal').show();



		})


		$roles_html = '<?php foreach ($absb_all_roles as $key_role => $value_role) { ?>
			<option value="<?php echo filter_var($key_role); ?>"><?php echo filter_var(ucfirst($value_role)); ?></option>;	<?php } ?>';






			jQuery('body').on('click', '.absb_add_conditions', function(){

				jQuery('#tablemno').find('tr:last').after('<tr><td style="width: 30%;"><select style="width: 99%;" required class="select_one_for_condition" >	<option value="cartitems">Items in Cart</option><option value="subtotal">Subtotal</option><option value="total">Total</option><option value="cpn_used">Coupon Code Used</option><option value="slctd_product">Cart Contains Product</option><option value="slctd_cateory">Cart Contains Category</option><option value="user_role">User Role</option></select></td><td style="width: 30%; " ><select style="width: 99%;" required class="conditionn" id="absb_conditions"><option value="equals">Equal To</option><option value="notequal">Not Equal To</option><option value="greater">Greater Than</option><option value="less">Less Than</option></select></td> <td style="width: 30%;"><div id="absb_upsell_sngl_txt" class="divs_for_options"><input type="text" class="conditional_val" required style="width: 99%;"></div><div style="display: none;" id="select_for_product_div" class="divs_for_options" ><select name="upcell_select_products[]"  style="max-width: 99%;width: 99%;font-size: 12px;"  id="upcell_select_products" class="upcell_select_products" ></select></div><div style="display: none;" id="select_for_category_div" class="divs_for_options" ><select  name="upcell_select_category[]" style="max-width:99%;width: 99%;font-size: 12px;" id="upcell_select_category" > <?php echo filter_var($absb_product_category_html); ?> </select></div><div style="display: none;" id="select_for_role_div" class="divs_for_options"><select id="upsell_select_roles"  style="width: 99%;">'+ $roles_html +'</select></div></td><td><button type="button" class="del_range_btn" style=" border:none; padding:0px 28px; background-color:white; color:red; cursor:pointer;"><i class="fa fa-trash" style="font-size:14px !important; margin-left:40px; border:1px solid red; padding:8px 10px; border-radius:4px;"></i> </button></td></tr>');



				jQuery('.upcell_select_products').select2({

					ajax: {
						url : '<?php echo filter_var(admin_url('admin-ajax.php')); ?>',
						dataType: 'json',
						type: 'post',
						delay: 250, 
						data: function (params) {
							return {
								q: params.term, 
								action: 'frq_bgt_search_productss', 

							};
						},
						processResults: function( data ) {

							var options = [];
							if ( data ) {


								jQuery.each( data, function( index, text ) { 
									options.push( { id: text[0], text: text[1]  } );
								});

							}
							return {
								results: options
							};
						},
						cache: true
					},
							// multiple: true,
							placeholder: 'Choose Products',
							minimumInputLength: 3 

						});


			})
			jQuery('body').on('click', '.close', function(){
				jQuery('.modalpopup').hide();
			})


			jQuery('body').on('change', '.select_one_for_condition', function(){
				var selected_option = jQuery(this).val();
				if ('slctd_product' == selected_option) {
					jQuery(this).parent().parent().find('#select_for_product_div').show();
					jQuery(this).parent().parent().find('#absb_upsell_sngl_txt').hide();
					jQuery(this).parent().parent().find('#select_for_category_div').hide();
					jQuery(this).parent().parent().find('#select_for_role_div').hide();

				} else if ('slctd_cateory' == selected_option) {
					jQuery(this).parent().parent().find('#select_for_category_div').show();
					jQuery(this).parent().parent().find('#absb_upsell_sngl_txt').hide();
					jQuery(this).parent().parent().find('#select_for_product_div').hide();
					jQuery(this).parent().parent().find('#select_for_role_div').hide();

				} else if ('user_role' == selected_option) {
					jQuery(this).parent().parent().find('#select_for_role_div').show();
					jQuery(this).parent().parent().find('#absb_upsell_sngl_txt').hide();
					jQuery(this).parent().parent().find('#select_for_category_div').hide();
					jQuery(this).parent().parent().find('#select_for_product_div').hide();

				} else {
					jQuery(this).parent().parent().find('#absb_upsell_sngl_txt').show();
					jQuery(this).parent().parent().find('#select_for_role_div').hide();
					jQuery(this).parent().parent().find('#select_for_category_div').hide();
					jQuery(this).parent().parent().find('#select_for_product_div').hide();

				} 


				if ('slctd_product' == selected_option || 'slctd_cateory' == selected_option || 'user_role' == selected_option || 'cpn_used' == selected_option ) {
					jQuery(this).parent().parent().find('.conditionn').html('<option value="equals">Equal To</option><option value="notequal">Not Equal To</option>');
				} else {
					jQuery(this).parent().parent().find('.conditionn').html('<option value="equals">Equal To</option><option value="notequal">Not Equal To</option> <option value="greater" >Greater Than</option> <option value="less">Less Than</option>');
				}

			})

			jQuery('body').on('click', '.del_range_btn', function() {

				jQuery(this).parent().parent().remove();
			});


			jQuery('body').on('change', '#absb_upsell_appliedon', function() {
				var absb_selected=jQuery('#absb_upsell_appliedon').val();
				if ('products' == absb_selected) {
					jQuery('#upsell_label_for_options').show();
					jQuery('#upsell_1').show();
					jQuery('#upsell_2').hide();
				}
				else if ('categories' == absb_selected) {
					jQuery('#upsell_label_for_options').show();
					jQuery('#upsell_1').hide();
					jQuery('#upsell_2').show();	
				}


			});




			jQuery('body').on('click', '#upsell_save_rule' , function(){
				var flag=false;

				jQuery('.select_one_for_condition').each(function(){
					if (jQuery(this).val() == '') {
						alert('Please fill all condition fields');	
						flag=true;			
						return;
					}
				});
				if(flag){
					return;
				}
				jQuery('.conditionn').each(function(){
					if (jQuery(this).val() == '') {
						alert('Please fill all condition fields');flag=true;					
						return;
					}
				});
				if(flag){
					return;
				}
				jQuery('.divs_for_options').each(function(){
					if (jQuery(this).is(':visible')){
						if (jQuery(this).children().val() == '' || jQuery(this).children().val() == null) {
							alert('Please fill all condition fields');flag=true;					
							return;
						}
					}
				});


				if(flag){
					return;
				}


				var select_iss=[];
				var conditionn=[];
				var conditional_val=[];

				jQuery('.select_one_for_condition').each(function(){
					select_iss.push(jQuery(this).val());
				});
				jQuery('.conditionn').each(function(){
					conditionn.push(jQuery(this).val());
				});



				jQuery('.divs_for_options').each(function(){

					if (jQuery(this).is(':visible')){
						conditional_val.push(jQuery(this).children().val());  
					}

				});




				var activate_rule = jQuery('#upsell_activate_rule').prop('checked');




				var appliedon_upsell=jQuery('#absb_upsell_appliedon').val();
				if('products'==appliedon_upsell){
					var procat_ids_upsell=jQuery('#upsell_funnel_select_product').val();
				}
				else if('categories'==appliedon_upsell){
					var procat_ids_upsell=jQuery('#upsell_funnel_select_category').val();
				}


				if(''==procat_ids_upsell){
					alert('PLEASE SELECT PRODUCT/CATEGORY');
					return;
				}


				var upsell_rule_name = jQuery('#upsell_rule_name').val();
				var location_on_cart = jQuery('#upsell_location_cart').val();
				var location_on_checkout = jQuery('#upsell_location_checkout').val();


				jQuery.ajax({
					url : '<?php echo filter_var(admin_url('admin-ajax.php')); ?>',
					type : 'post',
					data : {
						action : 'absb_saving_upsell_rule',

						upsell_rule_name:upsell_rule_name,
						activate_rule:activate_rule,

						select_iss:select_iss,
						conditionn:conditionn,
						conditional_val:conditional_val,			

						appliedon_upsell:appliedon_upsell,
						procat_ids_upsell:procat_ids_upsell,
						location_on_cart:location_on_cart,
						location_on_checkout:location_on_checkout


					},
					success : function( response ) {
						window.onbeforeunload = null;
						datatable.ajax.reload();







						jQuery('.close').click();

						jQuery('#create_qty_rule_modal').hide();





						jQuery('.conditional_val').val('');
						jQuery('.conditionn').val('equals');
						jQuery('.select_one_for_condition').val('cartitems');
						jQuery(".upcell_select_products").val([]).trigger('change');
						jQuery(".upsell_cat_select").val([]).trigger('change');
						jQuery("#upsell_funnel_select_product").val([]).trigger('change');






						jQuery('#absbsavemsg').html('<div class="notice notice-success is-dismissible elt_msg" ><p id="absb_messageonsave">Rule has been saved</p><button type="button" class="notice-dismiss hidedivv"><span class="screen-reader-text">Dismiss this notice.</span></button></div>')
						jQuery('#absbsavemsg').show();
						jQuery('#absb_messageonsave').html('Rule has been saved');
						jQuery("html, body").animate({ scrollTop: 0 }, "slow");
						jQuery("#absbsavemsg").delay(7000).fadeOut(3000);
					}

				});	

			});





			var datatable = jQuery('#absb_datatable_upsell').DataTable({

				ajax: {
					url: '<?php echo filter_var(admin_url('admin-ajax.php')); ?>?action=upsell_get_all_rules_from_db_fordatatable'

				},
				columns: [
				{data: 'Serial #'},
				{data: 'Rule Name'},
				{data: 'Applied on'},

				{data: 'Status'},


				{data: "Edit / Delete" ,render: function ( data, type, full ) {
					var btnhtml='<button type="button" value="'+data+'" style="background:white;border-color:green; color:green;" class="button-primary upsell_edit_btn"><i class="fa fa-fw fa-edit"></i></button>';

					btnhtml = btnhtml + '<button style="margin-left:2%;background:white;border-color:red; color:red;" class="button-primary upsell_delete_btn" value="'+data+'" type="button" id="elt_btn_dlt" ><i class="fa fa-fw fa-trash"><span class="fa-li"></i></button>';
					return btnhtml;
				}}

				],
			});


			jQuery('body').on('click', '.upsell_edit_btn' , function(){

				jQuery('#upsell_edit_rules_div').find('.modal-body1').html('<center><h1>Loading...</h1></center>');

				console.log();

				var index=jQuery(this).val();
				jQuery('#upsell_update_rules').val(index);

				jQuery("#upsell_edit_rules_div").show();
				jQuery('body').on('click', '.close1', function(){

					jQuery("#upsell_edit_rules_div").hide();
				})

				jQuery.ajax({
					url : '<?php echo filter_var(admin_url('admin-ajax.php')); ?>',

					type : 'post',
					data : {
						action : 'upsell_popup_for_edit',      
						index : index       

					},
					success : function( response ) {
						window.onbeforeunload = null;
						jQuery('#upsell_edit_rules_div').find('.modal-body1').html(response);



						var absb_selected=jQuery('#absb_upsell_appliedon1').val();
						if ('products' == absb_selected) {
							jQuery('#upsell_label_for_options1').show();
							jQuery('#upsell_11').show();
							jQuery('#upsell_21').hide();
						}
						else if ('categories' == absb_selected) {
							jQuery('#upsell_label_for_options1').show();
							jQuery('#upsell_11').hide();
							jQuery('#upsell_21').show();	
						}

						datatable.ajax.reload();

					}
				})
			})





			jQuery('body').on('change', '.select_one_for_condition1', function() {
				var selected_option = jQuery(this).val();
				if ('slctd_product' == selected_option) {
					jQuery(this).parent().parent().find('#select_for_product_div1').show();
					jQuery(this).parent().parent().find('#absb_upsell_sngl_txt1').hide();
					jQuery(this).parent().parent().find('#select_for_category_div1').hide();
					jQuery(this).parent().parent().find('#select_for_role_div1').hide();

					// jQuery(this).parent().parent().find('.conditionn1').html('<option value="equals">Equal To</option><option value="notequal">Not Equal To</option>');

				} else if ('slctd_cateory' == selected_option) {
					jQuery(this).parent().parent().find('#select_for_category_div1').show();
					jQuery(this).parent().parent().find('#absb_upsell_sngl_txt1').hide();
					jQuery(this).parent().parent().find('#select_for_product_div1').hide();
					jQuery(this).parent().parent().find('#select_for_role_div1').hide();

					// jQuery(this).parent().parent().find('.conditionn1').html('<option value="equals">Equal To</option><option value="notequal">Not Equal To</option>');

				} else if ('user_role' == selected_option) {
					jQuery(this).parent().parent().find('#select_for_role_div1').show();
					jQuery(this).parent().parent().find('#absb_upsell_sngl_txt1').hide();
					jQuery(this).parent().parent().find('#select_for_category_div1').hide();
					jQuery(this).parent().parent().find('#select_for_product_div1').hide();

					// jQuery(this).parent().parent().find('.conditionn1').html('<option value="equals">Equal To</option><option value="notequal">Not Equal To</option>');

				} else {
					jQuery(this).parent().parent().find('#absb_upsell_sngl_txt1').show();
					jQuery(this).parent().parent().find('#select_for_role_div1').hide();
					jQuery(this).parent().parent().find('#select_for_category_div1').hide();
					jQuery(this).parent().parent().find('#select_for_product_div1').hide();
					;
					
				}
				if ('slctd_product' == selected_option || 'slctd_cateory' == selected_option || 'user_role' == selected_option || 'cpn_used' == selected_option ) {
					jQuery(this).parent().parent().find('.conditionn1').html('<option value="equals">Equal To</option><option value="notequal">Not Equal To</option>');
				} else {
					jQuery(this).parent().parent().find('.conditionn1').html('<option value="equals">Equal To</option><option value="notequal">Not Equal To</option> <option value="greater" >Greater Than</option> <option value="less">Less Than</option>');
				}

			})


			jQuery('body').on('click', '.upsell_delete_btn', function(){
				if(!confirm('Are you sure to permanently remove this rule?')){
					return;
				}
				var index=jQuery(this).val();


				jQuery.ajax({
					url :'<?php echo filter_var(admin_url('admin-ajax.php')); ?>',

					type : 'post',
					data : {
						action : 'upsell_deleting_rule',      
						index : index       

					},
					success : function( response ) {
						datatable.ajax.reload();
						jQuery('#absbsavemsg').html('<div class="notice notice-success is-dismissible elt_msg" ><p id="absb_messageonsave">Rule has been saved</p><button type="button" class="notice-dismiss hidedivv"><span class="screen-reader-text">Dismiss this notice.</span></button></div>')
						jQuery('#absbsavemsg').show();
						jQuery('#absb_messageonsave').html('Rule has been deleted !!!');
						jQuery("html, body").animate({ scrollTop: 0 }, "slow");
						jQuery("#absbsavemsg").delay(7000).fadeOut(3000);

					}
				});
			});



			jQuery('body').on('click', '.absb_add_conditions1', function(){

				jQuery('#tablemno1').find('tr:last').after('<tr><td style="width: 30%;"><select style="width: 99%;" required class="select_one_for_condition1" >	<option value="cartitems">Items in Cart</option><option value="subtotal">Subtotal</option><option value="total">Total</option><option value="cpn_used">Coupon Code Used</option><option value="slctd_product">Cart Contains Product</option><option value="slctd_cateory">Cart Contains Category</option><option value="user_role">User Role</option></select></td><td style="width: 30%;"><select style="width: 99%;" required class="conditionn1" id="absb_conditions1"><option value="equals">Equal To</option><option value="notequal">Not Equal To</option><option value="greater">Greater Than</option><option value="less">Less Than</option></select></td><td style="width: 30%;"><div id="absb_upsell_sngl_txt1" class="divs_for_options1"><input type="text" class="conditional_val1" required style="width: 99%;"></div><div style="display: none;" id="select_for_product_div1" class="divs_for_options1" ><select name="upcell_select_products1[]"  style="max-width: 99%;width: 99%;font-size: 12px;"  id="upcell_select_products1"  class="upcell_select_products1"></select></div><div style="display: none;" id="select_for_category_div1" class="divs_for_options1" ><select  name="upcell_select_category[]" style="max-width:99%;width: 99%;font-size: 12px;" id="upcell_select_category1" > + <?php echo filter_var($absb_product_category_html); ?> + </select></div><div style="display: none;" id="select_for_role_div1" class="divs_for_options1"><select id="upsell_select_roles1"  style="width: 99%;">'+ $roles_html +'</select></div></td><td><button type="button" class="del_range_btn" style=" border:none; padding:0px 28px; background-color:white; color:red; cursor:pointer; margin-left:40px; border:1px solid red; padding:8px 10px; border-radius:4px;"><i class="fa fa-trash" style="font-size:14px !important; "></i> </button></td></tr>');

				jQuery('.upcell_select_products1').select2({
					ajax: {
						url : '<?php echo filter_var(admin_url('admin-ajax.php')); ?>',
						dataType: 'json',
						type: 'post',
						delay: 250, 
						data: function (params) {
							return {
								q: params.term, 
								action: 'frq_bgt_search_productss', 

							};
						},
						processResults: function( data ) {

							var options = [];
							if ( data ) {


								jQuery.each( data, function( index, text ) { 
									options.push( { id: text[0], text: text[1]  } );
								});

							}
							return {
								results: options
							};
						},
						cache: true
					},
			// multiple: true,
			placeholder: 'Choose Products',
			minimumInputLength: 3 

		});



			})








			jQuery('body').on('click', '#upsell_update_rules',function(){


				var index = jQuery(this).val();


				var flag=false;

				jQuery('.select_one_for_condition1').each(function(){
					if (jQuery(this).val() == '') {
						alert('Please fill all condition fields');	
						flag=true;			
						return;
					}
				});
				if(flag){
					return;
				}
				jQuery('.conditionn1').each(function(){
					if (jQuery(this).val() == '') {
						alert('Please fill all condition fields');flag=true;					
						return;
					}
				});
				if(flag){
					return;
				}
				jQuery('.divs_for_options1').each(function(){
					if (jQuery(this).is(':visible')){
						if (jQuery(this).children().val() == '' || jQuery(this).children().val() == null) {
							alert('Please fill all condition fields');flag=true;					
							return;
						}
					}
				});
				if(flag){
					return;
				}

				var select_iss=[];
				var conditionn=[];
				var conditional_val=[];

				jQuery('.select_one_for_condition1').each(function(){
					select_iss.push(jQuery(this).val());
				});
				jQuery('.conditionn1').each(function(){
					conditionn.push(jQuery(this).val());
				});



				jQuery('.divs_for_options1').each(function(){

					if (jQuery(this).is(':visible')){
						conditional_val.push(jQuery(this).children().val());  
					}

				});




				var activate_rule = jQuery('#upsell_activate_rule1').prop('checked');




				var appliedon_upsell=jQuery('#absb_upsell_appliedon1').val();
				if('products'==appliedon_upsell){
					var procat_ids_upsell=jQuery('#upsell_funnel_select_product1').val();
				}
				else if('categories'==appliedon_upsell){
					var procat_ids_upsell=jQuery('#upsell_funnel_select_category1').val();
				}


				if(''==procat_ids_upsell){
					alert('PLEASE SELECT PRODUCT/CATEGORY');
					return;
				}


				var upsell_rule_name = jQuery('#upsell_rule_name1').val();
				var location_on_cart = jQuery('#upsell_location_cart1').val();
				var location_on_checkout = jQuery('#upsell_location_checkout1').val();


				jQuery.ajax({
					url : '<?php echo filter_var(admin_url('admin-ajax.php')); ?>',
					type : 'post',
					data : {
						action : 'absb_update_upsell_rule',
						index:index,
						upsell_rule_name:upsell_rule_name,
						activate_rule:activate_rule,

						select_iss:select_iss,
						conditionn:conditionn,
						conditional_val:conditional_val,			

						appliedon_upsell:appliedon_upsell,
						procat_ids_upsell:procat_ids_upsell,
						location_on_cart:location_on_cart,
						location_on_checkout:location_on_checkout


					},
					success : function( response ) {
						window.onbeforeunload = null;
						datatable.ajax.reload();







						jQuery('.close').click();

						jQuery('#upsell_edit_rules_div').hide();





						jQuery('#absbsavemsg').html('<div class="notice notice-success is-dismissible elt_msg" ><p id="absb_messageonsave">Rule has been saved</p><button type="button" class="notice-dismiss hidedivv"><span class="screen-reader-text">Dismiss this notice.</span></button></div>')
						jQuery('#absbsavemsg').show();
						jQuery('#absb_messageonsave').html('Rule has been updated successfully');
						jQuery("html, body").animate({ scrollTop: 0 }, "slow");
						jQuery("#absbsavemsg").delay(7000).fadeOut(3000);
					}

				});	

			})

		},1000);

	</script>
