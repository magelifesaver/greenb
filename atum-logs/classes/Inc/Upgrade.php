<?php
/**
 * Upgrade tasks class
 *
 * @package         AtumLogs
 * @subpackage      Inc
 * @author          BE REBEL - https://berebel.studio
 * @copyright       ©2025 Stock Management Labs™
 *
 * @since           1.3.6.1
 */

namespace AtumLogs\Inc;

defined( 'ABSPATH' ) || die;

use Atum\Addons\Addons;

class Upgrade {

	/**
	 * The current ATUM Action Logs version
	 *
	 * @var string
	 */
	private $current_version = '';

	/**
	 * Whether ATUM Action Logs is being installed for the first time
	 *
	 * @var bool
	 */
	private $is_fresh_install = FALSE;

	/**
	 * Upgrade constructor
	 *
	 * @since 1.3.6.1
	 *
	 * @param string $db_version  The ATUM Action Logs version saved in db as an option.
	 */
	public function __construct( $db_version ) {

		$this->current_version = $db_version;

		if ( ! $db_version || version_compare( $db_version, '0.0.1', '<=' ) ) {
			$this->is_fresh_install = TRUE;
		}
		
		// Update the db version to the current ATUM Actin Logs version before upgrade to prevent various executions.
		update_option( 'atum_action_logs_version', ATUM_LOGS_VERSION );

		// Make sure any old status transient is cleared for the add-on.
		Addons::delete_status_transient( 'Action Logs' );

		/************************
		 * UPGRADE ACTIONS START
		 **********************!*/

		/**********************
		 * UPGRADE ACTIONS END
		 ********************!*/

		do_action( 'atum/action_logs/after_upgrade', $db_version );

	}

}
