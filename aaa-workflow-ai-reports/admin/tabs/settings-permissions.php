<?php
/**
 * ============================================================================
 * File Path: /aaa-workflow-ai-reports/admin/tabs/settings-permissions.php
 * Description: Tab 2 — Manage which user roles can access AAA Workflow AI Reports.
 * Dependencies: admin_post_aaa_wf_ai_save_permissions action, options-helpers.php
 * File Version: 1.0.0
 * Updated: 2025-12-28
 * ============================================================================
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$roles_allowed = aaa_wf_ai_get_option( 'aaa_wf_ai_allowed_roles', [ 'administrator', 'shop_manager' ] );
$all_roles     = wp_roles()->roles;

// Handle success notice
if ( isset( $_GET['updated'] ) ) {
    echo '<div class="notice notice-success is-dismissible"><p>Permissions updated successfully.</p></div>';
}
?>
<h2>User Access Permissions</h2>
<p>Select which user roles are allowed to view and use the AI Reports dashboard.</p>
<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
    <input type="hidden" name="action" value="aaa_wf_ai_save_permissions">
    <?php wp_nonce_field( 'aaa_wf_ai_save_permissions' ); ?>
    <table class="form-table">
        <tr>
            <th scope="row">Allowed Roles</th>
            <td>
                <?php foreach ( $all_roles as $role_key => $role_data ) : ?>
                    <label>
                        <input type="checkbox" name="aaa_wf_ai_allowed_roles[]" value="<?php echo esc_attr( $role_key ); ?>"
                               <?php checked( in_array( $role_key, (array) $roles_allowed, true ) ); ?>>
                        <?php echo esc_html( $role_data['name'] ); ?>
                    </label><br>
                <?php endforeach; ?>
                <p class="description">Only these roles will see the <strong>AI Reports</strong> menu.</p>
            </td>
        </tr>
    </table>
    <?php submit_button( 'Save Permissions' ); ?>
</form>
<?php aaa_wf_ai_debug( 'Rendered Permissions tab.', basename( __FILE__ ) ); ?>