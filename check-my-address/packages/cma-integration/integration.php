<?php
use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;

/**
 * Class for integrating with WooCommerce Blocks
 */
class CMA_Integration implements IntegrationInterface
{

	
	public function get_name()
	{
		return 'cma-integration';
	}

	
	public function initialize()
	{
		
		$this->register_cma_integration_block_frontend_scripts();
	
		$this->register_main_integration();
	
	}


	
	private function register_main_integration()
	{
		$script_path = '/build/index.js';
		

		$script_url = plugins_url($script_path, __FILE__);
		

		$script_asset_path = dirname(__FILE__) . '/build/index.asset.php';
		$script_asset = file_exists($script_asset_path)
			? require $script_asset_path
			: [
				'dependencies' => [],
				'version' => $this->get_file_version($script_path),
			];

		

		wp_register_script(
			'cma-blocks-integration',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);
		


	}

	
	public function get_script_handles()
	{
		return ['cma-blocks-integration', 'cma-block-frontend'];
	}

	
	public function get_editor_script_handles()
	{
		return array(); 
	}

	
	public function get_script_data()
	{
		$data = array();

		return $data;

	}

	

	public function register_cma_integration_block_frontend_scripts()
	{
		$script_path = '/build/cma-integration-block-frontend.js';
		$script_url = plugins_url($script_path, __FILE__);
		$script_asset_path = dirname(__FILE__) . '/build/cma-integration-block-frontend.asset.php';
		$script_asset = file_exists($script_asset_path)
			? require $script_asset_path
			: [
				'dependencies' => [],
				'version' => $this->get_file_version($script_asset_path),
			];


		wp_register_script(
			'cma-block-frontend',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);
		
	}

	


	protected function get_file_version($file)
	{
		if (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG && file_exists($file)) {
			return filemtime($file);
		}
		return CMA_VERSION;
	}
}