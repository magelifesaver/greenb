<?php
/**
 * File: includes/class-aaa-api-taxonomy-mapper-generator.php
 *
 * Core taxonomy mapper generator class.
 * Handles brands, categories, suppliers, locations, and attributes JSON
 * mappings.  This version introduces a per-file debug constant
 * (`AAA_API_TAXONOMY_MAPPER_GENERATOR_DEBUG`) which can be defined
 * in `wp-config.php` to enable or disable diagnostic logging for this
 * class.  The constant inherits the global `AAA_API_MAPPER_DEBUG`
 * flag by default.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Ensure per-file debug constant is defined.  Inherit the global flag if
// not set explicitly.
if ( ! defined( 'AAA_API_TAXONOMY_MAPPER_GENERATOR_DEBUG' ) ) {
    if ( defined( 'AAA_API_MAPPER_DEBUG' ) ) {
        define( 'AAA_API_TAXONOMY_MAPPER_GENERATOR_DEBUG', AAA_API_MAPPER_DEBUG );
    } else {
        define( 'AAA_API_TAXONOMY_MAPPER_GENERATOR_DEBUG', false );
    }
}

class AAA_API_TaxonomyMapperGenerator {

    private $table = 'aaa_oc_options';
    private $scope = 'mapper';
    private $output_dir;
    private $output_url;

    public function __construct() {
        $upload_dir       = wp_upload_dir();
        $this->output_dir = trailingslashit( $upload_dir['basedir'] ) . 'mappings/';
        $this->output_url = trailingslashit( $upload_dir['baseurl'] ) . 'mappings/';

        add_action( 'admin_menu', [ $this, 'register_settings_page' ] );
        add_filter( 'plugin_action_links_' . plugin_basename( AAA_API_MAPPER_PLUGIN_FILE ), [ $this, 'settings_link' ] );

        add_action( 'created_term', [ $this, 'maybe_regenerate' ] );
        add_action( 'edited_term', [ $this, 'maybe_regenerate' ] );
        add_action( 'delete_term', [ $this, 'maybe_regenerate' ] );

        add_action( 'tm_mapper_cron_rebuild', [ $this, 'generate_all_json' ] );
        $this->schedule_cron();

        // Debug message when the core class is constructed
        if ( AAA_API_TAXONOMY_MAPPER_GENERATOR_DEBUG ) {
            error_log( '[AAA_API_TaxonomyMapperGenerator] Core class constructed.' );
        }
    }

    /** ------------------------------
     *  CRON SETUP
     *  ------------------------------ */
    private function schedule_cron() {
        if ( ! wp_next_scheduled( 'tm_mapper_cron_rebuild' ) ) {
            wp_schedule_event( time(), 'daily', 'tm_mapper_cron_rebuild' );
        }
    }

    /** ------------------------------
     *  ADMIN PAGE
     *  ------------------------------ */
    public function register_settings_page() {
        add_menu_page(
            'AAA Taxonomy Mapper',
            'Taxonomy Mapper',
            'manage_options',
            'aaa-taxonomy-mapper',
            [ $this, 'settings_page' ],
            'dashicons-rest-api'
        );
    }

    public function settings_link( $links ) {
        $settings_link = '<a href="admin.php?page=aaa-taxonomy-mapper">Settings</a>';
        array_unshift( $links, $settings_link );
        return $links;
    }

    public function settings_page() {
        global $wpdb;

        $tabs = [
            'brands',
            'categories',
            'suppliers',
            'locations',
            'attributes',
            'product_exports',
        ];

        $active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'brands';
        if ( ! in_array( $active_tab, $tabs, true ) ) {
            $active_tab = 'brands';
        }

        $settings = $this->get_settings( $active_tab );

        echo '<div class="wrap"><h1>AAA API Taxonomy Mapper Generator</h1><h2 class="nav-tab-wrapper">';
        foreach ( $tabs as $tab ) {
            $active = ( $active_tab === $tab ) ? 'nav-tab-active' : '';
            $label  = ucfirst( $tab );
            if ( 'product_exports' === $tab ) {
                $label = 'Product Exports';
            }
            echo '<a href="' . esc_url( admin_url( 'admin.php?page=aaa-taxonomy-mapper&tab=' . $tab ) ) . '" class="nav-tab ' . esc_attr( $active ) . '">' . esc_html( $label ) . '</a>';
        }
        echo '</h2>';

        // Hand off the Product Exports tab to the dedicated module.
        if ( 'product_exports' === $active_tab ) {
            /**
             * Hook: aaa_tm_render_product_exports_tab
             * Fired when the Product Exports tab is active.
             */
            do_action( 'aaa_tm_render_product_exports_tab' );
            echo '</div>';
            return;
        }

        if ( ! empty( $_POST['save_tab'] ) ) {
            check_admin_referer( 'aaa_tm_save_' . $active_tab );
            $this->save_settings( sanitize_key( $_POST['save_tab'] ), $_POST );
            wp_safe_redirect( admin_url( 'admin.php?page=aaa-taxonomy-mapper&tab=' . $active_tab ) );
            exit;
        }

        if ( ! empty( $_POST['tm_regenerate'] ) ) {
            check_admin_referer( 'aaa_tm_regen_' . $active_tab );
            $this->generate_json( sanitize_key( $_POST['tm_regenerate'] ) );
            echo '<div class="updated"><p>' . esc_html( ucfirst( $active_tab ) ) . ' JSON regenerated successfully.</p></div>';
        }

        $enabled      = ! empty( $settings['enabled'] ) ? 'checked' : '';
        $cron_enabled = ! empty( $settings['cron_enabled'] ) ? 'checked' : '';
        $auto_update  = ! empty( $settings['auto_update'] ) ? 'checked' : '';
        $show_log     = ! empty( $settings['show_log'] ) ? 'checked' : '';
        $file_path    = $settings['file_path'] ?? ( $this->output_dir . $active_tab . '.json' );
        $file_url     = $this->output_url . $active_tab . '.json';
        $last_rebuild = $settings['last_rebuild'] ?? 'Never';

        echo '<form method="post">';
        wp_nonce_field( 'aaa_tm_save_' . $active_tab );
        echo '<table class="form-table">';
        echo '<tr><th>Enable</th><td><label><input type="checkbox" name="enabled" value="1" ' . $enabled . '> Enable generation for this taxonomy</label></td></tr>';
        echo '<tr><th>File Path</th><td><input type="text" name="file_path" value="' . esc_attr( $file_path ) . '" size="70"> <br><small>URL: <a href="' . esc_url( $file_url ) . '" target="_blank">' . esc_html( $file_url ) . '</a></small></td></tr>';
        echo '<tr><th>Rebuild on Save</th><td><label><input type="checkbox" name="auto_update" value="1" ' . $auto_update . '> Automatically rebuild JSON when terms are saved</label></td></tr>';
        echo '<tr><th>Enable Daily Cron</th><td><label><input type="checkbox" name="cron_enabled" value="1" ' . $cron_enabled . '> Rebuild nightly via cron</label></td></tr>';
        echo '<tr><th>Show File Log</th><td><label><input type="checkbox" name="show_log" value="1" ' . $show_log . '> Display JSON file contents below</label></td></tr>';
        echo '<tr><th>Last Rebuild</th><td><strong>' . esc_html( $last_rebuild ) . '</strong></td></tr>';
        echo '</table>';
        submit_button( 'Save Settings' );
        echo '<input type="hidden" name="save_tab" value="' . esc_attr( $active_tab ) . '">';
        echo '</form>';

        echo '<hr><form method="post">';
        wp_nonce_field( 'aaa_tm_regen_' . $active_tab );
        echo '<input type="hidden" name="tm_regenerate" value="' . esc_attr( $active_tab ) . '">';
        submit_button( 'Manually Regenerate ' . esc_html( ucfirst( $active_tab ) ) . ' JSON' );
        echo '</form>';

        if ( ! empty( $settings['show_log'] ) && file_exists( $file_path ) ) {
            $contents = esc_html( file_get_contents( $file_path ) );
            echo '<h3>File Log (' . esc_html( basename( $file_path ) ) . ')</h3><textarea readonly rows="20" style="width:100%;font-family:monospace;">' . $contents . '</textarea>';
        }
        echo '</div>';
    }

    /** ------------------------------
     *  SETTINGS HANDLERS
     *  ------------------------------ */
    private function save_settings( $tab, $data ) {
        global $wpdb;

        $file_path = isset( $data['file_path'] ) ? sanitize_text_field( $data['file_path'] ) : '';

        $settings = [
            'enabled'      => ! empty( $data['enabled'] ),
            'file_path'    => $file_path,
            'auto_update'  => ! empty( $data['auto_update'] ),
            'cron_enabled' => ! empty( $data['cron_enabled'] ),
            'show_log'     => ! empty( $data['show_log'] ),
            'last_rebuild' => current_time( 'mysql' ),
        ];

        $wpdb->replace(
            $wpdb->prefix . $this->table,
            [
                'scope'        => $this->scope,
                'option_key'   => $tab,
                'option_value' => maybe_serialize( $settings ),
            ]
        );
    }

    private function get_settings( $tab ) {
        global $wpdb;
        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT option_value FROM {$wpdb->prefix}{$this->table} WHERE scope = %s AND option_key = %s",
                $this->scope,
                $tab
            )
        );
        return $row ? maybe_unserialize( $row->option_value ) : [];
    }

    /** ------------------------------
     *  REBUILD TRIGGERS
     *  ------------------------------ */
    public function maybe_regenerate() {
        $sections = [ 'brands', 'categories', 'suppliers', 'locations', 'attributes' ];
        foreach ( $sections as $section ) {
            $settings = $this->get_settings( $section );
            if ( ! empty( $settings['enabled'] ) && ! empty( $settings['auto_update'] ) ) {
                $this->generate_json( $section );
            }
        }
    }

    public function generate_all_json() {
        $sections = [ 'brands', 'categories', 'suppliers', 'locations', 'attributes' ];
        foreach ( $sections as $section ) {
            $this->generate_json( $section );
        }
    }

    /** ------------------------------
     *  FILE GENERATION
     *  ------------------------------ */
    private function generate_json( $type ) {
        global $wpdb;

        if ( ! file_exists( $this->output_dir ) ) {
            wp_mkdir_p( $this->output_dir );
        }

        switch ( $type ) {
            case 'brands':
                $data = $this->get_terms_data( 'berocket_brand' );
                break;
            case 'categories':
                $data = $this->get_terms_data( 'product_cat' );
                break;
            case 'suppliers':
                $data = $this->get_suppliers_data();
                break;
            case 'locations':
                $data = $this->get_terms_data( 'atum_location' );
                break;
            case 'attributes':
                $data = $this->get_attributes_data();
                break;
            default:
                $data = [];
        }

        $file   = $this->output_dir . $type . '.json';
        $result = file_put_contents( $file, wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) );

        $settings                 = $this->get_settings( $type );
        $settings['last_rebuild'] = current_time( 'mysql' );

        $wpdb->replace(
            $wpdb->prefix . $this->table,
            [
                'scope'        => $this->scope,
                'option_key'   => $type,
                'option_value' => maybe_serialize( $settings ),
            ]
        );

        if ( false === $result ) {
            // Always log write failures.
            error_log( 'Taxonomy Mapper Error: failed to write ' . $file );
            add_action(
                'admin_notices',
                function () use ( $file ) {
                    echo '<div class="error"><p>Failed to write JSON file for ' . esc_html( $file ) . ' (check permissions).</p></div>';
                }
            );
        }
    }

    /** ------------------------------
     *  DATA FETCHERS
     *  ------------------------------ */
    private function get_terms_data( $taxonomy ) {
        $terms = get_terms(
            [
                'taxonomy'   => $taxonomy,
                'hide_empty' => false,
            ]
        );
        if ( is_wp_error( $terms ) ) {
            return [];
        }

        $base = home_url();

        return array_map(
            static function ( $t ) use ( $taxonomy, $base ) {

                // Determine correct archive base
                $path = '';
                switch ( $taxonomy ) {
                    case 'product_cat':
                        // Front-end base for categories
                        $path = '/preroll/';
                        break;

                    case 'berocket_brand':
                        // Front-end base for brands
                        $path = '/brands/';
                        break;

                    case 'pa_classification':
                        // Front-end base for classification attribute
                        $path = '/classification/';
                        break;

                    default:
                        // Generic fallback to taxonomy slug
                        $path = '/' . sanitize_title( $taxonomy ) . '/';
                        break;
                }

                // Construct full URL
                $url = trailingslashit( $base . $path . $t->slug );

                return [
                    'id'   => $t->term_id,
                    'name' => $t->name,
                    'slug' => $t->slug,
                    'url'  => $url,
                ];
            },
            $terms
        );
    }

    private function get_suppliers_data() {
        if ( taxonomy_exists( 'atum_supplier' ) ) {
            return $this->get_terms_data( 'atum_supplier' );
        }

        $posts = get_posts(
            [
                'post_type'      => 'atum_supplier',
                'posts_per_page' => -1,
                'post_status'    => 'publish',
            ]
        );

        return array_map(
            static function ( $p ) {
                return [
                    'id'   => $p->ID,
                    'name' => $p->post_title,
                    'slug' => $p->post_name,
                ];
            },
            $posts
        );
    }

    private function get_attributes_data() {
        $data       = [];
        $attributes = wc_get_attribute_taxonomies();

        foreach ( $attributes as $attr ) {
            $taxonomy = wc_attribute_taxonomy_name( $attr->attribute_name );
            $terms    = get_terms(
                [
                    'taxonomy'   => $taxonomy,
                    'hide_empty' => false,
                ]
            );
            if ( is_wp_error( $terms ) ) {
                continue;
            }

            $data[] = [
                'id'    => (int) $attr->attribute_id,
                'name'  => $attr->attribute_label,
                'slug'  => $taxonomy,
                'terms' => array_map(
                    static function ( $t ) {
                        return [
                            'id'   => $t->term_id,
                            'name' => $t->name,
                            'slug' => $t->slug,
                        ];
                    },
                    $terms
                ),
            ];
        }

        return $data;
    }
}
