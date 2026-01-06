<script type="text/javascript">

	setTimeout(function(){
		jQuery('#absb_select_product').select2({

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



		jQuery('#absb_select_category').select2();
		jQuery('#absb_customer_role').select2();
		jQuery('body').on('click', '#addranges' , function() {
			jQuery('#absb_rule_table_03').find('tr:last').after('<tr><td><input type="number" name="start" id="startrangee" class="starting" style="width: 100%; min-width: 100px;" >  </td><td><input type="number" name="end" id="endrangee" class="ending" style="width: 100%; min-width: 100px;"> </td><td><select id="discounttypee" class="distype" style="width: 100%; min-width: 100px;"><option value="fix">Fixed</option><option value="per">Percentage</option><option value="revised">Revised Price</option>      </select></td><td>      <input type="number" name="amount" id="disamountt" class="discountamount" style="width: 100%; min-width: 100px;">    </td><td><button type="button" class="del" style="margin-left: 35%; border:1px solid red; padding:8px 10px; background-color:white; color:red; cursor:pointer; border-radius:4px; "><i class="fa fa-trash"></i></button></td></tr>');

		});
		jQuery('body').on('click', '.del' , function() {
			jQuery(this).parent().parent().remove();

		});
		jQuery(document).ready(function(){
			jQuery('#qty_rule_div').show();
			jQuery('#qty_gen_div').hide();
			jQuery('#qtydisrulesetting').addClass('abcactive');


			jQuery('body').on('click', '#qtydisgensetting' , function() {
				jQuery('#qty_gen_div').show();
				jQuery('#qty_rule_div').hide();

			});
		});
		jQuery('body').on('click', '#qtydisrulesetting' , function() {
			jQuery('#qty_rule_div').show();
			jQuery('#qty_gen_div').hide();

		});
		jQuery('body').on('click', '.inner_buttons' , function() {
			jQuery('.inner_buttons').removeClass('abcactive');
			jQuery(this).addClass('abcactive');
			jQuery('.add_buttons').removeClass('activeee');

		});

		jQuery('body').on('click', '#absb_open_popup' , function() {

			jQuery('.modalpopup').show(); 

		});
		jQuery('body').on('click', '.close', function(){
			jQuery('.modalpopup').hide();
			jQuery('#absbsavemsg').hide();
		});
		jQuery('body').on('click', '.hidedivv', function(){
			jQuery('#absbsavemsg').hide();
		});





		jQuery('body').on('change', '#absb_appliedon', function(){
			var elt_selected=jQuery(this).val();
			if ('products'==elt_selected) {
				jQuery('#absb_label_for_options').show();
				jQuery('#absb_1').show();
				jQuery('#absb_2').hide();
			}
			else if ('categories' ==elt_selected) {
				jQuery('#absb_label_for_options').show();
				jQuery('#absb_1').hide();
				jQuery('#absb_2').show();     
			} 
		});


		jQuery('#absb_save_rule_settings_btn').on('click', function(){






			var activaterule=jQuery('#absb_active_rule').prop('checked');
			var absb_qty_dict_is_guest=jQuery('#absb_qty_dict_is_guest').prop('checked');
			var rulename=jQuery('#absb_rule_name').val();

			var appliedon=jQuery('#absb_appliedon').val();
			if('products'==appliedon){
				var procat_ids=jQuery('#absb_select_product').val();
			}
			else if('categories'==appliedon){
				var procat_ids=jQuery('#absb_select_category').val();
			}


			if(''==procat_ids){
				alert('PLEASE SELECT PRODUCT/CATEGORY');
				return;
			}





			var validity=false;

			jQuery('.starting').each(function(){
				if (jQuery(this).val() == '') {
					alert('Please fill Start Range field');   
					validity=true;                
					return;
				} 
			});
			if(validity){
				return;
			}




			jQuery('.starting').each(function(){
				if (jQuery(this).val() <= 0) {
					alert('Please fill valid range in Start Range field');      
					validity=true;                
					return;
				}
			});
			if(validity){
				return;
			}



			jQuery('.ending').each(function(){
				if (jQuery(this).val() == '') {
					alert('Please fill End Range field');
					validity=true;                            
					return;
				}
			});
			if(validity){
				return;
			}


			jQuery('.ending').each(function(){
				if (jQuery(this).val() <= 0) {
					alert('Please fill valid range in End Range field');  
					validity=true;                
					return;
				}
			});
			if(validity){
				return;
			}
			// jQuery('.ending').each(function(){
			// 	if (jQuery(this).val() < jQuery(this).parent().parent().find('.starting').val()) {
			// 		alert('End Range must be greater than start range'); 
			// 		validity=true;               
			// 		return;
			// 	}
			// });
			// if(validity){
			// 	return;
			// }






			jQuery('.distype').each(function(){
				if (jQuery(this).val() == '') {
					alert('Please Select one');
					validity=true;                           
					return;
				}
			});
			if(validity){
				return;
			}
			jQuery('.discountamount').each(function(){
				if (jQuery(this).val() == '') {
					alert('Please fill Discount Amount field');
					validity=true;                           
					return;
				}
			});
			if(validity){
				return;
			}



			jQuery('.discountamount').each(function(){
				if (jQuery(this).val() < 0) {
					alert('Please fill valid discount amount');    
					validity=true;               
					return;
				}
			});
			if(validity){
				return;
			}




			var startrange=[];

			jQuery('body').find('.starting').each(function() {
				startrange.push(jQuery(this).val());
			})


			var endrange=[];

			jQuery('body').find('.ending').each(function() {
				endrange.push(jQuery(this).val());
			})


			var discounttype=[];

			jQuery('body').find('.distype').each(function() {
				discounttype.push(jQuery(this).val());
			})



			var discountamount=[];

			jQuery('body').find('.discountamount').each(function() {
				discountamount.push(jQuery(this).val());
			})





			var allowedrole = jQuery('#absb_customer_role').val();




			jQuery.ajax({
				url : '<?php echo filter_var(admin_url('admin-ajax.php')); ?>',
				type : 'post',
				data : {
					action : 'absb_saving_first_rule_settings',
					rulename:rulename,
					activaterule:activaterule,
					appliedon:appliedon,
					procat_ids:procat_ids,
					startrange:startrange,
					endrange:endrange,
					discounttype:discounttype,
					discountamount:discountamount,
					allowedrole:allowedrole,
					absb_qty_dict_is_guest:absb_qty_dict_is_guest

				},
				success : function( response ) {
					window.onbeforeunload = null;
					datatable.ajax.reload();


					jQuery('.close').click();

					jQuery('#absb_rule_name').val('');
					jQuery('#startrange').val('');
					jQuery('#endrange').val('');
					jQuery('#disamount').val('');
					jQuery("#absb_select_product").val([]).trigger('change');
					jQuery("#absb_select_category").val([]).trigger('change');
					jQuery("#absb_select_category").val([]).trigger('change');


					jQuery('#absbsavemsg').html('<div class="notice notice-success is-dismissible elt_msg" ><p id="absb_messageonsave">Rule has been saved</p><button type="button" class="notice-dismiss hidedivv"><span class="screen-reader-text">Dismiss this notice.</span></button></div>')
					jQuery('#absbsavemsg').show();
					jQuery('#absb_messageonsave').html('Rule has been saved');
					jQuery("html, body").animate({ scrollTop: 0 }, "slow");
					jQuery("#absbsavemsg").delay(7000).fadeOut(3000);




				}

			});
		});


var datatable = jQuery('#absb_datatable').DataTable({

	ajax: {
		url: '<?php echo filter_var(admin_url('admin-ajax.php')); ?>?action=absb_get_all_rules_from_db'

	},
	columns: [
	{data: 'Rule Name'},
	{data: 'Applied On'},

	{data: 'Status'},
	{data: 'Allowed Role'},

	{data: "Edit / Delete" ,render: function ( data, type, full ) {
		var btnhtml='<button type="button" value="'+data+'" style="background:white;border-color:green; color:green;" class="button-primary absb_edit_btn"><i class="fa fa-fw fa-edit"></i></button>';

		btnhtml = btnhtml + '<button style="margin-left:2%;background:white;border-color:red; color:red;" class="button-primary absb_delete_btn" value="'+data+'" type="button" id="elt_btn_dlt" ><i class="fa fa-fw fa-trash"><span class="fa-li"></i></button>';
		return btnhtml;
	}}

	],
});

jQuery('body').on('click', '.absb_edit_btn' , function(){

	jQuery('#absb_edit_rules_div').find('.modal-body').html('<center><h1>Loading...</h1></center>');

	var index=jQuery(this).val();
	jQuery('#absb_update_rules').val(index);

	jQuery("#absb_edit_rules_div").show();
	jQuery('body').on('click', '.close1', function(){

		jQuery("#absb_edit_rules_div").hide();
	})

	jQuery.ajax({
		url : '<?php echo filter_var(admin_url('admin-ajax.php')); ?>',

		type : 'post',
		data : {
			action : 'absb_popup_for_edit',      
			index : index       

		},
		success : function( response ) {
			window.onbeforeunload = null;
			jQuery('#absb_edit_rules_div').find('.modal-body').html(response);


			var absb_selected=jQuery('#absb_appliedon1').val();
			if ('products' == absb_selected) {
				jQuery('#absb_label_for_options11').show();
				jQuery('#absb_11').show();
				jQuery('#absb_21').hide();
			}
			else if ('categories' == absb_selected) {
				jQuery('#absb_label_for_options11').show();
				jQuery('#absb_11').hide();
				jQuery('#absb_21').show();      
			}

			datatable.ajax.reload();

		}
	})
})



jQuery('body').on('change', '#absb_appliedon1', function() {
	var absb_selected=jQuery('#absb_appliedon1').val();
	if ('products' == absb_selected) {
		jQuery('#absb_label_for_options11').show();
		jQuery('#absb_11').show();
		jQuery('#absb_21').hide();
	}
	else if ('categories' == absb_selected) {
		jQuery('#absb_label_for_options11').show();
		jQuery('#absb_11').hide();
		jQuery('#absb_21').show();      
	}


});

jQuery('#absb_update_rules').on('click', function(){
	var index = jQuery(this).val();
	var activaterule=jQuery('#absb_active_rule1').prop('checked');
	var absb_qty_dict_is_guest=jQuery('#absb_qty_dict_is_guest1').prop('checked');
	var rulename=jQuery('#absb_rule_name1').val();

	var appliedon=jQuery('#absb_appliedon1').val();
	if('products'==appliedon){
		var procat_ids=jQuery('#absb_select_product11').val();
	}
	else if('categories'==appliedon){
		var procat_ids=jQuery('#absb_select_category11').val();
	}


	if(''==procat_ids){
		alert('PLEASE SELECT PRODUCT/CATEGORY');
		return;
	}
	var startrange=[];

	jQuery('body').find('.starting1').each(function() {
		startrange.push(jQuery(this).val());
	})


	var endrange=[];

	jQuery('body').find('.ending1').each(function() {
		endrange.push(jQuery(this).val());
	})


	var discounttype=[];

	jQuery('body').find('.distype1').each(function() {
		discounttype.push(jQuery(this).val());
	})



	var discountamount=[];

	jQuery('body').find('.discountamount1').each(function() {
		discountamount.push(jQuery(this).val());
	})





	var validity=false;

	jQuery('.starting1').each(function(){
		if (jQuery(this).val() == '') {
			alert('Please fill Start Range field');  
			validity=true;               
			return;
		}
	});
	if(validity){
		return;
	}




	jQuery('.starting1').each(function(){
		if (jQuery(this).val() <= 0) {
			alert('Please fill valid range in Start Range field');     
			validity=true;               
			return;
		}
	});
	if(validity){
		return;
	}



	jQuery('.ending1').each(function(){
		if (jQuery(this).val() == '') {
			alert('Please fill End Range field');
			validity=true;                           
			return;
		}
	});
	if(validity){
		return;
	}


	jQuery('.ending1').each(function(){
		if (jQuery(this).val() <= 0) {
			alert('Please fill valid range in End Range field'); 
			validity=true;               
			return;		
		}
	});
	if(validity){
		return;
	}

	// jQuery('.ending1').each(function(){
	// 	if (jQuery(this).val() < jQuery(this).parent().parent().find('.starting1').val()) {
	// 		alert('End Range must be greater than start range'); 
	// 		validity=true;               
	// 		return;
	// 	}
	// });
	// if(validity){
	// 	return;
	// }






	jQuery('.distype1').each(function(){
		if (jQuery(this).val() == '') {
			alert('Please Select one');
			validity=true;                           
			return;
		}
	});
	if(validity){
		return;
	}
	jQuery('.discountamount1').each(function(){
		if (jQuery(this).val() == '') {
			alert('Please fill Discount Amount field');
			validity=true;                           
			return;
		}
	});
	if(validity){
		return;
	}



	jQuery('.discountamount1').each(function(){
		if (jQuery(this).val() < 0) {
			alert('Please fill valid discount amount');    
			validity=true;               
			return;
		}
	});
	if(validity){
		return;
	}






	var allowedrole = jQuery('#absb_customer_role1').val();



	jQuery.ajax({
		url : '<?php echo filter_var(admin_url('admin-ajax.php')); ?>',

		type : 'post',
		data : {
			action : 'absb_update_edited_rules_settings',
			rulename:rulename,
			activaterule:activaterule,
			appliedon:appliedon,
			procat_ids:procat_ids,
			startrange:startrange,
			endrange:endrange,
			discounttype:discounttype,
			discountamount:discountamount,
			allowedrole:allowedrole,
			absb_qty_dict_is_guest:absb_qty_dict_is_guest,
			index:index

		},
		success : function( response ) {
			window.onbeforeunload = null;
			datatable.ajax.reload();


			jQuery('.close1').click();




			jQuery('#absbsavemsg').html('<div class="notice notice-success is-dismissible elt_msg" ><p id="absb_messageonsave">Rule has been saved</p><button type="button" class="notice-dismiss hidedivv"><span class="screen-reader-text">Dismiss this notice.</span></button></div>')
			jQuery('#absbsavemsg').show();
			jQuery('#absb_messageonsave').html('Rule has been updated successfully');
			jQuery("html, body").animate({ scrollTop: 0 }, "slow");
			jQuery("#absbsavemsg").delay(7000).fadeOut(3000);




		}

	});
});



jQuery('body').on('click', '.absb_delete_btn', function(){
	if(!confirm('Are you sure to permanently remove this rule?')){
		return;
	}
	var index=jQuery(this).val();


	jQuery.ajax({
		url :'<?php echo filter_var(admin_url('admin-ajax.php')); ?>',

		type : 'post',
		data : {
			action : 'absb_deleting_rule',      
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


jQuery('#absb_save_general_settings_btn').on('click', function(){




	var location = jQuery('#absb_location').val();
	var tabletitle = jQuery('#tableheading').val();
	if ('' == tabletitle) {
		alert('Please enter title of table');
		return;
	}
	var heading_1 = jQuery('#firstth').val();
	if ('' == heading_1) {
		alert('Please fill all fields of table headings');
		return;
	}

	var heading_2 = jQuery('#secondth').val();
	if ('' == heading_2) {
		alert('Please fill all fields of table headings');
		return;
	}
	var heading_3 = jQuery('#thirdth').val();
	if ('' == heading_3) {
		alert('Please fill all fields of table headings');
		return;
	}
	var head_bg_color = jQuery('#tbh_bgcolor').val();
	var head_text_color = jQuery('#tbh_txtcolor').val();
	var table_bg_color = jQuery('#tbl_bgcolor').val();
	var table_text_color = jQuery('#tbl_text_color').val();
	var qty_dsct_activate = jQuery('#qty_dsct_activate').prop('checked');



	jQuery.ajax({
		url : '<?php echo filter_var(admin_url('admin-ajax.php')); ?>',

		type : 'post',
		data : {
			action : 'absb_save_general_settings_quantity_discount',
			location:location,
			qty_dsct_activate:qty_dsct_activate,
			tabletitle:tabletitle,
			heading_1:heading_1,
			heading_2:heading_2,
			heading_3:heading_3,
			head_bg_color:head_bg_color,
			head_text_color:head_text_color,
			table_bg_color:table_bg_color,
			table_text_color:table_text_color


		},
		success : function( response ) {
			window.onbeforeunload = null;

			jQuery('.close1').click();


			jQuery('#absbsavemsg').html('<div class="notice notice-success is-dismissible elt_msg" ><p id="absb_messageonsave">Rule has been saved</p><button type="button" class="notice-dismiss hidedivv"><span class="screen-reader-text">Dismiss this notice.</span></button></div>')
			jQuery('#absbsavemsg').show();
			jQuery('#absb_messageonsave').html('General has been Saved successfully');
			jQuery("html, body").animate({ scrollTop: 0 }, "slow");
			jQuery("#absbsavemsg").delay(7000).fadeOut(3000);




		}

	});
});



jQuery('#frq_bgt_gen_save').on('click', function(){

	var frq_bgt_activate=jQuery('#frq_bgt_activate').prop('checked');
	var frq_bgt_image=jQuery('#frq_bgt_image').prop('checked');
	var frq_bgt_price=jQuery('#frq_bgt_price').prop('checked');
	var frq_bgt_cartbtn=jQuery('#frq_bgt_cartbtn').prop('checked');
	var frq_bgt_tablename=jQuery('#frq_bgt_tablename').prop('checked');

	var frq_bgt_enable_ad_cart=jQuery('#frq_bgt_enable_ad_cart').prop('checked');
	var frq_bgt_enable_ad_quantity=jQuery('#frq_bgt_enable_ad_quantity').prop('checked');

	var frq_bgt_cart_btn_txt=jQuery('#frq_bgt_cart_btn_txt').val();
	var frq_bgt_cart_btn_txt_variables=jQuery('#frq_bgt_cart_btn_txt_variables').val();
	var bg_clr_btns=jQuery('#bg_clr_btns').val();
	var txt_clr_btns=jQuery('#txt_clr_btns').val();
	var number_to_show = jQuery('input:radio[name="number_frq"]:checked').val();





	var frq_bgt_location = jQuery('#frq_bgt_location').val();
	var frq_bgt_tabletitle = jQuery('#frq_bgt_tabletitle').val();










	jQuery.ajax({
		url : '<?php echo filter_var(admin_url('admin-ajax.php')); ?>',

		type : 'post',
		data : {
			action : 'frq_bgt_saving_general_settings',
			frq_bgt_activate:frq_bgt_activate,
			frq_bgt_image:frq_bgt_image,
			frq_bgt_price:frq_bgt_price,
			frq_bgt_cartbtn:frq_bgt_cartbtn,
			frq_bgt_location:frq_bgt_location,
			frq_bgt_tablename:frq_bgt_tablename,
			frq_bgt_tabletitle:frq_bgt_tabletitle,

			frq_bgt_enable_ad_cart:frq_bgt_enable_ad_cart,
			frq_bgt_enable_ad_quantity:frq_bgt_enable_ad_quantity,
			frq_bgt_cart_btn_txt:frq_bgt_cart_btn_txt,
			frq_bgt_cart_btn_txt_variables:frq_bgt_cart_btn_txt_variables,
			bg_clr_btns:bg_clr_btns,
			txt_clr_btns:txt_clr_btns,
			number_to_show:number_to_show
		},
		success : function( response ) {
			window.onbeforeunload = null;
			datatable.ajax.reload();


			jQuery('.close1').click();




			jQuery('#absbsavemsg').html('<div class="notice notice-success is-dismissible elt_msg" ><p id="absb_messageonsave">Rule has been saved</p><button type="button" class="notice-dismiss hidedivv"><span class="screen-reader-text">Dismiss this notice.</span></button></div>')
			jQuery('#absbsavemsg').show();
			jQuery('#absb_messageonsave').html('Settings have been saved.');
			jQuery("html, body").animate({ scrollTop: 0 }, "slow");
			jQuery("#absbsavemsg").delay(7000).fadeOut(3000);




		}

	});
});


},2000);



jQuery('body').on('click', '#addranges1' , function() {

	jQuery('#absb_rule_table_03a').find('tr:last').after('<tr><td><input type="number" name="start" id="startrangeee" class="starting1" style="width: 100%; min-width: 100px;"></td><td><input type="number" name="end" id="endrangeee" class="ending1" style="width: 100%; min-width: 100px;"></td><td><select id="discounttypeee" class="distype1" style="width: 100%; min-width: 100px;"><option value="fix">Fixed</option><option value="per">Percentage</option><option value="revised">Revised Price</option></select></td><td><input type="number" name="amount" id="disamounttt" class="discountamount1" style="width: 100%; min-width: 100px;">     </td><td><button type="button" class="del" style="margin-left: 35%; border:1px solid red; padding:8px 10px; background-color:white; color:red; cursor:pointer; border-radius:4px;"><i class="fa fa-trash"></i></button></td></tr>');
});

</script>
