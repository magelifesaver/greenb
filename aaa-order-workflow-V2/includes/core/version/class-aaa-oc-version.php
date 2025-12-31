<?php
/**
 * File: /includes/core/version/class-aaa-oc-version.php
 * Purpose: Central helpers for plugin + per-module versions (manual bump).
 * Triggers: none (static helpers only).
 * Version: 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! defined( 'AAA_OC_VERSION' ) ) {
    // Master plugin version (keep in main file if you prefer and remove this default)
    define( 'AAA_OC_VERSION', '2.0.0-core' );
}

final class AAA_OC_Version {

    /**
     * Return the declared "code" version for a module (manual).
     * Example: AAA_OC_Version::module('PAYCONFIRM')
     */
    public static function module( $slug_or_const ) : string {
        $const = is_string($slug_or_const) ? 'AAA_OC_' . strtoupper($slug_or_const) . '_VERSION' : (string)$slug_or_const;
        return defined($const) ? constant($const) : AAA_OC_VERSION;
    }

    /**
     * Asset version to use in wp_enqueue_* for a module.
     * Falls back to module version, then master version.
     */
    public static function assets( $slug ) : string {
        $c = 'AAA_OC_' . strtoupper($slug) . '_ASSETS_VER';
        if ( defined($c) ) return constant($c);
        return self::module($slug);
    }
}
