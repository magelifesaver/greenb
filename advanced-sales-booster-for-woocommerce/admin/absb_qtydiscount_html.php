<?php 
$rules_settingss=get_option('absb_rule_settings');
?>


<div id="main_qtydis_inner_div">

	<div id="qty_buttons_div" style="margin-top: 1%; margin-bottom: 2%;" >
		<button type="button" id="qtydisrulesetting" class="inner_buttons" style=" padding: 9px 30px;
		border-radius: unset !important;   font-weight: 500; margin-left: 5px; font-size: 13px;">Discount Rules</button>
		<button type="button" id="qtydisgensetting" class="inner_buttons activeee" style=" padding: 9px 30px; margin: unset !important;
		border-radius: unset !important;   font-weight: 500; font-size:13px; ">Product Discount Settings</button>
		<hr>

	</div>


	<div id="qty_rule_div" style="display: none;">


		<div style="margin-right: 3%; text-align: right;">
			<button type="button" id="absb_open_popup" style="background-color: green; color: white; padding: 8px 12px; font-size: 14px; font-weight: 500; cursor: pointer; border:1px solid green; border-radius: 3px !important;"><i class="fa-solid fa-plus"></i> Add Rules</button>
		</div>
		<div style="width:60%; margin-left: 1%;" >
			<h1 class="main_heading" > All Rules</h1>
		</div><hr>
		


		<form method="POST">
			<table id="absb_datatable" class="table hover" style="width:100%; margin-top: 1% !important; text-align: center; border:none;">
				<thead>
					<tr id="recordtr" style="width:100%; text-align: center;">


						<th>Rule Name</th>
						<th>Applied On</th>
						<th>Status</th>
						<th>Allowed Roles</th>
						<th>Edit / Delete</th>

					</tr>
				</thead>


				<tbody>

				</tbody>

				<tfoot>
					<tr style="text-align: center;">
						<th>Rule Name</th>
						<th>Applied On</th>
						<th>Status</th>
						<th>Allowed Roles</th>
						<th>Edit / Delete</th>


					</tfoot>

				</table>
			</form>


			<?php 




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






			<div id="rules_settings_main_div">
				<div id="myModal" class="modalpopup">

					<div class="modal-content">
						<div class="modal-header" style="">
							<button type="button" class="close" data-dismiss="modal" style="margin-top: -1%;">&times;</button>

							<h2 class="modal-title" style="color: #000 !important; ">Configure Rule</h2><hr>


						</div>
						<h3>Basic Settings</h3>   
						<table id="absb_rule_table_01" class="absb_rule_tables" style="width: 100%;">
							<tr>
								<td style="text-align: left;"><strong>Rule Name</strong>

									<input type="text" name="absb_name" id="absb_rule_name" class="absbmsgbox" style="width:70%; padding:2px;">
								</td>
								<td style="text-align:right;"><strong>Activate Rule</strong>

									<label class="switch">
										<input type="checkbox" id="absb_active_rule" checked>
										<span class="slider"></span>
									</label>
								</td>
							</tr>
						</table>
						<h3>Select Products/Categories</h3>
						<table id="absb_rule_table_02" class="absb_rule_tables">
							<tr>
								<td  style="width: 30%;">
									<strong>Applied On</strong>
								</td>

								<td  style="width: 70%;">
									<select name="absb_selectone" id="absb_appliedon" class="absbselect" style="width: 100% !important; max-width: 100% !important; padding:1px; text-align: center;">
										<option value="products">Specific Products</option>
										<option value="categories">Specific Categories</option>

									</select>
								</td>
							</tr>
							<tr>
								<td id="absb_label_for_options"  style="width: 30%;">
									<strong>Select Product/Category <span style="color:red;">*</span></strong>
								</td>
								<td id="absb_1"  style="width: 70%;">
									<select multiple id="absb_select_product" class="absbselect" name="multi[]">
										
									</select>
								</td>
								<td id="absb_2" style="display: none;">
									<select multiple id="absb_select_category" name="multi2[]" class="absbselect">
										<?php echo filter_var($absb_product_category_html); ?>
									</select>
								</td>
							</tr>
						</table>
						<h3>Discount Settings</h3>

						<div class="absb_rule_tables" style="padding-right: unset !important; width: 96% !important;">
							<button type="button" id="addranges" style="background-color: green; color: white; border:1px solid green; padding: 8px 6px; font-size: 14px; font-weight: 500; cursor: pointer; border-radius: 3px; margin-bottom: 5px; float: right; margin-right: 20px;"> Add Range</button>
							<table id="absb_rule_table_03" class="" style="max-width: 98% !important; width: 98% !important; margin-top: 5px;" >
							<!-- <tr>
								
								<td style="width: 20%;"></td>
								<td style="width: 20%;"></td>
								<td style="width: 20%;"></td> 
								<td style="width: 20%;"></td>
								<td style="text-align: center; width: 20%;">
									<button type="button" id="addranges" style="background-color: white; color: #007cba; border:1px solid #007cba; padding: 8px 6px; font-size: 14px; font-weight: 500; cursor: pointer; border-radius: 3px; width: 65px !important;"> Add Range</button>
								</td>
							</tr> -->
							<tr>
								<th style="width: 20%;">Start Range <span style="color:red;">*</span></th>
								<th style="width: 20%;">End Range <span style="color:red;">*</span></th>
								<th style="width: 20%;">Discount type <span style="color:red;">*</span></th>
								<th style="width: 20%;">Amount <span style="color:red;">*</span></th>
								<th style="width: 20%;">Action</th>
							</tr>
							<tr>
								<td style="width: 20%;">
									<input type="number" name="start" id="startrange" class="starting" style="width: 100%; min-width: 100px;">
								</td>
								<td style="width: 20%;">
									<input type="number" name="end" id="endrange" class="ending" style="width: 100%; min-width: 100px;">
								</td>
								<td style="width: 20%;">
									<select id="discounttype" class="distype" style="width: 100%;" style="width: 20%; min-width: 100px;">
										<option value="fix" selected>Fixed</option>
										<option value="per">Percentage</option>
										<option value="revised">Revised Price</option>
									</select>
								</td>
								<td style="width: 20%;">
									<input type="number" name="amount" id="disamount" class="discountamount" style="width: 100%; min-width: 100px;">
								</td>

								<td></td>


							</tr>



						</table>
					</div>
					<table id="absb_rule_table_04" class="absb_rule_tables">
						<h3>Roles Settings</h3>



						<tr>
							<td>
								<strong >Allowed Roles</strong>
							</td>
							<td>


								<?php 
								global $wp_roles;
								$absb_all_roles = $wp_roles->get_names();
								?>
								<select class="absb_customer_roleclass" id="absb_customer_role" multiple="multiple" class="form-control " style="width: 98%;">
									<?php
									foreach ($absb_all_roles as $key_role => $value_role) {
										?>
										<option value="<?php echo filter_var($key_role); ?>"><?php echo filter_var(ucfirst($value_role)); ?></option>
										<?php
									}
									?>

								</select>	
								<br><i style="color: #007cba;">(Important : Leaving empty will be considered as All Roles Allowed.)</i>
							</td>		
						</tr>		
						<tr>
							<td>
								<strong>Allow Guest User</strong>
							</td>
							<td>
								<label class="switch">
									<input type="checkbox" id="absb_qty_dict_is_guest" >
									<span class="slider"></span>
								</label>
							</td>
						</tr>


					</table>



					<div style="text-align: right;">
						<button type="button" id="absb_save_rule_settings_btn" style="background-color: #007cba; cursor: pointer; color: white; padding: 9px 30px; border:none; border-radius: 5px;"><i class="icon-save"></i> Save Rule</button>
					</div>

				</div>
			</div>
		</div>




		<div class="modelpopup1" id="absb_edit_rules_div" role="dialog" style="display: none;">
			<div class="modal-dialog">
				<div class="modal-content1">
					<div class="modal-header" style="">
						<button type="button" class="close1" data-dismiss="modal" style="margin-top:-1%; font-size: 28px;">&times;</button>

						<h2 class="modal-title" style="color: #000 !important; ">Edit Rule</h2><hr>
					</div>
					<div class="modal-body animate__animated animate__flash" >
					</div>
					<div class="modal-footer" style="text-align: right;">
						<button type="button" id="absb_update_rules" style="background-color: #007cba; cursor: pointer; color: white; padding: 9px 30px; border:none; border-radius: 5px;" ><i class="icon-save" ></i> Update Rule</button>
					</div>
				</div>
			</div>
		</div>
		<?php 

		$data = get_option('absb_gen_settings_for_quantity_discount');

		?>


	</div>
	<div id="qty_gen_div" style="margin-bottom: 3%;">


		<table style="width: 98% !important; margin-left: 1%;" class="absb_rule_tables">



			<tr>
				<td style="width: 40%;">
					<strong style="color: #007cba;">Activate Product Discount</strong>
				</td>
				<td style="width: 60%;">

					<label class="switch">
						<input type="checkbox" id="qty_dsct_activate" 
						<?php
						if (isset($data['qty_dsct_activate']) && 'true' == $data['qty_dsct_activate']) {
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
					<strong>Discount Table Location <div class="tooltip"><i class="fa fa-question-circle tooltip" aria-hidden="true" style="cursor: help;"></i>
						<span class="tooltiptext"> Location on Product Page where discount table will be displayed</span>
					</div></strong>
				</td>
				<td>
					<select id="absb_location" class="input_type">
						<option value="beforeadd" 
						<?php
						if ('beforeadd' == $data['location']) {
							echo 'selected';
						}
						?>
						>Before Add to Cart</option>
						<option value="afteradd" 
						<?php
						if ('afteradd' == $data['location']) {
							echo 'selected';
						}
						?>
						>After Add to Cart</option>
						<option value="aftersummary"
						<?php
						if ('aftersummary' == $data['location']) {
							echo 'selected';
						}
						?>
						>After Product Summary</option>
					</select>
				</td>
			</tr>
			<tr>
				<td>
					<strong><i>Discount</i> Table Title</strong>
				</td>
				<td>
					<input type="text" name="heading" id="tableheading" class="input_type" value="<?php echo esc_attr($data['tabletitle']); ?>">
				</td>
			</tr>


			<tr>
				<td>
					<strong><i>Discount</i> Table Heads Background Color</strong>
				</td>
				<td>
					<input type="color" id="tbh_bgcolor" class="input_type" value="<?php echo esc_attr($data['head_bg_color']); ?>">
				</td>
			</tr>

			<tr>
				<td>
					<strong><i>Discount</i> Table Heads Text Color</strong>
				</td>
				<td>
					<input type="color" id="tbh_txtcolor" class="input_type" value="<?php echo esc_attr($data['head_text_color']); ?>">
				</td>
			</tr>

			<tr>
				<td>
					<strong><i>Discount</i> Table Background Color</strong>
				</td>
				<td>
					<input type="color" id="tbl_bgcolor" class="input_type" value="<?php echo esc_attr($data['table_bg_color']); ?>">
				</td>
			</tr>

			<tr>
				<td>
					<strong><i>Discount</i> Table Text Color</strong>
				</td>
				<td>
					<input type="color" id="tbl_text_color" class="input_type" value="<?php echo esc_attr($data['table_text_color']); ?>">
				</td>
			</tr>


			<tr>
				<td>
					<strong><i>Discount</i> Table Coulumns Headings</strong>
				</td>
				<td style="margin-top: 1%;">


					<table>
						<tbody>
							<tr>
								<th>First Heading <span class="required" style="color: red; border:none; font-weight: 300;">*</span></th>
								<th>Second Heading <span class="required" style="color: red; border:none; font-weight: 300;">*</span></th>
								<th>Third Heading <span class="required" style="color: red; border:none; font-weight: 300;">*</span></th>
							</tr>
							<tr>
								<td><input type="text" id="firstth" value="<?php echo esc_attr($data['heading_1']); ?>"></td>
								<td><input type="text" id="secondth" value="<?php echo esc_attr($data['heading_2']); ?>"></td>
								<td><input type="text" id="thirdth" style="width: 93%;" value="<?php echo esc_attr($data['heading_3']); ?>"></td>
							</tr>
						</tbody>
					</table>


				</td>
			</tr>



		</table>
		<div style="text-align: right; margin-bottom: 1%;">
			<button type="button" id="absb_save_general_settings_btn" style="background-color: #007cba; cursor: pointer; color: white; padding: 9px 30px; border:none;     margin-right:16px; border-radius: 5px;"><i class="icon-save"></i> Save Settings</button>
		</div>
	</div>
</div>






<?php 
include('absb_javascript.php');
?>




<style type="text/css">


	#absb_rule_table_03 , #absb_rule_table_02 {
		border-collapse: collapse;
		width: 100%;
	}

	#absb_rule_table_03 th,
	#absb_rule_table_03 td, #absb_rule_table_02  th, #absb_rule_table_02  td {
		border: 1px solid #ccc;
		padding: 6px 10px;
		text-align: left;
	}
	
	.inner_buttons {
		border: none;
		color: black;
		/*font-size: 12px;*/
		background-color: #dcdcde ;
		/*font-weight: 600;*/
	}
	.inner_buttons:hover {
		cursor: pointer;
		color: ##dcdcde ;
	}
	.abcactive {

		color: ##dcdcde ;
	}
	.input_type{
		width: 53%;
		margin-top: 1%;
		padding-right: 4px !important;
		border-radius: 0px !important;

	}
	#absb_location {
		width: 53% !important;
		max-width: 53% !important;
	}

	.switch {
		position: relative;
		display: inline-block;
		width: 46px;
		height: 24px;
	}

	.switch input {
		opacity: 0;
		width: 0;
		height: 0;
	}

	.slider {
		position: absolute;
		cursor: pointer;
		top: 0;
		left: 0;
		right: 0;
		bottom: 0;
		background-color: #dcdcde;
		transition: 0.4s;
		border-radius: 34px;
	}

	.slider:before {
		position: absolute;
		content: "";
		height: 20px;
		width: 20px;
		left: 2px;
		bottom: 2px;
		background-color: #fff;
		transition: 0.4s;
		border-radius: 50%;
		box-shadow: 0 1px 2px rgba(0,0,0,0.2);
	}

	input:checked + .slider {
		background-color: #007cba;
		background-image: linear-gradient(#007cba, #007cba);
	}

	input:focus + .slider {
		box-shadow: 0 0 0 2px rgba(0,124,186,0.3);
	}

	input:checked + .slider:before {
		transform: translateX(22px);
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
		height: 90%; 
		overflow: auto; 
		background-color: rgb(0,0,0); 
		background-color: rgba(0,0,0,0.4);
		padding: 3%;
		/*border: 1px solid #ae7b3b;*/
		border-radius: 8px;
	}
	.modalpopup {
		overflow-y:scroll;
		overflow: auto !important;
		display: none; 
		position: fixed;
		z-index: 9999;		
		left: 0;
		top: 0;
		width: 100%; 
		height: 90%; 
		overflow: auto; 
		background-color: rgb(0,0,0); 
		background-color: rgba(0,0,0,0.4); 
		padding: 3%; 

	}
	.modal-content1 {
		background-color: #fefefe;
		margin: auto;
		padding: 20px;
		/*border: 2px solid #ae7b3b;*/
		width: 60%;
		border-radius: 4px;
	}
	.modal-content {
		background-color: #fefefe;
		margin: auto;
		padding: 20px;
		/*border: 2px solid #ae7b3b;*/
		width: 60%;
		border-radius: 4px;
	}
	.absb_rule_tables {
		width: 100% !important;
		border-left: solid 1px lightgrey;
		border-bottom: solid 1px lightgrey;
		border-top: solid 1px lightgrey;
		border-right: solid 1px lightgrey;
		/*border: 1px solid lightgrey;*/
		border-radius: 4px;
		padding: 35px !important;
		margin: 5px;
		/*border-left: 3px solid #007cba;*/
	}
	.select2 {
		width: 100% !important;		
	}
	.close {
		color: #aaaaaa;
		float: right;
		font-size: 28px;
		font-weight: bold;
		border:none;
		background-color: white;
	}
	.close:hover,
	.close:focus {
		color: #000;
		text-decoration: none;
		cursor: pointer;
	}
	.close1 {
		color: rgba(0,0,0,0.3);
		float: right;
		font-size: 24px;
		font-weight: bold;
		background-color: white;
		border-style: none;
		margin-right: 1%;
		margin-top: 1%;
	}
	.close1:hover,
	.close1:focus {
		color: #000;
		text-decoration: none;
		cursor: pointer;
		margin-right: 1%;
	}
	table.dataTable tfoot th, table.dataTable tfoot td {
		padding: 10px 18px 6px 18px;
		border-top: 1px solid #cdcbcd;
	}
	table.dataTable thead th, table.dataTable thead td {
		padding: 10px 18px;
		border-bottom: 1px solid #cdcbcd ;
	}
		/*.absbsavemsg {
			border-left: 5px solid #ae7b3b !important;
			border-bottom: 1px dotted #ae7b3b !important;
			border-top: 1px dotted #ae7b3b !important;
			border-right: 1px dotted #ae7b3b !important;
			}*/

			@media screen and (max-width: 997px) {


				.modalpopup {
					overflow-y:scroll !important;
					overflow: auto !important;
					display: none; 
					position: absolute !important;
					z-index: 9999 !important;		
					left: 0;
					top: 0;
					width: 100% !important; 
					height: 100% !important; 
					overflow: auto !important; 
					/*background-color: rgb(0,0,0); */
					background-color: transparent !important; 
					padding: 3%; 
					margin-top: 6% !important !important;

				}

				.modal-content {
					background-color: #fefefe !important;
					margin: auto !important;
					padding: 20px !important;
					border: 2px solid <?php echo filter_var($gen_data_bmi['bmi_brder_clr']); ?>;
					width: 80% !important;
					border-radius: 4px !important;
				}

				.close {
					color: #aaaaaa;
					float: right;
					/*font-size: 28px;*/
					/*font-weight: bold;*/
					border:none;
					background-color: white;
				}
				.close:hover,
				.close:focus {
					color: #000;
					text-decoration: none;
					cursor: pointer;
					background-color: white;
					margin: 0;
					color: red;
				}
				/*width: 50%;*/



				#absb_rule_table_03, #absb_rule_table_02 {
					/*width: 50%;*/
					/*border:none !important;*/
					/*overflow: scroll;*/
					/*display: block;*/
					/*width: 50%;*/
				}
				#startrange, #distype, #endrange, #disamount {
					/*width: 50% !important;*/

				}
				#addranges {
					/*font-size: 9px !important;*/
					/*width: 59% !important;*/
				}
				.del {
					/*width: 20%;*/

				}
				#discounttype {
					/*min-width: 50% !important;*/
					/*width: unset !important;*/
				}
			}






		</style>
