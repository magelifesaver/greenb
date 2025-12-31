<?php

/**
 * @package     PublishPress\ChecklistsPro\AdvancedCustomFields\Requirement
 * @author      PublishPress <help@publishpress.com>
 * 
 * @since       1.0.0
 */

namespace PublishPress\ChecklistsPro\AdvancedCustomFields\Requirement;

use PublishPress\Checklists\Core\Requirement\Base_simple;

defined('ABSPATH') or die('No direct script access allowed.');

class Base extends Base_simple
{
  /**
   * The name of the group, used for the tabs
   * 
   * @var string
   */
  public $group = 'advanced-custom-fields';

  /**
   * @var string
   */
  protected $type = 'acf_base';

  /**
   * Returns the value of the given option. The option name should
   * be in the short form, without the name of the requirement as
   * the prefix.
   *
   * @param string $option_name
   *
   * @return mixed
   */
  public function get_option($option_name)
  {
    $options     = $this->module->options;
    $option_name = sprintf('%s_%s', $this->name, $option_name);

    if (isset($options->{$option_name}) && isset($options->{$option_name}[$this->post_type])) {
      return $options->{$option_name}[$this->post_type];
    }

    return null;
  }
}
