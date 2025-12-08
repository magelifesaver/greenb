<?php
/**
 * Plugin Name: AAA Product Promotions LIVE
 * Plugin URI:  https://example.com
 * Description: Applies discounts on eligible products (by tag) for every complete group (Stiiizy3AT50, WCC3AT50, etc.). Includes admin settings to auto‑apply discounts on the front‑end or use coupon codes in the admin.
 * Version:     1.1.1
 * Author:      Your Name
 * Text Domain: aaa-product-promotions
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class AAA_Product_Promotions {

    /**
     * Define promotion data with keys for settings, discount values, etc.
     */
    private $promotions_data = [
        'stiiizy' => [
            'label'            => 'Stiiizy3AT50 (50% off, buy 3)',
            'enable_key'       => 'enable_stiiizy',
            'disable_auto_key' => 'disable_auto_load_stiiizy',
            'coupon_code_key'  => 'coupon_code_stiiizy',
            'tag'              => 'Stiiizy3AT50',
            'discount'         => 0.5,  // 50% discount
            'group_size'       => 3,
            'type'             => 'percent'
        ],
        'wcc' => [
            'label'            => 'WCC3AT50 (50% off, buy 3)', // Reverted to 50% off.
            'enable_key'       => 'enable_wcc',
            'disable_auto_key' => 'disable_auto_load_wcc',
            'coupon_code_key'  => 'coupon_code_wcc',
            'tag'              => 'WCC3AT50',
            'discount'         => 0.5,  // 50% discount
            'group_size'       => 3,
            'type'             => 'percent'
        ],
        'gs50' => [
            'label'            => 'GS3AT50 (50% off, buy 3)',
            'enable_key'       => 'enable_gs50',
            'disable_auto_key' => 'disable_auto_load_gs50',
            'coupon_code_key'  => 'coupon_code_gs50',
            'tag'              => 'GS3AT50',
            'discount'         => 0.5,
            'group_size'       => 3,
            'type'             => 'percent'
        ],
        'gs30' => [
            'label'            => 'GS3AT30 (30% off, buy 3)',
            'enable_key'       => 'enable_gs30',
            'disable_auto_key' => 'disable_auto_load_gs30',
            'coupon_code_key'  => 'coupon_code_gs30',
            'tag'              => 'GS3AT30',
            'discount'         => 0.3,
            'group_size'       => 3,
            'type'             => 'percent'
        ],
        'gs40' => [
            'label'            => 'GS3AT40 (40% off, buy 3)',
            'enable_key'       => 'enable_gs40',
            'disable_auto_key' => 'disable_auto_load_gs40',
            'coupon_code_key'  => 'coupon_code_gs40',
            'tag'              => 'GS3AT40',
            'discount'         => 0.4,
            'group_size'       => 3,
            'type'             => 'percent'
        ],
        'rove' => [
            'label'            => 'ROVE3AT50 (50% off, buy 3)',
            'enable_key'       => 'enable_rove',
            'disable_auto_key' => 'disable_auto_load_rove',
            'coupon_code_key'  => 'coupon_code_rove',
            'tag'              => 'ROVE3AT50',
            'discount'         => 0.5,
            'group_size'       => 3,
            'type'             => 'percent'
        ],
        'punchch' => [
            'label'            => 'PUNCHCH3AT50 (50% off, buy 3)',
            'enable_key'       => 'enable_punchch',
            'disable_auto_key' => 'disable_auto_load_punchch',
            'coupon_code_key'  => 'coupon_code_punchch',
            'tag'              => 'PUNCHCH3AT50',
            'discount'         => 0.5,
            'group_size'       => 3,
            'type'             => 'percent'
        ],
        'gs2' => [
            'label'            => 'GS2AT100 (Buy 2 at $50 each)',
            'enable_key'       => 'enable_gs2',
            'disable_auto_key' => 'disable_auto_load_gs2',
            'coupon_code_key'  => 'coupon_code_gs2',
            'tag'              => 'GS2AT100',
            'discount'         => 50,   // For fixed promotions, use the fixed price value.
            'group_size'       => 2,
            'type'             => 'fixed'
        ],
    ];

    public function __construct() {
        // Admin settings page.
        add_action( 'admin_menu', [ $this, 'aaa_pp_add_settings_page' ] );
        add_action( 'admin_init', [ $this, 'aaa_pp_register_settings' ] );

        // Front-end discount logic.
        add_action( 'woocommerce_cart_calculate_fees', [ $this, 'aaa_pp_apply_promotions' ] );

        // Admin order discount logic – skip auto‑apply if editing an order.
        if ( ! ( is_admin() && isset( $_GET['post'] ) ) ) {
            add_action( 'woocommerce_order_after_calculate_totals', [ $this, 'aaa_pp_apply_promotions_admin' ], 10, 2 );
        }

        // Dashboard widget.
        add_action( 'wp_dashboard_setup', [ $this, 'aaa_pp_register_dashboard_widget' ] );

        // When the promotion settings are updated, create coupon codes if needed.
        add_action( 'updated_option', [ $this, 'aaa_pp_maybe_create_coupons' ], 10, 3 );
    }

    /**
     * Add a settings page in "Settings" menu.
     */
    public function aaa_pp_add_settings_page() {
        add_options_page(
            __( 'AAA Product Promotions Settings', 'aaa-product-promotions' ),
            __( 'Product Promotions', 'aaa-product-promotions' ),
            'manage_options',
            'aaa-product-promotions',
            [ $this, 'aaa_pp_render_settings_page' ]
        );
    }

    /**
     * Render the settings page.
     */
    public function aaa_pp_render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'AAA Product Promotions Settings', 'aaa-product-promotions' ); ?></h1>
            <form method="post" action="options.php">
                <?php
                    settings_fields( 'aaa_product_promotions_group' );
                    do_settings_sections( 'aaa-product-promotions' );
                    submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register all settings for each promotion.
     */
    public function aaa_pp_register_settings() {
        register_setting(
            'aaa_product_promotions_group',
            'aaa_product_promotions_options',
            [ $this, 'aaa_pp_sanitize_options' ]
        );

        add_settings_section(
            'aaa_product_promotions_section',
            __( 'Promotion Settings', 'aaa-product-promotions' ),
            null,
            'aaa-product-promotions'
        );

        // Loop through promotions and add three fields for each.
        foreach ( $this->promotions_data as $key => $promo ) {
            // Field for enabling the promotion.
            add_settings_field(
                $promo['enable_key'],
                $promo['label'] . ' - ' . __( 'Enable', 'aaa-product-promotions' ),
                [ $this, 'aaa_pp_render_checkbox' ],
                'aaa-product-promotions',
                'aaa_product_promotions_section',
                [ 'id' => $promo['enable_key'] ]
            );
            // Field for disabling admin auto‑load.
            add_settings_field(
                $promo['disable_auto_key'],
                $promo['label'] . ' - ' . __( 'Disable Admin Auto‑Load', 'aaa-product-promotions' ),
                [ $this, 'aaa_pp_render_checkbox' ],
                'aaa-product-promotions',
                'aaa_product_promotions_section',
                [ 'id' => $promo['disable_auto_key'] ]
            );
            // Field for entering the coupon code.
            add_settings_field(
                $promo['coupon_code_key'],
                $promo['label'] . ' - ' . __( 'Coupon Code', 'aaa-product-promotions' ),
                [ $this, 'aaa_pp_render_text_field' ],
                'aaa-product-promotions',
                'aaa_product_promotions_section',
                [ 'id' => $promo['coupon_code_key'] ]
            );
        }
    }

    /**
     * Sanitize and store options.
     */
    public function aaa_pp_sanitize_options( $input ) {
        $output = [];
        // Loop through each promotion.
        foreach ( $this->promotions_data as $key => $promo ) {
            $output[ $promo['enable_key'] ] = ! empty( $input[ $promo['enable_key'] ] ) ? 1 : 0;
            $output[ $promo['disable_auto_key'] ] = ! empty( $input[ $promo['disable_auto_key'] ] ) ? 1 : 0;
            $output[ $promo['coupon_code_key'] ] = isset( $input[ $promo['coupon_code_key'] ] ) ? sanitize_text_field( $input[ $promo['coupon_code_key'] ] ) : '';
        }
        return $output;
    }

    /**
     * Render a checkbox input field.
     */
    public function aaa_pp_render_checkbox( $args ) {
        $options = get_option( 'aaa_product_promotions_options', [] );
        $id = $args['id'];
        $checked = ! empty( $options[ $id ] ) ? 'checked' : '';
        echo '<input type="checkbox" name="aaa_product_promotions_options[' . esc_attr( $id ) . ']" value="1" ' . $checked . ' />';
    }

    /**
     * Render a text input field.
     */
    public function aaa_pp_render_text_field( $args ) {
        $options = get_option( 'aaa_product_promotions_options', [] );
        $id = $args['id'];
        $value = isset( $options[ $id ] ) ? esc_attr( $options[ $id ] ) : '';
        echo '<input type="text" name="aaa_product_promotions_options[' . esc_attr( $id ) . ']" value="' . $value . '" />';
    }

    /**
     * Front-end discount logic (cart).
     *
     * Note: The disable auto‑load option is not checked here so that discounts always apply on the front‑end.
     */
    public function aaa_pp_apply_promotions() {
        $options = get_option( 'aaa_product_promotions_options', [] );
        $cart = WC()->cart;
        if ( ! $cart ) {
            return;
        }

        $promotions = [];

        // Loop over each promotion in our mapping.
        foreach ( $this->promotions_data as $promo ) {
            // For front-end, we only check if the promotion is enabled.
            if ( ! empty( $options[ $promo['enable_key'] ] ) ) {
                $promotion = [
                    'tag'        => $promo['tag'],
                    'name'       => $promo['label'],
                    'group_size' => $promo['group_size'],
                ];
                if ( $promo['type'] === 'fixed' ) {
                    $promotion['type'] = 'fixed_price';
                    $promotion['fixed_price'] = $promo['discount'];
                } else {
                    $promotion['discount'] = $promo['discount'];
                }
                $promotions[] = $promotion;
            }
        }

        // Apply each promotion as a negative fee.
        foreach ( $promotions as $promo ) {
            $eligible = [];
            foreach ( $cart->get_cart() as $cart_item ) {
                $product = $cart_item['data'];
                if ( has_term( $promo['tag'], 'product_tag', $product->get_id() ) ) {
                    $eligible[] = [
                        'price' => floatval( $product->get_price() ),
                        'qty'   => $cart_item['quantity']
                    ];
                }
            }
            $total_qty = 0;
            foreach ( $eligible as $el ) {
                $total_qty += $el['qty'];
            }
            if ( $total_qty < $promo['group_size'] ) {
                continue;
            }
            $discount_qty = floor( $total_qty / $promo['group_size'] ) * $promo['group_size'];
            $discount = 0;
            if ( ! empty( $promo['type'] ) && $promo['type'] === 'fixed_price' ) {
                usort( $eligible, function( $a, $b ) { return $a['price'] <=> $b['price']; } );
                foreach ( $eligible as $item ) {
                    if ( $discount_qty <= 0 ) {
                        break;
                    }
                    $apply = min( $item['qty'], $discount_qty );
                    $per_item_disc = max( 0, $item['price'] - $promo['fixed_price'] );
                    $discount += $apply * $per_item_disc;
                    $discount_qty -= $apply;
                }
            } else {
                usort( $eligible, function( $a, $b ) { return $a['price'] <=> $b['price']; } );
                foreach ( $eligible as $item ) {
                    if ( $discount_qty <= 0 ) {
                        break;
                    }
                    $apply = min( $item['qty'], $discount_qty );
                    $discount += $apply * $item['price'] * $promo['discount'];
                    $discount_qty -= $apply;
                }
            }
            if ( $discount > 0 ) {
                $cart->add_fee( $promo['name'], -$discount );
            }
        }
    }

    /**
     * Admin order discount logic.
     *
     * Note: In the admin, the discount is only auto‑applied if "Disable Admin Auto‑Load" is NOT checked.
     */
    public function aaa_pp_apply_promotions_admin( $and_taxes, $order ) {
        // Skip auto‑apply when editing an order in admin.
        if ( is_admin() && isset( $_GET['post'] ) ) {
            return;
        }

        if ( ! $order || ! is_a( $order, 'WC_Order' ) ) {
            return;
        }

        $options = get_option( 'aaa_product_promotions_options', [] );
        // Remove existing fees from this plugin.
        foreach ( $order->get_items( 'fee' ) as $fee_id => $fee_item ) {
            $fee_name = $fee_item->get_name();
            if ( strpos( $fee_name, 'Off' ) !== false || strpos( $fee_name, 'Promotion' ) !== false ) {
                $order->remove_item( $fee_id );
            }
        }

        $promotions = [];
        foreach ( $this->promotions_data as $promo ) {
            // In admin, check both the promotion enable and that auto‑load is not disabled.
            if ( ! empty( $options[ $promo['enable_key'] ] ) && empty( $options[ $promo['disable_auto_key'] ] ) ) {
                $promotion = [
                    'tag'        => $promo['tag'],
                    'name'       => $promo['label'],
                    'group_size' => $promo['group_size'],
                ];
                if ( $promo['type'] === 'fixed' ) {
                    $promotion['type'] = 'fixed_price';
                    $promotion['fixed_price'] = $promo['discount'];
                } else {
                    $promotion['discount'] = $promo['discount'];
                }
                $promotions[] = $promotion;
            }
        }

        $eligibleItems = function( $tag, $order ) {
            $results = [];
            foreach ( $order->get_items() as $item ) {
                if ( ! is_a( $item, 'WC_Order_Item_Product' ) ) {
                    continue;
                }
                $prod = $item->get_product();
                if ( ! $prod ) {
                    continue;
                }
                if ( has_term( $tag, 'product_tag', $prod->get_id() ) ) {
                    $qty      = $item->get_quantity();
                    $subtotal = $item->get_subtotal();
                    $priceEach = ( $qty > 0 ) ? floatval( $subtotal / $qty ) : 0.0;
                    $results[] = [
                        'qty'   => $qty,
                        'price' => $priceEach,
                    ];
                }
            }
            return $results;
        };

        foreach ( $promotions as $promo ) {
            $items = $eligibleItems( $promo['tag'], $order );
            $total_qty = 0;
            foreach ( $items as $it ) {
                $total_qty += $it['qty'];
            }
            if ( $total_qty < $promo['group_size'] ) {
                continue;
            }
            $discount_qty = floor( $total_qty / $promo['group_size'] ) * $promo['group_size'];
            $discount = 0;
            if ( ! empty( $promo['type'] ) && $promo['type'] === 'fixed_price' ) {
                usort( $items, function( $a, $b ) { return $a['price'] <=> $b['price']; } );
                foreach ( $items as $it ) {
                    if ( $discount_qty <= 0 ) {
                        break;
                    }
                    $apply = min( $it['qty'], $discount_qty );
                    $per_item_disc = max( 0, $it['price'] - $promo['fixed_price'] );
                    $discount += $apply * $per_item_disc;
                    $discount_qty -= $apply;
                }
            } else {
                usort( $items, function( $a, $b ) { return $a['price'] <=> $b['price']; } );
                foreach ( $items as $it ) {
                    if ( $discount_qty <= 0 ) {
                        break;
                    }
                    $apply = min( $it['qty'], $discount_qty );
                    $discount += $apply * $it['price'] * $promo['discount'];
                    $discount_qty -= $apply;
                }
            }
            if ( $discount > 0 ) {
                $fee = new WC_Order_Item_Fee();
                $fee->set_name( $promo['label'] );
                $fee->set_amount( -$discount );
                $fee->set_total( -$discount );
                $order->add_item( $fee );
            }
        }
        $order->save();
    }

    /**
     * Dashboard widget registration.
     */
    public function aaa_pp_register_dashboard_widget() {
        wp_add_dashboard_widget(
            'aaa_product_promotions_widget',
            __( 'Current Product Promotions', 'aaa-product-promotions' ),
            [ $this, 'aaa_pp_render_dashboard_widget' ]
        );
    }

    /**
     * Dashboard widget output: list each promotion's status.
     */
    public function aaa_pp_render_dashboard_widget() {
        $options = get_option( 'aaa_product_promotions_options', [] );
        echo '<ul style="margin-left:20px;">';
        foreach ( $this->promotions_data as $promo ) {
            $enabled = ! empty( $options[ $promo['enable_key'] ] );
            echo '<li><strong>' . esc_html( $promo['label'] ) . ':</strong> ';
            echo $enabled ? '<span style="color:green;">Enabled</span>' : '<span style="color:red;">Disabled</span>';
            echo '</li>';
        }
        echo '</ul>';
        echo '<p style="margin-top:10px;">' .
             sprintf(
                __( 'Enable or disable these promotions in <a href="%s">Settings &rarr; Product Promotions</a>.', 'aaa-product-promotions' ),
                esc_url( admin_url( 'options-general.php?page=aaa-product-promotions' ) )
             ) .
             '</p>';
    }

    /**
     * When promotion settings are updated, create coupon codes if needed.
     */
    public function aaa_pp_maybe_create_coupons( $option, $old_value, $new_value ) {
        if ( 'aaa_product_promotions_options' !== $option ) {
            return;
        }

        // Loop through each promotion.
        foreach ( $this->promotions_data as $promo ) {
            $enabled       = ! empty( $new_value[ $promo['enable_key'] ] );
            $disable_auto  = ! empty( $new_value[ $promo['disable_auto_key'] ] );
            $coupon_code   = isset( $new_value[ $promo['coupon_code_key'] ] ) ? trim( $new_value[ $promo['coupon_code_key'] ] ) : '';

            // Only create a coupon if the promotion is enabled, admin auto‑load is disabled, and a coupon code is provided.
            if ( $enabled && $disable_auto && ! empty( $coupon_code ) ) {
                $this->aaa_pp_create_or_update_coupon( $promo, $coupon_code );
            }
        }
    }

    /**
     * Create or update a WooCommerce coupon for a given promotion.
     *
     * @param array  $promo       Promotion data from $this->promotions_data.
     * @param string $coupon_code The coupon code to create/update.
     */
    private function aaa_pp_create_or_update_coupon( $promo, $coupon_code ) {
        // Check if coupon exists using the WooCommerce helper function.
        $coupon_id = wc_get_coupon_id_by_code( $coupon_code );
        $coupon_data = [
            'post_title'   => $coupon_code,
            'post_content' => '',
            'post_status'  => 'publish',
            'post_author'  => get_current_user_id(),
            'post_type'    => 'shop_coupon'
        ];

        // Prepare meta data.
        $meta_data = [];
        if ( $promo['type'] === 'fixed' ) {
            $meta_data['discount_type'] = 'fixed_cart';
            $meta_data['coupon_amount'] = $promo['discount'];
        } else {
            $meta_data['discount_type'] = 'percent';
            // Convert fraction to percentage.
            $meta_data['coupon_amount'] = $promo['discount'] * 100;
        }
        $meta_data['individual_use'] = 'yes';
        $meta_data['usage_limit'] = 1;

        // Create or update coupon.
        if ( $coupon_id ) {
            // Update coupon meta.
            foreach ( $meta_data as $meta_key => $meta_value ) {
                update_post_meta( $coupon_id, $meta_key, $meta_value );
            }
        } else {
            // Insert new coupon.
            $new_coupon_id = wp_insert_post( $coupon_data );
            if ( $new_coupon_id && ! is_wp_error( $new_coupon_id ) ) {
                foreach ( $meta_data as $meta_key => $meta_value ) {
                    update_post_meta( $new_coupon_id, $meta_key, $meta_value );
                }
            }
        }
    }
}

// Instantiate the plugin.
new AAA_Product_Promotions();
