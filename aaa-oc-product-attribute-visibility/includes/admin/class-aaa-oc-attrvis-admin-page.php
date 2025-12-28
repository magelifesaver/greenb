<?php
/**
 * File: /aaa-oc-product-attribute-visibility/includes/admin/class-aaa-oc-attrvis-admin-page.php
 * Purpose: Renders the admin page under Products for reporting and fixing
 * attribute visibility.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'AAA_OC_ATTRVIS_DEBUG_ADMIN_PAGE' ) ) {
    define( 'AAA_OC_ATTRVIS_DEBUG_ADMIN_PAGE', true );
}

class AAA_OC_AttrVis_Admin_Page {

    /**
     * Initialize hooks for the admin page.
     */
    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
    }

    /**
     * Register the submenu under Products.
     */
    public static function menu() {
        // Place under Products → Attribute Visibility.
        add_submenu_page(
            'edit.php?post_type=product',
            __( 'Attribute Visibility', 'aaa-oc-attrvis' ),
            __( 'Attribute Visibility', 'aaa-oc-attrvis' ),
            'manage_woocommerce',
            AAA_OC_ATTRVIS_SLUG,
            array( __CLASS__, 'render' )
        );
    }

    /**
     * Render the settings/report page.
     */
    public static function render() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( esc_html__( 'Insufficient permissions.', 'aaa-oc-attrvis' ) );
        }
        // Get user inputs.
        $batch    = isset( $_GET['batch'] ) ? max( 1, (int) $_GET['batch'] ) : 50;
        $category = isset( $_GET['category'] ) ? (int) $_GET['category'] : 0;
        $paged    = isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1;
        $stock_status = isset( $_GET['stock_status'] ) ? sanitize_text_field( wp_unslash( $_GET['stock_status'] ) ) : '';
        $attr_names   = isset( $_GET['attributes'] ) ? array_map( 'sanitize_key', (array) $_GET['attributes'] ) : array();
        $do_report = ! empty( $_GET['report'] );
        $scheduled = ! empty( $_GET['aaa_oc_attrvis_scheduled'] );
        $fix_selected_scheduled = ! empty( $_GET['aaa_oc_attrvis_selected_scheduled'] );
        $fix_selected_done      = ! empty( $_GET['aaa_oc_attrvis_selected'] );
        $fix_selected_checked   = isset( $_GET['selected_checked'] ) ? (int) $_GET['selected_checked'] : 0;
        $fix_selected_updated   = isset( $_GET['selected_updated'] ) ? (int) $_GET['selected_updated'] : 0;
        $fix_selected_rows      = isset( $_GET['selected_rows'] ) ? (int) $_GET['selected_rows'] : 0;
        // Base URL for forms.
        $base_url = admin_url( 'edit.php?post_type=product&page=' . AAA_OC_ATTRVIS_SLUG );
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Attribute Visibility Report', 'aaa-oc-attrvis' ); ?></h1>
            <p><?php esc_html_e( 'This page lets you audit and repair taxonomy‑based product attribute visibility. Only attributes defined as taxonomies are affected. Custom (non‑taxonomy) attributes are always visible on the storefront.', 'aaa-oc-attrvis' ); ?></p>

            <?php
            // Show success notice if fix was scheduled.
            if ( $scheduled ) {
                echo '<div class="notice notice-success"><p>' . esc_html__( 'A background job has been scheduled to fix all products. Results will appear below once complete.', 'aaa-oc-attrvis' ) . '</p></div>';
            }

            // Show notice if selected products fix was scheduled.
            if ( $fix_selected_scheduled ) {
                echo '<div class="notice notice-success"><p>' . esc_html__( 'A background job has been scheduled to fix the selected products.', 'aaa-oc-attrvis' ) . '</p></div>';
            }

            // Show notice if selected products were fixed immediately.
            if ( $fix_selected_done ) {
                echo '<div class="notice notice-success"><p>' . esc_html__( 'Selected products fixed:', 'aaa-oc-attrvis' ) . ' ' . esc_html( sprintf( '%d checked, %d updated, %d rows flipped.', $fix_selected_checked, $fix_selected_updated, $fix_selected_rows ) ) . '</p></div>';
            }

            // Show last job summary if available.
            $last = get_option( AAA_OC_AttrVis_Cron::OPTION_LAST_RESULT );
            if ( is_array( $last ) ) {
                $date = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $last['timestamp'] );
                echo '<div class="notice notice-info"><p><strong>' . esc_html__( 'Last fix summary:', 'aaa-oc-attrvis' ) . '</strong> ';
                echo esc_html( sprintf( '%s checked: %d products; updated: %d products; rows flipped: %d on %s.',
                    ( 'ids' === $last['type'] ? esc_html__( 'Bulk selection', 'aaa-oc-attrvis' ) : esc_html__( 'Fix all', 'aaa-oc-attrvis' ) ),
                    (int) $last['checked'],
                    (int) $last['products_updated'],
                    (int) $last['rows_changed'],
                    $date
                ) );
                echo '</p></div>';
            }
            ?>

            <form method="get" action="<?php echo esc_url( $base_url ); ?>" style="margin-bottom:20px;">
                <input type="hidden" name="post_type" value="product" />
                <input type="hidden" name="page" value="<?php echo esc_attr( AAA_OC_ATTRVIS_SLUG ); ?>" />
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="batch"><?php esc_html_e( 'Batch size', 'aaa-oc-attrvis' ); ?></label></th>
                        <td><input id="batch" name="batch" type="number" min="1" value="<?php echo esc_attr( $batch ); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="category"><?php esc_html_e( 'Category term ID (optional)', 'aaa-oc-attrvis' ); ?></label></th>
                        <td><input id="category" name="category" type="number" min="0" value="<?php echo esc_attr( $category ); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="stock_status"><?php esc_html_e( 'Stock status', 'aaa-oc-attrvis' ); ?></label></th>
                        <td>
                            <select id="stock_status" name="stock_status">
                                <option value="" <?php selected( $stock_status, '' ); ?>><?php esc_html_e( 'All statuses', 'aaa-oc-attrvis' ); ?></option>
                                <option value="instock" <?php selected( $stock_status, 'instock' ); ?>><?php esc_html_e( 'In stock', 'aaa-oc-attrvis' ); ?></option>
                                <option value="outofstock" <?php selected( $stock_status, 'outofstock' ); ?>><?php esc_html_e( 'Out of stock', 'aaa-oc-attrvis' ); ?></option>
                                <option value="onbackorder" <?php selected( $stock_status, 'onbackorder' ); ?>><?php esc_html_e( 'On backorder', 'aaa-oc-attrvis' ); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label><?php esc_html_e( 'Attributes to include', 'aaa-oc-attrvis' ); ?></label></th>
                        <td>
                            <?php
                            // List available WooCommerce attribute taxonomies.
                            $attribute_options = array();
                            if ( function_exists( 'wc_get_attribute_taxonomies' ) ) {
                                $taxes = wc_get_attribute_taxonomies();
                                if ( is_array( $taxes ) ) {
                                    foreach ( $taxes as $tax ) {
                                        // Each taxonomy slug is prefixed with 'pa_'.
                                        $slug  = 'pa_' . $tax->attribute_name;
                                        $label = $tax->attribute_label ? $tax->attribute_label : $tax->attribute_name;
                                        $attribute_options[ $slug ] = $label;
                                    }
                                }
                            }
                            if ( empty( $attribute_options ) ) {
                                echo '<em>' . esc_html__( 'No product attributes found.', 'aaa-oc-attrvis' ) . '</em>';
                            } else {
                                foreach ( $attribute_options as $slug => $label ) {
                                    $checked = in_array( $slug, $attr_names, true );
                                    echo '<label style="margin-right:12px;"><input type="checkbox" name="attributes[]" value="' . esc_attr( $slug ) . '"' . checked( $checked, true, false ) . ' /> ' . esc_html( $label ) . '</label>';
                                }
                            }
                            ?>
                        </td>
                    </tr>
                </table>
                <p>
                    <button class="button" name="update" value="1"><?php esc_html_e( 'Update fields', 'aaa-oc-attrvis' ); ?></button>
                    <button class="button button-secondary" name="report" value="1"><?php esc_html_e( 'Generate report', 'aaa-oc-attrvis' ); ?></button>
                </p>
                <input type="hidden" name="paged" value="1" />
            </form>

            <?php
            // If the user requested a report, produce and display it.
            if ( $do_report ) {
                $report = AAA_OC_AttrVis_Fixer::get_visibility_report( $batch, $paged, $category, $stock_status, $attr_names );
                echo '<h2>' . esc_html__( 'Report Results', 'aaa-oc-attrvis' ) . '</h2>';
                if ( empty( $report['items'] ) ) {
                    echo '<p>' . esc_html__( 'No products found for the selected criteria.', 'aaa-oc-attrvis' ) . '</p>';
                } else {
                    // Begin form for selecting products to fix.
                    echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" id="aaa-oc-attrvis-fix-selected-form">';
                    wp_nonce_field( 'aaa_oc_attrvis_fix_selected', 'aaa_oc_attrvis_nonce' );
                    echo '<input type="hidden" name="action" value="aaa_oc_attrvis_fix_selected" />';
                    echo '<input type="hidden" name="paged" value="' . esc_attr( $paged ) . '" />';
                    echo '<input type="hidden" name="batch" value="' . esc_attr( $batch ) . '" />';
                    echo '<input type="hidden" name="category" value="' . esc_attr( $category ) . '" />';
                    echo '<input type="hidden" name="stock_status" value="' . esc_attr( $stock_status ) . '" />';
                    // Persist attribute filters for fix selected.
                    if ( ! empty( $attr_names ) ) {
                        foreach ( $attr_names as $attr_slug ) {
                            echo '<input type="hidden" name="attributes[]" value="' . esc_attr( $attr_slug ) . '" />';
                        }
                    }
                    echo '<table class="widefat striped" style="max-width:100%;">
                        <thead>
                            <tr>
                                <th style="width:25px;"><input type="checkbox" id="aaa_oc_attrvis_select_all" /></th>
                                <th>' . esc_html__( 'Product', 'aaa-oc-attrvis' ) . '</th>
                                <th>' . esc_html__( 'Attribute (taxonomy)', 'aaa-oc-attrvis' ) . '</th>
                                <th>' . esc_html__( 'Visible', 'aaa-oc-attrvis' ) . '</th>
                            </tr>
                        </thead>
                        <tbody>';
                    foreach ( $report['items'] as $item ) {
                        $pid   = (int) $item['product_id'];
                        $title = esc_html( $item['product_title'] );
                        $attrs = (array) $item['attributes'];
                        if ( empty( $attrs ) ) {
                            // Single row when there are no matching attributes (shouldn’t happen with filters).
                            echo '<tr>';
                            echo '<td><input type="checkbox" class="aaa-oc-attrvis-checkbox" name="selected[]" value="' . $pid . '" /></td>';
                            echo '<td>' . $title . '</td>';
                            echo '<td colspan="2">' . esc_html__( '(none)', 'aaa-oc-attrvis' ) . '</td>';
                            echo '</tr>';
                        } else {
                            $first_row = true;
                            $rowspan   = count( $attrs );
                            foreach ( $attrs as $attr ) {
                                echo '<tr>';
                                if ( $first_row ) {
                                    echo '<td rowspan="' . $rowspan . '"><input type="checkbox" class="aaa-oc-attrvis-checkbox" name="selected[]" value="' . $pid . '" /></td>';
                                    echo '<td rowspan="' . $rowspan . '">' . $title . '</td>';
                                    $first_row = false;
                                }
                                echo '<td>' . esc_html( $attr['name'] ) . '</td>';
                                echo '<td>' . ( $attr['visible'] ? esc_html__( 'Yes', 'aaa-oc-attrvis' ) : esc_html__( 'No', 'aaa-oc-attrvis' ) ) . '</td>';
                                echo '</tr>';
                            }
                        }
                    }
                    echo '</tbody>
                    </table>';
                    echo '<p><button class="button" name="fix_selected" value="1">' . esc_html__( 'Fix selected', 'aaa-oc-attrvis' ) . '</button></p>';
                    echo '</form>';
                    // Navigation links for pagination.
                    echo '<p style="margin-top:12px;">';
                    if ( $paged > 1 ) {
                        $prev_url = add_query_arg( array(
                            'post_type'    => 'product',
                            'page'         => AAA_OC_ATTRVIS_SLUG,
                            'batch'        => $batch,
                            'category'     => $category,
                            'stock_status' => $stock_status,
                            'attributes'   => $attr_names,
                            'paged'        => max( 1, $paged - 1 ),
                            'report'       => 1,
                        ), admin_url( 'edit.php' ) );
                        echo '<a class="button" href="' . esc_url( $prev_url ) . '" style="margin-right:8px;">' . esc_html__( 'Previous page', 'aaa-oc-attrvis' ) . '</a>';
                    }
                    if ( ! empty( $report['has_more'] ) ) {
                        $next_url = add_query_arg( array(
                            'post_type'    => 'product',
                            'page'         => AAA_OC_ATTRVIS_SLUG,
                            'batch'        => $batch,
                            'category'     => $category,
                            'stock_status' => $stock_status,
                            'attributes'   => $attr_names,
                            'paged'        => $report['next_paged'],
                            'report'       => 1,
                        ), admin_url( 'edit.php' ) );
                        echo '<a class="button" href="' . esc_url( $next_url ) . '">' . esc_html__( 'Next page', 'aaa-oc-attrvis' ) . '</a>';
                    }
                    echo '</p>';
                    // Fix all form beneath the report.
                    echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
                    wp_nonce_field( 'aaa_oc_attrvis_fix_all', 'aaa_oc_attrvis_nonce' );
                    echo '<input type="hidden" name="action" value="aaa_oc_attrvis_fix_all" />';
                    echo '<input type="hidden" name="batch" value="' . esc_attr( $batch ) . '" />';
                    echo '<input type="hidden" name="category" value="' . esc_attr( $category ) . '" />';
                    echo '<input type="hidden" name="stock_status" value="' . esc_attr( $stock_status ) . '" />';
                    if ( ! empty( $attr_names ) ) {
                        foreach ( $attr_names as $attr_slug ) {
                            echo '<input type="hidden" name="attributes[]" value="' . esc_attr( $attr_slug ) . '" />';
                        }
                    }
                    echo '<p><button class="button button-primary" name="fix_all" value="1">' . esc_html__( 'Fix all', 'aaa-oc-attrvis' ) . '</button></p>';
                    echo '</form>';

                    // Add script to manage the select all checkbox.
                    echo '<script type="text/javascript">(function(){ document.addEventListener(\'DOMContentLoaded\', function() { var master = document.getElementById("aaa_oc_attrvis_select_all"); if ( master ) { master.addEventListener("change", function(){ var checkboxes = document.querySelectorAll(".aaa-oc-attrvis-checkbox"); for (var i=0; i<checkboxes.length; i++) { checkboxes[i].checked = master.checked; } }); } }); })();</script>';
                }
            }
            ?>
        </div>
        <?php
    }
}