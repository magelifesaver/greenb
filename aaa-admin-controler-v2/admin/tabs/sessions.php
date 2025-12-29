<?php
/**
 * File: /wp-content/plugins/aaa-admin-controler-v2/admin/tabs/sessions.php
 *
 * Purpose: Realtime Sessions tab with extended functionality. This version adds
 * bulk selection, last activity, customer and cart indicators. Administrators
 * can choose a role, optionally filter to only online users, and then select
 * individual or multiple rows to end sessions via AJAX without reloading the
 * entire page. A “Select All” checkbox aids quick bulk actions. Additional
 * columns display the last activity timestamp, whether the user is marked
 * customer/paying customer, and if the session currently contains items in
 * the WooCommerce cart.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$enabled_roles = aaa_ac_get_enabled_roles();
$all_roles     = aaa_ac_get_all_roles();
?>
<?php if ( isset($_GET['ended']) ): ?>
    <div class="updated notice"><p><?php esc_html_e('Session ended.','aaa-ac'); ?></p></div>
<?php endif; ?>

<h2><?php esc_html_e('Realtime Sessions','aaa-ac'); ?></h2>
<p><?php esc_html_e('Pick a role and click Load Users. No auto‑refresh (keeps load minimal).','aaa-ac'); ?></p>

<div class="aaa-ac-toolbar">
    <label for="aaa_ac_role"><?php esc_html_e('Role','aaa-ac'); ?></label>
    <select id="aaa_ac_role" <?php disabled( empty($enabled_roles) ); ?>>
        <?php if ( empty($enabled_roles) ): ?>
            <option value=""><?php esc_html_e('No roles enabled in Settings','aaa-ac'); ?></option>
        <?php else: foreach($enabled_roles as $slug): ?>
            <option value="<?php echo esc_attr($slug); ?>"><?php echo esc_html( $all_roles[$slug] ?? $slug ); ?></option>
        <?php endforeach; endif; ?>
    </select>
    <button id="aaa_ac_load" class="button" <?php disabled( empty($enabled_roles) ); ?>><?php esc_html_e('Load Users','aaa-ac'); ?></button>
    <button id="aaa_ac_clear" class="button"><?php esc_html_e('Clear','aaa-ac'); ?></button>
    <label style="margin-left:12px;"><input type="checkbox" id="aaa_ac_online_only"> <?php esc_html_e('Only Online','aaa-ac'); ?></label>
    <button id="aaa_ac_end_selected" class="button button-secondary" style="margin-left:12px;"><?php esc_html_e('End Selected','aaa-ac'); ?></button>
    <label style="margin-left:12px;"><input type="checkbox" id="aaa_ac_select_all"> <?php esc_html_e('Select All','aaa-ac'); ?></label>
</div>

<table class="widefat striped" style="margin-top:12px;">
    <thead><tr>
        <th></th>
        <th>#</th>
        <th><?php esc_html_e('User','aaa-ac'); ?></th>
        <th><?php esc_html_e('User ID','aaa-ac'); ?></th>
        <th><?php esc_html_e('Session Start','aaa-ac'); ?></th>
        <th><?php esc_html_e('Last Activity','aaa-ac'); ?></th>
        <th><?php esc_html_e('Duration','aaa-ac'); ?></th>
        <th><?php esc_html_e('Status','aaa-ac'); ?></th>
        <th><?php esc_html_e('Customer','aaa-ac'); ?></th>
        <th><?php esc_html_e('Cart','aaa-ac'); ?></th>
        <th><?php esc_html_e('Action','aaa-ac'); ?></th>
        <th><?php esc_html_e('Token','aaa-ac'); ?></th>
    </tr></thead>
    <tbody id="aaa_ac_rows"><tr><td colspan="12" style="text-align:center;color:#777;">
        <?php echo empty($enabled_roles) ? esc_html__('Enable roles in Settings first.','aaa-ac') : esc_html__('No users loaded.','aaa-ac'); ?>
    </td></tr></tbody>
</table>
