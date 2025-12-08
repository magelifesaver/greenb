( function( $, settings ) {
    'use strict';

    $( document ).ready( function() {

        const toggleFCSettings = ( $checkinput, $properties, isValue ) => {

            if ( typeof isValue === 'undefined' ) {
                isValue = true;
            }

            if ( $checkinput.length ) {
                let checkedCount = 0;
                for( let $checkbox of $checkinput ) {
                    if ( $checkbox.length ) {
                        checkedCount += $checkbox.filter( ':checked' ).length;
                    } else {
                        checkedCount += ( $checkbox.checked ? 1 : 0 );
                    }
                }
                if ( checkedCount === $checkinput.length ) {
                    isValue ? $properties.show() : $properties.hide();
                } else {
                    isValue ? $properties.hide() : $properties.show();
                }

            } else {

                $properties.hide();
                
            }
        };

        const setupFCSettingToggle = ( settingId, toggleId, isValue ) => {

            let $setting = $( `#wc_fast_cart_settings[${settingId}]` );
            if ( $setting.length === 0 ) {
                $setting = $( `input[name="wc_fast_cart_settings[${settingId}]"]` );
            }
            let $toggles = $( `input[name="wc_fast_cart_settings[${toggleId}]"]` ).closest( 'tr' );

            $setting.on( 'change', () => {
                toggleFCSettings( [ $setting ], $toggles, isValue );
            } );

            toggleFCSettings( [ $setting ], $toggles, isValue );
        };

        const setupFCSettingTable = ( settingId, toggleId, isValue ) => {

            if ( typeof isValue === 'undefined' ) {
                isValue = true;
            }

            let $setting = $( `#wc_fast_cart_settings[${settingId}]` );
            if ( $setting.length === 0 ) {
                $setting = $( `input[name="wc_fast_cart_settings[${settingId}]"]` );
            }
            let $table = $( `input[name="wc_fast_cart_settings[${toggleId}]"]` ).closest( 'table' );
            let $h2 = $table.prev( 'h2' );

            $setting.on( 'change', () => {
                toggleFCSettings( $setting, $table, isValue );
                if ( $setting[0].checked === isValue ) {
                    $h2.show();
                } else {
                    $h2.hide();
                }
            } );

            toggleFCSettings( $setting, $table, isValue );
            if ( $setting[0].checked === isValue ) {
                $h2.show();
            } else {
                $h2.hide();
            }
        };

        const setupFCSettingSection = ( settingIds, toggleId, isValue ) => {

            if ( typeof isValue === 'undefined' ) {
                isValue = true;
            }

            if ( typeof settingIds === 'string' ) {
                settingIds = [ settingIds ];
            }

            let $tr = $( `input[name="wc_fast_cart_settings[${toggleId}]"]` ).closest( 'tr' );
            let $settings = [];
            for ( let settingAttr of settingIds ) {
                let $setting = $( `#wc_fast_cart_settings[${settingAttr}]` );
                if ( $setting.length === 0 ) {
                    $setting = $( `input[name="wc_fast_cart_settings[${settingAttr}]"]` );
                }
                $settings.push( $setting );

                $setting.on( 'change', () => {
                    toggleFCSettings( $settings, $tr, isValue );
                } );
                
            }

            toggleFCSettings( $settings, $tr, isValue );
            
        };

        
        //setupFCSettingToggle( 'enable_cart_button', 'cart_icon_position' );
        setupFCSettingToggle( 'enable_fast_checkout', 'replace_checkout_page' );
        setupFCSettingSection( 'enable_fast_checkout', 'enable_direct_checkout' );

        setupFCSettingSection( 'enable_fast_checkout', 'enable_autocomplete' );
        setupFCSettingSection( ['enable_fast_checkout','enable_autocomplete'], 'maps_api' );
        //setupFCSettingTable( 'enable_cart_button', 'cart_icon_fill' );
        
        setupFCSettingSection( 'enable_direct_checkout', 'replace_cart_page', false );

        setupFCSettingToggle( 'fast_cart_mode', 'cart_show_headings', 'modal' );

        let apiKeyField = document.getElementById( 'wc_fast_cart_settings[maps_api]' );
        if ( apiKeyField ) {
            let statusIcon = document.createElement( 'span' );
            statusIcon.style.fontSize = '1em';
            statusIcon.style.display = 'inline-flex';
            statusIcon.style.height = '28px';
            statusIcon.style.alignItems = 'center';
            statusIcon.style.marginLeft = '0.6em';
            apiKeyField.after( statusIcon );
            apiKeyField.addEventListener( 'change', window.FastCartAdmin.testApiKey );
            apiKeyField.statusIcon = statusIcon;

            window.FastCartAdmin.testApiKey( { target: apiKeyField } );
        }


        const toggleCartText = function() {
            let cartStyle = $('select[name="wc_fast_cart_settings[cart_button_style]"]').val();
            if (cartStyle !== 'icon') {
                $('input[name="wc_fast_cart_settings[cart_button_text]"]').closest('tr').show();
            } else {
                $('input[name="wc_fast_cart_settings[cart_button_text]"]').closest('tr').hide();
            }
        }
    
        toggleCartText();

        $('select[name="wc_fast_cart_settings[cart_button_style]"]').on( 'change', function() {
            toggleCartText();
        });
    } );

    window.FastCartAdmin = ( ( settings ) => {

        let statusIcon, iframeTest;

        const strings = settings.strings;

        const testApiKey = ( e ) => {

            if ( ! e.target ) {
                return;
            }
            if ( ! e.target.statusIcon ) {
                return;
            }

            statusIcon = e.target.statusIcon;

            if ( ! statusIcon ) {
                return;
            }

            if ( iframeTest ) {
                iframeTest.remove();
            }

            if ( e.target.value.length === 0 ) {
                mapApiResponse( 'empty' );
                return;
            }

            iframeTest = document.createElement( 'iframe' );
            iframeTest.src = settings.keyTestEndpoint + '?key=' + e.target.value + '&_wpnonce=' + settings.adminNonce;
            iframeTest.style.display = 'none';

            document.body.append( iframeTest );

        };

        const mapApiResponse = ( status ) => {

            const statusField = document.getElementById( 'wc_fast_cart_settings[maps_api_status]' );

            let errorMessage = statusIcon.parentNode.querySelector( '.error-response' );
            if ( ! errorMessage ) {
                errorMessage = document.createElement( 'p' );
                errorMessage.classList.add( 'error-response' );
                errorMessage.style.display = 'none';
                errorMessage.style.color = 'red';
                errorMessage.setAttribute( 'aria-hidden', 'true' );
                statusIcon.parentNode.append( errorMessage );
            }

            if ( status === 'success' ) {
                statusIcon.innerText = strings.validAPIKey;
                statusIcon.style.color = 'green';
                errorMessage.style.display = 'none';
                errorMessage.setAttribute( 'aria-hidden', 'true' );
            }
            if ( status === 'failure' ) {
                statusIcon.innerText = strings.invalidAPIKey;
                statusIcon.style.color = 'red';
                errorMessage.style.display = '';
                //errorMessage.innerText = ;
                errorMessage.setAttribute( 'aria-hidden', 'false' );
            }
            if ( status === 'empty' ) {
                statusIcon.innerText = '';
                errorMessage.style.display = 'none';
                errorMessage.innerText = ''; //strings.emptyAPIKey;
                errorMessage.setAttribute( 'aria-hidden', 'false' );
            }

            if ( statusField ) {
                statusField.value = status;
            }

        };

        return {
            testApiKey,
            mapApiResponse
        }
    } )( wc_fast_cart_admin_settings );
} )( jQuery, wc_fast_cart_admin_settings );