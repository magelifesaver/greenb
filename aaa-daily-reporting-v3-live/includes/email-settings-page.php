<?php
/**
 * File: includes/email-settings-page.php
 * Description: Registers menus for AAA Daily Reporting and loads form/save logic.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Add top-level and submenu pages under "Daily Report (v3)"

function aaa_render_email_settings_page() {
    ?>
    <div class="wrap">
      <h1 style="display:flex;justify-content:space-between;align-items:center;">
        <span>Generate &amp; Send Report</span>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=aaa-daily-report-v3' ) ); ?>" class="button button-secondary">
          ← Back to Report
        </a>
      </h1>

      <table class="form-table">
        <tr>
          <th><label for="aaa_email_field">Recipient Emails</label></th>
          <td>
            <input type="text" id="aaa_email_field" class="regular-text" placeholder="email@example.com, another@example.com" required>
            <p class="description">Enter one or more emails, separated by commas.</p>
            <p class="description">buk808@gmail.com</p>
            <p class="description">borzoo.b@gmail.com</p>
            <p class="description">elizabethkazar@gmail.com</p>
            <p class="description">webmaster@lokeydelivery.com</p>
            <p class="description">webmaster@lokeydelivery.com, marselsaeedi@gmail.com</p>
            <p class="description">buk808@gmail.com, borzoo.b@gmail.com, elizabethkazar@gmail.com, webmaster@lokeydelivery.com</p>
          </td>
        </tr>
        <tr>
          <th><label for="aaa_report_date">Report Date</label></th>
          <td>
            <input type="date" id="aaa_report_date" required>
          </td>
        </tr>
      </table>

      <p>
        <button id="aaa-send-report-btn" class="button button-primary">Generate &amp; Send Report</button>
        <span id="aaa-send-report-status"></span>
      </p>
    </div>
    <?php
}

// Enqueue & localize
add_action( 'admin_enqueue_scripts', function() {
    if ( empty( $_GET['page'] ) || $_GET['page'] !== 'aaa-report-email-settings' ) {
        return;
    }

    wp_enqueue_script(
      'aaa-generate-send',
      plugins_url( '../assets/js/generate-send.js', __FILE__ ),
      [ 'jquery' ],
      '1.2.0', // updated version
      true
    );
    wp_localize_script( 'aaa-generate-send', 'AAAgen', [
      'ajax_url'            => admin_url( 'admin-ajax.php' ),
      'custom_date_nonce'   => wp_create_nonce( 'aaa_generate_custom_nonce' ),
    ] );
});

add_action( 'wp_ajax_aaa_send_report_by_date', function() {
    check_ajax_referer( 'aaa_generate_custom_nonce', 'nonce' );

    $raw_input = $_POST['email'] ?? '';
    $date      = sanitize_text_field( $_POST['date'] ?? '' );

    // Validate date format (YYYY-MM-DD)
    if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
        wp_send_json_error( 'Invalid date format.' );
    }

    // Split & sanitize emails
    $email_list = array_filter(
        array_map( 'sanitize_email', explode( ',', $raw_input ) ),
        'is_email'
    );

    if ( empty( $email_list ) ) {
        wp_send_json_error( 'No valid email addresses provided.' );
    }

    $orders = aaa_get_orders_for_date( $date );
    if ( empty( $orders ) ) {
        wp_send_json_error( 'No orders found for that date.' );
    }

    // Build HTML
    ob_start();
      echo '<h1>AAA Daily Report — ' . esc_html( $date ) . '</h1>';
      aaa_render_report_summary(            $orders );
      aaa_render_top_summary_section(       $orders );
      aaa_render_orders_section_v3(         $orders );
      aaa_render_product_breakdown(         $orders );
      aaa_render_product_summary_table(     $orders );
      aaa_render_customer_summary_v2(       $orders );
      aaa_render_payment_summary_v2(        $orders );
      aaa_render_brands_categories_summary_v2( $orders );
      aaa_render_delivery_city_report(      $orders );
      aaa_render_refunds_and_cancels_v2(    $date );
    $html = ob_get_clean();

    // PDF logic
    $dompdf = new \Dompdf\Dompdf();
    $css_file = plugin_dir_path( __FILE__ ) . '../assets/css/report-style.css';
    $css = file_exists( $css_file ) ? file_get_contents( $css_file ) : '';
    $full_html = '<!doctype html><html><head><style>' . $css . '</style></head><body>' . $html . '</body></html>';
    $dompdf->loadHtml( $full_html );
    $dompdf->setPaper( 'A4', 'landscape' );
    $dompdf->render();

    $tmp = tempnam( sys_get_temp_dir(), 'aaa-report-' . $date );
    file_put_contents( $tmp, $dompdf->output() );

    $sent = wp_mail(
        $email_list,
        'AAA Daily Report – ' . $date,
        $html,
        [ 'Content-Type: text/html' ],
        [ $tmp ]
    );

    @unlink( $tmp );

    if ( $sent ) {
        wp_send_json_success( 'Sent to ' . esc_html( implode( ', ', $email_list ) ) );
    } else {
        wp_send_json_error( 'Failed to send email.' );
    }
});
