<?php 
$absb_rule_settingsss=get_option('absb_frq_bgt_items');

?>

<div id="main_frequently_inner_div">

	<div id="qty_buttons_div" style="margin-top: 1%; margin-bottom: 2%;">
		<button type="button" id="frq_bgt_rule_settings" class="inner_buttons" style=" padding: 9px 45px;
		border-radius: unset !important;   font-weight: 500; border-bottom: none; margin-left: 5px; font-size: 13px;">All Rules</button>
		<button type="button" id="frq_bgt_gen_settings" class="inner_buttons activeee" style=" padding: 9px 30px; margin: unset !important;
		border-radius: unset !important;   font-weight: 500; border-bottom: none; font-size:13px; ">Frequently Bought Settings</button>
		<hr>
		
	</div>


	<div id="xyzdiv">


		<div id="main_div_frq">
			<div>
				

				<div style="text-align: right;">
					<button type="button" id="frq_bgt_add_rule_new" style="background-color: green; color: white; border:1px solid green; padding: 8px 12px !important; font-size: 14px; font-weight: 400; cursor: pointer; border-radius: 2px; margin-right: 1%; border-radius: 3px;" ><i class="fa-solid fa-plus"></i> Add Rule</button>
				</div>
				<style>
					.tooltip {
						position: relative;
						display: inline-block;
					}

					.tooltip .tooltiptext {
						visibility: hidden;
						width: 277px;
						background-color: black;
						color: #fff;
						text-align: center;
						border-radius: 6px;
						cursor: pointer;

						position: absolute;
						z-index: 1;
						bottom: 100%;
					}

					.tooltip:hover .tooltiptext {
						cursor: pointer;
						visibility: visible;
						padding: 3px 10px;

					}
				</style>
				

				<table class="absb_rule_tables" style="width: 98% !important; margin-left: 1% !important; display: none;" id="frq_bgt_opening_table">
					<tr>
						<td style="width: 30%;">
							<strong>Select Product  <div class="tooltip"><i class="fa fa-question-circle tooltip" aria-hidden="true" style="cursor: help;"></i>
								<span class="tooltiptext">Choose product where frequently bought products will be shown</span>
							</div></strong>
							

						</td>
						<td style="width: 70%;">
							<div id="newdiv12345">
								<select  id="to_prevent_duplicate" class="absbselect" >

								</select>
							</div>
						</td>
					</tr>
				</table>

			</div>


			<div id="absb_divhidden" style="display: none;">
				<table class="absb_rule_tables" style="width: 98% !important; margin-left: 1% !important;" id="tb2">
					<tr>
						<td style="width: 30%;">
							<strong>Frequently Bought Products <span style="color:red;">*</span></strong>
						</td>
						<td style="width: 70%;">
							<select multiple id="frq_bgt_products">
								<?php
								if ( ! empty( $sendingdata['freq_bgt'] ) && is_array( $sendingdata['freq_bgt'] ) ) {
									foreach ( $sendingdata['freq_bgt'] as $keyabc => $valueabc ) {
										$product = wc_get_product( $valueabc );

										if ( $product ) {
											if ( $product->is_type( 'variation' ) ) {
												$parent = wc_get_product( $product->get_parent_id() );
												$variation_attributes = wc_get_formatted_variation( $product, true );
												$title = $parent->get_name() . ' - ' . $variation_attributes;
											} else {
												$title = $product->get_name();
											}

											echo '<option value="' . esc_attr( trim( $valueabc ) ) . '" selected>' . esc_html( $title ) . '</option>';
										}
									}
								}
								?>
							</select>
						</td>

					</tr>
				</table>
			</div>
			<div style="text-align: right; display: none; margin-left: 82%;" id="sabebtndivfrq"  >
				<button type="button" id="cancel_saving_frq_bgt_rule" style="background-color: white; cursor: pointer; color: red; padding: 9px 40px; margin-right:4px; border:1px solid red; border-radius: 5px;" ><i class="fa-solid fa-xmark"></i> Cancel</button>
				<button type="button" id="save_frq_bgt" style="background-color: #007cba; cursor: pointer; color: white; padding: 10px 30px; margin-right:6%; border:none; border-radius: 5px;" ><i class="icon-save"></i> Save Rule</button>

			</div>
			<div style="text-align: right; margin-right: 1%;">
				<button type="button" id="cancel_eralier" style="background-color: white; cursor: pointer; color: red; padding: 9px 30px; border:1px solid red; margin-right: 4px; display: none; border-radius: 5px;" ><i class="fa-solid fa-xmark"></i> Cancel</button>
			</div>
		</div>


		<div id="newdiv" style="display: none;">

			<div class="div_body">



			</div>




			<div style="text-align: right;" id="updatebtndiv_frq">
				<button type="button" id="cancel_update_frq_bgt" style="background-color: white; cursor: pointer; color: red; padding: 9px 30px; border:1px solid red; margin-right: 4px; border-radius: 5px;" ><i class="fa-solid fa-xmark"></i> Cancel</button>
				<button type="button" id="update_frq_bgt" style="background-color: #007cba; cursor: pointer; color: white; padding: 9px 30px; border:none; margin-right: 6%; border-radius: 5px;" ><i class="icon-save"></i> Update</button>
			</div>
		</div>



		<h1 style="margin-left: 1%; margin-top: 3%;">All Rules</h1>
		<hr>



		<form method="POST">
			<table id="absb_datatable_frq_bgt" class="table table-striped" style="width:100%; margin-top: 5% !important; text-align: center;">
				<thead>
					<tr id="recordtr" style="width:100%; ">


						<th>Serial No.</th>
						<th>Applied On</th>
						<th>Frequently Bought</th>				
						<th>Edit / Delete</th>

					</tr>
				</thead>


				<tbody>

				</tbody>

				<tfoot>
					<tr style="text-align: center;">
						<th>Serial No.</th>
						<th>Applied On</th>
						<th>Frequently Bought</th>
						<th>Edit / Delete</th>
					</tr>


				</tfoot>

			</table>
		</form>
	</div>





	<?php 
	$gen_settings = get_option('frq_bgt_general_settings');
	?>
	<div id="frq_bgt_general_settings" style="display: none;">

		<table class="absb_rule_tables" style="width: 98% !important; margin-left: 1% !important;">

			<tr>
				<td style="width: 40%;">
					<strong style="color: #007cba;">Activate <i>Frequently Bought Products</i></strong>
				</td>
				<td style="text-align: left; width: 60%;">

					<label class="switch">
						<input type="checkbox" id="frq_bgt_activate" 
						<?php
						if (isset($gen_settings['frq_bgt_activate']) && 'true' == $gen_settings['frq_bgt_activate']) {
							echo 'checked';
						}
						?>
						>


						<span class="slider"></span>
					</label>
				</td>
			</tr>

			<tr>
				<td style="width: 40%;">
					<strong>Display Image of <i>Frequently Bought Products</i>  </strong>
				</td>
				<td style="text-align: left; width: 60%;">

					<label class="switch">
						<input type="checkbox" id="frq_bgt_image" 
						<?php
						if (isset($gen_settings['frq_bgt_image']) && 'true' == $gen_settings['frq_bgt_image']) {
							echo 'checked';
						} 
						?>
						>


						<span class="slider"></span>
					</label>
				</td>
			</tr>

			<tr>
				<td style="width: 40%;">
					<strong>Display Price of <i>Frequently Bought Products</i></strong>
				</td>
				<td style="text-align: left; width: 60%;">

					<label class="switch">
						<input type="checkbox" id="frq_bgt_price"
						<?php
						if (isset($gen_settings['frq_bgt_price']) && 'true' == $gen_settings['frq_bgt_price']) {
							echo 'checked';
						}
						?>
						>


						<span class="slider"></span>
					</label>
				</td>
			</tr>
			
			<tr>
				<td style="width: 40%;">
					<strong>Display Title of <i>Frequently Bought Products</i></strong>
				</td>
				<td style="text-align: left; width: 60%;">

					<label class="switch">
						<input type="checkbox" id="frq_bgt_cartbtn" 
						<?php
						if (isset($gen_settings['frq_bgt_cartbtn']) && 'true' == $gen_settings['frq_bgt_cartbtn']) {
							echo 'checked';
						}
						?>
						>


						<span class="slider"></span>
					</label>
				</td>
			</tr>



			<tr>
				<td style="width: 40%;">
					<strong>Display <i>Frequently Bought Products</i> Table Title</strong>
				</td>
				<td style="text-align: left; width: 60%;">

					<label class="switch">
						<input type="checkbox" id="frq_bgt_tablename"
						<?php
						if (isset($gen_settings['frq_bgt_tablename']) && 'true' == $gen_settings['frq_bgt_tablename']) {
							echo 'checked';
						}
						?>
						>


						<span class="slider"></span>
					</label>
				</td>
			</tr>

			<tr> 
				<td>
					<strong>Show Checkbox for Add to Cart</strong>
				</td>
				<td>
					<label class="switch">
						<input type="checkbox" id="frq_bgt_enable_ad_cart"
						<?php
						if (isset($gen_settings['frq_bgt_enable_ad_cart']) && 'true' == $gen_settings['frq_bgt_enable_ad_cart']) {
							echo 'checked';
						}
						?>
						>
						<span class="slider"></span>
					</label>


				</td>
			</tr>


			<tr>
				<td>
					<strong>Table's Title</strong>
				</td>
				<td>
					<input type="text" name="txt" id="frq_bgt_tabletitle" style="width: 44%;" value="<?php echo esc_attr($gen_settings['frq_bgt_tabletitle']); ?>">
				</td>
			</tr>

			<tr>
				<td>
					<strong><i>Frequently Bought Products</i> Location</strong>
				</td>
				<td>
					<select id="frq_bgt_location" class="input_type">
						<option value="beforeadding" 
						<?php
						if ('beforeadding' == $gen_settings['frq_bgt_location']) {
							echo 'selected';
						}
						?>
						>Before Add to Cart (Recommended)</option>
						<option value="afteradding" 
						<?php
						if ('afteradding' == $gen_settings['frq_bgt_location']) {
							echo 'selected';
						}
						?>
						>After Add to Cart</option>

					</select>
				</td>
			</tr>
		</tr>



	</table>

	<div style="text-align: right; margin-bottom: 1%;"  >
		<button type="button" id="frq_bgt_gen_save" style="background-color: #007cba; cursor: pointer; color: white; padding: 9px 30px; border:none; margin-right: 1%; border-radius: 5px;" >Save Settings</button>
	</div>
</div>


</div>


<script type="text/javascript">




	jQuery('#to_prevent_duplicate').select2({

		ajax: {
			url : '<?php echo filter_var(admin_url('admin-ajax.php')); ?>',
			dataType: 'json',
			type: 'post',
			delay: 250, 
			data: function (params) {
				return {
					q: params.term, 
					action: 'frq_bgt_search_productss_duplicate', 
					
				};
			},
			processResults: function( data ) {
				console.log(data);
				var options = [];
				if ( data ) {

					
					jQuery.each( data, function( index, text ) { 
						options.push( { id: text[0], text: text[1], disabled: text[2]  } );
					});

				}
				return {
					results: options
				};
			},
			cache: true
		},
		placeholder: 'Choose Products',
		minimumInputLength: 3,
		maximumSelectionLength: 6


	});

	jQuery('#frq_bgt_products').select2({

		ajax: {
			url : '<?php echo filter_var(admin_url('admin-ajax.php')); ?>',
			dataType: 'json',
			type: 'post',
			delay: 250, 
			data: function (params) {
				var main_product = jQuery('#to_prevent_duplicate').val()

				return {
					q: params.term, 
					main_product : main_product,

					action: 'frq_bgt_search_productss_only_for_frq_bgt', 
					
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
		minimumInputLength: 3,
		maximumSelectionLength: 6


	});




	setTimeout(function(){

		jQuery('body').on('change', '#frq_bgt_tablename', function(){


			if (jQuery(this).is(':checked')) {
				jQuery('#frq_bgt_tabletitle').prop('disabled', false);

			} else {

				jQuery('#frq_bgt_tabletitle').prop('disabled', true);

			}
		});


		jQuery('body').on('click', '#frq_bgt_gen_settings', function(){
			jQuery('#frq_bgt_general_settings').show();
			jQuery('#xyzdiv').hide();

		})
		jQuery('body').on('click', '#frq_bgt_rule_settings', function(){
			jQuery('#frq_bgt_general_settings').hide();
			jQuery('#xyzdiv').show();

		})
		jQuery('body').on('click', '#cancel_eralier', function(){
			jQuery(this).hide();
			jQuery('#frq_bgt_opening_table').hide();
		})

		jQuery('body').on('click', '#frq_bgt_add_rule_new', function(){

			jQuery('#frq_bgt_opening_table').show();

			if (jQuery('#cancel_eralier').length === 0) {
				jQuery('#cancel_eralier').show();
			}

		})
		jQuery('body').on('click', '#cancel_update_frq_bgt', function(){

			jQuery('#tb111').hide();
			jQuery('#tb222').hide();
			jQuery('#updatebtndiv_frq').hide();
			jQuery('#frq_bgt_add_rule_new').show();
		})

		jQuery('body').on('click', '#cancel_saving_frq_bgt_rule', function(){

			if(!confirm('By canceling, your filled data will be removed.')){
				return;
			}
			jQuery("#to_prevent_duplicate").val([]).trigger('change');


			jQuery('#frq_bgt_opening_table').hide();
			jQuery('#sabebtndivfrq').hide();
			jQuery('#tb2').hide();

			jQuery.ajax({
				url : '<?php echo filter_var(admin_url('admin-ajax.php')); ?>',

				type : 'post',
				data : {
					action : 'frq_bgt_prevent_duplicate_value',
				},
				success : function( response ) {
					window.onbeforeunload = null;	
					datatable.ajax.reload();
					// jQuery('#newdiv12345').html(response);


					jQuery('#frq_bgt_opening_table').css("border-bottom", '1px solid lightgrey');
					jQuery('#frq_bgt_opening_table').css("border-radius", '0px');
					jQuery('#tb2').css("border-top", '1px solid lightgrey');
					jQuery('#tb2').css("border-radius", '0px');

					jQuery('body').on('change', '#to_prevent_duplicate', function(){
						jQuery('#tb2').show();
						jQuery('#sabebtndivfrq').show();
					})
				}

			});

		})

		jQuery(document).ready(function(){

			if (jQuery('#frq_bgt_tablename').is(':checked')) {
				jQuery('#frq_bgt_tabletitle').prop('disabled', false);

			} else {

				jQuery('#frq_bgt_tabletitle').prop('disabled', true);


			}
			jQuery('#xyzdiv').show();
			jQuery('#frq_bgt_rule_settings').addClass('abcactive');

		});


		jQuery('body').on('change', '#to_prevent_duplicate', function(){
			jQuery('#absb_divhidden').show();
			jQuery('#sabebtndivfrq').show();
			jQuery('#cancel_eralier').hide();
			jQuery('#tb2').show();

			jQuery('#frq_bgt_opening_table').css("border-bottom", 'none');
			jQuery('#frq_bgt_opening_table').css("border-radius", '0px');
			jQuery('#tb2').css("border-top", 'none');
			jQuery('#tb2').css("border-radius", '0px');


			var selected_product = jQuery('#to_prevent_duplicate').val();

			jQuery.ajax({
				url : '<?php echo filter_var(admin_url('admin-ajax.php')); ?>',

				type : 'post',
				data : {
					action : 'saving_product_frequently_bought',
					selected_product:selected_product
				},
				success : function( response ) {

					window.onbeforeunload = null;	
					datatable.ajax.reload();	
					jQuery('#frq_bgt_products').html(response);	
					jQuery("#frq_bgt_products").change();
				}

			});
		})


		jQuery('body').on('click', '#save_frq_bgt', function(){

			var selected_productzz = jQuery('#to_prevent_duplicate').val();
			var freq_bgt = jQuery('#frq_bgt_products').val();
			if(''== freq_bgt) {
				alert('Please select at least one product')
				return;
			}


			jQuery.ajax({
				url : '<?php echo filter_var(admin_url('admin-ajax.php')); ?>',

				type : 'post',
				data : {
					action : 'save_first_time_frq_bgt',
					selected_productzz:selected_productzz,
					freq_bgt:freq_bgt


				},
				success : function( response ) {

					window.onbeforeunload = null;	
					datatable.ajax.reload();	
					jQuery("#to_prevent_duplicate").val([]).trigger('change');

					jQuery('#frq_bgt_opening_table').css("border-bottom", '1px solid lightgrey');
					jQuery('#absb_divhidden').hide();
					jQuery('#sabebtndivfrq').hide();
					jQuery('#frq_bgt_opening_table').hide();
					jQuery("#frq_bgt_products").val([]).trigger('change');

					jQuery('#absbsavemsg').html('<div class="notice notice-success is-dismissible elt_msg" ><p id="absb_messageonsave">Rule has been saved</p><button type="button" class="notice-dismiss hidedivv"><span class="screen-reader-text">Dismiss this notice.</span></button></div>')
					jQuery('#absbsavemsg').show();
					jQuery('#absb_messageonsave').html('Rule has been Saved successfully');
					jQuery("html, body").animate({ scrollTop: 0 }, "slow");
					jQuery("#absbsavemsg").delay(7000).fadeOut(3000);

					jQuery('#absb_divhidden').hide();
					jQuery('#sabebtndivfrq').hide();

					jQuery.ajax({
						url : '<?php echo filter_var(admin_url('admin-ajax.php')); ?>',

						type : 'post',
						data : {
							action : 'frq_bgt_prevent_duplicate_value',
						},
						success : function( response ) {
							window.onbeforeunload = null;	

						}

					});

				}

			});
		})



		var datatable = jQuery('#absb_datatable_frq_bgt').DataTable({

			ajax: {
				url: '<?php echo filter_var(admin_url('admin-ajax.php')); ?>?action=absb_get_all_rules_from_db_for_frq_bgt'

			},
			columns: [
			{data: 'Serial No'},
			{data: 'Applied On'},

			{data: 'Frequently Bought'},

			{data: "Edit / Delete" ,render: function ( data, type, full ) {
				var btnhtml='<button type="button" value="'+data+'" style="background:white;border-color:green; color:green;" class="button-primary absb_edit_btn_frq_bgt"><i class="fa fa-fw fa-edit"></i></button>';

				btnhtml = btnhtml + '<button style="margin-left:2%;background:white;border-color:red; color:red;" class="button-primary absb_delete_btn_frq_bgt" value="'+data+'" type="button" id="elt_btn_dlt" ><i class="fa fa-fw fa-trash"><span class="fa-li"></i></button>';
				return btnhtml;
			}}

			],
		});



		jQuery('body').on('click', '.absb_delete_btn_frq_bgt', function() {
			
			if(!confirm('Are you sure to permanently remove this rule?')) {
				return;
			}
			var index=jQuery(this).val();


			jQuery.ajax({
				url :'<?php echo filter_var(admin_url('admin-ajax.php')); ?>',

				type : 'post',
				data : {
					action : 'absb_deleting_rule_frq_bgt',      
					index : index       

				},
				success : function( response ) {
					datatable.ajax.reload();
					jQuery('#absbsavemsg').html('<div class="notice notice-success is-dismissible elt_msg" ><p id="absb_messageonsave">Rule has been saved</p><button type="button" class="notice-dismiss hidedivv"><span class="screen-reader-text">Dismiss this notice.</span></button></div>')
					jQuery('#absbsavemsg').show();
					jQuery('#absb_messageonsave').html('Rule has been deleted !!!');
					jQuery("html, body").animate({ scrollTop: 0 }, "slow");
					jQuery("#absbsavemsg").delay(7000).fadeOut(3000);

					jQuery.ajax({
						url : '<?php echo filter_var(admin_url('admin-ajax.php')); ?>',

						type : 'post',
						data : {
							action : 'frq_bgt_prevent_duplicate_value',
						},
						success : function( response ) {
							window.onbeforeunload = null;
						}

					});
				}
			});
		});

		jQuery('body').on('click', '.absb_edit_btn_frq_bgt' , function(){

			jQuery('#newdiv').show();
			var index=jQuery(this).val();

			jQuery('#updatebtndiv_frq').show();
			jQuery('#frq_bgt_add_rule_new').hide();
			jQuery('#frq_bgt_opening_table').hide();
			jQuery('#tb2').hide();
			jQuery('#cancel_eralier').hide();
			jQuery('#sabebtndivfrq').hide();

			jQuery('#update_frq_bgt').val(index);

			jQuery.ajax({
				url : '<?php echo filter_var(admin_url('admin-ajax.php')); ?>',

				type : 'post',
				data : {
					action : 'absb_edit_frq_bgt',      
					index : index       

				},
				success : function( response ) {
					window.onbeforeunload = null;
					jQuery('#newdiv').find('.div_body').html(response);
					datatable.ajax.reload();
				}
			})
		})

		jQuery('#update_frq_bgt').on('click', function(){


			var index = jQuery(this).val();
			var freq_bgt=jQuery('#frq_bgt_products_1').val();

			if(''== freq_bgt) {
				alert('Please select at least one product')
				return;
			}

			var selected_productzz=jQuery('#absb_select_product_frequently_1').val();

			jQuery.ajax({
				url : '<?php echo filter_var(admin_url('admin-ajax.php')); ?>',

				type : 'post',
				data : {
					action : 'absb_update_edited_rules_settings_frq_bgt',
					selected_productzz:selected_productzz,
					freq_bgt:freq_bgt,

					index:index

				},
				success : function( response ) {
					window.onbeforeunload = null;
					datatable.ajax.reload();

					jQuery('.close1').click();
					jQuery('#frq_bgt_add_rule_new').show();

					jQuery('#absbsavemsg').html('<div class="notice notice-success is-dismissible elt_msg" ><p id="absb_messageonsave">Rule has been saved</p><button type="button" class="notice-dismiss hidedivv"><span class="screen-reader-text">Dismiss this notice.</span></button></div>')
					jQuery('#absbsavemsg').show();
					jQuery('#absb_messageonsave').html('Rule has been updated successfully');
					jQuery("html, body").animate({ scrollTop: 0 }, "slow");

					jQuery("#absbsavemsg").delay(7000).fadeOut(3000);

					jQuery('#main_div_frq').show();
					jQuery('#absb_divhidden').show();
					jQuery('#newdiv').hide();
					jQuery('#absb_divhidden').hide();
					jQuery('#sabebtndivfrq').hide();
				}

			});
		});

	},2000);

</script>


<style type="text/css">


/*	.absb_edit_btn_frq_bgt:hover {
		background-color: #007cba !important;
		color: white !important;
		}*/

		[type=radio] { 

			opacity: 0;
			width:30px;
			height:30px;
			margin: 3px;
			padding: 10%;
		}

		[type=radio] + img {
			cursor: pointer;
			border-color: black;
		}

		[type=radio]:checked + img {
			color: #007cba;
			background-color: #007cba;
		}
		

	</style>
