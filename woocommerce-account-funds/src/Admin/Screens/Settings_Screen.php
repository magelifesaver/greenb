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

namespace Kestrel\Account_Funds\Admin\Screens;

defined( 'ABSPATH' ) or exit;

use Kestrel\Account_Funds\Admin\Screens\Traits\Loads_WooCommerce_Scripts;
use Kestrel\Account_Funds\Plugin;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Admin\Screens\Screen;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Settings\Exceptions\Setting_Exception;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Settings\Field;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Settings\Types\Type;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\WooCommerce\Settings\Settings_Section;
use Kestrel\Account_Funds\Settings\Store_Credit_Account_Top_Up;
use Kestrel\Account_Funds\Settings\Store_Credit_Label;
use WC_Admin_Settings;

/**
 * Settings screen for store credit.
 *
 * @since 4.0.0
 *
 * @method static Plugin plugin()
 */
final class Settings_Screen extends Screen {
	use Loads_WooCommerce_Scripts;

	/** @var string screen ID */
	public const ID = 'store-credit-settings';

	/** @var Settings_Section[] settings */
	private array $settings = [];

	/** @var array<array<string, mixed>>|null adapted settings */
	private ?array $adapted_settings = null;

	/**
	 * Screen constructor.
	 *
	 * @since 4.0.0
	 *
	 * @param string $id
	 * @param string $title
	 */
	protected function __construct( string $id = '', string $title = '' ) {

		parent::__construct( $id, $title );

		$this->settings = $this->get_settings();

		self::add_filter( 'woocommerce_screen_ids', [ $this, 'load_woocommerce_scripts' ] );
	}

	/**
	 * Returns the screen title.
	 *
	 * @since 4.0.0
	 *
	 * @return string
	 */
	public function get_menu_title() : string {

		return __( 'Settings', 'woocommerce-account-funds' );
	}

	/**
	 * Returns the screen title.
	 *
	 * @since 4.0.0
	 *
	 * @return string
	 */
	public function get_page_title() : string {

		return __( 'Account funds settings', 'woocommerce- account-funds' );
	}

	/**
	 * Returns the plugin settings handled by this screen.
	 *
	 * @since 4.0.0
	 *
	 * @return Settings_Section[]
	 */
	protected function get_settings() : array {

		if ( ! empty( $this->settings ) ) {
			return $this->settings;
		}

		$this->settings = [
			Settings_Section::create( [
				'title'       => __( 'Labels', 'woocommerce-account-funds' ),
				'description' => __( 'Determine how store credit is presented to customers. Leave blank to use the default "Store credit".', 'woocommerce-account-funds' ),
				'settings'    => Store_Credit_Label::get_settings(),
			] ),
			Settings_Section::create( [
				'title'       => __( 'Funding', 'woocommerce-account-funds' ),
				'description' => __( 'Determine if customers are able to increase their store credit from their account page.', 'woocommerce-account-funds' ),
				'settings'    => Store_Credit_Account_Top_Up::get_settings(),
			] ),
		];

		return $this->settings;
	}

	/**
	 * Returns the adapted settings.
	 *
	 * @since 4.0.0
	 *
	 * @return array<array<string, mixed>>
	 */
	protected function get_adapted_settings() : array {

		if ( null !== $this->adapted_settings ) {
			return $this->adapted_settings;
		}

		$this->adapted_settings = [];

		foreach ( $this->get_settings() as $section ) {
			$this->adapted_settings = array_merge( $this->adapted_settings, $section->get_settings_definitions() );
		}

		return $this->adapted_settings;
	}

	/**
	 * Outputs the screen content with the settings fields.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	protected function output() : void {

		wp_enqueue_media();
		wp_enqueue_script( self::plugin()->handle( 'settings' ), self::plugin()->assets_url( 'js/admin/settings.js' ), [ 'jquery' ], self::plugin()->version(), [ 'in_footer' => true ] );
		wp_enqueue_style( self::plugin()->handle( 'settings' ), self::plugin()->assets_url( 'css/admin/settings.css' ), [], self::plugin()->version() );

		$hook = self::plugin()->hook( 'settings' );

		$this->save_settings( $hook );

		$payment_gateway_note = sprintf(
			/* translators: Placeholder: %1$s - opening link tag, %2$s - closing link tag */
			__( 'To configure the store credit payment gateway and let customers pay with their store credit, please visit the %1$s store credit payment gateway settings%2$s.', 'woocommerce-account-funds' ),
			'<a href="' . esc_url( self::plugin()->gateway()->settings_url() ) . '">',
			'</a>'
		);

		?>
		<div class="wrap woocommerce">
			<?php WC_Admin_Settings::show_messages(); ?>
			<form method="post" id="mainform" action="" enctype="multipart/form-data">
				<h1><?php echo esc_html( $this->get_page_title() ); ?></h1>
				<br class="clear">
				<?php

				echo wp_kses_post( '<p>' . $payment_gateway_note . '</p>' );

				WC_Admin_Settings::output_fields( $this->get_adapted_settings() );

				wp_nonce_field( $hook );

				?>
				<p class="submit">
					<button name="save" class="button button-primary" type="submit" value="<?php esc_attr_e( 'Save changes', 'woocommerce-account-funds' ); ?>"><?php esc_html_e( 'Save changes', 'woocommerce-account-funds' ); ?></button>
				</p>
			</form>
		</div>
		<?php
	}

	/**
	 * Saves the settings.
	 *
	 * @since 4.0.0
	 *
	 * @param string $admin_referer hook
	 * @return void
	 */
	protected function save_settings( string $admin_referer ) : void {

		$posted_data = $_POST; // phpcs:ignore

		if ( empty( $_POST ) ) {
			return;
		}

		check_admin_referer( $admin_referer );

		foreach ( $this->get_settings() as $section ) {

			foreach ( $section->get_settings() as $setting ) {

				$type  = $setting->get_type();
				$value = $posted_data[ $setting->get_name() ] ?? null;

				if ( '' === $value ) {
					$value = null;
				}

				if ( $type instanceof Type && $type->get_field() === Field::CHECKBOX ) {
					$value = wc_bool_to_string( $value );
				}

				$setting->set_value( $value );

				try {
					$setting->save();
				} catch ( Setting_Exception $exception ) {
					// validation exceptions are caught and displayed as errors
					WC_Admin_Settings::add_error( esc_html( $exception->getMessage() ) );
				}
			}
		}
	}

}
