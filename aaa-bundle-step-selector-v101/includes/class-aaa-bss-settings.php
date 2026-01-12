<?php
/**
 * File: /wp-content/plugins/aaa-bundle-step-selector/includes/class-aaa-bss-settings.php
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class AAA_BSS_Settings {

    const OPTION_KEY = 'aaa_bss_settings';

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'admin_menu' ], 99 );
        add_action( 'admin_init', [ $this, 'maybe_save' ] );
        add_filter( 'plugin_action_links_' . AAA_BSS_BASENAME, [ $this, 'plugin_links' ] );
    }

    public static function defaults() {
        return [
            'step1_product_ids' => '',
            'step1_max'         => 2,
            'step2_product_ids' => '',
            'step2_max'         => 1,
            'step2_is_free'     => 'yes',
            'redirect_to'       => 'cart', // cart|checkout
        ];
    }

    public static function get() {
        $saved = get_option( self::OPTION_KEY, [] );
        if ( ! is_array( $saved ) ) { $saved = []; }
        return array_merge( self::defaults(), $saved );
    }

    public static function parse_ids( $csv ) {
        $csv = is_string( $csv ) ? $csv : '';
        $csv = preg_replace( '/[^0-9,]/', '', $csv );
        $parts = array_filter( array_map( 'trim', explode( ',', $csv ) ) );
        $ids = array_map( 'absint', $parts );
        return array_values( array_unique( array_filter( $ids ) ) );
    }

    public function plugin_links( $links ) {
        $url = admin_url( 'admin.php?page=aaa-bss' );
        array_unshift( $links, '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'aaa-bss' ) . '</a>' );
        return $links;
    }

    public function admin_menu() {
        add_submenu_page(
            'woocommerce',
            esc_html__( 'Bundle Step Selector', 'aaa-bss' ),
            esc_html__( 'Bundle Step Selector', 'aaa-bss' ),
            'manage_woocommerce',
            'aaa-bss',
            [ $this, 'render' ]
        );
    }

    public function maybe_save() {
        if ( empty( $_POST['aaa_bss_action'] ) || 'save' !== $_POST['aaa_bss_action'] ) { return; }
        if ( ! current_user_can( 'manage_woocommerce' ) ) { return; }
        check_admin_referer( 'aaa_bss_save', 'aaa_bss_nonce' );

        $settings = self::get();
        $settings['step1_product_ids'] = sanitize_text_field( wp_unslash( $_POST['step1_product_ids'] ?? '' ) );
        $settings['step1_max'] = max( 1, absint( $_POST['step1_max'] ?? 2 ) );

        $settings['step2_product_ids'] = sanitize_text_field( wp_unslash( $_POST['step2_product_ids'] ?? '' ) );
        $settings['step2_max'] = max( 0, absint( $_POST['step2_max'] ?? 1 ) );

        $settings['step2_is_free'] = isset( $_POST['step2_is_free'] ) ? 'yes' : 'no';

        $redirect_to = sanitize_key( wp_unslash( $_POST['redirect_to'] ?? 'cart' ) );
        $settings['redirect_to'] = in_array( $redirect_to, [ 'cart', 'checkout' ], true ) ? $redirect_to : 'cart';

        update_option( self::OPTION_KEY, $settings, false );

        aaa_bss_log( 'Settings saved', [
            'step1_ids' => $settings['step1_product_ids'],
            'step1_max' => (int) $settings['step1_max'],
            'step2_ids' => $settings['step2_product_ids'],
            'step2_max' => (int) $settings['step2_max'],
        ] );

        wp_safe_redirect( add_query_arg( [ 'page' => 'aaa-bss', 'aaa_bss_saved' => '1' ], admin_url( 'admin.php' ) ) );
        exit;
    }

    public function render() {
        $settings = self::get();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__( 'Bundle Step Selector', 'aaa-bss' ); ?></h1>

            <?php if ( isset( $_GET['aaa_bss_saved'] ) ) : ?>
                <div class="notice notice-success"><p><?php echo esc_html__( 'Settings saved.', 'aaa-bss' ); ?></p></div>
            <?php endif; ?>

            <p><strong><?php echo esc_html__( 'Shortcode:', 'aaa-bss' ); ?></strong> <code>[aaa_bss]</code></p>

            <form method="post">
                <?php wp_nonce_field( 'aaa_bss_save', 'aaa_bss_nonce' ); ?>
                <input type="hidden" name="aaa_bss_action" value="save" />

                <h2><?php echo esc_html__( 'Step 1 (Paid selection)', 'aaa-bss' ); ?></h2>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php echo esc_html__( 'Product IDs', 'aaa-bss' ); ?></th>
                        <td>
                            <input type="text" class="regular-text" name="step1_product_ids" value="<?php echo esc_attr( $settings['step1_product_ids'] ); ?>" />
                            <p class="description"><?php echo esc_html__( 'Comma-separated product IDs allowed in Step 1.', 'aaa-bss' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html__( 'Max total quantity', 'aaa-bss' ); ?></th>
                        <td>
                            <input type="number" min="1" name="step1_max" value="<?php echo esc_attr( (int) $settings['step1_max'] ); ?>" />
                            <p class="description"><?php echo esc_html__( 'Total units across Step 1 products the customer can select.', 'aaa-bss' ); ?></p>
                        </td>
                    </tr>
                </table>

                <h2><?php echo esc_html__( 'Step 2 (Free selection)', 'aaa-bss' ); ?></h2>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php echo esc_html__( 'Product IDs', 'aaa-bss' ); ?></th>
                        <td>
                            <input type="text" class="regular-text" name="step2_product_ids" value="<?php echo esc_attr( $settings['step2_product_ids'] ); ?>" />
                            <p class="description"><?php echo esc_html__( 'Comma-separated product IDs allowed in Step 2.', 'aaa-bss' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html__( 'Max total quantity', 'aaa-bss' ); ?></th>
                        <td>
                            <input type="number" min="0" name="step2_max" value="<?php echo esc_attr( (int) $settings['step2_max'] ); ?>" />
                            <p class="description"><?php echo esc_html__( 'Total units across Step 2 products the customer can select.', 'aaa-bss' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html__( 'Make Step 2 items free in cart', 'aaa-bss' ); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="step2_is_free" <?php checked( $settings['step2_is_free'], 'yes' ); ?> />
                                <?php echo esc_html__( 'Yes (set Step 2 item price to $0 in cart)', 'aaa-bss' ); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html__( 'After Add to Cart redirect to', 'aaa-bss' ); ?></th>
                        <td>
                            <select name="redirect_to">
                                <option value="cart" <?php selected( $settings['redirect_to'], 'cart' ); ?>><?php echo esc_html__( 'Cart', 'aaa-bss' ); ?></option>
                                <option value="checkout" <?php selected( $settings['redirect_to'], 'checkout' ); ?>><?php echo esc_html__( 'Checkout', 'aaa-bss' ); ?></option>
                            </select>
                        </td>
                    </tr>
                </table>

                <?php submit_button( esc_html__( 'Save Settings', 'aaa-bss' ) ); ?>
            </form>
        </div>
        <?php
    }
}
