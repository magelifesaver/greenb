<?php

/**
 * @package     PublishPress\ChecklistsPro
 * @author      PublishPress <help@publishpress.com>
 * @copyright   copyright (C) 2019 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

// Prevent direct access
defined('ABSPATH') or die('No direct script access allowed.');

/**
 * Class PPCH_Duplicate_Checklist
 * 
 * Handles duplication of checklist requirements with role-based configurations
 */
class PPCH_Duplicate_Checklist
{
    /**
     * Constructor
     */
    public function __construct()
    {
        add_action('publishpress_checklists_tasks_list_th', [$this, 'addDuplicateColumnHeader'], 30);
        add_action('publishpress_checklists_tasks_list_td', [$this, 'addDuplicateColumnCell'], 30, 2);

        add_action('wp_ajax_ppc_duplicate_requirement', [$this, 'ajaxDuplicateRequirement']);
        add_action('wp_ajax_ppc_delete_duplicate_requirement', [$this, 'ajaxDeleteDuplicateRequirement']);


        if (file_exists(__DIR__ . '/lib/DuplicateIntegration.php')) {
            require_once __DIR__ . '/lib/DuplicateIntegration.php';
            new \PublishPress\ChecklistsPro\DuplicateChecklist\DuplicateIntegration();
        }

        if (file_exists(__DIR__ . '/lib/DuplicateHelper.php')) {
            require_once __DIR__ . '/lib/DuplicateHelper.php';
        }
        if (file_exists(__DIR__ . '/lib/DuplicateNameGenerator.php')) {
            require_once __DIR__ . '/lib/DuplicateNameGenerator.php';
        }
        if (file_exists(__DIR__ . '/lib/DuplicateSettingsManager.php')) {
            require_once __DIR__ . '/lib/DuplicateSettingsManager.php';
        }
        if (file_exists(__DIR__ . '/lib/DynamicClassGenerator.php')) {
            require_once __DIR__ . '/lib/DynamicClassGenerator.php';
        }

        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);

    }

    /**
     * Add duplicate column header to global checklists table
     */
    public function addDuplicateColumnHeader()
    {
        
        echo '<th class="ppc-duplicate-header" title="' . esc_attr__('Duplicate Requirement', 'publishpress-checklists-pro') . '">Actions</th>';
    }

    /**
     * Add duplicate button to each requirement row
     *
     * @param object $requirement The requirement object
     * @param string $post_type The post type
     */
    public function addDuplicateColumnCell($requirement, $post_type)
    {
        

        $is_duplicate = strpos($requirement->name, '_duplicate_') !== false;

        if ((is_object($requirement) && get_class($requirement) === 'PublishPress\\Checklists\\Core\\Requirement\\Custom_item')) {
            echo '<td class="ppc-duplicate-actions"></td>';
            return;
        }

        if ($is_duplicate) {
            $buttons = sprintf(
                '<td class="ppc-duplicate-actions">
                    <button type="button" class="button button-secondary ppc-delete-duplicate-btn" data-requirement="%s" data-post-type="%s" title="%s">
                        ' . esc_html__('Delete', 'publishpress-checklists-pro') . '
                    </button>
                </td>',
                esc_attr($requirement->name),
                esc_attr($post_type),
                esc_attr__('Delete this duplicate', 'publishpress-checklists-pro')
            );
        } else {
            $is_disabled = $this->isRequirementDisabled($requirement->name, $post_type);

            if ($is_disabled) {
                $tooltip_id = 'ppc-duplicate-tooltip-' . sanitize_title($requirement->name) . '-' . uniqid('', false);
                $buttons = sprintf(
                    '<td class="ppc-duplicate-actions ppc-duplicate-disabled">
                        <div class="ppc-tooltip-wrapper">
                            <button type="button" class="button button-secondary ppc-duplicate-btn" data-requirement="%1$s" data-post-type="%2$s" aria-describedby="%4$s" disabled>
                                %3$s
                            </button>
                            <span id="%4$s" class="ppc-tooltip" role="tooltip">%5$s</span>
                        </div>
                    </td>',
                    esc_attr($requirement->name),
                    esc_attr($post_type),
                    esc_html__('Duplicate', 'publishpress-checklists-pro'),
                    esc_attr($tooltip_id),
                    esc_html__('Please enable the task before using this Duplicate feature.', 'publishpress-checklists-pro')
                );
            } else {
                $buttons = sprintf(
                    '<td class="ppc-duplicate-actions"><button type="button" class="button button-secondary ppc-duplicate-btn" data-requirement="%s" data-post-type="%s" title="%s">
                        ' . esc_html__('Duplicate', 'publishpress-checklists-pro') . '
                    </button></td>',
                    esc_attr($requirement->name),
                    esc_attr($post_type),
                    esc_attr__('Duplicate this task', 'publishpress-checklists-pro')
                );
            }
        }

        echo $buttons;
    }

    /**
     * AJAX handler for duplicating requirements
     */
    public function ajaxDuplicateRequirement()
    {
        if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', 'ppc_duplicate_checklist')) {
            wp_send_json_error('Invalid nonce');
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $requirement_name = sanitize_text_field($_POST['requirement'] ?? '');
        $post_type = sanitize_text_field($_POST['post_type'] ?? '');

        if (empty($requirement_name) || empty($post_type)) {
            wp_send_json_error('Missing required parameters');
        }

        require_once __DIR__ . '/lib/DuplicateHandler.php';
        $handler = new \PublishPress\ChecklistsPro\DuplicateChecklist\DuplicateHandler();

        $result = $handler->duplicateRequirement($requirement_name, $post_type);

        if ($result['success']) {
            $result['debug'] = $handler->debugDuplicate($result['duplicate_name']);
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message']);
        }
    }

    /**
     * AJAX handler for deleting duplicate requirements
     */
    public function ajaxDeleteDuplicateRequirement()
    {
        if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', 'ppc_duplicate_checklist')) {
            wp_send_json_error('Invalid nonce');
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $requirement_name = sanitize_text_field($_POST['requirement'] ?? '');

        if (empty($requirement_name) || strpos($requirement_name, '_duplicate_') === false) {
            wp_send_json_error('Invalid requirement name');
        }

        require_once __DIR__ . '/lib/DuplicateHandler.php';
        $handler = new \PublishPress\ChecklistsPro\DuplicateChecklist\DuplicateHandler();

        $result = $handler->deleteDuplicateRequirement($requirement_name);

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message']);
        }
    }


    /**
     * Enqueue assets for the duplicate functionality
     */
    public function enqueueAssets($hook)
    {
        $base_url = plugin_dir_url(__FILE__);
        $version = defined('PPCHPRO_VERSION') ? PPCHPRO_VERSION : '1.0.0';

        wp_enqueue_script(
            'ppc-duplicate-checklist',
            $base_url . 'assets/js/duplicate-checklist.js',
            ['jquery'],
            $version,
            true
        );

        wp_enqueue_style(
            'ppc-duplicate-checklist',
            $base_url . 'assets/css/duplicate-checklist.css',
            [],
            $version
        );

        // Localize script
        wp_localize_script(
            'ppc-duplicate-checklist',
            'ppcDuplicateChecklist',
            [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ppc_duplicate_checklist'),
                'strings' => [
                    'confirm_duplicate' => __('Are you sure you want to duplicate this task?', 'publishpress-checklists-pro'),
                    'duplicating' => __('Duplicating...', 'publishpress-checklists-pro'),
                    'success' => __('Task duplicated successfully!', 'publishpress-checklists-pro'),
                    'error' => __('Error duplicating Task.', 'publishpress-checklists-pro'),
                ]
            ]
        );
    }

    /**
     * Check if a requirement is disabled for a specific post type
     *
     * @param string $requirement_name
     * @param string $post_type
     * @return bool
     */
    private function isRequirementDisabled($requirement_name, $post_type)
    {
        $options = get_option('publishpress_checklists_checklists_options', []);

        $options_array = is_object($options) ? (array) $options : (array) $options;

        $rule_key = $requirement_name . '_rule';

        if (!isset($options_array[$rule_key])) {
            return true;
        }

        $rule_value = $options_array[$rule_key];

        if (is_object($rule_value)) {
            $status = $rule_value->{$post_type} ?? null;
        } elseif (is_array($rule_value)) {
            $status = $rule_value[$post_type] ?? null;
        } else {
            $status = $rule_value;
        }

        if ($status === null) {
            return true;
        }

        return in_array($status, ['off', 'disabled', 0, '0', false, 'false'], true);
    }

    /**
     * Get the original requirement type from duplicate name
     *
     * @param string $duplicate_name
     * @return string|false
     */
    private function getOriginalType($duplicate_name)
    {
        // Extract original type from duplicate name
        // Format: {original_name}_duplicate_{number}
        $parts = explode('_duplicate_', $duplicate_name);
        return isset($parts[0]) ? $parts[0] : false;
    }


}
