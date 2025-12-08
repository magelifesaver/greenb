<?php
if ( ! defined( 'ABSPATH' ) ) exit;
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields;

class ADBG_Fields {
  public static function register(){
    add_action('woocommerce_init', function(){
      if ( ! function_exists('woocommerce_register_additional_checkout_field') ) return;
      $opts = get_option('delivery_geo', []);
      $hide = ! empty($opts['hide_checkout_geo_fields']);
      $attrs = [ 'aria-hidden' => $hide ? 'true' : 'false' ];

      $def = function($id, $label) use ($attrs) {
        return [
          'id'                => $id,
          'label'             => $label,
          'location'          => 'address',
          'type'              => 'text',
          'attributes'        => $attrs,
          'sanitize_callback' => fn($v) => is_scalar($v) ? (string) $v : ''
        ];
      };

      // ✅ Only register if NOT hidden
      if ( ! $hide ) {
        woocommerce_register_additional_checkout_field( $def(ADBG_FIELD_ETA,'ETA (seconds)') );
        woocommerce_register_additional_checkout_field( $def(ADBG_FIELD_ETA_RANGE,'ETA Range (csv)') );
        woocommerce_register_additional_checkout_field( $def(ADBG_FIELD_ORIGIN,'ETA Origin') );
        woocommerce_register_additional_checkout_field( $def(ADBG_FIELD_DISTANCE,'Distance (m)') );
        woocommerce_register_additional_checkout_field( $def(ADBG_FIELD_TRAVEL,'Travel (s)') );
        woocommerce_register_additional_checkout_field( $def(ADBG_FIELD_REFRESHED,'Travel Refreshed (unix)') );
      }
    });

    add_action('woocommerce_store_api_checkout_update_order_meta',[__CLASS__,'save_geo'],10,1);
  }

  public static function save_geo( WC_Order $order ){
    try{
      $cf = Package::container()->get( CheckoutFields::class );
      foreach(['billing','shipping'] as $group){
        $prefix = $group==='billing' ? CheckoutFields::BILLING_FIELDS_PREFIX : CheckoutFields::SHIPPING_FIELDS_PREFIX;
        $eta  = (string) $cf->get_field_from_object( ADBG_FIELD_ETA, $order, $group );
        $rng  = (string) $cf->get_field_from_object( ADBG_FIELD_ETA_RANGE, $order, $group );
        $org  = (string) $cf->get_field_from_object( ADBG_FIELD_ORIGIN, $order, $group );
        $dst  = (string) $cf->get_field_from_object( ADBG_FIELD_DISTANCE, $order, $group );
        $trv  = (string) $cf->get_field_from_object( ADBG_FIELD_TRAVEL, $order, $group );
        $ref  = (string) $cf->get_field_from_object( ADBG_FIELD_REFRESHED, $order, $group );

        // If empty and coordinates exist, compute once as fallback
        if ($eta==='' || $dst==='' || $trv===''){
          $uid = (int) $order->get_user_id();
          if ($uid){
            $p = ADBG_Travel::compute_for_user($uid,'shipping',true);
            if (empty($p['error'])){
              $eta=(string)$p['eta_s']; $rng=(string)($p['eta_range']??''); $org=(string)$p['origin_id'];
              $dst=(string)$p['distance_m']; $trv=(string)$p['travel_s']; $ref=(string)$p['refreshed'];
            }
          }
        }

        $order->update_meta_data( $prefix . ADBG_FIELD_ETA, $eta );
        $order->update_meta_data( $prefix . ADBG_FIELD_ETA_RANGE, $rng );
        $order->update_meta_data( $prefix . ADBG_FIELD_ORIGIN, $org );
        $order->update_meta_data( $prefix . ADBG_FIELD_DISTANCE, $dst );
        $order->update_meta_data( $prefix . ADBG_FIELD_TRAVEL, $trv );
        $order->update_meta_data( $prefix . ADBG_FIELD_REFRESHED, $ref );

        if ($uid = $order->get_user_id()){
          update_user_meta($uid,'_wc_'.$group.'/'.ADBG_FIELD_ETA,$eta);
          update_user_meta($uid,'_wc_'.$group.'/'.ADBG_FIELD_ETA_RANGE,$rng);
          update_user_meta($uid,'_wc_'.$group.'/'.ADBG_FIELD_ORIGIN,$org);
          update_user_meta($uid,'_wc_'.$group.'/'.ADBG_FIELD_DISTANCE,$dst);
          update_user_meta($uid,'_wc_'.$group.'/'.ADBG_FIELD_TRAVEL,$trv);
          update_user_meta($uid,'_wc_'.$group.'/'.ADBG_FIELD_REFRESHED,$ref);
        }
      }
    } catch (\Throwable $e) {}
  }
}
