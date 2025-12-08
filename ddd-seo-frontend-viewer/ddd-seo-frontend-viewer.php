<?php
/**
 * Plugin Name: Yoast SEO & Social Fields Viewer
 * Description: Adds a meta box on WooCommerce product edit screen to view and edit Yoast SEO + Social fields (7 fields total).
 * Version: 1.0.0
 * Author: Workflow Dev
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Yoast_SEO_Social_Fields_Viewer {

    private static $meta_keys = [
        '_yoast_wpseo_title'                => 'Yoast SEO Title',
        '_yoast_wpseo_focuskw'              => 'Yoast Focus Keyphrase',
        '_yoast_wpseo_metadesc'             => 'Yoast Meta Description',
        '_yoast_wpseo_opengraph-title'      => 'Yoast Facebook Title',
        '_yoast_wpseo_opengraph-description'=> 'Yoast Facebook Description',
        '_yoast_wpseo_twitter-title'        => 'Yoast Twitter Title',
        '_yoast_wpseo_twitter-description'  => 'Yoast Twitter Description',
    ];

    public static function init() {
        add_action( 'add_meta_boxes', [ __CLASS__, 'add_meta_box' ] );
        add_action( 'save_post_product', [ __CLASS__, 'save_fields' ] );
    }

    public static function add_meta_box() {
        add_meta_box(
            'yoast_seo_social_fields',
            'Yoast SEO & Social Fields',
            [ __CLASS__, 'render_meta_box' ],
            'product',
            'normal',
            'default'
        );
    }

    public static function render_meta_box( $post ) {
        wp_nonce_field( 'yoast_meta_fields_nonce', 'yoast_meta_fields_nonce_field' );

        echo '<table class="form-table"><tbody>';
        foreach ( self::$meta_keys as $key => $label ) {
            $value = get_post_meta( $post->ID, $key, true );
            echo '<tr>';
            echo '<th><label for="'. esc_attr($key) .'">'. esc_html($label) .'</label></th>';
            echo '<td><input type="text" style="width:100%;" id="'. esc_attr($key) .'" name="'. esc_attr($key) .'" value="'. esc_attr($value) .'"/></td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    }

    public static function save_fields( $post_id ) {
        if ( ! isset( $_POST['yoast_meta_fields_nonce_field'] ) ||
             ! wp_verify_nonce( $_POST['yoast_meta_fields_nonce_field'], 'yoast_meta_fields_nonce' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        foreach ( self::$meta_keys as $key => $label ) {
            if ( isset( $_POST[$key] ) ) {
                $value = sanitize_text_field( $_POST[$key] );
                update_post_meta( $post_id, $key, $value );
            }
        }
    }
}

Yoast_SEO_Social_Fields_Viewer::init();
