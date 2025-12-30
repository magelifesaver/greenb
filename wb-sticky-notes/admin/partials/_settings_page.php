<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
?>
<div class="wb_stn_content">
	<h2><?php esc_html_e('General Settings', 'wb-sticky-notes'); ?></h2>
	<form method="post">
		<?php
		if (function_exists('wp_nonce_field'))
		{
		    wp_nonce_field(WB_STN_SETTINGS);
		}
		if(isset($_GET['wb-suss']))
		{
			echo '<div class="updated"><p>'. esc_html__('Settings Updated.', 'wb-sticky-notes').'</p></div>';
		}
		?>
		<table class="wb_stn_form-table form-table">
			<tr>
				<th scope="row"><?php esc_html_e('Enable sticky notes', 'wb-sticky-notes'); ?>
				</th>
				<td>
					<div class="wb_stn_radio_field_main">
						<input type="radio" name="wb_stn[enable]" value="1" <?php echo $the_settings['enable']==1 ? 'checked' : '';?> /> <?php _e('Enable', 'wb-sticky-notes'); ?>
					</div>
					<div class="wb_stn_radio_field_main">
						<input type="radio" name="wb_stn[enable]" value="0" <?php echo $the_settings['enable']==0 ? 'checked' : '';?> /> <?php _e('Disable', 'wb-sticky-notes'); ?>
					</div>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e('User roles to uses sticky notes', 'wb-sticky-notes'); ?></th>
				<td>
					<?php
					foreach(get_editable_roles() as $role_name => $role_info)
					{
						if('subscriber'===$role_name || 'customer'===$role_name) {
							continue;
						}
					?>
						<div class="wb_stn_font_preview_small_main">
							<div class="wb_stn_radio_field">
								<input type="checkbox" name="wb_stn[role_name][]" id="wb_stn_role_name_<?php echo esc_attr($role_name);?>" value="<?php echo esc_attr($role_name);?>" <?php echo in_array($role_name, $the_settings['role_name']) ? 'checked' : '';?> <?php echo esc_attr('administrator' === $role_name ? 'disabled' : ''); ?>>
								<label style="width:auto; font-weight:normal; <?php echo esc_attr('administrator' === $role_name ? 'opacity:.7; cursor:default; ' : ''); ?>" for="wb_stn_role_name_<?php echo esc_attr($role_name);?>"><?php echo $role_info['name'];?></label>
							</div>
						</div>
						<?php
					}
					?>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php esc_html_e('Hide sticky notes from these admin pages', 'wb-sticky-notes'); ?></th>
				<td>
					<select name="wb_stn[hide_on_these_pages][]" id="wb_stn_hide_on_these_pages" multiple>
						<?php
						$admin_screens = array(
						    // WordPress Admin Screens.
						    'dashboard'               => 'Dashboard',
						    'edit-post'               => 'Posts',
						    'post'                    => 'Add/Edit Post',
						    'edit-category'           => 'Categories',
						    'edit-post_tag'           => 'Tags',
						    'upload'                  => 'Media Library',
						    'media'                   => 'Add/Edit Media',
						    'edit-page'               => 'Pages',
						    'page'                    => 'Add/Edit Page',
						    'edit-comments'           => 'Comments',
						    'themes'                  => 'Appearance',
						    'widgets'                 => 'Widgets',
						    'nav-menus'               => 'Menus',
						    'plugins'                 => 'Plugins',
						    'plugin-install'          => 'Add New Plugin',
						    'users'                   => 'Users',
						    'user'                    => 'Add/Edit User',
						    'tools'                   => 'Tools',
						    'import'                  => 'Import',
						    'export'                  => 'Export',
						    'options-general'         => 'General Settings',
						    'options-writing'         => 'Writing Settings',
						    'options-reading'         => 'Reading Settings',
						    'options-discussion'      => 'Discussion Settings',
						    'options-media'           => 'Media Settings',
						    'options-permalink'       => 'Permalink Settings',

						    // WooCommerce Admin Screens.
						    'woocommerce_page_wc-admin'   => 'WooCommerce Dashboard',
						    'edit-shop_order'             => 'Orders',
						    'shop_order'                  => 'Add/Edit Order',
						    'edit-product'                => 'Products',
						    'product'                     => 'Add/Edit Product',
						    'edit-shop_coupon'            => 'Coupons',
						    'shop_coupon'                 => 'Add/Edit Coupon',
						    'woocommerce_page_wc-reports' => 'Reports',
						    'woocommerce_page_wc-settings'=> 'Settings',
						    'woocommerce_page_wc-status'  => 'System Status',
						    'woocommerce_page_wc-addons'  => 'Extensions',

						    // WooCommerce Subscriptions.
						    'edit-shop_subscription'      => 'Subscriptions',
						    'shop_subscription'           => 'Add/Edit Subscription',

						    // WooCommerce Product tabs.
						    'settings_page_wb-product-tab-settings'      => 'Tab settings',
						    'edit-wb-custom-tabs'      => 'Global tabs',
						    'wb-custom-tabs'           => 'Add/Edit Global tabs',

						    // WordPress email logger.
						    'tools_page_wb-mail-logger'=> 'WordPress email log page',
						);

						foreach ($admin_screens as $admin_screen_id => $admin_screen_name) {
							?>
							<option value="<?php echo esc_attr( $admin_screen_id );?>" <?php selected( true, in_array( $admin_screen_id, $the_settings['hide_on_these_pages'] ) ); ?>>
								<?php echo esc_html( $admin_screen_name );?>		
							</option>
							<?php
						}
						?>
					</select>
					<p class="description"><?php esc_html_e('If the page you want to hide the sticky note on is not listed here, you can create a code snippet using the wb_stn_hide_on_these_pages filter to hide the sticky note from that page. Note: Sticky notes cannot be hidden on the Sticky Notes settings pages.', 'wb-sticky-notes'); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e('Default theme', 'wb-sticky-notes'); ?></th>
				<td>
					<?php
					foreach(Wb_Sticky_Notes::$themes as $colork=>$color)
					{
						?>
						<div class="wb_stn_preview_small_main">
							<div class="wb_stn_radio_field">
								<input type="radio" name="wb_stn[theme]" value="<?php echo esc_attr($colork);?>" <?php checked( $the_settings['theme'], $colork );?> >
							</div>
							<div class="wb_stn_preview_small wb_stn_<?php echo esc_attr($color);?>">
								<div class="wb_stn_note_hd"></div>	
								<div class="wb_stn_note_body"></div>	
							</div>
						</div>
						<?php
					}
					?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php _e('Default font', 'wb-sticky-notes'); ?></th>
				<td>
					<?php
					foreach(Wb_Sticky_Notes::$fonts as $fontk=>$font)
					{
					?>
						<div class="wb_stn_font_preview_small_main">
							<div class="wb_stn_radio_field">
								<input type="radio" name="wb_stn[font_family]" value="<?php echo esc_attr($fontk);?>" <?php checked($the_settings['font_family'], $fontk);?> >
							</div>
							<div class="wb_stn_font_preview_small wb_stn_font_<?php echo esc_attr($font);?>">
								Sample text
							</div>
						</div>
						<?php
					}
					?>
				</td>
			</tr>
			<tr>
				<th scope="row"></th>
				<td>
					<input type="submit" class="button button-primary" name="wb_stn_update_settings" value="<?php esc_attr_e('Save', 'wb-sticky-notes'); ?>">
				</td>
			</tr>
		</table>
	</form>
</div>