<?php
/**
 * File: /wp-content/plugins/aaa-admin-controler-v2/admin/tabs/reports.php
 * Purpose: Reports UI with filters, sorting, pagination, and CSV export (now shows Log ID first)
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
$roles_map = function_exists('aaa_ac_get_all_roles') ? aaa_ac_get_all_roles() : [];
?>
<h2><?php esc_html_e('Reports','aaa-ac'); ?></h2>
<p><?php esc_html_e('Filter by role and date range, then Load. Click column headers to sort.','aaa-ac'); ?></p>

<div class="aaa-ac-toolbar">
	<label for="aaa_ac_reports_role"><?php esc_html_e('Role','aaa-ac'); ?></label>
	<select id="aaa_ac_reports_role" style="min-width:200px;">
		<option value=""><?php esc_html_e('All Roles','aaa-ac'); ?></option>
		<?php foreach($roles_map as $slug=>$label): ?>
			<option value="<?php echo esc_attr($slug); ?>"><?php echo esc_html($label); ?></option>
		<?php endforeach; ?>
	</select>

	<label for="aaa_ac_reports_start"><?php esc_html_e('From','aaa-ac'); ?></label>
	<input type="date" id="aaa_ac_reports_start">
	<label for="aaa_ac_reports_end"><?php esc_html_e('To','aaa-ac'); ?></label>
	<input type="date" id="aaa_ac_reports_end">

	<label for="aaa_ac_reports_per" style="margin-left:12px;"><?php esc_html_e('Per Page','aaa-ac'); ?></label>
	<select id="aaa_ac_reports_per">
		<option>25</option>
		<option selected>50</option>
		<option>100</option>
		<option>200</option>
	</select>

	<button class="button" id="aaa_ac_reports_load"><?php esc_html_e('Load Records','aaa-ac'); ?></button>
	<button class="button button-secondary" id="aaa_ac_reports_export"><?php esc_html_e('Export CSV','aaa-ac'); ?></button>

	<span style="margin-left:auto;color:#666;">
		<span id="aaa_ac_reports_count">0</span>
		&nbsp;&nbsp;
		<button class="button" id="aaa_ac_reports_prev" disabled>&laquo; <?php esc_html_e('Prev','aaa-ac'); ?></button>
		<span id="aaa_ac_reports_page">1 / 1</span>
		<button class="button" id="aaa_ac_reports_next" disabled><?php esc_html_e('Next','aaa-ac'); ?> &raquo;</button>
	</span>
</div>

<table class="widefat striped" id="aaa_ac_reports" style="margin-top:12px;">
	<thead>
	<tr>
		<th data-sort="id"><?php esc_html_e('Log ID','aaa-ac'); ?></th>
		<th data-sort="user_id"><?php esc_html_e('User ID','aaa-ac'); ?></th>
		<th><?php esc_html_e('User','aaa-ac'); ?></th>
		<th data-sort="role_at_login"><?php esc_html_e('Role','aaa-ac'); ?></th>
		<th><?php esc_html_e('IP','aaa-ac'); ?></th>
		<th data-sort="session_token"><?php esc_html_e('Token','aaa-ac'); ?></th>
		<th data-sort="login_time"><?php esc_html_e('Login','aaa-ac'); ?></th>
		<th data-sort="logout_time"><?php esc_html_e('Logout','aaa-ac'); ?></th>
		<th data-sort="end_trigger"><?php esc_html_e('Trigger','aaa-ac'); ?></th>
		<th><?php esc_html_e('Auto','aaa-ac'); ?></th>
		<th><?php esc_html_e('User Action','aaa-ac'); ?></th>
		<th data-sort="is_online"><?php esc_html_e('Status','aaa-ac'); ?></th>
	</tr>
	</thead>
	<tbody id="aaa_ac_reports_rows">
	<tr><td colspan="12" style="text-align:center;color:#777;"><?php esc_html_e('No records. Click Load.','aaa-ac'); ?></td></tr>
	</tbody>
</table>
