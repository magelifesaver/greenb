<?php
use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;

/**
 * Class for integrating with WooCommerce Blocks
 */
    class TPFW_Pickup_Timepicker_Blocks_Integration implements IntegrationInterface {

	
	public function get_name() {
		return 'tpfw-pickup-timepicker';
	}

	
	public function initialize() {
		
		$this->register_pickup_timepicker_block_frontend_scripts();
		$this->register_pickup_timepicker_block_editor_scripts();
		$this->register_pickup_timepicker_block_editor_styles();
		$this->register_main_integration();
		
		
		
		
	}

	

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

	

		wp_register_script(
			'tpfw-pickup-timepicker-blocks-integration',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);
		wp_set_script_translations(
			'tpfw-pickup-timepicker-blocks-integration',
			'checkout-time-picker-for-woocommerce',
			TPFW_PLUGINDIRPATH . '/languages'
		
		);
	}

	
	public function get_script_handles() {
		return [ 'tpfw-pickup-timepicker-blocks-integration', 'tpfw-pickup-timepicker-block-frontend' ];
	}

	
	public function get_editor_script_handles() {
		return [ 'tpfw-pickup-timepicker-blocks-integration', 'tpfw-pickup-timepicker-block-editor' ];
	}

	
	public function get_script_data() {
		
		$data = [
			
			'date_input' => self::get_date_args(),
			
			
		];

		return $data;

	}
	
	public static function get_date_args() {
            
          
		
	  if(TPFW_Timepicker::do_any_timepicker()){
		
		  
			  
			  $mode = TPFW_Timepicker::do_timepicker('do_pickup_time') ? 'pickup' : 'delivery';
			  $title_time = $mode == 'pickup' ? __('Select Pickup Time', 'checkout-time-picker-for-woocommerce') : __('Select Delivery Time', 'checkout-time-picker-for-woocommerce');
			  $title_date = $mode == 'pickup' ? __('Select Pickup Date', 'checkout-time-picker-for-woocommerce') : __('Select Delivery Date', 'checkout-time-picker-for-woocommerce');
		  
			  
			  
			 
			  $datetime_today = current_datetime();
			  $today = $datetime_today->format('Y-m-d');
			  // Make date array
			  $date_array = array();
			  $today_default = array(
				  'value' => $today , 'label'=>  __('Today', 'checkout-time-picker-for-woocommerce') ,
			   );
			  $date_array[] = $today_default;
			  $max = get_option('tpfw_timepick_days_qty', 2) <= 1 ? 0 : get_option('tpfw_timepick_days_qty', 2) - 1;
			 
			  $date_format = get_option('tpfw_timepicker_dateformat', 'default'  ) == 'default' ? get_option('date_format') : get_option('tpfw_timepicker_dateformat_custom', get_option('date_format')   );
			  for ($i = 0;$i < $max;$i++) {
				  $temp_day = $datetime_today->modify('+' . ($i + 1) . ' days');
				  $wp_date = wp_date( $date_format  , $temp_day->getTimestamp());
				  if($wp_date == false){
					  continue;
				  }
				  $date_new =  array( 'value' => $temp_day->format('Y-m-d')  , 'label' =>  $wp_date );
				  array_push($date_array, $date_new ); 
			  }

			  $input = 
				  array(


					  
					  'options'  => $date_array,
					  'default' => $today_default,
					 
				  );
			  
			
			  
				  }
		 
	  return $input;
		 
	  }

	public function register_pickup_timepicker_block_editor_styles() {
	
	}

	public function register_pickup_timepicker_block_editor_scripts() {
		$script_path       = '/build/tpfw-pickup-timepicker-block.js';
		$script_url        = plugins_url( $script_path, __FILE__ );
		$script_asset_path = dirname( __FILE__ ) . '/build/tpfw-pickup-timepicker-block.asset.php';
		$script_asset      = file_exists( $script_asset_path )
			? require $script_asset_path
			: [
				'dependencies' => [],
				'version'      => $this->get_file_version( $script_asset_path ),
			];

		wp_register_script(
			'tpfw-pickup-timepicker-block-editor',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		wp_set_script_translations(
			'tpfw-pickup-timepicker-block-editor',
			'checkout-time-picker-for-woocommerce',
			TPFW_PLUGINDIRPATH . '/languages'
			
		);
	}

	public function register_pickup_timepicker_block_frontend_scripts() {
		$script_path       = '/build/tpfw-pickup-timepicker-block-frontend.js';
		$script_url        = plugins_url( $script_path, __FILE__ );
		$script_asset_path = dirname( __FILE__ ) . '/build/tpfw-pickup-timepicker-block-frontend.asset.php';
		$script_asset      = file_exists( $script_asset_path )
			? require $script_asset_path
			: [
				'dependencies' => [],
				'version'      => $this->get_file_version( $script_asset_path ),
			];

		wp_register_script(
			'tpfw-pickup-timepicker-block-frontend',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);
		wp_set_script_translations(
			'tpfw-pickup-timepicker-block-frontend',
			'checkout-time-picker-for-woocommerce',
			TPFW_PLUGINDIRPATH . '/languages'
			
		);
	}

	
	protected function get_file_version( $file ) {
		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG && file_exists( $file ) ) {
			return filemtime( $file );
		}
		return TPFW_PICKUP_TIMEPICKER_VERSION;
	}
}
