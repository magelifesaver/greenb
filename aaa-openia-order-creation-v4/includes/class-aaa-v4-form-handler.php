<?php
// File: /aaa-openia-order-creation-v4/includes/class-aaa-v4-form-handler.php
 
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
 
class AAA_V4_Form_Handler {
 
    public static function render_form() {
        $order_html = isset( $_POST['order_html'] ) ? wp_unslash( $_POST['order_html'] ) : '';
 
        // === Inject fallback image from settings ===
        $settings     = get_option( 'aaa_v4_order_creator_settings', [] );
        $fallback_raw = $settings['fallback_image_id'] ?? '';
        $fallback_url = '';
 
        if ( is_numeric( $fallback_raw ) ) {
            $fallback_url = wp_get_attachment_url( (int) $fallback_raw );
        } elseif ( is_string( $fallback_raw ) && filter_var( $fallback_raw, FILTER_VALIDATE_URL ) ) {
            $fallback_url = esc_url( $fallback_raw );
        }
        ?>
        <div class="wrap">
            <h1>Create Order (V4)</h1>
	        <div style="margin-bottom: 15px;">
        <a href="<?php echo admin_url( 'admin.php?page=aaa-v4-order-settings' ); ?>" 
           class="button button-modern button-ocsettings button-secondary"
           target="_blank">
           ⚙️ Order Creator Settings
        </a>
    </div>
	    <?php if ( ! empty( $_POST['aaa_v4_finalize_order'] ) ) :
	    $order_id = AAA_V4_Order_Creator::create_order_from_preview( $_POST, [] );
	    if ( $order_id ) :
	        echo '<div style="padding:10px; background:#e6ffed; border:1px solid #b2f2bb; margin:15px 0;">
	                <strong>Order created successfully!</strong>
	                <a href="' . esc_url( admin_url( 'post.php?post=' . $order_id . '&action=edit' ) ) . '" target="_blank">
	                    View Order
	                </a>
	              </div>';
		endif;
	    endif; ?>
 
 
            <!-- Global JS variable for placeholder image -->
            <script>
                window.AAA_V4_PLACEHOLDER_IMAGE = "<?php echo esc_js( $fallback_url ); ?>";
            </script>

            <?php if ( empty( $settings['hide_parser_textarea'] ) ) : ?>
            <form method="post" id="aaa-v4-upload-form">
                <p>
                    <label for="order_html"><strong>Paste Full Order HTML:</strong></label><br>
                    <textarea id="order_html" name="order_html" rows="5" style="width:100%;"><?php echo esc_textarea( $order_html ); ?></textarea>
                </p>
                <p>
                    <input type="submit" name="aaa_v4_parse_html" class="button button-primary" value="Parse and Preview Order">
                </p>
            </form>
            <?php endif; ?>
 
        <?php
        // === Preview/create form ===
        $parsed_data = [];

        // If parser is disabled, skip parsing but still set empty array
        if ( empty( $settings['disable_parser'] ) ) {
            $parsed_data = AAA_V4_Parser::parse_html_and_build_preview( $_POST );
        }

        // Always ensure parsed_data is an array
        if ( ! is_array( $parsed_data ) ) {
            $parsed_data = [];
        }

        // Render the preview builders with parsed_data (even if empty)
        AAA_V4_Preview_Builder_Top::render_top( $parsed_data );
        AAA_V4_Preview_Builder_Bottom::render_bottom( $parsed_data );
        ?>
        </div>
        <?php
    }
}
