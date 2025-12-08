<?php
namespace MetaBox\UserProfile;

class TemplateLoader extends \Gamajo_Template_Loader {
	/**
	 * Prefix for filter names.
	 */
	protected $filter_prefix = 'mbup';

	/**
	 * Directory name where custom templates for this plugin should be found in the theme.
	 */
	protected $theme_template_directory = 'mb-user-profile';

	/**
	 * Reference to the root directory path of this plugin.
	 * Can either be a defined constant, or a relative reference from where the subclass lives.
	 */
	protected $plugin_directory = MBUP_DIR;

	/**
	 * Directory name where templates are found in this plugin.
	 * Can either be a defined constant, or a relative reference from where the subclass lives.
	 * e.g. 'templates' or 'includes/templates', etc.
	 */
	protected $plugin_template_directory = 'templates';
}
