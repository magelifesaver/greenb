<?php
add_action( 'wp_footer' , 'absbs_function_to_enque' );

function absbs_function_to_enque() {
	

	wp_enqueue_script('absb_animation_for_modal', 'https://code.jquery.com/ui/1.13.1/jquery-ui.min.js', '1.0', 'all');
	wp_enqueue_style('dfds', 'https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css', '1.0', 'all');
	?>
	<div class="mainpopup animate__zoomInLeft" style="width: 200px;background-color: red;color: white; padding: 10px 5px; display: none;position: absolute;top: 300px;">notification</div>
	<?php
}


add_action( 'woocommerce_before_add_to_cart_form', 'absbs_snf_add_custom_text_to_product_page' );

function absbs_snf_add_custom_text_to_product_page() {

	$message_settings = get_option('nfs_message_settings_for_notify') ;
	if ('true' == $message_settings['enable_custom_msg_for_product']) {
		$custom_message = $message_settings['custom_msg_product'];
		// $number_for_text = rand($message_settings['min_for_custom'], $message_settings['max_for_custom']);
		
		if (get_option('stored_plgfy_nmbr')=='') {
			$number_for_text = $message_settings['min_for_custom'];
			update_option('stored_plgfy_nmbr', $number_for_text);
			update_option('time_of_update_plgfy', time());
		} else {			
			$number_for_text=get_option('stored_plgfy_nmbr');
			$time_of_update_plgfy=get_option('time_of_update_plgfy');
			$diffff=time()-$time_of_update_plgfy;

			if ($diffff>180) {
				$number_for_text= floatval($number_for_text) + floatval($message_settings['max_for_custom']);

				update_option('stored_plgfy_nmbr', $number_for_text);
				update_option('time_of_update_plgfy', time());
			}			
		}
		
		// if (str_contains($custom_message, '{number}')) { 
			$messages = str_replace('{number}', $number_for_text, $custom_message);
		// }

		echo esc_html($messages);
	}
}

function isMobile() {
	if (isset($_SERVER['HTTP_USER_AGENT'])) {
		return preg_match('/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i', filter_var($_SERVER['HTTP_USER_AGENT']));
	} else {
		return false;
	}
}

add_action( 'wp_footer', 'notify_creating_notify_div');
function notify_creating_notify_div () {

	$gen_settings = get_option('nfs_general_settings_for_notify')  ;
	$message_settings = get_option('nfs_message_settings_for_notify') ;
	$time_settings =  get_option('nfs_time_settings_for_notify');
	$shortcode_settings = get_option('nfs_saving_shortcode_settings') ;
	$display_settings =  get_option('nfs_display_settings_for_notify');

	if (isset($time_settings['display_time'])) {
		$notification_display_time = $time_settings['display_time']*1000;	
	}
	if (isset($time_settings['start_range_notify'])) {
		$random_start = $time_settings['start_range_notify'];
	}
	if (isset($time_settings['end_range_notify'])) {
		$random_end = $time_settings['end_range_notify'];
	}

	if (isset($shortcode_settings['appliedon_notify']) && 'products' == $shortcode_settings['appliedon_notify']) {

		$products_array = $shortcode_settings['procat_ids_notify'];

		$random_products = $products_array[rand(0, count($products_array)-1)];
	}
	if (isset($shortcode_settings['appliedon_notify']) && 'categories' == $shortcode_settings['appliedon_notify']) {
		$args = array(      
			'post_type' => 'product',
			'post_status' => 'publish',
			'tax_query' => array(
				array(
					'taxonomy' => 'product_cat',
					'field'    => 'term_id',
					'terms'    => $shortcode_settings['procat_ids_notify'],
				),
			),
		);

		$products = get_posts($args);
		$random_products = $products[rand(0, count($products)-1)];
		$random_products = $random_products->ID;
	}
	?>

	<div class="nfs_message">
	</div>
	<?php

	$bottom = '';
	$top = '';
	$right = '';
	$left = '';

	if (isset( $display_settings['notify_location'] ) && 'topleft' == $display_settings['notify_location']) {

		$bottom = '0';
		$top = '5%';
		$right = 'auto';
		$left = '3%';

	}

	if (isset( $display_settings['notify_location'] ) && 'topright' == $display_settings['notify_location']) {

		$bottom = '0';
		$top = '5%';
		$right = '3%';
		$left = 'auto';

	}


	if (isset( $display_settings['notify_location'] ) && 'rightbottom' == $display_settings['notify_location']) {

		$bottom = '5%';
		$right = '3%';
		$left = 'auto';

	}

	if (isset( $display_settings['notify_location'] ) && 'leftbottom' == $display_settings['notify_location']) {

		$bottom = '5%';
		$right = 'auto';
		$left = '3%';

	}


	if (isset( $display_settings['notify_animate'] ) && 'slideright' == $display_settings['notify_animate']) {
		$animate = 'right';
		$close = 'left';
	}

	if (isset( $display_settings['notify_animate'] ) && 'slideleft' == $display_settings['notify_animate']) {
		$animate = 'left';
		$close = 'right';
	}


	if (isset( $display_settings['notify_animate'] ) && 'slideup' == $display_settings['notify_animate']) {
		$animate = 'up';
		$close = 'down';
	}


	if (isset( $display_settings['notify_animate'] ) && 'slidedown' == $display_settings['notify_animate']) {
		$animate = 'down';
		$close = 'up';
	}

	?>


	<style type="text/css">


		.modalpopup1 {
			/*overflow: auto !important;*/
			position: fixed;
			z-index: 9999;		
			right: <?php echo esc_attr($right); ?> !important;
			top: <?php echo esc_attr($top); ?>!important;
			bottom: <?php echo esc_attr($bottom); ?>!important;
			left: <?php echo esc_attr($left); ?>!important;
			width: 400px; 
			height: fit-content; 
			margin-bottom: 20px;
			display: none;

		}

		.nfs_message .modal-content {
			background-color: <?php echo ( is_array( $display_settings ) && isset( $display_settings['notify_bgcolor'] ) ) ? esc_attr( $display_settings['notify_bgcolor'] ) : 'white'; ?>;
			margin: auto;
			padding: 10px;
			border: 1px solid <?php echo ( is_array( $display_settings ) && isset( $display_settings['notify_border_color'] ) ) ? esc_attr( $display_settings['notify_border_color'] ) : 'black'; ?>;
			width: 100%;
			border-radius: <?php echo ( is_array( $display_settings ) && isset( $display_settings['notify_radius'] ) ) ? esc_attr( $display_settings['notify_radius'] ) : '5'; ?>px;
			color: <?php echo ( is_array( $display_settings ) && isset( $display_settings['notify_txt_color'] ) ) ? esc_attr( $display_settings['notify_txt_color'] ) : 'black'; ?>;
			margin-bottom: 20px;
			padding-left: 15px;
			padding-right: 15px;
		}

		.close_notification {
			color: <?php echo isset($display_settings['notify_txt_color']) ? esc_attr($display_settings['notify_txt_color']) : '#000000'; ?>;
			float: right;
			font-size: 18px;
			font-weight: bold;
			background:none;
			border-style: none;
			margin-right: 1%;
			border:none;
		}
		.close_notification:hover,
		.close_notification:focus {
			color: red;
			text-decoration: none;
			cursor: pointer;
			/*margin-bottom: 1%;*/
			background: none;
			border:none !important;
		}
		.w3-animate-top{
			display: block;
			animation:animatetop 1.4s;
		}
		@keyframes animatetop{
			from{
				top:-300px;
				opacity:0
			}
			to{
				top:0;
				opacity:1
			}
		}

		@media screen and (max-width: 550px) {
			.modal-content  {
				background-color: <?php echo isset($display_settings['notify_bgcolor']) ? esc_attr($display_settings['notify_bgcolor']) : 'white'; ?>;
				border: 1px solid <?php echo isset($display_settings['notify_border_color']) ? esc_attr($display_settings['notify_border_color']) : 'black'; ?>;
				border-radius: <?php echo isset($display_settings['notify_radius']) ? esc_attr($display_settings['notify_radius']) : '5'; ?>px;
				color: <?php echo isset($display_settings['notify_txt_color']) ? esc_attr($display_settings['notify_txt_color']) : 'black'; ?>;
				width: auto;
				height: auto;
				margin-bottom: 20px;
				padding-left: 15px;
				padding-right: 15px;
				margin-top: 20px;
			}
			.modalpopup1 {
				width: 95%;
				height: 30px;
				margin-bottom: 20px;
			}
		}

	</style>


	<?php 
	$is_show_notification = false;
	if (is_shop() && 'true' == $gen_settings['notifyonshop']) {
		$is_show_notification = true;
	}


	if (is_product() && 'true' == $gen_settings['notifyonproduct']) {
		$is_show_notification = true;
	}

	if (is_cart() && isset($gen_settings['notifyoncart']) && 'true' == $gen_settings['notifyoncart']) {
		$is_show_notification = true;
	}

	if (is_checkout() && 'true' == $gen_settings['notify_on_checkout']) {
		$is_show_notification = true;
	}



	if (isMobile()) {

		if ('true' == $gen_settings['enablephone']) {
			$is_show_notification = true;
		} else {
			$is_show_notification = false;
		}

	}

	$host = '';

	if (isset($_SERVER['HTTP_HOST'])) {
		$host = filter_var($_SERVER['HTTP_HOST']);
	}

	$request_uri = '';
	if (isset($_SERVER['REQUEST_URI'])) {
		$request_uri = filter_var($_SERVER['REQUEST_URI']);
	}


	$uri = filter_var($host) . filter_var($request_uri)  ;

	if (isset($_SERVER['HTTPS']) && 'on' === $_SERVER['HTTPS'] ) {
		$link = 'https';
	} else {
		$link = 'http';
	}
	$link .= '://';

	$link .= filter_var($host);

	$link .= filter_var($request_uri);

	if ( isset( $gen_settings['notify_on_custom_url'] ) && 'true' == $gen_settings['notify_on_custom_url'] && isset($gen_settings['custom_url']) && is_array($gen_settings['custom_url']) && in_array($link, $gen_settings['custom_url']) ) {
		$is_show_notification = true;
	}

	if ($is_show_notification) {
		?>
		<script type="text/javascript">

			// jQuery(document).ready(function(){
				// setInterval(function(){
				// 	jQuery('.mainpopup').show();
				// },3000)
			// });
			jQuery('body').on('click', '.close_notification' , function() {
				jQuery('.modalpopup1').fadeOut(1000);
			})

			jQuery(document).ready(function() {

				var count = 2;

				<?php $random_gap1 = rand($random_start * 1000, $random_end * 1000); ?>

				setTimeout(function(){

					send_ajax_for_notification ();


					if (1 == <?php echo esc_html($time_settings['number_of_notify']); ?>) {
						return;
					}


					var interval = setInterval(function() {



						<?php $random_gap = rand($random_start * 1000, $random_end * 1000); ?>

						<?php $first_notification_time = $notification_display_time + $random_gap; ?>
						send_ajax_for_notification ();

						count = count+1;

						if (count > <?php echo esc_html($time_settings['number_of_notify']); ?>) {
							clearInterval(interval);
						}


					}, <?php echo esc_html($first_notification_time); ?>);  

				}, <?php echo esc_html($random_gap1); ?>);

			});

			function send_ajax_for_notification () {
				jQuery.ajax({
					url : '<?php echo filter_var(admin_url('admin-ajax.php')); ?>',
					type : 'post',
					data : {
						action : 'nfs_ajax_for_notification_show',
					},
					success : function( response ) {
						// console.log(response);
						jQuery('.nfs_message').html(response);


						jQuery('.modalpopup1').show();
						setTimeout(function(){							
							jQuery('.modalpopup1').fadeOut(2000);
						},  <?php echo esc_html($notification_display_time); ?>);

					}

				});
			}

		</script>

		<?php

	}
}
