<?php

/**
 * @package     PublishPress\ChecklistsPro\AdvancedCustomFields\Requirement
 * @author      PublishPress
 * 
 * @since       1.0.0
 */

namespace PublishPress\ChecklistsPro\AdvancedCustomFields\Requirement;

use PublishPress\Checklists\Core\Requirement\Interface_parametrized;
use PublishPress\ChecklistsPro\Factory;
use PublishPress\ChecklistsPro\HooksAbstract;
use WPPF\Plugin\ServicesAbstract;

class Base_simple extends Base implements Interface_parametrized
{
  /**
   * The priority for the action to load the requirement
   */
  const PRIORITY = 100;

  /**
   * The name of the requirement, in a slug format. This is dynamic.
   *
   * @var string
   */
  public $name = 'acf_input_text';

  /**
   * The name of the group, used for the tabs
   * 
   * @var string
   */
  public $group = 'advanced-custom-fields';

  /**
   * @var HooksHandlerInterface
   */
  private $hooksHandler;

  protected $key;
  protected $label;
  protected $fieldName;
  protected $fieldType;
  protected $fieldValue;
  protected $maxLength;

  /**
   * @inheritDoc
   */
  public function __construct($module, $post_type)
  {
    parent::__construct($module, $post_type);

    $container = Factory::getContainer();

    $this->hooksHandler = $container->get(ServicesAbstract::HOOKS_HANDLER);
  }

  /**
   * Method to initialize the Requirement, adding filters and actions to
   * interact with the Add-on.
   *
   * @return void
   */
  public function init()
  {
    parent::init();

    $this->hooksHandler->addFilter(HooksAbstract::FILTER_ACF_LOCALIZED_DATA, [$this, 'localize_data']);
  }

  /**
   * Initialize the language strings for the instancef
   *
   * @return void
   */
  public function init_language()
  {
    $this->lang['label']          = __($this->label, 'publishpress-checklists-pro');
    $this->lang['label_settings'] = __($this->label . ' is filled', 'publishpress-checklists-pro');
  }

  /**
   * Returns the current status of the requirement.
   *
   * @param stdClass $post
   * @param mixed $option_value
   *
   * @return mixed
   */
  public function get_current_status($post, $option_value)
  {
    $validate_status = '';
    if (function_exists('get_field')) {
      // Get the current post ID
      $post_id = get_the_ID();

      // Get the value of the custom field
      $field_value = get_field($this->fieldName, $post_id);

      switch ($this->fieldType) {
        case 'email':
          $validate_status = $this->validateEmail($field_value);
          break;
        case 'url':
          $validate_status = $this->validateUrl($field_value);
          break;
        case 'number':
          $validate_status = $this->validateNumber($field_value);
          break;
        case 'range':
          $validate_status = $this->validateRange($field_value);
          break;
        case 'password':
          $validate_status = $this->validatePassword($field_value);
          break;
        case 'wysiwyg':
          $validate_status = $this->validateWysiwyg($field_value);
          break;
        case 'onembed':
          $validate_status = $this->validateOnembed($field_value);
          break;
        case 'select':
          $validate_status = $this->validateSelect($field_value);
          break;
        case 'text':
          $validate_status = $this->validateText($field_value);
          break;
        default:
          $validate_status = false;
          break;
      }
    }

    return $validate_status;
  }

  /**
   * @param array $params
   *
   * @return array|void
   */
  public function set_params($params)
  {
    $this->fieldName = $params['name'];
    $this->fieldType = $params['type'];
    $this->key       = $params['key'];
    $this->label     = $params['label'];
    $this->maxLength = $params['maxlength'];
    $this->name      = strtolower($this->name . '__' . $this->key . '__' .  $this->fieldName);
  }

  /**
   * Add the requirement to the list to be displayed in the metabox.
   *
   * @param array $requirements
   * @param stdClass $post
   *
   * @return array
   */
  public function filter_requirements_list($requirements, $post)
  {
    // Check if it is a compatible post type. If not, ignore this requirement.
    if ($post->post_type !== $this->post_type) {
      return $requirements;
    }

    // Rule
    $rule = $this->get_option_rule();

    // Enabled
    $enabled = $this->is_enabled();

    // Get the value
    $option_value = array();
    if (isset($this->module->options->{$this->name}[$this->post_type])) {
      $option_value = $this->module->options->{$this->name}[$this->post_type];
    }

    // Register in the requirements list
    if ($enabled) {
      $requirements[$this->name] = [
        'status'    => $this->get_current_status($post, $enabled),
        'label'     => $this->lang['label'],
        'value'     => $option_value,
        'rule'      => $rule,
        'type'      => $this->type,
        'is_custom' => false,
      ];
    }

    return $requirements;
  }

  /**
   * Add data to the javascript script
   *
   * @param array $data
   *
   * @return array
   */
  public function localize_data($data)
  {
    $data[$this->name] = $this->get_option('rule');

    return $data;
  }

  // Function to validate email
  private function validateEmail($email)
  {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
  }

  // Function to validate URL
  private function validateUrl($url)
  {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
  }

  // Function to validate number
  private function validateNumber($number)
  {
    return is_numeric($number);
  }

  // Function to validate range (example: 0-100)
  private function validateRange($number, $min = 0, $max = 100)
  {
    return is_numeric($number) && $number >= $min && $number <= $max;
  }

  // Function to validate password (example: minimum 8 characters)
  private function validatePassword($password)
  {
    return is_string($password) ? strlen(trim($password)) >= 8 : false;
  }

  // Function to validate WYSIWYG content (example: basic HTML tags)
  private function validateWysiwyg($content)
  {
    return strip_tags($content, '<p><a><b><i><strong><em><ul><ol><li><br><hr>') === $content;
  }

  // Function to validate embedded content (example: contains <a> tag)
  private function validateOnembed($content)
  {
    return preg_match('/<a\s+href="[^"]+">[^<]+<\/a>/', $content);
  }

  // Function to validate select options (example: predefined options)
  private function validateSelect($value, $options = ['red', 'green', 'blue'])
  {
    return in_array($value, $options);
  }

  // Function to validate text (example: minimum 8 characters)
  private function validateText($text)
  {
    return is_string($text) ? strlen(trim($text)) >= 0 : false;
  }
}
