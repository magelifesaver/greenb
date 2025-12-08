<?php
// File: /aaa-openia-order-creation-v4/includes/class-aaa-v4-settings.php

if ( ! defined( 'ABSPATH' ) ) exit;

class AAA_V4_Settings {

    const OPTION_KEY = 'aaa_v4_order_creator_settings';

    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'add_settings_page' ] );
        add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
    }

    public static function add_settings_page() {
        add_submenu_page(
            'aaa-openia-order-creation-v4',  // ✅ plugin's top-level slug
            'Order Creator Settings',
            'Settings',
            'manage_woocommerce',
            'aaa-v4-order-settings',
            [ __CLASS__, 'render_settings_page' ]
        );
    }

    public static function register_settings() {
        register_setting( 'aaa_v4_settings_group', self::OPTION_KEY );

        add_settings_section(
            'aaa_v4_main',
            'Order Creator Configuration',
            null,
            'aaa-v4-order-settings'
        );

        add_settings_field(
            'brand_meta_key',
            'Brand Meta Field Key',
            [ __CLASS__, 'brand_meta_field' ],
            'aaa-v4-order-settings',
            'aaa_v4_main'
        );

        add_settings_field(
            'default_order_status',
            'Default Order Status',
            [ __CLASS__, 'order_status_field' ],
            'aaa-v4-order-settings',
            'aaa_v4_main'
        );

        add_settings_field(
            'upload_parser_csv',
            'Upload Parsing Table CSV',
            [ __CLASS__, 'upload_csv_field' ],
            'aaa-v4-order-settings',
            'aaa_v4_main'
        );
        add_settings_field(
            'product_match_table',
            'Product Match Table',
            [ __CLASS__, 'product_match_table_field' ],
            'aaa-v4-order-settings',
            'aaa_v4_main'
        );
        add_settings_field(
            'product_lookup_source',
            'Product Lookup Source',
            [ __CLASS__, 'product_lookup_source_field' ],
            'aaa-v4-order-settings',
            'aaa_v4_main'
        );

        add_settings_field(
            'custom_table_name',
            'Custom Table Name (if using custom)',
            [ __CLASS__, 'custom_table_name_field' ],
            'aaa-v4-order-settings',
            'aaa_v4_main'
        );

        add_settings_field(
            'custom_id_column',
            'Product ID Match Column',
            [ __CLASS__, 'custom_id_column_field' ],
            'aaa-v4-order-settings',
            'aaa_v4_main'
        );
        add_settings_field(
            'fallback_image_id',
            'Placeholder Image (Media ID or URL)',
            [ __CLASS__, 'fallback_image_field' ],
            'aaa-v4-order-settings',
            'aaa_v4_main'
        );
	add_settings_field(
            'default_shipping_method',
            'Default Shipping Method',
            [ __CLASS__, 'default_shipping_method_field' ],
            'aaa-v4-order-settings',
            'aaa_v4_main'
        );


        // ───────────────────────────────────────────────
        // NEW: Default State (two‐letter USPS code)
        // ───────────────────────────────────────────────
        add_settings_field(
            'default_state',
            'Default State',
            [ __CLASS__, 'default_state_field' ],
            'aaa-v4-order-settings',
            'aaa_v4_main'
        );

        // ───────────────────────────────────────────────
        // NEW: Default Country
        // ───────────────────────────────────────────────
        add_settings_field(
            'default_country',
            'Default Country',
            [ __CLASS__, 'default_country_field' ],
            'aaa-v4-order-settings',
            'aaa_v4_main'
        );
	add_settings_field(
	    'hide_parser_textarea',
	    'Hide Parser Textarea',
	    [ __CLASS__, 'hide_parser_textarea_field' ],
	    'aaa-v4-order-settings',
	    'aaa_v4_main'
	);
	// inside register_settings()
	add_settings_field(
	    'disable_parser',
	    'Disable Parser',
	    [ __CLASS__, 'disable_parser_field' ],
	    'aaa-v4-order-settings',
	    'aaa_v4_main'
	);

	// External Order Number toggle
	add_settings_field(
	    'enable_external_order_number',
	    'Enable External Order # Field',
	    [ __CLASS__, 'enable_external_order_number_field' ],
	    'aaa-v4-order-settings',
	    'aaa_v4_main'
	);

	// Required fields toggles
	add_settings_field(
	    'require_id_number',
	    'Require Driver’s License Number',
	    [ __CLASS__, 'require_id_number_field' ],
	    'aaa-v4-order-settings',
	    'aaa_v4_main'
	);

	add_settings_field(
	    'require_dl_expiration',
	    'Require DL Expiration Date',
	    [ __CLASS__, 'require_dl_expiration_field' ],
	    'aaa-v4-order-settings',
	    'aaa_v4_main'
	);

	add_settings_field(
	    'require_birthday',
	    'Require Birthday',
	    [ __CLASS__, 'require_birthday_field' ],
	    'aaa-v4-order-settings',
	    'aaa_v4_main'
	);

	
    }

    public static function get_settings() {
        return get_option( self::OPTION_KEY, [] );
    }

    public static function brand_meta_field() {
        $opts = self::get_settings();
        $value = esc_attr( $opts['brand_meta_key'] ?? '' );
        echo '<input type="text" name="' . self::OPTION_KEY . '[brand_meta_key]" value="' . $value . '" style="width: 300px;">';
    }

    public static function order_status_field() {
        $opts = self::get_settings();
        $value = $opts['default_order_status'] ?? 'processing';

        $statuses = wc_get_order_statuses();
        echo '<select name="' . self::OPTION_KEY . '[default_order_status]">';
        foreach ( $statuses as $status_key => $status_label ) {
            $selected = selected( $value, $status_key, false );
            echo '<option value="' . esc_attr( $status_key ) . '"' . $selected . '>' . esc_html( $status_label ) . '</option>';
        }
        echo '</select>';
    }
    public static function default_shipping_method_field() {
        $opts  = self::get_settings();
        $value = $opts['default_shipping_method'] ?? '';

        // Fetch available shipping methods
        $methods = WC()->shipping()->get_shipping_methods();

        echo '<select name="' . self::OPTION_KEY . '[default_shipping_method]">';
        echo '<option value="">— None —</option>';
        foreach ( $methods as $id => $method ) {
            // Note: sub-sites may have instance IDs (like flat_rate:3)
            $label = $method->get_method_title() . ' (' . esc_html( $id ) . ')';
            $sel   = selected( $value, $id, false );
            echo "<option value=\"" . esc_attr( $id ) . "\" {$sel}>{$label}</option>";
        }
        echo '</select>';
        echo '<p class="description">Choose the default shipping method ID (e.g., flat_rate:1). Leave blank for none.</p>';
    }


    public static function fallback_image_field() {
        $opts = self::get_settings();
        $val = $opts['fallback_image_id'] ?? '';

        echo '<input type="text" name="' . self::OPTION_KEY . '[fallback_image_id]" value="' . esc_attr($val) . '" style="width:300px;">';
        echo '<p class="description">Enter a WordPress Media Library ID or full image URL (e.g. https://...)</p>';
    }

    public static function upload_csv_field() {
        echo '<input type="file" name="aaa_v4_parser_upload" accept=".csv" />';
        echo '<p><em>Upload a 23-column CSV to populate the parsing table.</em></p>';
    }

    public static function product_match_table_field() {
        $opts = self::get_settings();
        $value = $opts['product_match_table'] ?? 'aaa_wf_v4_parser_index';
        $options = [
            'aaa_wf_v4_parser_index' => 'Internal Parsing Table (Recommended)',
            'lkd_wm_fields'          => 'Live Site Table (lkd_wm_fields)'
        ];

        echo '<select name="' . self::OPTION_KEY . '[product_match_table]">';
        foreach ( $options as $key => $label ) {
            $selected = selected( $value, $key, false );
            echo "<option value=\"{$key}\" {$selected}>{$label}</option>";
        }
        echo '</select>';
    }

    public static function product_lookup_source_field() {
        $opts = self::get_settings();
        $value = $opts['product_lookup_source'] ?? 'woocommerce';
        $options = [
            'woocommerce' => 'WooCommerce (Default)',
            'custom'      => 'Custom Table',
        ];

        echo '<select name="' . self::OPTION_KEY . '[product_lookup_source]">';
        foreach ( $options as $key => $label ) {
            $selected = selected( $value, $key, false );
            echo "<option value=\"{$key}\" {$selected}>{$label}</option>";
        }
        echo '</select>';
    }

    public static function custom_table_name_field() {
        $opts = self::get_settings();
        $value = esc_attr( $opts['custom_table_name'] ?? '' );
        echo '<input type="text" name="' . self::OPTION_KEY . '[custom_table_name]" value="' . $value . '" style="width:300px;">';
    }

    public static function custom_id_column_field() {
        $opts = self::get_settings();
        $value = esc_attr( $opts['custom_id_column'] ?? '' );
        echo '<input type="text" name="' . self::OPTION_KEY . '[custom_id_column]" value="' . $value . '" style="width:300px;">';
    }
	    public static function disable_parser_field() {
	    $opts = self::get_settings();
	    $checked = ! empty( $opts['disable_parser'] ) ? 'checked' : '';
	    echo '<label><input type="checkbox" name="' . self::OPTION_KEY . '[disable_parser]" value="1" ' . $checked . '> Do not run HTML parser at all</label>';
	}


	public static function enable_external_order_number_field() {
	    $opts = self::get_settings();
	    $checked = ! empty( $opts['enable_external_order_number'] ) ? 'checked' : '';
	    echo '<label><input type="checkbox" name="' . self::OPTION_KEY . '[enable_external_order_number]" value="1" ' . $checked . '> Show external order # field (optional)</label>';
	}

	public static function require_id_number_field() {
	    $opts = self::get_settings();
	    $checked = ! empty( $opts['require_id_number'] ) ? 'checked' : '';
	    echo '<label><input type="checkbox" name="' . self::OPTION_KEY . '[require_id_number]" value="1" ' . $checked . '> Force ID number required</label>';
	}

	public static function require_dl_expiration_field() {
	    $opts = self::get_settings();
	    $checked = ! empty( $opts['require_dl_expiration'] ) ? 'checked' : '';
	    echo '<label><input type="checkbox" name="' . self::OPTION_KEY . '[require_dl_expiration]" value="1" ' . $checked . '> Force DL expiration required</label>';
	}

	public static function require_birthday_field() {
	    $opts = self::get_settings();
	    $checked = ! empty( $opts['require_birthday'] ) ? 'checked' : '';
	    echo '<label><input type="checkbox" name="' . self::OPTION_KEY . '[require_birthday]" value="1" ' . $checked . '> Force Birthday required</label>';
}

    // ───────────────────────────────────────────────
    // NEW: Render Default State dropdown
    // ───────────────────────────────────────────────
    public static function default_state_field() {
        $opts = self::get_settings();
        $value = $opts['default_state'] ?? 'CA';

        $states = [
            'AL' => 'Alabama',
            'AK' => 'Alaska',
            'AZ' => 'Arizona',
            'AR' => 'Arkansas',
            'CA' => 'California',
            'CO' => 'Colorado',
            'CT' => 'Connecticut',
            'DE' => 'Delaware',
            'FL' => 'Florida',
            'GA' => 'Georgia',
            'HI' => 'Hawaii',
            'ID' => 'Idaho',
            'IL' => 'Illinois',
            'IN' => 'Indiana',
            'IA' => 'Iowa',
            'KS' => 'Kansas',
            'KY' => 'Kentucky',
            'LA' => 'Louisiana',
            'ME' => 'Maine',
            'MD' => 'Maryland',
            'MA' => 'Massachusetts',
            'MI' => 'Michigan',
            'MN' => 'Minnesota',
            'MS' => 'Mississippi',
            'MO' => 'Missouri',
            'MT' => 'Montana',
            'NE' => 'Nebraska',
            'NV' => 'Nevada',
            'NH' => 'New Hampshire',
            'NJ' => 'New Jersey',
            'NM' => 'New Mexico',
            'NY' => 'New York',
            'NC' => 'North Carolina',
            'ND' => 'North Dakota',
            'OH' => 'Ohio',
            'OK' => 'Oklahoma',
            'OR' => 'Oregon',
            'PA' => 'Pennsylvania',
            'RI' => 'Rhode Island',
            'SC' => 'South Carolina',
            'SD' => 'South Dakota',
            'TN' => 'Tennessee',
            'TX' => 'Texas',
            'UT' => 'Utah',
            'VT' => 'Vermont',
            'VA' => 'Virginia',
            'WA' => 'Washington',
            'WV' => 'West Virginia',
            'WI' => 'Wisconsin',
            'WY' => 'Wyoming',
        ];

        echo '<select name="' . self::OPTION_KEY . '[default_state]">';
        foreach ( $states as $abbr => $label ) {
            $sel = selected( $value, $abbr, false );
            echo "<option value=\"" . esc_attr( $abbr ) . "\" {$sel}>" . esc_html( "{$label} ({$abbr})" ) . "</option>";
        }
        echo '</select>';
        echo '<p class="description">Select the state (two‐letter code) that will always be assigned.</p>';
    }

    // ───────────────────────────────────────────────
    // NEW: Render Default Country dropdown
    // ───────────────────────────────────────────────
    public static function default_country_field() {
        $opts = self::get_settings();
        $value = $opts['default_country'] ?? 'United States';

        $countries = [
            'United States' => 'United States',
            'Canada'        => 'Canada',
            'Mexico'        => 'Mexico',
        ];

        echo '<select name="' . self::OPTION_KEY . '[default_country]">';
        foreach ( $countries as $key => $label ) {
            $sel = selected( $value, $key, false );
            echo "<option value=\"" . esc_attr( $key ) . "\" {$sel}>" . esc_html( $label ) . "</option>";
        }
        echo '</select>';
        echo '<p class="description">Select the country that will always be assigned.</p>';
    }
	public static function hide_parser_textarea_field() {
	    $opts = self::get_settings();
	    $checked = ! empty( $opts['hide_parser_textarea'] ) ? 'checked' : '';
	    echo '<label><input type="checkbox" name="' . self::OPTION_KEY . '[hide_parser_textarea]" value="1" ' . $checked . '> Hide “Paste Full Order HTML” box</label>';
	}


    public static function handle_upload() {
        if (
            isset($_FILES['aaa_v4_parser_upload']) &&
            ! empty($_FILES['aaa_v4_parser_upload']['tmp_name']) &&
            current_user_can('manage_woocommerce')
        ) {
            $file = $_FILES['aaa_v4_parser_upload']['tmp_name'];
            $handle = fopen($file, 'r');

            if (! $handle) {
                AAA_V4_Logger::log('CSV upload failed: could not open file.');
                return;
            }

            $header = fgetcsv($handle); // Skip header row

            if (count($header) < 23) {
                AAA_V4_Logger::log('CSV upload aborted: fewer than 23 columns.');
                return;
            }

            global $wpdb;
            $table = $wpdb->prefix . 'aaa_wf_v4_parser_index';

            $row_num = 1;
            $row_count = 0;

            while (($row = fgetcsv($handle)) !== false) {
                $row_num++;

                if (count($row) < 23) {
                    AAA_V4_Logger::log("Row {$row_num} skipped: not enough columns.");
                    continue;
                }

                if (empty($row[4])) {
                    AAA_V4_Logger::log("Row {$row_num} skipped: wm_external_id is empty.");
                    continue;
                }

                $result = $wpdb->replace($table, [
                    'post_id'              => 0, // Default unless linked later
                    'wm_og_body'           => $row[1],
                    'wm_og_brand_id'       => $row[2],
                    'wm_og_brand_name'     => $row[3],
                    'wm_external_id'       => sanitize_text_field($row[4]),
                    'wm_genetics'          => $row[5],
                    'wm_og_name'           => $row[6],
                    'wm_published'         => ($row[7] === '1') ? 1 : 0,
                    'wm_og_slug'           => $row[8],
                    'wm_strain_id'         => $row[9],
                    'wm_product_id'        => $row[10],
                    'wm_category_raw'      => $row[11],
                    // tags is not in table — skipping index 12
                    'wm_cbd_percentage'    => $row[13],
                    'wm_thc_percentage'    => $row[14],
                    'wm_created_at'        => $row[15],
                    'wm_updated_at'        => $row[16],
                    'wm_license_type'      => $row[17],
                    'wm_online_orderable'  => ($row[18] === '1') ? 1 : 0,
                    'wm_price_currency'    => 'USD', // Fixed as per your note
                    'wm_unit_price'        => floatval($row[20]),
                    'wm_discount_type'     => $row[21],
                    'wm_discount_value'    => $row[22],
                    'wm_sale_price'        => 0, // optional override
                    'was_created'          => 0,
                ]);

                if ( $wpdb->last_error ) {
                    AAA_V4_Logger::log("Row {$row_num} failed: " . $wpdb->last_error);
                } else {
                    $row_count++;
                }
            }

            fclose($handle);
            AAA_V4_Logger::log("CSV import completed: {$row_count} rows added to parser index.");
        }
    }

    public static function render_settings_page() {
        echo '<div class="wrap">';
        echo '<h1>Order Creator Settings</h1>';
        if ( get_transient( 'aaa_v4_csv_upload_success' ) ) {
            echo '<div class="notice notice-success is-dismissible"><p>CSV imported successfully.</p></div>';
            delete_transient( 'aaa_v4_csv_upload_success' );
        }
        echo '<form method="post" action="options.php" enctype="multipart/form-data">';
        settings_fields( 'aaa_v4_settings_group' );
        do_settings_sections( 'aaa-v4-order-settings' );
        submit_button();
        echo '</form></div>';
    }
}
