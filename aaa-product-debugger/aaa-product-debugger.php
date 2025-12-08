<?php
/**
 * Plugin Name: ATUM Product Debugger
 * Description: Debug tool to inspect product meta and related ATUM product data.
 * Version: 1.0
 * Author: Workflow Delivery
 * File Path: /wp-content/plugins/atum-product-debugger/atum-product-debugger.php
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class ATUM_Product_Debugger {

    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'menu' ] );
        add_action( 'admin_post_atum_product_debugger', [ __CLASS__, 'handle' ] );
        add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), [ __CLASS__, 'settings_link' ] );
    }

    public static function settings_link( $links ) {
        $url = admin_url( 'admin.php?page=atum-product-debugger' );
        $links[] = '<a href="' . esc_url( $url ) . '">Settings</a>';
        return $links;
    }

    public static function menu() {
        add_submenu_page(
            'woocommerce',
            'Product Debugger',
            'Product Debugger',
            'manage_woocommerce',
            'atum-product-debugger',
            [ __CLASS__, 'page' ]
        );
    }

    public static function page() {
        echo '<div class="wrap"><h1>Product Debugger</h1>';
        echo '<p>Enter a product ID to dump product meta and related ATUM tables.</p>';
        echo '<form method="post" target="_blank" action="' . esc_url( admin_url('admin-post.php') ) . '">';
        wp_nonce_field( 'atum_product_debugger' );
        echo '<input type="hidden" name="action" value="atum_product_debugger">';
        echo '<input type="number" name="product_id" placeholder="Product ID" style="width:140px;" required> ';
        submit_button( 'Run Debug', 'primary', '', false );
        echo '</form></div>';
    }

    public static function handle() {
        check_admin_referer( 'atum_product_debugger' );
        global $wpdb;

        $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
        echo '<div class="wrap"><h1>Debugger Results</h1>';

        if ( ! $product_id ) {
            echo '<p>No product ID provided.</p></div>';
            exit;
        }

        $product = wc_get_product( $product_id );
        if ( ! $product ) {
            echo '<p>Product not found.</p></div>';
            exit;
        }

        echo '<h2>Product #'.esc_html($product_id).'</h2>';

        echo '<div style="margin:10px 0;display:flex;gap:8px;align-items:center">';
        echo '  <button class="button button-primary" id="atum-copy-top" onclick="atumCopyDebugger(this)">Copy All</button>';
        echo '  <a class="button" href="' . esc_url( admin_url( 'admin.php?page=atum-product-debugger' ) ) . '">&larr; Back</a>';
        echo '</div>';

        echo '<div id="atum-debug-container" style="white-space:pre-wrap;font-family:monospace;border:1px solid #ddd;padding:12px;border-radius:6px;background:#fff;">';

        echo "=== PRODUCT DATA (Woo Product Object) ===\n";
        echo esc_html( print_r( $product->get_data(), true ) );

        echo "\n\n=== PRODUCT META ===\n";
        echo esc_html( print_r( get_post_meta( $product_id ), true ) );

        $atum_data = $wpdb->get_results( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}atum_product_data WHERE product_id = %d", $product_id), ARRAY_A );
        echo "\n\n=== ATUM PRODUCT DATA ===\n";
        echo esc_html( $atum_data ? print_r( $atum_data, true ) : '(no ATUM product data)' );

        $atum_locs = $wpdb->get_results( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}atum_product_locations WHERE product_id = %d", $product_id), ARRAY_A );
        echo "\n\n=== ATUM PRODUCT LOCATIONS ===\n";
        echo esc_html( $atum_locs ? print_r( $atum_locs, true ) : '(no ATUM product locations)' );

        echo '</div>';

        echo '<p><button class="button button-primary" id="atum-copy-bottom" onclick="atumCopyDebugger(this)">Copy All</button></p>';

        echo '<script>
        function atumCopyDebugger(btn){
            try{
                const box = document.getElementById("atum-debug-container");
                if(!box){return;}
                const text = box.innerText;
                const old = btn.innerText;
                btn.disabled = true;
                btn.innerText = "Copying…";
                navigator.clipboard.writeText(text).then(()=>{
                    btn.innerText = "Copied!";
                    var twin = (btn.id === "atum-copy-top") ? document.getElementById("atum-copy-bottom") : document.getElementById("atum-copy-top");
                    if(twin){ twin.innerText = "Copied!"; }
                    setTimeout(()=>{
                        btn.innerText = old;
                        btn.disabled = false;
                        if(twin){ twin.innerText = "Copy All"; }
                    }, 1200);
                }).catch(()=>{
                    btn.innerText = "Copy Failed";
                    setTimeout(()=>{ btn.innerText = old; btn.disabled = false; }, 1200);
                });
            }catch(e){
                console.error(e);
            }
        }
        </script>';

        echo '<p><a class="button" href="' . esc_url( admin_url( 'admin.php?page=atum-product-debugger' ) ) . '">&larr; Back</a></p></div>';
        exit;
    }
}

ATUM_Product_Debugger::init();