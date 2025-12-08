<?php
/**
 * AddAtum Action Logs Settings' tab to ATUM Settings
 *
 * @package     AtumLogs
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2025 Stock Management Labs™
 *
 * @since       1.3.0
 */

namespace AtumLogs\Inc;

defined( 'ABSPATH' ) || die;

use Atum\Inc\Helpers as AtumHelpers;
use Atum\Settings\Settings as AtumSettings;
use AtumLogs\Models\LogModel;


class Settings {

	/**
	 * The singleton instance holder
	 *
	 * @var Settings
	 */
	private static $instance;


	/**
	 * Settings singleton constructor
	 *
	 * @since 0.0.1
	 */
	private function __construct() {

		// Add the Atum Action Logs' settings to ATUM.
		add_filter( 'atum/settings/tabs', array( $this, 'add_settings_tab' ), 11 );
		add_filter( 'atum/settings/defaults', array( $this, 'add_settings_defaults' ), 11 );
		add_action( 'atum/settings/after_script_runner_field', array( $this, 'logs_remover_tool_ui' ) );

		// Add tools to AtumCli commands.
		add_action( 'atum/cli/register_hooks', array( $this, 'add_tools_to_atum_cli' ) );

	}

	/**
	 * Add a new tab to the ATUM settings page
	 * 
	 * @since 0.0.1
	 *
	 * @param array $tabs
	 *
	 * @return array
	 */
	public function add_settings_tab( $tabs ) {

		$tabs['atum_logs'] = array(
			'label'    => __( 'Action Logs', ATUM_LOGS_TEXT_DOMAIN ),
			'icon'     => 'atmi-logs',
			'sections' => array(
				'al_general'    => __( 'General Options', ATUM_LOGS_TEXT_DOMAIN ),
				'al_displaying' => __( 'Displaying Options', ATUM_LOGS_TEXT_DOMAIN ),
			),
		);

		return $tabs;
	}

	/**
	 * Add fields to the ATUM settings page
	 * 
	 * @since 0.0.1
	 *
	 * @param array $defaults
	 *
	 * @return array
	 */
	public function add_settings_defaults( $defaults ) {

		$defaults = array_merge( $defaults, array(
			'al_show_avatar'   => array(
				'group'   => 'atum_logs',
				'section' => 'al_displaying',
				'name'    => __( 'Show user avatar', ATUM_LOGS_TEXT_DOMAIN ),
				'desc'    => __( 'Shows the user avatar in the logs list.', ATUM_LOGS_TEXT_DOMAIN ),
				'type'    => 'switcher',
				'default' => 'no',
			),
			'al_logs_per_page' => array(
				'group'   => 'atum_logs',
				'section' => 'al_displaying',
				'name'    => __( 'Logs per page', ATUM_LOGS_TEXT_DOMAIN ),
				'desc'    => __( "Controls the number of logs displayed per page within the Atum Action Logs screen. Please note, you can set this value within the 'Screen Option' tab as well.", ATUM_LOGS_TEXT_DOMAIN ),
				'type'    => 'number',
				'default' => AtumSettings::DEFAULT_POSTS_PER_PAGE,
				'options' => array(
					'min' => 1,
					'max' => 50,
				),
			),
			'al_relative_date' => array(
				'group'   => 'atum_logs',
				'section' => 'al_displaying',
				'name'    => __( 'Relative dates', ATUM_LOGS_TEXT_DOMAIN ),
				'desc'    => __( 'Shows the dates in a relative format, indicating the elapsed time.', ATUM_LOGS_TEXT_DOMAIN ),
				'type'    => 'switcher',
				'default' => 'yes',
			),
		) );

		foreach ( LogModel::get_sources() as $i => $source ) {

			if ( ! LogModel::check_source_dependency( $i ) ) {
				continue;
			}

			$modules = array();
			foreach ( LogModel::get_modules() as $k => $module ) {

				if ( ! LogModel::check_module_dependency( $k ) ) {
					continue;
				}

				if ( LogModel::get_module_parent( $k ) !== $i ) {
					continue;
				}

				$modules[ $k ] = [
					'value' => 'yes',
					'name'  => $module,
					/* Translators: Module */
					'desc'  => sprintf( __( 'Check this option to register logs of type %s.', ATUM_LOGS_TEXT_DOMAIN ), $module ),
				];

			}

			$defaults[ 'al_register_' . $i ] = array(
				'group'           => 'atum_logs',
				'section'         => 'al_general',
				'name'            => $source,
				/* Translators: Source */
				'desc'            => sprintf( __( 'Enable this option to register logs from %s.', ATUM_LOGS_TEXT_DOMAIN ), $source ),
				'type'            => 'multi_checkbox',
				'default'         => 'yes',
				'main_switcher'   => TRUE,
				'default_options' => $modules,
			);
		}

		$al_label = '<br><span class="label label-secondary">' . __( 'Action Logs', ATUM_LOGS_TEXT_DOMAIN ) . '</span>';

		$defaults['al_tool_remove_logs'] = array(
			'group'   => 'tools',
			'section' => 'tools',
			'name'    => __( 'Delete logs', ATUM_LOGS_TEXT_DOMAIN ) . $al_label,
			'desc'    => __( 'Select a date range or delete all the action logs at once.', ATUM_LOGS_TEXT_DOMAIN ),
			'type'    => 'script_runner',
			'options' => array(
				'button_text'   => __( 'Delete Range', ATUM_LOGS_TEXT_DOMAIN ),
				'button_style'  => 'danger',
				'script_action' => 'atum_tool_al_remove_logs',
				'wrapper_class' => 'range-remover',
			),
		);

		return $defaults;

	}

	/**
	 * Outputs the UI for the logs remover tool
	 *
	 * @since 0.5.1
	 *
	 * @param array $field_atts
	 */
	public function logs_remover_tool_ui( $field_atts ) {

		if ( 'al_tool_remove_logs' === $field_atts['id'] ) {

			AtumHelpers::load_view( ATUM_LOGS_PATH . 'views/tools/logs-remover', compact( 'field_atts' ) );

		}
	}

	/**
	 * Add Action Logs tools to ATUM CLI
	 *
	 * @since 1.4.2
	 */
	public function add_tools_to_atum_cli() {
		\WP_CLI::do_hook( 'before_add_command:atum', $this->add_settings_defaults( [] ), __NAMESPACE__ );
	}


	/*******************
	 * Instance methods
	 *******************/

	/**
	 * Cannot be cloned
	 */
	public function __clone() {

		_doing_it_wrong( __FUNCTION__, esc_attr__( 'Cheatin&#8217; huh?', ATUM_LOGS_TEXT_DOMAIN ), '1.0.0' );
	}

	/**
	 * Cannot be serialized
	 */
	public function __sleep() {

		_doing_it_wrong( __FUNCTION__, esc_attr__( 'Cheatin&#8217; huh?', ATUM_LOGS_TEXT_DOMAIN ), '1.0.0' );
	}

	/**
	 * Get Singleton instance
	 *
	 * @return Settings instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}
