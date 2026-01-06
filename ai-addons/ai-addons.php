<?php
/**
 * Plugin Name: AI Addons
 * Plugin URI: https://woocommerce.com/products/ai-addons/
 * Author: Plugify
 * Author URI: https://woocommerce.com/vendor/plugify/
 * Version: 1.0.1
 * Description: Integrate Artificial Intelligence (AI) to your store for instant support, auto-generating product images, descriptions, and review replies.
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Developed By: Plugify
 * Requires at least: 4.4
 * Requires PHP: 4.4
 * Tested up to: 6.8
 * Text Domain: ai-addons
 * WC requires at least: 3.0
 * WC tested up to: 9.*.*
 * Woo: 18734005668393:6bb53190974c0b9b916e9f72118a1ea7

 */ 
if ( ! defined( 'ABSPATH' ) ) { 
	exit; // Exit if accessed directly
}

define( 'QCG_AI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

/**
 * Check if WooCommerce is active
 * if wooCommerce is not active this plugin will not work.
 **/
if (!is_multisite()) {
	if ( !in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

		function plugify_ai_admin_notice() {        
			deactivate_plugins(__FILE__);
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( isset( $_GET['activate'] ) ) {
				unset( $_GET['activate'] );
			}
			?>
			<div id="message" class="error">
				<p>AI Addons requires <a href="https://wordpress.org/extend/plugins/woocommerce/">WooCommerce</a> plugin to be installed and active!</p> 
			</div>
			<?php
		}
		add_action( 'admin_notices', 'plugify_ai_admin_notice' );
	}
}
// phpcs:ignore WordPress.PHP.DevelopmentFunctions.prevent_path_disclosure_error_reporting
// error_reporting(0);

// * ====================================================
// * Woocommerce HPOS 
// * ====================================================

use Automattic\WooCommerce\Utilities\OrderUtil;
add_action( 'before_woocommerce_init', 'plugify_ai_hpos_compatibility');
function plugify_ai_hpos_compatibility() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}

	if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {			
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
	}
}

// * ====================================================
// * Calling Admin-end and Front-end Files
// * ====================================================

if (is_admin()) {
	include('admin/plgfy-ai-main-admin.php');
} else {
	include('front/plgfy-ai-main-front.php');
}


add_action('wp_ajax_plugify_generate_ai_description', 'plugify_generate_ai_description');

function plugify_generate_ai_description() {

	if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'plugify_custom_nonce_for_js')) {
		return;
	}

	$prod_name    = isset($_POST['prod_name']) ? sanitize_text_field(wp_unslash($_POST['prod_name'])) : '';
	$key_features = isset($_POST['key_features']) ? sanitize_textarea_field(wp_unslash($_POST['key_features'])) : '';
	$max_limit    = isset($_POST['max_limit']) ? intval($_POST['max_limit']) : 200;
	$limit_type   = isset($_POST['limit_type']) ? sanitize_text_field(wp_unslash($_POST['limit_type'])) : 'tokens';

	$plugify_save_ai_settings = get_option('plugify_save_ai_settings');
	$api_key       = $plugify_save_ai_settings['openai_apii'];
	$openai_modell = $plugify_save_ai_settings['openai_modell'];
	$url           = 'https://api.openai.com/v1/chat/completions';

	$user_prompt = "Write a complete product description for \"$prod_name\" with the following key features: $key_features.";

	if ('characters' == $limit_type) {
		$user_prompt .= " The description must not exceed {$max_limit} characters.";
	} else if ('words' == $limit_type) {
		$user_prompt .= " The description must not exceed {$max_limit} words.";
	} else if ('tokens' == $limit_type) {
		$user_prompt .= " The description must stay within {$max_limit} tokens.";
	}

	$data = [
		'model' => $openai_modell,
		'messages' => [
			['role' => 'system', 'content' => 'You are an expert product copywriter. Write engaging and high-converting product descriptions. Ensure the response ends with a full sentence and does not cut off mid-sentence.'],
			['role' => 'user', 'content' => $user_prompt]
		],
		'temperature' => 0.4
	];

	if ($limit_type === 'tokens') {
		$data['max_tokens'] = $max_limit;
	}

	$response = wp_remote_post($url, [
		'headers' => [
			'Content-Type'  => 'application/json',
			'Authorization' => 'Bearer ' . $api_key,
		],
		'body' => json_encode($data),
		'timeout' => 20,
	]);

	if (is_wp_error($response)) {
		wp_send_json_error(['message' => 'API request failed.']);
	} else {
		$body = json_decode(wp_remote_retrieve_body($response), true);

		if (isset($body['choices'][0]['message']['content'])) {
			$generated_description = trim($body['choices'][0]['message']['content']);

			if ($limit_type === 'characters') {
				$generated_description = mb_substr($generated_description, 0, $max_limit);
			} elseif ($limit_type === 'words') {
				$words = preg_split('/\s+/', $generated_description);
				if (count($words) > $max_limit) {
					$generated_description = implode(' ', array_slice($words, 0, $max_limit));
				}
			}

			wp_send_json_success([
				'description' => $generated_description
			]);
		} else {
			wp_send_json_error([
				'message' => 'Invalid response from API.',
				'raw_response' => $body
			]);
		}
	}

	wp_die();
}


add_action('wp_ajax_plugify_enhance_ai_description', 'plugify_enhance_ai_description');

function plugify_enhance_ai_description() {

	if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'plugify_custom_nonce_for_js')) {
		return;
	}


	if (isset($_POST['current_description'])) {
		$current_description = sanitize_textarea_field(wp_unslash($_POST['current_description']));
	} else {
		wp_send_json_error(['message' => 'No description provided.']);
		wp_die();
	}

	$plugify_save_tokens_settings = get_option('plugify_save_tokens_settings');
	$max_enhance_tokens = $plugify_save_tokens_settings['max_enhance_tokens'];

	$plugify_save_ai_settings = get_option('plugify_save_ai_settings');
	$openai_apii = $plugify_save_ai_settings['openai_apii'];
	$openai_modell = $plugify_save_ai_settings['openai_modell'];


	$api_key = $openai_apii;
	$url = 'https://api.openai.com/v1/chat/completions';

	$data = [
		'model' => $openai_modell,
		'messages' => [
			['role' => 'system', 'content' => 'You are an expert product copywriter. Enhance product descriptions to make them more engaging, polished, and persuasive.'],
			[
				'role' => 'user',
				'content' => "Enhance the following product description to make it more engaging, persuasive, and polished:\n\n\"$current_description\"\n\nMake sure to:\n- Use proper line breaks and spacing.\n- Stay within approximately $max_enhance_tokens tokens.\n- Finish with a complete sentence and do not cut off mid-sentence.\n- If necessary, shorten or simplify to fit the token limit."
			]

		],
		'max_tokens' => intval($max_enhance_tokens),
		'temperature' => 0.4
	];

	$response = wp_remote_post($url, [
		'headers' => [
			'Content-Type'  => 'application/json',
			'Authorization' => 'Bearer ' . $api_key,
		],
		'body' => json_encode($data),
		'timeout' => 20,
	]);

	if (is_wp_error($response)) {
		$error_message = $response->get_error_message();
		wp_send_json_error(['message' => 'API request failed: ' . $error_message]);
	} else {
		$body = json_decode(wp_remote_retrieve_body($response), true);

		if (isset($body['choices'][0]['message']['content'])) {
			$enhanced_description = $body['choices'][0]['message']['content'];
			wp_send_json_success([
				'description' => $enhanced_description
			]);
		} else {
			wp_send_json_error([
				'message' => 'Invalid response from API. Please check your API key and internet connection.',
				'raw_response' => $body
			]);
		}
	}

	wp_die();
}




add_action('wp_ajax_plugify_generate_ai_short_description', 'plugify_generate_ai_short_description');

function plugify_generate_ai_short_description() {

	if (
		!isset($_POST['nonce']) ||
		!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'plugify_custom_nonce_for_js')
	) {
		wp_send_json_error(['message' => 'Security check failed.']);
		wp_die();
	}

	if (empty($_POST['current_description'])) {
		wp_send_json_error(['message' => 'No description provided.']);
		wp_die();
	}
	if (empty($_POST['short_limit_type']) || empty($_POST['short_max_limit'])) {
		wp_send_json_error(['message' => 'Limit type and max limit are required.']);
		wp_die();
	}

	$current_description = sanitize_textarea_field(wp_unslash($_POST['current_description']));
	$short_limit_type    = sanitize_text_field(wp_unslash($_POST['short_limit_type']));
	$short_max_limit     = intval($_POST['short_max_limit']);

	$plugify_save_ai_settings = get_option('plugify_save_ai_settings');
	$openai_apii   = $plugify_save_ai_settings['openai_apii'] ?? '';
	$openai_modell = $plugify_save_ai_settings['openai_modell'] ?? '';

	if (empty($openai_apii) || empty($openai_modell)) {
		wp_send_json_error(['message' => 'AI API settings are missing.']);
		wp_die();
	}

	$api_key = $openai_apii;
	$url     = 'https://api.openai.com/v1/chat/completions';

	$prompt = "Summarize the following WooCommerce product description:\n\n\"$current_description\"\n\nGuidelines:\n";
	if ($short_limit_type === 'characters') {
		$prompt .= "- Stay within approximately $short_max_limit characters.\n";
	} else if ($short_limit_type === 'words') {
		$prompt .= "- Stay within approximately $short_max_limit words.\n";
	} else {
		$prompt .= "- Stay within approximately $short_max_limit tokens.\n";
	}
	$prompt .= "- Ensure the summary is complete and ends with a full sentence.\n- Do not cut off mid-sentence.\n- If needed, simplify to fit the limit.";

	$data = [
		'model'       => $openai_modell,
		'messages'    => [
			['role' => 'system', 'content' => 'You are an expert product copywriter. Summarize product descriptions in a concise, engaging, and clear way, ideal for short product summaries.'],
			['role' => 'user', 'content' => $prompt]
		],
		'temperature' => 0.4
	];

	$response = wp_remote_post($url, [
		'headers' => [
			'Content-Type'  => 'application/json',
			'Authorization' => 'Bearer ' . $api_key,
		],
		'body'    => json_encode($data),
		'timeout' => 20,
	]);

	if (is_wp_error($response)) {
		wp_send_json_error(['message' => 'API request failed: ' . $response->get_error_message()]);
	} else {
		$body = json_decode(wp_remote_retrieve_body($response), true);

		if (isset($body['choices'][0]['message']['content'])) {
			$short_description = trim($body['choices'][0]['message']['content']);
			wp_send_json_success([
				'description' => $short_description
			]);
		} else {
			wp_send_json_error([
				'message'      => 'Invalid response from API.',
				'raw_response' => $body
			]);
		}
	}

	wp_die();
}



add_action('wp_ajax_plugify_generate_ai_reply_to_review', 'plugify_generate_ai_reply_to_review');

function plugify_generate_ai_reply_to_review() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if (!isset($_REQUEST['comment_id'])) {
		wp_send_json_error(['message' => 'No comment ID provided.']);
		wp_die();
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$comment_id = intval($_REQUEST['comment_id']);
	$comment = get_comment($comment_id);

	if (!$comment) {
		wp_send_json_error(['message' => 'Invalid comment ID.']);
		wp_die();
	}

	$plugify_save_tokens_settings = get_option('plugify_save_tokens_settings');
	$max_reply_tokens = $plugify_save_tokens_settings['max_reply_tokens'];

	$author = sanitize_text_field($comment->comment_author);
	$review_content = sanitize_textarea_field($comment->comment_content);
	$rating = sanitize_text_field(get_comment_meta($comment_id, 'rating', true));


	$plugify_save_ai_settings = get_option('plugify_save_ai_settings');
	$openai_apii = $plugify_save_ai_settings['openai_apii'];
	$openai_modell = $plugify_save_ai_settings['openai_modell'];

	$api_key = $openai_apii;
	$url = 'https://api.openai.com/v1/chat/completions';

	$data = [
		'model' => $openai_modell,
		'messages' => [
			['role' => 'system', 'content' => 'You are a professional and friendly customer support representative. Write concise, polite, and helpful replies that fit fully within the given token limit. Always end the reply with a complete sentence. Avoid trailing or incomplete thoughts.'],

			['role' => 'user', 'content' => "Review by \"$author\" (Rating: $rating/5): \"$review_content\"\n\nGenerate a short, professional reply within $max_reply_tokens tokens. The response must be complete and end with a full sentence."]

		],
		'max_tokens' => intval($max_reply_tokens),
		'temperature' => 0.6
	];

	$response = wp_remote_post($url, [
		'headers' => [
			'Content-Type'  => 'application/json',
			'Authorization' => 'Bearer ' . $api_key,
		],
		'body' => json_encode($data),
		'timeout' => 20,
	]);

	if (is_wp_error($response)) {
		$error_message = $response->get_error_message();
		wp_send_json_error(['message' => 'API request failed: ' . $error_message]);
	} else {
		$body = json_decode(wp_remote_retrieve_body($response), true);

		if (isset($body['choices'][0]['message']['content'])) {
			$reply = $body['choices'][0]['message']['content'];
			wp_send_json_success([
				'reply' => $reply
			]);
		} else {
			wp_send_json_error([
				'message' => 'Invalid response from API. Please check your API key and internet connection.',
				'raw_response' => $body
			]);
		}
	}

	wp_die();
}




add_action('wp_ajax_plugify_woo_ai_clear_chat', 'plugify_woo_ai_clear_chatbot_handle_chat');
add_action('wp_ajax_nopriv_plugify_woo_ai_clear_chat', 'plugify_woo_ai_clear_chatbot_handle_chat');

function plugify_woo_ai_clear_chatbot_handle_chat() {

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
		$last_index = count($plugify_chat_historyy) - 1;
		update_post_meta($post_id, 'plugify_last_chat_cleared_index', $last_index);
	}
}


add_action('wp_ajax_plugify_woo_ai_chat', 'plugify_woo_ai_chatbot_handle_chat');
add_action('wp_ajax_nopriv_plugify_woo_ai_chat', 'plugify_woo_ai_chatbot_handle_chat');

function plugify_woo_ai_chatbot_handle_chat() {
	session_start();
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if (isset($_REQUEST['message'])) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$message = sanitize_text_field(wp_unslash($_REQUEST['message']));
	}
	
	// $conversation_raw = isset($_REQUEST['conversation']) ? wp_unslash($_REQUEST['conversation']) : '';
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if (isset($_REQUEST['conversation'])) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$conversation_raw = map_deep( wp_unslash( $_REQUEST['conversation']), 'sanitize_text_field');
	}

	$conversation = !empty($conversation_raw) ? json_decode($conversation_raw, true) : [];

	$plugify_save_chatbot_settings = get_option('plugify_save_chatbot_settings');

	if (empty($message)) {
		wp_send_json_error('Empty message');
	}

	preg_match('/[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}/i', $message, $matches);
	if (filter_var($message, FILTER_VALIDATE_EMAIL) || !empty($matches)) {
		$email_found = filter_var($message, FILTER_VALIDATE_EMAIL) ?: $matches[0];

		$conversation[] = array('role' => 'user', 'content' => $message);
		$conversation[] = array('role' => 'assistant', 'content' => $plugify_save_chatbot_settings['after_mail_msg']);

		if (is_user_logged_in()) {
			$user_id = get_current_user_id();
		} else {
			if (!isset($_SESSION['guest_chat_id'])) {
				$_SESSION['guest_chat_id'] = 'guest_' . bin2hex(random_bytes(8));
			}
			$user_id = filter_var($_SESSION['guest_chat_id']);
		}

		$post_id = plugify_ai_save_chat_history($user_id, $conversation);
		update_post_meta($post_id, 'alert_user_emailll', $email_found);
		update_post_meta($post_id, 'chat_statusss', 'Awaiting a response from support team.');

		wp_send_json_success(array(
			'response' => $plugify_save_chatbot_settings['after_mail_msg'],
			'conversation' => $conversation
		));
	}
    //plugify_important
	$message_type = plugify_woo_ai_determine_message_type($message);

	if ('product' == $message_type) {
		$products_info = plugify_woo_ai_chatbot_get_all_products();
		$ai_response = plugify_woo_ai_chatbot_generate_product_response($message, $products_info, $conversation);
	} elseif ('order' == $message_type) {
		$ai_response = plugify_woo_ai_chatbot_generate_order_response($message, $conversation);
	} else {
		$ai_response = plugify_woo_ai_chatbot_generate_general_response($message, $conversation);
	}

	$conversation[] = array('role' => 'user', 'content' => $message);
	$conversation[] = array('role' => 'assistant', 'content' => $ai_response);

	if (is_user_logged_in()) {
		$user_id = get_current_user_id();
	} else {
		if (!isset($_SESSION['guest_chat_id'])) {
			$_SESSION['guest_chat_id'] = 'guest_' . bin2hex(random_bytes(8));
		}
		$user_id = filter_var($_SESSION['guest_chat_id']);
	}

	plugify_ai_save_chat_history($user_id, $conversation);

	wp_send_json_success(array(
		'response' => $ai_response,
		'conversation' => $conversation
	));
}


// add_action('wp_footer', 'lol');
// function lol () {
// 	echo plugify_woo_ai_determine_message_type('sdadsa');


// }
function plugify_woo_ai_determine_message_type($message) {
	$plugify_save_ai_settings = get_option('plugify_save_ai_settings');
	$openai_key = $plugify_save_ai_settings['openai_apii'];

	if (empty($openai_key)) {
		return 'general';
	}

	$response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
		'headers' => array(
			'Content-Type' => 'application/json',
			'Authorization' => 'Bearer ' . $openai_key
		),
		'body' => json_encode(array(
			'model' => 'gpt-3.5-turbo',
			'messages' => array(
				array(
					'role' => 'system',
					'content' => "Analyze the user's message and determine if it's primarily about: 
					1) 'product' - if asking about products, inventory, prices, features, etc.
					2) 'order' - if asking about order status, shipping, returns, or order history
					3) 'general' - for all other inquiries
					Respond ONLY with one of these three words: 'product', 'order', or 'general'."
				),
				array(
					'role' => 'user',
					'content' => $message
				)
			),
			'temperature' => 0.1,
			'max_tokens' => 10
		))
	));

	if (is_wp_error($response)) {
		return 'general';
	}

	$body = json_decode($response['body'], true);
	$message_type = strtolower(trim($body['choices'][0]['message']['content']));

	if (in_array($message_type, ['product', 'order', 'general'])) {
		return $message_type;
	}

	return 'general';
}

function plugify_woo_ai_chatbot_generate_product_response($message, $products_info, $conversation) {
	$plugify_save_ai_settings = get_option('plugify_save_ai_settings');
	$openai_key = $plugify_save_ai_settings['openai_apii'];
	$plugify_save_chatbot_settings = get_option('plugify_save_chatbot_settings');

	$products_text = "Current product information:\n\n";
	foreach ($products_info as $product) {
		$products_text .= sprintf(
			"Product: [%s](%s) (ID: %d)\nType: %s\nDescription: %s\nPrice: %s\nStock: %s (%s)\nSKU: %s\nCategories: %s\n",
			$product['name'],
			$product['url'],
			$product['id'],
			$product['type'],
			$product['description'],
			"Price: {$product['currency']}{$product['price']}\n",
			isset($product['stock']) ? $product['stock'] : 'N/A',
			$product['stock_status'],
			isset($product['sku']) ? $product['sku'] : 'N/A',
			implode(', ', $product['categories'])
		);

		if ('variable' == $product['type'] && !empty($product['attributes'])) {
			$products_text .= "Available Attributes:\n";
			foreach ($product['attributes'] as $attr_name => $attribute) {
				$products_text .= sprintf(
					"- %s: %s\n",
					$attribute['name'],
					implode(', ', $attribute['options'])
				);
			}
		}

		if (!empty($product['variations'])) {
			$products_text .= "Available Variations:\n";
			foreach ($product['variations'] as $variation) {
				$attr_text = array();
				foreach ($variation['attributes'] as $attr_name => $attr_data) {
					$attr_text[] = sprintf('%s: %s', $attr_data['name'], $attr_data['value']);
				}
				$products_text .= sprintf(
					"- [%s](%s) (Variation ID: %d)\n  Price: %s\n  Stock: %s (%s)\n  SKU: %s\n",
					implode(', ', $attr_text),
					$variation['url'],
					$variation['variation_id'],
					"Price: {$product['currency']}{$product['price']}\n",
					isset($variation['stock']) ? $variation['stock'] : 'N/A',
					$variation['stock_status'],
					isset($variation['sku']) ? $variation['sku'] : 'N/A'
				);
			}
		}
		$products_text .= "\n";
	}



	if (isset($plugify_save_chatbot_settings['store_details'])) {

		$store_details = $plugify_save_chatbot_settings['store_details'];

		$store_details = strtr($store_details, array(
			'{admin_email}'     => get_option('admin_email'),
			'{shop_page_url}'   => get_permalink(wc_get_page_id('shop')),
			'{home_page_url}'   => home_url(),
			'{my_account_url}'  => get_permalink(get_option('woocommerce_myaccount_page_id')),
		));

	} else {
		$store_details = '';
	}


	$system_message = "You are a helpful product support chatbot. You have COMPLETE knowledge of all products in the store.\n\n";
	$system_message .= "Store Details:\n" . $store_details . "\n\n";

	$system_message .= $products_text . "\n\n";
	$system_message .= "Important Rules:\n";
	$system_message .= "- Focus only on product information\n";
	$system_message .= "- If asked about orders, politely direct them to ask about orders separately\n";
	$system_message .= "- Before asking for the customer's email, review the available store details and try to answer using that information if possible.\n";

	$system_message .= "- If unsure, say: '" . stripcslashes($plugify_save_chatbot_settings['bot_email_request_message']) . "'\n";

	$messages = array(
		array('role' => 'system', 'content' => $system_message)
	);

	foreach ($conversation as $msg) {
		$messages[] = $msg;
	}

	$messages[] = array('role' => 'user', 'content' => $message);

	$response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
		'headers' => array(
			'Content-Type' => 'application/json',
			'Authorization' => 'Bearer ' . $openai_key
		),
		'body' => json_encode(array(
			'model' => $plugify_save_ai_settings['openai_modell'],
			'messages' => $messages,
			'max_tokens' => intval($plugify_save_chatbot_settings['max_bot_reply']),
			'temperature' => 0.7
		))
	));

	if (is_wp_error($response)) {
		return stripcslashes($plugify_save_chatbot_settings['technical_issue']);
	}

	$body = json_decode($response['body'], true);
	return $body['choices'][0]['message']['content'];
}

function plugify_woo_ai_chatbot_generate_order_response($message, $conversation) {
	$plugify_save_ai_settings = get_option('plugify_save_ai_settings');
	$plugify_save_chatbot_settings = get_option('plugify_save_chatbot_settings');

	$orders_text = '';
	if (is_user_logged_in()) {


		$woo_ai_chatbot_cached_orders = get_transient('woo_ai_chatbot_cached_orders');
		if ($woo_ai_chatbot_cached_orders !== false) {
			$customer_orders = $woo_ai_chatbot_cached_orders;
		}

		if (empty($customer_orders)) {

			$customer_orders = wc_get_orders(array(
				'customer_id' => get_current_user_id(),
				'limit' => -1,
			));
		}

		set_transient('woo_ai_chatbot_cached_orders', $customer_orders, HOUR_IN_SECONDS);			

		if (!empty($customer_orders)) {
			$orders_text = "\n\nCustomer Order History:\n";
			foreach ($customer_orders as $order) {
				$order_date = $order->get_date_created()->date('Y-m-d H:i:s');
				$order_status = $order->get_status();
				$order_total = $order->get_total();
				$order_currency = $order->get_currency();
				$order_id = $order->get_id();

				$orders_text .= sprintf(
					"Order #%s - Date: %s - Status: %s - Total: %s%s\n",
					$order_id,
					$order_date,
					$order_status,
					$order_currency,
					$order_total
				);

				$items = $order->get_items();
				foreach ($items as $item) {
					$product = $item->get_product();
					$orders_text .= sprintf(
						"- %s × %s (Product ID: %s) - %s%s each\n",
						$item->get_quantity(),
						$item->get_name(),
						$item->get_product_id(),
						$order_currency,
						$product ? $product->get_price() : 'N/A'
					);
				}

				$shipping_method = $order->get_shipping_method();
				$shipping_address = $order->get_formatted_shipping_address();
				if (!empty($shipping_method) || !empty($shipping_address)) {
					$orders_text .= "Shipping:\n";
					if (!empty($shipping_method)) {
						$orders_text .= "- Method: $shipping_method\n";
					}
					if (!empty($shipping_address)) {
						$orders_text .= "- Address: $shipping_address\n";
					}
				}
				$orders_text .= "\n";
			}
		}
	}


	if (isset($plugify_save_chatbot_settings['store_details'])) {

		$store_details = $plugify_save_chatbot_settings['store_details'];

		$store_details = strtr($store_details, array(
			'{admin_email}'     => get_option('admin_email'),
			'{shop_page_url}'   => get_permalink(wc_get_page_id('shop')),
			'{home_page_url}'   => home_url(),
			'{my_account_url}'  => get_permalink(get_option('woocommerce_myaccount_page_id')),
		));

	} else {
		$store_details = '';
	}



	$system_message = "You are a helpful order support chatbot. You can access order information when available.\n\n";
	$system_message .= "Store Details:\n" . $store_details . "\n\n";
	$system_message .= $orders_text . "\n\n";
	$system_message .= "Important Rules for Orders:\n";
	$system_message .= "- Always ask for order number first if not provided\n";

	$system_message .= "- Before asking for the customer's email, review the available store details and try to answer using that information if possible.\n";
	$system_message .= "- If unsure, say: '" . stripcslashes($plugify_save_chatbot_settings['bot_email_request_message']) . "'\n";

	$messages = array(
		array('role' => 'system', 'content' => $system_message)
	);

	foreach ($conversation as $msg) {
		$messages[] = $msg;
	}

	$messages[] = array('role' => 'user', 'content' => $message);

	$response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
		'headers' => array(
			'Content-Type' => 'application/json',
			'Authorization' => 'Bearer ' . $plugify_save_ai_settings['openai_apii']
		),
		'body' => json_encode(array(
			'model' => $plugify_save_ai_settings['openai_modell'],
			'messages' => $messages,
			'max_tokens' => intval($plugify_save_chatbot_settings['max_bot_reply']),
			'temperature' => 0.7
		))
	));

	if (is_wp_error($response)) {
		return stripcslashes($plugify_save_chatbot_settings['technical_issue']);
	}

	$body = json_decode($response['body'], true);
	return $body['choices'][0]['message']['content'];
}

function plugify_woo_ai_chatbot_generate_general_response($message, $conversation) {
	$plugify_save_ai_settings = get_option('plugify_save_ai_settings');
	$plugify_save_chatbot_settings = get_option('plugify_save_chatbot_settings');

	if (isset($plugify_save_chatbot_settings['store_details'])) {

		$store_details = $plugify_save_chatbot_settings['store_details'];

		$store_details = strtr($store_details, array(
			'{admin_email}'     => get_option('admin_email'),
			'{shop_page_url}'   => get_permalink(wc_get_page_id('shop')),
			'{home_page_url}'   => home_url(),
			'{my_account_url}'  => get_permalink(get_option('woocommerce_myaccount_page_id')),
		));


	} else {
		$store_details = '';
	}

	$system_message = 'You are a helpful customer support chatbot for ' . esc_attr(stripcslashes($plugify_save_chatbot_settings['store_namee'])) . '. It is woocommerce store.';
	$system_message .= "Store Details:\n" . $store_details . "\n\n";
	$system_message .= "STRICT RULES YOU MUST FOLLOW:\n";
	$system_message .= "1. You can ONLY answer questions about:\n";
	$system_message .= "   - Products available in the store (direct to product questions)\n";
	$system_message .= "   - Orders (direct to order questions)\n";
	$system_message .= "   - Basic store information\n";
	$system_message .= "2. Never answer questions about:\n";
	$system_message .= "   - Other stores/websites\n";
	$system_message .= "   - General knowledge\n";
	$system_message .= "   - Politics, sports, entertainment, etc.\n";
	$system_message .= "- Before asking for the customer's email, review the available store details and try to answer using that information if possible.\n";

	$system_message .= "- If unsure, say: '" . stripcslashes($plugify_save_chatbot_settings['bot_email_request_message']) . "'\n";
	$system_message .= 'You are a helpful customer support chatbot for ' . esc_attr(stripcslashes($plugify_save_chatbot_settings['store_namee'])) . '. It is woocommerce store.';

	$messages = array(
		array('role' => 'system', 'content' => $system_message)
	);

	foreach ($conversation as $msg) {
		$messages[] = $msg;
	}

	$messages[] = array('role' => 'user', 'content' => $message);

	$response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
		'headers' => array(
			'Content-Type' => 'application/json',
			'Authorization' => 'Bearer ' . $plugify_save_ai_settings['openai_apii']
		),
		'body' => json_encode(array(
			'model' => $plugify_save_ai_settings['openai_modell'],
			'messages' => $messages,
			'max_tokens' => intval($plugify_save_chatbot_settings['max_bot_reply']),
			'temperature' => 0.7
		))
	));

	if (is_wp_error($response)) {
		return stripcslashes($plugify_save_chatbot_settings['technical_issue']);
	}

	$body = json_decode($response['body'], true);
	return $body['choices'][0]['message']['content'];
}

function plugify_ai_save_chat_history($user_id, $conversation) {
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
		$existing_chat_log = get_post_meta($post_id, 'plugify_chat_historyyy', true);
		if (!is_array($existing_chat_log)) {
			$existing_chat_log = [];
		}

		$new_entries = [];
		foreach ($conversation as $msg) {
			$found = false;
			foreach ($existing_chat_log as $existing_msg) {
				if ($existing_msg['message'] === $msg['content'] && 
					$existing_msg['role'] === $msg['role']) {
					$found = true;
				break;
			}
		}

		if (!$found) {
			$new_entries[] = [
				'time' => current_time('timestamp'),
				'role' => $msg['role'],
				'message' => $msg['content']
			];
		}
	}

	$updated_chat_log = array_merge($existing_chat_log, $new_entries);
	update_post_meta($post_id, 'plugify_chat_historyyy', $updated_chat_log);
	update_post_meta($post_id, 'plugify_chat_unread', 'true');
} else {
	if (is_numeric($user_id)) {
		$user_info = get_userdata($user_id);
		$user_name = $user_info ? $user_info->display_name : 'User_' . $user_id;
		$post_title = $user_name;
	} else {
		$post_title = ucfirst($user_id); 
	}

	$post_id = wp_insert_post([
		'post_type' => 'woo_ai_chat',
		'post_status' => 'publish',
		'post_title' => $post_title,
	]);

	update_post_meta($post_id, 'chat_user_id', $user_id);

	$chat_log = [];
	foreach ($conversation as $msg) {
		$chat_log[] = [
			'time' => current_time('timestamp'),
			'role' => $msg['role'],
			'message' => $msg['content']
		];
	}

	update_post_meta($post_id, 'plugify_chat_historyyy', $chat_log);
	update_post_meta($post_id, 'plugify_chat_unread', 'true');
}

return $post_id;
}

function plugify_woo_ai_chatbot_get_all_products() {

	$cached_products = get_transient('woo_ai_chatbot_products');
	if ($cached_products !== false) {
		return $cached_products;
	}

	$args = array(
		'post_type' => 'product',
		'posts_per_page' => -1,
		'post_status' => 'publish'
	);

	$products = new WP_Query($args);
	$products_info = array();

	if ($products->have_posts()) {
		while ($products->have_posts()) {
			$products->the_post();
			$product = wc_get_product(get_the_ID());

			$product_data = array(
				'id' => $product->get_id(),
				'name' => $product->get_name(),
				'description' => $product->get_short_description() ?: $product->get_description(),
				'price' => $product->get_price(),
				'regular_price' => $product->get_regular_price(),
				'sale_price' => $product->get_sale_price(),
				'currency' => get_woocommerce_currency_symbol(),
				'stock' => $product->get_stock_quantity(),
				'stock_status' => $product->get_stock_status(),
				'sku' => $product->get_sku(),
				'url' => get_permalink(),
				'categories' => wp_get_post_terms(get_the_ID(), 'product_cat', array('fields' => 'names')),
				'type' => $product->get_type(),
				'variations' => array(),
				'attributes' => array()
			);

			if ($product->is_type('variable')) {
				$attributes = $product->get_variation_attributes();
				foreach ($attributes as $attribute_name => $options) {
					$clean_name = str_replace('pa_', '', $attribute_name);
					$product_data['attributes'][$clean_name] = array(
						'name' => wc_attribute_label($attribute_name),
						'options' => array()
					);

					foreach ($options as $option) {
						$term = get_term_by('slug', $option, $attribute_name);
						$product_data['attributes'][$clean_name]['options'][] = $term ? $term->name : $option;
					}
				}

				$variation_ids = $product->get_children();
				foreach ($variation_ids as $variation_id) {
					$variation = wc_get_product($variation_id);

					if ($variation && $variation->exists()) {
						$variation_attributes = array();
						$attributes = $variation->get_attributes();

						foreach ($attributes as $attr_name => $attr_value) {
							$clean_name = str_replace('pa_', '', $attr_name);
							$term = get_term_by('slug', $attr_value, $attr_name);
							$variation_attributes[$clean_name] = array(
								'name' => wc_attribute_label($attr_name),
								'value' => $term ? $term->name : $attr_value
							);
						}

						$product_data['variations'][] = array(
							'variation_id' => $variation_id,
							'attributes' => $variation_attributes,
							'price' => $variation->get_price(),
							'regular_price' => $variation->get_regular_price(),
							'sale_price' => $variation->get_sale_price(),
							'currency' => get_woocommerce_currency_symbol(),
							'stock' => $variation->get_stock_quantity(),
							'stock_status' => $variation->get_stock_status(),
							'sku' => $variation->get_sku(),
							'url' => $variation->get_permalink(),
							'image' => wp_get_attachment_url($variation->get_image_id())
						);
					}
				}
			}

			$products_info[] = $product_data;
		}
		wp_reset_postdata();
	}

	set_transient('woo_ai_chatbot_products', $products_info, HOUR_IN_SECONDS);
	return $products_info;
}



add_action('save_post_product', 'plugify_woo_ai_chatbot_clear_cache');

function plugify_woo_ai_chatbot_clear_cache() {
	delete_transient('woo_ai_chatbot_products');
}



add_action('init', 'plugify_custom_chatbot_start_session', 1);
function plugify_custom_chatbot_start_session() {
	if (!session_id()) {
		session_start();
	}
}


add_action('wp_ajax_plugify_generate_variation_ai_description', 'plugify_generate_variation_ai_description');
add_action('wp_ajax_plugify_enhance_variation_ai_description', 'plugify_enhance_variation_ai_description');

function plugify_generate_variation_ai_description() {

	if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'plugify_custom_nonce_for_js')) {
		return;
	}

	if (!isset($_POST['variation_id']) || !isset($_POST['prod_name'])) {
		wp_send_json_error(['message' => 'Missing required parameters.']);
		wp_die();
	}

	$variation_id    = intval($_POST['variation_id']);
	$prod_name       = sanitize_text_field(wp_unslash($_POST['prod_name']));
	$key_features    = isset($_POST['key_features']) ? sanitize_textarea_field(wp_unslash($_POST['key_features'])) : '';
	$max_limit       = isset($_POST['variation_max_limit']) ? intval($_POST['variation_max_limit']) : 200;
	$limit_type      = isset($_POST['variation_limit_type']) ? sanitize_text_field(wp_unslash($_POST['variation_limit_type'])) : 'tokens';

	$plugify_save_ai_settings = get_option('plugify_save_ai_settings');
	$api_key       = $plugify_save_ai_settings['openai_apii'];
	$openai_modell = $plugify_save_ai_settings['openai_modell'];
	$url           = 'https://api.openai.com/v1/chat/completions';

	$prompt = "Generate a product variation description for \"$prod_name\". ";
	if (!empty($key_features)) {
		$prompt .= "Key features: $key_features. ";
	}

	if ($limit_type === 'characters') {
		$prompt .= "The description must not exceed {$max_limit} characters.";
	} elseif ($limit_type === 'words') {
		$prompt .= "The description must not exceed {$max_limit} words.";
	} elseif ($limit_type === 'tokens') {
		$prompt .= "The description must stay within {$max_limit} tokens.";
	}

	$data = [
		'model' => $openai_modell,
		'messages' => [
			['role' => 'system', 'content' => 'You are an expert product copywriter specializing in ecommerce product variations. Ensure the response is complete, natural, and ends with a full sentence.'],
			['role' => 'user', 'content' => $prompt]
		],
		'temperature' => 0.4
	];

	if ($limit_type === 'tokens') {
		$data['max_tokens'] = $max_limit;
	}

	$response = wp_remote_post($url, [
		'headers' => [
			'Content-Type'  => 'application/json',
			'Authorization' => 'Bearer ' . $api_key,
		],
		'body' => json_encode($data),
		'timeout' => 20,
	]);

	if (is_wp_error($response)) {
		wp_send_json_error(['message' => 'API request failed: ' . $response->get_error_message()]);
	} else {
		$body = json_decode(wp_remote_retrieve_body($response), true);

		if (isset($body['choices'][0]['message']['content'])) {
			$generated_description = trim($body['choices'][0]['message']['content']);

			if ($limit_type === 'characters') {
				$generated_description = mb_substr($generated_description, 0, $max_limit);
			} elseif ($limit_type === 'words') {
				$words = preg_split('/\s+/', $generated_description);
				if (count($words) > $max_limit) {
					$generated_description = implode(' ', array_slice($words, 0, $max_limit));
				}
			}

			wp_send_json_success([
				'description' => $generated_description
			]);
		} else {
			wp_send_json_error([
				'message' => 'Invalid response from API.',
				'raw_response' => $body
			]);
		}
	}

	wp_die();
}

function plugify_enhance_variation_ai_description() {

	if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'plugify_custom_nonce_for_js')) {
		return;
	}


	if (!isset($_POST['variation_id']) || !isset($_POST['current_description'])) {
		wp_send_json_error(['message' => 'Missing required parameters.']);
		wp_die();
	}

	$variation_id = intval($_POST['variation_id']);
	$current_description = sanitize_textarea_field(wp_unslash($_POST['current_description']));

	$plugify_save_tokens_settings = get_option('plugify_save_tokens_settings');
	$max_enhance_tokens = $plugify_save_tokens_settings['max_enhance_tokens'];

	$plugify_save_ai_settings = get_option('plugify_save_ai_settings');
	$openai_apii = $plugify_save_ai_settings['openai_apii'];
	$openai_modell = $plugify_save_ai_settings['openai_modell'];

	$api_key = $openai_apii;
	$url = 'https://api.openai.com/v1/chat/completions';

	$data = [
		'model' => $openai_modell,
		'messages' => [
			['role' => 'system', 'content' => 'You are an expert editor who enhances product descriptions to be more engaging and SEO-friendly while preserving their original meaning. Keep the output concise, natural, and complete. End with a full sentence, and stay within the token limit. If needed, summarize slightly.'],

			['role' => 'user', 'content' => "Improve this product variation description while keeping the same length:\n\n$current_description"]
		],
		'max_tokens' => intval($max_enhance_tokens),
		'temperature' => 0.3
	];

	$response = wp_remote_post($url, [
		'headers' => [
			'Content-Type'  => 'application/json',
			'Authorization' => 'Bearer ' . $api_key,
		],
		'body' => json_encode($data),
		'timeout' => 20,
	]);

	if (is_wp_error($response)) {
		wp_send_json_error(['message' => 'API request failed: ' . $response->get_error_message()]);
	} else {
		$body = json_decode(wp_remote_retrieve_body($response), true);

		if (isset($body['choices'][0]['message']['content'])) {
			$enhanced_description = $body['choices'][0]['message']['content'];
			wp_send_json_success([
				'description' => $enhanced_description
			]);
		} else {
			wp_send_json_error([
				'message' => 'Invalid response from API.',
				'raw_response' => $body
			]);
		}
	}

	wp_die();
}



add_action('wp_ajax_plugify_save_ai_settings', 'plugify_save_ai_settings');
add_action('wp_ajax_plugify_save_tokens_settings', 'plugify_save_tokens_settings');
add_action('wp_ajax_plugify_save_chatbot_settings', 'plugify_save_chatbot_settings');

function plugify_save_ai_settings () {
	if (isset($_POST['nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'plugify_custom_nonce_for_js')) {
		update_option('plugify_save_ai_settings', $_POST);
	}
}

function plugify_save_tokens_settings () {
	if (isset($_POST['nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'plugify_custom_nonce_for_js')) {
		update_option('plugify_save_tokens_settings', $_POST);
	}
}

function plugify_save_chatbot_settings () {
	if (isset($_POST['nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'plugify_custom_nonce_for_js')) {
		update_option('plugify_save_chatbot_settings', $_POST);
	}
}



register_activation_hook( __FILE__, 'plugify_prp_activation_hook_for_default_settings' );

function plugify_prp_activation_hook_for_default_settings () {

	$plugify_save_chatbot_settings = get_option('plugify_save_chatbot_settings');

	$admin_email = get_option('admin_email');	

	if ('' == $plugify_save_chatbot_settings) {
		$plugify_save_chatbot_settings=array(
			'max_bot_reply' => '500',
			'enable_chatbott' => 'true',
			'bot_how_many_unsolved' => '2',
			'bot_email_request_message' => 'Could you please share your email address or send us email on ' . filter_var($admin_email) . ' so we can contact you with more details?',
			'bot_header_text' => 'Store Assistant',
			'bot_header_bg' => '#007cba',
			'bot_header_txt_clr' => '#FFF',
			'send_btn_txt' => 'Send',
			'send_btn_bg' => '#007cba',
			'send_btn_txt_color' => '#FFFFFF',
			'icon_bg' => '#007cba',
			'icon_color' => '#FFFFFF',
			'chatbot_display_page' => array(),
			'chatbot_display_woocommerce_pages' => array(
				'home_page',
				'shop_page',
				'product_page',
				'myaccount'
			),
			'after_mail_msg' => 'Thank you for providing us your email address! Our team will contact you soon with more details.',
			'technical_issue' => 'I am having trouble responding right now. Please try again later or contact support.',
			'window_bg' => '#FFFFFF',
			'admin_bg' => '#efecec',
			'admin_txt_color' => '#422b2b',
			'user_bg' => '#e3f2fd',
			'user_txt_color' => '#422b2b',
			'admin_time_color' => '#888',
			'user_time_color' => '#888',
			'store_namee' => 'My Store',
		);

		update_option('plugify_save_chatbot_settings', $plugify_save_chatbot_settings);
	}

	$plugify_save_tokens_settings = get_option('plugify_save_tokens_settings');

	if ('' == $plugify_save_tokens_settings) {
		$plugify_save_tokens_settings=array(
			'max_enhance_tokens' => '200',
			'max_short_descriptions_tokens' => '100',
			'max_reply_tokens' => '100',
		);
		
		update_option('plugify_save_tokens_settings', $plugify_save_tokens_settings);
	}


	$plugify_save_ai_settings = get_option('plugify_save_ai_settings');

	if ('' == $plugify_save_ai_settings) {
		$plugify_save_ai_settings=array(
			'openai_apii' => '',
			'openai_modell' => 'gpt-4o-mini',
		);
		
		update_option('plugify_save_ai_settings', $plugify_save_ai_settings);
	}


}


add_filter( 'plugin_action_links', 'plugify_ai_plugins_page_action_links' , 10, 2 );
function plugify_ai_plugins_page_action_links ( $links, $file ) {

	if ( 'ai-addons/ai-addons.php' == $file ) {
		$settings = '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=plugify_ai_set' ) . '">' . esc_html__( 'Settings', 'ai-addons' ) . '</a>';
		array_unshift( $links, $settings);
	}
	return (array) $links;
}





