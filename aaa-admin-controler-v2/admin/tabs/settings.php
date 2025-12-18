<?php
/**
 * File: /wp-content/plugins/aaa-admin-controler-v2/admin/tabs/settings.php
 * Purpose: Roles to Control + Cron Schedule + per-user schedule/popup grid
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
$all = aaa_ac_get_all_roles();
$on  = aaa_ac_get_enabled_roles();
$cron_mode = get_site_option( 'aaa_ac_cron_mode', 'dev' );
?>
<?php if ( isset($_GET['updated']) ): ?>
	<div class="updated notice"><p><?php esc_html_e('Settings saved.','aaa-ac'); ?></p></div>
<?php endif; ?>
<?php if ( isset($_GET['cronupdated']) ): ?>
	<div class="updated notice"><p><?php esc_html_e('Cron schedule updated.','aaa-ac'); ?></p></div>
<?php endif; ?>

<h2><?php esc_html_e('Roles to Control','aaa-ac'); ?></h2>
<p><?php esc_html_e('Choose which roles are affected by session controls. Customers should remain unchecked.','aaa-ac'); ?></p>

<form method="post" action="<?php echo esc_url( network_admin_url('edit.php?action=aaa_ac_save_roles') ); ?>">
	<?php wp_nonce_field('aaa_ac_save_roles'); ?>
	<select name="aaa_roles[]" multiple style="min-width:320px;height:200px;">
		<?php foreach($all as $slug=>$label): ?>
			<option value="<?php echo esc_attr($slug); ?>" <?php selected( in_array($slug,$on,true) ); ?>>
				<?php echo esc_html($label); ?>
			</option>
		<?php endforeach; ?>
	</select>
	<p><button class="button button-primary" type="submit"><?php esc_html_e('Save','aaa-ac'); ?></button></p>
</form>

<hr>

<h2><?php esc_html_e('Cron Schedule','aaa-ac'); ?></h2>
<p>
	<?php esc_html_e('Select how often to check and enforce Forced Session Ending Times. In LIVE, use 15-minute increments (e.g., 09:00, 09:15, 09:30, 09:45).','aaa-ac'); ?>
</p>
<form method="post" action="<?php echo esc_url( network_admin_url('edit.php?action=aaa_ac_save_cron') ); ?>" style="margin-bottom:10px;">
	<?php wp_nonce_field('aaa_ac_save_cron'); ?>
	<label style="margin-right:16px;">
		<input type="radio" name="aaa_ac_cron_mode" value="dev" <?php checked($cron_mode,'dev'); ?>>
		<?php esc_html_e('Every Minute — DEVELOPMENT','aaa-ac'); ?>
	</label>
	<label>
		<input type="radio" name="aaa_ac_cron_mode" value="live" <?php checked($cron_mode,'live'); ?>>
		<?php esc_html_e('Every 15 Minutes — LIVE','aaa-ac'); ?>
	</label>
	<p>
		<button class="button button-primary" type="submit"><?php esc_html_e('Save Cron Schedule','aaa-ac'); ?></button>
		<span style="margin-left:10px;color:#666;"><?php printf( esc_html__('Site Time now: %s (%s)','aaa-ac'), esc_html( wp_date('H:i') ), esc_html( wp_timezone_string() ) ); ?></span>
	</p>
</form>

<h2><?php esc_html_e('Per-User Schedule & Popup','aaa-ac'); ?></h2>
<p><?php esc_html_e('Load users by role to set forced session end times and popup checks (CSV HH:MM, 24-hour).','aaa-ac'); ?></p>

<div class="aaa-ac-toolbar">
	<label for="aaa_ac_settings_role"><?php esc_html_e('Role','aaa-ac'); ?></label>
	<select id="aaa_ac_settings_role" <?php disabled( empty($on) ); ?>>
		<?php if ( empty($on) ): ?>
			<option value=""><?php esc_html_e('No roles enabled above','aaa-ac'); ?></option>
		<?php else: foreach($on as $slug): ?>
			<option value="<?php echo esc_attr($slug); ?>"><?php echo esc_html( $all[$slug] ?? $slug ); ?></option>
		<?php endforeach; endif; ?>
	</select>

	<button id="aaa_ac_settings_load" class="button" <?php disabled( empty($on) ); ?>><?php esc_html_e('Load Users','aaa-ac'); ?></button>
	<button id="aaa_ac_settings_clear" class="button"><?php esc_html_e('Clear','aaa-ac'); ?></button>
	<button id="aaa_ac_settings_save" class="button button-primary" disabled><?php esc_html_e('Save Changes','aaa-ac'); ?></button>

	<label style="margin-left:12px;"><?php esc_html_e('Site Time','aaa-ac'); ?></label>
	<input type="text" id="aaa_ac_now_site" class="aaa-ac-now aaa-ac-now-site" readonly
	       value="<?php echo esc_attr( sprintf('%s (%s)', wp_date('H:i'), wp_timezone_string() ) ); ?>"
	       title="<?php esc_attr_e('Current time in the site timezone (used by scheduler)', 'aaa-ac'); ?>">

	<label style="margin-left:12px;"><?php esc_html_e('Local Time','aaa-ac'); ?></label>
	<input type="text" id="aaa_ac_now_local" class="aaa-ac-now aaa-ac-now-local" readonly value=""
	       title="<?php esc_attr_e('Your browser/device time', 'aaa-ac'); ?>">
</div>

<table class="widefat striped" style="margin-top:12px;">
	<thead><tr>
		<th>#</th>
		<th><?php esc_html_e('User','aaa-ac'); ?></th>
		<th><?php esc_html_e('User ID','aaa-ac'); ?></th>
		<th><?php esc_html_e('Forced Session Ending Times (CSV)','aaa-ac'); ?></th>
		<th><?php esc_html_e('Trigger Popup User Check (CSV)','aaa-ac'); ?></th>
		<th><?php esc_html_e('Include in Session Logs','aaa-ac'); ?></th>
	</tr></thead>
	<tbody id="aaa_ac_settings_rows">
		<tr><td colspan="6" style="text-align:center;color:#777;">
			<?php echo empty($on) ? esc_html__('Enable roles above first.','aaa-ac') : esc_html__('No users loaded.','aaa-ac'); ?>
		</td></tr>
	</tbody>
</table>
