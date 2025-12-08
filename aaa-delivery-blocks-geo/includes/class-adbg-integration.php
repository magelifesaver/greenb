<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;
class ADBG_Integration implements IntegrationInterface {
    public function get_name(){ return 'aaa-delivery-blocks-geo'; }
    public function initialize(){
        $src  = trailingslashit( ADBG_PLUGIN_URL ) . 'assets/js/adbg-checkout.js';
        wp_register_script('adbg-checkout', $src, array(), '0.3.0', true);
        wp_localize_script('adbg-checkout','adbgGeoSettings', array(
            'ajaxUrl'=> admin_url('admin-ajax.php'),
            'nonce'  => wp_create_nonce('adbg_ajax'),
            'debug'  => (bool) ADBG_DEBUG,
        ));
    }
    public function get_script_handles(){ return array('adbg-checkout'); }
    public function get_editor_script_handles(){ return array(); }
    public function get_script_data(){ return array(); }
}
