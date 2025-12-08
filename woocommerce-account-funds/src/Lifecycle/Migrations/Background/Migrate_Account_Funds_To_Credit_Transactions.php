<?php
/**
 * Account Funds for WooCommerce
 *
 * This source file is subject to the GNU General Public License v3.0 that is bundled with this plugin in the file license.txt.
 *
 * Please do not modify this file if you want to upgrade this plugin to newer versions in the future.
 * If you want to customize this file for your needs, please review our developer documentation.
 * Join our developer program at https://kestrelwp.com/developers
 *
 * @author    Kestrel
 * @copyright Copyright (c) 2015-2025 Kestrel Commerce LLC [hey@kestrelwp.com]
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

declare( strict_types = 1 );

namespace Kestrel\Account_Funds\Lifecycle\Migrations\Background;

defined( 'ABSPATH' ) or exit;

use Kestrel\Account_Funds\Lifecycle\Database;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Jobs\Background_Job;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Logger;

/**
 * Background migration job to migrate user meta account funds to credit transactions.
 *
 * @since 4.0.0
 */
final class Migrate_Account_Funds_To_Credit_Transactions extends Background_Job {

	/** @var string */
	protected $action = 'migrate_funds_to_ledger';

	/**
	 * Migrate user meta account funds to credit transactions.
	 *
	 * @since 4.0.0
	 *
	 * @param int|mixed $item user ID
	 * @return bool
	 */
	protected function task( $item ) {

		$user_id = is_numeric( $item ) ? intval( $item ) : 0;

		if ( $user_id >= 0 ) {
			Database::migrate_legacy_user_account_funds( $user_id, 'Migrated in background from user meta (plugin version ' . self::plugin()->version() . ')' );
		}

		return false; // remove the item from the queue after processing
	}

	/**
	 * Called when the background job is completed.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	protected function completed() {

		parent::completed();

		update_option( self::plugin()->key( 'legacy_account_funds_migration_completed_at' ), gmdate( 'Y-m-d H:i:s' ), false );

		Logger::notice( 'Migration of account funds from user meta to credit transactions completed.' );
	}

}
