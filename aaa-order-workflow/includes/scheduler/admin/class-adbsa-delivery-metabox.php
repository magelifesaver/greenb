<?php
/**
 * File: admin/class-adbsa-delivery-metabox.php
 * Purpose: Admin metabox for editing Delivery Schedule on orders.
 * Version: 1.4.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'ADBSA_Delivery_Metabox' ) ) :

final class ADBSA_Delivery_Metabox {

    const NONCE = 'adbsa_delivery_metabox_nonce';

    public function __construct() {
        add_action( 'add_meta_boxes', [ $this, 'add_box' ] );
        add_action( 'save_post',      [ $this, 'save'     ], 20, 2 );
    }

    public function add_box() {
        add_meta_box(
            'adbsa_delivery',
            __( 'Delivery Schedule', 'adbsa' ),
            [ $this, 'render' ],
            'shop_order',
            'side',
            'high'
        );
    }

    public function render( $post ) {
        $order = wc_get_order( $post->ID );
        if ( ! $order ) return;

        wp_nonce_field( self::NONCE, self::NONCE );

        if ( class_exists( 'ADBSA_Delivery_Field_Renderer' ) ) {
            ADBSA_Delivery_Field_Renderer::render_fields( $order, 'adbsa' );
        }
    }

    public function save( $post_id, $post ) {
        if ( $post->post_type !== 'shop_order' ) return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! isset( $_POST[ self::NONCE ] ) || ! wp_verify_nonce( $_POST[ self::NONCE ], self::NONCE ) ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        $order = wc_get_order( $post_id );
        if ( ! $order ) return;

        $dateYmd = sanitize_text_field( $_POST['adbsa_date'] ?? '' ); // Y-m-d
        $fromH24 = sanitize_text_field( $_POST['adbsa_from'] ?? '' ); // HH:mm
        $toH24   = sanitize_text_field( $_POST['adbsa_to']   ?? '' ); // HH:mm

        $tz = wp_timezone();
        $changed_bits = [];

        // ---- DATE ----
        if ( $dateYmd && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $dateYmd ) ) {
            $ts_midnight = strtotime( $dateYmd . ' 00:00:00 ' . wp_timezone_string() );
            $loc_label   = wp_date( 'l, F j, Y', $ts_midnight, $tz );

            $order->update_meta_data( 'delivery_date',           $ts_midnight );
            $order->update_meta_data( 'delivery_date_formatted', $dateYmd );
            $order->update_meta_data( 'delivery_date_locale',    $loc_label );
            $order->update_meta_data( '_wc_other/adbsa/delivery-date', $dateYmd );

            $changed_bits[] = 'date ' . $loc_label;
        }

        // ---- TIME ----
        if ( $fromH24 && $toH24 ) {
            $fromDT = DateTime::createFromFormat( 'H:i', $fromH24, $tz );
            $toDT   = DateTime::createFromFormat( 'H:i', $toH24, $tz );
            $from12 = $fromDT ? strtolower( $fromDT->format( 'g:i a' ) ) : $fromH24;
            $to12   = $toDT   ? strtolower( $toDT->format( 'g:i a' ) )   : $toH24;

            $range = sprintf( 'From %s - To %s', $from12, $to12 );

            $order->update_meta_data( 'delivery_time',       $from12 );
            $order->update_meta_data( 'delivery_time_range', $range );
            $order->update_meta_data( '_wc_other/adbsa/delivery-time', $range );

            $changed_bits[] = 'time ' . $range;
        }

        if ( $changed_bits ) {
            $order->save();

            // Add WC order note (admin metabox)
            $admin = wp_get_current_user();
            $when  = current_time( 'mysql' );
            $msg   = sprintf(
                'Delivery updated in admin by %s at %s: %s.',
                $admin ? $admin->display_name : 'system',
                $when,
                implode( ', ', $changed_bits )
            );
            $order->add_order_note( $msg );
        }
    }
}

new ADBSA_Delivery_Metabox();

endif;
