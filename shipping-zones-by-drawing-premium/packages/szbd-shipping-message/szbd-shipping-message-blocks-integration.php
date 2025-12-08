<?php
use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;

/**
 * Class for integrating with WooCommerce Blocks
 */
class SZBD_Shipping_Message_Blocks_Integration implements IntegrationInterface
{


	public function get_name()
	{
		return 'szbd-shipping-message';
	}


	public function initialize()
	{
		require_once __DIR__ . '/szbd-shipping-message-extend-store-endpoint.php';
		$this->register_szbd_shipping_message_block_frontend_scripts();
		$this->register_szbd_shipping_message_block_editor_scripts();
		$this->register_szbd_shipping_message_block_editor_styles();

		$this->register_main_integration();
		$this->extend_store_api();


	}


	private function extend_store_api()
	{
		SZBD_Shipping_Message_Extend_Store_Endpoint::init();
	}




	private function register_main_integration()
	{
		$script_path = '/build/index.js';
		$style_path = '/build/style-index.css';

		$script_url = plugins_url($script_path, __FILE__);
		$style_url = plugins_url($style_path, __FILE__);

		$script_asset_path = dirname(__FILE__) . '/build/index.asset.php';
		$script_asset = file_exists($script_asset_path)
			? require $script_asset_path
			: [
				'dependencies' => [],
				'version' => $this->get_file_version($script_path),
			];

		wp_enqueue_style(
			'szbd-shipping-message-blocks-integration',
			$style_url,
			[],
			$this->get_file_version($style_path)
		);

		wp_register_script(
			'szbd-shipping-message-blocks-integration',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);
		wp_set_script_translations(
			'szbd-shhipping-message-blocks-integration',
			'szbd',
			dirname(__FILE__) . '/languages'
		);


	}


	public function get_script_handles()
	{
		return ['szbd-shipping-message-blocks-integration', 'szbd-shipping-message-block-frontend'];
	}


	public function get_editor_script_handles()
	{
		return ['szbd-shipping-message-blocks-integration', 'szbd-shipping-message-block-editor'];
	}


	public function get_script_data()
	{
		$data = [
			'szbd-shipping-message-active' => true,


			'defaultLabelText' => __('Shipping Zones by drawing Shipping message', 'szbd'),
		];

		return $data;

	}

	public function register_szbd_shipping_message_block_editor_styles()
	{
		$style_path = '/build/style-szbd-shipping-message-block.css';

		$style_url = plugins_url($style_path, __FILE__);
		wp_enqueue_style(
			'szbd-shipping-message-block',
			$style_url,
			[],
			$this->get_file_version($style_path)
		);
	}

	public function register_szbd_shipping_message_block_editor_scripts()
	{
		$script_path = '/build/szbd-shipping-message-block.js';
		$script_url = plugins_url($script_path, __FILE__);
		$script_asset_path = dirname(__FILE__) . '/build/szbd-shipping-message-block.asset.php';
		$script_asset = file_exists($script_asset_path)
			? require $script_asset_path
			: [
				'dependencies' => [],
				'version' => $this->get_file_version($script_asset_path),
			];

		wp_register_script(
			'szbd-shipping-message-block-editor',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		wp_set_script_translations(
			'szbd-shipping-message-block-editor',
			'szbd',
			dirname(__FILE__) . '/languages'
		);
	}

	public function register_szbd_shipping_message_block_frontend_scripts()
	{
		$script_path = '/build/szbd-shipping-message-block-frontend.js';
		$script_url = plugins_url($script_path, __FILE__);
		$script_asset_path = dirname(__FILE__) . '/build/szbd-shipping-message-block-frontend.asset.php';
		$script_asset = file_exists($script_asset_path)
			? require $script_asset_path
			: [
				'dependencies' => [],
				'version' => $this->get_file_version($script_asset_path),
			];

		wp_register_script(
			'szbd-shipping-message-block-frontend',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);
		wp_set_script_translations(
			'szbd-shipping-message-block-frontend',
			'szbd',
			dirname(__FILE__) . '/languages'
		);
	}


	protected function get_file_version($file)
	{
		if (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG && file_exists($file)) {
			return filemtime($file);
		}
		return SZBD_SHIPPING_MESSAGE_VERSION;
	}
}