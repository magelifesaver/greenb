<?php
// File: /aaa-openia-order-creation-v4/includes/class-aaa-v4-preview-builder-top.php
// Purpose: Renders the top half of the Order Creator preview form (customer, source, ID fields, uploads)
// Version: 4.6.0
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAA_V4_Preview_Builder_Top {

    public static function render_top( $parsed_data ) {
        $settings               = get_option( 'aaa_v4_order_creator_settings', [] );
        $available_gateways     = WC()->payment_gateways()->get_available_payment_gateways();
        $zones                  = WC_Shipping_Zones::get_zones();

        $customer_check = AAA_V4_Customer_Handler::find_existing_customer(
            $parsed_data['customer_email'] ?? '',
            $parsed_data['customer_phone'] ?? ''
        );
        $customer_status       = 'New Customer (Will Be Created)';
        $customer_status_color = 'blue';
        $customer_profile_url  = '';
        $matched_by            = '';
        $customer_id           = 0;

        if ( $customer_check ) {
            $customer_id           = $customer_check['user_id'];
            $matched_by            = $customer_check['matched_by'];
            $customer_status       = 'Existing Customer (Matched by ' . ucfirst( $matched_by ) . ')';
            $customer_status_color = 'green';
            $customer_profile_url  = admin_url( 'user-edit.php?user_id=' . $customer_id );
        }

        $uploads     = wp_upload_dir();
        $base_url_js = esc_js( $uploads['baseurl'] );
        ?>
        <script type="text/javascript">
        window.AAA_V4_BASE_UPLOAD_URL = '<?php echo $base_url_js; ?>';
        </script>

        <h2>Order Preview</h2>
        <form method="post" id="aaa-v4-order-form" enctype="multipart/form-data">
            <input type="hidden" name="aaa_v4_finalize_order" value="1">
            <input type="hidden" id="aaa_user_id" name="aaa_user_id" value="<?php echo esc_attr( $customer_id ); ?>">

            <div class="aaa-v4-section">
                <h3>Customer Information</h3>

                <div id="customer-status-display">
                    <p>
                        <strong>Customer Status:</strong>
                        <span style="color:<?php echo esc_attr( $customer_status_color ); ?>;">
                            <?php echo esc_html( $customer_status ); ?>
                        </span>
                        <?php if ( $customer_profile_url ) : ?>
                            | <a href="<?php echo esc_url( $customer_profile_url ); ?>" target="_blank">View Profile</a>
                        <?php endif; ?>
                    </p>
                </div>

								<p><strong>Order Source:</strong><br>
								  <input
								    type="hidden"
								    name="order_source_type"
								    id="order_source_type"
								    value="<?php echo esc_attr( $parsed_data['order_source_type'] ?? '' ); ?>"
								  >

                  <!-- Existing types -->
                  <button type="button" class="order-source-btn button-modern" data-value="weedmaps">Weedmaps</button>
                  <button type="button" class="order-source-btn button-modern" data-value="phone">Phone</button>
                  <button type="button" class="order-source-btn button-modern" data-value="employee">Employee</button>

                  <!-- NEW types -->
                  <button type="button" class="order-source-btn button-modern" data-value="weedmaps_ftp">Weedmaps FTP</button>
                  <button type="button" class="order-source-btn button-modern" data-value="phone_ftp">Phone FTP</button>
                  <button type="button" class="order-source-btn button-modern" data-value="web_ftp">WEB FTP</button>
                </p>

                <script>
                jQuery(function($){
                  $('.order-source-btn').on('click', function(e){
                    e.preventDefault();
                    var val = $(this).data('value');
                    $('#order_source_type').val(val);

                    $('.order-source-btn').removeClass('active');
                    $(this).addClass('active');
                  });

                  var current = $('#order_source_type').val();
                  if (current) {
                    $('.order-source-btn[data-value="'+current+'"]').addClass('active');
                  }
                });
                </script>

                <style>
                  .order-source-btn.active { background: #fed701 !important; color: #fff !important; }
                </style>

                <?php if ( ! empty( $settings['enable_external_order_number'] ) ) : ?>
                    <p><strong>External Order #:</strong><br>
                        <input
                            type="text"
                            name="external_order_number"
                            id="external_order_number"
                            style="width: 50%;"
                            value="<?php echo esc_attr( $parsed_data['external_order_number'] ?? '' ); ?>">
                        <em>(Optional)</em>
                    </p>
                <?php endif; ?>
            </div>

            <div style="display: flex; margin-bottom: 2rem;">
                <!-- LEFT -->
                <div style="flex: 1; padding: 10px; margin: 10px; border: 3px solid #cc0000; border-radius: 4px;">
                    <p><strong>First Name:</strong><br>
                        <input type="text" name="customer_first_name" id="customer_first_name" style="width: 100%;"
                               value="<?php echo esc_attr( $parsed_data['customer_first_name'] ?? '' ); ?>">
                    </p>

                    <p><strong>Last Name:</strong><br>
                        <input type="text" name="customer_last_name" id="customer_last_name" style="width: 100%;"
                               value="<?php echo esc_attr( $parsed_data['customer_last_name'] ?? '' ); ?>">
                    </p>

                    <p><strong>Phone Number:</strong><br>
                        <input type="text" name="customer_phone" id="customer_phone" style="width: 100%;"
                               value="<?php echo esc_attr( $parsed_data['customer_phone'] ?? '' ); ?>">
                        <button type="button" class="button-modern" id="lookup-by-phone">Lookup by Phone</button>
                    </p>

                    <p><strong>Email Address:</strong><br>
                        <input type="email" name="customer_email" id="customer_email" style="width: 100%;"
                               value="<?php echo esc_attr( $parsed_data['customer_email'] ?? '' ); ?>">
                        <button type="button" id="relookup-customer" class="button-modern">Lookup By Email</button>
                    </p>

                    <div id="relookup-message" style="margin-top: 10px;"></div>

                    <?php do_action( 'aaa_v4_preview_after_email_field', $parsed_data ); ?>
                </div>

                <!-- ============================
                     NEW: auto-trigger “Lookup by Phone” if needed
                     ============================ -->
                <?php if ( ! empty( $parsed_data['trigger_phone_relookup'] ) ) : ?>
                    <script type="text/javascript">
                    jQuery(function($){
                        console.log(
                          '[Auto Relookup] Multiple users share phone <?php echo esc_js( $parsed_data['customer_phone'] ); ?>, scheduling Lookup by Phone...'
                        );
                        setTimeout(function(){
                            console.log(
                              '[Auto Relookup] Actually clicking “Lookup by Phone” at ' + new Date().toISOString()
                            );
                            $('#lookup-by-phone').trigger('click');
                        }, 100);
                    });
                    </script>
                <?php endif; ?>
                <!-- ============================
                     End of auto-trigger snippet
                     ============================ -->

                <!-- RIGHT: meta fields -->
                <div class="aaa-v4-section aaa-v4-section-meta" style="flex: 1; padding: 10px; margin: 10px; border: 3px solid #cc0000; border-radius: 4px;">
                    <?php
                    $saved_dln      = $customer_id ? get_user_meta( $customer_id, 'afreg_additional_4532', true ) : '';
                    $saved_dl_exp   = $customer_id ? get_user_meta( $customer_id, 'afreg_additional_4623', true ) : '';
                    $saved_birthday = $customer_id ? get_user_meta( $customer_id, 'afreg_additional_4625', true ) : '';

                    $require_id       = ! empty( $settings['require_id_number'] );
                    $require_dl_exp   = ! empty( $settings['require_dl_expiration'] );
                    $require_birthday = ! empty( $settings['require_birthday'] );
                    ?>

                    <p><strong>Driver’s License # (ID number):</strong><br>
                        <input type="text" name="afreg_additional_4532" id="afreg_additional_4532"
                               class="aaa-v4-input" style="width:100%;"
                               value="<?php echo esc_attr( $parsed_data['afreg_additional_4532'] ?? $saved_dln ); ?>"
                               <?php echo $require_id ? 'required' : ''; ?>>
                        <?php if ( $require_id ) : ?><em style="color:red;">Required</em><?php endif; ?>
                    </p>

                    <p><strong>DL Expiration Date:</strong><br>
                        <input type="date" name="afreg_additional_4623" id="afreg_additional_4623"
                               min="<?php echo date('Y-m-d'); ?>" class="aaa-v4-input" style="width:100%;"
                               value="<?php echo esc_attr( $parsed_data['afreg_additional_4623'] ?? $saved_dl_exp ); ?>"
                               <?php echo $require_dl_exp ? 'required' : ''; ?>>
                        <?php if ( $require_dl_exp ) : ?><em style="color:red;">Required</em><?php endif; ?>
                    </p>

                    <p><strong>Birthday:</strong><br>
                        <input type="date" name="afreg_additional_4625" id="afreg_additional_4625"
                               max="<?php echo date('Y-m-d', strtotime('-18 years')); ?>" class="aaa-v4-input" style="width:100%;"
                               value="<?php echo esc_attr( $parsed_data['afreg_additional_4625'] ?? $saved_birthday ); ?>"
                               <?php echo $require_birthday ? 'required' : ''; ?>>
                        <?php if ( $require_birthday ) : ?><em style="color:red;">Required</em><?php endif; ?>
                    </p>
                </div>
            </div>

            <!-- ─────────────────────────────────────────────────── -->
            <!-- BOTTOM ROW: plugin’s upload fields (blue boxes + green buttons) -->
            <!-- ─────────────────────────────────────────────────── -->
            <div class="aaa-v4-section aaa-v4-section-uploads" style="display: flex; gap: 1rem; margin-bottom: 2rem;">

                <!-- Medical Record -->
                <div class="dropzone aaa-v4-upload" data-field="afreg_additional_4630" style="flex:1; padding:1rem; border:1px solid #0066cc; border-radius:4px; text-align:center;">
                    <?php
                    $saved_med_filename = $customer_id ? get_user_meta( $customer_id, 'afreg_additional_4630', true ) : '';
                    $med_url = $saved_med_filename ? trailingslashit( $uploads['baseurl'] ) . 'addify_registration_uploads/' . $saved_med_filename : '';
                    ?>
                    <img id="preview_medrec_img" class="preview" src="<?php echo esc_url( $med_url ); ?>"
                         style="max-width:100%; margin-bottom:0.5rem; <?php echo $med_url ? '' : 'display:none;'; ?>" />
                    <?php if ( ! $med_url ) : ?><em>No Medical Record Uploaded</em><br><?php endif; ?>
                    <p><strong>Upload Medical Record:</strong><br>
                        <input type="file" name="afreg_additional_4630" id="afreg_additional_4630" class="aaa-v4-input" accept="image/*,application/pdf">
                    </p>
                </div>

                <!-- Selfie -->
                <div class="dropzone aaa-v4-upload" data-field="afreg_additional_4627" style="flex:1; padding:1rem; border:1px solid #0066cc; border-radius:4px; text-align:center;">
                    <?php
                    $saved_selfie_filename = $customer_id ? get_user_meta( $customer_id, 'afreg_additional_4627', true ) : '';
                    $selfie_url = $saved_selfie_filename ? trailingslashit( $uploads['baseurl'] ) . 'addify_registration_uploads/' . $saved_selfie_filename : '';
                    ?>
                    <img id="preview_selfie_img" class="preview" src="<?php echo esc_url( $selfie_url ); ?>"
                         style="max-width:100%; margin-bottom:0.5rem; <?php echo $selfie_url ? '' : 'display:none;'; ?>" />
                    <?php if ( ! $selfie_url ) : ?><em>No Selfie Uploaded</em><br><?php endif; ?>
                    <p><strong>Upload Selfie:</strong><br>
                        <input type="file" name="afreg_additional_4627" id="afreg_additional_4627" class="aaa-v4-input" accept="image/*">
                    </p>
                </div>

                <!-- DL ID -->
                <div class="dropzone aaa-v4-upload" data-field="afreg_additional_4626" style="flex:1; padding:1rem; border:1px solid #0066cc; border-radius:4px; text-align:center;">
                    <?php
                    $saved_id_filename = $customer_id ? get_user_meta( $customer_id, 'afreg_additional_4626', true ) : '';
                    $id_url = $saved_id_filename ? trailingslashit( $uploads['baseurl'] ) . 'addify_registration_uploads/' . $saved_id_filename : '';
                    ?>
                    <img id="preview_id_img" class="preview" src="<?php echo esc_url( $id_url ); ?>"
                         style="max-width:100%; margin-bottom:0.5rem; <?php echo $id_url ? '' : 'display:none;'; ?>" />
                    <?php if ( ! $id_url ) : ?><em>No DL ID Uploaded</em><br><?php endif; ?>
                    <p><strong>Upload DL ID Image:</strong><br>
                        <input type="file" name="afreg_additional_4626" id="afreg_additional_4626" class="aaa-v4-input" accept="image/*">
                    </p>
                </div>

            </div>

        <?php
        // bottom half is rendered by Preview_Builder_Bottom
    }
}
?>
