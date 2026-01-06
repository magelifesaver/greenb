<?php

include('plgfy-ai-settings-tab.php');
include('plgfy-ai-products-description.php');
include('plgfy-ai-variations-description.php');
include('plgfy-ai-reviews-reply.php');


add_action('admin_enqueue_scripts', 'plugify_ai_register_swiper_script');

function plugify_ai_register_swiper_script() {

	$screen = get_current_screen();
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotValidated 
	if ( ( $screen && 'post' == $screen->base && 'product' == $screen->post_type ) || ( isset($_GET['page']) && 'wc-settings' == $_GET['page'] && isset($_GET['tab']) && 'plugify_ai_set' == $_GET['tab'] ) ) {

		$nonce = wp_create_nonce('plugify_custom_nonce_for_js');

		wp_enqueue_script( 'select2', plugins_url( 'js/select2.min.js', __FILE__ ), false, '1.0', 'all');
		wp_enqueue_style( 'select2', plugins_url( 'js/select2.min.css', __FILE__ ), false, '1.0', 'all' );

		wp_enqueue_script('jscolor', plugins_url( 'jscolor.min.js', __FILE__ ), false, '1.0', 'all' );
		wp_enqueue_script('jscolor-full', plugins_url( 'jscolor.js', __FILE__ ), false, '1.0', 'all' );

		wp_register_script('plgfy_js', plugins_url('/js/plgfy_pta_custom_scripts.js', __FILE__), [], '1.0.2', true);

		wp_enqueue_script('plgfy_js');

		wp_enqueue_editor();

		$plgfy_custom_dataa = array(
			'admin_url' => admin_url('admin-ajax.php'),
			'nonce' => $nonce,		
		);

		wp_localize_script('plgfy_js', 'plgfy_custom_dataa', $plgfy_custom_dataa);

		?>
		<style type="text/css">
			.woocommerce-save-button {
				display:none !important;
			}
		</style>
		<?php
	}
}


function plugify_ai_cahts_remove_quick_edit_from_cpt($actions, $post) {
	if ($post->post_type === 'woo_ai_chat') {
		unset($actions['inline hide-if-no-js']);
	}

	if (isset($actions['edit'])) {
		$edit_link = get_edit_post_link($post->ID);
		$actions['view'] = '<a href="' . esc_url($edit_link) . '">View</a>';
		unset($actions['edit']);
	}


	return $actions;
}
add_filter('post_row_actions', 'plugify_ai_cahts_remove_quick_edit_from_cpt', 10, 2);



function plugify_ai_chats_remove_cpt_filters_views($views) {
	global $post_type;
	if ($post_type === 'woo_ai_chat') {
		unset($views['mine']);
		unset($views['publish']);
		unset($views['draft']);
		unset($views['trash']);
		// unset($views['all']);
	}
	return $views;
}
add_filter('views_edit-woo_ai_chat', 'plugify_ai_chats_remove_cpt_filters_views');


add_filter('post_updated_messages', 'plugifty_chat_ai_post_type_messages_update');

function plugifty_chat_ai_post_type_messages_update( $messages ) {
	global $post;

	if ('woo_ai_chat' == $post->post_type) {
		$messages['woo_ai_chat'] = array(
			0 => '',
			1 => __('Chat updated.', 'ai-addons'),
			2 => __('Chat updated.', 'ai-addons'),
			3 => __('Chat deleted.', 'ai-addons'),
			4 => __('Chat updated.', 'ai-addons'),
			5 => __('Chat saved.', 'ai-addons'),
			6 => __('Chat saved.', 'ai-addons'),
			7 => __('Chat submitted.', 'ai-addons'),
			8 => __('Chat draft updated.', 'ai-addons'),
		);
	}

	return $messages;
}

add_filter('bulk_post_updated_messages', 'plugify_chat_ai_bulk_messages', 10, 2);

function plugify_chat_ai_bulk_messages($bulk_messages, $bulk_counts) {
	$bulk_messages['woo_ai_chat'] = array(
		/* translators: %s: Number of events updated. */
		'updated'   => _n('%s chat updated.', '%s chats updated.', $bulk_counts['updated'], 'ai-addons'),

		/* translators: %s: Number of events not updated due to being locked. */
		'locked'    => _n('%s chat not updated, someone is editing it.', '%s chats not updated, someone is editing them.', $bulk_counts['locked'], 'ai-addons'),

		/* translators: %s: Number of events permanently deleted. */
		'deleted'   => _n('%s chat permanently deleted.', '%s chats permanently deleted.', $bulk_counts['deleted'], 'ai-addons'),

		/* translators: %s: Number of events moved to trash. */
		'trashed'   => _n('%s chat moved to the Trash.', '%s chats moved to the Trash.', $bulk_counts['trashed'], 'ai-addons'),

		/* translators: %s: Number of events restored from trash. */
		'untrashed' => _n('%s chat restored from the Trash.', '%s chats restored from the Trash.', $bulk_counts['untrashed'], 'ai-addons'),
	);

	return $bulk_messages;
}



add_action('init', 'plugify_register_chat_history_post_type');
function plugify_register_chat_history_post_type() {
	register_post_type('woo_ai_chat',
		array(
			'labels' => array(
				'name'               => _x( 'AI Chats', 'post type general name', 'ai-addons' ),
				'singular_name'      => _x( 'AI Chat', 'post type singular name', 'ai-addons' ),
				'menu_name'          => __( 'AI Chats', 'ai-addons' ),
				'name_admin_bar'     => __( 'AI Chat', 'ai-addons' ),
				'add_new'            => __( 'Add New', 'ai-addons' ),
				// 'add_new_item'       => __( 'Add New Event', 'plugify_em' ),
				'new_item'           => __( 'New Chat', 'ai-addons' ),
				'edit_item'          => __( 'Edit Chat', 'ai-addons' ),
				'view_item'          => __( 'View Chat', 'ai-addons' ),
				'all_items'          => __( 'AI Chats', 'ai-addons' ),
				'search_items'       => __( 'Search Chats', 'ai-addons' ),
				'not_found'          => __( 'No chats found.', 'ai-addons' ),
				'not_found_in_trash' => __( 'No chats found in Trash.', 'ai-addons' )
			),
			'public' => false,
			'show_ui' => true,
			'show_in_menu' => 'woocommerce',
			'supports' => array('title'),
			'capability_type' => 'post',

		)
	);
}

add_action('admin_head', 'plugify_hide_add_new_button_for_cpt');
function plugify_hide_add_new_button_for_cpt() {
	$screen = get_current_screen();
	if (!is_object($screen)) {
		return;	
	} 

	$cpt_slug = 'woo_ai_chat';

	if ('edit-' . $cpt_slug == 	$screen->id) {
		echo '<style>.page-title-action { display: none !important; }</style>';
	}

	if ($screen->id === $cpt_slug) {
		echo '<style>.wrap .page-title-action { display: none !important; }</style>';
	}
}





add_filter('manage_edit-woo_ai_chat_columns', 'plugify_ai_custom_columns');
function plugify_ai_custom_columns( $columns ) {

	unset($columns['date']);

	$columns['chat_started'] = 'Chat started';
	$columns['last_message'] = 'Last message';
	$columns['emailll'] = 'Email Address';
	$columns['chat_statusss'] = 'Chat Status';

	// $columns['date'] = 'Date';

	return $columns;
}


add_filter('manage_edit-woo_ai_chat_sortable_columns', 'plugify_ai_sortable_columns');
function plugify_ai_sortable_columns( $columns ) {
	$columns['chat_started'] = 'chat_started';
	$columns['last_message'] = 'last_message';
	$columns['emailll'] = 'alert_user_emailll';
	$columns['chat_statusss'] = 'chat_statusss'; 
	return $columns;
}



add_action('admin_footer', 'plugify_chat_ai_handel_unread_chat_admin_side');

function plugify_chat_ai_handel_unread_chat_admin_side () {
	$screen = get_current_screen();

	if ( ( $screen && 'woo_ai_chat' == $screen->post_type ) ) {

		$args = array(
			'post_type' => 'woo_ai_chat',
			'posts_per_page' => -1
		);

		$query = new WP_Query($args);

		if ($query->have_posts()) :



			while ($query->have_posts()) : $query->the_post();

				$unread = get_post_meta(get_the_ID(), 'plugify_chat_unread', true);
				if ($unread === 'true') {

					?>
					<script type="text/javascript">
						jQuery(document).ready(function () {
							jQuery('#post-<?php echo filter_var(get_the_ID()); ?>').addClass('plugify-bold');
						});
					</script>
					<style type="text/css">
						.plugify-bold .row-title, .plugify-bold .chat_started, .plugify-bold .last_message, .plugify-bold .emailll, .plugify-bold .chat_statusss {
							font-weight: bold;
						}
					</style>

					<?php	
				} 
			endwhile;
			wp_reset_postdata();
		endif;

	}
}


// add_action('wp', 'plugify_mark_chat_as_read');

// function plugify_mark_chat_as_read() {
// 	if (is_singular('woo_ai_chat')) {
// 		global $post;

// 		if (!empty($post)) {
// 			$unread = get_post_meta($post->ID, 'plugify_chat_unread', true);

// 			if ($unread == 'true') {
// 				update_post_meta($post->ID, 'plugify_chat_unread', 'false');
// 			}
// 		}
// 	}
// }




add_action('manage_woo_ai_chat_posts_custom_column', 'plugifyy_woo_ai_chat_custom_column_data', 10, 2);
function plugifyy_woo_ai_chat_custom_column_data( $column, $post_id ) {
	

	$plugify_chat_historyy = get_post_meta($post_id, 'plugify_chat_historyyy', true);
	$chat_user_id = get_post_meta($post_id, 'chat_user_id', true);

	$count_chat = count($plugify_chat_historyy);
	$user = get_userdata($chat_user_id);

	if ($user) {
		$user_namee = $user->display_name;
		$user_emaill = $user->user_email;
	} else {
		$user_namee = 'Guest User';
		$user_emaill = get_post_meta($post_id, 'alert_user_emailll', true);
	}

	$chat_start_time = gmdate('d M, Y h:i A', $plugify_chat_historyy[0]['time']);
	$last_message_time = gmdate('d M, Y h:i A', $plugify_chat_historyy[$count_chat - 1]['time']);

	$chat_statusss = get_post_meta($post_id, 'chat_statusss', true);


	switch ($column) {

		case 'chat_started':
		echo isset($chat_start_time) ? esc_html($chat_start_time) : '-';
		break;

		case 'last_message':
		echo isset($last_message_time) ? esc_html($last_message_time) : '-';
		break;

		case 'emailll':
		echo isset($user_emaill) ? esc_html($user_emaill) : '-';
		break;


		case 'chat_statusss':
		if ('' != $chat_statusss) {
			echo esc_html($chat_statusss);
		} else {
			echo 'Chatbot has addressed the message.';
		}


		break;

		default:
		break;
	}
}



add_action('add_meta_boxes', 'plugify_ai_create_links_rules_meta_boxes', 10);

function plugify_ai_create_links_rules_meta_boxes() {

	remove_meta_box('postcustom', 'woo_ai_chat', 'normal'); 
	remove_meta_box('postexcerpt', 'woo_ai_chat', 'normal'); 
	remove_meta_box('commentstatusdiv', 'woo_ai_chat', 'normal'); 
	remove_meta_box('commentsdiv', 'woo_ai_chat', 'normal'); 
	remove_meta_box('slugdiv', 'woo_ai_chat', 'normal'); 
	remove_meta_box('submitdiv', 'woo_ai_chat', 'side');

	add_meta_box(
		'woo_ai_chat_metabox',        
		'Chat History', 
		'plugify_ai_chat_history_callback_function_for_metabox', 
		'woo_ai_chat',                  
		'normal',                           
		'high'                                      
	);

	add_meta_box(
		'woo_ai_email_metabox',
		'Send Email to User',
		'plugify_ai_send_email_callback_function_for_metabox',
		'woo_ai_chat',
		'normal',
		'default'
	);

}

function plugify_formated_message_admin( $message ) {

	$message = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2" target="_blank">$1</a>', $message);


	$message = nl2br(trim($message));

	return $message;
}

function plugify_ai_chat_history_callback_function_for_metabox( $post ) {
	$post_idd = $post->ID;

	$plugify_chat_historyy = get_post_meta($post_idd, 'plugify_chat_historyyy', true);
	$chat_user_id = get_post_meta($post_idd, 'chat_user_id', true);
	$count_chat = 0;
	if (is_array($plugify_chat_historyy)) {

		$count_chat = count($plugify_chat_historyy);
	}

	$user = get_userdata($chat_user_id);

	if ($user) {
		$user_namee = $user->display_name;
		$user_emaill = $user->user_email;
	} else {
		$user_namee = 'Guest User';
		$user_emaill = get_post_meta($post_idd, 'alert_user_emailll', true);
	}

	$chat_start_time = '';
	$last_message_time = '';

	if (is_array($plugify_chat_historyy) && !empty($plugify_chat_historyy)) {
		$chat_start_time = gmdate('d M, Y h:i A', $plugify_chat_historyy[0]['time']);
		$last_message_time = gmdate('d M, Y h:i A', $plugify_chat_historyy[$count_chat - 1]['time']);
	}
	?>

	<div class="chat-summary-card">
		<h3>Chat Summary</h3>
		<div class="chat-summary-row"><b>User Name:</b> <p><?php echo esc_html($user_namee); ?></p></div>
		<div class="chat-summary-row"><b>User Email:</b> <p><?php echo esc_html($user_emaill); ?></p></div>
		<div class="chat-summary-row"><b>Chat Starts:</b> <p><?php echo esc_html($chat_start_time); ?></p></div>
		<div class="chat-summary-row"><b>Last Message:</b> <p><?php echo esc_html($last_message_time); ?></p></div>
	</div>

	<hr>

	<div class="chat-summary-card-1" style="max-height: 400px; overflow-y: auto;">
		<h3>Conversation</h3>
		<div class="chat-summary-card">
			<?php if (is_array($plugify_chat_historyy)) { ?>
				<?php
				foreach ($plugify_chat_historyy as $valuee) { 
					$classs =  'user' == $valuee['role'] ? 'user-message' : 'assistant-message';
					?>
					<div class="chat-message <?php echo esc_attr($classs); ?>">
						<div class="chat-header" style="display: flex; justify-content: space-between; align-items: center;">
							<span class="chat-role-badge" style="background-color: #b4cee9; padding: 6px 12px; border-radius: 5px; font-size: 12px;">
								<?php echo esc_html(ucfirst($valuee['role'])); ?>
							</span>
							<small style="color: #888; font-size: 11px;"><?php echo esc_html(gmdate('d M, Y h:i A', $valuee['time'])); ?></small>
						</div>
						<hr>
						<div class="chat-message-content" style="margin-top: 5px;">
							<?php echo filter_var(plugify_formated_message_admin($valuee['message'])); ?>
						</div>
					</div>
				<?php } ?>
			<?php } ?>
		</div>
	</div>

	<style>


		.chat-summary-card-1 h3 {
			position: sticky;
			top: 0;
			background-color: #f9f9f9;
			z-index: 10; 
			padding: 10px 15px;
			margin: 0;
			border-bottom: 1px solid #ddd;
			font-size: 22px;
			color: #333;
		}
		.chat-message {
			margin-bottom: 15px;
			padding: 12px 15px;
			border-radius: 18px;
			max-width: 50%;
			word-wrap: break-word;
			line-height: 1.4;
			animation: fadeIn 0.3s ease;
		}
		.user-message {
			background: #e3f2fd;
			margin-left: auto;
			border-bottom-right-radius: 4px;
		}
		.assistant-message {
			background: #ffffff;
			margin-right: auto;
			border: 1px solid #eee;
			border-bottom-left-radius: 4px;
		}
		.chat-summary-card {
			max-width: 100%;
			margin: 20px auto;
			padding: 20px;
			border-radius: 12px;
			background: #f9f9f9;
			box-shadow: 0 4px 8px rgba(0,0,0,0.1);
			font-family: Arial, sans-serif;
		}
		.chat-summary-card h3 {
			margin-top: 0;
			font-size: 22px;
			color: #333;
		}
		.chat-summary-row {
			margin-bottom: 12px;
		}
		.chat-summary-row b {
			display: inline-block;
			width: 130px;
			color: #555;
		}
		.chat-summary-row p {
			display: inline-block;
			margin: 0;
			font-weight: 500;
			color: #222;
		}
	</style>

	<script type="text/javascript">
		window.onload = function () {
			var message_container = jQuery('.chat-summary-card-1');
			message_container.scrollTop(message_container[0].scrollHeight);
		};
	</script>
	<?php
}

function plugify_ai_send_email_callback_function_for_metabox( $post ) {
	$user_email = get_post_meta($post->ID, 'alert_user_emailll', true);
	if (!$user_email) {
		$chat_user_id = get_post_meta($post->ID, 'chat_user_id', true);
		if ($chat_user_id) {
			$user = get_userdata($chat_user_id);
			$user_email = $user ? $user->user_email : '';
		}
	}


	update_post_meta($post->ID, 'plugify_chat_unread', 'false');


	$default_subject = 'Regarding your recent chat session';
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset($_GET['email_sent']) && 'true' == $_GET['email_sent'] ) {
		echo '<div class="notice notice-success is-dismissible"><p>Email has been sent successfully!</p></div>';
	}
	?>

	<form method="post">
		<?php wp_nonce_field('plugify_send_email_action', 'plugify_send_email_nonce'); ?>
		<input type="hidden" name="plugify_send_email_now" value="yes">
		<input type="hidden" name="post_id" value="<?php echo esc_attr($post->ID); ?>">

		<table class="form-table">
			<tr>
				<th><label for="plugify_email_to">To:</label></th>
				<td><input type="email" name="plugify_email_to" id="plugify_email_to" class="regular-text" style="width:100%;" value="<?php echo esc_attr($user_email); ?>" ></td>
			</tr>
			<tr>
				<th><label for="plugify_email_subject">Subject:</label></th>
				<td><input type="text" name="plugify_email_subject" id="plugify_email_subject" class="regular-text" style="width:100%;" value="<?php echo esc_attr($default_subject); ?>"></td>
			</tr>
			<tr>
				<th><label for="plugify_email_message">Message:</label></th>
				<td>
					<?php
					wp_editor('', 'plugify_email_message', [
						'textarea_name' => 'plugify_email_message',
						'textarea_rows' => 8,
						'media_buttons' => false,
						'teeny' => true,
					]);
					?>
				</td>
			</tr>
		</table>

		<p>
			<input type="submit" class="button button-primary" value="Send Email">
		</p>
	</form>

	<?php
}



add_action('admin_init', 'plugify_handle_email_send');

function plugify_handle_email_send() {
	if (isset( $_POST['plugify_send_email_now'] ) && 'yes' == $_POST['plugify_send_email_now'] && check_admin_referer( 'plugify_send_email_action', 'plugify_send_email_nonce' ) ) {

		if (isset($_POST['plugify_email_to'])) {
			$to = sanitize_email(wp_unslash($_POST['plugify_email_to']));
		}

		if (isset($_POST['plugify_email_subject'])) {			
			$subject = sanitize_text_field(wp_unslash($_POST['plugify_email_subject']));
		}

		if (isset($_POST['plugify_email_message'])) {			
			$message = wp_kses_post(wp_unslash($_POST['plugify_email_message']));
		}

		if (isset($_POST['post_id'])) {			
			$post_id = intval(wp_unslash($_POST['post_id']));
		}

		$headers = ['Content-Type: text/html; charset=UTF-8'];

		$sent = wp_mail($to, $subject, $message, $headers);

		if ($sent) {
			update_post_meta($post_id, 'chat_statusss', 'Admin has responded via email');
		}

		wp_safe_redirect(add_query_arg(['email_sent' => 'true'], get_edit_post_link($post_id, 'url')));
		exit;
	}
}






add_action('admin_footer', function(){
	global $post;
	
	if(get_post_type($post) !== 'product') {
		return;
	}

	?>
	<script>
		jQuery(document).ready(function($){

			const btn = $('<p><button style="width:100%;" type="button" class="button" id="generate-ai-image-btn">Generate AI Image</button></p>');
			$('#postimagediv .inside').after(btn);


			const popupHtml = `
			<div id="ai-image-popup" style="display:none; width:50%; position:fixed; top:50%; left:50%; border-radius:10px; transform:translate(-50%,-50%);
			background:#fff; padding:20px; z-index:9999; border:1px solid #ccc; box-shadow:0 0 10px rgba(0,0,0,0.3);">
			<h3>Generate AI Image</h3>
			<p><b>Image Description:</b><br>
			<textarea id="ai-image-desc" rows="5" style="width:100%;"></textarea>
			<p>
			<button type="button" class="button-primary" id="ai-image-generate">Generate</button>
			<button type="button" class="button" id="ai-image-cancel">Cancel</button>
			</p>
			</div>
			<div id="ai-image-overlay" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.4); z-index:9998;"></div>
			`;
			$('body').append(popupHtml);


			$(document).on('click', '#generate-ai-image-btn', function(){
				$('#ai-image-popup, #ai-image-overlay').show();
			});


			$(document).on('click', '#ai-image-cancel, #ai-image-overlay', function(){
				$('#ai-image-popup, #ai-image-overlay').hide();
			});


			$(document).on('click', '#ai-image-generate', function(){

				let desc = $('#ai-image-desc').val().trim();
				if(desc === ''){
					alert('Please enter image description.');
					return;
				}

				if(!confirm('This will consume many tokens. Continue?')) {
					return;
				}

				$('#ai-image-popup, #ai-image-overlay').hide();
				$('#postimagediv .inside').append('<div class="ai-loader" style="text-align:center;padding:10px;">Generating Image...</div>');

				$('#postimagediv .inside').block({
					message: null,
					overlayCSS: {
						background: '#fff',
						opacity: 0.6
					}
				});

				let data = {
					action: 'plugify_generate_ai_image',
					prompt: desc,
					product_id: <?php echo filter_var($post->ID); ?>
				};

				$.post(ajaxurl, data, function(response){
					$('.ai-loader').remove();
					$('#postimagediv .inside').css('opacity', '1');

					if(response.success){

						$('#postimagediv .inside').find('img').remove();
						<?php // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>
						$('#postimagediv .inside').prepend('<img src="'+response.image_url+'" style="max-width:100%; height:auto;" />');

						$('#postimagediv .inside').unblock();


						if (typeof wp !== 'undefined' && wp.media && wp.media.featuredImage) {
							wp.media.featuredImage.set(response.attachment_id);
						}
					} else {
						alert(response.message);
					}
				}, 'json');
			});
		});
	</script>
	<?php
});




add_action('wp_ajax_plugify_generate_ai_image', function() {
	$plugify_save_ai_settings = get_option('plugify_save_ai_settings');
	$api_key = $plugify_save_ai_settings['openai_apii'] ?? '';
	// phpcs:ignore WordPress.Security.NonceVerification.Missing
	if (isset($_POST['prompt'])) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$prompt = sanitize_text_field(wp_unslash($_POST['prompt']));
	}
	// phpcs:ignore WordPress.Security.NonceVerification.Missing
	if (isset($_POST['product_id'])) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$product_id = intval($_POST['product_id']);
	}

	$url = "https://api.openai.com/v1/images/generations";

	$data = [
		"model" => "gpt-image-1",
		"prompt" => $prompt,
		"size" => "1024x1024"
	];

	$response = wp_remote_post($url, [
		'headers' => [
			'Content-Type'  => 'application/json',
			'Authorization' => 'Bearer ' . $api_key
		],
		'body'    => wp_json_encode($data),
		'timeout' => 120
	]);

	if (is_wp_error($response)) {
		wp_send_json([
			'success' => false,
			'message' => 'Request error: ' . $response->get_error_message()
		]);
	}

	$body = wp_remote_retrieve_body($response);
	$result = json_decode($body, true);

	if (isset($result['data'][0]['b64_json'])) {
		$image_data = base64_decode($result['data'][0]['b64_json']);
		$upload_dir = wp_upload_dir();
		$filename = 'ai_image_' . time() . '.png';
		$file_path = $upload_dir['path'] . '/' . $filename;

		file_put_contents($file_path, $image_data);

		$attachment = [
			'post_mime_type' => 'image/png',
			'post_title' => sanitize_file_name($filename),
			'post_content' => '',
			'post_status' => 'inherit'
		];

		$attach_id = wp_insert_attachment($attachment, $file_path, $product_id);
		require_once(ABSPATH . 'wp-admin/includes/image.php');
		$attach_data = wp_generate_attachment_metadata($attach_id, $file_path);
		wp_update_attachment_metadata($attach_id, $attach_data);

		set_post_thumbnail($product_id, $attach_id);

		wp_send_json([
			'success' => true,
			'message' => 'AI Image Generated & Set Successfully!',
			'attachment_id' => $attach_id,
			'image_url' => wp_get_attachment_image_url($attach_id, 'thumbnail')
		]);
	} else {
		wp_send_json([
			'success' => false,
			'message' => 'Error generating image: ' . json_encode($result)
		]);
	}
});




add_action('admin_footer', function(){
	global $post;
	if(get_post_type($post) !== 'product') return;
	?>
	<script>
		jQuery(document).ready(function($){

			function addVariationButtons(){
				$('.woocommerce_variation').each(function(){
					let $variation = $(this);
					if($variation.find('.generate-ai-variation-btn').length === 0){
						let btn = $('<p><button type="button" class="button generate-ai-variation-btn" style="width:100%;">Generate AI Image</button></p>');
						$variation.find('.upload_image_button').parent().append(btn);
					}
				});
			}


			addVariationButtons();
			$(document).on('woocommerce_variations_loaded', function(){
				addVariationButtons();
			});


			if($('#ai-variation-popup').length === 0){
				const popupHtml = `
				<div id="ai-variation-popup" style="display:none; width:50%; position:fixed; top:50%; left:50%; transform:translate(-50%,-50%);
				background:#fff; padding:20px; z-index:9999; border:1px solid #ccc; box-shadow:0 0 10px rgba(0,0,0,0.3);">
				<h3>Generate AI Image for Variation</h3>
				<p><b>Image Description:</b><br>
				<textarea id="ai-variation-desc" rows="5" style="width:100%;"></textarea>
				<p>
				<button type="button" class="button-primary" id="ai-variation-generate">Generate</button>
				<button type="button" class="button" id="ai-variation-cancel">Cancel</button>
				</p>
				</div>
				<div id="ai-variation-overlay" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.4); z-index:9998;"></div>
				`;
				$('body').append(popupHtml);
			}

			let currentVariationRow = null;


			$(document).on('click', '.generate-ai-variation-btn', function(){
				currentVariationRow = $(this).closest('.woocommerce_variation');
				$('#ai-variation-popup, #ai-variation-overlay').show();
			});


			$(document).on('click', '#ai-variation-cancel, #ai-variation-overlay', function(){
				$('#ai-variation-popup, #ai-variation-overlay').hide();
			});


			$(document).on('click', '#ai-variation-generate', function(){
				let desc = $('#ai-variation-desc').val().trim();
				if(desc === ''){
					alert('Please enter image description.');
					return;
				}

				if(!confirm('This will consume many tokens. Continue?')) {
					return;
				}

				$('#ai-variation-popup, #ai-variation-overlay').hide();

				$('.form-flex-box .upload_image').block({
					message: null,
					overlayCSS: {
						background: '#fff',
						opacity: 0.6
					}
				});


				let imageBox = currentVariationRow.find('.upload_image_button').parent();
				imageBox.css('opacity', '0.5').append('<div class="ai-loader" style="text-align:center;padding:10px;">Generating Image...</div>');

				// let variation_id = currentVariationRow.find('input.variation_id').val();
				let variation_id = currentVariationRow.find('.upload_image_button').attr('rel');
				
				let data = {
					action: 'plugify_generate_ai_variation_image',
					prompt: desc,
					variation_id: variation_id
				};

				$.post(ajaxurl, data, function(response){
					imageBox.find('.ai-loader').remove();
					imageBox.css('opacity', '1');


					$('.form-flex-box .upload_image').unblock();

					if(response.success){
						<?php // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>
						let imgHtml = '<img src="'+response.image_url+'" style="max-width:100%; height:auto;" />';
						currentVariationRow.find('img').remove();
						currentVariationRow.find('.upload_image_button').before(imgHtml);

						currentVariationRow.find('.upload_image_button').hide();
						currentVariationRow.find('.remove_image_button').show();


						currentVariationRow.find('input.variation_image_id').val(response.attachment_id).trigger('change');


						$(document.body).trigger('woocommerce_variations_save_variations');
					} else {
						alert(response.message);
					}

				}, 'json');
			});

		});
	</script>
	<?php
});



add_action('wp_ajax_plugify_generate_ai_variation_image', function() {

	$plugify_save_ai_settings = get_option('plugify_save_ai_settings');
	$api_key = $plugify_save_ai_settings['openai_apii'] ?? '';
	// phpcs:ignore WordPress.Security.NonceVerification.Missing
	if (isset($_POST['prompt'])) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$prompt = sanitize_text_field(wp_unslash($_POST['prompt']));
	}
	// phpcs:ignore WordPress.Security.NonceVerification.Missing
	if (isset($_POST['variation_id'])) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing	
		$variation_id = intval($_POST['variation_id']);
	}
	$url = "https://api.openai.com/v1/images/generations";

	$data = [
		"model" => "gpt-image-1",
		"prompt" => $prompt,
		"size" => "1024x1024"
	];

	$response = wp_remote_post($url, [
		'headers' => [
			'Content-Type'  => 'application/json',
			'Authorization' => 'Bearer ' . $api_key
		],
		'body'    => wp_json_encode($data),
		'timeout' => 180
	]);

	if (is_wp_error($response)) {
		wp_send_json([
			'success' => false,
			'message' => 'Request error: ' . $response->get_error_message()
		]);
	}

	$body = wp_remote_retrieve_body($response);
	$result = json_decode($body, true);

	if (isset($result['data'][0]['b64_json'])) {
		$image_data = base64_decode($result['data'][0]['b64_json']);
		$upload_dir = wp_upload_dir();
		$filename = 'ai_variation_' . time() . '.png';
		$file_path = $upload_dir['path'] . '/' . $filename;

		file_put_contents($file_path, $image_data);

		$attachment = [
			'post_mime_type' => 'image/png',
			'post_title' => sanitize_file_name($filename),
			'post_content' => '',
			'post_status' => 'inherit'
		];

		$attach_id = wp_insert_attachment($attachment, $file_path, $variation_id);
		require_once(ABSPATH . 'wp-admin/includes/image.php');
		$attach_data = wp_generate_attachment_metadata($attach_id, $file_path);
		wp_update_attachment_metadata($attach_id, $attach_data);

		update_post_meta($variation_id, '_thumbnail_id', $attach_id);

		wp_send_json([
			'success' => true,
			'message' => 'AI Variation Image Generated & Set Successfully!',
			'attachment_id' => $attach_id,
			'image_url' => wp_get_attachment_image_url($attach_id, 'thumbnail')
		]);
	} else {
		wp_send_json([
			'success' => false,
			'message' => 'Error generating image: ' . json_encode($result)
		]);
	}
});
