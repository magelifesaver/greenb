<?php
class WPSunshine_Address_Autocomplete_PaidMembershipsPro {

    public function __construct() {

        add_filter( 'wps_aa_addons', array( $this, 'register' ), 99 );

        if ( !defined( 'PMPRO_VERSION' ) ) {
            return;
        }

        add_action( 'wps_aa_instances', array( $this, 'add_instances' ) );

    }

    public function register( $addons ) {
        $addons['paidmembershipspro'] = __( 'Paid Memberships Pro', 'address-autocomplete-anything' );
        return $addons;
    }

    public function add_instances( $instances ) {
        global $pmpro_countries;

        $addons = get_option( 'wps_aa_addons' );
        if ( empty( $addons ) ) {
            return $instances;
        }

        $build_instances = false;

        if ( in_array( 'paidmembershipspro', $addons ) ) {
            $build_instances = true;
        }

        if ( array_key_exists( 'paidmembershipspro', $instances ) ) {
            return $instances;
        }

        if ( $build_instances ) {

            $fields = array();
            // Build instance data for Billing
            $fields[] = array(
                'selector' => '#bcountry',
                'data' => '{country:short_name}'
            );
            $fields[] = array(
                'selector' => '#baddress1',
                'data' => '{address1:long_name}'
            );
            $fields[] = array(
                'selector' => '#baddress2',
                'data' => '{address2:long_name}'
            );
            $fields[] = array(
                'selector' => '#bcity',
                'data' => '{locality:long_name}'
            );
            $fields[] = array(
                'selector' => '#bstate',
                'data' => '{administrative_area_level_1:short_name}'
            );
            $fields[] = array(
                'selector' => '#bzipcode',
                'data' => '{postal_code:long_name}'
            );

            $instances[ 'paidmembershipspro' ] = array(
                'label' => 'Paid Memberships Pro',
                'init' => '#baddress1',
                'page' => intval( get_option( "pmpro_checkout_page_id") ),
                'allowed_countries' => ( count( $pmpro_countries ) <= 5 ) ? array_keys( $pmpro_countries ) : '',
                'fields' => $fields
            );

        }

        return $instances;

    }

}

$wps_aa_lifterlms = new WPSunshine_Address_Autocomplete_PaidMembershipsPro();
