<?php

/**
 * @package     PublishPress\Checklists
 * @author      PublishPress <help@publishpress.com>
 * @copyright   copyright (C) 2019 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */


namespace PublishPress\ChecklistsPro\ApprovedByUser\Requirement;

use PublishPress\Checklists\Core\Requirement\Base_multiple;
use PublishPress\Checklists\Core\Requirement\Interface_required;

defined('ABSPATH') or die('No direct script access allowed.');

class ApprovedByUser extends Base_multiple implements Interface_required
{
    /**
     * The name of the requirement, in a slug format
     *
     * @var string
     */
    public $name = 'approved_by_user';

    /**
     * The name of the group, used for the tabs
     * 
     * @var string
     */
    public $group = 'approval';

    protected $field_name = 'users';

    const POST_META_PREFIX = 'pp_checklist_custom_item';

    /**
     * @var int
     */
    public $position = 171;

    /**
     * Initialize the language strings for the instance
     *
     * @return void
     */
    public function init_language()
    {
        $this->lang['label']          = __('Approved by %s', 'publishpress-checklists-pro');
        $this->lang['label_settings'] = __('Approved by specific user', 'publishpress-checklists-pro');
    }

    /**
     * Returns the current status of the requirement.
     *
     * @param \stdClass $post
     * @param mixed $option_value
     *
     * @return mixed
     */
    public function get_current_status($post, $option_value)
    {
        $post_id = isset($post->ID) ? $post->ID : 0;
        return self::VALUE_YES === get_post_meta($post_id, static::POST_META_PREFIX . '_' . $this->name, true);
    }

    /**
     * Gets settings drop down labels.
     *
     * @return array.
     */
    public function get_setting_drop_down_labels()
    {
        // Get all users who can edit posts
        $users = get_users([
            'capability' => 'edit_posts',
            'orderby' => 'display_name',
            'order' => 'ASC'
        ]);

        $user_labels = [];
        foreach ($users as $user) {
            $user_labels[$user->ID] = $user->display_name;
        }

        return $user_labels;
    }

    /**
     * Add the requirement to the list to be displayed in the meta box.
     *
     * @param array $requirements
     * @param \stdClass $post
     *
     * @return array
     */
    public function filter_requirements_list($requirements, $post)
    {
        // Check if it is a compatible post type. If not, ignore this requirement.
        if ($post->post_type !== $this->post_type) {
            return $requirements;
        }

        $requirements = parent::filter_requirements_list($requirements, $post);

        // If not enabled, bypass the method
        if (!$this->is_enabled()) {
            return $requirements;
        }

        // Option names
        $option_name_multiple = $this->name . '_' . $this->field_name;

        // Get the value
        $option_value = array();
        if (isset($this->module->options->{$option_name_multiple}[$this->post_type])) {
            $option_value = $this->module->options->{$option_name_multiple}[$this->post_type];
        }

        if (empty($option_value)) {
            return $requirements;
        }

        // Get the user names
        $user_names = [];
        foreach ($option_value as $user_id) {
            $user = get_user_by('id', $user_id);
            if ($user) {
                $user_names[] = $user->display_name;
            }
        }
        $user_names = implode(', ', $user_names);

        // Register in the requirements list
        $requirements[$this->name]['label'] = sprintf($this->lang['label'], $user_names);
        $requirements[$this->name]['is_custom'] = $this->isUserPermitted();
        $requirements[$this->name]['id'] = 'approved_by_user';

        return $requirements;
    }

    /**
     * Check if current user is permitted to validate this task
     */
    private function isUserPermitted()
    {
        // Option name
        $option_name_multiple = $this->name . '_' . $this->field_name;

        if (!isset($this->module->options->{$option_name_multiple}[$this->post_type])) {
            return true;
        }

        // Saved value
        $option_value = isset($this->module->options->{$option_name_multiple}[$this->post_type]) ? $this->module->options->{$option_name_multiple}[$this->post_type] : array();

        $current_user_id = get_current_user_id();
        
        if (in_array($current_user_id, $option_value)) {
            return true;
        }

        return false;
    }

    /**
     * Generates an <option> element.
     *
     * @param string $value The option's value.
     * @param string $label The option's label.
     * @param string $selected HTML selected attribute for an option.
     *
     * @return string The generated <option> element.
     */
    protected function generate_option($value, $label, $selected = '')
    {
        return '<option value="' . esc_attr($value) . '" ' . $selected . '>' . esc_html($label) . '</option>';
    }
}
