<?php
/**
 * File: wp-content/plugins/aaa-fluentcrm-unique-validation/aaa-fluentcrm-unique-validation.php
 * Plugin Name: AAA FluentCRM - Unique Validation (Live AJAX) (XHV98-FLU)
 * Description: Live + step-triggered uniqueness validation for Email, Phone, and Driver’s License in Fluent Forms (multi-step safe) with verbose logging.
 * Version:     1.2.0
 * Author:      Webmaster Workflow
 * Text Domain: aaa-fluentcrm-unique-validation
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* -------------------------------------------------------------------------
   DEBUG TOGGLE (logs to error_log when true)
---------------------------------------------------------------------------*/
if ( ! defined( 'AAA_FCRM_UNIQUE_VALIDATION_DEBUG' ) ) {
    define( 'AAA_FCRM_UNIQUE_VALIDATION_DEBUG', true );
}

/* -------------------------------------------------------------------------
   OPTIONS
---------------------------------------------------------------------------*/
define( 'AAA_FCRM_UV_OPTIONS_KEY', 'aaa_fcrm_unique_validation_opts' );

function aaa_fcrm_uv_get_opts() {
    // Prefilled from your export (Form ID 5)
    $defaults = array(
        'form_id'          => '5',
        'email_field'      => 'email',
        'phone_field'      => 'registration_phone',
        'phone_meta_key'   => 'billing_phone',
        'license_field'    => 'reg_id_number',
        'license_meta_key' => 'afreg_additional_4532',
    );
    $saved = get_option( AAA_FCRM_UV_OPTIONS_KEY, array() );
    return wp_parse_args( is_array( $saved ) ? $saved : array(), $defaults );
}

/* -------------------------------------------------------------------------
   ADMIN MENU (bottom of FluentCRM)
---------------------------------------------------------------------------*/
add_action( 'admin_menu', function () {
    add_submenu_page(
        'fluentcrm-admin',
        'Unique Validation',
        'Unique Validation',
        'manage_options',
        'aaa-fluentcrm-unique-validation',
        'aaa_fcrm_uv_render_admin_page'
    );
}, 999 );

function aaa_fcrm_uv_render_admin_page() {
    if ( ! current_user_can( 'manage_options' ) ) return;
    $opts = aaa_fcrm_uv_get_opts();

    if ( isset($_POST['aaa_fcrm_uv_save']) && check_admin_referer('aaa_fcrm_uv_save_action','aaa_fcrm_uv_nonce') ) {
        foreach ( array('form_id','email_field','phone_field','phone_meta_key','license_field','license_meta_key') as $k ) {
            $opts[$k] = sanitize_text_field( $_POST[$k] ?? '' );
        }
        update_option( AAA_FCRM_UV_OPTIONS_KEY, $opts );
        echo '<div class="notice notice-success"><p>Settings saved.</p></div>';
        if ( AAA_FCRM_UNIQUE_VALIDATION_DEBUG ) error_log('[AAA_FCRM_UV] Settings saved: '. wp_json_encode($opts));
    }
    ?>
    <div class="wrap">
        <h1>AAA FluentCRM — Unique Validation (Live AJAX)</h1>
        <form method="post">
            <?php wp_nonce_field('aaa_fcrm_uv_save_action','aaa_fcrm_uv_nonce'); ?>
            <table class="form-table" role="presentation">
                <tr><th><label for="form_id">Fluent Form ID</label></th>
                    <td><input type="number" id="form_id" name="form_id" class="regular-text" value="<?php echo esc_attr($opts['form_id']); ?>" required></td></tr>
                <tr><th>Email field key</th><td><input name="email_field" class="regular-text" value="<?php echo esc_attr($opts['email_field']); ?>"></td></tr>
                <tr><th>Phone field key</th><td><input name="phone_field" class="regular-text" value="<?php echo esc_attr($opts['phone_field']); ?>"></td></tr>
                <tr><th>Phone usermeta key</th><td><input name="phone_meta_key" class="regular-text" value="<?php echo esc_attr($opts['phone_meta_key']); ?>"></td></tr>
                <tr><th>DL field key</th><td><input name="license_field" class="regular-text" value="<?php echo esc_attr($opts['license_field']); ?>"></td></tr>
                <tr><th>DL usermeta key</th><td><input name="license_meta_key" class="regular-text" value="<?php echo esc_attr($opts['license_meta_key']); ?>"></td></tr>
            </table>
            <p class="submit"><button class="button button-primary" name="aaa_fcrm_uv_save">Save Settings</button></p>
        </form>
        <p style="color:#666;margin-top:6px">Interception classes used: <code>.reg_break_two</code> (email+phone) and <code>.reg_break_three</code> (driver’s license).</p>
    </div>
    <?php
}

/* -------------------------------------------------------------------------
   CORE VALIDATION (server-side)
---------------------------------------------------------------------------*/
function aaa_fcrm_uv_check_values( $vals, $opts ) {
    global $wpdb;
    $errors = array();

    // Email → wp_users
    if ( ! empty( $vals['email'] ) ) {
        $email = sanitize_email( $vals['email'] );
        if ( $email && email_exists( $email ) ) {
            $errors[ $opts['email_field'] ] = __( 'This email address is already registered.', 'aaa-fluentcrm-unique-validation' );
            if ( AAA_FCRM_UNIQUE_VALIDATION_DEBUG ) error_log( "[AAA_FCRM_UV] Email duplicate: {$email}" );
        }
    }

    // Phone → usermeta
    if ( ! empty( $vals['phone'] ) ) {
        $phone = sanitize_text_field( $vals['phone'] );
        if ( $phone ) {
            $uid = $wpdb->get_var( $wpdb->prepare(
                "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value = %s LIMIT 1",
                $opts['phone_meta_key'], $phone
            ) );
            if ( $uid ) {
                $errors[ $opts['phone_field'] ] = __( 'This phone number is already in use.', 'aaa-fluentcrm-unique-validation' );
                if ( AAA_FCRM_UNIQUE_VALIDATION_DEBUG ) error_log( "[AAA_FCRM_UV] Phone duplicate ({$opts['phone_meta_key']}): {$phone}" );
            }
        }
    }

    // Driver’s License → usermeta
    if ( ! empty( $vals['license'] ) ) {
        $license = sanitize_text_field( $vals['license'] );
        if ( $license ) {
            $uid = $wpdb->get_var( $wpdb->prepare(
                "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value = %s LIMIT 1",
                $opts['license_meta_key'], $license
            ) );
            if ( $uid ) {
                $errors[ $opts['license_field'] ] = __( 'This driver’s license number is already registered.', 'aaa-fluentcrm-unique-validation' );
                if ( AAA_FCRM_UNIQUE_VALIDATION_DEBUG ) error_log( "[AAA_FCRM_UV] DL duplicate ({$opts['license_meta_key']}): {$license}" );
            }
        }
    }

    return $errors;
}

// Attach to Fluent Forms validation (fires on each step & final submit)
add_action( 'init', function () {
    $opts    = aaa_fcrm_uv_get_opts();
    $form_id = absint( $opts['form_id'] );
    if ( ! $form_id ) return;

    $hook = "fluentform/validation_errors_{$form_id}";
    add_filter( $hook, function ( $errors, $fields, $form ) use ( $opts ) {
        if ( AAA_FCRM_UNIQUE_VALIDATION_DEBUG ) {
            error_log('[AAA_FCRM_UV] Server validation firing. Fields snapshot: '. wp_json_encode( array_intersect_key( $fields, array_flip([$opts['email_field'],$opts['phone_field'],$opts['license_field']]) ) ) );
        }
        $vals  = array(
            'email'   => $fields[ $opts['email_field'] ]   ?? '',
            'phone'   => $fields[ $opts['phone_field'] ]   ?? '',
            'license' => $fields[ $opts['license_field'] ] ?? '',
        );
        $check = aaa_fcrm_uv_check_values( $vals, $opts );
        return array_merge( $errors, $check );
    }, 10, 3 );
});

/* -------------------------------------------------------------------------
   AJAX ENDPOINT (live checks) + verbose logging
---------------------------------------------------------------------------*/
add_action( 'wp_ajax_aaa_fcrm_uv_check', 'aaa_fcrm_uv_ajax_check' );
add_action( 'wp_ajax_nopriv_aaa_fcrm_uv_check', 'aaa_fcrm_uv_ajax_check' );

function aaa_fcrm_uv_ajax_check() {
    if ( AAA_FCRM_UNIQUE_VALIDATION_DEBUG ) error_log('[AAA_FCRM_UV][AJAX] Incoming: '. wp_json_encode($_POST) );

    if ( ! check_ajax_referer( 'aaa_fcrm_uv', 'nonce', false ) ) {
        if ( AAA_FCRM_UNIQUE_VALIDATION_DEBUG ) error_log('[AAA_FCRM_UV][AJAX] Bad nonce');
        wp_send_json_error( array( 'message' => 'Bad nonce' ), 403 );
    }

    $opts   = aaa_fcrm_uv_get_opts();
    $vals   = array(
        'email'   => $_POST['email']   ?? '',
        'phone'   => $_POST['phone']   ?? '',
        'license' => $_POST['license'] ?? '',
    );
    $errors = aaa_fcrm_uv_check_values( $vals, $opts );

    if ( AAA_FCRM_UNIQUE_VALIDATION_DEBUG ) error_log('[AAA_FCRM_UV][AJAX] Errors: '. wp_json_encode($errors) );
    wp_send_json_success( array( 'errors' => $errors ) );
}
/**
 * Replace {account_setup_link} in ANY outgoing email right before send.
 * Uses the first recipient as the user (typical for user-facing mails).
 */
add_filter('wp_mail', function ($args) {
    // Only act if the placeholder exists
    if (empty($args['message']) || strpos($args['message'], '{account_setup_link}') === false) {
        return $args;
    }

    // Determine the recipient email (first in array/string)
    $to = $args['to'];
    if (is_array($to)) {
        $to = reset($to);
    }
    $to = is_string($to) ? trim($to) : '';

    // Find the user by recipient email
    $user = $to ? get_user_by('email', $to) : false;
    if (!$user) {
        // Fallback: try to sniff an email from headers or leave the placeholder as-is
        return $args;
    }

    // Build the secure password-reset (account setup) URL
    $key = get_password_reset_key($user);
    if (is_wp_error($key)) {
        return $args; // don't break mail if WP can't make a key
    }
	$url = home_url('/my-account/?action=rp&key=' . rawurlencode($key) . '&login=' . rawurlencode($user->user_login));

    // Replace placeholder with the URL (safe in plain text or HTML)
    $args['message'] = str_replace('{account_setup_link}', esc_url($url), $args['message']);

    return $args;
}, 10, 1);
/**
 * Remove the WooCommerce registration section entirely from My Account page.
 */
add_action('woocommerce_register_form', function () {
    remove_action('woocommerce_register_form', 'woocommerce_register_form');
}, 0);
/* -------------------------------------------------------------------------
   FRONT-END: Localized data + Inline JS
---------------------------------------------------------------------------*/
add_action( 'wp_enqueue_scripts', function () {
    $opts = aaa_fcrm_uv_get_opts();
    // dummy handle to carry localization
    wp_register_script( 'aaa-fcrm-uv', false, array(), '1.2.0', true );
    wp_localize_script( 'aaa-fcrm-uv', 'AAA_FCRM_UV', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'aaa_fcrm_uv' ),
        'form_id'  => (string) $opts['form_id'],
        'fields'   => array(
            'email'   => $opts['email_field'],
            'phone'   => $opts['phone_field'],
            'license' => $opts['license_field'],
        ),
    ) );
    wp_enqueue_script( 'aaa-fcrm-uv' );
});

add_action( 'wp_print_footer_scripts', function () {
    static $printed = false; if ( $printed ) return; $printed = true;
    $opts   = aaa_fcrm_uv_get_opts();
    $formID = esc_js( $opts['form_id'] );
    ?>
<script>
(function($){
  // ===== Verbose logger =====
  function L(){ if(window.console){ var a=[].slice.call(arguments); a.unshift('[AAA_FCRM_UV]'); console.log.apply(console,a); } }

  var FIELDS = (window.AAA_FCRM_UV && AAA_FCRM_UV.fields) || {};
  var FORM_ID = (window.AAA_FCRM_UV && AAA_FCRM_UV.form_id) || '<?php echo $formID; ?>';
  var WRAP_SEL = '.fluentform_wrapper_' + FORM_ID;

  function sel(name){ return WRAP_SEL + ' [name="'+name+'"]'; }

  function showError($input, message){
    var $g = $input.closest('.ff-el-group');
    $g.addClass('ff-el-is-error');
    var $e = $g.find('.error-text');
    if(!$e.length){ $e = $('<div class="error-text" />').appendTo($g.find('.ff-el-input--content')); }
    $e.text(message);
  }
  function clearError($input){
    var $g = $input.closest('.ff-el-group');
    $g.removeClass('ff-el-is-error');
    $g.find('.error-text').remove();
  }
  function clearAll(){
    [FIELDS.email, FIELDS.phone, FIELDS.license].forEach(function(n){
      if(!n) return;
      clearError($(sel(n)));
    });
  }
  function payload(){
    var $w = $(WRAP_SEL);
    return {
      action: 'aaa_fcrm_uv_check',
      nonce: window.AAA_FCRM_UV && AAA_FCRM_UV.nonce,
      email:   $w.find(sel(FIELDS.email).split(' ').pop()).val()   || '',
      phone:   $w.find(sel(FIELDS.phone).split(' ').pop()).val()   || '',
      license: $w.find(sel(FIELDS.license).split(' ').pop()).val() || ''
    };
  }

  function applyErrors(errs){
    clearAll();
    Object.keys(errs||{}).forEach(function(name){
      var $i = $(WRAP_SEL + ' [name="'+name+'"]');
      if($i.length){ showError($i, errs[name]); }
    });
  }

  function validate(cb){
    var data = payload();
    L('validate() start', data);
    $.post(AAA_FCRM_UV.ajax_url, data)
      .done(function(res){
        L('AJAX success', res);
        if(!res || !res.success){ cb(false); return; }
        var errs = res.data && res.data.errors || {};
        applyErrors(errs);
        cb(Object.keys(errs).length === 0);
      })
      .fail(function(xhr, status, err){
        L('AJAX fail', status, err);
        cb(false);
      });
  }

  // Live blur/change checks (non-blocking)
  $(document).on('blur change', sel(FIELDS.email),   function(){ validate(function(){}); });
  $(document).on('blur change', sel(FIELDS.phone),   function(){ validate(function(){}); });
  $(document).on('blur change', sel(FIELDS.license), function(){ validate(function(){}); });

// ===== Next button interception (capture phase) =====
function interceptNext(selector) {
  // Use raw addEventListener with capture=true so we beat Fluent's own handler
  document.addEventListener('click', function (e) {
    const btn = e.target.closest(selector);
    if (!btn) return;
    if (window.AAA_FCRM_UV_BYPASS) { L('Bypass ON → letting Fluent advance'); return; }

    L('CAPTURE PHASE Next clicked:', selector);
    e.preventDefault();
    e.stopPropagation();
    e.stopImmediatePropagation();

    const $btn = $(btn).prop('disabled', true).addClass('aaa-uv-wait');

    validate(function (ok) {
      L('validate() complete for Next (capture):', ok);
      $btn.prop('disabled', false).removeClass('aaa-uv-wait');
      if (ok) {
        window.AAA_FCRM_UV_BYPASS = true;
        try {
          if (window.FluentFormApp && typeof FluentFormApp.trigger === 'function') {
            L('Triggering FluentFormApp.nextPage');
            FluentFormApp.trigger('nextPage', { formId: FORM_ID });
          } else {
            const n = $btn.closest('form').find('.step-nav .ff-btn-next').get(0);
            if (n) { L('Fallback next click'); n.click(); }
          }
        } finally {
          setTimeout(function(){ window.AAA_FCRM_UV_BYPASS = false; }, 400);
        }
      } else {
        L('Validation failed → stay on current page');
      }
    });
  }, true); // <-- capture phase
}

// Register capture-phase listeners for your steps
interceptNext('.reg_break_two .ff-btn-next');    // Email + Phone
interceptNext('.reg_break_three .ff-btn-next');  // Driver’s License

  // Also sanity-check just before fluent's internal page change hook
  window.addEventListener('fluentform_before_page_change', function(){
    L('fluentform_before_page_change event fired (FYI)');
  });

})(jQuery);
</script>
    <?php
});
