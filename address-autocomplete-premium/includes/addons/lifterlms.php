<?php
class WPSunshine_Address_Autocomplete_LifterLMS {

    public function __construct() {

        add_filter( 'wps_aa_addons', array( $this, 'register' ), 99 );

        if ( !class_exists( 'LifterLMS' ) ) {
            return;
        }

        add_action( 'wps_aa_instances', array( $this, 'add_instances' ) );

    }

    public function register( $addons ) {
        $addons['lifterlms_purchase'] = __( 'LifterLMS Purchase', 'address-autocomplete-anything' );
        return $addons;
    }

    public function add_instances( $instances ) {

        $addons = get_option( 'wps_aa_addons' );
        if ( empty( $addons ) ) {
            return $instances;
        }

        if ( array_key_exists( 'lifterlms_purchase', $instances ) ) {
            return $instances;
        }

        $build_instances = false;

        if ( in_array( 'lifterlms_purchase', $addons ) ) {
            $build_instances = true;
        }

        if ( $build_instances ) {

            $fields = array();
            // Build instance data for Billing
            $fields[] = array(
                'selector' => '#llms_billing_country',
                'data' => '{country:short_name}'
            );
            $fields[] = array(
                'selector' => '#llms_billing_address_1',
                'data' => '{address1:long_name}'
            );
            $fields[] = array(
                'selector' => '#llms_billing_address_2',
                'data' => '{address2:long_name}'
            );
            $fields[] = array(
                'selector' => '#llms_billing_city',
                'data' => '{locality:long_name}'
            );
            $fields[] = array(
                'selector' => '#llms_billing_state',
                'data' => '{administrative_area_level_1:short_name}'
            );
            $fields[] = array(
                'selector' => '#llms_billing_zip',
                'data' => '{postal_code:long_name}'
            );

            $instances[ 'lifterlms_purchase' ] = array(
                'label' => 'LifterLMS Purchase',
                'init' => '#llms_billing_address_1',
                'page' => llms_get_page_id( 'checkout' ),
                //'allowed_countries' => array_keys( get_lifterlms_countries() ),
                'fields' => $fields
            );

        }

        return $instances;

    }

}

$wps_aa_lifterlms = new WPSunshine_Address_Autocomplete_LifterLMS();
