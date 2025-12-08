<?php
// File: /aaa-openia-order-creation-v4/includes/class-aaa-v4-product-matcher.php

if ( ! defined( 'ABSPATH' ) ) exit;

class AAA_V4_Product_Matcher {

    public static function find_product_id( $product_name ) {
        if ( empty( $product_name ) ) return false;

        $product_id = self::lookup_from_selected_table( $product_name );

        if ( $product_id ) {
            AAA_V4_Logger::log( "✅ Matched: '{$product_name}' → Product ID {$product_id}" );
        } else {
            AAA_V4_Logger::log( "❌ No match in selected table for '{$product_name}'" );
            $product_id = self::fallback_ai( $product_name );
        }

        return $product_id ? (int) $product_id : false;
    }

private static function lookup_from_selected_table( $input_value ) {
    global $wpdb;
    // --- decode any HTML entities (&#039; → ') before matching
    $search_value = html_entity_decode( $input_value, ENT_QUOTES | ENT_HTML5 );
    $settings  = get_option( 'aaa_v4_order_creator_settings', [] );
    $table_key = $settings['product_match_table'] ?? 'aaa_wf_v4_parser_index';

    if ( $table_key === 'lkd_wm_fields' ) {
        $table    = $wpdb->prefix . 'lkd_wm_fields';
        $column   = 'lkd_wm_og_name';
        $id_field = 'ID';
    } else {
        $table    = $wpdb->prefix . 'aaa_wf_v4_parser_index';
        $column   = 'wm_external_id';
        $id_field = 'post_id';
    }

    $sql = $wpdb->prepare(
        "SELECT {$id_field} 
	FROM {$table}
         WHERE {$column} = %s
         LIMIT 1
	 ", $search_value
    );

    AAA_V4_Logger::log( "Searching '{$search_value}' in {$table}. Match column: {$column}. SQL: {$sql}" );

    $post_id = $wpdb->get_var( $sql );
    return $post_id ? (int) $post_id : false;
}

    private static function fallback_ai( $name ) {
        if ( function_exists( 'aaa_v1_ai_lookup_product_id' ) ) {
            $id = aaa_v1_ai_lookup_product_id( $name );
            if ( $id ) {
                AAA_V4_Logger::log( "AI fallback matched '{$name}' → Product ID {$id}" );
                return $id;
            }
        }

        AAA_V4_Logger::log( "No fallback match for '{$name}'" );
        return false;
    }
}
