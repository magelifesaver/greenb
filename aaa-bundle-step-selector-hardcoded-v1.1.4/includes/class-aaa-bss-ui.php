<?php
/**
 * File: /wp-content/plugins/aaa-bundle-step-selector/includes/class-aaa-bss-ui.php
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class AAA_BSS_UI {

    private $did_output = false;


    public function __construct() {
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );

        add_action( 'woocommerce_before_main_content', [ $this, 'maybe_render_banner_and_modal' ], 6 );
        add_action( 'woocommerce_before_single_product', [ $this, 'maybe_render_banner_and_modal' ], 6 );

        // Fallback: some templates/filters skip Woo hooks (especially for logged-out/cached views).
        add_action( 'wp_footer', [ $this, 'maybe_render_banner_and_modal' ], 5 );

        add_shortcode( 'aaa_bss_modal', [ $this, 'shortcode_modal_only' ] );
    }

    public function enqueue_assets() {
        wp_register_style( 'aaa-bss', AAA_BSS_URL . 'assets/css/aaa-bss.css', [], AAA_BSS_VERSION );
        wp_register_script( 'aaa-bss', AAA_BSS_URL . 'assets/js/aaa-bss.js', [ 'jquery' ], AAA_BSS_VERSION, true );
    }

    public function shortcode_modal_only() {
        if ( ! function_exists( 'WC' ) ) { return ''; }
        $this->enqueue_now();
        return $this->render_modal_markup();
    }

    public function maybe_render_banner_and_modal() {
        if ( $this->did_output ) { return; }
        if ( ! function_exists( 'WC' ) ) { return; }
        if ( ! $this->should_show_banner_here() ) { return; }

        $this->enqueue_now();
        $this->did_output = true;

        echo $this->render_banner_markup();
        echo $this->render_modal_markup();
    }

    private function enqueue_now() {
        wp_enqueue_style( 'aaa-bss' );
        wp_enqueue_script( 'aaa-bss' );

        wp_localize_script( 'aaa-bss', 'AAA_BSS', [
            'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'aaa_bss_add_to_cart' ),
            'step1Max' => (int) AAA_BSS_Config::step1_max(),
            'step2Max' => (int) AAA_BSS_Config::step2_max(),
        ] );
    }

    private function should_show_banner_here() {
        $uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
        $uri_no_qs = strtok( $uri, '?' );

        foreach ( AAA_BSS_Config::archive_path_matches() as $p ) {
            if ( 0 === strpos( $uri_no_qs, (string) $p ) ) {
                return true;
            }
        }

        if ( function_exists( 'is_product' ) && is_product() ) {
            $pid = (int) get_queried_object_id();
            if ( in_array( $pid, AAA_BSS_Config::product_page_ids(), true ) ) {
                return true;
            }
        }

        return false;
    }

    private function render_banner_markup() {
        $img_url = AAA_BSS_Config::banner_image_url();

        $html  = '<div class="aaa-bss-banner-wrap">';
        $html .= '<button type="button" class="aaa-bss-banner-btn" aria-label="' . esc_attr__( 'Open promotion', 'aaa-bss' ) . '">';
        $html .= '<img class="aaa-bss-banner-img" src="' . esc_url( $img_url ) . '" alt="' . esc_attr__( 'Promotion', 'aaa-bss' ) . '" />';
        $html .= '</button>';
        $html .= '</div>';

        return $html;
    }

    private function render_modal_markup() {
        $step1_ids = AAA_BSS_Config::step1_product_ids();
        $step2_ids = AAA_BSS_Config::step2_product_ids();

        ob_start();
        ?>
        <div class="aaa-bss-modal" style="display:none;" aria-hidden="true">
            <div class="aaa-bss-modal-backdrop"></div>
            <div class="aaa-bss-modal-panel" role="dialog" aria-modal="true">
                <div class="aaa-bss-modal-header">
                    <div class="aaa-bss-modal-title"><?php echo esc_html__( 'Promotion', 'aaa-bss' ); ?></div>
                    <button type="button" class="aaa-bss-close"><?php echo esc_html__( 'Close', 'aaa-bss' ); ?></button>
                </div>

                <div class="aaa-bss" data-step="1">
                    <div class="aaa-bss-success" style="display:none;">
                        <div class="aaa-bss-success-title"><?php echo esc_html__( 'Added to cart.', 'aaa-bss' ); ?></div>
                        <div class="aaa-bss-success-sub"><?php echo esc_html__( 'This promotion is active in your cart.', 'aaa-bss' ); ?></div>
                    </div>

                    <div class="aaa-bss-form">
                        <div class="aaa-bss-step aaa-bss-step-1">
                            <div class="aaa-bss-title"><?php echo esc_html__( 'Step 1', 'aaa-bss' ); ?></div>
                            <div class="aaa-bss-subtitle"><?php echo esc_html__( 'Select your items (up to the maximum).', 'aaa-bss' ); ?></div>
                            <?php echo $this->render_product_rows( $step1_ids, 'step1', (int) AAA_BSS_Config::step1_max() ); ?>
                            <div class="aaa-bss-actions">
                                <button type="button" class="aaa-bss-btn aaa-bss-next"><?php echo esc_html__( 'Next', 'aaa-bss' ); ?></button>
                            </div>
                        </div>

                        <div class="aaa-bss-step aaa-bss-step-2" style="display:none;">
                            <div class="aaa-bss-title"><?php echo esc_html__( 'Step 2', 'aaa-bss' ); ?></div>
                            <div class="aaa-bss-subtitle"><?php echo esc_html__( 'Select your gift item (required).', 'aaa-bss' ); ?></div>
                            <?php echo $this->render_product_rows( $step2_ids, 'step2', (int) AAA_BSS_Config::step2_max() ); ?>
                            <div class="aaa-bss-actions">
                                <button type="button" class="aaa-bss-btn aaa-bss-back"><?php echo esc_html__( 'Back', 'aaa-bss' ); ?></button>
                                <button type="button" class="aaa-bss-btn aaa-bss-submit"><?php echo esc_html__( 'Add to Cart', 'aaa-bss' ); ?></button>
                            </div>
                        </div>
                    </div>

                    <div class="aaa-bss-msg" style="display:none;"></div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_product_rows( array $product_ids, $group_key, $max_total ) {
        $html = '<div class="aaa-bss-list" data-group="' . esc_attr( $group_key ) . '" data-max="' . esc_attr( (int) $max_total ) . '">';

        $found = 0;
        $missing = [];

        foreach ( $product_ids as $pid ) {
            $product = wc_get_product( (int) $pid );

            if ( ! $product ) {
                $missing[] = (int) $pid;
                $html .= '<div class="aaa-bss-row aaa-bss-row-missing" data-product-id="' . esc_attr( (int) $pid ) . '">';
                $html .= '<div class="aaa-bss-name">' . esc_html__( 'Missing product ID:', 'aaa-bss' ) . ' ' . esc_html( (int) $pid ) . '</div>';
                $html .= '<div class="aaa-bss-qty"><input type="number" min="0" step="1" value="0" disabled /></div>';
                $html .= '</div>';
                continue;
            }

            $found++;
            $meta = $this->build_meta_pills( $product );

            $html .= '<div class="aaa-bss-row" data-product-id="' . esc_attr( (int) $pid ) . '">';
            $html .= '<div class="aaa-bss-left">';
            $html .= '<div class="aaa-bss-name">' . esc_html( $product->get_name() ) . '</div>';
            if ( $meta ) {
                $html .= '<div class="aaa-bss-pills">' . $meta . '</div>';
            }
            $html .= '</div>';
            $html .= '<div class="aaa-bss-qty"><input type="number" min="0" step="1" value="0" /></div>';
            $html .= '</div>';
        }

        $html .= '<div class="aaa-bss-maxnote">' . sprintf( esc_html__( 'Maximum total for this step: %d', 'aaa-bss' ), (int) $max_total ) . '</div>';
        $html .= '</div>';

        aaa_bss_log( 'Render rows', [
            'group'   => $group_key,
            'max'     => (int) $max_total,
            'found'   => (int) $found,
            'missing' => $missing,
        ] );

        return $html;
    }

    private function build_meta_pills( $product ) {
        if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
            return '';
        }

        $pills = [];

        // Categories: show child + parent if possible.
        $terms = get_the_terms( $product->get_id(), 'product_cat' );
        if ( $terms && ! is_wp_error( $terms ) ) {
            // Prefer deepest term (largest parent chain).
            $best = null;
            $best_depth = -1;
            foreach ( $terms as $t ) {
                $depth = 0;
                $cur = $t;
                while ( $cur && $cur->parent ) {
                    $cur = get_term( $cur->parent, 'product_cat' );
                    if ( is_wp_error( $cur ) || ! $cur ) { break; }
                    $depth++;
                    if ( $depth > 10 ) { break; }
                }
                if ( $depth > $best_depth ) {
                    $best_depth = $depth;
                    $best = $t;
                }
            }
            if ( $best ) {
                $pills[] = $this->pill( $best->name );
                if ( $best->parent ) {
                    $parent = get_term( $best->parent, 'product_cat' );
                    if ( $parent && ! is_wp_error( $parent ) ) {
                        $pills[] = $this->pill( $parent->name );
                    }
                }
            }
        }

        // Attributes: try common weight slugs (best effort).
        $attrs_try = [ 'pa_flower-weight', 'pa_weight', 'pa_size', 'pa_pack-size' ];
        foreach ( $attrs_try as $tax ) {
            $val = $product->get_attribute( $tax );
            if ( $val ) {
                $clean = trim( preg_split( '/[|,]/', $val )[0] );
                if ( $clean ) {
                    $pills[] = $this->pill( $clean );
                    break;
                }
            }
        }

        return implode( '', $pills );
    }

    private function pill( $text ) {
        return '<span class="aaa-bss-pill">' . esc_html( $text ) . '</span>';
    }
}
