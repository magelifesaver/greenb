<?php
/**
 * Status Filter module for PublishPress Checklists Pro
 *
 * @package     PublishPress\ChecklistsPro\StatusFilter
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (c) 2023 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

namespace PublishPress\ChecklistsPro\StatusFilter;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class StatusFilter
 *
 * Adds the ability to exclude checklist requirements based on post status
 */
class StatusFilter
{
    /**
     * Option name for storing status exclusions
     *
     * @var string
     */
    const OPTION_NAME = 'ppc_statuses_per_requirement';

    /**
     * Nonce action for AJAX requests
     *
     * @var string
     */
    const NONCE_ACTION = 'ppc_status_filter_nonce';

    /**
     * Cached exclusion options
     *
     * @var array|null
     */
    private $cachedOptions = null;

    /**
     * Constructor
     */
    public function __construct()
    {
        // Add column header and cell to global checklist table
        add_action('publishpress_checklists_tasks_list_th', [$this, 'addStatusesColumnHeader']);
        add_action('publishpress_checklists_tasks_list_td', [$this, 'addStatusesColumnCell'], 10, 2);

        // Filter requirements for post edit screen
        add_filter('publishpress_checklists_requirement_list', [$this, 'filterRequirementsByStatus'], 20, 2);

        // AJAX for saving statuses
        add_action('wp_ajax_ppc_save_checklist_statuses', [$this, 'ajaxSaveStatuses']);

        // Enqueue assets
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    /**
     * Add the column header for Statuses
     */
    public function addStatusesColumnHeader()
    {
        echo '<th>' . esc_html__('Exclude Statuses', 'publishpress-checklists-pro') . '</th>';
    }

    /**
     * Add the column cell with Select2 for statuses
     *
     * @param object $requirement The requirement object
     * @param string $post_type   The post type
     */
    public function addStatusesColumnCell($requirement, $post_type)
    {
        // Get requirement ID primarily from object's name property
        $requirement_id = '';
        if (is_object($requirement) && isset($requirement->name)) {
            $requirement_id = esc_attr($requirement->name);
        }

        $statuses = $this->getAllStatuses();
        $excluded = $this->getExcludedStatuses($requirement_id, $post_type);

        echo '<td>';
        echo '<select class="ppc-statuses-select" name="ppc_statuses['.esc_attr($requirement_id).'][]" multiple="multiple" data-requirement="'.esc_attr($requirement_id).'" data-post_type="'.esc_attr($post_type).'" data-placeholder="' . esc_attr__('Select statuses to exclude', 'publishpress-checklists-pro') . '">';

        foreach ($statuses as $status => $obj) {
            $is_selected = in_array($status, $excluded) ? 'selected' : '';
            echo '<option value="'.esc_attr($status).'" '.$is_selected.'>'.esc_html($obj->label).'</option>';
        }

        echo '</select>';
        echo '</td>';
    }

    /**
     * Get all post statuses (built-in and custom)
     *
     * @return array Array of post status objects
     */
    private function getAllStatuses()
    {
        return get_post_stati(['internal' => false], 'objects');
    }

    /**
     * Get the cached exclusion options
     *
     * @return array
     */
    private function getOptions()
    {
        if ($this->cachedOptions === null) {
            $this->cachedOptions = get_option(self::OPTION_NAME, []);
        }
        return $this->cachedOptions;
    }

    /**
     * Get excluded statuses for a requirement
     *
     * @param string $requirement_id The requirement ID
     * @param string $post_type      The post type
     *
     * @return array Array of excluded status slugs
     */
    private function getExcludedStatuses($requirement_id, $post_type)
    {
        $option = $this->getOptions();
        return $option[$post_type][$requirement_id] ?? [];
    }

    /**
     * AJAX handler to save selected statuses
     */
    public function ajaxSaveStatuses()
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], self::NONCE_ACTION)) {
            wp_send_json_error('Invalid nonce');
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $requirement_id = sanitize_text_field($_POST['requirement_id'] ?? '');
        $statuses = isset($_POST['statuses']) && is_array($_POST['statuses'])
            ? array_map('sanitize_text_field', $_POST['statuses'])
            : [];
        $post_type = sanitize_text_field($_POST['post_type'] ?? 'post');

        $option = get_option(self::OPTION_NAME, []);
        $option[$post_type][$requirement_id] = $statuses;
        update_option(self::OPTION_NAME, $option);

        // Clear cache
        $this->cachedOptions = null;

        wp_send_json_success();
    }

    /**
     * Filter requirements by post status (for post edit screen)
     *
     * @param array        $requirements List of requirements
     * @param int|WP_Post  $post         Post ID or post object
     *
     * @return array Filtered requirements
     */
    public function filterRequirementsByStatus($requirements, $post)
    {
        $post_obj = null;

        if (is_numeric($post)) {
            $post_obj = get_post($post);
        } elseif (is_object($post) && isset($post->ID)) {
            $post_obj = $post;
        }

        if (!$post_obj) {
            return $requirements; // Return original requirements if post context is unclear
        }

        $current_screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if ($current_screen && method_exists($current_screen, 'is_block_editor') && $current_screen->is_block_editor()) {
            return $requirements;
        }

        $current_post_type = $post_obj->post_type;
        $current_post_status = property_exists($post_obj, 'post_status') && is_string($post_obj->post_status) ? $post_obj->post_status : '';
        
        // Treat auto-draft as draft for exclusion purposes
        if ($current_post_status === 'auto-draft') {
            $current_post_status = 'draft';
        }
        
        $option = $this->getOptions();
        $filtered_requirements = [];

        // Process each requirement
        foreach ($requirements as $req_id => $req_data) {
            // If requirement ID is empty, keep it in the list
            if (empty($req_id)) {
                $filtered_requirements[$req_id] = $req_data;
                continue;
            }

            // Get excluded statuses for this requirement
            $excluded_statuses = [];
            if (isset($option[$current_post_type][$req_id])) {
                $excluded_statuses = $option[$current_post_type][$req_id];
            }

            // Check if current status is excluded
            $is_excluded = in_array($current_post_status, $excluded_statuses, true);

            // Add to filtered list if not excluded
            if (!$is_excluded) {
                $filtered_requirements[$req_id] = $req_data;
            }
        }

        return $filtered_requirements;
    }

    /**
     * Helper function to determine if all requirements are excluded for a given status.
     *
     * @param string $post_type                     The post type
     * @param string $status                        The status to check
     * @param array  $all_requirements_for_post_type Array of requirement IDs/names
     * @param array  $exclusion_options             The exclusion options array
     *
     * @return bool True if the panel should be hidden for this status, false otherwise
     */
    private function shouldHidePanelForStatus($post_type, $status, $all_requirements_for_post_type, $exclusion_options)
    {
        if (empty($all_requirements_for_post_type)) {
            return false; // No requirements to check, so don't hide by default
        }

        foreach ($all_requirements_for_post_type as $req_id) {
            $excluded_statuses = [];

            if (isset($exclusion_options[$post_type][$req_id])) {
                $excluded_statuses = $exclusion_options[$post_type][$req_id];
            }

            if (!in_array($status, $excluded_statuses, true)) {
                // If even one requirement is NOT excluded for this status, the panel should be shown
                return false;
            }
        }

        // If all requirements are excluded for this status, the panel should be hidden
        return true;
    }

    /**
     * Enqueue JS and CSS assets for the statuses column
     *
     * @param string $hook The current admin page
     */
    public function enqueueAssets($hook)
    {
        $base_url = plugin_dir_url(__FILE__);

        if ($hook === 'toplevel_page_ppch-checklists') {
            wp_enqueue_script(
                'ppc-statuses-column',
                $base_url . 'assets/js/statuses-column.js',
                ['jquery'],
                PPCHPRO_VERSION,
                true
            );

            wp_localize_script(
                'ppc-statuses-column',
                'ppcStatusFilterData',
                [
                    'nonce' => wp_create_nonce(self::NONCE_ACTION)
                ]
            );
        }

        // Gutenberg specific script
        $current_screen = get_current_screen();
        if ($current_screen && $current_screen->is_block_editor()) {
            wp_enqueue_script(
                'ppc-gutenberg-status-visibility',
                $base_url . 'assets/js/gutenberg-status-visibility.js',
                ['wp-data', 'wp-dom-ready', 'jquery', 'wp-edit-post'],
                PPCHPRO_VERSION,
                true
            );

            $post_type = $current_screen->post_type;
            $registered_statuses = get_post_stati(['show_in_admin_status_list' => true], 'names');
            $excluded_option = $this->getOptions();

            // Get all requirements for the current post type
            $all_req_objects = [];
            $all_req_ids = [];

            if (class_exists('\PublishPress\Checklists\Core\Requirements')) {
                $requirements_handler = \PublishPress\Checklists\Core\Requirements::instance();
                if (method_exists($requirements_handler, 'get_requirements_for_post_type')) {
                    $all_req_objects = $requirements_handler->get_requirements_for_post_type($post_type, 'edit');
                }
            }

            // Extract requirement IDs
            if (is_array($all_req_objects)) {
                $all_req_ids = array_keys($all_req_objects);
            }

            // Determine which statuses should hide the panel
            $hide_on_statuses = [];
            foreach ($registered_statuses as $status_slug) {
                if ($this->shouldHidePanelForStatus($post_type, $status_slug, $all_req_ids, $excluded_option)) {
                    $hide_on_statuses[] = $status_slug;
                }
            }

            // Data to pass to JavaScript
            $data_to_localize = [
                'hideOnStatuses' => $hide_on_statuses,
                'debug_exclusion_options_for_post_type' => isset($excluded_option[$post_type]) ? $excluded_option[$post_type] : []
            ];

            wp_localize_script(
                'ppc-gutenberg-status-visibility',
                'ppcStatusFilterGutenbergData',
                $data_to_localize
            );
        }
    }

}
