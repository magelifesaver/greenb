<?php // Dashboard Widgets Suite - Display Settings

if (!defined('ABSPATH')) exit;

function dashboard_widgets_suite_menu_pages() {
	
	// add_options_page( $page_title, $menu_title, $capability, $menu_slug, $function )
	add_options_page('Widgets Suite', 'Dashboard Widgets', 'manage_options', 'dashboard_widgets_suite', 'dashboard_widgets_suite_display_settings');
	
}

function dashboard_widgets_suite_get_tabs() {
	
	$tabs = array(
		'tab1' => esc_html__('General Settings', 'dashboard-widgets-suite'), 
		'tab2' => esc_html__('User Notes',       'dashboard-widgets-suite'), 
		'tab3' => esc_html__('Feed Box',         'dashboard-widgets-suite'), 
		'tab4' => esc_html__('Social Box',       'dashboard-widgets-suite'), 
		'tab5' => esc_html__('List Box',         'dashboard-widgets-suite'), 
		'tab6' => esc_html__('Widget Box',       'dashboard-widgets-suite'), 
		'tab7' => esc_html__('System Info',      'dashboard-widgets-suite'), 
		'tab8' => esc_html__('Debug Log',        'dashboard-widgets-suite'), 
		'tab9' => esc_html__('Error Log',        'dashboard-widgets-suite'),
	);
	
	return $tabs;
	
}

function dashboard_widgets_suite_display_settings() { 
	
	$tab_active = 'tab1';
	
	if (isset($_GET['tab'])) {
		
		switch ($_GET['tab']) {
			
			case 'tab1' : $tab_active = 'tab1'; break;
			
			case 'tab2' : $tab_active = 'tab2'; break;
			
			case 'tab3' : $tab_active = 'tab3'; break;
			
			case 'tab4' : $tab_active = 'tab4'; break;
			
			case 'tab5' : $tab_active = 'tab5'; break;
			
			case 'tab6' : $tab_active = 'tab6'; break;
			
			case 'tab7' : $tab_active = 'tab7'; break;
			
			case 'tab8' : $tab_active = 'tab8'; break;
			
			case 'tab9' : $tab_active = 'tab9'; break;
			
		}
		
	}
	
	$tab_href = admin_url('options-general.php?page=dashboard_widgets_suite');
	
	$tab_names = dashboard_widgets_suite_get_tabs();
	
	?>
	
	<div class="wrap wrap-<?php echo $tab_active; ?>">
		<h1><span class="fa fa-pad fa-th"></span> <?php echo DWS_NAME; ?> <span class="dws-version"><?php echo DWS_VERSION; ?></span></h1>
		<h2 class="nav-tab-wrapper">
			
			<?php 
				
				foreach ($tab_names as $key => $value) {
					
					$active = ($tab_active === $key) ? ' nav-tab-active' : '';
					
					echo '<a href="'. $tab_href .'&tab='. $key .'" class="nav-tab nav-'. $key . $active .'">'. $value .'</a>';
					
				}
				
			?>
			
		</h2>
		<form method="post" action="options.php">
			
			<?php
				
				if ($tab_active === 'tab1') {
					
					settings_fields('dws_options_general');
					do_settings_sections('dws_options_general');
				
				} elseif ($tab_active === 'tab2') {
					
					settings_fields('dws_options_notes_user');
					do_settings_sections('dws_options_notes_user');
					
				} elseif ($tab_active === 'tab3') {
					
					settings_fields('dws_options_feed_box');
					do_settings_sections('dws_options_feed_box');
					
				} elseif ($tab_active === 'tab4') {
					
					settings_fields('dws_options_social_box');
					do_settings_sections('dws_options_social_box');
					dashboard_widgets_suite_section_social_box_note();
					
				} elseif ($tab_active === 'tab5') {
					
					settings_fields('dws_options_list_box');
					do_settings_sections('dws_options_list_box');
					
				} elseif ($tab_active === 'tab6') {
					
					settings_fields('dws_options_widget_box');
					do_settings_sections('dws_options_widget_box');
					
				} elseif ($tab_active === 'tab7') {
					
					settings_fields('dws_options_system_info');
					do_settings_sections('dws_options_system_info');
					
				} elseif ($tab_active === 'tab8') {
					
					settings_fields('dws_options_log_debug');
					do_settings_sections('dws_options_log_debug');
					
				} elseif ($tab_active === 'tab9') {
					
					settings_fields('dws_options_log_error');
					do_settings_sections('dws_options_log_error');
				
				}
				
				submit_button();
				
				dashboard_widgets_suite_wp_resources($tab_active);
				
			?>
			
		</form>
	</div>
	
<?php }

function dashboard_widgets_suite_wp_resources($tab_active) {
	
	if ($tab_active !== 'tab1') :
		
		$array = array(
			
			0  => '<a target="_blank" rel="noopener noreferrer" href="https://digwp.com/" title="Take your WP skills to the next level"><img width="125" height="125" src="'. DWS_URL .'img/resources/book-digging-into-wordpress.jpg" alt=""></a>',
			
			1  => '<a target="_blank" rel="noopener noreferrer" href="https://htaccessbook.com/" title="Secure and optimize your website"><img width="125" height="125" src="'. DWS_URL .'img/resources/book-htaccess-made-easy.jpg" alt=""></a>',
			
			2  => '<a target="_blank" rel="noopener noreferrer" href="https://wp-tao.com/" title="Learn the Way of WordPress"><img width="125" height="125" src="'. DWS_URL .'img/resources/book-tao-of-wordpress.jpg" alt=""></a>',
			
			3  => '<a target="_blank" rel="noopener noreferrer" href="https://books.perishablepress.com/downloads/wizards-collection-sql-recipes-wordpress/" title="Wizard&rsquo;s SQL Recipes for WordPress"><img width="125" height="125" src="'. DWS_URL .'img/resources/book-wizards-sql-wordpress.jpg" alt=""></a>',
			
			4  => '<a target="_blank" rel="noopener noreferrer" href="https://wp-tao.com/wordpress-themes-book/" title="Build and sell awesome themes"><img width="125" height="125" src="'. DWS_URL .'img/resources/book-wp-themes-in-depth.jpg" alt=""></a>',
			
			//
			
			5  => '<a target="_blank" rel="noopener noreferrer" href="https://plugin-planet.com/banhammer-pro/" title="Banhammer Pro: Drop the hammer."><img width="125" height="125" src="'. DWS_URL .'img/resources/plugin-banhammer-pro.jpg" alt=""></a>',
			
			6  => '<a target="_blank" rel="noopener noreferrer" href="https://plugin-planet.com/bbq-pro/" title="Fastest WordPress Firewall"><img width="125" height="125" src="'. DWS_URL .'img/resources/plugin-bbq-pro.jpg" alt=""></a>',	
						
			7  => '<a target="_blank" rel="noopener noreferrer" href="https://plugin-planet.com/blackhole-pro/" title="Trap bad bots in a virtual black hole"><img width="125" height="125" src="'. DWS_URL .'img/resources/plugin-blackhole-pro.jpg" alt=""></a>',
			
			8  => '<a target="_blank" rel="noopener noreferrer" href="https://plugin-planet.com/ga-google-analytics-pro/" title="Connect Google Analytics to WordPress"><img width="125" height="125" src="'. DWS_URL .'img/resources/plugin-ga-pro.jpg" alt=""></a>',
			
			9  => '<a target="_blank" rel="noopener noreferrer" href="https://plugin-planet.com/head-meta-pro/" title="Ultimate Meta Tags for WordPress"><img width="125" height="125" src="'. DWS_URL .'img/resources/plugin-head-meta-pro.jpg" alt=""></a>',
			
			10 => '<a target="_blank" rel="noopener noreferrer" href="https://plugin-planet.com/simple-ajax-chat-pro/" title="Unlimited chats for WordPress"><img width="125" height="125" src="'. DWS_URL .'img/resources/plugin-sac-pro.jpg" alt=""></a>',
					
			11 => '<a target="_blank" rel="noopener noreferrer" href="https://plugin-planet.com/usp-pro/" title="Unlimited front-end forms"><img width="125" height="125" src="'. DWS_URL .'img/resources/plugin-usp-pro.jpg" alt=""></a>',
			
		);
		
		$items = array_rand($array, 4);
		
		$item1 = isset($array[$items[0]]) ? $array[$items[0]] : null;
		$item2 = isset($array[$items[1]]) ? $array[$items[1]] : null;
		$item3 = isset($array[$items[2]]) ? $array[$items[2]] : null;
		$item4 = isset($array[$items[3]]) ? $array[$items[3]] : null;
		
		if ($item1 && $item3 && $item3 && $item4) :
		
		?>
			
			<hr class="wp-resources-sep">
			
			<div class="wp-resources">
				
				<h2 class="dws-noicon"><span class="dashicons dashicons-coffee"></span> <?php esc_html_e('WP Resources', 'dashboard-widgets-suite'); ?></h2>
				
				<p><?php esc_html_e('Check out more WordPress resources from this developer:', 'dashboard-widgets-suite'); ?></p>
				
				<div class="wp-resources-wrap"><?php echo $item1 . $item2 . $item3 . $item4; ?></div>
				
			</div>
			
		<?php
		
		endif;
		
	endif;
	
}