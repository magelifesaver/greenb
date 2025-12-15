<?php
/**
 * File: includes/class-aaa-api-product-category-export.php
 *
 * Handles product-category-based export profiles and JSON generation.
 *
 * This version introduces a per-file debug constant
 * (`AAA_API_PRODUCT_CATEGORY_EXPORT_DEBUG`) which controls whether
 * diagnostic messages are written to the error log.  The constant
 * inherits the value of `AAA_API_MAPPER_DEBUG` by default.  Define it
 * in `wp-config.php` to enable or disable debug logging for this
 * specific module.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Ensure per-file debug constant is defined.  It inherits the global
// debug flag when not explicitly set.
if ( ! defined( 'AAA_API_PRODUCT_CATEGORY_EXPORT_DEBUG' ) ) {
    if ( defined( 'AAA_API_MAPPER_DEBUG' ) ) {
        define( 'AAA_API_PRODUCT_CATEGORY_EXPORT_DEBUG', AAA_API_MAPPER_DEBUG );
    } else {
        define( 'AAA_API_PRODUCT_CATEGORY_EXPORT_DEBUG', false );
    }
}

class AAA_API_ProductCategoryExport {

    private $table      = 'aaa_oc_options';
    private $scope      = 'mapper_profiles';
    private $option_key = 'product_category_exports';
    private $output_dir;
    private $output_url;

    /**
     * Cached attribute taxonomy index: taxonomy_slug => attribute object.
     *
     * @var array
     */
    private static $attribute_index = [];

    public function __construct() {
        $upload_dir = wp_upload_dir();

        // ✅ Force correct site-specific uploads subdirectory for mapping files
        $this->output_dir = trailingslashit( $upload_dir['basedir'] ) . 'sites/9/mappings/';
        $this->output_url = trailingslashit( $upload_dir['baseurl'] ) . 'sites/9/mappings/';

        add_action( 'admin_init', [ $this, 'handle_post' ] );
        add_action( 'aaa_tm_render_product_exports_tab', [ $this, 'render_tab' ] );

        // Tie product export profiles into the same cron hook as the taxonomy mapper.
        add_action( 'tm_mapper_cron_rebuild', [ $this, 'cron_run_all_profiles' ] );

        // Debug message when the module is constructed
        if ( AAA_API_PRODUCT_CATEGORY_EXPORT_DEBUG ) {
            error_log( '[AAA_API_ProductCategoryExport] Module constructed.' );
        }
    }

    /** ------------------------------
     *  STORAGE HELPERS
     *  ------------------------------ */
    private function get_profiles() {
        global $wpdb;
        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT option_value FROM {$wpdb->prefix}{$this->table} WHERE scope = %s AND option_key = %s",
                $this->scope,
                $this->option_key
            )
        );
        $profiles = $row ? maybe_unserialize( $row->option_value ) : [];
        return is_array( $profiles ) ? $profiles : [];
    }

    private function save_profiles( array $profiles ) {
        global $wpdb;
        $wpdb->replace(
            $wpdb->prefix . $this->table,
            [
                'scope'        => $this->scope,
                'option_key'   => $this->option_key,
                'option_value' => maybe_serialize( $profiles ),
            ]
        );
    }

    /** ------------------------------
     *  POST HANDLER
     *  ------------------------------ */
    public function handle_post() {
        if ( ! is_admin() || empty( $_POST['aaa_tm_pe_action'] ) ) {
            return;
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        if ( empty( $_POST['aaa_tm_pe_nonce'] ) || ! wp_verify_nonce( $_POST['aaa_tm_pe_nonce'], 'aaa_tm_pe_action' ) ) {
            return;
        }

        $action   = sanitize_key( $_POST['aaa_tm_pe_action'] );
        $profiles = $this->get_profiles();
        $message  = '';

        if ( 'add_profile' === $action ) {
            $cat_id = isset( $_POST['category_id'] ) ? (int) $_POST['category_id'] : 0;

            $attribute_mode = isset( $_POST['attribute_mode'] ) ? sanitize_key( $_POST['attribute_mode'] ) : 'all_attributes';
            if ( ! in_array( $attribute_mode, [ 'all_attributes', 'selected_attributes' ], true ) ) {
                $attribute_mode = 'all_attributes';
            }

            // Stock filter: 'both', 'instock', 'outofstock'.
            $stock_filter = isset( $_POST['stock_filter'] ) ? sanitize_key( $_POST['stock_filter'] ) : 'both';
            if ( ! in_array( $stock_filter, [ 'both', 'instock', 'outofstock' ], true ) ) {
                $stock_filter = 'both';
            }

            // Selected attributes: allow multiple.
            $selected_taxonomies = [];
            if ( 'selected_attributes' === $attribute_mode && ! empty( $_POST['attribute_taxonomies'] ) && is_array( $_POST['attribute_taxonomies'] ) ) {
                foreach ( $_POST['attribute_taxonomies'] as $slug ) {
                    $slug = sanitize_text_field( wp_unslash( $slug ) );
                    if ( $slug !== '' ) {
                        $selected_taxonomies[] = $slug;
                    }
                }
                $selected_taxonomies = array_values( array_unique( $selected_taxonomies ) );
                if ( empty( $selected_taxonomies ) ) {
                    $message = 'Please select at least one attribute when using "Selected attributes" mode.';
                    set_transient( 'aaa_tm_pe_message', $message, 30 );
                    wp_safe_redirect( admin_url( 'admin.php?page=aaa-taxonomy-mapper&tab=product_exports' ) );
                    exit;
                }
            }

            if ( $cat_id ) {
                $term = get_term( $cat_id, 'product_cat' );
                if ( $term && ! is_wp_error( $term ) ) {
                    $key              = 'cat_' . $term->term_id;
                    $profiles[ $key ] = [
                        'key'                 => $key,
                        'category_id'         => $term->term_id,
                        'category_name'       => $term->name,
                        'category_slug'       => $term->slug,
                        'file_name'           => 'products-cat-' . $term->term_id . '.json',
                        'attribute_mode'      => $attribute_mode,
                        // NEW: array of selected attribute taxonomies.
                        'attribute_taxonomies'=> $selected_taxonomies,
                        // Legacy single field kept for backward compatibility (first selected or empty).
                        'attribute_taxonomy'  => isset( $selected_taxonomies[0] ) ? $selected_taxonomies[0] : '',
                        'stock_filter'        => $stock_filter,
                        'last_rebuild'        => '',
                        'last_count'          => 0,
                        'last_size'           => 0,
                    ];
                    $this->save_profiles( $profiles );
                    $message = 'Profile added for category: ' . $term->name;
                }
            }
        } elseif ( 'delete_profile' === $action && ! empty( $_POST['profile_key'] ) ) {
            $key = sanitize_key( $_POST['profile_key'] );
            if ( isset( $profiles[ $key ] ) ) {
                unset( $profiles[ $key ] );
                $this->save_profiles( $profiles );
                $message = 'Profile deleted.';
            }
        } elseif ( 'export_profile' === $action && ! empty( $_POST['profile_key'] ) ) {
            $key = sanitize_key( $_POST['profile_key'] );
            if ( isset( $profiles[ $key ] ) ) {
                $export_meta = null;
                $ok          = $this->run_export( $profiles[ $key ], $export_meta );
                if ( $ok ) {
                    $profiles[ $key ]['last_rebuild'] = current_time( 'mysql' );
                    if ( is_array( $export_meta ) ) {
                        $profiles[ $key ]['last_count'] = isset( $export_meta['count'] ) ? (int) $export_meta['count'] : 0;
                        $profiles[ $key ]['last_size']  = isset( $export_meta['size'] ) ? (int) $export_meta['size'] : 0;
                    }
                    $this->save_profiles( $profiles );
                    $message = 'Export completed for: ' . $profiles[ $key ]['category_name'];
                } else {
                    $message = 'Export failed (see debug.log).';
                }
            }
        }

        if ( $message ) {
            set_transient( 'aaa_tm_pe_message', $message, 30 );
        }

        wp_safe_redirect( admin_url( 'admin.php?page=aaa-taxonomy-mapper&tab=product_exports' ) );
        exit;
    }

    /** ------------------------------
     *  RENDER TAB
     *  ------------------------------ */
    public function render_tab() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to access this page.', 'aaa-api-mapper' ) );
        }

        $profiles = $this->get_profiles();
        $message  = get_transient( 'aaa_tm_pe_message' );
        delete_transient( 'aaa_tm_pe_message' );

        if ( $message ) {
            echo '<div class="updated"><p>' . esc_html( $message ) . '</p></div>';
        }

        echo '<p>This section lets you create JSON exports of products by category, with either all attributes or a selected set of attributes per product, and optional stock filtering.</p>';

        // Existing profiles list.
        echo '<h2>Export Profiles</h2>';
        if ( ! empty( $profiles ) ) {
            echo '<table class="widefat striped"><thead><tr>';
            echo '<th>Category</th><th>Mode</th><th>File</th><th>Last Rebuild</th><th>Actions</th>';
            echo '</tr></thead><tbody>';
            foreach ( $profiles as $profile ) {
                $file_url     = $this->output_url . $profile['file_name'];
                $meta_modes   = $this->normalize_attribute_settings( $profile );
                $attribute_mode      = $meta_modes['mode'];
                $attribute_taxonomies = $meta_modes['taxonomies'];

                $stock_filter = $profile['stock_filter'] ?? 'both';
                $last_count   = isset( $profile['last_count'] ) ? (int) $profile['last_count'] : 0;
                $last_size    = isset( $profile['last_size'] ) ? (int) $profile['last_size'] : 0;

                if ( 'selected_attributes' === $attribute_mode && ! empty( $attribute_taxonomies ) ) {
                    $names = [];
                    foreach ( $attribute_taxonomies as $slug ) {
                        $meta       = $this->get_attribute_meta_for_taxonomy( $slug );
                        $names[]    = $meta ? $meta->attribute_label : $slug;
                    }
                    $mode_label = 'Selected attributes: ' . implode( ', ', $names );
                } else {
                    $mode_label = 'All attributes';
                }

                $stock_label = 'Both in/out of stock';
                if ( 'instock' === $stock_filter ) {
                    $stock_label = 'In-stock only';
                } elseif ( 'outofstock' === $stock_filter ) {
                    $stock_label = 'Out-of-stock only';
                }

                $size_label = $last_size > 0 ? $this->format_file_size( $last_size ) : 'N/A';

                echo '<tr>';
                echo '<td>' . esc_html( $profile['category_name'] ) . ' (ID ' . (int) $profile['category_id'] . ')</td>';
                echo '<td>' . esc_html( $mode_label ) . '<br><small>Stock: ' . esc_html( $stock_label ) . '</small></td>';
                echo '<td><code>' . esc_html( $profile['file_name'] ) . '</code><br>';
                echo '<small><a href="' . esc_url( $file_url ) . '" target="_blank">View JSON</a></small><br>';
                echo '<small>Products: ' . (int) $last_count . ' | Size: ' . esc_html( $size_label ) . '</small>';
                echo '</td>';
                echo '<td>' . ( $profile['last_rebuild'] ? esc_html( $profile['last_rebuild'] ) : '<em>Never</em>' ) . '</td>';
                echo '<td>';
                echo '<form method="post" style="display:inline;margin-right:4px;">';
                echo '<input type="hidden" name="aaa_tm_pe_action" value="export_profile">';
                echo '<input type="hidden" name="profile_key" value="' . esc_attr( $profile['key'] ) . '">';
                wp_nonce_field( 'aaa_tm_pe_action', 'aaa_tm_pe_nonce' );
                submit_button( 'Run Export', 'secondary', '', false );
                echo '</form>';

                echo '<form method="post" style="display:inline;" onsubmit="return confirm(\'Delete this profile?\');">';
                echo '<input type="hidden" name="aaa_tm_pe_action" value="delete_profile">';
                echo '<input type="hidden" name="profile_key" value="' . esc_attr( $profile['key'] ) . '">';
                wp_nonce_field( 'aaa_tm_pe_action', 'aaa_tm_pe_nonce' );
                submit_button( 'Delete', 'delete', '', false );
                echo '</form>';

                echo '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        } else {
            echo '<p><em>No profiles yet. Create one below.</em></p>';
        }

        // Add profile form.
        echo '<h2 style="margin-top:24px;">Add New Profile</h2>';
        $cats = get_terms(
            [
                'taxonomy'   => 'product_cat',
                'hide_empty' => false,
            ]
        );
        if ( is_wp_error( $cats ) || empty( $cats ) ) {
            echo '<p><em>No product categories found.</em></p>';
            return;
        }

        // Build attribute taxonomy list dynamically.
        $attribute_taxonomies = wc_get_attribute_taxonomies();
        $attr_options         = [];
        if ( is_array( $attribute_taxonomies ) && ! empty( $attribute_taxonomies ) ) {
            foreach ( $attribute_taxonomies as $tax ) {
                $slug = wc_attribute_taxonomy_name( $tax->attribute_name );
                $attr_options[ $slug ] = $tax->attribute_label . ' (' . $slug . ')';
            }
        }

        echo '<form method="post">';
        wp_nonce_field( 'aaa_tm_pe_action', 'aaa_tm_pe_nonce' );
        echo '<input type="hidden" name="aaa_tm_pe_action" value="add_profile">';
        echo '<table class="form-table">';

        // Category select with parent path mapping.
        echo '<tr><th><label for="aaa_tm_pe_category">Product Category</label></th><td>';
        echo '<select name="category_id" id="aaa_tm_pe_category">';
        foreach ( $cats as $cat ) {
            $label = $this->build_category_path_label( $cat );
            echo '<option value="' . (int) $cat->term_id . '">' . esc_html( $label ) . ' (ID ' . (int) $cat->term_id . ')</option>';
        }
        echo '</select>';
        echo '<p class="description">Each profile generates a JSON file with all products in this category.</p>';
        echo '</td></tr>';

        // Attribute mode + multi-select for attributes.
        echo '<tr><th><label for="aaa_tm_pe_mode">Attributes to include</label></th><td>';

        echo '<select name="attribute_mode" id="aaa_tm_pe_mode">';
        echo '<option value="all_attributes" selected>All attributes</option>';
        echo '<option value="selected_attributes">Selected attributes only</option>';
        echo '</select>';

        if ( ! empty( $attr_options ) ) {
            echo '<br><br><label for="aaa_tm_pe_attr">Selected attributes (when using "Selected attributes" mode)</label><br>';
            echo '<select name="attribute_taxonomies[]" id="aaa_tm_pe_attr" multiple size="16" style="min-width:260px;">';
            foreach ( $attr_options as $slug => $label ) {
                echo '<option value="' . esc_attr( $slug ) . '">' . esc_html( $label ) . '</option>';
            }
            echo '</select>';
            echo '<p class="description">Hold Ctrl (Windows) or Command (Mac) to select multiple attributes.</p>';
        } else {
            echo '<p class="description">No global attributes found yet. Define attributes in WooCommerce before using "Selected attributes" mode.</p>';
        }

        echo '</td></tr>';

        // Stock filter.
        echo '<tr><th><label for="aaa_tm_pe_stock">Stock filter</label></th><td>';
        echo '<select name="stock_filter" id="aaa_tm_pe_stock">';
        echo '<option value="both" selected>Both in-stock and out-of-stock</option>';
        echo '<option value="instock">In-stock products only</option>';
        echo '<option value="outofstock">Out-of-stock products only</option>';
        echo '</select>';
        echo '<p class="description">Filter products by stock status in the export.</p>';
        echo '</td></tr>';

        echo '</table>';
        submit_button( 'Add Profile' );
        echo '</form>';
    }

    /** ------------------------------
     *  ATTRIBUTE SETTINGS NORMALIZER
     *  ------------------------------ */
    private function normalize_attribute_settings( array $profile ) {
        // Normalize mode: map legacy 'single_attribute' to 'selected_attributes'.
        $mode = $profile['attribute_mode'] ?? 'all_attributes';
        if ( 'single_attribute' === $mode ) {
            $mode = 'selected_attributes';
        }
        if ( ! in_array( $mode, [ 'all_attributes', 'selected_attributes' ], true ) ) {
            $mode = 'all_attributes';
        }

        // Normalize selected attribute list.
        $taxonomies = [];
        if ( ! empty( $profile['attribute_taxonomies'] ) && is_array( $profile['attribute_taxonomies'] ) ) {
            foreach ( $profile['attribute_taxonomies'] as $slug ) {
                $slug = sanitize_text_field( $slug );
                if ( $slug !== '' ) {
                    $taxonomies[] = $slug;
                }
            }
        } elseif ( ! empty( $profile['attribute_taxonomy'] ) ) {
            // Legacy single attribute fallback.
            $taxonomies[] = sanitize_text_field( $profile['attribute_taxonomy'] );
        }

        $taxonomies = array_values( array_unique( $taxonomies ) );

        return [
            'mode'       => $mode,
            'taxonomies' => $taxonomies,
        ];
    }

    /** ------------------------------
     *  ATTRIBUTE TAXONOMY LOOKUP
     *  ------------------------------ */
    private function get_attribute_meta_for_taxonomy( $taxonomy_slug ) {
        if ( empty( self::$attribute_index ) ) {
            $taxonomies = wc_get_attribute_taxonomies();
            if ( is_array( $taxonomies ) ) {
                foreach ( $taxonomies as $tax ) {
                    $slug = wc_attribute_taxonomy_name( $tax->attribute_name );
                    self::$attribute_index[ $slug ] = $tax;
                }
            }
        }

        return isset( self::$attribute_index[ $taxonomy_slug ] ) ? self::$attribute_index[ $taxonomy_slug ] : null;
    }

    /** ------------------------------
     *  EXPORT LOGIC
     *  ------------------------------ */
    private function run_export( array $profile, &$export_meta = null ) {
        if ( ! file_exists( $this->output_dir ) ) {
            wp_mkdir_p( $this->output_dir );
        }

        $cat_id       = (int) $profile['category_id'];
        $stock_filter = $profile['stock_filter'] ?? 'both';

        $attr = $this->normalize_attribute_settings( $profile );
        $attribute_mode       = $attr['mode'];
        $attribute_taxonomies = $attr['taxonomies'];

        if ( ! in_array( $stock_filter, [ 'both', 'instock', 'outofstock' ], true ) ) {
            $stock_filter = 'both';
        }

        if ( ! $cat_id ) {
            return false;
        }

        $q = new WP_Query(
            [
                'post_type'      => 'product',
                'posts_per_page' => -1,
                'post_status'    => 'publish',
                'tax_query'      => [
                    [
                        'taxonomy' => 'product_cat',
                        'field'    => 'term_id',
                        'terms'    => $cat_id,
                    ],
                ],
                'fields'         => 'ids',
            ]
        );

        $data = [];

        if ( $q->have_posts() ) {
            foreach ( $q->posts as $product_id ) {
                $product = wc_get_product( $product_id );
                if ( ! $product ) {
                    continue;
                }

                // Stock filter at product level.
                if ( 'both' !== $stock_filter ) {
                    $status = $product->get_stock_status(); // 'instock', 'outofstock', etc.
                    if ( 'instock' === $stock_filter && 'instock' !== $status ) {
                        continue;
                    }
                    if ( 'outofstock' === $stock_filter && 'outofstock' !== $status ) {
                        continue;
                    }
                }

                // Categories context.
                $cats      = get_the_terms( $product_id, 'product_cat' );
                $cat_terms = [];
                if ( ! is_wp_error( $cats ) && ! empty( $cats ) ) {
                    foreach ( $cats as $c ) {
                        $cat_terms[] = [
                            'id'   => $c->term_id,
                            'name' => $c->name,
                            'slug' => $c->slug,
                        ];
                    }
                }

                // Attributes (collect all).
                $attributes    = [];
                $product_attrs = $product->get_attributes();

                foreach ( $product_attrs as $attr_obj ) {
                    if ( ! is_a( $attr_obj, 'WC_Product_Attribute' ) ) {
                        continue;
                    }

                    $taxonomy   = $attr_obj->get_name();
                    $is_tax     = $attr_obj->is_taxonomy();
                    $options    = $attr_obj->get_options();
                    $option_ids = [];
                    $labels     = [];

                    if ( $is_tax ) {
                        $option_ids = array_map( 'intval', $options );
                        if ( ! empty( $option_ids ) ) {
                            $terms = get_terms(
                                [
                                    'taxonomy'   => $taxonomy,
                                    'include'    => $option_ids,
                                    'hide_empty' => false,
                                ]
                            );
                            if ( ! is_wp_error( $terms ) ) {
                                foreach ( $terms as $t ) {
                                    $labels[] = $t->name;
                                }
                            }
                        }
                    } else {
                        $labels = array_map( 'wc_clean', $options );
                    }

                    $attr_id    = null;
                    $attr_label = $taxonomy;

                    if ( $is_tax ) {
                        $meta = $this->get_attribute_meta_for_taxonomy( $taxonomy );
                        if ( $meta ) {
                            $attr_id    = (int) $meta->attribute_id;
                            $attr_label = $meta->attribute_label;
                        }
                    }

                    $attributes[] = [
                        'id'          => $attr_id,
                        'name'        => $attr_label,
                        'slug'        => $taxonomy,
                        'visible'     => (bool) $attr_obj->get_visible(),
                        'is_taxonomy' => (bool) $is_tax,
                        'options'     => array_values( $labels ),
                        'option_ids'  => array_values( $option_ids ),
                    ];
                }

                // Filter down to selected attributes, if that mode is active.
                if ( 'selected_attributes' === $attribute_mode && ! empty( $attribute_taxonomies ) ) {
                    $attributes = array_values(
                        array_filter(
                            $attributes,
                            function ( $a ) use ( $attribute_taxonomies ) {
                                return isset( $a['slug'] ) && in_array( $a['slug'], $attribute_taxonomies, true );
                            }
                        )
                    );
                }

                $data[] = [
                    'id'         => $product_id,
                    'sku'        => $product->get_sku(),
                    'name'       => $product->get_name(),
                    'categories' => $cat_terms,
                    'attributes' => $attributes,
                ];
            }
        }

        $file   = $this->output_dir . $profile['file_name'];
        $json   = wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
        $result = file_put_contents( $file, $json );

        if ( false === $result ) {
            // Always log write failures, regardless of debug setting.
            error_log( '[AAA_API_ProductCategoryExport] Failed to write ' . $file );
            return false;
        }

        $export_meta = [
            'count' => count( $data ),
            'size'  => (int) $result,
        ];

        return true;
    }

    /** ------------------------------
     *  CRON RUNNER FOR ALL PROFILES
     *  ------------------------------ */
    public function cron_run_all_profiles() {
        $profiles = $this->get_profiles();
        if ( empty( $profiles ) || ! is_array( $profiles ) ) {
            return;
        }

        foreach ( $profiles as $key => $profile ) {
            $export_meta = null;
            $ok          = $this->run_export( $profile, $export_meta );
            if ( $ok && is_array( $export_meta ) ) {
                $profiles[ $key ]['last_rebuild'] = current_time( 'mysql' );
                $profiles[ $key ]['last_count']   = isset( $export_meta['count'] ) ? (int) $export_meta['count'] : 0;
                $profiles[ $key ]['last_size']    = isset( $export_meta['size'] ) ? (int) $export_meta['size'] : 0;
            }
        }

        $this->save_profiles( $profiles );
    }

    /** ------------------------------
     *  HELPERS
     *  ------------------------------ */

    /**
     * Build a "Parent › Child" style label for a category.
     */
    private function build_category_path_label( $term ) {
        if ( ! $term instanceof WP_Term ) {
            return '';
        }

        $names     = [];
        $ancestors = get_ancestors( $term->term_id, 'product_cat' );
        if ( ! empty( $ancestors ) ) {
            $ancestors = array_reverse( $ancestors );
            foreach ( $ancestors as $ancestor_id ) {
                $ancestor = get_term( $ancestor_id, 'product_cat' );
                if ( $ancestor && ! is_wp_error( $ancestor ) ) {
                    $names[] = $ancestor->name;
                }
            }
        }
        $names[] = $term->name;

        return implode( ' › ', $names );
    }

    /**
     * Simple file size formatter (bytes to KB/MB).
     */
    private function format_file_size( $bytes ) {
        $bytes = (int) $bytes;
        if ( $bytes <= 0 ) {
            return '0 B';
        }
        if ( $bytes < 1024 ) {
            return $bytes . ' B';
        }
        if ( $bytes < 1048576 ) {
            return round( $bytes / 1024, 1 ) . ' KB';
        }
        return round( $bytes / 1048576, 2 ) . ' MB';
    }
}
