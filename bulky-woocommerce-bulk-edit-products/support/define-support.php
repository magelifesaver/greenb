<?php

namespace BULKY\Support;

defined( 'ABSPATH' ) || exit;

class Define_Support {

	protected static $instance = null;

	public function __construct() {
		add_action( 'init', array( $this, 'support' ) );
		add_action( 'admin_init', [ $this, 'check_update' ] );
		add_action( 'vi_wbe_admin_field_auto_update_key', [ $this, 'auto_update_key' ] );
	}

	public static function instance() {
		return self::$instance == null ? self::$instance = new self : self::$instance;
	}

	public function support() {
		if ( ! class_exists( 'VillaTheme_Support_Pro' ) ) {
			include_once BULKY_CONST['plugin_dir'] . 'support/support.php';
		}

		new \VillaTheme_Support_Pro(
			array(
				'support'   => 'https://villatheme.com/supports/forum/plugins/bulky-woocommerce-bulk-edit-products',
				'docs'      => 'https://docs.villatheme.com/?item=bulky-woocommerce-bulk-edit-products',
				'review'    => 'https://codecanyon.net/downloads',
				'css'       => BULKY_CONST['dist_url'],
				'image'     => BULKY_CONST['img_url'],
				'slug'      => BULKY_CONST['slug'],
				'menu_slug' => 'vi_wbe_edit_products',
				'version'   => BULKY_CONST['version']
			)
		);
	}

	public function check_update() {
		if ( ! class_exists( 'VillaTheme_Plugin_Check_Update' ) ) {
			include_once BULKY_CONST['plugin_dir'] . 'support/check_update.php';
		}

		if ( ! class_exists( 'VillaTheme_Plugin_Updater' ) ) {
			include_once BULKY_CONST['plugin_dir'] . 'support/update.php';
		}

		$setting_url = admin_url( 'admin.php?page=vi_wbe_settings#/update' );
		$key         = get_option( 'vi_wbe_auto_update_key' );

		new \VillaTheme_Plugin_Check_Update (
			BULKY_CONST['version'],                    // current version
			'https://villatheme.com/wp-json/downloads/v3',  // update path
			BULKY_CONST['basename'],                  // plugin file slug
			BULKY_CONST['slug'],
			'98166', //Pro id on VillaTheme
			$key,
			$setting_url
		);

		new \VillaTheme_Plugin_Updater( BULKY_CONST['basename'], BULKY_CONST['slug'], $setting_url );
	}

	public function auto_update_key() {
		$key = get_option( 'vi_wbe_auto_update_key' );
		?>
        <table class="form-table">
            <tr>
                <th>
					<?php esc_html_e( 'Auto update key', 'bulky-woocommerce-bulk-edit-products' ); ?>
                </th>
                <td>
                    <div class="vi-ui action input">
                        <input type="text" name="vi_wbe_auto_update_key" value="<?php echo esc_attr( $key ); ?>">
                        <div class="villatheme-get-key-button vi-ui button small green"
                             data-href="https://api.envato.com/authorization?response_type=code&client_id=villatheme-download-keys-6wzzaeue&redirect_uri=https://villatheme.com/update-key"
                             data-id="33285875">
							<?php esc_html_e( 'Get key', 'bulky-woocommerce-bulk-edit-products' ); ?>
                        </div>
                    </div>
					<?php do_action( BULKY_CONST['slug'] . '_key' ) ?>
                    <p class="description">
						<?php
						printf( '%s <a target="_blank" href="https://villatheme.com/my-download">https://villatheme.com/my-download</a>. %s <a target="_blank" href="https://villatheme.com/knowledge-base/how-to-use-auto-update-feature/">%s</a>',
							esc_html__( 'Please fill your key what you get from', 'bulky-woocommerce-bulk-edit-products' ),
							esc_html__( 'You can auto update this plugin.', 'bulky-woocommerce-bulk-edit-products' ),
							esc_html__( 'See guide', 'bulky-woocommerce-bulk-edit-products' )
						)
						?>
                    </p>
                </td>
            </tr>
        </table>

		<?php
	}
}