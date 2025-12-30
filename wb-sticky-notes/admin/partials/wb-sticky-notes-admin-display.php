<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://wordpress.org/plugins/wb-sticky-notes
 * @since      1.0.0
 *
 * @package    Wb_Sticky_Notes
 * @subpackage Wb_Sticky_Notes/admin/partials
 */ 
?>
<div class="wrap">
	<?php 
	if('archives' === $tab)
	{
		require_once plugin_dir_path( __FILE__ ).'_archives_page.php';
	}else
	{
		require_once plugin_dir_path( __FILE__ ).'settings.php';
	}
	?>
	<div style="float:left; margin-top:25px; width:100%;">
		<div style="float:left; font-weight:bold; font-size:18px; width:100%;"><?php _e('Our free plugins', 'wb-sticky-notes'); ?></div>
			<div style="float:left; width:99%; margin-left:1%; margin-top:15px; border:solid 1px #ccc; background:#fff; padding:15px; box-sizing:border-box;">
				<div style="float:left; margin-bottom:0px; width:100%;">
					<div style="float:left; font-weight:bold; font-size:18px; width:100%;">
						<a href="https://webbuilder143.com/woocommerce-custom-product-tabs/" target="_blank" style="text-decoration:none;"><?php _e('Custom Product Tabs For WooCommerce', 'wb-sticky-notes'); ?></a>
					</div>
					<div style="float:left; font-size:13px; width:100%;">
						<ul style="list-style:none;">
							<li>
								<span style="color:green;" class="dashicons dashicons-yes-alt"></span> <?php _e('Add unlimited number of custom product tabs to WooCommerce products.', 'wb-sticky-notes');?>
							</li>
							<li>
								<span style="color:green;" class="dashicons dashicons-yes-alt"></span> <?php _e('Use the Global Tab option to add product tabs to products by selecting individual products, categories, tags, or brands.', 'wb-sticky-notes');?>
							</li>
							<li>
								<span style="color:green;" class="dashicons dashicons-yes-alt"></span> <?php _e('Tab position re-arrange option.', 'wb-sticky-notes');?>
							</li>
							<li>
								<span style="color:green;" class="dashicons dashicons-yes-alt"></span> <?php _e('Shortcode support in tab content.', 'wb-sticky-notes');?>
							</li>
							<li>
								<span style="color:green;" class="dashicons dashicons-yes-alt"></span> <?php _e('Youtube embed option.', 'wb-sticky-notes');?>
							</li>
							<li>
								<span style="color:green;" class="dashicons dashicons-yes-alt"></span> <?php _e('Filters for developers to alter tab content and position.', 'wb-sticky-notes');?>
							</li>
						</ul>
						<a href="https://wordpress.org/plugins/wb-custom-product-tabs-for-woocommerce/" target="_blank" class="button button-primary"><?php _e('Get the plugin now', 'wb-sticky-notes');?></a>
					</div>
				</div>
			</div>

			<div style="float:left; width:99%; margin-left:1%; margin-top:15px; border:solid 1px #ccc; background:#fff; padding:15px; box-sizing:border-box;">
				<div style="float:left; margin-bottom:0px; width:100%;">
					<div style="float:left; font-weight:bold; font-size:18px; width:100%;">
						<a href="https://webbuilder143.com/mail-logger-for-wordpress/" target="_blank" style="text-decoration:none;"><?php _e('Email logger for WordPress', 'wb-sticky-notes'); ?></a>
					</div>
					<div style="float:left; font-size:13px; width:100%;">
						<ul style="list-style:none;">
							<li>
								<span style="color:green;" class="dashicons dashicons-yes-alt"></span> <?php _e('Save all WordPress emails in the dashboard', 'wb-sticky-notes');?>
							</li>
							<li>
								<span style="color:green;" class="dashicons dashicons-yes-alt"></span> <?php _e('Check email sender, receiver, attachments, send status, send time etc from the dashboard.', 'wb-sticky-notes');?>
							</li>
							<li>
								<span style="color:green;" class="dashicons dashicons-yes-alt"></span> <?php _e('Read all sent/failed emails from WP dashboard.', 'wb-sticky-notes');?>
							</li>
							<li>
								<span style="color:green;" class="dashicons dashicons-yes-alt"></span> <?php _e('Option to resend emails.', 'wb-sticky-notes');?>
							</li>
						</ul>
						<a href="https://wordpress.org/plugins/wb-mail-logger/" target="_blank" class="button button-primary"><?php _e('Get the plugin now', 'wb-sticky-notes');?></a>
					</div>
				</div>
			</div>
	</div>

	<div style="background-color: #6d4cb7; color: white; padding: 20px; text-align: center; border-radius: 8px; font-family: Arial, sans-serif; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); margin-top:50px; clear:both; float:left; width:100%; box-sizing:border-box;">
		<h2 style="font-size: 24px; margin: 0; font-weight: bold; color: white;">Support Our Plugin</h2>

		<p style="font-size: 16px; margin-top: 20px;">
		<strong>Click <a href="https://wordpress.org/support/plugin/wb-sticky-notes/reviews/?rate=5#new-post" target="_blank" style="text-decoration:none; font-weight:bold; color:#09f309;">here</a> to rate us ⭐️⭐️⭐️⭐️⭐️,</strong> if you like the Admin Sticky notes plugin!
		</p>

		<p style="font-size: 14px; margin-top: 15px;">Thank you for your support!</p>
	</div>
</div>