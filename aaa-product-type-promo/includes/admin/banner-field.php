<?php
/**
 * Banner image field for Promo products without relying on external meta box
 * plugins.  This module registers a meta box with a simple interface to
 * upload or select an image from the WordPress media library.  The field
 * appears only when editing or creating a Promo product type.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register the banner meta box for products.
 */
add_action( 'add_meta_boxes', function () {
    add_meta_box(
        'promo_banner_metabox',
        __( 'Promo Banner Image', 'aaa' ),
        function ( $post ) {
            // Nonce field for security.
            wp_nonce_field( 'promo_banner_save', 'promo_banner_nonce' );
            // Determine product type.
            $product_type = get_post_meta( $post->ID, '_product_type', true );
            $requested_type = isset( $_GET['product_type'] ) ? wc_clean( wp_unslash( $_GET['product_type'] ) ) : '';
            // When creating a new product, _product_type won't exist yet, so use requested type.
            $current_type = $product_type ?: $requested_type;
            if ( 'promo' !== $current_type ) {
                echo '<p>' . esc_html__( 'The promo banner image field is only available when the product type is set to Promo.', 'aaa' ) . '</p>';
                return;
            }
            $image_id = get_post_meta( $post->ID, '_promo_banner_image', true );
            $image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'thumbnail' ) : '';
            ?>
            <div class="promo-banner-image-field">
                <img id="promo-banner-preview" src="<?php echo esc_url( $image_url ); ?>" style="<?php echo $image_url ? '' : 'display:none;'; ?> max-width:100%; height:auto; margin-bottom:10px;" />
                <input type="hidden" id="promo-banner-image-id" name="promo_banner_image" value="<?php echo esc_attr( $image_id ); ?>" />
                <button type="button" class="button" id="promo-banner-upload"><?php esc_html_e( 'Select Banner Image', 'aaa' ); ?></button>
                <button type="button" class="button" id="promo-banner-remove" style="<?php echo $image_url ? '' : 'display:none;'; ?> margin-left:5px;">
                    <?php esc_html_e( 'Remove', 'aaa' ); ?>
                </button>
            </div>
            <?php
        },
        'product',
        'side',
        'low'
    );
} );

/**
 * Enqueue media scripts and our custom JS for the admin banner field.
 *
 * @param string $hook The current admin page.
 */
add_action( 'admin_enqueue_scripts', function ( $hook ) {
    // Only enqueue on product add/edit screens.
    if ( in_array( $hook, [ 'post-new.php', 'post.php' ], true ) ) {
        $screen = get_current_screen();
        if ( $screen && 'product' === $screen->post_type ) {
            wp_enqueue_media();
            wp_enqueue_script(
                'promo-banner-admin-script',
                plugin_dir_url( __FILE__ ) . 'js/promo-banner-admin.js',
                [ 'jquery' ],
                '1.0',
                true
            );
        }
    }
} );

/**
 * Save the banner image meta when a product is saved.  Only save for promo
 * products and when a valid nonce is present.
 *
 * @param int $post_id The product ID being saved.
 */
add_action( 'save_post_product', function ( $post_id ) {
    // Verify nonce.
    if ( ! isset( $_POST['promo_banner_nonce'] ) || ! wp_verify_nonce( $_POST['promo_banner_nonce'], 'promo_banner_save' ) ) {
        return;
    }
    // Prevent autosave from interfering.
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    // Check user capability.
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }
    // Only store the value for promo products.
    $product_type = get_post_meta( $post_id, '_product_type', true );
    if ( 'promo' === $product_type && isset( $_POST['promo_banner_image'] ) ) {
        $new_id = absint( $_POST['promo_banner_image'] );
        update_post_meta( $post_id, '_promo_banner_image', $new_id );
    }
} );
