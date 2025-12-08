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

namespace Kestrel\Account_Funds\Admin\Screens\Traits;

defined( 'ABSPATH' ) or exit;

use Kestrel\Account_Funds\Admin\Screens\Settings_Screen;
use Kestrel\Account_Funds\Admin\Screens\Store_Credit\Reward_Screen;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Helpers\Strings;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\WordPress;

/**
 * Trait for loading WooCommerce scripts in admin screens.
 *
 * The constructor of the class needs to hook into the `woocommerce_screen_ids` filter for this to work.
 *
 * @see Reward_Screen::__construct()
 * @see Settings_Screen::__construct()
 *
 * @since 4.0.0
 */
trait Loads_WooCommerce_Scripts {

	/**
	 * Loads the WooCommerce scripts in the current screen.
	 *
	 * @since 4.0.0
	 *
	 * @param mixed|string[] $screens
	 * @return mixed|string[]
	 */
	protected function load_woocommerce_scripts( $screens ) {

		// @phpstan-ignore-next-line type safety checks are legit
		if ( ! is_array( $screens ) || ! is_callable( [ $this, 'get_id' ] ) ) {
			return $screens;
		}

		$screen = WordPress\Admin::current_screen();

		if ( ! $screen || ! $screen->id || in_array( $screen->id, $screens, true ) ) {
			return $screens;
		}

		if ( Strings::string( $screen->id )->ends_with( $this->get_id() ) ) {
			$screens[] = $screen->id;
		}

		return $screens;
	}

}
