<?php
/**
 * Export Pro + Atum Action Logs integration
 *
 * @since       0.4.1
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2025 Stock Management Labs™
 *
 * @package     AtumLogs
 * @subpackage  Integrations
 */

namespace AtumLogs\Integrations;

use Atum\Components\AtumCache;
use AtumExport\Inc\Helpers as AtumEPHelpers;
use AtumExport\Models\Base\AtumExportEntity;
use AtumExport\Models\Template;
use AtumLogs\Inc\Helpers;
use AtumLogs\Models\LogEntry;
use AtumLogs\Models\LogModel;


defined( 'ABSPATH' ) || die;


class ExportPro {

	/**
	 * The singleton instance holder
	 *
	 * @var ExportPro
	 */
	private static $instance;

	/**
	 * Store whether the template being saved es new or not.
	 *
	 * @since 0.5.1
	 *
	 * @var boolean
	 */
	private $is_new_template;

	/**
	 * ExportPro singleton constructor
	 *
	 * @since 0.4.1
	 */
	private function __construct() {

		if ( is_admin() ) {
			$this->register_admin_hooks();
		}

		$this->register_global_hooks();

	}

	/**
	 * Register the hooks for the admin side
	 *
	 * @since 0.4.1
	 */
	public function register_admin_hooks() {

		add_action( 'atum/ajax/export_pro/run_export', array( $this, 'ep_run_export' ), 10, 3 );
		add_action( 'atum/ajax/export_pro/save_template', array( $this, 'ep_save_template' ), 10, 2 );
		add_action( 'atum/export/after_save_template', array( $this, 'ep_after_save_template' ), 10, 2 );
		add_action( 'wp_ajax_aep_prepare_import', array( $this, 'ep_before_import' ), 1 );
		add_action( 'atum/export/import/after_import_rows', array( $this, 'ep_after_import' ), 10, 3 );
		add_action( 'atum/export/after_delete_template', array( $this, 'ep_after_delete_template' ), 10, 2 );
		add_action( 'atum/ajax/export_pro/download_export', array( $this, 'ep_download_export' ), 10, 2 );

	}

	/**
	 * Register the global hooks
	 *
	 * @since 0.4.1
	 */
	public function register_global_hooks() {

		$templates = Template::get_all_templates( [
			'view'        => 'recurring',
			'non_expired' => TRUE,
		] );

		foreach ( $templates as $export_template ) {
			add_action( 'atum_export_run_export_' . $export_template->id, array(
				$this,
				'ep_run_scheduled_export',
			) );
		}

		add_action( 'atum/export/after_send_export_email', array( $this, 'ep_send_export_email' ), 10, 4 );
		add_action( 'atum/export/after_unlink_template_files', array( $this, 'ep_unlink_files' ) );

	}

	/**
	 * Logs manually export template
	 *
	 * @since 0.3.1
	 *
	 * @param Template $template
	 * @param int      $template_id
	 * @param string   $file_name
	 */
	public function ep_run_export( $template, $template_id, $file_name ) {

		$data = $_POST;

		if ( empty( $data['entity'] ) ) {

			$log_data = [
				'source' => LogModel::SRC_EP,
				'module' => LogModel::MOD_EXPORT,
				'data'   => [
					'template'  => $this->get_template_data_formatted( $template ),
					'file_name' => $file_name,
				],
				'entry'  => LogEntry::ACTION_EP_NO_TEMPLATE_EXPORT,
			];
		}
		else {

			parse_str( $data['settings'], $settings );

			unset( $data['action'], $data['security'], $data['settings'] );

			$data_merged = array_merge( $settings, $data );

			if ( $template_id && is_numeric( $template_id ) ) {
				$data_merged = array_merge( $data_merged, [ 'template_id' => $template_id ] );
			}
			elseif ( ! $template->__get( 'id' ) instanceof \WP_Error ) {
				$data_merged = array_merge( $data_merged, [ 'template_id' => $template->__get( 'id' ) ] );
			}

			$log_data = [
				'source' => LogModel::SRC_EP,
				'module' => LogModel::MOD_EXPORT,
				'data'   => $data_merged,
				'entry'  => LogEntry::ACTION_EP_TEMPLATE_EXPORT,
			];
		}

		LogModel::maybe_save_log( $log_data );

	}

	/**
	 * Logs saved a template as new custom template
	 *
	 * @since 0.3.1
	 *
	 * @param Template $template
	 * @param int      $template_id
	 */
	public function ep_save_template( $template, $template_id ) {

		$template_data = $this->get_template_data_formatted( $template );

		$transient_key_template = AtumCache::get_transient_key( 'log_export_data_' . $template_id );
		AtumCache::set_transient( $transient_key_template, $template_data, MINUTE_IN_SECONDS, TRUE );
	}

	/**
	 * Logs changes in template data
	 *
	 * @param mixed $data
	 * @param int   $template_id
	 *
	 * @since 0.3.1
	 */
	public function ep_after_save_template( $data, $template_id ) {

		if ( empty( $_POST['template_id']) ) {

			$template      = new Template( $template_id );
			$template_data = $this->get_template_data_formatted( $template );

			$log_data = [
				'source' => LogModel::SRC_EP,
				'module' => LogModel::MOD_EXPORT,
				'data'   => $template_data,
				'entry'  => LogEntry::ACTION_EP_CUSTOM_TEMPLATE,
			];
			LogModel::maybe_save_log( $log_data );
		}
		else {

			$transient_key_template = AtumCache::get_transient_key( 'log_export_data_' . $template_id );
			$old_data               = AtumCache::get_transient( $transient_key_template, TRUE );
			$old_fields             = array();
			$new_fields             = array();

			if ( empty( $old_data ) ) {
				return;
			}

			foreach ( $data as $i => $v ) {
				if ( is_object( $v ) ) {
					$data[ $i ] = (array) $v;
				}
			}

			// Parse old fields.
			if ( ! empty( $old_data['data'] ) ) {
				foreach ( $old_data['data'] as $dt ) {
					$index                = ! empty( $dt['settings']['column_name'] ) ? $dt['settings']['column_name'] : $dt['name'];
					$old_fields[ $index ] = $dt;
				}
			}

			// Parse new fields.
			foreach ( $data['data'] as $dt ) {
				$dt['settings']       = empty( $dt['settings'] ) || '[]' === $dt['settings'] ? [] : json_decode( stripslashes( $dt['settings'] ), TRUE );
				$dt['filters']        = empty( $dt['filters'] ) || '[]' === $dt['filters'] ? [] : json_decode( stripslashes( $dt['filters'] ), TRUE );
				$index                = ! empty( $dt['settings']['column_name'] ) ? $dt['settings']['column_name'] : $dt['name'];
				$new_fields[ $index ] = $dt;
			}

			$changes = [];

			foreach ( $old_fields as $field => $old_field_data ) {
				if ( array_key_exists( $field, $new_fields ) ) {

					foreach ( [ 'settings', 'filters' ] as $attr ) {
						$new_settings = $new_fields[ $field ][ $attr ];

						if ( isset( $old_field_data[ $attr ] ) && is_array( $old_field_data[ $attr ] ) && ! empty( $old_field_data[ $attr ] ) ) {

							foreach ( $old_field_data[ $attr ] as $setting_name => $setting_value ) {
								if ( $setting_value !== $new_settings[ $setting_name ] ) {

									if ( empty( $changed[ $field ] ) ) {
										$changed[ $field ] = [];
									}
									if ( empty( $changed[ $field ][ $attr ] ) ) {
										$changed[ $field ][ $attr ] = [];
									}

									$changed[ $field ][ $attr ][ $setting_name ] = [
										'old_value' => $setting_value,
										'new_value' => isset( $new_settings[ $setting_name ] ) ? $new_settings[ $setting_name ] : '',
									];

								}
							}
						}
						elseif ( ! empty( $new_settings ) ) {

							foreach ( $new_settings as $setting_name => $setting_value ) {

								if ( $setting_value ) {

									if ( empty( $changed[ $field ] ) ) {
										$changed[ $field ] = [];
									}
									if ( empty( $changed[ $field ][ $attr ] ) ) {
										$changed[ $field ][ $attr ] = [];

										$changed[ $field ][ $attr ][ $setting_name ] = [
											'old_value' => '',
											'new_value' => $setting_value,
										];
									}

								}
							}

						}
					}

					unset( $old_fields[ $field ], $new_fields[ $field ] );

				}
			}

			foreach ( $old_data as $index => $odt ) {
				switch ( $index ) {
					case 'data':
					case 'created':
					case 'modified':
					case 'template_id':
						break;
					case 'time':
					case 'end_date':
					case 'last_export':
						if ( 'time' === $index ) {
							$data[ $index ] = gmdate( 'H:i:s', strtotime( $data[ $index ] ) );
						}
						else {
							$data[ $index ] = $this->format_date( $data[ $index ] );
						}
						// no break.
					default:
						if ( $odt !== $data[ $index ] ) {
							$changes[ $index ] = array(
								'template_id' => $template_id,
								'name'        => $data['name'],
								'field'       => $index,
								'old_data'    => $odt,
								'new_data'    => $data[ $index ],
							);
						}
						break;
				}
				if ( 'data' === $index ) {
					continue;
				}
			}

			if ( ! empty( $changes ) ) {
				foreach ( $changes as $name => $change ) {

					$log_data = [
						'source' => LogModel::SRC_EP,
						'module' => LogModel::MOD_EXPORT,
						'data'   => $change,
						'entry'  => LogEntry::ACTION_EP_TEMPLATE_EDIT,
					];
					LogModel::maybe_save_log( $log_data );

				}
			}

			$result_data = array();

			if ( ! empty( $new_fields ) ) {
				$result_data['added'] = $new_fields;
			}
			if ( ! empty( $old_fields ) ) {
				$result_data['deleted'] = $old_fields;
			}
			if ( ! empty( $changed ) ) {
				$result_data['changed'] = $changed;
			}

			if ( ! empty( $result_data ) ) {

				$result_data['template_id'] = $template_id;
				$result_data['name']        = $data['name'];

				$log_data = [
					'source' => LogModel::SRC_EP,
					'module' => LogModel::MOD_EXPORT,
					'data'   => $result_data,
					'entry'  => LogEntry::ACTION_EP_TEMPLATE_FIELDS,
				];
				LogModel::maybe_save_log( $log_data );

			}
		}
	}
	
	/**
	 * Log template deletion.
	 *
	 * @since 0.5.1
	 *
	 * @param Template $template
	 * @param boolean  $deleted
	 */
	public function ep_after_delete_template( $template, $deleted ) {
		
		$data = $this->get_template_data_formatted( $template );
		
		$log_data = [
			'source' => LogModel::SRC_EP,
			'module' => LogModel::MOD_EXPORT,
			'data'   => $data,
			'entry'  => $deleted ? LogEntry::ACTION_EP_TEMPLATE_DEL_SUCCESS : LogEntry::ACTION_EP_TEMPLATE_DEL_FAIL,
		];
	
		LogModel::maybe_save_log( $log_data );
	
	}
	
	/**
	 * Logs a scheduled export
	 *
	 * @param int $template_id
	 *
	 * @since 0.3.1
	 */
	public function ep_run_scheduled_export( $template_id ) {

		$template = new Template( $template_id );
		$data     = $this->get_template_data_formatted( $template );
		
		$log_data = [
			'source' => LogModel::SRC_EP,
			'module' => LogModel::MOD_EXPORT,
			'data'   => $data,
			'entry'  => LogEntry::ACTION_EP_SCHEDULED_EXPORT,
		];
		LogModel::maybe_save_log( $log_data );

		if ( $template->is_export_saving() ) {

			$this->ep_export_save_file( $template_id );

		}

	}

	/**
	 * Logs export email send
	 *
	 * @param bool     $sent
	 * @param Template $template
	 * @param string   $subject
	 * @param string   $message
	 *
	 * @since 0.3.1
	 * @throws \WP_Error
	 */
	public function ep_send_export_email( $sent, $template, $subject, $message ) {

		$data                = $this->get_template_data_formatted( $template );
		$data['subject']     = $subject;
		$data['message']     = $message;
		$data['sent']        = $sent;
		$data['template_id'] = ( $template->__get('id') instanceof \WP_Error ) ? FALSE : $template->__get( 'id' );

		$log_data = [
			'source'      => LogModel::SRC_EP,
			'module'      => LogModel::MOD_EXPORT,
			'data'        => $data,
			'entry'       => $sent ? LogEntry::ACTION_EP_EMAIL_SENT : LogEntry::ACTION_EP_EMAIL_NOT_SENT,
		];
		LogModel::maybe_save_log( $log_data );

	}

	/**
	 * Logs save exported file
	 *
	 * @param int $template_id
	 *
	 * @since 0.3.1
	 */
	public function ep_export_save_file( $template_id ) {

		$template = new Template( $template_id );

		$upload_dir = AtumEPHelpers::get_aep_upload_dir( $template_id );

		$file_name = $upload_dir . $template->get_exported_template_file_name() . '.' . $template->file_type;

		if ( file_exists( $file_name ) ) {

			$log_data = [
				'source' => LogModel::SRC_EP,
				'module' => LogModel::MOD_EXPORT,
				'data'   => [
					'template_id' => $template_id,
					'file'        => $file_name,
				],
				'entry'  => LogEntry::ACTION_EP_FILE_STORED,
			];

			LogModel::maybe_save_log( $log_data );

		}

	}

	/**
	 * Logs export files deleting
	 *
	 * @param array $files
	 *
	 * @since 0.3.1
	 */
	public function ep_unlink_files( $files ) {

		$log_data = [
			'source' => LogModel::SRC_EP,
			'module' => LogModel::MOD_EXPORT,
			'data'   => $files,
			'entry'  => LogEntry::ACTION_EP_MAX_FILES_REACHED,
		];

		LogModel::maybe_save_log( $log_data );
	}

	/**
	 * Logs importation atempt/start
	 *
	 * @since 1.0.8
	 */
	public function ep_before_import() {

		check_ajax_referer( 'aep-nonce', 'security' );

		if ( empty( $_POST['import_settings'] ) || empty( $_POST['import_settings']['file_hash'] ) || empty( $_POST['import_settings']['data_template'] ) )
			return;

		$file_hash   = esc_attr( $_POST['import_settings']['file_hash'] );
		$template_id = absint( $_POST['import_settings']['data_template'] );

		$template      = new Template( $template_id );
		$template_data = $template->get_template_data();

		$log_data = [
			'source' => LogModel::SRC_EP,
			'module' => LogModel::MOD_IMPORT,
			'data'   => [
				'template_id' => $template_id,
				'name'        => '#' . $template_id,
				'file'        => $file_hash,
				'data'        => $_POST['import_settings'],
			],
			'entry'  => LogEntry::ACTION_EP_IMPORT_START,
		];

		if( FALSE !== $template_data ) {

			$log_data['data']['template_data'] = $template_data;

			if ( isset( $template_data['name'] ) ) {
				$log_data['data']['name'] = $template_data['name'];
			}
		}

		LogModel::maybe_save_log( $log_data );
	}

	/**
	 * Logs import file
	 *
	 * @param AtumExportEntity $entity
	 * @param Template         $template
	 * @param array            $result_counters
	 *
	 * @since 0.3.1
	 */
	public function ep_after_import( $entity, $template, $result_counters ) {

		$process = LogEntry::ACTION_EP_IMPORT_SUCCESS;

		if ( ! empty( $result_counters['error'] ) ) {

			$process = LogEntry::ACTION_EP_IMPORT_FAIL;
		}
		elseif ( ! empty( $result_counters['warning'] ) ) {

			$process = LogEntry::ACTION_EP_IMPORT_WARNING;
		}

		$template_data = $template->get_template_data();
		$template_id  = $template->__get('id') instanceof \WP_Error ? FALSE : $template->__get( 'id' );
		$template_name = isset( $template_data['name'] ) ? $template_data['name'] : '#' . $template_id;

		$log_data = [
			'source' => LogModel::SRC_EP,
			'module' => LogModel::MOD_IMPORT,
			'data'   => [
				'template_id' => $template_id,
				'name'        => $template_name,
				'data'        => $result_counters,
			],
			'entry'  => $process,
		];

		if ( isset( $template_data['name'] ) )
			$log_data['data']['name'] = $template_data['name'];

		LogModel::maybe_save_log( $log_data );

	}

	/**
	 * Get all the template data formatted (and including the template id).
	 *
	 * @since 0.5.1
	 *
	 * @param Template $template
	 *
	 * @return array
	 */
	private function get_template_data_formatted( $template ) {

		$data = json_decode( $template->get_template_data(), TRUE );


		if ( ! empty( $data ) ) {

			foreach ( $data as $data_key => $data_value ) {

				switch ( $data_key ) {

					// Date columns.
					case 'end_date':
					case 'created':
					case 'modified':
					case 'last_export':
						$data[ $data_key ] = $this->format_date( $data_value );

						break;

					default:
						break;

				}

			}

		}

		if ( ! isset( $data['template_id'] ) ) {
			$template_id = $template->__get( 'id' );

			$data['template_id'] = ( is_numeric( $template_id ) && ! $template->__get('id') instanceof \WP_Error ) ? $template_id : '';
		}

		return $data;
	}

	/**
	 * Ensure date is a string a it's properly formatted.
	 *
	 * @since 0.5.1
	 *
	 * @param string|array|\WC_DateTime $date
	 *
	 * @return string
	 */
	private function format_date( $date ) {

		$return_date = '';

		if ( $date ) {

			if ( $date instanceof \WC_DateTime ) {
				$return_date = $date->format( 'Y-m-d H:i:s' );
			}
			else {

				// if is an array casted from a WC_DateTime object.
				if ( is_array( $date ) && isset( $date['date'] ) ) {

					$time_zone = $date['timezone'];
					$date      = $date['date'];
				}
				else {
					$time_zone = 'UTC';
				}

				try {
					$date        = new \WC_DateTime( $date, new \DateTimeZone( $time_zone ) );
					$return_date = $date->format( 'Y-m-d H:i:s' );
				} catch ( \Exception $e ) {
					$return_date = '';
				}
			}

		}

		return $return_date;
	}

	/**
	 * Logs template download
	 *
	 * @since 1.0.0
	 *
	 * @param int           $template_id
	 * @param Template|null $template
	 */
	public function ep_download_export( $template_id, $template ) {

		$name = '';

		if ( is_null( $template ) ) {
			$template = new Template( $template_id );
		}

		if ( $template ) {
			$name = $template->__get( 'name' );
		}

		$log_data = [
			'source' => LogModel::SRC_EP,
			'module' => LogModel::MOD_IMPORT,
			'data'   => [
				'template_id' => $template_id,
				'name'        => $name,
			],
			'entry'  => LogEntry::ACTION_EP_TEMPLATE_DOWNLOAD,
		];

		LogModel::maybe_save_log( $log_data );
	}
	
	/********************
	 * Instance methods
	 ********************/

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
	 * @return ExportPro instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}
