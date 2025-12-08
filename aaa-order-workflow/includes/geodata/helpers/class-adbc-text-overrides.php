<?php
/**
 * File: includes/helpers/class-adbc-text-overrides.php
 * Purpose: Override customer-facing labels (thank-you, order details, cart).
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class ADBC_Text_Overrides {

	public static function init() {
		// 1) Order totals table: "Shipping" → "Delivery"
		add_filter( 'woocommerce_get_order_item_totals', [ __CLASS__, 'change_shipping_label' ], 10, 3 );

		// 2) Thank-you / View Order section heading: "Additional information" → "Delivery Schedule"
		add_filter( 'gettext', [ __CLASS__, 'change_additional_information_text' ], 10, 3 );

		// 3) Cart note (classic templates): "Shipping will be calculated at checkout" → "Delivery ..."
		add_filter( 'gettext', [ __CLASS__, 'change_cart_shipping_text_server' ], 10, 3 );

		// 4) Cart note (Blocks UI): DOM patch so the same string is replaced client-side
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_cart_blocks_patch' ] );
	}

	/** Order totals table row label ("Shipping:" → "Delivery:") */
	public static function change_shipping_label( $totals, $order, $tax_display ) {
		if ( isset( $totals['shipping']['label'] ) ) {
			$totals['shipping']['label'] = __( 'Delivery:', 'aaa-delivery-blocks-coords' );
		}
		return $totals;
	}

	/** Thank-you / View Order heading ("Additional information" → "Delivery Schedule") */
	public static function change_additional_information_text( $translated, $text, $domain ) {
		// Scope to customer order pages to avoid changing unrelated places
		$is_order_page = ( function_exists( 'is_wc_endpoint_url' ) && ( is_wc_endpoint_url( 'order-received' ) || is_wc_endpoint_url( 'view-order' ) ) );
		if ( $is_order_page && $domain === 'woocommerce' && $text === 'Additional information' ) {
			return __( 'Delivery Schedule', 'aaa-delivery-blocks-coords' );
		}
		return $translated;
	}

	/** Classic templates (PHP-rendered) cart message override */
	public static function change_cart_shipping_text_server( $translated, $text, $domain ) {
		if ( $domain !== 'woocommerce' && $domain !== 'woocommerce-blocks' ) {
			return $translated;
		}
		if ( function_exists( 'is_cart' ) && is_cart() ) {
			$targets = [
				'Shipping will be calculated at checkout',
				'Shipping will be calculated during checkout',
			];
			if ( in_array( $text, $targets, true ) ) {
				return __( 'Delivery will be calculated at checkout', 'aaa-delivery-blocks-coords' );
			}
		}
		return $translated;
	}

	/** Blocks UI cart message override via tiny DOM patch */
	public static function enqueue_cart_blocks_patch() {
		if ( ! function_exists( 'is_cart' ) || ! is_cart() ) return;

		$js = <<<JS
(function(){
  var OLD1="Shipping will be calculated at checkout";
  var OLD2="Shipping will be calculated during checkout";
  var NEWT="Delivery will be calculated at checkout";

  function replaceIn(node){
    if(!node) return;
    // Only replace exact text nodes to avoid touching prices etc.
    if(node.nodeType===Node.TEXT_NODE){
      var t=node.nodeValue.trim();
      if(t===OLD1 || t===OLD2){ node.nodeValue=node.nodeValue.replace(t, NEWT); }
      return;
    }
    node.childNodes && node.childNodes.forEach(replaceIn);
  }

  function scan(){
    // Limit to cart blocks area if present, else whole document
    var container = document.querySelector('.wc-block-cart, .wp-block-woocommerce-cart, .wp-block-woocommerce-cart-totals-block, .wc-block-components-totals-wrapper') || document;
    replaceIn(container);
  }

  if(document.readyState==='loading'){
    document.addEventListener('DOMContentLoaded', scan);
  } else {
    scan();
  }
  // Re-scan when Blocks re-render
  new MutationObserver(function(){ scan(); }).observe(document.body,{childList:true,subtree:true});
})();
JS;

		// Register a tiny handle and inject inline JS so it runs on cart even without our checkout script.
		wp_register_script( 'adbc-text-patch', '', [], '1.0', true );
		wp_enqueue_script( 'adbc-text-patch' );
		wp_add_inline_script( 'adbc-text-patch', $js );
	}
}

// Bootstrap
ADBC_Text_Overrides::init();
