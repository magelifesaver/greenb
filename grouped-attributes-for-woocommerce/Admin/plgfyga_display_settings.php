<?php
$display_settings = get_option('plgfyga_save_display_settings_for_tables');

?>
<div id="absbsavemsg" style="display:none;">
</div>
<table class="absb_rule_tables" style="width: 98% !important; margin-left: 1% !important;" id="tb1234">

	<h3>General Settings</i></h3>


		<tr>
		<td style="width: 40%;">
			<strong>Select Table Style</strong>
		</td>
		<td style="width: 60%;">
			<select id="plgfyga_table_style" class="input_type" style="min-width: 53%;">
				<option value="tophead"
				<?php
				if ('tophead' == $display_settings['plgfyga_table_style']) {
					echo 'selected';
				}
				?>
				>Top Header</option>
				<option value="left"
				<?php
				if ('left' == $display_settings['plgfyga_table_style']) {
					echo 'selected';
				}
				?>
				>Left Header</option>
				<option value="accordion"
				<?php
				if ('accordion' == $display_settings['plgfyga_table_style']) {
					echo 'selected';
				}
				?>
				>Accordion View</option>
				<option value="tabs"
				<?php
				if ('tabs' == $display_settings['plgfyga_table_style']) {
					echo 'selected';
				}
				?>
				>Tabs View</option>
				<option value="grid"
				<?php
				if ('grid' == $display_settings['plgfyga_table_style']) {
					echo 'selected';
				}
				?>
				>Grid View</option>
			</select>
		</td>
	</tr>




	<tr>
		<td style="width: 40%;">
			<strong>Select Table Location</strong>
		</td>
		<td style="width: 60%;">
			<select id="plgfyga_table_location" class="input_type" style="min-width: 53%;">
				<option value="beforecart"
				<?php
				if ('beforecart' == $display_settings['plgfyga_table_location']) {
					echo 'selected';
				}
				?>
				>Before Add to Cart Form</option>
				<option value="default"
				<?php
				if ('default' == $display_settings['plgfyga_table_location']) {
					echo 'selected';
				}
				?>
				>Default Additional Information Space</option>
				
			</select>
		</td>
	</tr>


	
	<tr>
		<td style="width: 40%;"> 
			<strong>Enable View Attributes Button on Shop Page</strong>
		</td>
		<td style="width: 60%;">

			<label class="switch"  style="margin-top: 10px;">
				<input type="checkbox" id="plgfyga_enab_on_shop" 
				<?php
				if (isset($display_settings['plgfyga_enab_on_shop']) && 'true' == $display_settings['plgfyga_enab_on_shop']) {
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
			<strong>View Attributes Button Text </strong>
		</td>
		<td>
			<input type="text" name="view_attr_txt" id="plgfyga_view_attri" class="input_type" value="<?php echo esc_attr($display_settings['plgfyga_view_attri']); ?>">
		</td>
	</tr>


	<tr>
		<td style="width: 40%;">
			<strong>View Attributes Button Background Color</strong>
		</td>
		<td style="width: 60%;">
			<input type="color"id="plgfyga_btn_bg_color" class="input_type" value="<?php echo esc_attr($display_settings['plgfyga_btn_bg_color']); ?>">
		</td>
	</tr>



	<tr>
		<td style="width: 40%;">
			<strong>View Attributes Button Text Color</strong>
		</td>
		<td style="width: 60%;">
			<input type="color"id="plgfyga_btn_txt_color" class="input_type" value="<?php echo esc_attr($display_settings['plgfyga_btn_txt_color']); ?>">
		</td>
	</tr>









</table>

<div id="topleft">
	<h3>Color Settings</h3>

	<table class="absb_rule_tables" style="width: 98% !important; margin-left: 1% !important;" >

		<tr>
			<td style="width: 40%;">
				<strong>Table Header Background Color</strong>
			</td>
			<td style="width: 60%;">
				<input type="color"id="plgfyga_top_head_bg_color" class="input_type" value="<?php echo esc_attr($display_settings['plgfyga_top_head_bg_color']); ?>">
			</td>
		</tr>

		<tr>
			<td style="width: 40%;">
				<strong>Table Header Text Color</strong>
			</td>
			<td style="width: 60%;">
				<input type="color"id="plgfyga_top_head_txt_color" class="input_type" value="<?php echo esc_attr($display_settings['plgfyga_top_head_txt_color']); ?>">
			</td>
		</tr>


		<tr>
			<td style="width: 40%;">
				<strong>Table Body Background Color (Even Rows)</strong>
			</td>
			<td style="width: 60%;">
				<input type="color"id="plgfyga_top_body_bg_color" class="input_type" value="<?php echo esc_attr($display_settings['plgfyga_top_body_bg_color']); ?>">
			</td>
		</tr>

		<tr>
			<td style="width: 40%;">
				<strong>Table Body Background Color (Odd Rows)</strong>
			</td>
			<td style="width: 60%;">
				<input type="color"id="plgfyga_top_body_bg_color_odd" class="input_type" value="<?php echo esc_attr($display_settings['plgfyga_top_body_bg_color_odd']); ?>">
			</td>
		</tr>


		<tr>
			<td style="width: 40%;">
				<strong>Table Body Text Color</strong>
			</td>
			<td style="width: 60%;">
				<input type="color"id="plgfyga_top_body_text_color" class="input_type" value="<?php echo esc_attr($display_settings['plgfyga_top_body_text_color']); ?>">
			</td>
		</tr>
	</table>
</div>


<div id="accordion_tableeeee">
	<h3>Color Settings</h3>

	<table class="absb_rule_tables" style="width: 98% !important; margin-left: 1% !important;" id="">


		<tr>
			<td style="width: 40%;">
				<strong>Accordion Header Background Color</strong>
			</td>
			<td style="width: 60%;">
				<input type="color"id="plgfyga_accor_head_bg_color" class="input_type" value="<?php echo esc_attr($display_settings['plgfyga_accor_head_bg_color']); ?>">
			</td>
		</tr>

		<tr>
			<td style="width: 40%;">
				<strong>Accordion Header Text Color</strong>
			</td>
			<td style="width: 60%;">
				<input type="color"id="plgfyga_accor_head_txt_color" class="input_type" value="<?php echo esc_attr($display_settings['plgfyga_accor_head_txt_color']); ?>">
			</td>
		</tr>


		<tr>
			<td style="width: 40%;">
				<strong>Accordion Body Background Color (Even Rows)</strong>
			</td>
			<td style="width: 60%;">
				<input type="color"id="plgfyga_accor_body_bg_color" class="input_type" value="<?php echo esc_attr($display_settings['plgfyga_accor_body_bg_color']); ?>">
			</td>
		</tr>


		<tr>
			<td style="width: 40%;">
				<strong>Accordion Body Background Color (Odd Rows)</strong>
			</td>
			<td style="width: 60%;">
				<input type="color"id="plgfyga_accor_body_bg_color_odd" class="input_type" value="<?php echo esc_attr($display_settings['plgfyga_accor_body_bg_color_odd']); ?>">
			</td>
		</tr>


		<tr>
			<td style="width: 40%;">
				<strong>Accordion Body Text Color</strong>
			</td>
			<td style="width: 60%;">
				<input type="color"id="plgfyga_accor_body_txt_color" class="input_type" value="<?php echo esc_attr($display_settings['plgfyga_accor_body_txt_color']); ?>">
			</td>
		</tr>

	</table>
</div>

<div id="tabtableeeee">
	<h3>Color Settings</h3>

	<table class="absb_rule_tables" style="width: 98% !important; margin-left: 1% !important;" id="">




		<tr>
			<td style="width: 40%;">
				<strong>Tab Main Header Background Color</strong>
			</td>
			<td style="width: 60%;">
				<input type="color"id="plgfyga_tabs_head_main_bg_color" class="input_type" value="<?php echo esc_attr($display_settings['plgfyga_tabs_head_main_bg_color']); ?>">
			</td>
		</tr>

		<tr>
			<td style="width: 40%;">
				<strong>Tabs Background Color</strong>
			</td>
			<td style="width: 60%;">
				<input type="color"id="plgfyga_tabs_bg_color" class="input_type" value="<?php echo esc_attr($display_settings['plgfyga_tabs_bg_color']); ?>">
			</td>
		</tr>

		<tr>
			<td style="width: 40%;">
				<strong>Tabs Text Color</strong>
			</td>
			<td style="width: 60%;">
				<input type="color"id="plgfyga_tabs_txt_color" class="input_type" value="<?php echo esc_attr($display_settings['plgfyga_tabs_txt_color']); ?>">
			</td>
		</tr>



		<tr>
			<td style="width: 40%;">
				<strong>Active Tabs Background Color</strong>
			</td>
			<td style="width: 60%;">
				<input type="color"id="plgfyga_tabs_bg_active" class="input_type" value="<?php echo esc_attr($display_settings['plgfyga_tabs_bg_active']); ?>">
			</td>
		</tr>


		<tr>
			<td style="width: 40%;">
				<strong>Active Tabs Text Color</strong>
			</td>
			<td style="width: 60%;">
				<input type="color"id="plgfyga_tabs_txt_active" class="input_type" value="<?php echo esc_attr($display_settings['plgfyga_tabs_txt_active']); ?>">
			</td>
		</tr>




		<tr>
			<td style="width: 40%;">
				<strong>Table Body Background Color (Even Rows)</strong>
			</td>
			<td style="width: 60%;">
				<input type="color"id="plgfyga_tab_table_body_color" class="input_type" value="<?php echo esc_attr($display_settings['plgfyga_tab_table_body_color']); ?>">
			</td>
		</tr>


		<tr>
			<td style="width: 40%;">
				<strong>Table Body Background Color (Odd Rxows)</strong>
			</td>
			<td style="width: 60%;">
				<input type="color"id="plgfyga_tab_table_body_color_odd" class="input_type" value="<?php echo esc_attr($display_settings['plgfyga_tab_table_body_color_odd']); ?>">
			</td>
		</tr>


		<tr>
			<td style="width: 40%;">
				<strong>Table Body Text Color</strong>
			</td>
			<td style="width: 60%;">
				<input type="color"id="plgfyga_tabs_table_body_txt_color" class="input_type" value="<?php echo esc_attr($display_settings['plgfyga_tabs_table_body_txt_color']); ?>">
			</td>
		</tr>





	</table>

</div>
<div style="text-align: left; margin-left: 1%; margin-bottom: 2%;">
	<button type="button" id="plgfyga_save_display" style="background-color: #007cba; cursor: pointer; color: white; padding: 9px 30px; border:none; border-radius: 5px;"><i class="icon-save"></i> Save Settings</button>
</div>


<script type="text/javascript">

	const checkbox = document.getElementById('plgfyga_enab_on_shop');
	const inputs = document.querySelectorAll('#plgfyga_view_attri, #plgfyga_btn_bg_color, #plgfyga_btn_txt_color');

	function toggleInputs() {
		inputs.forEach(input => {
			input.disabled = !checkbox.checked;
		});
	}


	toggleInputs();

	checkbox.addEventListener('change', toggleInputs);





	jQuery('body').on('change', '#plgfyga_table_style', function () {

		var selected = jQuery(this).val();
		if ( 'tophead' == selected || 'left' == selected || 'grid' == selected ) {
			jQuery('#topleft').show();
			jQuery('#accordion_tableeeee').hide();
			jQuery('#tabtableeeee').hide();	
		} else if ( 'accordion' == selected ) {
			jQuery('#accordion_tableeeee').show();
			jQuery('#topleft').hide();
			jQuery('#tabtableeeee').hide();
		} else if ( 'tabs' == selected ) {
			jQuery('#tabtableeeee').show();
			jQuery('#topleft').hide();
			jQuery('#accordion_tableeeee').hide();	
		}

	})


	var selected = jQuery('#plgfyga_table_style').val();
	if ( 'tophead' == selected || 'left' == selected || 'grid' == selected) {
		jQuery('#topleft').show();
		jQuery('#accordion_tableeeee').hide();
		jQuery('#tabtableeeee').hide();	
	} else if ( 'accordion' == selected ) {
		jQuery('#accordion_tableeeee').show();
		jQuery('#topleft').hide();
		jQuery('#tabtableeeee').hide();
	} else if ( 'tabs' == selected ) {
		jQuery('#tabtableeeee').show();
		jQuery('#topleft').hide();
		jQuery('#accordion_tableeeee').hide();	
	}

	jQuery('body').on('click', '#plgfyga_save_display', function () {


		var plgfyga_table_location = jQuery('#plgfyga_table_location').val();
		var plgfyga_table_style = jQuery('#plgfyga_table_style').val();
		var plgfyga_top_head_bg_color = jQuery('#plgfyga_top_head_bg_color').val();
		var plgfyga_top_head_txt_color = jQuery('#plgfyga_top_head_txt_color').val();
		var plgfyga_top_body_bg_color = jQuery('#plgfyga_top_body_bg_color').val();
		var plgfyga_top_body_text_color = jQuery('#plgfyga_top_body_text_color').val();
		var plgfyga_accor_head_bg_color = jQuery('#plgfyga_accor_head_bg_color').val();
		var plgfyga_accor_head_txt_color = jQuery('#plgfyga_accor_head_txt_color').val();
		var plgfyga_accor_body_bg_color = jQuery('#plgfyga_accor_body_bg_color').val();
		var plgfyga_accor_body_txt_color = jQuery('#plgfyga_accor_body_txt_color').val();
		var plgfyga_tabs_bg_color = jQuery('#plgfyga_tabs_bg_color').val();
		var plgfyga_tabs_txt_color = jQuery('#plgfyga_tabs_txt_color').val();
		var plgfyga_tab_table_body_color = jQuery('#plgfyga_tab_table_body_color').val();
		var plgfyga_tabs_table_body_txt_color = jQuery('#plgfyga_tabs_table_body_txt_color').val();
		var tabs_styless = jQuery('#tabs_styless').val();
		var plgfyga_top_body_bg_color_odd = jQuery('#plgfyga_top_body_bg_color_odd').val();
		var plgfyga_accor_body_bg_color_odd = jQuery('#plgfyga_accor_body_bg_color_odd').val();
		var plgfyga_tab_table_body_color_odd = jQuery('#plgfyga_tab_table_body_color_odd').val();

		var plgfyga_tabs_head_main_bg_color = jQuery('#plgfyga_tabs_head_main_bg_color').val();
		var plgfyga_tabs_bg_active = jQuery('#plgfyga_tabs_bg_active').val();
		var plgfyga_tabs_txt_active = jQuery('#plgfyga_tabs_txt_active').val();

		var plgfyga_view_attri = jQuery('#plgfyga_view_attri').val();
		var plgfyga_btn_bg_color = jQuery('#plgfyga_btn_bg_color').val();
		var plgfyga_btn_txt_color = jQuery('#plgfyga_btn_txt_color').val();
		var plgfyga_enab_on_shop = jQuery('#plgfyga_enab_on_shop').prop('checked');





		jQuery.ajax({
			url : '<?php echo filter_var(admin_url() . 'admin-ajax.php'); ?>',

			type : 'post',
			data : {
				action : 'plgfyga_save_display_settings_for_tables',

				plgfyga_table_location:plgfyga_table_location,
				plgfyga_table_style:plgfyga_table_style,
				plgfyga_top_head_bg_color:plgfyga_top_head_bg_color,
				plgfyga_top_head_txt_color:plgfyga_top_head_txt_color,
				plgfyga_top_body_bg_color:plgfyga_top_body_bg_color,
				plgfyga_top_body_text_color:plgfyga_top_body_text_color,
				plgfyga_accor_head_bg_color:plgfyga_accor_head_bg_color,
				plgfyga_accor_head_txt_color:plgfyga_accor_head_txt_color,
				plgfyga_accor_body_bg_color:plgfyga_accor_body_bg_color,
				plgfyga_accor_body_txt_color:plgfyga_accor_body_txt_color,
				plgfyga_tabs_bg_color:plgfyga_tabs_bg_color,
				plgfyga_tabs_txt_color:plgfyga_tabs_txt_color,
				plgfyga_tab_table_body_color:plgfyga_tab_table_body_color,
				plgfyga_tabs_table_body_txt_color:plgfyga_tabs_table_body_txt_color,
				tabs_styless:tabs_styless,

				plgfyga_top_body_bg_color_odd:plgfyga_top_body_bg_color_odd,
				plgfyga_accor_body_bg_color_odd:plgfyga_accor_body_bg_color_odd,
				plgfyga_tab_table_body_color_odd:plgfyga_tab_table_body_color_odd,

				plgfyga_tabs_head_main_bg_color:plgfyga_tabs_head_main_bg_color,
				plgfyga_tabs_bg_active:plgfyga_tabs_bg_active,
				plgfyga_tabs_txt_active:plgfyga_tabs_txt_active,	
				plgfyga_view_attri:plgfyga_view_attri,
				plgfyga_btn_txt_color:plgfyga_btn_txt_color,
				plgfyga_btn_bg_color:plgfyga_btn_bg_color,
				plgfyga_enab_on_shop:plgfyga_enab_on_shop



			},
			success : function( response ) {
				window.onbeforeunload = null;
				jQuery('#absbsavemsg').html('<div class="notice notice-success is-dismissible elt_msg" ><p id="absb_messageonsave">Rule has been saved</p><button type="button" class="notice-dismiss hidedivv"><span class="screen-reader-text">Dismiss this notice.</span></button></div>')
				jQuery('#absbsavemsg').show();
				jQuery('#absb_messageonsave').html('Display Settings have been saved');
				jQuery("html, body").animate({ scrollTop: 0 }, "slow");
				jQuery("#absbsavemsg").delay(7000).fadeOut(3000);
				jQuery('body').on('click', '.hidedivv', function () {
					jQuery('#absbsavemsg').hide();
				})
			}
		});
	})

</script>

<style type="text/css">
	.input_type{
		width: 53%;
		margin-top: 1%;
		padding-right: 4px !important;
		border-radius: 0px !important;
	}
	.absb_rule_tables {
		width: 100% !important;
		border-left: solid 1px lightgrey;
		border-bottom: solid 1px lightgrey;
		border-top: solid 1px lightgrey;
		border-right: solid 1px lightgrey;	
		border-radius: 4px;
		padding: 35px !important;
		margin: 5px;	
	}
	.woocommerce-save-button {
		display: none !important;
	}



	.switch {
		position: relative;
		display: inline-block;
		width: 50px;
		height: 26px;
	}

	.switch input { 
		opacity: 0;
		width: 0;
		height: 0;
	}

	.slider {
		border-radius: 3px;
		position: absolute;
		cursor: pointer;
		top: 0;
		left: 0;
		right: 0;
		bottom: 0;
		background-color: #dcdcde;
		-webkit-transition: .4s;
		transition: .4s;
	}

	.slider:before {
		border-radius: 3px;
		position: absolute;
		content: "";
		height: 18px;
		width: 18px;
		left: 4px;
		bottom: 4px;
		background-color: white;
		-webkit-transition: .4s;
		transition: .4s;
	}

	input:checked + .slider {
		background-color: #ae7b3b;
		background-image: linear-gradient(#ae7b3b, #d69323);
	}

	input:focus + .slider {
		box-shadow: 0 0 1px #ae7b3b;
		
	}

	input:checked + .slider:before {
		-webkit-transform: translateX(26px);
		-ms-transform: translateX(26px);
		transform: translateX(26px);

	}
</style>
