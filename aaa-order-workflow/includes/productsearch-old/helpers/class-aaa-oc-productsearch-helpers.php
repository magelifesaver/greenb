<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/productsearch/helpers/class-aaa-oc-productsearch-helpers.php
 * Purpose: Helpers for tokenization, synonyms expansion, index SQL search, and archive redirects.
 * Version: 1.2.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'AAA_OC_PS_HELPERS_DEBUG' ) ) {
	define( 'AAA_OC_PS_HELPERS_DEBUG', false );
}

class AAA_OC_ProductSearch_Helpers {

	private static function t_index() {
		global $wpdb;
		return $wpdb->prefix . AAA_OC_ProductSearch_Table_Installer::T_INDEX;
	}

	private static function t_synonyms() {
		global $wpdb;
		return $wpdb->prefix . AAA_OC_ProductSearch_Table_Installer::T_SYNONYMS;
	}

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
	 * Legacy: single-word → category archive redirect (brands are skipped).
	 * Now mostly unused by search hooks, kept for back-compat.
	 */
	public static function maybe_redirect_to_term_archive( string $q ) {
		$tokens = array_values(
			array_filter(
				preg_split( '/\s+/', strtolower( $q ) )
			)
		);
		if ( count( $tokens ) !== 1 ) {
			return;
		}

		$tok = $tokens[0];

		// If this is a brand, DO NOT redirect. Let the index + synonyms handle it.
		$brand = get_term_by( 'slug', sanitize_title( $tok ), 'berocket_brand' );
		if ( ! $brand ) {
			$brand = get_term_by( 'name', $tok, 'berocket_brand' );
		}
		if ( $brand && ! is_wp_error( $brand ) ) {
			self::log( "maybe_redirect_to_term_archive: '{$q}' is brand; skipping redirect." );
			return;
		}

		// Categories can still redirect here if someone else calls this.
		$term = get_term_by( 'slug', sanitize_title( $tok ), 'product_cat' );
		if ( ! $term ) {
			$term = get_term_by( 'name', $tok, 'product_cat' );
		}

		if ( $term && ! is_wp_error( $term ) ) {
			$link = get_term_link( $term );
			if ( ! is_wp_error( $link ) ) {
				self::log( "Redirecting '{$q}' to category archive {$link}" );
				wp_safe_redirect( $link, 302 );
				exit;
			}
		}
	}

	/**
	 * New: redirect ONLY if all results share the same brand (for brand query)
	 * or all share the same category (for category query).
	 */
	public static function maybe_redirect_by_results( string $q, array $product_ids ) {
		if ( empty( $product_ids ) ) {
			return;
		}

		global $wpdb;

		$q_trim       = trim( (string) $q );
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

			$distinct = array();
			foreach ( $brand_rows as $bid ) {
				$distinct[ (int) $bid ] = true;
			}

			$distinct_ids   = array_keys( $distinct );
			$non_null_brands = array_filter(
				$distinct_ids,
				static function ( $id ) {
					return (int) $id > 0;
				}
			);

			// All rows share the same non-null brand and it matches the brand term in the query.
			if ( count( $non_null_brands ) === 1 && (int) $non_null_brands[0] === (int) $term->term_id && count( $distinct_ids ) === 1 ) {
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
			$by_product      = array();
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
	 * Expand tokens using synonyms (global + brand/category-bound, with bidi logic).
	 * Returns:
	 *  - tokens: the ORIGINAL user tokens (for AND semantics)
	 *  - map: token => [ variants to search for in index ]
	 */
	public static function expand_tokens( string $q ) : array {
		global $wpdb;

		$q = trim( (string) $q );
		if ( '' === $q ) {
			return array( 'tokens' => array(), 'map' => array() );
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
			$tokens = array( $normalized_q );
		} else {
			$tokens = array_values(
				array_unique(
					array_filter(
						preg_split( '/\s+/', $normalized_q )
					)
				)
			);
		}

		if ( empty( $tokens ) ) {
			return array( 'tokens' => array(), 'map' => array() );
		}

		$syn_table = self::t_synonyms();
		$map       = array();

		foreach ( $tokens as $t ) {
			$map[ $t ] = array( $t );
		}

		// 1) Synonym → canonical expansion (user typed the synonym itself).
		$rows = array();
		if ( ! empty( $tokens ) ) {
			$placeholders = implode( ',', array_fill( 0, count( $tokens ), '%s' ) );
			$sql          = "SELECT scope, term_id, synonym, bidi FROM {$syn_table} WHERE active=1 AND synonym IN ($placeholders)";
			$rows         = $wpdb->get_results( $wpdb->prepare( $sql, $tokens ), ARRAY_A );
		}

		foreach ( $rows as $r ) {
			$synonym = strtolower( remove_accents( $r['synonym'] ) );
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
					$canonical_forms = array(
						$term_row->slug,
						sanitize_title( $term_row->name ),
						strtolower( remove_accents( $term_row->name ) ),
					);
					$canonical_forms   = array_unique( $canonical_forms );
					$map[ $synonym ]   = array_unique( array_merge( $map[ $synonym ], $canonical_forms ) );
				}
			}
		}

		// 2) Canonical brand/category name → synonyms when bidi=1 and query is exactly that term.
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

		return array(
			'tokens' => $tokens,
			'map'    => $map,
		);
	}

	/** Search the index table only; return array of product IDs (respect Woo "hide oos"). */
	public static function search_index( string $q ) : array {
		global $wpdb;

		$exp = self::expand_tokens( $q );
		if ( empty( $exp['tokens'] ) ) {
			return array();
		}

		$table       = self::t_index();
		$respect_oos = ( get_option( 'woocommerce_hide_out_of_stock_items' ) === 'yes' ) ? 1 : 0;

		$where_parts = array();
		$params      = array();

		foreach ( $exp['tokens'] as $t ) {
			$vars = $exp['map'][ $t ] ?? array( $t );
			$sub  = array();

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
		$sql   = "SELECT product_id FROM {$table} WHERE (in_stock=1 OR %d=0) AND {$where} ORDER BY title ASC LIMIT 500";
		array_unshift( $params, $respect_oos ); // first %d for in_stock gate.

		return $wpdb->get_col( $wpdb->prepare( $sql, $params ) );
	}
}
