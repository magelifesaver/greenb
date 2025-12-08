<?php
/**
 * Upgrade tasks
 *
 * @package        AtumBarcodes
 * @subpackage     Inc
 * @author         BE REBEL - https://berebel.studio
 * @copyright      ©2025 Stock Management Labs™
 *
 * @since          0.1.2
 */

namespace AtumBarcodes\Inc;

defined( 'ABSPATH' ) || die;

use Atum\Addons\Addons;
use Atum\Inc\Globals as AtumGlobals;

class Upgrade {

	/**
	 * Whether ATUM barcodes PRO is being installed for the first time
	 *
	 * @var bool
	 */
	private $is_fresh_install = FALSE;

	/**
	 * Upgrade constructor
	 *
	 * @since 0.1.2
	 *
	 * @param string $db_version The ATUM Action Logs version saved in db as an option.
	 */
	public function __construct( $db_version ) {

		if ( ! $db_version || version_compare( $db_version, '0.0.1', '<=' ) ) {
			$this->is_fresh_install = TRUE;
		}

		// Update the db version to the current ATUM Barcodes PRO version before upgrade to prevent various executions.
		update_option( 'atum_barcodes_pro_version', ATUM_BARCODES_VERSION );

		// Make sure any old status transient is cleared for the add-on.
		Addons::delete_status_transient( 'Barcodes PRO' );

		/************************
		 * UPGRADE ACTIONS START
		 **********************!*/

		/* version 0.1.3: Added the barcode_type column to atum_product_data table. */
		if ( version_compare( $db_version, '0.1.3', '<' ) ) {
			$this->add_barcode_type_column();
		}

		/**********************
		 * UPGRADE ACTIONS END
		 ********************!*/

		do_action( 'atum/barcodes_pro/after_upgrade', $db_version );

	}

	/**
	 * Add the barcode_type column to atum_product_data table
	 *
	 * @since 0.1.3
	 */
	private function add_barcode_type_column() {

		global $wpdb;

		$db_name         = DB_NAME;
		$atum_data_table = $wpdb->prefix . AtumGlobals::ATUM_PRODUCT_DATA_TABLE;

		// Avoid adding the column if was already added.
		$column_exist = $wpdb->prepare( "
			SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
			WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND column_name = 'barcode_type'
		", $db_name, $atum_data_table );

		// Add the new column to the table.
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( ! $wpdb->get_var( $column_exist ) ) {
			$wpdb->query( "ALTER TABLE $atum_data_table ADD `barcode_type` VARCHAR(20) DEFAULT NULL;" ); // phpcs:ignore WordPress.DB.PreparedSQL
		}
		else {
			$wpdb->query( "ALTER TABLE $atum_data_table MODIFY `barcode_type` VARCHAR(20) DEFAULT NULL;" ); // phpcs:ignore WordPress.DB.PreparedSQL
		}

	}

}
