<?php // Dashboard Widgets Suite - Reset Settings

if (!defined('ABSPATH')) exit;

function dashboard_widgets_suite_admin_notice() {
	
	$screen_id = dashboard_widgets_suite_get_current_screen_id();
	
	if ($screen_id === 'settings_page_dashboard_widgets_suite') {
		
		if (isset($_GET['reset-options'])) {
			
			if ($_GET['reset-options'] === 'true') : ?>
				
				<div class="notice notice-success is-dismissible"><p><strong><?php esc_html_e('Default options restored.', 'dashboard-widgets-suite'); ?></strong></p></div>
				
			<?php else : ?>
				
				<div class="notice notice-info is-dismissible"><p><strong><?php esc_html_e('No changes made to options.', 'dashboard-widgets-suite'); ?></strong></p></div>
				
			<?php endif;
			
		} elseif (isset($_GET['delete-notes'])) {
			
			if ($_GET['delete-notes'] === 'true') : ?>
				
				<div class="notice notice-success is-dismissible"><p><strong><?php esc_html_e('All User Notes deleted.', 'dashboard-widgets-suite'); ?></strong></p></div>
				
			<?php else : ?>
				
				<div class="notice notice-info is-dismissible"><p><strong><?php esc_html_e('No changes made to User Notes.', 'dashboard-widgets-suite'); ?></strong></p></div>
				
			<?php endif;
			
		}
		
		if (!dashboard_widgets_suite_check_date_expired() && !dashboard_widgets_suite_dismiss_notice_check()) {
			
			$tabs = array('tab1', 'tab2', 'tab3', 'tab4', 'tab5', 'tab6', 'tab7', 'tab8', 'tab9');
			
			$tab = (isset($_GET['tab']) && in_array($_GET['tab'], $tabs)) ? $_GET['tab'] : 'tab1';
			
			?>
			
			<div class="notice notice-success notice-lh">
				<p>
					<strong><?php esc_html_e('Fall Sale!', 'dashboard-widgets-suite'); ?></strong> 
					<?php esc_html_e('Take 25% OFF any of our', 'dashboard-widgets-suite'); ?> 
					<a target="_blank" rel="noopener noreferrer" href="https://plugin-planet.com/"><?php esc_html_e('Pro WordPress plugins', 'dashboard-widgets-suite'); ?></a> 
					<?php esc_html_e('and', 'dashboard-widgets-suite'); ?> 
					<a target="_blank" rel="noopener noreferrer" href="https://books.perishablepress.com/"><?php esc_html_e('books', 'dashboard-widgets-suite'); ?></a>. 
					<?php esc_html_e('Apply code', 'dashboard-widgets-suite'); ?> <code>FALL2025</code> <?php esc_html_e('at checkout. Sale ends 1/11/2026.', 'dashboard-widgets-suite'); ?> 
					<?php echo dashboard_widgets_suite_dismiss_notice_link($tab); ?>
				</p>
			</div>
			
			<?php
			
		}
		
	}
	
}

//

function dashboard_widgets_suite_dismiss_notice_activate() {
	
	delete_option('dashboard-widgets-suite-dismiss-notice');
	
}

function dashboard_widgets_suite_dismiss_notice_version() {
	
	$version_current = DWS_VERSION;
	
	$version_previous = get_option('dashboard-widgets-suite-dismiss-notice');
	
	$version_previous = ($version_previous) ? $version_previous : $version_current;
	
	if (version_compare($version_current, $version_previous, '>')) {
		
		delete_option('dashboard-widgets-suite-dismiss-notice');
		
	}
	
}

function dashboard_widgets_suite_dismiss_notice_check() {
	
	$check = get_option('dashboard-widgets-suite-dismiss-notice');
	
	return ($check) ? true : false;
	
}

function dashboard_widgets_suite_dismiss_notice_save() {
	
	if (isset($_GET['dismiss-notice-verify']) && wp_verify_nonce($_GET['dismiss-notice-verify'], 'dashboard_widgets_suite_dismiss_notice')) {
		
		if (!current_user_can('manage_options')) exit;
		
		$result = update_option('dashboard-widgets-suite-dismiss-notice', DWS_VERSION, false);
		
		$result = $result ? 'true' : 'false';
		
		$tabs = array('tab1', 'tab2', 'tab3', 'tab4', 'tab5', 'tab6', 'tab7', 'tab8', 'tab9');
		
		$tab = (isset($_GET['tab']) && in_array($_GET['tab'], $tabs)) ? $_GET['tab'] : 'tab1';
		
		$location = admin_url('options-general.php?page=dashboard_widgets_suite&tab='. $tab .'&dismiss-notice='. $result);
		
		wp_redirect($location);
		
		exit;
		
	}
	
}

function dashboard_widgets_suite_dismiss_notice_link($tab) {
	
	$nonce = wp_create_nonce('dashboard_widgets_suite_dismiss_notice');
	
	$href  = add_query_arg(array('dismiss-notice-verify' => $nonce), admin_url('options-general.php?page=dashboard_widgets_suite&tab='. $tab));
	
	$label = esc_html__('Dismiss', 'dashboard-widgets-suite');
	
	return '<a class="dws-dismiss-notice" href="'. esc_url($href) .'">'. esc_html($label) .'</a>';
	
}

function dashboard_widgets_suite_check_date_expired() {
	
	$expires = apply_filters('dashboard_widgets_suite_check_date_expired', '2026-01-11');
	
	return (new DateTime() > new DateTime($expires)) ? true : false;
	
}

//

function dashboard_widgets_suite_reset_options() {
	
	if (isset($_GET['reset-options-verify']) && wp_verify_nonce($_GET['reset-options-verify'], 'dws_reset_options')) {
		
		if (!current_user_can('manage_options')) exit;
		
		$update_general     = update_option('dws_options_general',     Dashboard_Widgets_Suite::options_general());
		$update_notes_user  = update_option('dws_options_notes_user',  Dashboard_Widgets_Suite::options_notes_user());
		$update_feed_box    = update_option('dws_options_feed_box',    Dashboard_Widgets_Suite::options_feed_box());
		$update_social_box  = update_option('dws_options_social_box',  Dashboard_Widgets_Suite::options_social_box());
		$update_list_box    = update_option('dws_options_list_box',    Dashboard_Widgets_Suite::options_list_box());
		$update_system_info = update_option('dws_options_system_info', Dashboard_Widgets_Suite::options_system_info());
		$update_log_debug   = update_option('dws_options_log_debug',   Dashboard_Widgets_Suite::options_log_debug());
		$update_log_error   = update_option('dws_options_log_error',   Dashboard_Widgets_Suite::options_log_error());
		$update_widget_box  = update_option('dws_options_widget_box',  Dashboard_Widgets_Suite::options_widget_box());
		
		$result = 'false';
		
		if (
			$update_general     || 
			$update_notes_user  || 
			$update_feed_box    || 
			$update_social_box  || 
			$update_list_box    || 
			$update_system_info || 
			$update_log_debug   || 
			$update_log_error   || 
			$update_widget_box
			
		) $result = 'true';
		
		$location = admin_url('options-general.php?page=dashboard_widgets_suite&reset-options='. $result);
		wp_redirect($location);
		exit;
		
	}
	
}

function dashboard_widgets_suite_delete_notes() {
	
	if (isset($_GET['delete-notes-verify']) && wp_verify_nonce($_GET['delete-notes-verify'], 'dws_delete_notes')) {
		
		$result = false;
		
		if (current_user_can('manage_options')) {
			
			$result = delete_option('dws_notes_user_data');
			
		}
		
		$result = $result ? 'true' : 'false';
		
		$location = admin_url('options-general.php?page=dashboard_widgets_suite&tab=tab2&delete-notes='. $result);
		wp_redirect($location);
		exit;
		
	}
	
}
