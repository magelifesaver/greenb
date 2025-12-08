<?php
use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;

/**
 * Class for integrating with WooCommerce Blocks
 */
class Szbd_Method_Selection_Blocks_Integration implements IntegrationInterface {

	/**
	 * The name of the integration.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'szbd-method-selection';
	}

	/**
	 * When called invokes any initialization/setup for the integration.
	 */
	public function initialize() {
		
		$this->register_block_frontend_scripts();
		$this->register_block_editor_scripts();
		$this->register_block_editor_styles();
		$this->register_main_integration();
		
		
		
	}

	
	

	

	
	/**
	 * Registers the main JS file required to add filters and Slot/Fills.
	 */
	private function register_main_integration() {
		$script_path = '/build/index.js';
		$style_path  = '/build/style-index.css';

		$script_url = plugins_url( $script_path, __FILE__ );
		$style_url  = plugins_url( $style_path, __FILE__ );

		$script_asset_path = dirname( __FILE__ ) . '/build/index.asset.php';
		$script_asset      = file_exists( $script_asset_path )
			? require $script_asset_path
			: [
				'dependencies' => [],
				'version'      => $this->get_file_version( $script_path ),
			];

		wp_enqueue_style(
			'shipping-workshop-blocks-integration',
			$style_url,
			[],
			$this->get_file_version( $style_path )
		);

		wp_register_script(
			'szbd-method-selection-blocks-integration',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);
		
	}

	/**
	 * Returns an array of script handles to enqueue in the frontend context.
	 *
	 * @return string[]
	 */
	public function get_script_handles() {
		return [ 'szbd-method-selection-blocks-integration', 'szbd-method-selection-block-frontend' ];
	}

	/**
	 * Returns an array of script handles to enqueue in the editor context.
	 *
	 * @return string[]
	 */
	public function get_editor_script_handles() {
		return [ 'szbd-method-selection-blocks-integration', 'szbd-method-selection-block-editor' ];
	}

	/**
	 * An array of key, value pairs of data made available to the block on the client side.
	 *
	 * @return array
	 */
	public function get_script_data() {
		$data = [
			'szbd-method-selection-active' => true,
			'map_feature_active' => get_option('szbd_precise_address', 'no') != 'no' ? true : false,
		];

		return $data;

	}

	public function register_block_editor_styles() {
		$style_path = '/build/style-szbd-method-selection-block.css';

		$style_url = plugins_url( $style_path, __FILE__ );
		wp_enqueue_style(
			'szbd-method-selection-block',
			$style_url,
			[],
			$this->get_file_version( $style_path )
		);
	}

	public function register_block_editor_scripts() {
		$script_path       = '/build/szbd-method-selection-block.js';
		$script_url        = plugins_url( $script_path, __FILE__ );
		$script_asset_path = dirname( __FILE__ ) . '/build/szbd-method-selection-block.asset.php';
		$script_asset      = file_exists( $script_asset_path )
			? require $script_asset_path
			: [
				'dependencies' => [],
				'version'      => $this->get_file_version( $script_asset_path ),
			];

		wp_register_script(
			'szbd-method-selection-block-editor',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		wp_set_script_translations(
			'szbd-method-selection-block-editor',
			'szbd',
			dirname( __FILE__ ) . '/languages'
		);
	}

	public function register_block_frontend_scripts() {
		$script_path       = '/build/szbd-method-selection-block-frontend.js';
		$script_url        = plugins_url( $script_path, __FILE__ );
		$script_asset_path = dirname( __FILE__ ) . '/build/szbd-method-selection-block-frontend.asset.php';
		$script_asset      = file_exists( $script_asset_path )
			? require $script_asset_path
			: [
				'dependencies' => [],
				'version'      => $this->get_file_version( $script_asset_path ),
			];

		wp_register_script(
			'szbd-method-selection-block-frontend',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);
		wp_set_script_translations(
			'szbd-method-selection-block-frontend',
			'szbd',
			dirname( __FILE__ ) . '/languages'
		);
	}

	/**
	 * Get the file modified time as a cache buster if we're in dev mode.
	 *
	 * @param string $file Local path to the file.
	 * @return string The cache buster value to use for the given file.
	 */
	protected function get_file_version( $file ) {
		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG && file_exists( $file ) ) {
			return filemtime( $file );
		}
		return SHIPPING_METHOD_SELECTION_VERSION;
	}
}
