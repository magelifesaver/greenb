<?php
add_filter('woocommerce_settings_tabs_array', 'plugify_ai_set_filter_woocommerce_settings_tabs', 50);
add_action('woocommerce_settings_plugify_ai_set', 'plugify_ai_set_function_for_callback_of_tab');

function plugify_ai_set_filter_woocommerce_settings_tabs ( $tabs ) {
	$tabs['plugify_ai_set'] = 'AI Addons';      
	return $tabs;
}

function plugify_ai_set_function_for_callback_of_tab() {
	wp_nonce_field('plug_nonce_for_ajax', 'plug_nonce');

	$max_enhance_tokens = get_option('max_enhance_tokens');
	$max_short_descriptions_tokens = get_option('max_short_descriptions_tokens');
	$max_reply_tokens = get_option('max_reply_tokens');

	$plugify_save_ai_settings = get_option('plugify_save_ai_settings');
	$plugify_save_tokens_settings = get_option('plugify_save_tokens_settings');
	$plugify_save_chatbot_settings = get_option('plugify_save_chatbot_settings');

	$chatbot_display_woocommerce_pages = $plugify_save_chatbot_settings['chatbot_display_woocommerce_pages'];




	?>

	<div id="absbsavemsg" style="display:none;">
	</div>

	<ul class="subsubsub">
		<li id="open_ai_btn" class="main_buttons" data-target="#open_ai_div"><a class="normal_a current">Open AI key</a></li> |
		<li id="description_reply_btn" class="main_buttons" data-target="#description_reply"><a class="normal_a">Token settings</a></li> |
		<li id="chatbott_btn" class="main_buttons" data-target="#chatbott"><a class="normal_a">Chat bot settings</a></li>
	</ul>

	<br>

	<div id="open_ai_div">

		<input type="hidden" name="old_gpt_model" id="old_gpt_model" value="<?php echo esc_attr($plugify_save_ai_settings['openai_modell']); ?>">
		<table class="form-table">

			<tbody>


				<tr class="message_trrr" style="">
					<th scope="row" class="titledesc">OpenAI API key</th>
					<td class="forminp forminp-file">
						<fieldset>

							<?php echo wp_kses_post(wc_help_tip('Enter your OpenAI API key here.', false)); ?>

							<label for="openai_apii">

								<input type="text" name="openai_apii" id="openai_apii" value="<?php echo esc_attr($plugify_save_ai_settings['openai_apii']); ?>" >
							</label>  
						</fieldset>
					</td>
				</tr>

				<tr class="message_trrr" style="">
					<th scope="row" class="titledesc">Choose openAI model</th>
					<td class="forminp forminp-file">
						<fieldset>

							<?php echo wp_kses_post(wc_help_tip('Select your preferred model from here. Each model differs in intelligence, speed, and information handling.', false)); ?>

							<select id="openai_modell" name="openai_modell">
								<option value="gpt-4o-mini"
								<?php
								if ('gpt-4o-mini' == $plugify_save_ai_settings['openai_modell']) {
									echo 'selected';
								}
								?>
								>GPT-4o-mini – budget-friendly</option>
								<option value="gpt-4o"
								<?php
								if ('gpt-4o' == $plugify_save_ai_settings['openai_modell']) {
									echo 'selected';
								}
								?>
								>GPT-4o – Fastest, smartest</option>
								<option value="gpt-4-turbo"
								<?php
								if ('gpt-4-turbo' == $plugify_save_ai_settings['openai_modell']) {
									echo 'selected';
								}
								?>
								>GPT-4 Turbo – Optimized GPT-4 with lower cost</option>
								<option value="gpt-4"
								<?php
								if ('gpt-4' == $plugify_save_ai_settings['openai_modell']) {
									echo 'selected';
								}
								?>
								>GPT-4 – Original GPT-4 model</option>
								<option value="gpt-3.5-turbo"
								<?php
								if ('gpt-3.5-turbo' == $plugify_save_ai_settings['openai_modell']) {
									echo 'selected';
								}
								?>
								>GPT-3.5 Turbo – Budget-friendly and fast</option>
							</select>
							<br>
							<p style="margin-left: 25px; color: gray;">
								Want to compare features and pricing of OpenAI models?
								<a href="https://openai.com/api/pricing/" target="_blank" rel="noopener noreferrer">Click here</a>
								to view OpenAI's official pricing and model comparison
							</p>
						</fieldset>
					</td>
				</tr>


			</tbody>
		</table>
		<button type="button" class="components-button is-primary" id="save_openai_settings">Save changes</button>
	</div>

	<div id="description_reply" style="display:none;">



		<table class="form-table">
			<tbody>
				<tr class="message_trrr" style="">
					<th scope="row" class="titledesc">Max. tokens for description enhancements</th>
					<td class="forminp forminp-file">
						<fieldset>
							<?php echo wp_kses_post(wc_help_tip('Enter maximum token allowed for description enhancement.', false)); ?>
							<label for="max_enhance_tokens">
								<input type="number" min="50" max="1000" step="1" name="max_enhance_tokens" id="max_enhance_tokens" value="<?php echo esc_attr($plugify_save_tokens_settings['max_enhance_tokens']); ?>">
							</label>  
						</fieldset>
					</td>
				</tr>
<!-- 
				<tr class="message_trrr" style="">
					<th scope="row" class="titledesc">Max. tokens for short descriptions</th>
					<td class="forminp forminp-file">
						<fieldset>
							<?php //echo wp_kses_post(wc_help_tip('Enter maximum token allowed for short description.', false)); ?>
							<label for="max_short_descriptions_tokens">
								<input type="number" min="50" max="1000" step="1" name="max_short_descriptions_tokens" id="max_short_descriptions_tokens" value="<?php //echo esc_attr($plugify_save_tokens_settings['max_short_descriptions_tokens']); ?>">
							</label>  
						</fieldset>
					</td>
				</tr>
			-->
			<tr class="message_trrr" style="">
				<th scope="row" class="titledesc">Max. tokens for reviews replies</th>
				<td class="forminp forminp-file">
					<fieldset>
						<?php echo wp_kses_post(wc_help_tip('Enter maximum token allowed for reviews reply.', false)); ?>
						<label for="max_reply_tokens">
							<input type="number" min="50" max="1000" step="1" name="max_reply_tokens" id="max_reply_tokens" value="<?php echo esc_attr($plugify_save_tokens_settings['max_reply_tokens']); ?>">
						</label>  
					</fieldset>
				</td>
			</tr>

			<tr class="message_trrr">
				<td colspan="2" scope="row" class="titledesc" style="padding: 0px !important;">
					<p style="margin: 0 0 15px 0; color: #555; max-width: 800px;">
						<strong>Note:</strong> The number of tokens affects the level of detail in AI responses, and it also impacts API usage costs. More tokens generally mean more informative and longer responses also increase the cost. You can learn more about tokens and how they work at 
						<a href="https://platform.openai.com/tokenizer" target="_blank">OpenAI's Tokenizer Guide</a>.
					</p>
				</td>
			</tr>

		</tbody>
	</table>

	<button type="button" class="components-button is-primary" id="save_tokens_settings">Save changes</button>
</div>

<?php

$plugify_save_chatbot_settings['enable_chatbott'] = $plugify_save_chatbot_settings['enable_chatbott'] ?? 'true';


?>
<div id="chatbott" style="display:none;">

	<table class="form-table">

		<tbody>



			<tr class="message_trrr" style="">
				<th scope="row" class="titledesc">Enable Chat Bot</th>
				<td class="forminp forminp-file">
					<fieldset>

						<label for="enable_chatbott">
							<input style="margin-left:25px;" type="checkbox" name="enable_chatbott" id="enable_chatbott"
							<?php
							if ('true' == $plugify_save_chatbot_settings['enable_chatbott']) {
								echo 'checked';
							}
							?>
							>
							Enable the AI Chat Bot on the site
						</label>  
					</fieldset>
				</td>
			</tr>



			<tr class="message_trrr">
				<th scope="row" class="titledesc">Display chatbot on WooCommerce pages</th>
				<td class="forminp forminp-select">
					<fieldset>
						<?php echo wp_kses_post(wc_help_tip('Select WooCommerce pages where the chatbot should appear on WooCommerce pages.', false)); ?>
						<label for="chatbot_display_woocommerce_pages">
							<select name="chatbot_display_woocommerce_pages[]" id="chatbot_display_woocommerce_pages" multiple>


								<option value="home_page"
								<?php
								if (is_array($chatbot_display_woocommerce_pages) && in_array('home_page', $chatbot_display_woocommerce_pages)) {
									echo 'selected';
								}
								?>
								>Home Page</option>
								<option value="shop_page"
								<?php
								if (is_array($chatbot_display_woocommerce_pages) && in_array('shop_page', $chatbot_display_woocommerce_pages)) {
									echo 'selected';
								}
								?>
								>Shop Page</option>
								<option value="category_page"
								<?php
								if (is_array($chatbot_display_woocommerce_pages) && in_array('category_page', $chatbot_display_woocommerce_pages)) {
									echo 'selected';
								}
								?>
								>Category Page</option>
								<option value="product_page"
								<?php
								if (is_array($chatbot_display_woocommerce_pages) && in_array('product_page', $chatbot_display_woocommerce_pages)) {
									echo 'selected';
								}
								?>
								>Product Page</option>
								<option value="cart_page"
								<?php
								if (is_array($chatbot_display_woocommerce_pages) && in_array('cart_page', $chatbot_display_woocommerce_pages)) {
									echo 'selected';
								}
								?>
								>Cart Page</option>
								<option value="checkout_page"
								<?php
								if (is_array($chatbot_display_woocommerce_pages) && in_array('checkout_page', $chatbot_display_woocommerce_pages)) {
									echo 'selected';
								}
								?>
								>Checkout Page</option>
								<option value="thankyou"
								<?php
								if (is_array($chatbot_display_woocommerce_pages) && in_array('thankyou', $chatbot_display_woocommerce_pages)) {
									echo 'selected';
								}
								?>
								>Thank You Page</option>
								<option value="myaccount"
								<?php
								if (is_array($chatbot_display_woocommerce_pages) && in_array('myaccount', $chatbot_display_woocommerce_pages)) {
									echo 'selected';
								}
								?>
								>My Account Page</option>

							</select>
						</label>
						<br>
						<button style="margin-left: 25px;" type="button" class="button button-secondary" id="select_all_woo_pages">Select All</button>
						<button style="" type="button" class="button button-secondary" id="remove_all_woo_pages">Remove All</button>
					</fieldset>
				</td>
			</tr>

			<tr class="message_trrr">
				<th scope="row" class="titledesc">Display chatbot on other pages</th>
				<td class="forminp forminp-select">
					<fieldset>
						<?php echo wp_kses_post(wc_help_tip('Select the page where you want the chatbot to appear.', false)); ?>
						<label for="chatbot_display_page">
							<select name="chatbot_display_page[]" id="chatbot_display_page" multiple="" >
								<?php
								$chatbot_display_page = $plugify_save_chatbot_settings['chatbot_display_page'];

								$args = array(
									'post_type' => 'page',
									'post_status' => 'publish',
										// phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
									'exclude' => array(
										wc_get_page_id('shop'),
										wc_get_page_id('cart'),
										wc_get_page_id('checkout'),
										wc_get_page_id('myaccount'),
										wc_get_page_id('terms')
									)
								);

								$pages = get_pages($args);

								foreach ($pages as $page) {
									if (is_array($chatbot_display_page) && !empty($chatbot_display_page)) {
										$selected = ( in_array( $page->ID, $chatbot_display_page ) ) ? 'selected' : '';
									} else {
										$selected = '';
									}

									echo '<option value="' . esc_attr($page->ID) . '" ' . esc_attr($selected) . '>' . esc_html($page->post_title) . '</option>';
								}
								?>
							</select>
						</label>
						<br>
						<button style="margin-left: 25px;" type="button" class="button button-secondary" id="select_all_pages">Select All</button>
						<button style="" type="button" class="button button-secondary" id="remove_all_pages">Remove All</button>
					</fieldset>
				</td>
			</tr>

			<tr class="message_trrr" style="">
				<th scope="row" class="titledesc">Max. tokens for chatbot reply</th>
				<td class="forminp forminp-file">
					<fieldset>

						<?php echo wp_kses_post(wc_help_tip('Enter maximum token allowed for chatbot reply.', false)); ?>

						<label for="max_chatbot_tokens">

							<input type="number" min="50" max="1000" step="1" name="max_bot_reply" id="max_bot_reply" value="<?php echo esc_attr($plugify_save_chatbot_settings['max_bot_reply']); ?>">
						</label>  
					</fieldset>
				</td>
			</tr>



			<tr class="message_trrr" style="">
				<th scope="row" class="titledesc">Store name</th>
				<td class="forminp forminp-file">
					<fieldset>

						<?php echo wp_kses_post(wc_help_tip('Store name to be mentioned in chatbot replies.', false)); ?>

						<label for="max_chatbot_tokens">

							<input type="text" name="store_namee" id="store_namee" value="<?php echo esc_attr(stripcslashes($plugify_save_chatbot_settings['store_namee'])); ?>">
						</label>  
					</fieldset>
				</td>
			</tr>


			<tr class="message_trrr" style="">
				<th scope="row" class="titledesc">Additional store details</th>
				<td class="forminp forminp-file">
					<fieldset>

						<?php echo wp_kses_post(wc_help_tip('Add some additional store details here. These details will help the chatbot provide more personalized and accurate responses in its replies.', false)); ?>

						<label for="store_namee">
							<textarea name="store_details" id="store_details" rows="5" placeholder="e.g. We are located in New York, USA. We deliver within 5–7 business days. Currently, we ship to the US, Canada, UK, and Australia. Our customer support is available 24/7. For assistance, visit our contact page: My_Contact_Page_Link"><?php echo isset($plugify_save_chatbot_settings['store_details']) ? esc_html(stripcslashes($plugify_save_chatbot_settings['store_details'])) : ''; ?></textarea>
							<br>
							<p style="font-size: 13px; color: #333;">
								You may use the following shortcodes in your additional store details to dynamically insert relevant links or information:
								<br>
								<span class="copy-shortcode" style="color: #007cba;" data-shortcode="{admin_email}">{admin_email}</span> – Displays the store admin's email address<br>
								<span class="copy-shortcode" style="color: #007cba;" data-shortcode="{shop_page_url}">{shop_page_url}</span> – Inserts a link to the shop page<br>
								<span class="copy-shortcode" style="color: #007cba;" data-shortcode="{home_page_url}">{home_page_url}</span> – Inserts a link to the homepage<br>
								<span class="copy-shortcode" style="color: #007cba;" data-shortcode="{my_account_url}">{my_account_url}</span> – Inserts a link to the customer account page<br>
							</p>



						</label>  
					</fieldset>

				</td>
			</tr>


			<tr class="message_trrr" style="">
				<th scope="row" class="titledesc">Email request message</th>
				<td class="forminp forminp-file">
					<fieldset>

						<?php echo wp_kses_post(wc_help_tip('Enter message that will appear if chatboat asks for email address.', false)); ?>

						<label for="max_chatbot_tokens">

							<textarea name="bot_email_request_message" rows="4" id="bot_email_request_message"><?php echo esc_attr(stripcslashes($plugify_save_chatbot_settings['bot_email_request_message'])); ?></textarea>
						</label>  
					</fieldset>
				</td>
			</tr>

			<tr class="message_trrr" style="">
				<th scope="row" class="titledesc">Message after getting email</th>
				<td class="forminp forminp-file">
					<fieldset>

						<?php echo wp_kses_post(wc_help_tip('Enter message that will appear after getting the email address.', false)); ?>

						<label for="max_chatbot_tokens">

							<textarea name="after_mail_msg" rows="4" id="after_mail_msg"><?php echo esc_attr(stripcslashes($plugify_save_chatbot_settings['after_mail_msg'])); ?></textarea>
						</label>  
					</fieldset>
				</td>
			</tr>


			<tr class="message_trrr" style="">
				<th scope="row" class="titledesc">Message if bot faced technical issue</th>
				<td class="forminp forminp-file">
					<fieldset>

						<?php echo wp_kses_post(wc_help_tip('Enter message that will appear in case of technical issue or connection lost.', false)); ?>

						<label for="max_chatbot_tokens">

							<textarea name="technical_issue" rows="4" id="technical_issue"><?php echo esc_attr(stripcslashes($plugify_save_chatbot_settings['technical_issue'])); ?></textarea>
						</label>  
					</fieldset>
				</td>
			</tr>



			<tr class="message_trrr" style="">
				<th scope="row" class="titledesc">Chatbot header text</th>
				<td class="forminp forminp-file">
					<fieldset>



						<label for="max_chatbot_tokens">

							<input style="margin-left: 25px;" type="text" name="bot_header_text" id="bot_header_text" value="<?php echo esc_attr($plugify_save_chatbot_settings['bot_header_text']); ?>">
						</label>  
					</fieldset>
				</td>
			</tr>



			<tr class="message_trrr" style="">
				<th scope="row" class="titledesc">Chatbot header background color</th>
				<td class="forminp forminp-file">
					<fieldset>



						<label for="max_chatbot_tokens">

							<input style="margin-left: 25px;" type="text" class="jscolor" name="bot_header_bg" id="bot_header_bg" value="<?php echo esc_attr($plugify_save_chatbot_settings['bot_header_bg']); ?>">
						</label>  
					</fieldset>
				</td>
			</tr>


			<tr class="message_trrr" style="">
				<th scope="row" class="titledesc">Chatbot header text color</th>
				<td class="forminp forminp-file">
					<fieldset>



						<label for="max_chatbot_tokens">

							<input style="margin-left: 25px;" type="text" class="jscolor" name="bot_header_txt_clr" id="bot_header_txt_clr" value="<?php echo esc_attr($plugify_save_chatbot_settings['bot_header_txt_clr']); ?>">
						</label>  
					</fieldset>
				</td>
			</tr>




			<tr class="message_trrr" style="">
				<th scope="row" class="titledesc">Chatbot window background color</th>
				<td class="forminp forminp-file">
					<fieldset>



						<label for="max_chatbot_tokens">

							<input style="margin-left: 25px;" type="text" class="jscolor" name="window_bg" id="window_bg" value="<?php echo esc_attr($plugify_save_chatbot_settings['window_bg']); ?>">
						</label>  
					</fieldset>
				</td>
			</tr>




			<tr class="message_trrr" style="">
				<th scope="row" class="titledesc">Admin's message background color</th>
				<td class="forminp forminp-file">
					<fieldset>



						<label for="max_chatbot_tokens">

							<input style="margin-left: 25px;" type="text" class="jscolor" name="admin_bg" id="admin_bg" value="<?php echo esc_attr($plugify_save_chatbot_settings['admin_bg']); ?>">
						</label>  
					</fieldset>
				</td>
			</tr>


			<tr class="message_trrr" style="">
				<th scope="row" class="titledesc">Admin's message text color</th>
				<td class="forminp forminp-file">
					<fieldset>



						<label for="max_chatbot_tokens">

							<input style="margin-left: 25px;" type="text" class="jscolor" name="admin_txt_color" id="admin_txt_color" value="<?php echo esc_attr($plugify_save_chatbot_settings['admin_txt_color']); ?>">
						</label>  
					</fieldset>
				</td>
			</tr>


			<tr class="message_trrr" style="">
				<th scope="row" class="titledesc">Admin's message time color</th>
				<td class="forminp forminp-file">
					<fieldset>



						<label for="max_chatbot_tokens">

							<input style="margin-left: 25px;" type="text" class="jscolor" name="admin_time_color" id="admin_time_color" value="<?php echo esc_attr($plugify_save_chatbot_settings['admin_time_color']); ?>">
						</label>  
					</fieldset>
				</td>
			</tr>






			<tr class="message_trrr" style="">
				<th scope="row" class="titledesc">User's message background color</th>
				<td class="forminp forminp-file">
					<fieldset>


						<label for="max_chatbot_tokens">

							<input  style="margin-left: 25px;" type="text" class="jscolor" name="user_bg" id="user_bg" value="<?php echo esc_attr($plugify_save_chatbot_settings['user_bg']); ?>">
						</label>  
					</fieldset>
				</td>
			</tr>


			<tr class="message_trrr" style="">
				<th scope="row" class="titledesc">User's message text color</th>
				<td class="forminp forminp-file">
					<fieldset>


						<label for="max_chatbot_tokens">

							<input  style="margin-left: 25px;" type="text" class="jscolor" name="user_txt_color" id="user_txt_color" value="<?php echo esc_attr($plugify_save_chatbot_settings['user_txt_color']); ?>">
						</label>  
					</fieldset>
				</td>
			</tr>


			<tr class="message_trrr" style="">
				<th scope="row" class="titledesc">User's message time color</th>
				<td class="forminp forminp-file">
					<fieldset>

						<label for="max_chatbot_tokens">

							<input style="margin-left: 25px;" type="text" class="jscolor" name="user_time_color" id="user_time_color" value="<?php echo esc_attr($plugify_save_chatbot_settings['user_time_color']); ?>">
						</label>  
					</fieldset>
				</td>
			</tr>



			<tr class="message_trrr" style="">
				<th scope="row" class="titledesc">Send button text</th>
				<td class="forminp forminp-file">
					<fieldset>

						<label for="max_chatbot_tokens">

							<input  style="margin-left: 25px;" type="text" name="send_btn_txt" id="send_btn_txt" value="<?php echo esc_attr($plugify_save_chatbot_settings['send_btn_txt']); ?>">
						</label>  
					</fieldset>
				</td>
			</tr>



			<tr class="message_trrr" style="">
				<th scope="row" class="titledesc">Send button background color</th>
				<td class="forminp forminp-file">
					<fieldset>



						<label for="max_chatbot_tokens">

							<input  style="margin-left: 25px;" type="text" class="jscolor" name="send_btn_bg" id="send_btn_bg" value="<?php echo esc_attr($plugify_save_chatbot_settings['send_btn_bg']); ?>">
						</label>  
					</fieldset>
				</td>
			</tr>


			<tr class="message_trrr" style="">
				<th scope="row" class="titledesc">Send button text color</th>
				<td class="forminp forminp-file">
					<fieldset>



						<label for="max_chatbot_tokens">

							<input style="margin-left: 25px;" type="text" class="jscolor" name="send_btn_txt_color" id="send_btn_txt_color" value="<?php echo esc_attr($plugify_save_chatbot_settings['send_btn_txt_color']); ?>">
						</label>  
					</fieldset>
				</td>
			</tr>



			<tr class="message_trrr" style="">
				<th scope="row" class="titledesc">Chat icon background color</th>
				<td class="forminp forminp-file">
					<fieldset>


						<label for="max_chatbot_tokens">

							<input style="margin-left: 25px;" type="text" class="jscolor" name="icon_bg" id="icon_bg" value="<?php echo esc_attr($plugify_save_chatbot_settings['icon_bg']); ?>">
						</label>  
					</fieldset>
				</td>
			</tr>


			<tr class="message_trrr" style="">
				<th scope="row" class="titledesc">Chat icon font color</th>
				<td class="forminp forminp-file">
					<fieldset>



						<label for="max_chatbot_tokens">

							<input style="margin-left: 25px;" type="text" class="jscolor" name="icon_color" id="icon_color" value="<?php echo esc_attr($plugify_save_chatbot_settings['icon_color']); ?>">
						</label>  
					</fieldset>
				</td>
			</tr>




		</tbody>
	</table>
	<button type="button" class="components-button is-primary" id="save_chatbot_settings">Save changes</button>
</div>
<style type="text/css">
	.main_buttons {
		cursor: pointer;
	}
</style>
<?php
}

// add_action('woocommerce_update_options_plugify_ai_set', 'plugify_ai_save_gen_settings');

// function plugify_ai_save_gen_settings() {

// 	if (isset($_POST['plug_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['plug_nonce'])), 'plug_nonce_for_ajax')) {

// 		if (isset($_REQUEST['openai_apii'])) {
// 			update_option('openai_apii', sanitize_text_field(wp_unslash($_REQUEST['openai_apii'])));
// 		} 

// 		if (isset($_REQUEST['openai_modell'])) {
// 			update_option('openai_modell', sanitize_text_field(wp_unslash($_REQUEST['openai_modell'])));
// 		} 

// 		if (isset($_REQUEST['max_enhance_tokens'])) {
// 			update_option('max_enhance_tokens', sanitize_text_field(wp_unslash($_REQUEST['max_enhance_tokens'])));
// 		} 

// 		if (isset($_REQUEST['max_short_descriptions_tokens'])) {
// 			update_option('max_short_descriptions_tokens', sanitize_text_field(wp_unslash($_REQUEST['max_short_descriptions_tokens'])));
// 		} 

// 		if (isset($_REQUEST['max_reply_tokens'])) {
// 			update_option('max_reply_tokens', sanitize_text_field(wp_unslash($_REQUEST['max_reply_tokens'])));
// 		} 
// 	}
// }
