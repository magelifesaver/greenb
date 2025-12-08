<?php
add_action( 'wps_aa_options_before', 'wps_aa_options_after_upgrade', 10, 2 );
function wps_aa_options_after_upgrade( $options, $tab ) {

    if ( $tab == 'settings' ) {
        ?>
        <div id="wps-aa-upgrade">
            <h3>Get More Features!</h3>
            <p>Make implementing address autocomplete on your site more feature-rich and easier:</p>
            <ul>
                <li>Unlimited form instances</li>
                <li>More address data fields</li>
                <li>One-click integration for your favorite WordPress plugins such as WooCommerce, Gravity Forms, and many more coming soon!</li>
            </ul>
            <p><a href="https://wpsunshine.com/plugins/address-autocomplete/?utm_source=plugin&utm_medium=banner&utm_content=upgrade&utm_campaign=aa_upgrade" target="_blank" class="button-primary">Upgrade today!</a></div>
        <?php
    }

}

add_action( 'admin_notices', 'wps_aa_review_request' );
function wps_aa_review_request() {
    $review_status = get_option( 'wps_aa_review' );
    if ( $review_status == 'dismissed' ) {
        return;
    }
    $install_time = get_option( 'wps_aa_install_time' );
    if ( empty( $install_time ) ) {
        update_option( 'wps_aa_install_time', current_time( 'timestamp' ) );
        return;
    }
    if ( ( current_time( 'timestamp' ) - $install_time ) < DAY_IN_SECONDS * 15 ) {
        return;
    }
    ?>
        <div class="notice notice-info is-dismissable">
            <p>You having been using WP Sunshine Address Autocomplete Anything for a bit and that's awesome! Could you please do a big favor and give it a review on WordPress?  Reviews from users like you really help our plugins to grow and continue to improve.</p>
            <p>- Derek, WP Sunshine Lead Developer</p>
            <p><a href="https://wordpress.org/support/view/plugin-reviews/confetti?filter=5#postform" target="_blank" class="button-primary wps-aa-review-dismiss-button">Sure thing!</a> &nbsp; <a href="#" class="button wps-aa-review-dismiss-button">No thanks</a>
        </div>
        <script>
            jQuery( document ).on( 'click', '.wps-aa-review-dismiss-button', function() {
                jQuery.ajax({
                    type: 'POST',
                    url: ajaxurl,
                    data: {
                        action: 'wps_aa_dismiss_review',
                    }
                });
            });
        </script>
    <?php
}

add_action( 'wp_ajax_wps_aa_dismiss_review', 'wps_aa_review_dismiss' );
function wps_aa_review_dismiss() {
    update_option( 'wps_aa_review', 'dismissed' );
    wp_die();
}
