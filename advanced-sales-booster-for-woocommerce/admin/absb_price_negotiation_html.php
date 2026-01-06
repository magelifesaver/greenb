<div id="main_offer_inner_div">
	<?php
	$ruless = get_option('absb_offer_rule_settings');
	

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

	<div id="notify_buttons_div" style="margin-top: 1%; margin-bottom: 2%; ">
		<button type="button" id="offer_rule_settings" class="inner_buttons abcactive" style=" padding: 9px 30px; border-radius: unset !important;
		font-weight: 500; border-bottom: none; margin-left: 5px; font-size: 13px;">Rule Settings</button>
		<button type="button" id="offer_gen_settings" class="inner_buttons" style=" padding: 9px 30px; margin: unset !important;
		border-radius: unset !important;   font-weight: 500; border-bottom: none; font-size:13px; ">Price Offer Settings</button>
		<button type="button" id="offer_email_settings" class="inner_buttons" style=" padding: 9px 30px; margin: unset !important;
		border-radius: unset !important;   font-weight: 500; border-bottom: none; font-size:13px; ">Email Templates</button>
		<hr>
	</div>


	<div id="rule_offer_div">



		<div style="margin-right: 3%; text-align: right;">
			<button type="button" id="absb_offer_add_new" style="background-color: green; color: white; padding: 8px 12px; font-size: 14px; font-weight: 500; cursor: pointer; border:1px solid green; border-radius: 3px !important;"><i class="fa-solid fa-plus"></i> Add Rules</button>
		</div>
		<div style="width:60%; margin-left: 1%;" >
			<h1 class="main_heading" > All Rules</h1>
		</div><hr>



		<form method="POST">
			<table id="absb_datatable_offer" class="table hover" style=" text-align: center; border:none; 	width: 97%;	margin: 20px; margin-bottom: 1%; margin-top: 1%;">
				<thead>
					<tr id="recordtr" style="width:100%; text-align: center;">


						<th>Rule Name</th>
						<th>Applied on</th>
						<th>Allowed Roles</th>
						<th>Status</th>
						<th>Edit / Delete</th>

					</tr>
				</thead>


				<tbody>

				</tbody>

				<tfoot>
					<tr style="text-align: center;">
						<th>Rule Name</th>
						<th>Applied on</th>
						<th>Allowed Roles</th>
						<th>Status</th>
						<th>Edit / Delete</th>
					</tr>


				</tfoot>

			</table>
		</form>

		<div class="modelpopup1" id="offer_edit_rules_div" role="dialog" style="display: none;">
			<div class="modal-dialog">
				<div class="modal-content1">
					<div class="modal-header" style="">
						<button type="button" class="close1" data-dismiss="modal" style="margin-top:-1%; font-size: 28px;">&times;</button>

						<h2 class="modal-title" style="color: #000 !important; ">Edit Rule</h2><hr>
					</div>
					<div class="modal-body12 animate__animated animate__flash" >
					</div>
					<div class="modal-footer" style="text-align: right;">
						<button type="button" id="offer_update_rules" style="background-color: #007cba; cursor: pointer; color: white; padding: 9px 30px; border:none; border-radius: 5px;" ><i class="icon-save" ></i> Update Rule</button>
					</div>
				</div>
			</div>
		</div>


		<div class="modal fade modalpopup" id="create_qty_rule_modal_123" role="dialog" style="display: none;">
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

									<input type="text" id="offer_rule_name" class="absbmsgbox" style="width:70%; padding:2px;">



								</td>
								<td style="text-align:right;"><strong>Activate Rule</strong>

									<label class="switch">
										<input type="checkbox" id="offer_activate_rule" checked>
										<span class="slider"></span>
									</label>
								</td>
							</tr>
						</table>
						<h2 style="text-align: left;">Select Products/Categories Settings</h2>

						<table style="width: 98% !important; margin-left: 1%;" class="absb_rule_tables table_ppp">

							<tr>
								<td style="width: 30%;">
									<strong>Select One</strong>
								</td>

								<td style="width: 70%;">
									<select id="absb_offer_appliedon" class="absbselect" style="width: 100% !important; max-width: 100% !important; padding:1px; text-align: center;">
										<option value="products">Products</option>
										<option value="categories">Categories</option>

									</select>
								</td>
							</tr>
							<tr>
								<td id="offer_label_for_options" style="width: 30%;">
									<strong>Select Product/Category <span class="required" style="color: red; border:none; font-weight: 300;">*</span></strong>
								</td>
								<td id="offer_1" style="width: 70%;" >
									<select multiple id="offer_select_product" class="absbselect" name="multi[]">
									</select>
								</td>
								<td id="offer_2" style="display: none; width: 70%;">
									<select multiple id="offer_select_category" name="multi2[]" class="absbselect upsell_cat_select">
										<?php echo filter_var($absb_product_category_html); ?>
									</select>
								</td>
							</tr>


						</table>


						<h2 style="text-align: left;">Select User Roles</h2>

						<table style="width: 98% !important; margin-left: 1%;" class="absb_rule_tables table_ppp">

							<?php 
							global $wp_roles;
							$absb_all_roles = $wp_roles->get_names();
							?>
							<tr>
								<td style="width: 30%;">
									<strong>User Roles</strong>
								</td>
								<td style="width: 70%;">
									<select multiple id="offer_select_roles"  style="width: 100% !important; padding: 3px;  max-width: 100% !important;">
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
										<input type="checkbox" id="absb_prc_neg_is_guest" >
										<span class="slider"></span>
									</label>
								</td>
							</tr>


						</table>


						<div style="text-align: right;">
							<button type="button" id="offer_save_rule" style="background-color: #007cba; cursor: pointer; color: white; padding: 9px 30px; border:none; border-radius: 5px;"><i class="icon-save"></i> Save Rule</button>
						</div>
					</div>


				</div>



			</div>
		</div>

	</div>

	<?php 
	$data = get_option('absb_saved_general_settings_for_price_negotiate');
	?>

	<div id="gen_offer_div">
		<h3 style="margin-left: 1%;">General Settings for Buttons</h3>
		<table style="width: 98% !important; margin-left: 1%;" class="absb_rule_tables">



			<tr>
				<td style="width: 40%;">
					<strong style="color: #007cba;">Activate Price Negotiation</strong>
				</td>
				<td style="width: 60%;">

					<label class="switch">
						<input type="checkbox" id="offer_mod_activate" 
						<?php 
						if ('true' ==$data['activate_offer']) {
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
					<strong>Show Create Offer Button on Shop Page</strong>
				</td>
				<td style="width: 60%;">

					<label class="switch">
						<input type="checkbox" id="offer_mod_onshop"
						<?php
						if ('true' == $data['offer_show_onshop']) {
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
					<strong>Create Offer Button Text</strong>
				</td>
				<td>
					<input type="text" id="create_offer_txt" class="input_type" value="<?php echo esc_attr($data['create_offer_btn_text']); ?>">
				</td>
			</tr>

			<tr>
				<td>
					<strong>View Offer Button Text</strong>
				</td>
				<td>
					<input type="text" id="view_offer_txt" class="input_type" value="<?php echo esc_attr($data['view_ofr__btn_txt']); ?>">
				</td>
			</tr>

			<tr>
				<td>
					<strong>Create Offer Button Background Color</strong>
				</td>
				<td>
					<input type="color" id="create_offer_bg_clr" class="input_type" value="<?php echo esc_attr($data['create_offer_btn_bg_color']); ?>">
				</td>
			</tr>

			<tr>
				<td>
					<strong>Create Offer Button Text Color</strong>
				</td>
				<td>
					<input type="color" id="create_offer_txt_clr" class="input_type" value="<?php echo esc_attr($data['create_offer_btn_text_clr']); ?>">
				</td>
			</tr>


			<tr>
				<td>
					<strong>View Offer Button Background Color</strong>
				</td>
				<td>
					<input type="color" id="view_offer_bg_clr" class="input_type" value="<?php echo esc_attr($data['view_offer_btn_bg_color']); ?>">
				</td>
			</tr>

			<tr>
				<td>
					<strong>View Offer Button Text Color</strong>
				</td>
				<td>
					<input type="color" id="view_offer_txt_clr" class="input_type" value="<?php echo esc_attr($data['view_offer_btn_text_clr']); ?>">
				</td>
			</tr>
		</table>

		<h3 style="margin-left: 1%;">General Settings for Price Negotiations Table</h3>




		<table style="width: 98% !important; margin-left: 1%;" class="absb_rule_tables">

			<tr>
				<td style="width: 40%;">
					<strong>Create Offer Table Head Text</strong>
				</td>
				<td style="width: 60%;">
					<input type="text" id="table_head_txt" class="input_type" value="<?php echo esc_attr($data['popoup_head_txt']); ?>" >
				</td>
			</tr>
			
			<td>
				<strong>View Offer Table Head Text</strong>
			</td>
			<td>
				<input type="text" id="view_table_head_txt" class="input_type" value="<?php echo esc_attr($data['view_popup_headtxt']); ?>" >
			</td>
		</tr>











	</table>
	<div style="text-align: right; margin-bottom: 1%;"  >
		<button type="button" id="offer_save_gen_settings" style="background-color: #007cba; cursor: pointer; color: white; padding: 9px 30px; border:none; margin-right: 1%; border-radius: 5px;" >Save Settings</button>
	</div>
</div>





<?php

$email_data = get_option('absb_ofer_email_contnets_datum');

?>
<div id="offer_emails_div">
	<h3 style="margin-left: 1%;">Email Template for Offer Received</h3>

	<table style="width: 98% !important; margin-left: 1%;" class="absb_rule_tables">	
		<tr>
			<td style="width: 40%;">
				<strong>From <span class="required" style="color: red; border:none; font-weight: 300;">*</span></strong>
			</td>
			<td style="width: 60%;">
				<input type="text" id="ofr_rec_from"  type="email"  style="width:60%;" value="<?php echo esc_attr($email_data['ofr_recieved_mail_from']); ?>">
			</td>
		</tr>
		<tr>
			<td>
				<strong>Subject <span class="required" style="color: red; border:none; font-weight: 300;">*</span></strong>
			</td>
			<td>
				<input type="text" id="ofr_rec_subject" style="width:60%;" value="<?php echo esc_attr($email_data['ofr_recieved_mail_subject']); ?>">
			</td>
		</tr>

		<tr>
			<td>
				<strong>Email Content <span class="required" style="color: red; border:none; font-weight: 300;">*</span></strong>
			</td>
			<td>
				<?php
				$content_ofr_recieved = $email_data['ofr_recieved_mail_content'];

				wp_editor($content_ofr_recieved  , 'email_temp_for_ofr_rcvd', '' );
				?>
			</td>
		</tr>

	</table>

	<h3 style="margin-left: 1%;">Email Template for Offer Accepted </h3>

	<table style="width: 98% !important; margin-left: 1%;" class="absb_rule_tables">	

		<tr>
			<td style="width: 40%;">
				<strong>Subject <span class="required" style="color: red; border:none; font-weight: 300;">*</span></strong>
			</td>
			<td style="width: 60%;">
				<input  type="text" id="subject_for_accept" style="width:60%;" value="<?php echo esc_attr($email_data['ofr_accepted_subject']); ?>">
			</td>
		</tr>

		<tr>
			<td>
				<strong>Email Content <span class="required" style="color: red; border:none; font-weight: 300;">*</span></strong>
			</td>
			<td>

				<?php
				$email_content_for_accepted = $email_data['ofr_accepted_content'];
				wp_editor($email_content_for_accepted, 'email_temp_for_accept' , '' );
				?>

			</td>
		</tr>
		<tr>
			<td></td>
			<td style="color: grey;">
				<strong>Note: </strong><span>You can use the following shortcodes in E-mail content</span><br>
				<span style="color:red;"><b>{product_name}</b></span> for Product Title<br>
				<span style="color:red;"><b>{Approved_Discount}</b></span> for Approved Discount Amount<br>					
				<span style="color:red;"><b>{valid_till}</b></span> for Discount Validity<br>
			</td>
		</tr>

	</table>

	<h3 style="margin-left: 1%;">Email Template for Offer Rejected</h3>


	<table style="width: 98% !important; margin-left: 1%;" class="absb_rule_tables">	

		<tr>
			<td style="width:40%;">
				<strong>Subject <span class="required" style="color: red; border:none; font-weight: 300;">*</span></strong>
			</td>
			<td style="width: 60%;">
				<input type="text" id="subject_for_reject" style="width:60%;" value="<?php echo esc_attr($email_data['ofr_rejeceted_subject']); ?>">
			</td>
		</tr>



		<tr>
			<td>
				<strong>Email Content <span class="required" style="color: red; border:none; font-weight: 300;">*</span></strong>
			</td>
			<td>

				<?php
				$content_ofr_rejected = $email_data['ofr_rejected_content'];
				wp_editor($content_ofr_rejected, 'email_temp_for_reject', ' ' );
				?>

			</td>

		</tr>
		<tr>
			<td></td>

			<td style="color: grey;">
				<strong>Note: </strong><span>You can use the following shortcode in E-mail content</span><br>

				<span style="color:red;"><b>{product_name}</b></span> for Product Title<br>
			</td>
		</tr>

	</table>
	<div style="text-align: right; margin-bottom: 1%;"  >
		<button type="button" id="offer_save_templates" style="background-color: #007cba; cursor: pointer; color: white; padding: 9px 30px; border:none; margin-right: 1%; border-radius: 5px;" >Save Templates</button>
	</div>

</div>

</div>

<style type="text/css">
	
	#absb_datatable_offer_wrapper {
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

	jQuery('#offer_select_roles').select2({
		minimumInputLength:1,
	});
	jQuery('#offer_select_category').select2();
	jQuery('#offer_select_product').select2({

		ajax: {
			url : '<?php echo filter_var(admin_url('admin-ajax.php')); ?>',
			dataType: 'json',
			type: 'post',
			delay: 250, 
			data: function (params) {
				return {
					q: params.term, 
					action: 'frq_bgt_search_productss_for_price_negotiate', 

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
	setTimeout(function(){


		jQuery('body').on('change', '#absb_offer_appliedon', function() {
			var offer_selected=jQuery('#absb_offer_appliedon').val();
			if ('products' == offer_selected) {
				jQuery('#offer_label_for_options').show();
				jQuery('#offer_1').show();
				jQuery('#offer_2').hide();
			}
			else if ('categories' == offer_selected) {
				jQuery('#offer_label_for_options').show();
				jQuery('#offer_1').hide();
				jQuery('#offer_2').show();	
			}


		});

		jQuery(document).ready(function(){
			jQuery('body').on('click', '#absb_offer_module', function(){

				jQuery('#rule_offer_div').show();
				jQuery('#gen_offer_div').hide();
				jQuery('#offer_emails_div').hide();

				jQuery('#offer_rule_settings').addClass('abcactive');
				jQuery('#offer_gen_settings').removeClass('abcactive');
				jQuery('#offer_email_settings').removeClass('abcactive');
			});
		});

		jQuery('body').on('click', '#offer_rule_settings', function(){

			jQuery('#rule_offer_div').show();
			jQuery('#gen_offer_div').hide();
			jQuery('#offer_emails_div').hide();

		})
		jQuery('body').on('click', '#offer_gen_settings', function(){

			jQuery('#rule_offer_div').hide();
			jQuery('#offer_emails_div').hide();
			jQuery('#gen_offer_div').show();

		})
		jQuery('body').on('click', '#offer_email_settings', function(){

			jQuery('#rule_offer_div').hide();
			jQuery('#offer_emails_div').show();
			jQuery('#gen_offer_div').hide();

		})


		jQuery('#absb_offer_add_new').click(function(){
			jQuery('#create_qty_rule_modal_123').show();



		})


		jQuery('.close').click(function(){
			jQuery('#create_qty_rule_modal_123').hide();



		})



		jQuery('body').on('click', '#offer_save_gen_settings', function(){





			var activate_offer = jQuery('#offer_mod_activate').prop('checked');
			var offer_show_onshop = jQuery('#offer_mod_onshop').prop('checked');

			var create_offer_btn_text = jQuery('#create_offer_txt').val();
			var view_ofr__btn_txt = jQuery('#view_offer_txt').val();
			var create_offer_btn_bg_color = jQuery('#create_offer_bg_clr').val();
			var create_offer_btn_text_clr = jQuery('#create_offer_txt_clr').val();
			var view_offer_btn_bg_color = jQuery('#view_offer_bg_clr').val();
			var view_offer_btn_text_clr = jQuery('#view_offer_txt_clr').val();
			var popoup_head_txt = jQuery('#table_head_txt').val();
			
			var view_popup_headtxt = jQuery('#view_table_head_txt').val();
			
			


			jQuery.ajax({
				url : '<?php echo filter_var(admin_url('admin-ajax.php')); ?>',

				type : 'post',
				data : {
					action : 'absb_saving_offers_general_settingss',
					activate_offer:activate_offer,
					offer_show_onshop:offer_show_onshop,

					view_ofr__btn_txt:view_ofr__btn_txt,
					create_offer_btn_bg_color:create_offer_btn_bg_color,

					create_offer_btn_text_clr:create_offer_btn_text_clr,
					view_offer_btn_bg_color:view_offer_btn_bg_color,

					view_offer_btn_text_clr:view_offer_btn_text_clr,
					popoup_head_txt:popoup_head_txt,

					view_popup_headtxt:view_popup_headtxt,
					
					create_offer_btn_text:create_offer_btn_text


				},
				success : function( response ) {
					window.onbeforeunload = null;







					jQuery('#absbsavemsg').html('<div class="notice notice-success is-dismissible elt_msg" ><p id="absb_messageonsave">Rule has been saved</p><button type="button" class="notice-dismiss hidedivv"><span class="screen-reader-text">Dismiss this notice.</span></button></div>')
					jQuery('#absbsavemsg').show();
					jQuery('#absb_messageonsave').html('General Settings have been successfully');
					jQuery("html, body").animate({ scrollTop: 0 }, "slow");
					jQuery("#absbsavemsg").delay(7000).fadeOut(3000);




				}

			});



		})





		jQuery('body').on('click', '#offer_save_rule', function(){



			var activate_offer_rule = jQuery('#offer_activate_rule').prop('checked');
			var absb_prc_neg_is_guest = jQuery('#absb_prc_neg_is_guest').prop('checked');
			var offer_rule_name = jQuery('#offer_rule_name').val();
			var offer_appliedon = jQuery('#absb_offer_appliedon').val();


			var offer_roles = jQuery('#offer_select_roles').val();


			if('products'==offer_appliedon){
				var offer_procat_ids=jQuery('#offer_select_product').val();
			}
			else if('categories'==offer_appliedon){
				var offer_procat_ids=jQuery('#offer_select_category').val();
			}


			if(''==offer_procat_ids){
				alert('PLEASE SELECT PRODUCT/CATEGORY');
				return;
			}


			jQuery.ajax({
				url : '<?php echo filter_var(admin_url('admin-ajax.php')); ?>',

				type : 'post',
				data : {
					action : 'absb_saving_rule_for_offer',
					activate_offer_rule:activate_offer_rule,
					offer_rule_name:offer_rule_name,
					offer_appliedon:offer_appliedon,

					offer_procat_ids:offer_procat_ids,
					offer_roles:offer_roles,
					absb_prc_neg_is_guest:absb_prc_neg_is_guest

				},
				success : function( response ) {
					window.onbeforeunload = null;
					datatable.ajax.reload();


					jQuery('.close').click();

					jQuery('#create_qty_rule_modal_123').hide();

					jQuery('#offer_rule_name').val('');
					jQuery('#absb_offer_appliedon').val('products');
					jQuery("#offer_select_product").val([]).trigger('change');
					jQuery("#offer_select_category").val([]).trigger('change');
					jQuery("#offer_select_roles").val([]).trigger('change');







					jQuery('#absbsavemsg').html('<div class="notice notice-success is-dismissible elt_msg" ><p id="absb_messageonsave">Rule has been saved</p><button type="button" class="notice-dismiss hidedivv"><span class="screen-reader-text">Dismiss this notice.</span></button></div>')
					jQuery('#absbsavemsg').show();
					jQuery('#absb_messageonsave').html('Rule has been saved successfully');
					jQuery("html, body").animate({ scrollTop: 0 }, "slow");
					jQuery("#absbsavemsg").delay(7000).fadeOut(3000);




				}

			});


		})





		var datatable = jQuery('#absb_datatable_offer').DataTable({

			ajax: {
				url: '<?php echo filter_var(admin_url('admin-ajax.php')); ?>?action=absbs_offer_get_all_rules_from_db_fordatatable'

			},
			columns: [
			{data: 'Rule Name'},
			{data: 'Applied on'},
			{data: 'Allowed Roles'},

			{data: 'Status'},


			{data: "Edit / Delete" ,render: function ( data, type, full ) {
				var btnhtml='<button type="button" value="'+data+'" style="background:white;border-color:green; color:green;" class="button-primary offer_edit_btn"><i class="fa fa-fw fa-edit"></i></button>';

				btnhtml = btnhtml + '<button style="margin-left:2%;background:white;border-color:red; color:red;" class="button-primary offer_delete_btn" value="'+data+'" type="button" id="offer_btn_dlt" ><i class="fa fa-fw fa-trash"><span class="fa-li"></i></button>';
				return btnhtml;
			}}

			],
		});







		jQuery('body').on('click', '#offer_btn_dlt', function(){

			if(!confirm('Are you sure to permanently remove this rule?')){
				return;
			}
			var index=jQuery(this).val();


			jQuery.ajax({
				url :'<?php echo filter_var(admin_url('admin-ajax.php')); ?>',

				type : 'post',
				data : {
					action : 'absb_offer_deleting_rule',      
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

		})





		jQuery('body').on('click', '.offer_edit_btn', function(){




			jQuery('#offer_edit_rules_div').find('.modal-body12').html('<center><h1>Loading...</h1></center>');


			var index=jQuery(this).val();
			jQuery('#offer_update_rules').val(index);

			jQuery("#offer_edit_rules_div").show();
			jQuery('body').on('click', '.close1', function(){

				jQuery("#offer_edit_rules_div").hide();
			})

			jQuery.ajax({
				url : '<?php echo filter_var(admin_url('admin-ajax.php')); ?>',

				type : 'post',
				data : {
					action : 'absb_offer_popup_for_edit',      
					index : index       

				},
				success : function( response ) {
					window.onbeforeunload = null;
					jQuery('#offer_edit_rules_div').find('.modal-body12').html(response);


					jQuery('body').on('change', '#absb_offer_appliedon1', function(){
						var absb_selected123=jQuery('#absb_offer_appliedon1').val();
						if ('products' == absb_selected123) {
							jQuery('#offer_label_for_options1').show();
							jQuery('#offer_11').show();
							jQuery('#offer_21').hide();
						}
						else if ('categories' == absb_selected123) {
							jQuery('#offer_label_for_options1').show();
							jQuery('#offer_11').hide();
							jQuery('#offer_21').show();	
						}
					})

					// datatable.ajax.reload();

				}
			})
		})



		jQuery('body').on('click', '#offer_update_rules',function(){
			var index = jQuery(this).val();

			var activate_offer_rule = jQuery('#offer_activate_rule1').prop('checked');
			var absb_prc_neg_is_guest = jQuery('#absb_prc_neg_is_guest1').prop('checked');
			var offer_rule_name = jQuery('#offer_rule_name1').val();
			var offer_appliedon = jQuery('#absb_offer_appliedon1').val();


			var offer_roles = jQuery('#offer_select_roles1').val();


			if('products'==offer_appliedon){
				var offer_procat_ids=jQuery('#offer_select_product1').val();
			}
			else if('categories'==offer_appliedon){
				var offer_procat_ids=jQuery('#offer_select_category1').val();
			}


			if(''==offer_procat_ids){
				alert('PLEASE SELECT PRODUCT/CATEGORY');
				return;
			}


			jQuery.ajax({
				url : '<?php echo filter_var(admin_url('admin-ajax.php')); ?>',

				type : 'post',
				data : {
					action : 'absb_updating_eidted_rule_for_offer',
					index:index,
					activate_offer_rule:activate_offer_rule,
					offer_rule_name:offer_rule_name,
					offer_appliedon:offer_appliedon,

					offer_procat_ids:offer_procat_ids,
					offer_roles:offer_roles,
					absb_prc_neg_is_guest:absb_prc_neg_is_guest

				},
				success : function( response ) {
					window.onbeforeunload = null;
					datatable.ajax.reload();


					jQuery('.close1').click();

					jQuery('#offer_edit_rules_div').hide();





					jQuery('#absbsavemsg').html('<div class="notice notice-success is-dismissible elt_msg" ><p id="absb_messageonsave">Rule has been saved</p><button type="button" class="notice-dismiss hidedivv"><span class="screen-reader-text">Dismiss this notice.</span></button></div>')
					jQuery('#absbsavemsg').show();
					jQuery('#absb_messageonsave').html('Rule has been updated successfully');
					jQuery("html, body").animate({ scrollTop: 0 }, "slow");
					jQuery("#absbsavemsg").delay(7000).fadeOut(3000);



				}


			})
		})




		jQuery('body').on('click', '#offer_save_templates', function(){

			jQuery('#email_temp_for_ofr_rcvd-html').click();
			jQuery('#email_temp_for_accept-html').click();
			jQuery('#email_temp_for_reject-html').click();

			var ofr_recieved_mail_from = jQuery('#ofr_rec_from').val(); 
			var ofr_recieved_mail_subject = jQuery('#ofr_rec_subject').val(); 
			var ofr_recieved_mail_content = jQuery('#email_temp_for_ofr_rcvd').val(); 

			var ofr_accepted_subject = jQuery('#subject_for_accept').val(); 
			var ofr_accepted_content = jQuery('#email_temp_for_accept').val(); 

			var ofr_rejeceted_subject = jQuery('#subject_for_reject').val(); 
			var ofr_rejected_content = jQuery('#email_temp_for_reject').val(); 

			if (ofr_recieved_mail_from == '' || ofr_recieved_mail_subject == '' || ofr_recieved_mail_content == '' || ofr_accepted_subject == '' || ofr_accepted_content == '' || ofr_rejeceted_subject == '' || ofr_rejected_content == '' ) {
				alert('Please insert content on all email related fields. ');
				return;
			}


			jQuery.ajax({
				url : '<?php echo filter_var(admin_url('admin-ajax.php')); ?>',

				type : 'post',
				data : {
					action : 'absb_saving_email_templates_for_offer_mod',
					ofr_recieved_mail_from:ofr_recieved_mail_from,
					ofr_recieved_mail_subject:ofr_recieved_mail_subject,
					ofr_recieved_mail_content:ofr_recieved_mail_content,

					ofr_accepted_subject:ofr_accepted_subject,
					ofr_accepted_content:ofr_accepted_content,

					ofr_rejeceted_subject:ofr_rejeceted_subject,
					ofr_rejected_content:ofr_rejected_content

				},
				success : function( response ) {
					window.onbeforeunload = null;
					datatable.ajax.reload();


					jQuery('.close').click();


					jQuery('#email_temp_for_ofr_rcvd-tmce').click();
					jQuery('#email_temp_for_accept-tmce').click();
					jQuery('#email_temp_for_reject-tmce').click();






					jQuery('#absbsavemsg').html('<div class="notice notice-success is-dismissible elt_msg" ><p id="absb_messageonsave">Rule has been saved</p><button type="button" class="notice-dismiss hidedivv"><span class="screen-reader-text">Dismiss this notice.</span></button></div>')
					jQuery('#absbsavemsg').show();
					jQuery('#absb_messageonsave').html('Templates have been saved successfully');
					jQuery("html, body").animate({ scrollTop: 0 }, "slow");
					jQuery("#absbsavemsg").delay(7000).fadeOut(3000);



				}


			})






		})

	},1000)
</script>
