 jQuery(document).ready(function() {


 	jQuery('#chatbot_display_page').select2();
 	jQuery('#chatbot_display_woocommerce_pages').select2();


 	jQuery('body').on('click', '#select_all_pages', function () {
 		jQuery('#chatbot_display_page option').prop('selected', true);
 		jQuery('#chatbot_display_page').trigger('change');
 	});


 	jQuery('body').on('click', '#select_all_woo_pages', function () {
 		jQuery('#chatbot_display_woocommerce_pages option').prop('selected', true);
 		jQuery('#chatbot_display_woocommerce_pages').trigger('change');
 	});

 	jQuery('body').on('click', '.hidedivv', function () {
 		jQuery('#absbsavemsg').hide();
 	})


 	jQuery('body').on('click', '#remove_all_pages', function () {
 		jQuery('#chatbot_display_page option').prop('selected', false);
 		jQuery('#chatbot_display_page').trigger('change');
 	});

 	jQuery('body').on('click', '#remove_all_woo_pages', function () {
 		jQuery('#chatbot_display_woocommerce_pages option').prop('selected', false);
 		jQuery('#chatbot_display_woocommerce_pages').trigger('change');
 	});


 	jQuery('body').on('click', '.main_buttons', function () {
 		
 		const target = jQuery(this).data('target');

 		jQuery('#open_ai_div, #description_reply, #chatbott').hide();
 		jQuery(target).show();
 		jQuery('.normal_a').removeClass('current');
 		jQuery(this).find('.normal_a').addClass('current');
 	});


 	
 	jQuery('body').on('click', '#generate_description_btn', function () {
 		jQuery('#generate_description_modal').fadeIn();
 	});


 	jQuery('body').on('click', '#enhance_description_btn', function () {
 		jQuery('#enhance_description_modal').fadeIn();
 	});


 	
 	jQuery('body').on('click', '#generate_short', function () {
 		jQuery('#short_des_modal').fadeIn();
 	});


 	jQuery('body').on('click', '.close-modal', function () {
 		jQuery('.custom-modal').fadeOut();
 	});


 	jQuery('body').on('click', '#generate_description', function () {

 		var prod_name = jQuery('#product_name').val();
 		var key_features = jQuery('#key_features').val();
 		var max_limit = jQuery('#max_limit').val();
 		var limit_type = jQuery('#limit_type').val();
 		var format = jQuery("input[name='desc_format']:checked").val();

 		if ('' == prod_name) {
 			alert('Please enter product name.');
 			return;
 		}

 		if ('' == key_features) {
 			alert('Please enter key features.');
 			return;
 		}

 		if ('' == max_limit ) {
 			alert('Max limit field can not be empty.');
 			return;
 		}

 		var thissss = jQuery(this);
 		thissss.parent().block({
 			message: null,
 			overlayCSS: {
 				background: '#fff',
 				opacity: 0.6
 			}
 		});

 		jQuery.ajax({
 			url: plgfy_custom_dataa.admin_url,
 			type: 'post',
 			data: {
 				action: 'plugify_generate_ai_description',
 				prod_name: prod_name,
 				key_features: key_features,
 				max_limit: max_limit,
 				limit_type: limit_type,
 				format: format,
 				nonce: plgfy_custom_dataa.nonce
 			},
 			success: function (response) {
 				thissss.parent().unblock();

 				// console.log(response);

 				if (response.success) {
 					var generated_description = response.data.description;

 					jQuery('body').find('#current_description').val(generated_description);

 					if (tinymce.get('content')) {

 						var formatted_description = generated_description.replace(/\n/g, '<br>');
 						tinymce.get('content').setContent(formatted_description);
 					} else {
 						jQuery('#content').val(generated_description);
 					}

 					jQuery('.custom-modal').fadeOut();
 				} else {
 					alert('Error: ' + response.data.message);
 				}
 			}

 		});

 	});


 	tinymce.on('change', function (e) {
 		var contentValue = tinymce.get('content').getContent(); 
 		jQuery('#current_description').val(contentValue); 
 	});


 	jQuery('body').on('input', '#content', function () {
 		var contentVal = jQuery(this).val();
 		jQuery('#current_description').val(contentVal);
 	});


 	jQuery("#enhance_description_btn").on("click", function () {
 		var editorContent = tinymce.get('content') ? tinymce.get('content').getContent() : jQuery("#content").val();


 		var plainText = jQuery("<div>").html(editorContent).text(); 

 		jQuery("#current_description").val(plainText);
 		jQuery("#enhance_description_modal").fadeIn();
 	});


 	jQuery('body').on('click', '#enhance_descriptionn', function () {
 		var current_description = jQuery('#current_description').val();

 		if ('' == current_description) {
 			alert('Please enter a description to enhance.');
 			return;
 		}

 		var thissss = jQuery(this);
 		thissss.parent().block({
 			message: null,
 			overlayCSS: {
 				background: '#fff',
 				opacity: 0.6
 			}
 		});

 		jQuery.ajax({
 			url: plgfy_custom_dataa.admin_url,
 			type: 'post',
 			data: {
 				action: 'plugify_enhance_ai_description',
 				current_description: current_description,
 				nonce: plgfy_custom_dataa.nonce
 			},
 			success: function (response) {

 				thissss.parent().unblock();

 				if (response.success) {

 					jQuery('#donee').show();

 					var enhanced_description = response.data.description;

 					jQuery('#enhanced_description').val(enhanced_description);

 					if (tinymce.get('enhanced_description')) {
 						tinymce.get('enhanced_description').setContent(enhanced_description);
 					} else {
 						jQuery('#enhanced_description').val(enhanced_description);
 					}
 				} else {
 					alert('Error: ' + response.data.message);
 				}
 			}
 		});
 	});


 	jQuery('body').on('click', '#donee', function () {
 		var enhancedText = jQuery('#enhanced_description').val();

 		if (tinymce.get('content')) {
 			var formattedText = enhancedText.replace(/\n/g, '<br>'); 
 			tinymce.get('content').setContent(formattedText);
 		} else {
 			jQuery('#content').val(enhancedText);
 		}

 		jQuery('#enhance_description_modal').fadeOut();
 	});






 	tinymce.on('change', function (e) {
 		var contentValue = tinymce.get('content').getContent(); 
 		jQuery('#current_description_1').val(contentValue); 
 	});


 	jQuery('body').on('input', '#content', function () {
 		var contentVal = jQuery(this).val();
 		jQuery('#current_description_1').val(contentVal);
 	});


 	jQuery("#generate_short").on("click", function () {
 		var editorContent = tinymce.get('content') ? tinymce.get('content').getContent() : jQuery("#content").val();


 		var plainText = jQuery("<div>").html(editorContent).text(); 

 		jQuery("#current_description_1").val(plainText);
 		jQuery("#short_des_modal").fadeIn();
 	});



 	jQuery('body').on('click', '#generate_short_descriptionnn', function () {
 		var current_description = jQuery('#current_description_1').val();
 		var short_limit_type = jQuery('#short_limit_type').val();
 		var short_max_limit = jQuery('#short_max_limit').val();

 		if ('' == current_description) {
 			alert('Please enter a description to enhance.');
 			return;
 		}

 		var thissss = jQuery(this);
 		thissss.parent().block({
 			message: null,
 			overlayCSS: {
 				background: '#fff',
 				opacity: 0.6
 			}
 		});

 		jQuery.ajax({
 			url: plgfy_custom_dataa.admin_url,
 			type: 'post',
 			data: {
 				action: 'plugify_generate_ai_short_description',
 				current_description: current_description,
 				short_limit_type: short_limit_type,
 				short_max_limit: short_max_limit,
 				nonce: plgfy_custom_dataa.nonce
 			},
 			success: function (response) {

 				thissss.parent().unblock();

 				if (response.success) {

 					jQuery('#doneeeee').show();

 					var new_short_description = response.data.description;

 					jQuery('#new_short_description').val(new_short_description);

 					if (tinymce.get('new_short_description')) {
 						tinymce.get('new_short_description').setContent(new_short_description);
 					} else {
 						jQuery('#new_short_description').val(new_short_description);
 					}
 				} else {
 					alert('Error: ' + response.data.message);
 				}
 			}
 		});
 	});




 	jQuery(document).ready(function() {
 		jQuery('body').on('click', '#doneeeee', function () {
 			// console.log('Button clicked');
 			var enhancedText = jQuery('#new_short_description').val();
 			// console.log('Enhanced Text:', enhancedText);

 			if (tinymce.get('excerpt')) {
 				// console.log('TinyMCE found');

 				var formattedText = enhancedText.replace(/\n/g, '<br>');
 				tinymce.get('excerpt').setContent(formattedText);
 			} else {
 				// console.log('TinyMCE not found, falling back to textarea');
 				jQuery('#excerpt').val(enhancedText);
 			}

 			jQuery('#short_des_modal').fadeOut();
 		});

 	});




 	jQuery(document).ready(function(jQuery) {

 		jQuery('body').on('click', '.plugify-generate-variation-desc', function() {
 			
 			var variation_id = jQuery(this).data('variation-id');
 			var loop = jQuery(this).data('loop');

 			var variation_name = jQuery('#variable_product_options').find('input[name="variable_description[' + loop + ']"]').val();

 			jQuery('#variation_id').val(variation_id);
 			jQuery('#variation_loop').val(loop);
 			jQuery('#variation_product_name').val(jQuery('#post #title').val());
 			jQuery('#variation_key_features').val('');

 			jQuery(this).parent().parent().find('#generate_variation_description_modal').fadeIn();
 		});

 		jQuery('body').on('click', '.plugify-enhance-variation-desc', function() {
 			var variation_id = jQuery(this).data('variation-id');
 			var loop = jQuery(this).data('loop');
 			var current_desc = jQuery('#variable_product_options').find('textarea[name="variable_description[' + loop + ']"]').val();

 			jQuery('#enhance_variation_id').val(variation_id);
 			jQuery('#enhance_variation_loop').val(loop);
 			jQuery('#variation_current_description').val(current_desc);
 			jQuery('#variation_enhanced_description').val('');

 			jQuery('#enhance_variation_description_modal').fadeIn();
 		});


 		jQuery('body').on('click', '#generate_variation_description', function() {
 			var variation_id = jQuery('#variation_id').val();
 			var loop = jQuery('#variation_loop').val();
 			var prod_name = jQuery('#variation_product_name').val();
 			var key_features = jQuery('#variation_key_features').val();
 			var variation_limit_type = jQuery('#variation_limit_type').val();
 			var variation_max_limit = jQuery('#variation_max_limit').val();


 			if ('' == prod_name) {
 				alert('Please enter product name');
 				return;
 			}

 			if ('' == key_features) {
 				alert('Please enter key features');
 				return;
 			}

 			if ('' == variation_max_limit) {
 				alert('Maximum field can not empty.');
 				return;
 			}

 			var thiss = jQuery(this);
 			thiss.prop('disabled', true).text('Generating...');


 			jQuery('.custom-modal-content').block({
 				message: null,
 				overlayCSS: {
 					background: '#fff',
 					opacity: 0.6
 				}
 			});

 			jQuery.ajax({
 				url: plgfy_custom_dataa.admin_url,
 				type: 'post',
 				data: {
 					action: 'plugify_generate_variation_ai_description',
 					variation_id: variation_id,
 					prod_name: prod_name,
 					key_features: key_features,
 					variation_limit_type: variation_limit_type,
 					variation_max_limit: variation_max_limit, 					
 					nonce: plgfy_custom_dataa.nonce
 				},
 				success: function(response) {
 					thiss.prop('disabled', false).text('Generate');

 					jQuery('.custom-modal-content').unblock();

 					if (response.success) {
 						var generated_description = response.data.description;


 						jQuery('#variable_product_options').find('textarea[name="variable_description[' + loop + ']"]').val(generated_description);


 						jQuery('#generate_variation_description_modal').fadeOut();
 					} else {
 						alert('Error: ' + response.data.message);
 					}
 				},
 				error: function() {
 					thiss.prop('disabled', false).text('Generate');
 					alert('An error occurred. Please try again.');
 				}
 			});
 		});


 		jQuery('body').on('click', '#enhance_variation_description', function() {
 			var variation_id = jQuery('#enhance_variation_id').val();
 			var loop = jQuery('#enhance_variation_loop').val();
 			var current_desc = jQuery('#variation_current_description').val();

 			if ('' == current_desc) {
 				alert('Please enter current description');
 				return;
 			}

 			var thiss = jQuery(this);
 			thiss.prop('disabled', true).text('Enhancing...');

 			jQuery('.custom-modal-content').block({
 				message: null,
 				overlayCSS: {
 					background: '#fff',
 					opacity: 0.6
 				}
 			});


 			jQuery.ajax({
 				url: plgfy_custom_dataa.admin_url,
 				type: 'post',
 				data: {
 					action: 'plugify_enhance_variation_ai_description',
 					variation_id: variation_id,
 					current_description: current_desc,
 					nonce: plgfy_custom_dataa.nonce
 				},
 				success: function(response) {
 					thiss.prop('disabled', false).text('Enhance');

 					jQuery('.custom-modal-content').unblock();

 					if (response.success) {
 						var enhanced_description = response.data.description;
 						jQuery('#variation_enhanced_description').val(enhanced_description);
 						jQuery('#variation_done').show();
 					} else {
 						alert('Error: ' + response.data.message);
 					}
 				},
 				error: function() {
 					thiss.prop('disabled', false).text('Enhance');
 					alert('An error occurred. Please try again.');
 				}
 			});
 		});




 		jQuery('body').on('click', '#variation_done', function() {
 			var loop = jQuery('#enhance_variation_loop').val();
 			var enhanced_desc = jQuery('#variation_enhanced_description').val();

 			jQuery('#variable_product_options').find('textarea[name="variable_description[' + loop + ']"]').val(enhanced_desc);
 			jQuery('#enhance_variation_description_modal').fadeOut();
 		});
 	});

});


 jQuery('body').on('click', '#save_openai_settings', function () {
 	jQuery(this).addClass('is-busy');

 	var openai_apii = jQuery('#openai_apii').val();
 	var openai_modell = jQuery('#openai_modell').val();

 	var old_gpt_model = jQuery('#old_gpt_model').val();

 	if (old_gpt_model != openai_modell) {
 		if (!confirm('You are about to switch the AI model. Please note that different models may have different pricing and capabilities. Do you wish to proceed with this change?')) {
 			return;
 		}
 	}

 	jQuery.ajax({
 		url: plgfy_custom_dataa.admin_url,
 		type : 'post',
 		data : {
 			action : 'plugify_save_ai_settings',
 			openai_apii:openai_apii,
 			openai_modell:openai_modell,
 			nonce: plgfy_custom_dataa.nonce
 		},
 		success : function( response ) {
 			window.onbeforeunload = null;


 			jQuery('#old_gpt_model').val(openai_modell);

 			jQuery('#save_openai_settings').removeClass('is-busy');

 			jQuery('#absbsavemsg').html('<div class="notice notice-success is-dismissible elt_msg" ><p id="absb_messageonsave">Settings have been saved.</p><button type="button" class="notice-dismiss hidedivv"><span class="screen-reader-text">Dismiss this notice.</span></button></div>')
 			jQuery('#absbsavemsg').show();
 			jQuery('#absb_messageonsave').html('Settings have been saved.');
 			jQuery("html, body").animate({ scrollTop: 0 }, "slow");
 			jQuery("#absbsavemsg").delay(7000).fadeOut(3000);
 		}

 	});

 })



 jQuery('body').on('click', '#save_tokens_settings', function () {
 	jQuery(this).addClass('is-busy');

 	var max_enhance_tokens = jQuery('#max_enhance_tokens').val();
 	var max_short_descriptions_tokens = jQuery('#max_short_descriptions_tokens').val();
 	var max_reply_tokens = jQuery('#max_reply_tokens').val();


 	jQuery.ajax({
 		url: plgfy_custom_dataa.admin_url,
 		type : 'post',
 		data : {
 			action : 'plugify_save_tokens_settings',
 			max_enhance_tokens:max_enhance_tokens,
 			max_short_descriptions_tokens:max_short_descriptions_tokens,
 			max_reply_tokens:max_reply_tokens,
 			nonce: plgfy_custom_dataa.nonce
 		},
 		success : function( response ) {
 			window.onbeforeunload = null;
 			jQuery('#save_tokens_settings').removeClass('is-busy');
 			jQuery('#absbsavemsg').html('<div class="notice notice-success is-dismissible elt_msg" ><p id="absb_messageonsave">Settings have been saved.</p><button type="button" class="notice-dismiss hidedivv"><span class="screen-reader-text">Dismiss this notice.</span></button></div>')
 			jQuery('#absbsavemsg').show();
 			jQuery('#absb_messageonsave').html('Settings have been saved.');
 			jQuery("html, body").animate({ scrollTop: 0 }, "slow");
 			jQuery("#absbsavemsg").delay(7000).fadeOut(3000);
 		}

 	});



 })


 jQuery('body').on('click', '#save_chatbot_settings', function () {

 	jQuery(this).addClass('is-busy');


 	var max_bot_reply = jQuery('#max_bot_reply').val();
 	var bot_how_many_unsolved = jQuery('#bot_how_many_unsolved').val();
 	var bot_email_request_message = jQuery('#bot_email_request_message').val();
 	var bot_header_text = jQuery('#bot_header_text').val();
 	var bot_header_bg = jQuery('#bot_header_bg').val();
 	var bot_header_txt_clr = jQuery('#bot_header_txt_clr').val();
 	var send_btn_txt = jQuery('#send_btn_txt').val();
 	var send_btn_bg = jQuery('#send_btn_bg').val();
 	var send_btn_txt_color = jQuery('#send_btn_txt_color').val();
 	var icon_bg = jQuery('#icon_bg').val();
 	var icon_color = jQuery('#icon_color').val();
 	var chatbot_display_page = jQuery('#chatbot_display_page').val();
 	var chatbot_display_woocommerce_pages = jQuery('#chatbot_display_woocommerce_pages').val();
 	var after_mail_msg = jQuery('#after_mail_msg').val();
 	var technical_issue = jQuery('#technical_issue').val();

 	var window_bg = jQuery('#window_bg').val();
 	var admin_bg = jQuery('#admin_bg').val();
 	var admin_txt_color = jQuery('#admin_txt_color').val();
 	var user_bg = jQuery('#user_bg').val();
 	var user_txt_color = jQuery('#user_txt_color').val();

 	var admin_time_color = jQuery('#admin_time_color').val();
 	var store_namee = jQuery('#store_namee').val();
 	var store_details = jQuery('#store_details').val();
 	var user_time_color = jQuery('#user_time_color').val();
 	var enable_chatbott = jQuery('#enable_chatbott').is(':checked') ? 'true' : 'false';


 	jQuery.ajax({
 		url: plgfy_custom_dataa.admin_url,
 		type : 'post',
 		data : {
 			action : 'plugify_save_chatbot_settings',
 			max_bot_reply:max_bot_reply,
 			bot_how_many_unsolved:bot_how_many_unsolved,
 			bot_email_request_message:bot_email_request_message,
 			bot_header_text:bot_header_text,
 			bot_header_bg:bot_header_bg,
 			bot_header_txt_clr:bot_header_txt_clr,
 			send_btn_txt:send_btn_txt,
 			send_btn_bg:send_btn_bg,
 			send_btn_txt_color:send_btn_txt_color,
 			icon_bg:icon_bg,
 			icon_color:icon_color,
 			chatbot_display_page:chatbot_display_page,
 			chatbot_display_woocommerce_pages:chatbot_display_woocommerce_pages,
 			nonce: plgfy_custom_dataa.nonce,
 			after_mail_msg:after_mail_msg,
 			technical_issue:technical_issue,
 			window_bg:window_bg,
 			admin_bg:admin_bg,
 			admin_txt_color:admin_txt_color,
 			user_bg:user_bg,
 			user_txt_color:user_txt_color,
 			admin_time_color:admin_time_color,
 			user_time_color:user_time_color,
 			store_namee:store_namee,
 			store_details:store_details,
 			enable_chatbott:enable_chatbott

 		},
 		success : function( response ) {
 			window.onbeforeunload = null;
 			jQuery('#save_chatbot_settings').removeClass('is-busy');
 			jQuery('#absbsavemsg').html('<div class="notice notice-success is-dismissible elt_msg" ><p id="absb_messageonsave">Settings have been saved.</p><button type="button" class="notice-dismiss hidedivv"><span class="screen-reader-text">Dismiss this notice.</span></button></div>')
 			jQuery('#absbsavemsg').show();
 			jQuery('#absb_messageonsave').html('Settings have been saved.');
 			jQuery("html, body").animate({ scrollTop: 0 }, "slow");
 			jQuery("#absbsavemsg").delay(7000).fadeOut(3000);
 		}

 	});


 })




 document.addEventListener('DOMContentLoaded', function () {
 	const shortcodes = document.querySelectorAll('.copy-shortcode');

 	shortcodes.forEach(function (el) {
 		el.style.color = '#007cba';
 		el.style.cursor = 'pointer';
 		el.title = 'Click to copy';

 		el.addEventListener('click', function (e) {
 			e.preventDefault();
 			e.stopPropagation();

 			const textToCopy = el.getAttribute('data-shortcode');
 			navigator.clipboard.writeText(textToCopy).then(() => {
 				el.innerText = 'Copied!';
 				setTimeout(() => {
 					el.innerText = textToCopy;
 				}, 1000);
 			});
 		});
 	});
 });

