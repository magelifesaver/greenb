jQuery(document).ready(function($){
	
	"user strict";
	jQuery(".color_sepctrum").spectrum({
		color: "#000",
		preferredFormat: "hex",
	});

	jQuery('.afreg_additional_multi_select').select2();
	
	jQuery('form#registerform').attr('enctype','multipart/form-data');
	jQuery('.woocommerce-form-register').attr('enctype','multipart/form-data');
	jQuery('.woocommerce-EditAccountForm').attr('enctype','multipart/form-data');
	

	jQuery('.woocommerce-account-fields div.create-account').append(jQuery('div.afreg_extra_fields'));


	// Dependable Fields.
	check_dependable();
	// setTimeout(function(){ check_dependable(); },2000);
	$(document).on('change','.af-dependable-field input , .afreg-is-radio-dependable , .afreg-is-checkbox-dependable , .afreg-is-multi-checkbox-dependable , .afreg-is-multi-select-dependable , .afreg-is-select-dependable , #afreg_select_user_role',function(){
		check_dependable();
	});

	function	check_dependable() {

		var all_dependable_ids_array 			=  [];

		let other_filed_type 					= 'text number textarea email number password fileupload color description heading googlecaptcha timepicker datepicker';
		$('.af-dependable-field').each( function() {
			if( $(this).find('afreg-dependable-on-rules') ) {

				var hide_or_show_this_element 	= true; 

				let current_post_id = $(this).data('current_post_id'); 

				let user_select_role_at_reg = $('#afreg_select_user_role').length && $('#afreg_select_user_role').val() ? $('#afreg_select_user_role').val() : 'No Selected Yet';
				let admin_selected_role 	= $(this).find('.afreg-dependable-on-rules').length && $(this).find('.afreg-dependable-on-rules').val() ? $(this).find('.afreg-dependable-on-rules').val() : '';

				if ( admin_selected_role != '' && admin_selected_role != undefined ) {

					if ( admin_selected_role.indexOf( user_select_role_at_reg ) == -1 ) {
						
						$(this).hide('fast');
						// console.log('role not match admin_selected_role => ' + admin_selected_role + ' --- user_select_role_at_reg ==> ' + user_select_role_at_reg + ' ---- afreg_additional_'+current_post_id  );
						hide_or_show_this_element 	= false;
					}

				}

				if ( $(this).find('.afreg-dependable-field-on').length && $(this).find('.afreg-dependable-field-on').val() ) {

					let current_field_type 						= $(this).find('.afreg-dependable-field-on').data('current_field_type');
					let dependable_field_option 				= $(this).find('.afreg-dependable-field-on').data('dependable_field_option');
					let dependent_on_field_id 					= $(this).find('.afreg-dependable-field-on').val();
					let dependable_field_type 					= $(this).find('.afreg-dependable-field-on').data('dependable_field_type');
					let selected_opt_of_dependable_field 		= '';

					// User Selected Data.
					let selected_dependent_filed_value 			= '';

					if ( 'radio' == dependable_field_type && $('.afreg_additional_'+dependent_on_field_id+':checked').val() != undefined && $('.afreg_additional_'+dependent_on_field_id+':checked').val()) {

						selected_dependent_filed_value 					= $('.afreg_additional_'+dependent_on_field_id+':checked').val();

					} else if ( 'select' == dependable_field_type && $('.afreg_additional_'+ dependent_on_field_id).val() != undefined && $('.afreg_additional_'+ dependent_on_field_id).val()  ) {
						selected_dependent_filed_value 					= $('.afreg_additional_'+ dependent_on_field_id).val() ;


					} else if ( 'multiselect' == dependable_field_type && $('.afreg_additional_'+dependent_on_field_id).children('option:selected').val() != undefined && $('.afreg_additional_'+dependent_on_field_id).children('option:selected').val() ) {

						$('.afreg_additional_'+dependent_on_field_id).children('option:selected').each(function(){

							if ( $(this).val() && undefined != $(this).val() ) {
								selected_dependent_filed_value     		= selected_dependent_filed_value + ' ' + $(this).val();
							}

						});


					} else if ( 'multi_checkbox' == dependable_field_type && $('.afreg_additional_'+dependent_on_field_id+':checked').val() != undefined && $('.afreg_additional_'+dependent_on_field_id+':checked').val() ) {

						$('.afreg_additional_'+dependent_on_field_id+':checked').each(function(){

							if ( $(this).val() && undefined != $(this).val() ) {
								selected_dependent_filed_value     		= selected_dependent_filed_value + ' ' + $(this).val();
							}

						});



					}  else if ( 'checkbox' == dependable_field_type && $('.afreg_additional_'+dependent_on_field_id+':checked').val() && $('.afreg_additional_'+dependent_on_field_id+':checked').val() != undefined ) {
						
						selected_dependent_filed_value 					= $('.afreg_additional_'+dependent_on_field_id+':checked').val() ;

					}

					if ( dependable_field_option && dependable_field_option != undefined ) {
						dependable_field_option_arr 			= dependable_field_option.split(',');
					} else {
						dependable_field_option_arr 			= [];
					}


					if ( selected_dependent_filed_value && selected_dependent_filed_value != undefined ) {
						selected_dependent_filed_value 				= selected_dependent_filed_value.split(' ');
					} else {
						selected_dependent_filed_value 				= [];
					}

					dependable_field_option_arr 					= array_filter( dependable_field_option_arr ); 
					selected_dependent_filed_value 					= array_filter( selected_dependent_filed_value ); 


					let array_total_match_values = array_intersect( dependable_field_option_arr, selected_dependent_filed_value );

					let total_match_values = 0;

					$.each(array_total_match_values,function(index,val){
						if ( val != '' && val != undefined  ) {

							total_match_values++;

						}

					});

					if ( total_match_values < 1 && ( dependable_field_option != '' && dependable_field_option != undefined )) {

						hide_or_show_this_element 	= false;
						$('#afreg_additionalshowhide_' + current_post_id).hide('fast');
					}

					


					if ( $('#afreg_additionalshowhide_' + dependent_on_field_id).is(':hidden') ) {

						$('#afreg_additionalshowhide_' + current_post_id).hide('fast');
						hide_or_show_this_element 	= false;
					}

					let dependent_field_id_nd_current_post_id 						= []; 
					dependent_field_id_nd_current_post_id['current_post_id'] 		=  current_post_id;
					dependent_field_id_nd_current_post_id['dependent_on_field_id'] 	=  parseInt( dependent_on_field_id );
					all_dependable_ids_array.push(dependent_field_id_nd_current_post_id);

				}

				if ( hide_or_show_this_element ) {
					$(this).fadeIn('slow')
				}

			}


		});


		setInterval (function() {

			hide_parent_hidden_field(all_dependable_ids_array);
		} , 1000);

}

function hide_parent_hidden_field(all_dependable_ids_array){

	$.each(all_dependable_ids_array,function(index,dependent_field_id_nd_current_post_id_arr){

		if ( dependent_field_id_nd_current_post_id_arr['current_post_id'] != undefined && dependent_field_id_nd_current_post_id_arr['dependent_on_field_id'] != undefined ) {

			let current_post_id 		=  dependent_field_id_nd_current_post_id_arr['current_post_id'];
			let dependent_on_field_id 	=  dependent_field_id_nd_current_post_id_arr['dependent_on_field_id'];

			if ( $('#afreg_additionalshowhide_' + dependent_on_field_id).is(':hidden') ) {

				// console.log('Parent is Hidden afreg_additionalshowhide_'+ dependent_on_field_id +' afreg_additional_'+ current_post_id);

				$('#afreg_additionalshowhide_' + current_post_id).hide('fast');

			}

			if ( ! $('#afreg_additionalshowhide_'+dependent_on_field_id).length ) {


				$('#afreg_additionalshowhide_' + current_post_id).remove();

			}
		}
	});
}


function array_intersect( arr1, arr2) {


	let final_array = [];

	$.each(arr1,function(index,main_array_value){
		main_array_value = main_array_value.trim();


		$.each(arr2,function(index,second_array_value){
			second_array_value = second_array_value.trim();

			if (main_array_value == second_array_value) {

				final_array.push(main_array_value);
			}

		});

	});

	return final_array;

}

function array_filter(array) {
	var filteredArray = $.grep(array, function (element) {

		return element !== '';

	});

	return filteredArray;
}

});
