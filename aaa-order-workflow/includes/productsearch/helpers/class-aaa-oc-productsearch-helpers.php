<?php
/**
 * File: /plugins/aaa-order-workflow/includes/productsearch/helpers/class-aaa-oc-productsearch-helpers.php
 * Purpose: Provide helper methods for tokenization, synonym expansion,
 *          index-based search and smart redirects. This class is a
 *          near-verbatim copy of version 1.3.0 from the upstream plugin.
 *          All public API signatures are preserved to maintain drop-in
 *          compatibility with existing code.
 *
 * Version: 1.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'AAA_OC_PS_HELPERS_DEBUG' ) ) {
    define( 'AAA_OC_PS_HELPERS_DEBUG', false );
}

class AAA_OC_ProductSearch_Helpers {
    /**
     * Fully qualified index table name.
     */
    private static function t_index() {
        global $wpdb;
        return $wpdb->prefix . AAA_OC_ProductSearch_Table_Installer::T_INDEX;
    }

    /**
     * Fully qualified synonyms table name.
     */
    private static function t_synonyms() {
        global $wpdb;
        return $wpdb->prefix . AAA_OC_ProductSearch_Table_Installer::T_SYNONYMS;
    }

    /**
     * Debug logger for helpers. When AAA_OC_PS_HELPERS_DEBUG is true messages
     * are sent to aaa_oc_log() or error_log().
     */
    private static function log( $msg ) {
        if ( ! AAA_OC_PS_HELPERS_DEBUG ) {
            return;
        }
        if ( function_exists( 'aaa_oc_log' ) ) {
            aaa_oc_log( '[PRODUCTSEARCH][HELPERS] ' . $msg );
        } else {
            error_log( '[PRODUCTSEARCH][HELPERS] ' . $msg );
        }
    }

    /**
     * Smart redirect rule. After resolving a query into product IDs this method
     * optionally redirects the user to a brand or category archive when all
     * results share the same brand or category. The query must exactly match
     * a brand or category name or slug for a redirect to be considered.
     *
     * @param string $q         The original user query.
     * @param array  $product_ids List of product IDs resolved from the index.
     */
    public static function maybe_redirect_by_results( string $q, array $product_ids ) {
        if ( empty( $product_ids ) ) {
            return;
        }
        global $wpdb;
        $q_trim = trim( (string) $q );
        if ( '' === $q_trim ) {
            return;
        }
        $normalized_q = strtolower( remove_accents( $q_trim ) );
        $scope        = '';
        $term         = null;
        // Detect if the query string itself is a known brand.
        $term = get_term_by( 'name', $q_trim, 'berocket_brand' );
        if ( ! $term || is_wp_error( $term ) ) {
            $term = get_term_by( 'slug', sanitize_title( $q_trim ), 'berocket_brand' );
        }
        if ( $term && ! is_wp_error( $term ) ) {
            $scope = 'brand';
        } else {
            // Otherwise, see if it's a known category.
            $term = get_term_by( 'name', $q_trim, 'product_cat' );
            if ( ! $term || is_wp_error( $term ) ) {
                $term = get_term_by( 'slug', sanitize_title( $q_trim ), 'product_cat' );
            }
            if ( $term && ! is_wp_error( $term ) ) {
                $scope = 'category';
            }
        }
        if ( ! $term || ! $scope ) {
            // Query isn't a clean brand or category term → no redirect rule.
            return;
        }
        $table = self::t_index();
        $in    = implode( ',', array_fill( 0, count( $product_ids ), '%d' ) );
        if ( 'brand' === $scope ) {
            // Brand redirect: only if ALL results have this exact brand_term_id.
            $brand_rows = $wpdb->get_col(
                $wpdb->prepare(
                    "SELECT DISTINCT brand_term_id FROM {$table} WHERE product_id IN ($in)",
                    $product_ids
                )
            );
            if ( empty( $brand_rows ) ) {
                return;
            }
            $distinct_ids    = [];
            foreach ( $brand_rows as $bid ) {
                $distinct_ids[ (int) $bid ] = true;
            }
            $distinct_ids    = array_keys( $distinct_ids );
            $non_null_brands = array_filter(
                $distinct_ids,
                static function ( $id ) {
                    return (int) $id > 0;
                }
            );
            if (
                count( $non_null_brands ) === 1 &&
                (int) $non_null_brands[0] === (int) $term->term_id &&
                count( $distinct_ids ) === 1
            ) {
                $link = get_term_link( $term );
                if ( ! is_wp_error( $link ) ) {
                    self::log( "Redirecting '{$q_trim}' to brand archive {$link}" );
                    wp_safe_redirect( $link, 302 );
                    exit;
                }
            }
            return;
        }
        if ( 'category' === $scope ) {
            // Category redirect: only if EVERY result has this category term ID.
            $rows = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT product_id, cat_term_ids FROM {$table} WHERE product_id IN ($in)",
                    $product_ids
                ),
                ARRAY_A
            );
            if ( empty( $rows ) ) {
                return;
            }
            $required_cat_id = (int) $term->term_id;
            $by_product      = [];
            foreach ( $rows as $r ) {
                $by_product[ (int) $r['product_id'] ] = $r['cat_term_ids'];
            }
            foreach ( $product_ids as $pid ) {
                $pid = (int) $pid;
                if ( ! isset( $by_product[ $pid ] ) ) {
                    // Missing index row – be conservative, no redirect.
                    return;
                }
                $list = $by_product[ $pid ];
                if ( '' === $list || null === $list ) {
                    return;
                }
                $decoded = json_decode( $list, true );
                if ( ! is_array( $decoded ) ) {
                    return;
                }
                $decoded_ids = array_map( 'intval', $decoded );
                if ( ! in_array( $required_cat_id, $decoded_ids, true ) ) {
                    // At least one product doesn't have this category → no redirect.
                    return;
                }
            }
            // All products share this category.
            $link = get_term_link( $term );
            if ( ! is_wp_error( $link ) ) {
                self::log( "Redirecting '{$q_trim}' to category archive {$link}" );
                wp_safe_redirect( $link, 302 );
                exit;
            }
        }
    }

    /**
     * Expand tokens using synonyms (brand/category/global, with bidi logic).
     * Returns:
     *  - tokens: the ORIGINAL user tokens (for AND semantics)
     *  - map: token => [ variants to search for in index ]
     *
     * @param string $q The raw search query.
     * @return array{tokens: string[], map: array<string, string[]>}
     */
    public static function expand_tokens( string $q ) : array {
        global $wpdb;
        $q = trim( (string) $q );
        if ( '' === $q ) {
            return [ 'tokens' => [], 'map' => [] ];
        }
        $normalized_q = strtolower( remove_accents( $q ) );
        // Detect if full query matches a brand or category term exactly.
        $scope = '';
        $term  = null;
        $term = get_term_by( 'name', $q, 'berocket_brand' );
        if ( ! $term || is_wp_error( $term ) ) {
            $term = get_term_by( 'slug', sanitize_title( $q ), 'berocket_brand' );
        }
        if ( $term && ! is_wp_error( $term ) ) {
            $scope = 'brand';
        } else {
            $term = get_term_by( 'name', $q, 'product_cat' );
            if ( ! $term || is_wp_error( $term ) ) {
                $term = get_term_by( 'slug', sanitize_title( $q ), 'product_cat' );
            }
            if ( $term && ! is_wp_error( $term ) ) {
                $scope = 'category';
            }
        }
        // If user searched exactly for a brand/category name, treat whole query as one token.
        if ( $term && $scope ) {
            $tokens = [ $normalized_q ];
        } else {
            $tokens = array_values( array_unique( array_filter( preg_split( '/\s+/', $normalized_q ) ) ) );
        }
        if ( empty( $tokens ) ) {
            return [ 'tokens' => [], 'map' => [] ];
        }
        $syn_table = self::t_synonyms();
        $map       = [];
        foreach ( $tokens as $t ) {
            $map[ $t ] = [ $t ];
        }
        /**
         * 1) BRAND / CATEGORY: synonym → canonical expansion
         *    (user typed the synonym itself, scope = 'brand' or 'category').
         */
        $rows_bc      = [];
        $placeholders = implode( ',', array_fill( 0, count( $tokens ), '%s' ) );
        if ( $placeholders ) {
            $sql_bc = "SELECT scope, term_id, synonym, bidi FROM {$syn_table}
                    WHERE active=1 AND scope IN ('brand','category') AND synonym IN ($placeholders)";
            $rows_bc = $wpdb->get_results( $wpdb->prepare( $sql_bc, $tokens ), ARRAY_A );
        }
        foreach ( $rows_bc as $r ) {
            $synonym    = strtolower( remove_accents( $r['synonym'] ) );
            if ( ! isset( $map[ $synonym ] ) ) {
                // synonym is not one of the user tokens; skip.
                continue;
            }
            $scope_row = $r['scope'];
            $term_id   = (int) $r['term_id'];
            if ( 'brand' === $scope_row || 'category' === $scope_row ) {
                $tax      = ( 'brand' === $scope_row ) ? 'berocket_brand' : 'product_cat';
                $term_row = get_term( $term_id, $tax );
                if ( $term_row && ! is_wp_error( $term_row ) ) {
                    $canonical_forms = [
                        $term_row->slug,
                        sanitize_title( $term_row->name ),
                        strtolower( remove_accents( $term_row->name ) ),
                    ];
                    $canonical_forms = array_unique( $canonical_forms );
                    $map[ $synonym ] = array_unique( array_merge( $map[ $synonym ], $canonical_forms ) );
                }
            }
        }
        /**
         * 2) BRAND / CATEGORY: canonical name → synonyms when bidi=1 and query is exactly that term.
         */
        if ( $term && $scope ) {
            $syn_rows = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT synonym FROM {$syn_table} WHERE active=1 AND scope=%s AND term_id=%d AND bidi=1",
                    $scope,
                    (int) $term->term_id
                ),
                ARRAY_A
            );
            $token_key = $tokens[0]; // only token when exact term match.
            foreach ( $syn_rows as $sr ) {
                $syn_norm            = strtolower( remove_accents( $sr['synonym'] ) );
                $map[ $token_key ][] = $syn_norm;
            }
            $map[ $token_key ] = array_values( array_unique( $map[ $token_key ] ) );
        }
        /**
         * 3) GLOBAL groups (scope = 'global'):
         *    - Defined as search word + synonyms in the UI, stored as rows sharing term_id.
         *    - When bidi=1, each token in the group expands to ALL tokens in that group.
         *    - When bidi=0, group is ignored by expansion (safe, non-surprising default).
         */
        $rows_global = [];
        if ( $placeholders ) {
            $sql_g = "SELECT term_id, synonym, bidi FROM {$syn_table}
                    WHERE active=1 AND scope='global' AND synonym IN ($placeholders)";
            $rows_global = $wpdb->get_results( $wpdb->prepare( $sql_g, $tokens ), ARRAY_A );
        }
        if ( ! empty( $rows_global ) ) {
            $group_ids = [];
            foreach ( $rows_global as $rg ) {
                $group_ids[ (int) $rg['term_id'] ] = true;
            }
            $group_ids = array_keys( $group_ids );
            if ( ! empty( $group_ids ) ) {
                $in_groups      = implode( ',', array_fill( 0, count( $group_ids ), '%d' ) );
                $sql_all_groups = "SELECT term_id, synonym, bidi FROM {$syn_table}
                        WHERE active=1 AND scope='global' AND term_id IN ($in_groups)";
                $all_g          = $wpdb->get_results( $wpdb->prepare( $sql_all_groups, $group_ids ), ARRAY_A );
                $groups         = [];
                foreach ( $all_g as $row ) {
                    $gid = (int) $row['term_id'];
                    if ( ! isset( $groups[ $gid ] ) ) {
                        $groups[ $gid ] = [ 'tokens' => [], 'bidi' => 0 ];
                    }
                    $tok                     = strtolower( remove_accents( $row['synonym'] ) );
                    $groups[ $gid ]['tokens'][] = $tok;
                    if ( (int) $row['bidi'] === 1 ) {
                        $groups[ $gid ]['bidi'] = 1;
                    }
                }
                // Apply groups: only when bidi=1.
                foreach ( $groups as $g ) {
                    if ( empty( $g['tokens'] ) || 1 !== (int) $g['bidi'] ) {
                        continue;
                    }
                    $group_tokens = array_values( array_unique( $g['tokens'] ) );
                    foreach ( $tokens as $t ) {
                        if ( in_array( $t, $group_tokens, true ) ) {
                            $map[ $t ] = array_values(
                                array_unique(
                                    array_merge( $map[ $t ] ?? [ $t ], $group_tokens )
                                )
                            );
                        }
                    }
                }
            }
        }
        return [ 'tokens' => $tokens, 'map' => $map ];
    }

    /**
     * Search the index table only; return array of product IDs (respect Woo
     * "hide out of stock" setting). Within a single token: OR across fields
     * (title_norm, brand_slug, brand_name, cat_slugs). Across different
     * tokens: AND between tokens. This function does not modify the
     * existing WooCommerce queries directly; instead it returns IDs for
     * the caller to apply via `pre_get_posts` or the WC datastore filter.
     *
     * @param string $q The search query.
     * @return array<int> List of matching product IDs.
     */
    public static function search_index( string $q ) : array {
        global $wpdb;
        $exp = self::expand_tokens( $q );
        if ( empty( $exp['tokens'] ) ) {
            return [];
        }
        $table       = self::t_index();
        $respect_oos = ( get_option( 'woocommerce_hide_out_of_stock_items' ) === 'yes' ) ? 1 : 0;
        $where_parts = [];
        $params      = [];
        foreach ( $exp['tokens'] as $t ) {
            $vars = $exp['map'][ $t ] ?? [ $t ];
            $sub  = [];
            foreach ( $vars as $v ) {
                $like     = '%' . $wpdb->esc_like( $v ) . '%';
                $sub[]    = 'title_norm LIKE %s';
                $params[] = $like;
                $sub[]    = 'brand_slug LIKE %s';
                $params[] = $like;
                $sub[]    = 'brand_name LIKE %s';
                $params[] = $like;
                $sub[]    = 'cat_slugs  LIKE %s';
                $params[] = $like;
            }
            $where_parts[] = '( ' . implode( ' OR ', $sub ) . ' )';
        }
        $where = implode( ' AND ', $where_parts );
        // Use ORDER BY title for predictable ordering; limit to 500 results to
        // avoid excessive memory usage when a very broad query is made.
        $sql   = "SELECT product_id FROM {$table} WHERE (in_stock=1 OR %d=0) AND {$where} ORDER BY title ASC LIMIT 500";
        array_unshift( $params, $respect_oos ); // first %d for in_stock gate.
        /**
         * Using the second parameter to $wpdb->prepare() as an array is
         * supported in modern WordPress and ensures that all placeholders
         * are properly substituted. If you are running on an older
         * WordPress version (<4.1) you may need to replace this with
         * call_user_func_array().
         */
        return $wpdb->get_col( $wpdb->prepare( $sql, $params ) );
    }
}