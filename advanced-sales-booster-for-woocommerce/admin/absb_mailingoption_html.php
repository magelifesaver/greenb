<div id="main_mailing_inner_div">

	<div id="notify_buttons_div" style="margin-top: 1%; margin-bottom: 2%; ">
		<button type="button" id="mailing_template_settings" class="inner_buttons abcactive" style=" padding: 9px 30px; border-radius: unset !important;
		font-weight: 500; border-bottom: none; margin-left: 5px; font-size: 13px;">Template Settings</button>
		<button type="button" id="email_settings" class="inner_buttons" style=" padding: 9px 30px; margin: unset !important;
		border-radius: unset !important;   font-weight: 500; border-bottom: none; font-size:13px; ">Send Specific Email</button>
		<button type="button" id="gen_email_settings" class="inner_buttons" style=" padding: 9px 30px; margin: unset !important;
		border-radius: unset !important;   font-weight: 500; border-bottom: none; font-size:13px; ">Send General Email</button>
		<hr>
	</div>



	<div id="template_div" style="display: none;">
		<?php
		$data = get_option('saving_template_setting');
		?>
		<div style="margin-right: 3%; text-align: right;">
			<button type="button" id="mail_add_template" style="background-color: green; color: white; padding: 8px 12px; font-size: 14px; font-weight: 500; cursor: pointer; border:1px solid green; border-radius: 3px !important;"><i class="fa-solid fa-plus"></i> Add Template</button>
		</div>

		<div class="mail_templates">
			<?php 
			foreach ($data['temp_content'] as $key => $value) {              

				?>
				<table class="absb_rule_tables temp_table" style="width: 98% !important; margin-left: 1% !important;" >

					<tr>
						<td style="width: 40%;">
							<strong>Template Name <span style="color:red;">*</span></strong>
						</td>
						<td style="width: 50%;">
							<input type="text" name="temp_name" id="tempname" class="input_type temp_name" value="<?php echo esc_attr($value[0]); ?>" >
						</td>
						<td style="width: 10%;">
						</td>
					</tr>
					<tr>
						<td style="width: 40%;">
							<strong>Email Subject <span style="color:red;">*</span></strong>
						</td>
						<td style="width: 50%;">
							<input type="text" name="temp_sub" id="e_subject123" class="input_type e_subject" value="<?php echo esc_html($value[2]); ?>" >
						</td>
						<td style="width: 10%;">
						</td>
					</tr>
					<tr>
						<td>
							<strong>Write Your Email Content <span style="color:red;">*</span></strong>
						</td>
						<td>
							<?php 
							$settings = array( 
								'editor_height' => 150,
								'textarea_rows' => 5
							);
							wp_editor( esc_html($value[1]), 'mailig_content' . $key , $settings ); 
							?>
						</td>
					</tr>
					<tr>
						<td></td>
						<td></td>

						<?php 
						if ( 0 != $key) {
							?>
							<td><button type="button" class="del_template" style="margin-left: 35%; border:1px solid red; padding:9px 22px; background-color:white; color:red; cursor:pointer; border-radius:4px;">Remove</button></td>

							<?php 
						} else {

							?>
							<td><button type="button" class="" style="margin-left: 35%; border:none; padding:9px 10px; background-color:transparent; color:transparent; ">mmmmmm</button></td>
							<?php
						}
						?>
					</tr>

				</table>
				<?php

			}
			?>
		</div>
		<div style="text-align: right; margin-right: 1%; margin-bottom: 2%;">
			<button type="button" id="save_template_settings" style="background-color: #007cba; color: white; cursor: pointer; color: white; padding: 9px 30px; border:none; border-radius: 5px;"><i class="icon-save"></i> Save Templates</button>
		</div>
	</div>
	<div id="mail_div" style="display: none;">
		<?php 
		$absb_args = array(
			'posts_per_page' => '-1',
			'post_status'           => 'publish',
			'post_type'      =>   array('product', 'product_variation')
		);
		$absb_the_query = new WP_Query( $absb_args );
		$notify_product_options_html='';

		while ( $absb_the_query -> have_posts( ) ) {
			$absb_the_query -> the_post();   
			$absbproduct=wc_get_product(get_the_ID());
			if ('variable' != $absbproduct->get_type()) {

				$notify_product_options_html=$notify_product_options_html . '<option  class="absb_option-item" value=" ' . get_the_ID() . '" >' . get_the_title() . '</option>';
			}
		}
		$notify_product_category_html='';
		$absb_parentid = get_queried_object_id();
		$absb_args = array(
			'numberposts' => -1,
			'taxonomy' => 'product_cat',
		);
		$absb_terms = get_terms($absb_args);

		if ( $absb_terms ) {   
			foreach ( $absb_terms as $absb_term1 ) {

				$notify_product_category_html = $notify_product_category_html . '<option class="absb_catopt" value="' . $absb_term1->term_id . '" >' . $absb_term1->name . '</option>';

			}  
		}
		
		?>

		<div>
			<table class="absb_rule_tables" style="width: 98% !important; margin-left: 1% !important;" >
				<tr>
					<td style="width: 40%;">
						<strong>Send Email to </strong>
					</td>

					<td style="width: 60%;">
						<select name="absb_selectone" id="email_send_to" class="absbselect" style="width: 100% !important; max-width: 100% !important; padding:5px; ">
							<option value="products">Specific Product Buyers</option>
							<option value="user_roles">Specific User Roles</option>
							<option value="users">Specific Users</option>

						</select>
					</td>
				</tr>
				<tr id="products_tr">
					<td id="emails_label" style="width: 40%;">
						<strong>Select Product to find Buyers <div class="tooltip"><i class="fa fa-question-circle tooltip" aria-hidden="true" style="cursor: help;"></i>
							<span class="tooltiptext">Email will be sent to those customers who purchased the selected product(s)</span>
						</div></strong>
					</td>
					<td id="emails_1" style="width: 60%;" >
						<select multiple id="email_select_product" class="absbselect" name="multi[]">

						</select>
					</td>

				</tr>


				<tr id="userroles_tr">
					<td>
						<strong>Select Specific User's Roles</strong>
					</td>
					<td>
						<?php 
						global $wp_roles;
						$emails_all_roles = $wp_roles->get_names();
						?>
						<select class="email_user_roleclass" id="email_user_role" multiple="multiple" class="form-control " style="width: 98%;">
							<?php
							foreach ($emails_all_roles as $key_role => $value_role) {
								?>
								<option value="<?php echo filter_var($key_role); ?>"><?php echo filter_var(ucfirst($value_role)); ?></option>
								<?php
							}
							?>

						</select> 
					</td>   
				</tr>   

				<tr id="users_tr">
					<td>
						<strong>Select Specific Users</strong>
					</td>
					<td>
						<select multiple id="email_user" name="user[]" class="frgselect">
							<?php
							$users = get_users();
							$email = $user->user_email;
							foreach ($users as $key => $user) {
								echo filter_var('<option value="' . $user->user_email . '"  >' . $user->display_name . ' </option>');
							}
							?>
						</select>
					</td>
				</tr>
				<tr>
					<td>
						<strong>Select Mail Template</strong>
					</td>
					<td>

						<select id="select_template" style="width: 100% !important; margin-top: 3px !important; max-width: 100% !important; padding: 6px;">
							<?php
							foreach ($data['temp_content'] as $key => $value) {
								?>

								<option><?php echo esc_html($value[0]); ?> </option>
								<?php
							}
							?>
						</select>
					</td>
				</tr>
			</select>
		</table>
		<div style="text-align: right; width: 100%; margin-bottom: 2%;">
			<button type="button" name="sendmail" id="send_email" class="save_btns" style="background-color: #007cba; cursor: pointer; color: white; padding: 9px 30px; border:none; border-radius: 4px; margin-right: 1%;">Send Email</button>
		</div>
	</div>
</div>
<div id="gen_email_div">
	<strong style="color: grey; width: 98% !important; margin-left: 1% !important;"><i>Using this feature you can send E-mail to every user on the site</i></strong>
	<table class="absb_rule_tables" style="width: 98% !important; margin-left: 1% !important;" >

		<tr>
			<td style="width: 40%;">
				<strong>Email Subject <span style="color:red;">*</span></strong>
			</td>
			<td style="width: 60%;">
				<input type="text" name="temp_name" id="email_to_all_subject" class="input_type email_to_all" style="width: 43% !important;" >
			</td>
			<td>
			</td>
		</tr>
		<tr>
			<td>
				<strong>Write Email <span style="color:red;">*</span></strong>
			</td>
			<td>
				<?php 
				$settings = array( 
					'editor_height' => 150,
					'textarea_rows' => 5
				);
				wp_editor( '', 'emailtoall' , $settings  ); 
				?>
			</td>
		</tr>

	</table>
	<div style="text-align: right; width: 100%; margin-bottom: 2%;">
		<button type="button" name="sendmail" id="send_email_for_gen" class="save_btns" style="background-color: #007cba; cursor: pointer; color: white; padding: 9px 30px; border:none; border-radius: 4px; margin-right: 1%;">Send Email to All Users</button>
	</div>

	<div class="modelpopup1" id="send_email_bulk_in_process" role="dialog" style="display: none;">
		<div class="modal-dialog">
			<div class="modal-content1" style="margin-top: 5%;">
				<div class="modal-header" style="">
					<button type="button" class="close1" id="closet432112112es" data-dismiss="modal" style="margin-top: -1%; color: red !important;">&times;</button>

					<h2 class="modal-title" style="color: #000 !important; ">Task Added</h2><hr>

				</div>
				<div class="modal-body1 animate__animated animate__flash" >

					Task has been added at backend. All emails will be sent shortly.


				</div>

			</div>
		</div>
	</div>


</div>

</div>
<div id="wp_editor_div" style="display: none;">
	<?php
	$settings = array( 
		'editor_height' => 150,
		'textarea_rows' => 5
	);
	$data =  wp_editor( '', 'mailig_content', $settings  ); 
	?>
</div>

<script type="text/javascript">
	var length = 0;
	jQuery(document).ready(function(){
		length = jQuery('.temp_table').length;
	});

	jQuery('#email_select_category').select2();
	jQuery('#email_select_product').select2({

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

	jQuery('#email_user_role').select2();
	jQuery('#email_user').select2();
	jQuery('#email_user_for_gen').select2();
	jQuery('#email_user_role_for_gen').select2();

	jQuery('#users_tr').hide();
	jQuery('#products_tr').show();
	jQuery('#userroles_tr').hide(); 

	jQuery('body').on('change', '#email_send_to', function(){
		var selected=jQuery(this).val();
		if ('products'==selected) {
			jQuery('#products_tr').show();
			jQuery('#userroles_tr').hide();
			jQuery('#users_tr').hide();
		}
		else if ('user_roles' ==selected) {
			jQuery('#userroles_tr').show();
			jQuery('#products_tr').hide();
			jQuery('#users_tr').hide(); 
		} else if ('users' ==selected) {
			jQuery('#users_tr').show();
			jQuery('#products_tr').hide();
			jQuery('#userroles_tr').hide(); 
		} 
	});

	jQuery(document).ready(function(){
		jQuery('body').on('click', '#absb_mailingoption', function(){
			jQuery('#template_div').show();
			jQuery('#mail_div').hide();
			jQuery('#gen_email_div').hide();
			jQuery('#mailing_template_settings').addClass('abcactive');
			jQuery('#email_settings').removeClass('abcactive');
			jQuery('#gen_email_settings').removeClass('abcactive');
		});
	});

	jQuery('body').on('click', '#mailing_template_settings', function(){
		jQuery('#template_div').show();
		jQuery('#mail_div').hide();
		jQuery('#gen_email_div').hide();

	})
	jQuery('body').on('click', '#email_settings', function(){

		jQuery('#template_div').hide();
		jQuery('#gen_email_div').hide();
		jQuery('#mail_div').show();

	})
	jQuery('body').on('click', '#gen_email_settings', function(){

		jQuery('#template_div').hide();
		jQuery('#gen_email_div').show();
		jQuery('#mail_div').hide();

	})
	jQuery('body').on('click', '#mail_add_template', function(){
		jQuery.ajax({
			url : '<?php echo filter_var(admin_url('admin-ajax.php')); ?>',
			type : 'post',
			data : {
				action : 'creating_wp_editor_for_mailing_option',
			},
			success : function( response ) {
				response = response.replace( /mailig_content/gi ,'mailig_content'+length);
				jQuery("html, body").animate({ scrollTop: jQuery(document).height() }, 2000);
				jQuery('.mail_templates').append(response);
				tinymce.execCommand('mceAddEditor', true, 'mailig_content'+length); 
				length =parseInt(length) + 1;

			}

		});

	})

	jQuery('body').on('click', '.del_template', function(){
		jQuery(this).parent().parent().parent().parent().remove();
	})

	jQuery('#save_template_settings').on('click', function(){
		jQuery('.switch-html').click();
		var temp_content=[];   
		var is_stop = false;
		jQuery('body').find('.temp_table').each(function() {
			var temp_name=[];
			var e_name = jQuery(this).find('#tempname').val()
			temp_name.push(e_name);
			if (''== e_name) {
				alert('Please enter Template Name');
				is_stop=true; 
				return;

			}

			var e_content = jQuery(this).find('.wp-editor-area').val()
			temp_name.push(e_content);
			if (''==e_content) {
				alert('E-Mail Content box can not be empty');

				is_stop=true; 
				return;
			}
			var e_subject = jQuery(this).find('.e_subject').val();

			temp_name.push(e_subject);
			if (''==e_subject) {
				alert('Please Enter the subject of E-mail');

				is_stop=true; 
				return;
			}
			temp_content.push(temp_name);
		})

		if (is_stop) {

			return false;
		}

		jQuery.ajax({
			url : '<?php echo filter_var(admin_url('admin-ajax.php')); ?>',
			type : 'post',
			data : {
				action : 'saving_mail_template_setting',
				temp_content:temp_content

			},
			success : function( response ) {
				window.onbeforeunload = null;
				// location.reload();
				jQuery('.switch-tmce').click();
				jQuery('#absbsavemsg').html('<div class="notice notice-success is-dismissible elt_msg" ><p id="absb_messageonsave">Rule has been saved</p><button type="button" class="notice-dismiss hidedivv"><span class="screen-reader-text">Dismiss this notice.</span></button></div>')
				jQuery('#absbsavemsg').show();
				jQuery('#absb_messageonsave').html('Settings have been saved');
				jQuery("html, body").animate({ scrollTop: 0 }, "slow");
				jQuery("#absbsavemsg").delay(7000).fadeOut(3000);
				jQuery.ajax({
					url : '<?php echo filter_var(admin_url('admin-ajax.php')); ?>',
					type : 'post',
					data : {
						action : 'ajax_for_creating_select_for_template',
					},
					success : function( response ) {
						jQuery('body').find('#select_template').html(response);
					}

				});
			}

		});
	});

	jQuery('#send_email').on('click', function(){

		var email_template = jQuery('#select_template').val();
		var send_mail_to =jQuery('#email_send_to').val();
		if('products'==send_mail_to){
			var send_ids=jQuery('#email_select_product').val();

			if(''==send_ids){
				alert('Please select at least one Product');
				return;
			}

		} else if('user_roles'==send_mail_to){
			var send_ids=jQuery('#email_user_role').val();

			if(''==send_ids){
				alert('Please select at least one User Role');
				return;
			}

		} else if('users'==send_mail_to){
			var send_ids=jQuery('#email_user').val();
			if(''==send_ids){
				alert('Please select at least one User');
				return;
			}

		}


		jQuery('#send_email').html('<i class="fa fa-spinner fa-spin" id="spinbutton"></i> Please Wait');
		jQuery('#send_email').prop('disabled', true);
		jQuery('body').css('cursor' , 'wait');

		jQuery.ajax({
			url : '<?php echo filter_var(admin_url('admin-ajax.php')); ?>',
			type : 'post',
			data : {
				action : 'absb_sending_mail',
				send_mail_to:send_mail_to,
				send_ids:send_ids,
				email_template:email_template
			},
			success : function( response ) {

				window.onbeforeunload = null;

				jQuery('#absbsavemsg').html('<div class="notice notice-success is-dismissible elt_msg" ><p id="absb_messageonsave">Rule has been saved</p><button type="button" class="notice-dismiss hidedivv"><span class="screen-reader-text">Dismiss this notice.</span></button></div>')
				jQuery('#absbsavemsg').show();
				jQuery('#absb_messageonsave').html('E-mail Sent Successfully');
				jQuery("html, body").animate({ scrollTop: 0 }, "slow");
				jQuery("#absbsavemsg").delay(7000).fadeOut(3000);
				jQuery('#send_email').prop('disabled', false);
				jQuery('body').css('cursor' , 'unset');
				jQuery('#send_email').html('Send Email')

				jQuery("#email_select_product").val([]).trigger('change');
				jQuery("#email_user_role").val([]).trigger('change');
				jQuery("#email_user").val([]).trigger('change');

			}

		});
	});


	jQuery('#send_email_for_gen').on('click', function(){

		var email_to_all_subject = jQuery('#email_to_all_subject').val()


		if(''==email_to_all_subject){
			alert('Please enter the subject of E-mail');
			return;
		}

		jQuery('#emailtoall-html').click()
		var sendall_email = jQuery('#emailtoall').val()

		if(''==sendall_email){
			alert('E-mail content can not be empty');
			return;
		}


		jQuery('#send_email_for_gen').html('<i class="fa fa-spinner fa-spin" id="spinbutton"></i> Please Wait');
		jQuery('#send_email_for_gen').prop('disabled', true);
		jQuery('body').css('cursor' , 'wait');


		jQuery.ajax({
			url : '<?php echo filter_var(admin_url('admin-ajax.php')); ?>',
			type : 'post',
			data : {
				action : 'absb_sending_mail_to_all',


				sendall_email:sendall_email,
				email_to_all_subject:email_to_all_subject


			},
			success : function( response ) {

				jQuery('body').on('click', '#closet432112112es', function() {
					jQuery('#send_email_bulk_in_process').hide();
				})


				// jQuery('#absbsavemsg').html('<div class="notice notice-success is-dismissible elt_msg" ><p id="absb_messageonsave">Rule has been saved</p><button type="button" class="notice-dismiss hidedivv"><span class="screen-reader-text">Dismiss this notice.</span></button></div>')
				// jQuery('#absbsavemsg').show();
				// jQuery('#absb_messageonsave').html('E-mail Sent Successfully');
				// jQuery("html, body").animate({ scrollTop: 0 }, "slow");
				// jQuery("#absbsavemsg").delay(7000).fadeOut(3000);



				jQuery('#send_email_for_gen').prop('disabled', false);
				jQuery('body').css('cursor' , 'unset');
				jQuery('#send_email_for_gen').html('Send Email to All Users')

				jQuery('#email_to_all_subject').val('');
				jQuery('#emailtoall').val('');

				jQuery('.switch-tmce').click();


				jQuery('#send_email_bulk_in_process').show();
				jQuery('#send_email_bulk_in_process').delay(7000).fadeOut(2000);



			}

		});
	});


</script>
