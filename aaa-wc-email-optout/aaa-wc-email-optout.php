<?php
/**
 * Plugin Name: AAA WC Email Opt-out by Meta (XHV98-ADMIN)
 * Description: Blocks WooCommerce customer emails to users with meta 'uuu_disable_emails' truthy. Also removes them at wp_mail level.
 * Author: AAA Workflow
 * Version: 1.0.0
 *
 * File Path: wp-content/plugins/aaa-wc-email-optout/aaa-wc-email-optout.php
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* Optional: silence during WP-CLI runs to avoid notices in maintenance scripts */
if ( defined( 'WP_CLI' ) && WP_CLI ) { return; }

define( 'AAA_EMAIL_OPTOUT_DEBUG', true );
define( 'AAA_EMAIL_OPTOUT_META',  'uuu_disable_emails' );

/** Check if a user has opted out. */
function aaa_wc_email_optout_user_opted_out( $user_id ) {
    if ( ! $user_id ) return false;
    return (bool) get_user_meta( $user_id, AAA_EMAIL_OPTOUT_META, true );
}

/** Extract plain email from "Name <email@domain>" or raw address. */
function aaa_wc_email_optout_extract_addr( $raw ) {
    $raw = trim( (string) $raw );
    if ( preg_match( '/<([^>]+)>/', $raw, $m ) ) {
        return sanitize_email( trim( $m[1] ) );
    }
    return sanitize_email( $raw );
}

/**
 * 1) WooCommerce-level block (preferred): blank recipient for customer emails
 *    when the order/user has opted out. Runs for every Woo email ID.
 */
add_action( 'woocommerce_email', function( $email ) {

    // >>> Fix: only proceed for actual email classes, not WC_Emails manager
    if ( ! ( $email instanceof WC_Email ) ) {
        return;
    }
    // <<<

    add_filter( 'woocommerce_email_recipient_' . $email->id, function( $recipient, $object, $email ) {

        // Only affect customer-facing emails.
        if ( ! ( $email instanceof WC_Email ) || ! $email->is_customer_email() ) {
            return $recipient;
        }

        // Gather possible user IDs tied to this email.
        $user_ids = array();

        if ( $object instanceof WC_Order ) {
            $uid = (int) $object->get_user_id();
            if ( $uid ) $user_ids[] = $uid;

            // Also try billing email -> user lookup (covers cases where order email differs).
            $billing = $object->get_billing_email();
            if ( $billing ) {
                $u = get_user_by( 'email', $billing );
                if ( $u ) $user_ids[] = (int) $u->ID;
            }
        } elseif ( $object instanceof WP_User ) {
            $user_ids[] = (int) $object->ID;
        }

        $user_ids = array_unique( array_filter( $user_ids ) );

        foreach ( $user_ids as $uid ) {
            if ( aaa_wc_email_optout_user_opted_out( $uid ) ) {
                if ( AAA_EMAIL_OPTOUT_DEBUG ) {
                    error_log( "[EMAIL-OPTOUT] Blocked WC email {$email->id} to user {$uid} ({$recipient})" );
                }
                return ''; // No recipient => Woo skips sending this email.
            }
        }

        return $recipient;
    }, 10, 3 );
}, 9 );

/**
 * 2) wp_mail safety net: remove opted-out recipients from the "to" list.
 *    If all are removed, short-circuit sending via pre_wp_mail.
 */
add_filter( 'wp_mail', function( $atts ) {
    if ( empty( $atts['to'] ) ) return $atts;

    $to    = is_array( $atts['to'] ) ? $atts['to'] : explode( ',', $atts['to'] );
    $clean = array();

    foreach ( $to as $raw ) {
        $addr = aaa_wc_email_optout_extract_addr( $raw );
        if ( $addr ) {
            $user = get_user_by( 'email', $addr );
            if ( $user && aaa_wc_email_optout_user_opted_out( $user->ID ) ) {
                if ( AAA_EMAIL_OPTOUT_DEBUG ) {
                    error_log( "[EMAIL-OPTOUT] Removed recipient {$addr} via wp_mail filter" );
                }
                continue;
            }
        }
        $clean[] = $raw; // Keep original formatting.
    }

    if ( empty( $clean ) ) {
        // Mark for short-circuit in pre_wp_mail.
        add_filter( 'pre_wp_mail', function() { return false; }, 99 );
    } else {
        $atts['to'] = $clean;
    }

    return $atts;
}, 10 );

/** 3) Short-circuit sender early if every recipient was opted-out (WP 5.7+). */
add_filter( 'pre_wp_mail', function( $pre, $atts ) {
    if ( isset( $atts['to'] ) ) {
        $to = is_array( $atts['to'] ) ? $atts['to'] : explode( ',', $atts['to'] );
        $total = 0; $blocked = 0;

        foreach ( $to as $raw ) {
            $addr = aaa_wc_email_optout_extract_addr( $raw );
            $total++;
            $user = $addr ? get_user_by( 'email', $addr ) : false;
            if ( $user && aaa_wc_email_optout_user_opted_out( $user->ID ) ) {
                $blocked++;
            }
        }

        if ( $total > 0 && $blocked === $total ) {
            if ( AAA_EMAIL_OPTOUT_DEBUG ) {
                error_log( "[EMAIL-OPTOUT] Short-circuited wp_mail: all recipients opted-out" );
            }
            return false; // Cancel send.
        }
    }
    return $pre;
}, 10, 2 );
