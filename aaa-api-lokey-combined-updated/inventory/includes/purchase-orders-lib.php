<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( defined( 'LOKEY_INV_PO_LIB_LOADED' ) ) { return; }
define( 'LOKEY_INV_PO_LIB_LOADED', true );

function lokey_inv_po_allowed_statuses() {
    $list = [ 'atum_pending', 'atum_ordered', 'atum_onthewayin', 'atum_receiving', 'atum_received', 'trash' ];
    return apply_filters( 'lokey_inv_po_allowed_statuses', $list );
}

function lokey_inv_po_default_status() {
    return apply_filters( 'lokey_inv_po_default_status', 'atum_ordered' );
}

function lokey_inv_po_permission() {
    if ( ! function_exists( 'lokey_require_jwt_auth' ) ) {
        return new WP_Error( 'lokey_auth_missing', 'Auth helper not loaded.', [ 'status' => 500 ] );
    }
    $auth = lokey_require_jwt_auth();
    if ( is_wp_error( $auth ) ) { return $auth; }
    $cap = apply_filters( 'lokey_inv_po_required_capability', 'manage_woocommerce' );
    if ( $cap && ! current_user_can( $cap ) ) {
        return new WP_Error( 'forbidden', 'Insufficient permissions.', [ 'status' => 403 ] );
    }
    return true;
}

function lokey_inv_po_normalize_status( $raw ) {
    $v = strtolower( trim( sanitize_text_field( (string) $raw ) ) );
    $v = preg_replace( '/^wc-/', '', $v );
    if ( strpos( $v, 'atum_' ) === 0 ) { return $v; }
    $k = preg_replace( '/[^a-z]/', '', $v );
    $map = [ 'pending'=>'atum_pending','ordered'=>'atum_ordered','onthewayin'=>'atum_onthewayin','receiving'=>'atum_receiving','received'=>'atum_received','trash'=>'trash' ];
    return $map[ $k ] ?? $v;
}

function lokey_inv_po_sanitize_date_expected( $raw ) {
    $raw = sanitize_text_field( (string) $raw );
    if ( $raw === '' ) { return ''; }
    if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $raw ) ) { return $raw . 'T00:00:00'; }
    if ( preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(:\d{2})?/', $raw ) ) {
        return preg_replace( '/(T\d{2}:\d{2})(?!:\d{2})/', '$1:00', $raw );
    }
    $ts = strtotime( $raw );
    return $ts ? gmdate( 'Y-m-d\TH:i:s', $ts ) : '';
}

function lokey_inv_po_sanitize_line_items( $items, &$warnings ) {
    if ( ! is_array( $items ) ) { return []; }
    $out = [];
    foreach ( $items as $i => $item ) {
        if ( ! is_array( $item ) ) { $warnings[] = "line_items[$i] ignored: not an object"; continue; }
        if ( isset( $item['cost'] ) && ! isset( $item['purchase_price'] ) ) { $item['purchase_price'] = $item['cost']; }
        $pid = absint( $item['product_id'] ?? 0 );
        if ( $pid <= 0 ) { $warnings[] = "line_items[$i] ignored: missing product_id"; continue; }
        $qty = isset( $item['quantity'] ) ? max( 1, absint( $item['quantity'] ) ) : 1;
        $line = [ 'product_id' => $pid, 'quantity' => $qty ];
        $vid = absint( $item['variation_id'] ?? 0 );
        if ( $vid ) { $line['variation_id'] = $vid; }
        if ( isset( $item['purchase_price'] ) && $item['purchase_price'] !== '' && is_numeric( $item['purchase_price'] ) ) {
            $line['purchase_price'] = (float) $item['purchase_price'];
        }
        $out[] = $line;
    }
    return $out;
}

function lokey_inv_po_sanitize_payload( $payload, &$warnings, $mode = 'create' ) {
    $warnings = [];
    $payload  = is_array( $payload ) ? $payload : [];
    if ( ! empty( $payload['items'] ) && empty( $payload['line_items'] ) ) { $payload['line_items'] = $payload['items']; unset( $payload['items'] ); }
    if ( ! empty( $payload['supplier_id'] ) && empty( $payload['supplier'] ) ) { $payload['supplier'] = $payload['supplier_id']; unset( $payload['supplier_id'] ); }

    $readonly = [ 'id','date_created','date_created_gmt','date_modified','date_modified_gmt','discount_total','discount_tax','shipping_total','shipping_tax','cart_tax','total','total_tax','prices_include_tax','tax_lines','date_completed','date_completed_gmt','date_expected_gmt' ];
    foreach ( $readonly as $k ) { if ( array_key_exists( $k, $payload ) ) { unset( $payload[ $k ] ); $warnings[] = "$k removed (read-only)"; } }

    $allowed = [ 'status','currency','supplier','multiple_suppliers','date_expected','line_items','shipping_lines','fee_lines','meta_data','description' ];
    foreach ( array_keys( $payload ) as $k ) { if ( ! in_array( $k, $allowed, true ) ) { unset( $payload[ $k ] ); $warnings[] = "$k removed (not allowed)"; } }

    if ( array_key_exists( 'status', $payload ) ) {
        $payload['status'] = lokey_inv_po_normalize_status( $payload['status'] );
        if ( $payload['status'] === '' ) { unset( $payload['status'] ); }
    }
    if ( $mode === 'create' ) { $payload['status'] = $payload['status'] ?? lokey_inv_po_default_status(); }
    if ( isset( $payload['status'] ) && ! in_array( $payload['status'], lokey_inv_po_allowed_statuses(), true ) ) {
        return new WP_Error( 'invalid_status', 'Invalid purchase order status.', [ 'status' => 400, 'valid_statuses' => lokey_inv_po_allowed_statuses() ] );
    }

    if ( isset( $payload['currency'] ) ) {
        $cur = strtoupper( sanitize_text_field( (string) $payload['currency'] ) );
        if ( ! preg_match( '/^[A-Z]{3}$/', $cur ) ) { unset( $payload['currency'] ); $warnings[] = 'currency removed (invalid)'; }
        else { $payload['currency'] = $cur; }
    }

    if ( isset( $payload['supplier'] ) ) {
        $payload['supplier'] = absint( $payload['supplier'] );
        if ( $payload['supplier'] <= 0 ) { unset( $payload['supplier'] ); $warnings[] = 'supplier removed (invalid)'; }
    }

    if ( isset( $payload['multiple_suppliers'] ) ) { $payload['multiple_suppliers'] = (bool) $payload['multiple_suppliers']; }

    if ( isset( $payload['date_expected'] ) ) {
        $norm = lokey_inv_po_sanitize_date_expected( $payload['date_expected'] );
        if ( $norm === '' ) { unset( $payload['date_expected'] ); $warnings[] = 'date_expected removed (invalid)'; }
        else { $payload['date_expected'] = $norm; }
    }

    if ( isset( $payload['line_items'] ) ) { $payload['line_items'] = lokey_inv_po_sanitize_line_items( $payload['line_items'], $warnings ); }
    if ( $mode === 'create' && empty( $payload['line_items'] ) ) {
        return new WP_Error( 'line_items_required', 'line_items is required to create a purchase order.', [ 'status' => 400 ] );
    }

    foreach ( [ 'shipping_lines','fee_lines','meta_data' ] as $k ) {
        if ( isset( $payload[ $k ] ) && ! is_array( $payload[ $k ] ) ) { unset( $payload[ $k ] ); $warnings[] = "$k removed (must be array)"; }
    }

    if ( isset( $payload['description'] ) ) { $payload['description'] = wp_kses_post( (string) $payload['description'] ); }
    return $payload;
}
