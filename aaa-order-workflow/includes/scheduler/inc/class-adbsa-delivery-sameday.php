<?php
/**
 * File: includes/scheduler/inc/class-adbsa-delivery-sameday.php
 * Purpose: Same-Day Delivery slot builder + saver (reads aaa_oc_options → adbsa_options_sameday).
 * Version: 1.6.0
 */
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'ADBSA_Delivery_SameDay' ) ) :

final class ADBSA_Delivery_SameDay {

    const DEBUG = true;

    private static function log( $msg, $ctx = null ) {
        if ( ! self::DEBUG ) return;
        $line = '[ADBSA][SameDay] ' . ( is_string( $msg ) ? $msg : wp_json_encode( $msg ) );
        if ( $ctx !== null ) $line .= ' | ' . ( is_string( $ctx ) ? $ctx : wp_json_encode( $ctx ) );
        error_log( $line );
    }
    private static function dlog( string $tag, array $data = [], array $ctx = [] ) : void {
        if ( class_exists('OWFDT_Debug') ) {
            $ctx = array_merge(['module'=>'scheduler','page'=> ( function_exists('is_checkout') && is_checkout() ? 'checkout' : 'front' )], $ctx);
            OWFDT_Debug::log( $tag, $data, $ctx );
        } elseif ( self::DEBUG ) {
            self::log( $tag, $data );
        }
    }

    /** Load Same-Day settings from custom table (aaa_oc_options → adbsa_options_sameday) */
    private static function load_settings() : array {
        global $wpdb;
        $table = $wpdb->prefix . 'aaa_oc_options';
        $val   = $wpdb->get_var( "SELECT option_value FROM {$table} WHERE option_key = 'adbsa_options_sameday' LIMIT 1" );
        $s     = maybe_unserialize( $val );
        return is_array( $s ) ? $s : [];
    }

    /** Create a DateTimeImmutable today with H:i from $str (falls back to $fallback) */
    private static function today_time( DateTimeImmutable $today, string $str, string $fallback, DateTimeZone $tz ) : DateTimeImmutable {
        $hhmm = preg_match('/^\d{1,2}:\d{2}$/', $str) ? $str : $fallback;
        [$H, $i] = array_map('intval', explode(':', $hhmm));
        return $today->setTime( $H, $i, 0, 0 );
    }

    /** Build slots for TODAY using settings from custom table */
    public static function build_slots() : array {
        $s = self::load_settings();
        if ( empty( $s['enabled'] ) ) {
            self::dlog('SLOTS_BUILD_RESULT', ['reason'=>'disabled','count'=>0]);
            return [];
        }

        $tz   = wp_timezone();
        $now  = new DateTimeImmutable( 'now', $tz );
        $today= $now->setTime( 0, 0, 0, 0 );

        // Start-time components
        $processing = ! empty( $s['processing_enabled'] ) ? (int) ( $s['processing_minutes'] ?? 0 ) : 0;
        $travel     = 0;
        if ( ! empty( $s['travel_enabled'] ) ) {
            $travel = ( ( $s['travel_mode'] ?? 'static' ) === 'static' ) ? (int) ( $s['travel_static'] ?? 0 ) : 0;
        }
        $buffer     = ! empty( $s['buffer_enabled'] ) ? (int) ( $s['buffer_minutes'] ?? 0 ) : 0;

        $minutes = max( 0, $processing + $travel + $buffer );
        $start   = $now->modify( "+{$minutes} minutes" );

        // Round to interval
        $round = max( 1, (int) ( $s['round_interval'] ?? 15 ) );
        $mod   = (int) $start->format('i') % $round;
        if ( $mod !== 0 ) $start = $start->modify( '+' . ( $round - $mod ) . ' minutes' );

        // Window and bounds
        $slot_len = max( 1, (int) ( $s['slot_length'] ?? 60 ) );
        $open     = self::today_time( $today, (string)( $s['open_time'] ?? '11:00' ), '11:00', $tz );
        $cutoff   = self::today_time( $today, (string)( $s['cutoff_time'] ?? '21:45' ), '21:45', $tz );

        // clamp start to open
        if ( $start < $open ) $start = $open;

        // Effective step
        $overlap_mode = ! empty( $s['overlap_mode'] );
        $overlap_min  = max( 0, (int) ( $s['overlap_minutes'] ?? 0 ) );
        $slot_step    = isset( $s['slot_step'] ) ? (int) $s['slot_step'] : 15;
        if ( $overlap_mode ) {
            $step = max( 1, $slot_len - $overlap_min ); // e.g. length 60, overlap 30 → step 30
        } else {
            // Treat 0 as “back-to-back (slot_len)”. Never allow 0 loop step.
            $step = max( 1, $slot_step <= 0 ? $slot_len : $slot_step );
        }

        self::dlog('SLOTS_BUILD_REQUEST', [
            'now' => $now->format('Y-m-d H:i'),
            'open'=> $open->format('Y-m-d H:i'),
            'cutoff'=> $cutoff->format('Y-m-d H:i'),
            'start'=> $start->format('Y-m-d H:i'),
            'processing_min'=>$processing,
            'travel_min'=>$travel,
            'buffer_min'=>$buffer,
            'round_interval'=>$round,
            'slot_length'=>$slot_len,
            'step'=>$step,
            'overlap_mode'=>$overlap_mode ? 1 : 0,
            'overlap_min'=>$overlap_min,
            'lastslot_start'=> $s['lastslot_start'] ?? '',
        ]);

        // No window available
        if ( $start >= $cutoff ) {
            self::dlog('SLOTS_BUILD_RESULT', ['reason'=>'after_cutoff','count'=>0]);
            return [];
        }

        $slots   = [];
        $current = $start;
        while ( $current < $cutoff ) {
            $end    = $current->modify( "+{$slot_len} minutes" );
            if ( $end > $cutoff ) $end = $cutoff;

            $label = sprintf(
                'From %s - To %s',
                strtolower( wp_date( 'g:i a', $current->getTimestamp(), $tz ) ),
                strtolower( wp_date( 'g:i a', $end->getTimestamp(), $tz ) )
            );

            $slots[] = [ 'value' => $label, 'label' => $label ];
            $current = $current->modify( "+{$step} minutes" );
        }

        // Last Slot rule if nothing fit and still before cutoff
        if ( empty( $slots ) ) {
            $raw = trim( (string) ( $s['lastslot_start'] ?? '' ) );
            if ( $raw !== '' ) {
                $last = self::today_time( $today, $raw, $raw, $tz );
                if ( $now < $cutoff && $last < $cutoff ) {
                    $label = sprintf(
                        'From %s - To %s',
                        strtolower( wp_date( 'g:i a', $last->getTimestamp(), $tz ) ),
                        strtolower( wp_date( 'g:i a', $cutoff->getTimestamp(), $tz ) )
                    );
                    $slots[] = [ 'value' => $label, 'label' => $label, '_lastslot' => true ];
                    self::log( 'Last Slot Rule applied', ['label'=>$label,'lastslot_start'=>$raw] );
                } else {
                    self::log( 'Last Slot Rule skipped (past cutoff or invalid lastslot_start)' );
                }
            }
        }

        self::dlog('SLOTS_BUILD_RESULT', ['reason'=>'ok','count'=>count($slots)]);
        return $slots;
    }

    /** Save metas from Blocks checkout (Same-Day mode) */
    public static function save_delivery_meta( $order, $request ) {
        try {
            if ( ! $order instanceof WC_Order ) return;

            $tz       = wp_timezone();
            $date_ymd = null;
            $time_raw = null;

            if ( class_exists('\Automattic\WooCommerce\Blocks\Package')
              && class_exists('\Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields') ) {
                try {
                    $container = \Automattic\WooCommerce\Blocks\Package::container();
                    $svc = $container->get(\Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields::class);
                    $date_ymd = (string) $svc->get_field_from_object('adbsa/delivery-date', $order, 'other');
                    $time_raw = (string) $svc->get_field_from_object('adbsa/delivery-time', $order, 'other');
                    self::log( 'Blocks values', compact('date_ymd','time_raw') );
                } catch (\Throwable $e) {
                    self::log( 'Blocks service error', $e->getMessage() );
                }
            }

            if ( ( ! $date_ymd || ! $time_raw ) && $request instanceof WP_REST_Request ) {
                $body  = $request->get_json_params();
                $extra = $body['order']['additional_fields'] ?? [];
                if ( ! $date_ymd && isset( $extra['adbsa/delivery-date'] ) ) $date_ymd = $extra['adbsa/delivery-date'];
                if ( ! $time_raw && isset( $extra['adbsa/delivery-time'] ) ) $time_raw = $extra['adbsa/delivery-time'];
                self::log( 'Request fallback', compact('date_ymd','time_raw') );
            }

            $ts_midnight = 0; $loc_label = '';
            if ( $date_ymd && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_ymd) ) {
                $ts_midnight = strtotime( $date_ymd . ' 00:00:00 ' . wp_timezone_string() );
                $loc_label   = wp_date( 'l, F j, Y', $ts_midnight, $tz );
            }

            $start12 = ''; $range = '';
            if ( $time_raw && preg_match('/from\s+(.+?)\s*(?:-|–|—)?\s*to\s+(.+)/i', $time_raw, $m ) ) {
                $start12 = trim( strtolower( $m[1] ) );
                $end12   = trim( strtolower( $m[2] ) );
                $range   = "From {$start12} - To {$end12}";
            }

            if ( $date_ymd && $start12 ) {
                $order->update_meta_data( 'delivery_date',           (int) $ts_midnight );
                $order->update_meta_data( 'delivery_date_formatted', $date_ymd );
                $order->update_meta_data( 'delivery_date_locale',    $loc_label ?: $date_ymd );
                $order->update_meta_data( 'delivery_time',           $start12 );
                $order->update_meta_data( 'delivery_time_range',     $range );
                $order->update_meta_data( '_wc_other/adbsa/delivery-date', $date_ymd );
                $order->update_meta_data( '_wc_other/adbsa/delivery-time', $range );
                $order->save();
                self::log( 'Saved metas', compact('date_ymd','range') );
            }
        } catch (\Throwable $e) {
            error_log('[ADBSA][SameDay] Fatal error: ' . $e->getMessage());
        }
    }
}

add_action(
    'woocommerce_store_api_checkout_update_order_from_request',
    ['ADBSA_Delivery_SameDay','save_delivery_meta'],
    20, 2
);

endif;
