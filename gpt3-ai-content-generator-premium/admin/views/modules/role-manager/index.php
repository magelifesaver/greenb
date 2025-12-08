<?php

/**
 * AIPKit Role Manager Module - Admin View
 *
 * Allows administrators to assign module access permissions to different user roles.
 * @since NEXT_VERSION
 */

use WPAICG\AIPKit_Role_Manager; // Use the Role Manager class

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Fetch necessary data using the Role Manager class
$modules     = AIPKit_Role_Manager::get_manageable_modules();
$roles       = AIPKit_Role_Manager::get_editable_roles();
$permissions = AIPKit_Role_Manager::get_role_permissions(); // Get saved permissions ['module_slug' => ['role1', 'role2']]

// Ensure roles are sorted logically (e.g., Admin first) - optional but nice UX
$role_order = ['administrator', 'editor', 'author', 'contributor', 'subscriber']; // Define desired order
$sorted_roles = [];
foreach ($role_order as $role_key) {
    if (isset($roles[$role_key])) {
        $sorted_roles[$role_key] = $roles[$role_key];
        unset($roles[$role_key]); // Remove from original array
    }
}
// Add any remaining roles (custom roles) alphabetically
ksort($roles);
$sorted_roles = array_merge($sorted_roles, $roles);

// Prepare nonce for saving
$nonce = wp_create_nonce('aipkit_role_manager_nonce');

?>
<div class="aipkit_container aipkit_role_manager_container" id="aipkit_role_manager_container">
    <div class="aipkit_container-header">
        <div class="aipkit_container-title"><?php esc_html_e('Role Manager', 'gpt3-ai-content-generator'); ?></div>
        <div id="aipkit_role_manager_messages" class="aipkit_settings_messages">
            <!-- Status messages go here -->
        </div>
        <div class="aipkit_container-actions">
            <button id="aipkit_save_roles_btn" class="aipkit_btn aipkit_btn-primary">
                <span class="aipkit_btn-text"><?php esc_html_e('Save Permissions', 'gpt3-ai-content-generator'); ?></span>
                <span class="aipkit_spinner" style="display:none;"></span>
            </button>
        </div>
    </div>
    <div class="aipkit_container-body">
        <p><?php esc_html_e('Select which user roles should have access to each AI Power module.', 'gpt3-ai-content-generator'); ?></p>
        <form id="aipkit_role_manager_form">
            <input type="hidden" name="_ajax_nonce" value="<?php echo esc_attr($nonce); ?>">
            <table class="aipkit_data-table aipkit_role_manager_table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Module', 'gpt3-ai-content-generator'); ?></th>
                        <?php foreach ($sorted_roles as $role_slug => $role_name): ?>
                            <th class="aipkit_role_header"><?php echo esc_html(translate_user_role($role_name)); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($modules as $module_slug => $module_name): ?>
                        <tr>
                            <td><?php echo esc_html($module_name); ?></td>
                            <?php foreach ($sorted_roles as $role_slug => $role_name):
                                $allowed_roles_for_module = isset($permissions[$module_slug]) && is_array($permissions[$module_slug]) ? $permissions[$module_slug] : ['administrator'];
                                $is_checked = in_array($role_slug, $allowed_roles_for_module, true);
                                // Administrator role is always checked and disabled
                                $is_disabled = ($role_slug === 'administrator');
                                $checkbox_id = 'aipkit_perm_' . esc_attr($module_slug) . '_' . esc_attr($role_slug);
                                $checkbox_name = 'permissions[' . esc_attr($module_slug) . '][' . esc_attr($role_slug) . ']';
                            ?>
                                <td class="aipkit_role_cell">
                                    <input
                                        type="checkbox"
                                        id="<?php echo esc_attr( $checkbox_id ); ?>"
                                        name="<?php echo esc_attr( $checkbox_name ); ?>"
                                        value="1"
                                        <?php checked($is_checked); ?>
                                        <?php disabled($is_disabled); ?>
                                        <?php if ($is_disabled) : ?>
                                        title="<?php esc_attr_e('Administrators always have access.', 'gpt3-ai-content-generator'); ?>"
                                        <?php endif; ?>
                                    />
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </form>
    </div>
</div>