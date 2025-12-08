<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/views/dashboard.php
// Status: MODIFIED

// Silence direct access
if (!defined('ABSPATH')) {
    exit;
}

// Use the Dashboard_Beta class to retrieve module settings
use WPAICG\aipkit_dashboard;
use WPAICG\AIPKit_Role_Manager; // <-- Import Role Manager
use WPAICG\WP_AI_Content_Generator_Activator; // ADDED for migration check

// Retrieve the currently saved module settings
$moduleSettings = aipkit_dashboard::get_module_settings();

/**
 * Map each DB key to the nav label, dashicon, and the data-module attribute used for loading.
 * The data_module must match the folder name in /modules/ (e.g. 'chatbot', 'content-writer', etc.)
 * **AND** must match the keys used in AIPKit_Role_Manager::get_manageable_modules() for permission checks.
 */
$modulesMap = array(
    'chat_bot' => array(
        'label'       => __('Chat', 'gpt3-ai-content-generator'),
        'icon'        => 'format-chat',
        'data_module' => 'chatbot',
    ),
    'content_writer' => array(
        'label'       => __('Write', 'gpt3-ai-content-generator'),
        'icon'        => 'edit', // Changed from 'edit-page' to 'edit'
        'data_module' => 'content-writer',
    ),
    'autogpt' => array(
        'label'       => __('Automate', 'gpt3-ai-content-generator'),
        'icon'        => 'airplane',
        'data_module' => 'autogpt',
    ),
    'ai_forms' => array( // ADDED AI Forms
        'label'       => __('Forms', 'gpt3-ai-content-generator'),
        'icon'        => 'feedback', // Using 'feedback' icon for forms
        'data_module' => 'ai-forms',
    ),
    'image_generator' => array(
        'label'       => __('Images', 'gpt3-ai-content-generator'),
        'icon'        => 'format-image',
        'data_module' => 'image-generator',
    ),
    'training' => array(
        'label'       => __('Train', 'gpt3-ai-content-generator'),
        'icon'        => 'welcome-learn-more',
        'data_module' => 'ai-training',
    ),
    'ai_account' => array(
        'label'       => __('Credits', 'gpt3-ai-content-generator'),
        'icon'        => 'tickets-alt',
        'data_module' => 'user-credits',
    ),
    'logs_viewer' => array(
        'label'       => __('Logs', 'gpt3-ai-content-generator'),
        'icon'        => 'list-view',
        'data_module' => 'logs',
    ),
);

// Create a nonce for AJAX requests
$aipkit_nonce = wp_create_nonce('aipkit_nonce');

// --- ADDED: Logic to show migration notice ---
$show_migration_notice = false;
if (current_user_can('manage_options') && class_exists(WP_AI_Content_Generator_Activator::class)) {
    $migration_status = get_option(WP_AI_Content_Generator_Activator::MIGRATION_STATUS_OPTION);
    $data_exists = get_option(WP_AI_Content_Generator_Activator::MIGRATION_DATA_EXISTS_OPTION, false);
    $notice_dismissed = get_option('aipkit_migration_notice_dismissed', '0') === '1';

    if ($data_exists && !$notice_dismissed && !in_array($migration_status, ['completed', 'not_applicable', 'fresh_install_chosen'], true)) {
        $show_migration_notice = true;
        $migration_tool_url = admin_url('admin.php?page=aipkit-migration-tool');
    }
}
// --- END ADDED ---

?>
<div class="wrap aipkit_wrap">
    <header class="aipkit_top-nav" id="aipkit_top_nav">
        <!-- Far Left: Brand -->
        <div class="aipkit_brand">
            <div class="aipkit_brand-logo">AIP</div>
        </div>

        <!-- Navigation Links Container (for desktop and mobile dropdown) -->
        <nav class="aipkit_nav-links-container" id="aipkit_nav_links_container">
            <!-- Settings/Dashboard Link -->
            <?php if (AIPKit_Role_Manager::user_can_access_module('settings')): ?>
                <a
                    href="javascript:void(0);"
                    class="aipkit_nav-item aipkit_module-link"
                    data-module="settings"
                    onclick="aipkit_loadModule('settings');">
                    <span class="dashicons dashicons-admin-generic"></span>
                    <?php echo esc_html__('Dashboard', 'gpt3-ai-content-generator'); ?>
                </a>
            <?php endif; ?>
            <!-- Addons Link -->
            <?php if (AIPKit_Role_Manager::user_can_access_module('addons')): ?>
                 <a
                    href="javascript:void(0);"
                    class="aipkit_nav-item aipkit_module-link"
                    data-module="addons"
                    onclick="aipkit_loadModule('addons');">
                    <span class="dashicons dashicons-admin-plugins"></span>
                    <?php echo esc_html__('Add-ons', 'gpt3-ai-content-generator'); ?>
                </a>
            <?php endif; ?>
            <!-- Individual Module Nav Items -->
            <?php foreach ($modulesMap as $optionKey => $mod) :
                $module_slug = $mod['data_module'];
                $is_enabled = !isset($moduleSettings[$optionKey]) || !empty($moduleSettings[$optionKey]);
                if ($is_enabled && AIPKit_Role_Manager::user_can_access_module($module_slug)): ?>
                <a
                    href="javascript:void(0);"
                    class="aipkit_nav-item aipkit_module-link"
                    data-module="<?php echo esc_attr($module_slug); ?>"
                    onclick="aipkit_loadModule('<?php echo esc_js($module_slug); ?>')"
                >
                    <span class="dashicons dashicons-<?php echo esc_attr($mod['icon']); ?>"></span>
                    <?php echo esc_html($mod['label']); ?>
                </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>

        <!-- Far Right: Controls -->
        <div class="aipkit_nav-controls">
            <!-- Mobile Nav Toggle -->
            <button
                type="button"
                class="aipkit_mobile-nav-toggle dashicons dashicons-menu-alt3"
                aria-label="<?php echo esc_attr__('Toggle Navigation', 'gpt3-ai-content-generator'); ?>"
                aria-expanded="false"
                aria-controls="aipkit_nav_links_container"
            ></button>
            <!-- Modules Menu (Modern Toggle) -->
            <?php if (current_user_can('manage_options')): ?>
                <div
                    class="aipkit_modules-menu"
                    id="aipkit_modulesMenu"
                    title="<?php echo esc_attr__('Module Settings', 'gpt3-ai-content-generator'); ?>"
                >
                    <button class="aipkit_menu-trigger" type="button" aria-label="<?php echo esc_attr__('Module Settings', 'gpt3-ai-content-generator'); ?>">
                        <svg class="aipkit_menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="3"></circle>
                            <path d="m19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1 1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                        </svg>
                    </button>
                    <div class="aipkit_dropdown-panel">
                        <div class="aipkit_panel-header">
                            <h3><?php echo esc_html__('Module Settings', 'gpt3-ai-content-generator'); ?></h3>
                            <p><?php echo esc_html__('Enable or disable modules to customize your workspace.', 'gpt3-ai-content-generator'); ?></p>
                        </div>
                        <div class="aipkit_modules-list">
                            <?php foreach ($modulesMap as $optionKey => $mod) :
                                $checked = !isset($moduleSettings[$optionKey]) || !empty($moduleSettings[$optionKey]) ? 'checked' : '';
                                $inputId = 'aipkit_toggle_' . esc_attr($optionKey);
                            ?>
                                <div class="aipkit_module-item">
                                    <div class="aipkit_module-info">
                                        <span class="aipkit_module-icon dashicons dashicons-<?php echo esc_attr($mod['icon']); ?>"></span>
                                        <span class="aipkit_module-label"><?php echo esc_html($mod['label']); ?></span>
                                    </div>
                                    <label class="aipkit_toggle-switch" for="<?php echo esc_attr($inputId); ?>">
                                        <input
                                            type="checkbox"
                                            id="<?php echo esc_attr($inputId); ?>"
                                            name="<?php echo esc_attr($optionKey); ?>"
                                            class="aipkit_module-toggle"
                                            data-module="<?php echo esc_attr($mod['data_module']); ?>"
                                            data-option-key="<?php echo esc_attr($optionKey); ?>"
                                            data-icon="<?php echo esc_attr($mod['icon']); ?>"
                                            data-label="<?php echo esc_attr($mod['label']); ?>"
                                            <?php echo $checked ? 'checked' : ''; ?>
                                        >
                                        <span class="aipkit_toggle-slider"></span>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </header>
    
    <?php
    // --- ADDED: Display migration notice if needed ---
    if ($show_migration_notice) {
        $notice_partial_path = __DIR__ . '/partials/migration-notice.php'; // Corrected path
        if (file_exists($notice_partial_path)) {
            include $notice_partial_path;
        }
    }
    // --- END ADDED ---
    ?>

    <!-- Main content area -->
    <div class="aipkit_main-content" id="aipkit_module-container">
        <!-- Module content will be loaded here -->
    </div>
</div>