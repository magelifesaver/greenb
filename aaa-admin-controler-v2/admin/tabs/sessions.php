<?php
/**
 * File: /wp-content/plugins/aaa-admin-controler-v2/admin/tabs/sessions.php
 *
 * Purpose: Realtime Sessions tab. This updated version introduces an
 * optional filter allowing administrators to load only users who are
 * currently online. When the “Only Online” checkbox is checked the AJAX
 * request passes a flag so the server can skip offline users. The table
 * structure remains unchanged but the data set may be significantly
 * smaller when filtering is enabled.
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
</div>

<table class="widefat striped" style="margin-top:12px;">
        <thead><tr>
                <th><?php esc_html_e('Log ID','aaa-ac'); ?></th>
                <th>#</th>
                <th><?php esc_html_e('User','aaa-ac'); ?></th>
                <th><?php esc_html_e('User ID','aaa-ac'); ?></th>
                <th><?php esc_html_e('Session Start','aaa-ac'); ?></th>
                <th><?php esc_html_e('Status','aaa-ac'); ?></th>
                <th><?php esc_html_e('Action','aaa-ac'); ?></th>
                <th><?php esc_html_e('Token','aaa-ac'); ?></th>
        </tr></thead>
        <tbody id="aaa_ac_rows"><tr><td colspan="8" style="text-align:center;color:#777;">
                <?php echo empty($enabled_roles) ? esc_html__('Enable roles in Settings first.','aaa-ac') : esc_html__('No users loaded.','aaa-ac'); ?>
        </td></tr></tbody>
</table>