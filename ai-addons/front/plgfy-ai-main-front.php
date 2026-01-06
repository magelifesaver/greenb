<?php

function plugify_is_thankyou_page() {
	return is_wc_endpoint_url('order-received');
}

function plugify_formated_message_front( $message ) {

	$message = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2" target="_blank">$1</a>', $message);

	$message = nl2br(trim($message));

	return $message;
}

add_action('wp_footer', 'plugify_woo_ai_chatbot_add_icon');
function plugify_woo_ai_chatbot_add_icon() {


	$plugify_save_chatbot_settings = get_option('plugify_save_chatbot_settings');

	if (!isset($plugify_save_chatbot_settings['enable_chatbott'])) {
		$plugify_save_chatbot_settings['enable_chatbott'] = 'true';
	}

	if (isset($plugify_save_chatbot_settings['enable_chatbott']) && $plugify_save_chatbot_settings['enable_chatbott'] == 'false') {
		return;
	}

	$page_object = get_queried_object();
	$page_id     = get_queried_object_id();

	$allowed_pages_ids = array();

	if (isset($plugify_save_chatbot_settings['chatbot_display_page']) && is_array($plugify_save_chatbot_settings['chatbot_display_page'])) {

		$allowed_pages_ids = $plugify_save_chatbot_settings['chatbot_display_page'];
	}


	$chatbot_display_woocommerce_pages = array();

	if (isset($plugify_save_chatbot_settings['chatbot_display_woocommerce_pages']) && is_array($plugify_save_chatbot_settings['chatbot_display_woocommerce_pages'])) {
		$chatbot_display_woocommerce_pages = $plugify_save_chatbot_settings['chatbot_display_woocommerce_pages'];
	}

	if ( ( !is_product_category() && !is_product() && !is_front_page() && !is_shop() && !is_cart() && !is_checkout() && !plugify_is_thankyou_page() && !is_account_page() && !in_array( $page_id, $allowed_pages_ids ) ) ||   

		( ( is_home() || is_archive() || is_front_page() ) && !in_array( 'home_page', $chatbot_display_woocommerce_pages) ) ||
		( is_shop() && !in_array( 'shop_page', $chatbot_display_woocommerce_pages ) ) ||
		( is_product_category() && !in_array( 'category_page', $chatbot_display_woocommerce_pages ) ) ||
		( is_product() && !in_array( 'product_page', $chatbot_display_woocommerce_pages ) ) ||
		( is_cart() && !in_array( 'cart_page', $chatbot_display_woocommerce_pages ) ) ||
		( is_checkout() && !in_array( 'checkout_page', $chatbot_display_woocommerce_pages ) ) ||
		( plugify_is_thankyou_page() && !in_array( 'thankyou', $chatbot_display_woocommerce_pages ) ) ||
		( is_account_page() && !in_array( 'myaccount', $chatbot_display_woocommerce_pages ) ) 

	) {
		return;
}

$plugify_chat_historyy = array();

if (is_user_logged_in()) {
	$user_id = get_current_user_id();
} else {
	if (!isset($_SESSION['guest_chat_id'])) {
		$_SESSION['guest_chat_id'] = 'guest_' . bin2hex(random_bytes(8));
	}
	$user_id = filter_var($_SESSION['guest_chat_id']);
}

$existing_post = get_posts([
	'post_type' => 'woo_ai_chat',
		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
	'meta_key' => 'chat_user_id',
		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
	'meta_value' => $user_id,
	'posts_per_page' => 1
]);

if (!empty($existing_post)) {
	$post_id = $existing_post[0]->ID;
	$plugify_chat_historyy = get_post_meta($post_id, 'plugify_chat_historyyy', true);

	$last_index = get_post_meta($post_id, 'plugify_last_chat_cleared_index', true);

}

?>
<div id="woo-ai-chatbot-icon">
	<svg xmlns="http://www.w3.org/2000/svg" style="color:<?php echo esc_attr($plugify_save_chatbot_settings['icon_color']); ?>;" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
		<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
	</svg>
</div>

<div id="woo-ai-chatbot-container" style="display:none;">
	<div id="woo-ai-chatbot-header">
		<button id="woo-ai-chatbot-close" style="width:10%; margin-bottom: 9px; outline: none; color: <?php echo esc_attr($plugify_save_chatbot_settings['bot_header_txt_clr']); ?>;">⌄</button>
		<h3 style="width:80%; color:<?php echo esc_attr($plugify_save_chatbot_settings['bot_header_txt_clr']); ?>"><?php echo esc_html($plugify_save_chatbot_settings['bot_header_text']); ?></h3>

		<button id="woo-ai-chatbot-dot" style="width:10%; outline: none; color: <?php echo esc_attr($plugify_save_chatbot_settings['bot_header_txt_clr']); ?>;">︙</button>

		<div id="woo-ai-chatbot-dot-menu" style="display:none; position:absolute; top:50px; right:15px; background:#fff; border:1px solid #ccc; border-radius:5px; box-shadow: 0 2px 8px rgba(0,0,0,0.2); z-index:10000;">
			<ul style="list-style:none; margin:0; padding:5px 0;">
				<li id="woo-clear-chat" style="padding:8px 15px; cursor:pointer; white-space:nowrap;">Clear Chat</li>
			</ul>
		</div>
		
	</div>
	<div id="woo-ai-chatbot-messages">
		<?php if (is_array($plugify_chat_historyy) && !empty($plugify_chat_historyy)) { ?>
			<?php
			foreach ($plugify_chat_historyy as $keyy => $valuee) {

				if ($keyy <= $last_index) {
					continue;
				}

				$classs = 'user' == $valuee['role'] ? 'user-message' : 'assistant-message';
				?>
				<div class="chat-message <?php echo esc_attr($classs); ?>">
					<div class="chat-text">
						<?php echo filter_var(plugify_formated_message_front($valuee['message'])); ?>
					</div>
					<div class="chat-time">
						<?php echo esc_html(gmdate('d M, Y h:i A', $valuee['time'])); ?>
					</div>
				</div>

				<?php
			}
		}
		?>
	</div>
	<div id="woo-ai-chatbot-input">
		<input type="text" id="woo-ai-chatbot-message" placeholder="Ask about our products..." disabled>
		<button id="woo-ai-chatbot-send" disabled><?php echo esc_html($plugify_save_chatbot_settings['send_btn_txt']); ?></button>
	</div>
</div>


<script type="text/javascript">
	jQuery(document).ready(function($) {

		if (jQuery('.chat-message').length > 0) {
			jQuery('#woo-ai-chatbot-dot').css('visibility', 'visible');
		} else {
			jQuery('#woo-ai-chatbot-dot').css('visibility', 'hidden');

		}

		jQuery('#woo-ai-chatbot-dot').on('click', function(e) {
			e.stopPropagation();
			jQuery('#woo-ai-chatbot-dot-menu').fadeToggle(150);
		});

		jQuery(document).on('click', function(e) {
			if (!jQuery(e.target).closest('#woo-ai-chatbot-dot-menu').length && !jQuery(e.target).is('#woo-ai-chatbot-dot')) {
				jQuery('#woo-ai-chatbot-dot-menu').fadeOut(150);
			}
		});

		jQuery('#woo-clear-chat').on('click', function() {
			// console.log('aaa');
			jQuery('#woo-ai-chatbot-dot-menu').fadeOut(150);

			if (jQuery('.chat-message').length > 0) {

				if (!confirm('You are about to clear the chat history. This action cannot be undone. Do you want to proceed?')) {
					return;
				}

				jQuery('#woo-ai-chatbot-messages').css('filter', 'blur(5px)');
			}

			jQuery.ajax({
				url : '<?php echo filter_var(admin_url('admin-ajax.php')); ?>',
				type : 'post',
				data : {
					action : 'plugify_woo_ai_clear_chat',
				},
				success : function( response ) {
					jQuery('#woo-ai-chatbot-messages').css('filter', 'none');
					jQuery('.chat-message').remove();
					jQuery('#woo-ai-chatbot-dot').css('visibility', 'hidden');

				}
			});
		});


		const caht_icon = jQuery('#woo-ai-chatbot-icon');
		const chat_container = jQuery('#woo-ai-chatbot-container');
		const message_container = jQuery('#woo-ai-chatbot-messages');
		const message_inputt = jQuery('#woo-ai-chatbot-message');
		const send_buttonn = jQuery('#woo-ai-chatbot-send');

		let conversation = [];
		let chat_initialized = false;


		caht_icon.on('click', function() {
			if (!chat_initialized) {
				initChat();
				chat_initialized = true;
			}
			chat_container.slideToggle(300);

			message_container.stop().animate({
				scrollTop: message_container[0].scrollHeight
			}, 100);

		});


		jQuery('#woo-ai-chatbot-close').on('click', function() {
			chat_container.slideUp(300);
		});


		function initChat() {

			message_inputt.prop('disabled', false);
			send_buttonn.prop('disabled', false);

			send_buttonn.on('click', sendMessage);
			message_inputt.on('keypress', function(e) {
				if (e.which === 13) sendMessage();
			});
		}

		function addMessage(role, content) {
			const messageClass = role === 'user' ? 'user-message' : 'assistant-message';
			const safeContent = (content || '').toString(); 

			const html_content = safeContent
			.replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" target="_blank">$1</a>')
			.replace(/\n{2,}/g, '<br><br>')
			.replace(/\n/g, '<br>'); 

			const currentTime = new Date();
			const formattedTime = currentTime.toLocaleString('en-GB', {
				day: '2-digit', month: 'short', year: 'numeric',
				hour: '2-digit', minute: '2-digit', hour12: true
			});

			const messageHTML = `
			<div class="chat-message ${messageClass}">
			<div class="chat-text">${html_content}</div>
			<div class="chat-time">${formattedTime}</div>
			</div>
			`;

			message_container.append(messageHTML);
			message_container.scrollTop(message_container[0].scrollHeight);
		}



		function sendMessage() {
			const message = message_inputt.val().trim();
			if (!message) return;

			addMessage('user', message);
			message_inputt.val('');
			send_buttonn.prop('disabled', true);


			const typingIndicator = jQuery(`
				<div class="typing-indicator" style="display:none;">
				<span></span>
				<span></span>
				<span></span>
				</div>
				`);
			message_container.append(typingIndicator);

			function scrollToBottom() {
				message_container.stop().animate({
					scrollTop: message_container[0].scrollHeight
				}, 200);
			}

			jQuery('#woo-ai-chatbot-dot').css('visibility', 'visible');

			jQuery.ajax({
				url : '<?php echo filter_var(admin_url('admin-ajax.php')); ?>',
				type: 'POST',
				data: {
					action: 'plugify_woo_ai_chat',
					message: message,
					conversation: JSON.stringify(conversation),

				},

				beforeSend: function() {

					typingIndicator.show();
					scrollToBottom();
				},

				success: function(response) {

					// console.log(response)
					if (response.success) {
						conversation = response.data.conversation;
						addMessage('assistant', response.data.response);
					}
				},
				complete: function() {
					typingIndicator.hide();
					send_buttonn.prop('disabled', false);
					message_inputt.focus();
				},
				error: function() {
					typingIndicator.hide();
					addMessage('assistant', "Sorry, I'm having trouble responding. Please try again.");
					send_buttonn.prop('disabled', false);
				}
			});
		}
	});
</script>

<style type="text/css">


	#woo-ai-chatbot-dot-menu li:hover {
		background-color: #f0f0f0;
	}

	.chat-text {
		font-size: 14px;
		/*color: #333;*/
	}
	.chat-time {
		font-size: 11px;
		/*color: #888;*/
		margin-top: 6px;
		text-align: right;
	}

	.assistant-message .chat-time {
		color: <?php echo esc_attr($plugify_save_chatbot_settings['admin_time_color']); ?>;

	}

	.user-message .chat-time {
		color: <?php echo esc_attr($plugify_save_chatbot_settings['user_time_color']); ?>;

	}


	#woo-ai-chatbot-icon {
		position: fixed;
		bottom: 30px;
		right: 30px;
		width: 60px;
		height: 60px;
		background: <?php echo esc_attr($plugify_save_chatbot_settings['icon_bg']); ?>;
		border-radius: 50%;
		display: flex;
		align-items: center;
		justify-content: center;
		cursor: pointer;
		color: black;
		box-shadow: 0 2px 10px rgba(0,0,0,0.2);
		z-index: 9998;
		transition: all 0.3s ease;
	}

	#woo-ai-chatbot-icon:hover {
		transform: scale(1.1);
	}

	#woo-ai-chatbot-icon svg {
		width: 30px;
		height: 30px;
		color: white;
	}


	#woo-ai-chatbot-container {
		width: 380px;
		border: 1px solid #ddd;
		border-radius: 10px;
		overflow: hidden;
		font-family: Arial, sans-serif;
		box-shadow: 0 5px 15px rgba(0,0,0,0.1);
		position: fixed;
		bottom: 100px;
		right: 30px;
		background: white;
		z-index: 9999;
		display: none;
	}

	#woo-ai-chatbot-header {
		background-color: <?php echo esc_attr($plugify_save_chatbot_settings['bot_header_bg']); ?>;
		padding: 15px;
		display: flex;
		justify-content: space-between;
		align-items: center;
	}

	#woo-ai-chatbot-header h3 {
		margin: 0;
		font-size: 16px;
		font-weight: 600;
	}

	#woo-ai-chatbot-close, #woo-ai-chatbot-dot {
		background: none;
		border: none;
		color: white;
		font-size: 24px;
		cursor: pointer;
		line-height: 1;
		padding: 0 5px;
	}

	#woo-ai-chatbot-messages {
		height: 350px;
		overflow-y: auto;
		padding: 15px;
		background: <?php echo esc_attr($plugify_save_chatbot_settings['window_bg']); ?>;
		line-height: 1.5;
	}

	.chat-message {
		margin-bottom: 15px;
		padding: 12px 15px;
		border-radius: 18px;
		max-width: 85%;
		word-wrap: break-word;
		line-height: 1.4;
		animation: fadeIn 0.3s ease;
	}

	@keyframes fadeIn {
		from { opacity: 0; transform: translateY(10px); }
		to { opacity: 1; transform: translateY(0); }
	}

	.user-message {
		background: <?php echo esc_attr($plugify_save_chatbot_settings['user_bg']); ?> !important;
		color: <?php echo esc_attr($plugify_save_chatbot_settings['user_txt_color']); ?> !important;
		margin-left: auto;
		border-bottom-right-radius: 4px;
	}

	.assistant-message {
		background:  <?php echo esc_attr($plugify_save_chatbot_settings['admin_bg']); ?> !important;
		margin-right: auto;
		/*border: 1px solid #eee;*/
		border-bottom-left-radius: 4px;
		color:  <?php echo esc_attr($plugify_save_chatbot_settings['admin_txt_color']); ?> !important;
	}

	.assistant-message a {
		color: #2c3e50;
		text-decoration: underline;
		font-weight: 500;
	}

	.assistant-message a:hover {
		color: #1a252f;
	}

	#woo-ai-chatbot-input {
		display: flex;
		padding: 15px;
		border-top: 1px solid #ddd;
		background: white;
	}

	#woo-ai-chatbot-message {
		flex-grow: 1;
		padding: 12px;
		border: 1px solid #ddd;
		border-radius: 4px;
		font-size: 14px;
	}

	#woo-ai-chatbot-send {
		margin-left: 10px;
		padding: 0 20px;
		background: <?php echo esc_attr($plugify_save_chatbot_settings['send_btn_bg']); ?>;
		color: <?php echo esc_attr($plugify_save_chatbot_settings['send_btn_txt_color']); ?>;
		border: none;
		border-radius: 4px;
		cursor: pointer;
		font-weight: 500;
	}

	#woo-ai-chatbot-send:disabled {
		background: #cccccc;
		cursor: not-allowed;
	}

	@media (max-width: 480px) {
		#woo-ai-chatbot-container {
			width: 90%;
			right: 5%;
			bottom: 80px;
		}

		#woo-ai-chatbot-icon {
			bottom: 20px;
			right: 20px;
		}
	}


	.typing-indicator {
		background: #f1f1f1;
		padding: 2px 7px;
		border-radius: 18px;
		display: inline-block;
		margin-bottom: 15px;
		border: 1px solid #eee;
		border-bottom-left-radius: 4px;
	}

	.typing-indicator span {
		height: 4px;
		width: 4px;
		background: #666;
		border-radius: 50%;
		display: inline-block;
		margin: 0 2px;
		opacity: 0.4;
	}

	.typing-indicator span:nth-child(1) {
		animation: typing 1s infinite;
	}

	.typing-indicator span:nth-child(2) {
		animation: typing 1s infinite 0.2s;
	}

	.typing-indicator span:nth-child(3) {
		animation: typing 1s infinite 0.4s;
	}

	@keyframes typing {
		0% {
			opacity: 0.4;
			transform: translateY(0);
		}
		50% {
			opacity: 1;
			transform: translateY(-3px);
		}
		100% {
			opacity: 0.4;
			transform: translateY(0);
		}
	}
</style>



<?php
}


add_action('woocommerce_thankyou', 'plugify_woo_ai_chatbot_clear_cached_orders', 10, 1);

function plugify_woo_ai_chatbot_clear_cached_orders( $order_id ) {
	if (!$order_id) {
		return;	
	} 
	
	delete_transient('woo_ai_chatbot_cached_orders');
}
