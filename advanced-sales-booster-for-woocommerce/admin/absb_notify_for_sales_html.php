<div id="main_notifications_inner_div">

	<div id="notify_buttons_div" style="margin-top: 1%; margin-bottom: 2%; ">
		<button type="button" id="notify_general_settings" class="inner_buttons abcactive" style=" padding: 9px 30px;
		border-radius: unset !important;   font-weight: 500; border-bottom: none; margin-left: 5px; font-size: 13px;">Notifications Settings</button>
		<button type="button" id="notify_display_settings" class="inner_buttons" style=" padding: 9px 30px; margin: unset !important;
		border-radius: unset !important;   font-weight: 500; border-bottom: none; font-size:13px; ">Display Settings</button>
		<button type="button" id="notify_time_settings" class="inner_buttons" style=" padding: 9px 30px; margin: unset !important;
		border-radius: unset !important;   font-weight: 500; border-bottom: none; font-size:13px; ">Time Settings</button>
		<button type="button" id="notify_message_settings" class="inner_buttons" style=" padding: 9px 30px; margin: unset !important;
		border-radius: unset !important;   font-weight: 500; border-bottom: none; font-size:13px; ">Message Settings</button>   
		<button type="button" id="notify_shortcode_settings" class="inner_buttons" style=" padding: 9px 30px; margin: unset !important;
		border-radius: unset !important;   font-weight: 500; border-bottom: none; font-size:13px; ">Short-Code Settings</button>
		<hr>

	</div>

	<div id="gen_settings_div_notify" style="display: none;">
		<?php

		$gen_settings = get_option('nfs_general_settings_for_notify');


		?>


		<table class="absb_rule_tables" style="width: 98% !important; margin-left: 1% !important;" id="tb1234">

			<tr>
				<td style="width: 40%;"> 
					<strong style="color: #007cba;">Activate Sales Notifications</strong>
				</td>
				<td style="width: 60%;">

					<label class="switch">
						<input type="checkbox" id="notify_activate" 
						<?php
						if (isset($gen_settings['activatenotify']) && 'true' == $gen_settings['activatenotify']) {
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
					<strong>Enable for Mobile</strong>
				</td>
				<td style="width: 60%;">

					<label class="switch">
						<input type="checkbox" id="notify_enable_phone" 
						<?php
						if (isset($gen_settings['enablephone']) && 'true' == $gen_settings['enablephone']) {
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
					<strong>Enable on Shop Page</strong>
				</td>
				<td style="width: 60%;">

					<label class="switch">
						<input type="checkbox" id="notify_on_shop" 
						<?php
						if (isset($gen_settings['notifyonshop']) && 'true' == $gen_settings['notifyonshop']) {
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
					<strong>Enable on Product Page</strong>
				</td>
				<td style="width: 60%;">

					<label class="switch">
						<input type="checkbox" id="notify_on_product" 
						<?php
						if (isset($gen_settings['notifyonproduct']) && 'true' == $gen_settings['notifyonproduct']) {
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
					<strong>Enable on Cart Page</strong>
				</td>
				<td style="width: 60%;">

					<label class="switch">
						<input type="checkbox" id="notify_on_cart"
						<?php
						if (isset($gen_settings['notifyoncart']) && 'true' == $gen_settings['notifyoncart']) {
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
					<strong>Enable on Checkout Page</strong>
				</td>
				<td style="width: 60%;">

					<label class="switch">
						<input type="checkbox" id="notify_on_checkout"
						<?php
						if (isset($gen_settings['notify_on_checkout']) && 'true' == $gen_settings['notify_on_checkout']) {
							echo 'checked';
						}
						?>
						>


						<span class="slider"></span>
					</label><br>
				</td>

			</tr>
		</table>
		
		<table class="absb_rule_tables" style="width: 98% !important; margin-left: 1% !important;" id="tb321243">



			<tr>
				<td style="width: 40%;">
					<strong>Enable for Custom Pages</strong>
				</td>
				<td style="width: 60%;">

					<label class="switch">
						<input type="checkbox" id="notify_on_custom_page"
						<?php
						if (isset($gen_settings['notify_on_custom_url']) && 'true' == $gen_settings['notify_on_custom_url']) {
							echo 'checked';
						}


						?>
						>


						<span class="slider"></span>
					</label><br>
				</td>

			</tr>


			<tr class="url_tr_1">
				
				<td> 
					<button type="button" id="add_url" style="background-color: green; cursor: pointer; color:#fefefe; padding: 9px 40px; border: 1px solid green; float: left; margin-right: -10%; margin-bottom: 2%; border-radius: 5px;"><i class="fa-solid fa-plus"></i> Add URL</button>
				</td>


			</tr>

			<?php

			if (isset($gen_settings['custom_url'])) {
				foreach ($gen_settings['custom_url'] as $key => $value) {

					?>
					<tr class="url_tr_2">




						<td>
							<input type="text" class="custom_url" style="width: 85% !important; border-radius: 0px !important;" value="<?php echo esc_attr($value); ?>" >
						</td>
						<td>
							<?php
							if ( 0 != $key ) {
								?>
								<button type="button" class="del_url" style=" border:1px solid red; padding:7px 10px; background-color:white; cursor:pointer; color: red; border-radius: 5px;"><i  class="fa fa-trash"></i> </button>
								<?php
							}
							?>
						</td>
					</tr>

					<?php
				}
			}
			?>








		</table>
		<div style="text-align: right; margin-right: 1%; margin-bottom: 2%;">
			<button type="button" id="notify_save_general" style="background-color: #007cba; cursor: pointer; color: white; padding: 9px 30px; border:none; border-radius: 5px;"><i class="icon-save"></i> Save Notifications Settings</button>
		</div>



	</div>





	<div id="dis_settings_div_notify" style="display: none;">
		<?php
		$display_settings = get_option('nfs_display_settings_for_notify');

		?>

		<table class="absb_rule_tables" style="width: 98% !important; margin-left: 1% !important;">

			<tr>
				<td style="width: 40%;">
					<strong>Notification Box Background Color</strong>
				</td>
				<td style="width: 60%;">
					<input type="color" name="bgcolor" id="notify_bg_color" class="input_type" value="<?php echo esc_attr($display_settings['notify_bgcolor']); ?>">
				</td>
			</tr>

			<tr>
				<td style="width: 40%;">
					<strong>Notification Box Border Color</strong>
				</td>
				<td style="width: 60%;">
					<input type="color" name="bordercolor" id="notify_border_color" class="input_type" value="<?php echo esc_attr($display_settings['notify_border_color']); ?>">
				</td>
			</tr>

			<tr>
				<td style="width: 40%;">
					<strong>Notification Box Text Color</strong>
				</td>
				<td style="width: 60%;">
					<input type="color" name="txtcolor" id="notify_txt_color" class="input_type" value="<?php echo esc_attr($display_settings['notify_txt_color']); ?>">
				</td>
			</tr>

			<tr>
				<td>
					<strong>Notification Position</strong>
				</td>
				<td>
					<select id="notify_location" style="width: 53% !important; margin-top: 3px !important; max-width: 34rem !important;">
						<option value="leftbottom" 
						<?php
						if ('leftbottom' == $display_settings['notify_location'] && isset($display_settings['notify_location'])) {
							echo 'selected';
						}
						?>
						>Bottom Left</option>
						<option value="rightbottom" 
						<?php
						if ('rightbottom' == $display_settings['notify_location'] && isset($display_settings['notify_location'])) {
							echo 'selected';
						}
						?>
						>Bottom Right</option>
						<option value="topleft"
						<?php
						if ('topleft' == $display_settings['notify_location'] && isset($display_settings['notify_location'])) {
							echo 'selected';
						}
						?>
						>Top Left</option>
						<option value="topright" 
						<?php
						if ('topright' == $display_settings['notify_location'] && isset($display_settings['notify_location'])) {
							echo 'selected';
						}
						?>
						>Top Right</option>
					</select><br>
					<span style="color: grey;"><i>Important:The notification on mobile will be displayed according to any selection of top or bottom</i></span> 
				</td>
			</tr>
			<tr>
				<td>
					<strong>Notification Opening Animation</strong>
				</td>
				<td>
					<select id="notify_animate" style="width: 53% !important; margin-top: 3px !important; max-width: 34rem !important;">
						<option value="animate__bounce" 
						<?php
						if ('animate__bounce' == $display_settings['notify_animate'] && isset($display_settings['notify_animate'])) {
							echo 'selected';
						}
						?>
						> Bounce</option>
						<option value="animate__pulse"
						<?php
						if ('animate__pulse' == $display_settings['notify_animate'] && isset($display_settings['notify_animate'])) {
							echo 'selected';
						}
						?>
						>Pulse</option>

						<option value="animate__heartBeat"
						<?php
						if ('animate__heartBeat' == $display_settings['notify_animate'] && isset($display_settings['notify_animate'])) {
							echo 'selected';
						}
						?>
						>Heart Beat </option>

						<option value="animate__backInLeft"
						<?php
						if ('animate__backInLeft' == $display_settings['notify_animate'] && isset($display_settings['notify_animate'])) {
							echo 'selected';
						}
						?>
						>Back-In-Left</option>
						<option value="animate__backInUp"
						<?php
						if ('animate__backInUp' == $display_settings['notify_animate'] && isset($display_settings['notify_animate'])) {
							echo 'selected';
						}
						?>
						>Back-In-Up</option>

						<option value="animate__bounceInDown"
						<?php
						if ('animate__bounceInDown' == $display_settings['notify_animate'] && isset($display_settings['notify_animate'])) {
							echo 'selected';
						}
						?>
						>Bounce-In-Down</option>

						<option value="animate__fadeIn"
						<?php
						if ('animate__fadeIn' == $display_settings['notify_animate'] && isset($display_settings['notify_animate'])) {
							echo 'selected';
						}
						?>
						>FadeIn</option>


						<option value="animate__flipInX"
						<?php
						if ('animate__flipInX' == $display_settings['notify_animate'] && isset($display_settings['notify_animate'])) {
							echo 'selected';
						}
						?>
						>Flip-In-X</option>

						<option value="animate__rotateInDownLeft"
						<?php
						if ('animate__rotateInDownLeft' == $display_settings['notify_animate'] && isset($display_settings['notify_animate'])) {
							echo 'selected';
						}
						?>
						>Rotate-In-Down-Left</option>

						<option value="animate__rollIn"
						<?php
						if ('animate__rollIn' == $display_settings['notify_animate'] && isset($display_settings['notify_animate'])) {
							echo 'selected';
						}
						?>
						>Jack-In-The-Box</option>

						<option value="animate__slideInRight"
						<?php
						if ('animate__slideInRight' == $display_settings['notify_animate'] && isset($display_settings['notify_animate'])) {
							echo 'selected';
						}
						?>
						>Slide-In-Right</option>



					</select>
				</td>
			</tr>

			<tr>
				<td>
					<strong>Notification Box Border Radius px's (Rounded Corner)</strong>
				</td>
				<td>
					<input type="number" name="brder-radius" id="notify_radius" class="input_type" value="<?php echo esc_attr($display_settings['notify_radius']); ?>">
				</td>
			</tr>






		</table>

		<div style="text-align: right; margin-right: 1%; margin-bottom: 2%;">
			<button type="button" id="notify_save_display" style="background-color: #007cba; cursor: pointer; color: white; padding: 9px 30px; border:none; border-radius: 5px;"><i class="icon-save"></i> Save Display Settings</button>
		</div>

	</div>






	<div id="time_settings_div_notify" style="display: none;">
		<?php
		$time_settings = get_option('nfs_time_settings_for_notify');
		?>
		<table class="absb_rule_tables" style="width: 98% !important; margin-left: 1% !important;">
			<tr>
				<td style="width: 40%;">
					<strong>Maximum Notifications per Page </strong>
				</td>
				<td style="width: 60%;">
					<input type="number" name="numofnotify" id="number_of_notify" class="input_type" value="<?php echo esc_attr($time_settings['number_of_notify']); ?>">

				</td>
			</tr>



			<tr>
				<td>
					<strong>Notification Display Time <div class="tooltip"><i class="fa fa-question-circle tooltip" aria-hidden="true" style="cursor: help;"></i>
								<span class="tooltiptext"> The time <i>Notification</i> will displayed on screen</span>
							</div></strong>
				</td>
				<td>
					<input type="number" name="distimenotify" id="display_time_notify" class="input_type" value="<?php echo esc_attr($time_settings['display_time']); ?>"><br>
					<a style="color: grey;"><i>Second(s)</i></a>
				</td>
			</tr>

			<tr>
				<td>
					<strong>Notification Appearance Time <div class="tooltip"><i class="fa fa-question-circle tooltip" aria-hidden="true" style="cursor: help;"></i>
								<span class="tooltiptext"> Gap between one notification and next one</span>
							</div></strong>
				</td>
				<td>


					<table style="width: 60%;">
						<tbody>
							<tr>
								<th style="text-align: left;">Start Range</th>
								<th style="text-align: left;">End Range</th>
							</tr>
							<tr>
								<td><input type="number" id="start_range_notify" value="<?php echo esc_attr($time_settings['start_range_notify']); ?>"></td><br>
								<td><input type="number" id="end_range_notify" value="<?php echo esc_attr($time_settings['end_range_notify']); ?>"></td>
							</tr>
							<tr>
								<td><a style="color: grey;"><i>Second(s)</i></a></td>
								<td><a style="color: grey;"><i>Second(s)</i></a></td><br>

							</tr>
							<tr>

								<td colspan="2">
									<a style="color: grey;"><i>Notification will appear at randomly selected time from start range to end range </i></a>
								</td>
							</tr>



						</tbody>

					</table>


				</td>

			</tr>



		</table>

		<div style="text-align: right; margin-right: 1%; margin-bottom: 2%;">
			<button type="button" id="notify_save_time" style="background-color: #007cba; cursor: pointer; color: white; padding: 9px 30px; border:none; border-radius: 5px;"><i class="icon-save"></i> Save Time Settings</button>
		</div>

	</div>






	<div id="msg_settings_div_notify" style="display: none;">


		<?php

		$msg_settings = get_option('nfs_message_settings_for_notify');

	

		?>

		<div style="margin-right: 1%; text-align: right;">
			<button type="button" id="add_msg_notify" style="background-color: green; color: white; padding: 8px 12px; font-size: 14px; font-weight: 500; cursor: pointer; border:1px solid green; border-radius: 3px !important;"><i class="fa-solid fa-plus"></i> Add Messages</button>
		</div>

		<table class="absb_rule_tables" style="width: 98% !important; margin-left: 1% !important;" id="tabxyzxxx">

			<?php 
			if ( isset($msg_settings['notify_content']) && is_array($msg_settings['notify_content'])) {
				foreach ($msg_settings['notify_content'] as $key => $value) {
					?>
					<tr>
						<td style="width: 40%">
							<?php 
							if ( 0 == $key) {
								?>
								<strong>Notification Content</strong>
								<?php
							}
							?>
						</td>
						<td style="width: 50%;">
							<input type="text" name="msgnotify" id="msg_for_notify" class="notify_content input_type" style="width: 85%;" value='<?php echo esc_attr(stripslashes($value)); ?>'>
						</td>
						<td style="width: 10%;">
							<?php 
							if ( 0 != $key) {
								?>
								<button type="button" class="del_msg" id="delete_msg" style="border:1px solid red; padding:7px 24px; background-color:white; color:red; cursor:pointer; border-radius: 4px; " ><i class="fa fa-remove"></i> Remove</button>
								<?php
							}
							?>
						</td>
					</tr>
					<?php
				}
			} else {
				?>

				<tr>
					<td style="width: 40%">

						<strong>Notification Content</strong>

					</td>
					<td style="width: 50%;">
						<input type="text" name="msgnotify" id="msg_for_notify" class="notify_content input_type" style="width: 85%;" value='{first_name} in {city} purchased a <strong> {product} </strong> {product_with_link} <br> <i> {time_ago} </i>'>
					</td>
					<td style="width: 10%;">

						<button type="button" class="del_msg" id="delete_msg" style="border:1px solid red; padding:7px 24px; background-color:white; color:red; cursor:pointer; border-radius: 4px;  "> Remove</button>

					</td>
				</tr>
				<?php
			} 

			?>
			<tr>
				<td></td>
				<td style="color: grey;">
					<strong>Important:</strong>
					<span> <i>You can use follwing shortcode in your notificaton content</i></span>
					<br>
					<span style="color:red;"><b>{first_name}</b></span> for Customer's first name<br>
					<span style="color:red;"><b>{city}</b></span> for Customer's city / State<br>
					<span style="color:red;"><b>{product}</b></span> for Product title<br>
					<span style="color:red;"><b>{product_with_link}</b></span> for Product title with link<br>
					<span style="color:red;"><b>{time_ago}</b></span> for Time after purchase<br>

				</td>
			</tr>
			<tr>

				<td style="margin-top: 5%;"><hr></td>
				<td style="margin-top: 5%;"><hr></td>
				<td style="margin-top: 5%;"><hr></td>
			</tr>




			<tr style="border-spacing: 5em;">

				<td style="width: 40%;">

					<strong>Enable Custom Message On Product Page</strong>
				</td>
				<td style="width: 60%;">

					<label class="switch">
						<input type="checkbox" id="enable_custom_msg_for_product" 
						<?php
						if ('true' == $msg_settings['enable_custom_msg_for_product']) {
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
					<strong>Custom Message</strong>
				</td>
				<td>
					<input type="text" name="cst_msg" id="custom_msg_product" class="input_type" style="width: 85%;" 
					<?php
					if (isset($msg_settings['custom_msg_product'])) {
						?>
						value = "<?php echo esc_html($msg_settings['custom_msg_product']); ?>"
						<?php
					} else {
						?>
						value="{number} people seeing this product right now"
						<?php 
					}

					?>
					>
					<br>
					Use shortcode <span style="color:red;font-weight: bold;">{number}</span> to add number from below fields in the message.
				</td>
			</tr>

			<tr>
				<td>
					<strong>Numbers for Custom Message</strong>
				</td>
				<td>
					<table style="width: 100%;">
						<tbody>
							<tr>
								<th style="text-align: left;">Number Displayed in Custom Message</th>
								<th style="text-align: left;">Increment in Number</th>
							</tr>
							<tr>
								<td><input type="number" id="min_for_custom" style="width: 71%; " value="<?php echo esc_attr($msg_settings['min_for_custom']); ?>"></td><br>
								<td><input type="number" id="max_for_custom" style="width: 71%;" value="<?php echo esc_attr($msg_settings['max_for_custom']); ?>"></td>
							</tr>
							<tr>
								<td colspan="2"><a style="color: grey;"><i>Increment in number will be occured after 3 hours</i></a></td>

							</tr>



						</tbody>

					</table>
				</td>
			</tr>



		</table>


		<div style="text-align: right; margin-right: 1%; margin-bottom: 2%;">
			<button type="button" id="notify_save_msg" style="background-color: #007cba; cursor: pointer; color: white; padding: 9px 30px; border:none; border-radius: 5px;"><i class="icon-save"></i> Save Message Settings</button>
		</div>





	</div>


	<div id="shortcodediv" style="display: none;">

		<?php 




		$shortcodedata = get_option('nfs_saving_shortcode_settings');


		


		$notify_product_category_html='';

		$cat_selected = $shortcodedata['procat_ids_notify'];



		$absb_parentid = get_queried_object_id();
		$absb_args = array(
			'numberposts' => -1,
			'taxonomy' => 'product_cat',
		);
		$absb_terms = get_terms($absb_args);





		if ( $absb_terms ) {   
			foreach ( $absb_terms as $absb_term1 ) {



				$selected = '';

				if (is_array($cat_selected) && in_array($absb_term1->term_id , $cat_selected) && 'categories' == $shortcodedata['appliedon_notify']) {
					$selected = 'selected';
				}


				$notify_product_category_html = $notify_product_category_html . '<option class="absb_catopt" value="' . $absb_term1->term_id . '" ' . $selected . '>' . $absb_term1->name . '</option>';

			}  
		}









		?>



		<table class="absb_rule_tables" style="width: 98% !important; margin-left: 1% !important;">
			<tr>
				<td style="width: 40%;">
					<strong>Select One</strong>
				</td>

				<td style="width: 60%;">
					<select name="absb_selectone" id="notify_appliedon" class="absbselect" style="width: 100% !important; max-width: 100% !important; padding:1px; text-align: center;">
						<option value="products"
						<?php
						if ('products' == $shortcodedata['appliedon_notify']) {
							echo 'selected';
						}
						?>
						>Specific Products</option>
						<option value="categories"
						<?php
						if ('categories' == $shortcodedata['appliedon_notify']) {
							echo 'selected';
						}
						?>
						>Specific Categories</option>

					</select>
				</td>
			</tr>
			<tr>
				<td id="notify_label">
					<strong>Select Product/Category <div class="tooltip"><i class="fa fa-question-circle tooltip" aria-hidden="true" style="cursor: help;"></i>
								<span class="tooltiptext"> These selected products/categories will be displayed in notifications randomly</span>
							</div></strong>
						</td>
						<td id="notify_1" >
							<select multiple id="notify_select_product" class="absbselect" name="multi[]">
								<?php
								if ( 'products' == $shortcodedata['appliedon_notify'] && is_array( $shortcodedata['procat_ids_notify'] ) ) {

									foreach ( $shortcodedata['procat_ids_notify'] as $key => $value ) {

										$product = wc_get_product( $value );

										if ( $product ) {
											if ( $product->is_type( 'variation' ) ) {
												$parent = wc_get_product( $product->get_parent_id() );
												$variation_attributes = wc_get_formatted_variation( $product, true );
												$title = $parent->get_name() . ' - ' . $variation_attributes;
											} else {
												$title = $product->get_name();
											}

											echo '<option value="' . esc_attr( trim( $value ) ) . '" selected>' . esc_html( $title ) . '</option>';
										}
									}
								}
								?>
							</select>
				</td>
				<td id="notify_2" style="display: none;">
					<select multiple id="notify_select_category" name="multi2[]" class="absbselect">
						<?php echo filter_var($notify_product_category_html); ?>
					</select>
				</td>
			</tr>


			<tr>
				<td>
					<strong>Virtual First Names <div class="tooltip"><i class="fa fa-question-circle tooltip" aria-hidden="true" style="cursor: help;"></i>
								<span class="tooltiptext"> These virtual names will be displayed randomly in notifications as replacement of shortcode {first_name}</span>
							</div></strong>
				</td>
				<td>
					<textarea id="virtual_names" name="vurtual_names" rows="6" cols="60"><?php echo esc_html($shortcodedata['virtual_names']); ?></textarea><br>
					<span><i style="color: red">Important: Please must use comma ( , ) after each name except last one</i></span>
				</td>
			</tr>



			<tr>
				<td>
					<strong>Virtual Cities <div class="tooltip"><i class="fa fa-question-circle tooltip" aria-hidden="true" style="cursor: help;"></i>
								<span class="tooltiptext"> These virtual Cities will be displayed randomly in notifications as replacement of shortcode {city}</span>
							</div></strong>
				</td>
				<td>
					<textarea id="virtual_cities" name="vurtual_cities" rows="6" cols="60"><?php echo esc_html($shortcodedata['virtual_cities']); ?></textarea><br>
					<span><i style="color: red">Important: Please must use comma ( , ) after each city except last one</i></span>
				</td>
			</tr>




			<tr>
				<td>
					<strong>Virtual Time Ago <div class="tooltip"><i class="fa fa-question-circle tooltip" aria-hidden="true" style="cursor: help;"></i>
								<span class="tooltiptext"> These Virtual Time Ago will be displayed randomly in notifications as replacement of shortcode {time_ago}</span>
							</div></strong>
				</td>
				<td>
					<textarea id="virtual_timeago" name="vurtual_timeago" rows="6" cols="60"><?php echo esc_html($shortcodedata['virtual_timeago']); ?></textarea><br>
					<span><i style="color: red">Important: Please must use comma ( , ) after each virtual time except last one</i></span>
				</td>
			</tr>



		</table>
		<div style="text-align: right; margin-right: 1%; margin-bottom: 2%;">
			<button type="button" id="notify_save_shortcode" style="background-color: #007cba; cursor: pointer; color: white; padding: 9px 30px; border:none; border-radius: 5px;"><i class="icon-save"></i> Save Shortcode Settings</button>
		</div>



	</div>







</div>




<script type="text/javascript">
	var is_comingfrom_gen='false';


	jQuery('#notify_select_product').select2({

		ajax: {
			url : '<?php echo filter_var(admin_url('admin-ajax.php')); ?>',
			dataType: 'json',
			type: 'post',
			delay: 250, 
			data: function (params) {
				return {
					q: params.term, 
					action: 'frq_bgt_search_productss_for_instock_only', 
					
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
	jQuery('#notify_select_category').select2();









	var elt_selected=jQuery('#notify_appliedon').val();
	if ('products'==elt_selected) {
		jQuery('#notify_label').show();
		jQuery('#notify_1').show();
		jQuery('#notify_2').hide();
	}
	else if ('categories' ==elt_selected) {
		jQuery('#notify_label').show();
		jQuery('#notify_1').hide();
		jQuery('#notify_2').show(); 
	} 





	jQuery('body').on('change', '#notify_appliedon', function(){
		var notify_selected=jQuery(this).val();
		if ('products'==notify_selected) {
			jQuery('#notify_label').show();
			jQuery('#notify_1').show();
			jQuery('#notify_2').hide();
		}
		else if ('categories' ==notify_selected) {
			jQuery('#notify_label').show();
			jQuery('#notify_1').hide();
			jQuery('#notify_2').show(); 
		} 
	});


	jQuery(document).ready(function(){


		jQuery('body').on('click', '#absb_notify_for_sales', function(){

			jQuery('#gen_settings_div_notify').show();
			jQuery('#dis_settings_div_notify').hide();
			jQuery('#time_settings_div_notify').hide();
			jQuery('#msg_settings_div_notify').hide();
			jQuery('#shortcodediv').hide();
			jQuery('#notify_general_settings').addClass('abcactive');
			jQuery('#notify_display_settings').removeClass('abcactive');
			jQuery('#notify_time_settings').removeClass('abcactive');
			jQuery('#notify_message_settings').removeClass('abcactive');
			jQuery('#notify_shortcode_settings').removeClass('abcactive');
		});

		if (jQuery('#notify_on_custom_page').is(':checked')) {
			jQuery('.custom_url').attr('disabled', false);
			jQuery('.del_url').attr('disabled', false);
			jQuery('.del_url').css({'border':'1px solid red', 'color':'red', 'cursor':'pointer'});

			jQuery('#add_url').attr('disabled', false);
			jQuery('#add_url').css({'border':'1px solid green', 'color':'white' , 'cursor':'pointer'});

		} else {
			jQuery('.custom_url').attr('disabled', true);
			jQuery('.del_url').attr('disabled', true);
			jQuery('.del_url').css({'border': '1px solid lightgrey', 'color':'lightgrey', 'cursor':'not-allowed'});

			jQuery('#add_url').attr('disabled', true);
			jQuery('#add_url').css({'border': '1px solid lightgrey', 'color':'lightgrey', 'cursor':'not-allowed'});

		}



		if (jQuery('#enable_custom_msg_for_product').is(':checked'))  {
			jQuery('#custom_msg_product').attr('disabled', false);
			jQuery('#min_for_custom').attr('disabled', false);
			jQuery('#max_for_custom').attr('disabled', false);



		} else {
			jQuery('#custom_msg_product').attr('disabled', true);
			jQuery('#min_for_custom').attr('disabled', true);
			jQuery('#max_for_custom').attr('disabled', true);



		}




	});









	jQuery('body').on('change', '#enable_custom_msg_for_product', function(){
		if (jQuery(this).is(':checked'))  {
			jQuery('#custom_msg_product').attr('disabled', false);
			jQuery('#min_for_custom').attr('disabled', false);
			jQuery('#max_for_custom').attr('disabled', false);



		} else {
			jQuery('#custom_msg_product').attr('disabled', true);
			jQuery('#min_for_custom').attr('disabled', true);
			jQuery('#max_for_custom').attr('disabled', true);



		}
	});





















	jQuery('body').on('change', '#notify_on_custom_page', function(){
		if (jQuery(this).is(':checked'))  {
			jQuery('.custom_url').attr('disabled', false);
			jQuery('.del_url').attr('disabled', false);
			jQuery('.del_url').css({'border':'1px solid red', 'color':'red', 'cursor':'pointer'});

			jQuery('#add_url').attr('disabled', false);
			jQuery('#add_url').css({'border':'1px solid green', 'color':'white' , 'cursor':'pointer'});

		} else {
			jQuery('.custom_url').attr('disabled', true);
			jQuery('.del_url').attr('disabled', true);
			jQuery('.del_url').css({'border': '1px solid lightgrey', 'color':'lightgrey', 'cursor':'not-allowed'});

			jQuery('#add_url').attr('disabled', true);
			jQuery('#add_url').css({'border': '1px solid lightgrey', 'color':'lightgrey', 'cursor':'not-allowed'});

		}
	});

	jQuery('body').on('click', '#add_url' , function() {
		jQuery('#tb321243').find('tr:last').after('<tr><td><input type="text" placeholder="Enter Your Custom Url i.e: http://wordpress/my-account" class="custom_url" style="width:85%; " </td> <td><button type="button" class="del_url" style="border:1px solid red; padding:7px 10px; background-color:white; color:red; cursor:pointer; border-radius:5px; "><i class="fa fa-trash"></i> </button></td></tr>');

	});
	jQuery('body').on('click', '.del_url', function(){

		jQuery(this).parent().parent().remove();

	})
























	jQuery('body').on('click', '#add_msg_notify' , function() {
		jQuery('#tabxyzxxx').find('tr:first').after('<tr><td style="width: 30%"><strong></strong></td><td style="width: 60%;"><input type="text" name="msgnotify" id="msg_for_notify" class="notify_content input_type" style="width: 85%;" value="{first_name} in {city} purchased a <strong> {product} </strong> {product_with_link} <br> <i> {time_ago} </i>"></td><td style="width: 10%;"><button type="button" class="del_msg" id="delete_msg" style="border:1px solid red; padding:7px 24px; background-color:white; color:red; cursor:pointer; border-radius:4px; "> Remove</button></td> </tr>');

	});
	jQuery('body').on('click', '.del_msg', function(){

		jQuery(this).parent().parent().remove();

	})




















	jQuery('body').on('click', '#notify_general_settings' , function() {
		is_comingfrom_gen='false';
		jQuery('#gen_settings_div_notify').show();
		jQuery('#dis_settings_div_notify').hide();
		jQuery('#time_settings_div_notify').hide();
		jQuery('#msg_settings_div_notify').hide();
		jQuery('#shortcodediv').hide();




	});

	jQuery('body').on('click', '#notify_display_settings' , function() {
		is_comingfrom_gen='false';
		jQuery('#gen_settings_div_notify').hide();
		jQuery('#dis_settings_div_notify').show();
		jQuery('#time_settings_div_notify').hide();
		jQuery('#msg_settings_div_notify').hide();
		jQuery('#shortcodediv').hide();




	});


	jQuery('body').on('click', '#notify_time_settings' , function() {
		is_comingfrom_gen='false';
		jQuery('#gen_settings_div_notify').hide();
		jQuery('#dis_settings_div_notify').hide();
		jQuery('#time_settings_div_notify').show();
		jQuery('#msg_settings_div_notify').hide();
		jQuery('#shortcodediv').hide();




	});


	jQuery('body').on('click', '#notify_message_settings' , function() {
		is_comingfrom_gen='false';
		jQuery('#gen_settings_div_notify').hide();
		jQuery('#dis_settings_div_notify').hide();
		jQuery('#time_settings_div_notify').hide();
		jQuery('#msg_settings_div_notify').show();
		jQuery('#shortcodediv').hide();



	});



	jQuery('body').on('click', '#notify_shortcode_settings' , function() {
		
		jQuery('#gen_settings_div_notify').hide();
		jQuery('#dis_settings_div_notify').hide();
		jQuery('#time_settings_div_notify').hide();
		jQuery('#msg_settings_div_notify').hide();
		jQuery('#shortcodediv').show();



	});


	jQuery('#notify_save_general').on('click', function(){


		var activatenotify = jQuery('#notify_activate').prop('checked');

		// var comes_from_gen_settings = false;


		var appliedon_notify=jQuery('#notify_appliedon').val();
		if('products'==appliedon_notify){
			var procat_ids_notify=jQuery('#notify_select_product').val();
		}
		else if('categories'==appliedon_notify){
			var procat_ids_notify=jQuery('#notify_select_category').val();
		}

		if (jQuery('#notify_activate').is(':checked') && '' == procat_ids_notify )  {

			jQuery('#notify_shortcode_settings').click();
			// comes_from_gen_settings = true;
			alert('Please select atleast one product/category');

			  jQuery('#notify_select_product').siblings(".select2-container").css('border', '2px solid lightgrey');
			  jQuery('#notify_select_product').siblings(".select2-container").css('border-radius', '5px');
			  jQuery('#notify_select_category').siblings(".select2-container").css('border', '2px solid lightgrey');
			  jQuery('#notify_select_category').siblings(".select2-container").css('border-radius', '5px');


			  jQuery('body').on('change', '#notify_select_product' , function() {
			  jQuery('#notify_select_product').siblings(".select2-container").css('border', 'none');
			  })

			   jQuery('body').on('change', '#notify_select_category' , function() {
			  jQuery('#notify_select_category').siblings(".select2-container").css('border', 'none');
			  })

			   is_comingfrom_gen='true';
			return;

		} else {
			// comes_from_gen_settings = false;

		}



		// if (comes_from_gen_settings) {
		// 	if ('' != procat_ids_notify  ) {
		// 		jQuery('#notify_save_general').click();
		// 	}
		// }








		var enablephone = jQuery('#notify_enable_phone').prop('checked');
		var notifyonshop = jQuery('#notify_on_shop').prop('checked');
		var notifyonproduct = jQuery('#notify_on_product').prop('checked');
		var notifyoncart = jQuery('#notify_on_cart').prop('checked');
		var notify_on_checkout = jQuery('#notify_on_checkout').prop('checked');
		var notify_on_custom_url = jQuery('#notify_on_custom_page').prop('checked');

		var custom_url=[];

		jQuery('body').find('.custom_url').each(function() {
			custom_url.push(jQuery(this).val());
		})

		var validity = false;
		jQuery('.custom_url').each(function(){
			if (jQuery(this).val() == '' && notify_on_custom_url == true) {
				alert('Please fill Custom Url field');
				validity=true;
				return;
			}
		});
		if(validity){
			return;
		}






		jQuery.ajax({
			url : '<?php echo filter_var(admin_url('admin-ajax.php')); ?>',
			type : 'post',
			data : {
				action : 'nfs_saving_general_settings_for_notify',
				activatenotify:activatenotify,
				enablephone:enablephone,
				notifyonshop:notifyonshop,
				notifyonproduct:notifyonproduct,         
				notifyoncart:notifyoncart,
				notify_on_checkout:notify_on_checkout,
				notify_on_custom_url:notify_on_custom_url,
				custom_url:custom_url



			},
			success : function( response ) {
				window.onbeforeunload = null;





				jQuery('#absbsavemsg').html('<div class="notice notice-success is-dismissible elt_msg" ><p id="absb_messageonsave">Rule has been saved</p><button type="button" class="notice-dismiss hidedivv"><span class="screen-reader-text">Dismiss this notice.</span></button></div>')
				jQuery('#absbsavemsg').show();
				jQuery('#absb_messageonsave').html('General Settings have been saved');
				jQuery("html, body").animate({ scrollTop: 0 }, "slow");
				jQuery("#absbsavemsg").delay(7000).fadeOut(3000);




			}

		});
	});





	jQuery('#notify_save_display').on('click', function(){


		var notify_bgcolor = jQuery('#notify_bg_color').val();
		var notify_txt_color = jQuery('#notify_txt_color').val();
		var notify_border_color = jQuery('#notify_border_color').val();
		var notify_location = jQuery('#notify_location').val();
		var notify_animate = jQuery('#notify_animate').val();
		var notify_radius = jQuery('#notify_radius').val();
		var enable_bg_image = jQuery('#enable_bg_image').prop('checked');

		enable_bg_image

		if ( notify_radius > 30 || notify_radius <0) {
			alert('Please Select between 1 to 30');
			return;
		}


		jQuery.ajax({
			url : '<?php echo filter_var(admin_url('admin-ajax.php')); ?>',
			type : 'post',
			data : {
				action : 'nfs_saving_display_settings_for_notify',
				notify_bgcolor:notify_bgcolor,
				notify_txt_color:notify_txt_color,
				notify_border_color:notify_border_color,
				notify_location:notify_location,
				notify_animate:notify_animate,         
				notify_radius:notify_radius,
				enable_bg_image:enable_bg_image

			},
			success : function( response ) {
				window.onbeforeunload = null;





				jQuery('#absbsavemsg').html('<div class="notice notice-success is-dismissible elt_msg" ><p id="absb_messageonsave">Rule has been saved</p><button type="button" class="notice-dismiss hidedivv"><span class="screen-reader-text">Dismiss this notice.</span></button></div>')
				jQuery('#absbsavemsg').show();
				jQuery('#absb_messageonsave').html('Display Settings have been saved');
				jQuery("html, body").animate({ scrollTop: 0 }, "slow");
				jQuery("#absbsavemsg").delay(7000).fadeOut(3000);




			}

		});
	});

	jQuery('#notify_save_time').on('click', function(){


		var number_of_notify = jQuery('#number_of_notify').val();

		var display_time = jQuery('#display_time_notify').val();
		var start_range_notify = jQuery('#start_range_notify').val();
		var end_range_notify = jQuery('#end_range_notify').val();


		jQuery.ajax({
			url : '<?php echo filter_var(admin_url('admin-ajax.php')); ?>',
			type : 'post',
			data : {
				action : 'nfs_saving_time_settings_for_notify',
				number_of_notify:number_of_notify,
				display_time:display_time,
				start_range_notify:start_range_notify,       
				end_range_notify:end_range_notify       


			},
			success : function( response ) {
				window.onbeforeunload = null;





				jQuery('#absbsavemsg').html('<div class="notice notice-success is-dismissible elt_msg" ><p id="absb_messageonsave">Rule has been saved</p><button type="button" class="notice-dismiss hidedivv"><span class="screen-reader-text">Dismiss this notice.</span></button></div>')
				jQuery('#absbsavemsg').show();
				jQuery('#absb_messageonsave').html('Time Settings have been saved');
				jQuery("html, body").animate({ scrollTop: 0 }, "slow");
				jQuery("#absbsavemsg").delay(7000).fadeOut(3000);




			}

		});
	});



	jQuery('#notify_save_msg').on('click', function(){



		var enable_custom_msg_for_product = jQuery('#enable_custom_msg_for_product').prop('checked');

		var custom_msg_product = jQuery('#custom_msg_product').val(); 
		var min_for_custom = jQuery('#min_for_custom').val(); 
		var max_for_custom = jQuery('#max_for_custom').val();



		var notify_content=[];

		jQuery('body').find('.notify_content').each(function() {
			notify_content.push(jQuery(this).val());
		})




		var validity=false;

		jQuery('.notify_content').each(function(){
			if (jQuery(this).val() == '') {
				alert('Please fill Notification Content field');   
				validity=true;                
				return;
			} 
		});
		if(validity){
			return;
		}

		jQuery.ajax({
			url : '<?php echo filter_var(admin_url('admin-ajax.php')); ?>',
			type : 'post',
			data : {
				action : 'nfs_saving_message_settings_for_notify',


				notify_content:notify_content,
				enable_custom_msg_for_product:enable_custom_msg_for_product,
				custom_msg_product:custom_msg_product,

				min_for_custom:min_for_custom,
				max_for_custom:max_for_custom



			},
			success : function( response ) {
				window.onbeforeunload = null;





				jQuery('#absbsavemsg').html('<div class="notice notice-success is-dismissible elt_msg" ><p id="absb_messageonsave">Rule has been saved</p><button type="button" class="notice-dismiss hidedivv"><span class="screen-reader-text">Dismiss this notice.</span></button></div>')
				jQuery('#absbsavemsg').show();
				jQuery('#absb_messageonsave').html('Message Settings have been saved');
				jQuery("html, body").animate({ scrollTop: 0 }, "slow");
				jQuery("#absbsavemsg").delay(7000).fadeOut(3000);




			}

		});
	});



	jQuery('#notify_save_shortcode').on('click', function(){






		var appliedon_notify=jQuery('#notify_appliedon').val();
		if('products'==appliedon_notify){
			var procat_ids_notify=jQuery('#notify_select_product').val();
		}
		else if('categories'==appliedon_notify){
			var procat_ids_notify=jQuery('#notify_select_category').val();
		}


		if(''==procat_ids_notify){
			alert('PLEASE SELECT PRODUCT/CATEGORY');
			return;
		}



		var virtual_names = jQuery('#virtual_names').val(); 

		if(''==jQuery('#virtual_names').val()){
			alert('Please fill out some names');
			return;
		}


		if (virtual_names.trim().slice(-1) == ',') {
			virtual_names = virtual_names.substring(0, virtual_names.length-1);
		}


		var virtual_cities = jQuery('#virtual_cities').val();

		if(''==jQuery('#virtual_cities').val()){
			alert('Please fill out some cities');
			return;
		}

		if (virtual_cities.trim().slice(-1) == ',') {
			virtual_cities = virtual_cities.substring(0, virtual_cities.length-1);
		}


		var virtual_timeago = jQuery('#virtual_timeago').val();

		if(''==jQuery('#virtual_timeago').val()){
			alert('Please fill out some virtual times');
			return;
		}

		if (virtual_timeago.trim().slice(-1) == ',') {
			virtual_timeago = virtual_timeago.substring(0, virtual_timeago.length-1);
		}



		// var comes_from_gen_settings = false;

		if (is_comingfrom_gen=='true' )  {

			jQuery('#notify_save_general').click();
			is_comingfrom_gen = 'false';
		}

		// if (comes_from_gen_settings) {
		// 	if ('' != procat_ids_notify  ) {
		// 		jQuery('#notify_save_general').click();
		// 		alert()
		// 	}
		// }





		jQuery.ajax({
			url : '<?php echo filter_var(admin_url('admin-ajax.php')); ?>',
			type : 'post',
			data : {
				action : 'nfs_saving_shortcode_settings_for_notify',


				appliedon_notify:appliedon_notify,
				procat_ids_notify:procat_ids_notify,
				virtual_names:virtual_names,
				virtual_cities:virtual_cities,
				virtual_timeago:virtual_timeago




			},
			success : function( response ) {
				window.onbeforeunload = null;





				jQuery('#absbsavemsg').html('<div class="notice notice-success is-dismissible elt_msg" ><p id="absb_messageonsave">Rule has been saved</p><button type="button" class="notice-dismiss hidedivv"><span class="screen-reader-text">Dismiss this notice.</span></button></div>')
				jQuery('#absbsavemsg').show();
				jQuery('#absb_messageonsave').html('Settings have been saved');
				jQuery("html, body").animate({ scrollTop: 0 }, "slow");
				jQuery("#absbsavemsg").delay(7000).fadeOut(3000);




			}

		});
	});

</script>
