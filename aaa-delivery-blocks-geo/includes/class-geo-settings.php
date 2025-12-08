<?php
/**
 * File: includes/class-geo-settings.php
 * Notes:
 * - Fixed `origins_json` saving: now uses `wp_unslash()` instead of `wp_kses_post()`
 *   so JSON is stored raw in the DB without runaway backslashes.
 * - Changed server key input to `type="text"` so you can see and verify the saved key.
 * - Updated settings: now uses `hide_checkout_geo_fields` (consistent with coords plugin).
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class ADBG_Geo_Settings_Page {
  const OPT_KEY = 'delivery_geo';

  public function __construct(){ 
    add_action('admin_menu',[ $this,'register_menu' ]); 
  }

  public function register_menu(){
    add_submenu_page(
      'woocommerce',
      __('Delivery Geo','aaa-delivery-blocks-geo'),
      __('Delivery Geo','aaa-delivery-blocks-geo'),
      'manage_woocommerce',
      'delivery-geo',
      [ $this,'render_page' ]
    );
  }

  public function render_page(){
    $opts = get_option(self::OPT_KEY,[]);

    if(isset($_POST['adbg_geo_nonce']) && wp_verify_nonce($_POST['adbg_geo_nonce'],'save_delivery_geo')){
      $new = [
        'browse_ttl_seconds'       => max(0,intval($_POST['browse_ttl_seconds']??1200)),
        'checkout_ttl_seconds'     => max(0,intval($_POST['checkout_ttl_seconds']??120)),
        'slot_seconds'             => max(60,intval($_POST['slot_seconds']??600)),
        'origins_json'             => trim( wp_unslash($_POST['origins_json'] ?? '') ),
        'hide_checkout_geo_fields' => !empty($_POST['hide_checkout_geo_fields']),
      ];

      $server_key = trim((string)($_POST['server_key']??''));
      if($server_key!==''){ 
        $new['server_key'] = sanitize_text_field($server_key); 
      } elseif(isset($opts['server_key'])){ 
        $new['server_key'] = $opts['server_key']; 
      }

      $opts = $new; 
      update_option(self::OPT_KEY,$opts);
      echo '<div class="updated"><p>'.esc_html__('GEO settings saved.','aaa-delivery-blocks-geo').'</p></div>';
    }

    echo '<div class="wrap"><h1>'.esc_html__('Delivery Geo','aaa-delivery-blocks-geo').'</h1><form method="post">';
    wp_nonce_field('save_delivery_geo','adbg_geo_nonce');
    echo '<table class="form-table">';

    // Server key
    echo '<tr><th>'.esc_html__('Server API Key (Distance/Geocoding)','aaa-delivery-blocks-geo').'</th><td>';
    echo '<input type="text" name="server_key" value="'.esc_attr($opts['server_key'] ?? '').'" style="width:420px;">';
    echo '<p class="description">'.esc_html__('Enter only to change. Stored in DB, never exposed to the browser.','aaa-delivery-blocks-geo').'</p></td></tr>';

    // Origins JSON
    echo '<tr><th>'.esc_html__('Origins (JSON)','aaa-delivery-blocks-geo').'</th><td>';
    echo '<textarea name="origins_json" rows="6" style="width:100%;">'.esc_textarea($opts['origins_json'] ?? '[{"id":"default","lat":34.097,"lng":-117.648,"mode":"driving"}]').'</textarea>';
    echo '<p class="description">'.esc_html__('Example: [{"id":"main","lat":34.10,"lng":-118.32,"mode":"driving"}]','aaa-delivery-blocks-geo').'</p></td></tr>';

    // TTLs + Slot
    echo '<tr><th>'.esc_html__('Browse TTL (seconds)','aaa-delivery-blocks-geo').'</th><td><input type="number" name="browse_ttl_seconds" value="'.intval($opts['browse_ttl_seconds']??1200).'"></td></tr>';
    echo '<tr><th>'.esc_html__('Checkout TTL (seconds)','aaa-delivery-blocks-geo').'</th><td><input type="number" name="checkout_ttl_seconds" value="'.intval($opts['checkout_ttl_seconds']??120).'"></td></tr>';
    echo '<tr><th>'.esc_html__('ETA Slot Size (seconds)','aaa-delivery-blocks-geo').'</th><td><input type="number" name="slot_seconds" value="'.intval($opts['slot_seconds']??600).'"></td></tr>';

    // Hide toggle
    echo '<tr><th>'.esc_html__('Hide GEO fields on Checkout (debug)','aaa-delivery-blocks-geo').'</th><td>';
    echo '<label><input type="checkbox" name="hide_checkout_geo_fields" '.( !empty($opts['hide_checkout_geo_fields']) ? 'checked' : '' ).'> '.esc_html__('Hide the ETA/travel fields on checkout','aaa-delivery-blocks-geo').'</label>';
    echo '</td></tr>';

    echo '</table>'; 
    submit_button(); 
    echo '</form></div>';
  }
}
new ADBG_Geo_Settings_Page();
