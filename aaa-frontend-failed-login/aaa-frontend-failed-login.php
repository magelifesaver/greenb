<?php
/**
 * Plugin Name: AAA Frontend Failed Login Controller
 * Description: Prevents blank login submissions, handles frontend failed logins safely, and provides a settings panel with dedicated logging and toggles for all features.
 * Version: 2.0.1
 * Author: Webmaster Workflow
 * Text Domain: aaa-frontend-failed-login
 *
 * File: /wp-content/plugins/aaa-frontend-failed-login/aaa-frontend-failed-login.php
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/*--------------------------------------------------------------
# GLOBAL DEFINITIONS
--------------------------------------------------------------*/
define( 'AAA_FAILED_LOGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'AAA_FAILED_LOGIN_LOG', AAA_FAILED_LOGIN_DIR . 'aaa-frontend-login.log' );
define( 'AAA_FAILED_LOGIN_OPTIONS_KEY', 'aaa_failed_login_settings' );

/*--------------------------------------------------------------
# HELPER: SAFE LOGGING
--------------------------------------------------------------*/
function aaa_failed_login_log( $message ) {
    $opts = get_option( AAA_FAILED_LOGIN_OPTIONS_KEY, [] );
    if ( empty( $opts['enable_plugin'] ) || empty( $opts['enable_logging'] ) ) return;
    $timestamp = date_i18n( 'Y-m-d H:i:s' );
    error_log( "[$timestamp] $message\n", 3, AAA_FAILED_LOGIN_LOG );
}

/*--------------------------------------------------------------
# DEFAULT SETTINGS
--------------------------------------------------------------*/
function aaa_failed_login_defaults() {
    return [
        'enable_plugin'       => 1,
        'enable_js_validation'=> 1,
        'enable_server_check' => 1,
        'enable_redirect'     => 1,
        'enable_logging'      => 1,
    ];
}
register_activation_hook( __FILE__, function() {
    if ( ! get_option( AAA_FAILED_LOGIN_OPTIONS_KEY ) )
        update_option( AAA_FAILED_LOGIN_OPTIONS_KEY, aaa_failed_login_defaults() );
});

/*--------------------------------------------------------------
# SETTINGS PAGE
--------------------------------------------------------------*/
add_action( 'admin_menu', function() {
    add_options_page(
        'AAA Frontend Login',
        'AAA Frontend Login',
        'manage_options',
        'aaa-frontend-login',
        'aaa_failed_login_settings_page'
    );
});

function aaa_failed_login_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) return;
    $opts = get_option( AAA_FAILED_LOGIN_OPTIONS_KEY, aaa_failed_login_defaults() );

    // Save settings
    if ( isset( $_POST['aaa_failed_login_save'] ) && check_admin_referer( 'aaa_failed_login_save' ) ) {
        foreach ( $opts as $key => $val ) {
            $opts[$key] = isset( $_POST[$key] ) ? 1 : 0;
        }
        update_option( AAA_FAILED_LOGIN_OPTIONS_KEY, $opts );
        echo '<div class="updated"><p><strong>Settings saved.</strong></p></div>';
    }

    // Clear log
    if ( isset( $_POST['aaa_failed_login_clear_log'] ) && file_exists( AAA_FAILED_LOGIN_LOG ) ) {
        unlink( AAA_FAILED_LOGIN_LOG );
        echo '<div class="updated"><p><strong>Log file cleared.</strong></p></div>';
    }

    $log_content = file_exists( AAA_FAILED_LOGIN_LOG ) ? esc_textarea( file_get_contents( AAA_FAILED_LOGIN_LOG ) ) : 'No log entries yet.';
    ?>
    <div class="wrap">
        <h1>AAA Frontend Login Controller</h1>
        <form method="post">
            <?php wp_nonce_field( 'aaa_failed_login_save' ); ?>
            <table class="form-table" role="presentation">
                <tr><th>Main Plugin Toggle</th>
                    <td><input type="checkbox" name="enable_plugin" <?php checked( $opts['enable_plugin'], 1 ); ?>> Enable plugin</td></tr>
                <tr><th>Client-Side JS Validation</th>
                    <td><input type="checkbox" name="enable_js_validation" <?php checked( $opts['enable_js_validation'], 1 ); ?>> Prevent blank form submissions</td></tr>
                <tr><th>Server-Side Validation</th>
                    <td><input type="checkbox" name="enable_server_check" <?php checked( $opts['enable_server_check'], 1 ); ?>> Block empty credentials on server</td></tr>
                <tr><th>Safe Redirect on Failure</th>
                    <td><input type="checkbox" name="enable_redirect" <?php checked( $opts['enable_redirect'], 1 ); ?>> Redirect failed logins to /my-account/</td></tr>
                <tr><th>Enable Logging</th>
                    <td><input type="checkbox" name="enable_logging" <?php checked( $opts['enable_logging'], 1 ); ?>> Record events in dedicated log</td></tr>
            </table>
            <p><button type="submit" name="aaa_failed_login_save" class="button button-primary">Save Settings</button></p>
        </form>

        <h2>Event Log</h2>
        <form method="post">
            <textarea readonly rows="15" style="width:100%;font-family:monospace;"><?php echo $log_content; ?></textarea>
            <p><button type="submit" name="aaa_failed_login_clear_log" class="button">Clear Log</button></p>
        </form>
    </div>
    <?php
}

/*--------------------------------------------------------------
# ADD SETTINGS LINK TO PLUGIN LIST
--------------------------------------------------------------*/
add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), function( $links ) {
    $settings_link = '<a href="' . admin_url( 'options-general.php?page=aaa-frontend-login' ) . '">Settings</a>';
    array_unshift( $links, $settings_link );
    return $links;
});

/*--------------------------------------------------------------
# MAIN FUNCTIONALITY (conditionally loaded)
--------------------------------------------------------------*/
add_action( 'plugins_loaded', function() {
    $opts = get_option( AAA_FAILED_LOGIN_OPTIONS_KEY, aaa_failed_login_defaults() );
    if ( empty( $opts['enable_plugin'] ) ) return;

    /* Server-side validation */
    if ( ! empty( $opts['enable_server_check'] ) ) {
        add_filter( 'authenticate', function( $user, $username, $password ) {
            if ( empty( $username ) || empty( $password ) ) {
                aaa_failed_login_log( 'Empty username or password detected.' );
                remove_action( 'authenticate', 'wp_authenticate_username_password', 20 );
                return new WP_Error( 'aaa_empty_fields', __( 'Please enter both username (or email) and password.', 'aaa-frontend-failed-login' ) );
            }
            return $user;
        }, 10, 3 );
    }

    /* Redirect failed logins */
    if ( ! empty( $opts['enable_redirect'] ) ) {
        add_action( 'wp_login_failed', function( $username ) {
            $current = home_url( add_query_arg( null, null ) );
            if ( strpos( $current, '/my-account/' ) === false ) {
                $target = add_query_arg( 'login', 'failed', home_url( '/my-account/' ) );
                aaa_failed_login_log( "Login failed for {$username}. Redirecting to {$target}" );
                wp_safe_redirect( $target );
                exit;
            }
        });
    }

    /* JS Validation & inline errors */
    if ( ! empty( $opts['enable_js_validation'] ) ) {
        add_action( 'wp_footer', function() {
            if ( ! is_account_page() ) return;
            ?>
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                function showError(form,msg){
                    let c=form.querySelector('.aaa-login-error');
                    if(!c){c=document.createElement('div');c.className='aaa-login-error';form.prepend(c);}
                    c.textContent=msg;c.style.display='block';
                }
                function clearError(form){const c=form.querySelector('.aaa-login-error');if(c)c.style.display='none';}
                function attach(f){if(f.dataset.aaaAttached)return;f.dataset.aaaAttached=1;
                    f.addEventListener('submit',function(e){
                        clearError(f);
                        const u=f.querySelector('input[name="username"]');
                        const p=f.querySelector('input[name="password"]');
                        if(!u||!u.value.trim()||!p||!p.value.trim()){e.preventDefault();showError(f,'Please enter both username and password.');}
                    });
                }
                document.querySelectorAll('form.login,form.woocommerce-form-login').forEach(attach);
                const obs=new MutationObserver(m=>m.forEach(x=>x.addedNodes.forEach(n=>{if(n.querySelectorAll)n.querySelectorAll('form.login,form.woocommerce-form-login').forEach(attach);})));
                obs.observe(document.body,{childList:true,subtree:true});
                const p=new URLSearchParams(window.location.search);
                if(p.get('login')==='failed'){document.querySelectorAll('form.login,form.woocommerce-form-login').forEach(f=>showError(f,'Incorrect username or password.'));}
            });
            </script>
            <style>
            .aaa-login-error{display:none;background:#ffe5e5;border:1px solid #d00;color:#d00;border-radius:6px;padding:10px 14px;margin-bottom:12px;text-align:center;font-size:15px;font-weight:500;position:relative;z-index:5;}
            .woocommerce-notices-wrapper,.woocommerce-error,.woocommerce-message,.woocommerce-info{position:relative!important;z-index:5!important;margin-top:2em!important;}
            </style>
            <?php
        });
    }
});
