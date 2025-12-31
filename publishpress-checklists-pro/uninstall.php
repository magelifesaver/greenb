<?php
/**
 * Uninstall script for PublishPress Checklists Pro
 * 
 * This file is executed when the plugin is deleted from the WordPress admin.
 * It will remove all plugin data if the user has enabled the "Delete All Data on Uninstall" option.
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Delete all plugin data if the user has enabled the option
 */
function publishpress_checklists_pro_uninstall_cleanup()
{
    // Check if user has enabled data deletion on uninstall (mirrors free version behaviour)
    $settings_options = get_option('publishpress_checklists_settings_options', []);

    if (is_object($settings_options)) {
        $settings_options = (array) $settings_options;
    }

    if (!is_array($settings_options)) {
        $settings_options = [];
    }

    $delete_option_value = $settings_options['delete_data_on_uninstall'] ?? 'off';

    $delete_data = in_array($delete_option_value, ['on', '1', 1, true], true);

    if (!$delete_data) {
        // User hasn't enabled data deletion, so we keep all data
        return;
    }

    // Delete all plugin options (free base + pro specific)
    $options_to_delete = [
        // Core plugin options
        'publishpress_checklists_checklists_options',
        'publishpress_checklists_settings_options',
        'publishpress_checklists_prosettings_options',
        'publishpress_checklists_version',

        // Module options (pro)
        'publishpress_checklists_accessibility_options',
        'publishpress_checklists_advanced_custom_fields_options',
        'publishpress_checklists_all-in-one-seo_options',
        'publishpress_checklists_approved_by_user_options',
        'publishpress_checklists_audio_count_options',
        'publishpress_checklists_featured_image_size_options',
        'publishpress_checklists_image_count_options',
        'publishpress_checklists_no_heading_tags_options',
        'publishpress_checklists_permalinks_options',
        'publishpress_checklists_permissions_options',
        'publishpress_checklists_publish_time_options',
        'publishpress_checklists_rank-math_options',
        'publishpress_checklists_reviews_options',
        'publishpress_checklists_video_count_options',
        'publishpress_checklists_woocommerce_options',
        'publishpress_checklists_yoastseo_options',

        // Duplicate functionality
        'ppc_duplicate_class_mappings',

        // Migration flags
        'publishpress_checklists_options_migrated_2_0_0',
        'publishpress_checklists_options_migrated_2_6_0',

        // Reviews/branding
        'publishpress-checklists_wp_reviews_installed_on',
        'PUBLISHPRESS_CAPS_VERSION',

        // Activation flag
        'ppch_activated',
    ];

    foreach ($options_to_delete as $option_name) {
        delete_option($option_name);

        // Also delete from multisite if applicable
        if (is_multisite()) {
            delete_site_option($option_name);
        }
    }

    // Delete any transients (only if they exist)
    global $wpdb;
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_publishpress_checklists_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_publishpress_checklists_%'");

    // Clear any cached data
    wp_cache_flush();
}

// Execute the cleanup
publishpress_checklists_pro_uninstall_cleanup();
