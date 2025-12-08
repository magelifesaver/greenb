<?php
namespace Barn2\Plugin\WC_Fast_Cart;

use Barn2\Plugin\WC_Fast_Cart\Dependencies\Lib\Registerable;
use Barn2\Plugin\WC_Fast_Cart\Dependencies\Lib\Service\Premium_Service;

/**
 * Sets up settings preview feature
 *
 * @package   Barn2\woocommerce-fast-cart
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Frontend_Preview implements Registerable, Premium_Service {

	private static $features_enabled;

	protected $plugin;

	/**
	 * Constructor
	 *
	 * @param Plugin $plugin Fast Cart plugin object
	 */
	public function __construct( Plugin $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Register the service
	 *
	 * @return void
	 */
	public function register() {

		$this->try_cancel();

		$this->setup_installed_features();
		$this->try_install();

		add_filter( 'wfc_settings', [ $this, 'install_preview' ] );
		add_action( 'wp_footer', [ $this, 'preview_bar' ] );

		if ( current_user_can( 'manage_woocommerce' ) && ! empty( self::$features_enabled ) ) {
			add_filter( 'show_admin_bar', '__return_false' );
		}
	}

	/**
	 * Test to see if the user requested exiting the feature preview and then conditionally exit.
	 *
	 * @return void
	 */
	public function try_cancel() {

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		if ( empty( $_GET['_wfc-preview-cancel'] ) ) {
			return;
		}

		if ( wp_verify_nonce( $_GET['_wfc-preview-cancel'], 'cancel_barn2_preview' ) ) {
			setcookie( 'wfc_preview_features', '', time() - 3600 * 24, '/' );
			$url = remove_query_arg( [ '_wfc-preview-cancel', '_wfc-preview' ] );

			wp_safe_redirect( $url );
			exit;
		}
	}

	/**
	 * Check to see if the user requested a feature preview and conditionally install it.
	 *
	 * @return void
	 */
	public function try_install() {

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification
		if ( empty( $_GET['_wfc-preview'] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification
		$feature = sanitize_title( $_GET['_wfc-preview'] );

		if ( $this->is_feature_allowed( $feature ) ) {
			$this->enable_feature( $feature );
		}

		add_action( 'wp', [ $this, 'setup_preview_cart' ] );
	}

	/**
	 * Add some sample products to the cart if it's empty.
	 *
	 * @return void
	 */
	public function setup_preview_cart() {

		if ( WC()->cart->is_empty() ) {
			$test_products = wc_get_products(
				[
					'limit'        => 2,
					'status'       => 'publish',
					'type'         => 'simple',
					'visibility'   => 'visible',
					'stock_status' => 'instock',
				]
			);
			if ( ! empty( $test_products ) ) {
				foreach ( $test_products as $product ) {
					WC()->cart->add_to_cart( $product->get_id() );
				}
			}
		}
	}

	/**
	 * Test to see if the feature requested is a valid previewable feature.
	 *
	 * @param string $feature Feature slug
	 * @return boolean Is the feature allowed?
	 */
	public function is_feature_allowed( $feature ) {

		$features = $this->allowed_features();

		return isset( $features[ $feature ] );
	}

	/**
	 * Returns list of previewable features.
	 *
	 * @return array
	 */
	private function allowed_features() {

		return [
			'wfc-checkout' => [
				'label'   => __( 'Show hover button for Quick View', 'wc-fast-cart' ),
				'setting' => 'enable_fast_checkout',
				'value'   => true,
			],
		];
	}

	/**
	 * Install enabled features.
	 *
	 * @return void
	 */
	public function setup_installed_features() {

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			self::$features_enabled = [];
			return;
		}

		$data = $_COOKIE['wfc_preview_features'] ?? [];

		if ( empty( $data ) ) {
			self::$features_enabled = [];
			return;
		}

		$data = json_decode( stripslashes( $data ), true );
		if ( empty( $data ) ) {
			self::$features_enabled = [];
			return;
		}

		self::$features_enabled = $data;
	}

	/**
	 * Return list of enabled features.
	 *
	 * @return array
	 */
	public static function enabled_features() {

		return array_keys( self::$features_enabled );
	}

	/**
	 * Test to see if a feature is enabled.
	 *
	 * @param string $feature Feature slug
	 * @return boolean
	 */
	public static function is_feature_enabled( $feature ) {

		return isset( self::$features_enabled[ $feature ] );
	}

	/**
	 * Enable a feature.
	 *
	 * @param string $feature Feature slug.
	 * @return void
	 */
	private function enable_feature( $feature ) {

		self::$features_enabled[ $feature ] = true;

		setcookie( 'wfc_preview_features', wp_json_encode( self::$features_enabled ), 0, '/' );
	}

	/**
	 * Add preview setting to Fast Cart settings array.
	 *
	 * @param array $settings
	 * @return array Filtered settings.
	 */
	public function install_preview( $settings ) {

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return $settings;
		}

		$all_features = $this->allowed_features();

		$enabled = self::enabled_features();
		if ( is_array( $enabled ) ) {
			foreach ( $enabled as $key ) {
				$feature = $all_features[ $key ] ?? null;
				if ( empty( $feature ) || empty( $feature['setting'] ) || empty( $feature['value'] ) ) {
					continue;
				}
				$settings[ $feature['setting'] ] = $feature['value'];
			}
		}

		return $settings;
	}

	/**
	 * Outputs feature preview control bar.
	 *
	 * @return void
	 */
	public function preview_bar() {

		// phpcs:ignore WordPress.Security.NonceVerification
		if ( ! current_user_can( 'manage_woocommerce' ) || empty( self::$features_enabled ) || isset( $_GET['wfc-checkout'] ) || isset( $_GET['wfc-cart'] ) ) {
			return;
		}

		$nonce = wp_create_nonce( 'cancel_barn2_preview' );

		$cancel_url   = add_query_arg( '_wfc-preview-cancel', $nonce );
		$settings_url = add_query_arg( '_wfc-preview-cancel', $nonce, $this->plugin->get_settings_page_url() );

		?>

		<div id="barn2-preview-bar" class="barn2-preview-bar">
			<p class="barn2-preview-bar__text"><?php echo esc_html__( 'You are previewing a feature of WooCommerce Fast Cart.', 'wc-fast-cart' ); ?></p>
			<a href="<?php echo esc_url( $settings_url ); ?>" class="barn2-preview-bar__link" id="barn2-preview-bar--settings">&lsaquo; <?php echo esc_html__( 'Back to Settings', 'wc-fast-cart' ); ?></a>
			<a href="<?php echo esc_url( $cancel_url ); ?>" class="barn2-preview-bar__btn" id="barn2-preview-bar--cancel"><?php echo esc_html__( 'Close Preview', 'wc-fast-cart' ); ?></a>
		</div>
		<div id="barn2-preview-bar--borders" class="barn2-preview-bar--borders"></div>
		<style>
			html {
				margin-top: 20px;
			}
			.barn2-preview-bar {
				font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol";
				font-size: 16px;
				line-height: 1em;

				box-sizing: border-box;
				position: fixed;
				top: 0;
				left: 0;
				width: 100%;
				padding: 15px 20px;
				background: #03A0C7;
				color: white;

				display: flex;
				align-items: center;

				transition: opacity 0.3s;
				transition-delay: 1s;

				opacity: 0;
				z-index: 999998;
			}
			.barn2-preview-bar--borders {
				position: fixed;
				bottom: 0;
				left: 0;
				width: 100%;
				height: 20px;
				background: #03A0C7;
				z-index: 999997;

				transition: opacity 0.3s;
				transition-delay: 1s;

				opacity: 0;
			}
			.barn2-preview-bar--borders::before,
			.barn2-preview-bar--borders::after {
				content: '';
				position: fixed;
				top: 0;
				left: 0;
				height: 100%;
				width: 20px;
				background: #03A0C7;
				z-index: 999997;
			}
			.barn2-preview-bar--borders::after {
				left: auto;
				right: 0;
			}
			.barn2-preview-bar * {
				box-sizing: border-box !important;
				font-size: inherit !important;
				font-family: inherit !important;
				line-height: inherit !important;
			}
			.barn2-preview-bar p.barn2-preview-bar__text {
				margin: 0 auto 0 0 !important;
				padding: 0 !important;
				color: inherit !important;
			}
			.barn2-preview-bar a.barn2-preview-bar__link {
				color: inherit !important;
				font-weight: bold !important;
				white-space: nowrap;
			}
			.barn2-preview-bar a.barn2-preview-bar__btn {
				display: inline-block;
				margin: 0 0 0 15px !important;
				padding: 10px 20px !important;
				color: white !important;
				background: #FFB608;
				font-weight: bold !important;
				text-transform: uppercase !important;
				border-radius: 20px !important;
				transition: 0.3s;
				white-space: nowrap;
			}
			.barn2-preview-bar a.barn2-preview-bar__btn:hover,
			.barn2-preview-bar a.barn2-preview-bar__btn:focus,
			.barn2-preview-bar a.barn2-preview-bar__btn:active {
				color: #03A0C7 !important;
				background: white !important;
			}
			.wfc-open-cart-button.at-top {
				top: 75px;
			}
			@media (max-width: 767px) {
				.barn2-preview-bar {
					flex-wrap: wrap;
				}
				.barn2-preview-bar p.barn2-preview-bar__text {
					flex: 1 1 100%;
					margin-bottom: 15px !important;
				}
				.barn2-preview-bar a.barn2-preview-bar__link {
					margin-right: auto;
				}
				.barn2-preview-bar--borders {
					height: 5px;
				}
				.barn2-preview-bar--borders::before,
				.barn2-preview-bar--borders::after {
					width: 5px;
				}
			}
		</style>
		<script>
			var barn2previewBar = document.getElementById( 'barn2-preview-bar' );
			var barn2previewBarBorders = document.getElementById( 'barn2-preview-bar--borders' );
			document.addEventListener( 'DOMContentLoaded', function () {
				barn2previewBar.style.opacity = '1';
				barn2previewBarBorders.style.opacity = '1';
			} );
		</script>

		<?php
	}
}
